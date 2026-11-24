<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->tenant = Tenant::where('slug', 'paola-aguilera')->first();
        $this->admin = User::where('email', 'paola@nuvex-belleza.com')->first();

        // Obtener token Sanctum para el usuario admin
        $this->token = $this->admin->createToken('test-token', [$this->admin->role->value])->plainTextToken;
    }

    protected function authHeaders(array $extra = []): array
    {
        return array_merge([
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
            'X-Tenant-Slug' => $this->tenant->slug,
        ], $extra);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'paola@nuvex-belleza.com',
            'password' => 'Paola2026!*',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'paola@nuvex-belleza.com',
                        'role' => UserRole::ADMIN->value,
                    ],
                    'tenant' => [
                        'slug' => 'paola-aguilera',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'paola@nuvex-belleza.com',
            'password' => 'clave-erronea-123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->admin->update(['is_active' => false]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'paola@nuvex-belleza.com',
            'password' => 'Paola2026!*',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_auth_me_returns_authenticated_user_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'email' => 'paola@nuvex-belleza.com',
                        'role' => UserRole::ADMIN->value,
                    ],
                    'tenant' => [
                        'slug' => 'paola-aguilera',
                    ],
                ],
            ]);
    }

    public function test_auth_logout_revokes_token(): void
    {
        $response = $this->postJson('/api/v1/auth/logout', [], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        // Verificar que el token fue eliminado
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $this->admin->id,
            'name' => 'test-token',
        ]);
    }

    public function test_unauthenticated_request_to_admin_endpoints_is_rejected(): void
    {
        $response = $this->getJson('/api/v1/admin/appointments', [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_list_calendar_slots_grouped_by_morning_and_afternoon(): void
    {
        $service = Service::where('tenant_id', $this->tenant->id)->where('is_active', true)->first();
        $targetDate = Carbon::now()->addDays(2)->format('Y-m-d');

        $response = $this->getJson("/api/v1/admin/calendar/slots?service_id={$service->id}&date={$targetDate}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'service' => ['id', 'name', 'duration_minutes', 'base_price', 'deposit_amount'],
                    'date',
                    'day_name',
                    'is_available',
                    'total_slots',
                    'groups' => [
                        'morning' => ['label', 'count', 'slots'],
                        'afternoon' => ['label', 'count', 'slots'],
                    ],
                    'slots',
                ],
            ]);
    }

    public function test_admin_can_get_daily_calendar_summary_and_kpis(): void
    {
        $today = Carbon::today()->format('Y-m-d');

        $response = $this->getJson("/api/v1/admin/calendar/summary?date={$today}", $this->authHeaders());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'date',
                    'date_formatted',
                    'kpis' => [
                        'total_appointments',
                        'confirmed_count',
                        'pending_verification_count',
                        'financials' => ['expected_revenue', 'collected_deposits', 'pending_balance'],
                    ],
                ],
            ]);
    }

    public function test_admin_can_create_appointment_and_system_detects_conflicts(): void
    {
        $service = Service::where('tenant_id', $this->tenant->id)->where('is_active', true)->first();
        $targetDate = Carbon::now()->addDays(3)->format('Y-m-d');
        $startTime = '09:00';
        $endTime = '10:00';

        // 1. Crear la primera cita con éxito
        $response = $this->postJson('/api/v1/admin/appointments', [
            'service_id' => $service->id,
            'appointment_date' => $targetDate,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'client_name' => 'Valentina Restrepo',
            'client_phone' => '3157778899',
            'client_email' => 'valentina@example.com',
            'deposit_paid' => 20000,
            'status' => AppointmentStatus::CONFIRMED->value,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER->value,
        ], $this->authHeaders());

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'client' => [
                        'name' => 'Valentina Restrepo',
                        'phone' => '3157778899',
                    ],
                    'financials' => [
                        'deposit_paid' => 20000,
                    ],
                ],
            ]);

        // Verificar que el cliente fue creado y sincronizado en la base de datos
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Valentina Restrepo',
            'phone' => '3157778899',
        ]);

        // 2. Intentar agendar otra cita en el mismo horario (Conflicto)
        $conflictResponse = $this->postJson('/api/v1/admin/appointments', [
            'service_id' => $service->id,
            'appointment_date' => $targetDate,
            'start_time' => '09:15', // Solapa con 09:00 - 10:00
            'end_time' => '10:15',
            'client_name' => 'Camila Duque',
            'client_phone' => '3201112233',
        ], $this->authHeaders());

        $conflictResponse->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_admin_can_approve_nequi_payment(): void
    {
        $service = Service::where('tenant_id', $this->tenant->id)->first();
        $targetDate = Carbon::now()->addDays(4)->format('Y-m-d');

        // Cita en espera de verificación de comprobante
        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->admin->id,
            'service_id' => $service->id,
            'appointment_date' => $targetDate,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'client_name' => 'Andrea Morales',
            'client_phone' => '3114445566',
            'status' => AppointmentStatus::PENDING_VERIFICATION,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'total_amount' => $service->base_price,
            'deposit_amount' => $service->deposit_amount,
            'deposit_paid' => 0,
            'balance_due' => $service->base_price,
        ]);

        $response = $this->postJson("/api/v1/admin/appointments/{$appointment->id}/approve-nequi", [
            'deposit_paid' => (float) $service->deposit_amount,
            'notes' => 'Comprobante verificado con éxito en app Nequi.',
        ], $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => AppointmentStatus::CONFIRMED->value,
                    'financials' => [
                        'deposit_paid' => (float) $service->deposit_amount,
                    ],
                ],
            ]);

        $appointment->refresh();
        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->status);
        $this->assertNotNull($appointment->verified_at);
        $this->assertEquals((float) $service->deposit_amount, (float) $appointment->deposit_paid);
    }

    public function test_admin_can_search_customers(): void
    {
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mariana Salazar',
            'phone' => '3129990011',
            'email' => 'mariana@example.com',
            'is_legacy' => false,
        ]);

        $response = $this->getJson('/api/v1/admin/customers?q=Mariana', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('Mariana Salazar', $data[0]['name']);
        $this->assertArrayHasKey('loyalty_badge', $data[0]);
    }

    public function test_admin_can_list_services(): void
    {
        $response = $this->getJson('/api/v1/admin/services', $this->authHeaders());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $services = $response->json('data');
        $this->assertNotEmpty($services);
        $this->assertArrayHasKey('base_price', $services[0]);
        $this->assertArrayHasKey('deposit_amount', $services[0]);
    }
}
