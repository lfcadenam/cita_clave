<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedSlotsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected string $token;
    protected Service $service;
    protected BookingAvailabilityService $availabilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::where('slug', 'paola-aguilera')->first();
        $this->admin = User::where('email', 'paola@nuvex-belleza.com')->first();
        $this->token = $this->admin->createToken('test-token', [$this->admin->role->value])->plainTextToken;

        $this->service = Service::where('tenant_id', $this->tenant->id)->where('is_active', true)->first();
        $this->availabilityService = app(BookingAvailabilityService::class);
    }

    protected function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
            'X-Tenant-Slug' => $this->tenant->slug,
        ], $extra);
    }

    /**
     * 1. Listar bloqueos existentes desde el panel administrativo.
     */
    public function test_admin_can_list_blocked_slots(): void
    {
        $response = $this->getJson('/api/v1/admin/blocked-slots', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'title', 'blocked_date', 'start_time', 'end_time', 'time_range', 'is_recurring', 'reason'],
                ],
            ]);
    }

    /**
     * 2. Crear un bloqueo de horario específico y verificar en base de datos.
     */
    public function test_admin_can_create_specific_date_blocked_slot(): void
    {
        $targetDate = Carbon::now()->addDays(3)->format('Y-m-d');

        $response = $this->postJson('/api/v1/admin/blocked-slots', [
            'title' => 'Cita Médica Personal',
            'blocked_date' => $targetDate,
            'start_time' => '10:00',
            'end_time' => '12:00',
            'is_recurring' => false,
            'reason' => 'Permiso médico en la mañana',
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Franja bloqueada creada exitosamente.',
                'data' => [
                    'title' => 'Cita Médica Personal',
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                    'is_recurring' => false,
                ],
            ]);

        $this->assertDatabaseHas('blocked_slots', [
            'tenant_id' => $this->tenant->id,
            'title' => 'Cita Médica Personal',
            'start_time' => '10:00',
            'end_time' => '12:00',
        ]);
    }

    /**
     * 3. El bloqueo elimina inmediatamente los turnos disponibles en el motor de disponibilidad.
     */
    public function test_blocked_slot_removes_slots_from_availability_engine(): void
    {
        $targetDate = Carbon::now()->addDays(4)->next(Carbon::TUESDAY);
        $dateStr = $targetDate->format('Y-m-d');

        // Disponibilidad previa sin el bloqueo
        $slotsBefore = $this->availabilityService->getAvailableSlots($this->service, $dateStr, false);
        $has10amBefore = collect($slotsBefore)->contains(fn ($s) => substr($s['start_time'], 0, 5) === '10:00');
        $this->assertTrue($has10amBefore, 'A las 10:00 AM debe haber disponibilidad antes de bloquear');

        // Crear bloqueo de 10:00 a 11:30
        BlockedSlot::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Mantenimiento de Cabina',
            'blocked_date' => $dateStr,
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'is_recurring' => false,
        ]);

        // Consultar disponibilidad post-bloqueo
        $slotsAfter = $this->availabilityService->getAvailableSlots($this->service, $dateStr, false);

        // Verificar que ningún turno caiga o se traslape con la franja bloqueada 10:00 - 11:30
        $duration = $this->service->duration_minutes;
        foreach ($slotsAfter as $slot) {
            $slotStart = Carbon::parse("{$dateStr} {$slot['start_time']}");
            $slotEnd = $slotStart->copy()->addMinutes($duration);

            $blockStart = Carbon::parse("{$dateStr} 10:00:00");
            $blockEnd = Carbon::parse("{$dateStr} 11:30:00");

            $overlaps = $slotStart->lt($blockEnd) && $slotEnd->gt($blockStart);
            $this->assertFalse($overlaps, "El turno {$slot['start_time']} no debe traslaparse con el bloqueo 10:00-11:30");
        }

        // Verificar que isSlotAvailable retorne false
        $this->assertFalse(
            $this->availabilityService->isSlotAvailable($this->service, $dateStr, '10:00'),
            '10:00 AM no debe estar disponible tras registrar el bloqueo'
        );
    }

    /**
     * 4. Si un cliente intenta reservar una cita en un horario bloqueado, el sistema rechaza la solicitud (422).
     */
    public function test_booking_attempt_during_blocked_slot_is_rejected(): void
    {
        $targetDate = Carbon::now()->addDays(5)->next(Carbon::THURSDAY);
        $dateStr = $targetDate->format('Y-m-d');

        // Bloqueo de 15:00 a 17:00
        BlockedSlot::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Capacitación del Personal',
            'blocked_date' => $dateStr,
            'start_time' => '15:00:00',
            'end_time' => '17:00:00',
            'is_recurring' => false,
        ]);

        // Intento de reserva pública
        $response = $this->postJson('/api/v1/appointments/book', [
            'service_id' => $this->service->id,
            'date' => $dateStr,
            'start_time' => '15:00',
            'client_name' => 'Clienta Test',
            'client_phone' => '3109998877',
            'client_email' => 'clienta@test.com',
            'payment_method' => PaymentMethod::NEQUI_TRANSFER->value,
        ], ['X-Tenant-Slug' => $this->tenant->slug]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 5. Eliminar el bloqueo libera de inmediato el horario en el calendario y motor de citas.
     */
    public function test_deleting_blocked_slot_releases_schedule(): void
    {
        $targetDate = Carbon::now()->addDays(6)->next(Carbon::FRIDAY);
        $dateStr = $targetDate->format('Y-m-d');

        $block = BlockedSlot::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Bloqueo Temporal',
            'blocked_date' => $dateStr,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'is_recurring' => false,
        ]);

        $this->assertFalse(
            $this->availabilityService->isSlotAvailable($this->service, $dateStr, '11:00')
        );

        // Eliminar vía API administrativa
        $deleteResponse = $this->deleteJson("/api/v1/admin/blocked-slots/{$block->id}", [], $this->authHeaders());
        $deleteResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Franja bloqueada eliminada correctamente.',
            ]);

        $this->assertDatabaseMissing('blocked_slots', [
            'id' => $block->id,
        ]);

        // Verificar que el horario se liberó
        $this->assertTrue(
            $this->availabilityService->isSlotAvailable($this->service, $dateStr, '11:00'),
            'El horario 11:00 AM debe volver a estar disponible tras eliminar el bloqueo'
        );
    }
}
