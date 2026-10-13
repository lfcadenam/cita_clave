<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::where('slug', 'paola-aguilera')->first();
        $this->admin = User::where('email', 'paola@nuvex-belleza.com')->first();
        $this->service = Service::where('tenant_id', $this->tenant->id)->first();
    }

    public function test_appointment_automatically_creates_and_links_customer(): void
    {
        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Valentina Herrera',
            'client_phone' => '3129876543',
            'client_email' => 'valentina@test.com',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'total_amount' => 120000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 90000,
            'status' => AppointmentStatus::CONFIRMED,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        $this->assertNotNull($appointment->customer_id);

        $customer = Customer::find($appointment->customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals('Valentina Herrera', $customer->name);
        $this->assertEquals('3129876543', $customer->phone);
        $this->assertEquals('valentina@test.com', $customer->email);
        $this->assertEquals($this->tenant->id, $customer->tenant_id);
    }

    public function test_client_loyalty_recognition_new_vs_recurring(): void
    {
        // 1. Primera cita -> Cliente Nuevo
        $appt1 = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Camila Restrepo',
            'client_phone' => '3151112233',
            'client_email' => 'camila@test.com',
            'appointment_date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'total_amount' => 80000,
            'deposit_amount' => 20000,
            'deposit_paid' => 20000,
            'balance_due' => 60000,
            'status' => AppointmentStatus::CONFIRMED,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        $customer = $appt1->customer;
        $this->assertTrue($customer->isNew());
        $this->assertFalse($customer->isRecurring());
        $this->assertStringContainsString('Nueva', $customer->getLoyaltyBadge()['label']);

        // 2. Segunda cita -> Cliente Recurrente
        $appt2 = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Camila Restrepo',
            'client_phone' => '3151112233',
            'client_email' => 'camila@test.com',
            'appointment_date' => now()->addDays(5)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'total_amount' => 80000,
            'deposit_amount' => 20000,
            'deposit_paid' => 20000,
            'balance_due' => 60000,
            'status' => AppointmentStatus::CONFIRMED,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        $customer->refresh();
        $this->assertFalse($customer->isNew());
        $this->assertTrue($customer->isRecurring());
        $this->assertStringContainsString('Recurrente', $customer->getLoyaltyBadge()['label']);
        $this->assertEquals(2, $customer->appointments()->count());
    }

    public function test_customer_statistics_calculation(): void
    {
        $customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Catalina Maya',
            'phone' => '3189998877',
            'email' => 'catalina@test.com',
            'registered_at' => now()->subMonths(1),
        ]);

        // Cita 1 completada
        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $this->service->id,
            'client_name' => 'Catalina Maya',
            'client_phone' => '3189998877',
            'appointment_date' => now()->subDays(10)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_amount' => 100000,
            'deposit_paid' => 30000,
            'status' => AppointmentStatus::COMPLETED,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        // Cita 2 confirmada
        Appointment::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'service_id' => $this->service->id,
            'client_name' => 'Catalina Maya',
            'client_phone' => '3189998877',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'total_amount' => 150000,
            'deposit_paid' => 40000,
            'status' => AppointmentStatus::CONFIRMED,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        $this->assertEquals(2, $customer->appointments()->count());
        $this->assertEquals(2, $customer->getCompletedAppointmentsCount());
        $this->assertEquals(250000, $customer->total_spent);
        $this->assertEquals($this->service->id, $customer->getFavoriteService()?->id);
    }

    public function test_admin_can_access_customers_list_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        // Crear al menos una clienta
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sonia Benítez',
            'phone' => '3104445566',
            'registered_at' => now(),
        ]);

        $response = $this->get("/admin/{$this->tenant->slug}/customers");
        $response->assertSuccessful();

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords(Customer::where('tenant_id', $this->tenant->id)->get());
    }

    public function test_lookup_client_api_returns_loyalty_and_registration_metadata(): void
    {
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Lucía Arango',
            'phone' => '3167778899',
            'email' => 'lucia@test.com',
            'registered_at' => '2026-08-15 10:00:00',
        ]);

        $response = $this->getJson('/api/v1/clients/lookup?phone=3167778899');

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Lucía Arango',
                'phone' => '3167778899',
                'email' => 'lucia@test.com',
                'is_new' => true,
            ],
        ]);
        $this->assertNotNull($response->json('data.registered_at'));
    }

    public function test_legacy_customer_is_recognized_as_antigua_and_recurring(): void
    {
        $legacy = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mariana Histórica',
            'phone' => '3119991122',
            'email' => 'mariana@historica.com',
            'is_legacy' => true,
            'notes' => 'Clienta habitual histórica',
            'registered_at' => '2026-01-01 09:00:00',
        ]);

        $this->assertFalse($legacy->isNew());
        $this->assertTrue($legacy->isRecurring());
        $badge = $legacy->getLoyaltyBadge();
        $this->assertStringContainsString('Antigua', $badge['label']);
        $this->assertEquals('emerald', $badge['color']);

        // Lookup endpoint acknowledges is_new = false and is_registered = true
        $response = $this->getJson('/api/v1/clients/lookup?phone=3119991122');
        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'data' => [
                'name' => 'Mariana Histórica',
                'is_new' => false,
                'is_registered' => true,
            ],
        ]);
    }
}
