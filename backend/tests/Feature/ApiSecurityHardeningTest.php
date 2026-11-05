<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::first();
    }

    /**
     * 1.1 Ocultar / enmascarar información bancaria en nequi-info (Ley 1581 Habeas Data).
     */
    public function test_nequi_info_does_not_expose_document_id_and_masks_sensitive_data(): void
    {
        $response = $this->getJson('/api/v1/payments/nequi-info', [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);

        $response->assertSuccessful();
        $response->assertJsonMissing(['document_id']);
        $response->assertJson([
            'success' => true,
            'data' => [
                'holder_name' => 'Paola A. Aguilera C.',
            ],
        ]);
        $this->assertStringContainsString('*** ****', $response->json('data.account_number'));
    }

    /**
     * 1.2 Proteger confirmación de asistencia contra manipulación no autorizada.
     */
    public function test_confirm_attendance_fails_without_security_token_or_auth(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Camila Velez',
            'client_phone' => '3157774433',
            'appointment_date' => Carbon::now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_amount' => 80000,
            'deposit_amount' => 20000,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        // Sin token ni clave ni teléfono -> 403 Forbidden
        $response = $this->postJson("/api/v1/appointments/confirm-attendance/{$appointment->appointment_number}", [], [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);

        $response->assertStatus(403);
        $this->assertNull($appointment->fresh()->attendance_confirmed_at);

        // Con token incorrecto -> 403 Forbidden
        $badTokenRes = $this->postJson("/api/v1/appointments/confirm-attendance/{$appointment->appointment_number}", [
            'token' => 'token_invalido_xyz',
        ], [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);

        $badTokenRes->assertStatus(403);
        $this->assertNull($appointment->fresh()->attendance_confirmed_at);

        // Con token criptográfico legítimo generado para la cita -> 200 OK
        $validToken = $appointment->generateSecurityToken();
        $successRes = $this->postJson("/api/v1/appointments/confirm-attendance/{$appointment->appointment_number}", [
            'token' => $validToken,
        ], [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);

        $successRes->assertSuccessful();
        $this->assertNotNull($appointment->fresh()->attendance_confirmed_at);
    }

    /**
     * 1.3 Minimización de datos en consulta de estado (status).
     */
    public function test_appointment_status_masks_phone_and_name_and_omits_internal_id(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Daniela Morales Castro',
            'client_phone' => '3189998877',
            'appointment_date' => Carbon::now()->addDays(2)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'total_amount' => 90000,
            'deposit_amount' => 30000,
            'status' => AppointmentStatus::PENDING_VERIFICATION,
        ]);

        $response = $this->getJson("/api/v1/appointments/status/{$appointment->appointment_number}", [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);

        $response->assertSuccessful();
        $response->assertJsonMissing(['id']);
        
        $data = $response->json('data');
        $this->assertStringContainsString('****', $data['client_phone']);
        $this->assertEquals('Daniela M.', $data['client_name']);
    }

    /**
     * 2.2 Validación de API Key en endpoints frontend.
     */
    public function test_api_key_middleware_validates_x_api_key(): void
    {
        // Petición con API key correcta -> 200
        $validRes = $this->getJson('/api/v1/services', [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);
        $validRes->assertSuccessful();

        // Petición con API key inválida -> 401
        $invalidRes = $this->getJson('/api/v1/services', [
            'X-API-Key' => 'clave_falsa_hack_123',
        ]);
        $invalidRes->assertStatus(401);
        $invalidRes->assertJson([
            'success' => false,
            'message' => 'API key inválida o faltante en la cabecera X-API-Key.',
        ]);
    }

    /**
     * 5.2 & 5.3 Minimización de datos en perfil del tenant y slots (sin gap_after_minutes).
     */
    public function test_data_minimization_in_tenant_profile_and_availability(): void
    {
        // Perfil comercial del salón
        $profileRes = $this->getJson('/api/v1/tenant/profile', [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);
        $profileRes->assertSuccessful();
        $profileRes->assertJsonMissing(['id', 'email', 'phone', 'is_bold_enabled']);

        // Slots de disponibilidad
        $service = Service::first();
        $date = Carbon::now()->next(Carbon::WEDNESDAY)->toDateString();
        $slotsRes = $this->getJson("/api/v1/availability/slots?service_id={$service->id}&date={$date}", [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);

        $slotsRes->assertSuccessful();
        $slots = $slotsRes->json('data.slots');
        if (! empty($slots)) {
            $this->assertArrayNotHasKey('gap_after_minutes', $slots[0]);
        }
    }

    /**
     * 9. Endpoints de integración para n8n con su propia API Key dedicada.
     */
    public function test_n8n_integration_endpoints_require_dedicated_key(): void
    {
        // Sin clave de integraciones -> 401
        $unauthRes = $this->getJson('/api/v1/integrations/appointments/upcoming');
        $unauthRes->assertStatus(401);

        // Con clave de frontend errónea en ruta de integraciones -> 401
        $wrongKeyRes = $this->getJson('/api/v1/integrations/appointments/upcoming', [
            'X-API-Key' => 'nuvex_lbl_front_2026_sec_key',
        ]);
        $wrongKeyRes->assertStatus(401);

        // Con clave dedicada de integraciones n8n -> 200
        $n8nRes = $this->getJson('/api/v1/integrations/appointments/upcoming', [
            'X-API-Key' => 'nuvex_n8n_integration_2026_key',
        ]);
        $n8nRes->assertSuccessful();
        $n8nRes->assertJsonStructure([
            'success',
            'count',
            'data',
        ]);
    }
}
