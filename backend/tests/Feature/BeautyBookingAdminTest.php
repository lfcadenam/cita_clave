<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Filament\Resources\AppointmentResource\Pages\ListAppointments;
use App\Filament\Resources\BlockedSlotResource\Pages\ListBlockedSlots;
use App\Filament\Resources\ServiceResource\Pages\ListServices;
use App\Filament\Resources\WorkingScheduleResource\Pages\ListWorkingSchedules;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\User;
use App\Models\Waitlist;
use App\Models\WorkingSchedule;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

use App\Models\Tenant;
use Filament\Facades\Filament;

class BeautyBookingAdminTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->tenant = Tenant::where('slug', 'paola-aguilera')->first();
        $this->admin = User::where('email', 'paola@nuvex-belleza.com')->first();
    }

    public function test_database_seeder_populates_initial_data(): void
    {
        $this->assertDatabaseHas('users', [
            'email' => 'paola@nuvex-belleza.com',
            'role' => UserRole::ADMIN->value,
            'tenant_id' => $this->tenant->id,
        ]);

        $this->assertGreaterThanOrEqual(8, Service::count());
        $this->assertEquals(7, WorkingSchedule::count());
        $this->assertDatabaseHas('blocked_slots', [
            'title' => 'Hora de Almuerzo & Descanso',
            'is_recurring' => true,
        ]);
        $this->assertGreaterThanOrEqual(2, Appointment::count());
    }

    public function test_admin_can_access_filament_login(): void
    {
        $response = $this->get('/admin/login');
        $response->assertSuccessful();
    }

    public function test_admin_can_authenticate_and_access_dashboard(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera');
        $response->assertSuccessful();
    }

    public function test_admin_can_access_services_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera/services');
        $response->assertSuccessful();

        Livewire::test(ListServices::class)
            ->assertCanSeeTableRecords(Service::all());
    }

    public function test_admin_can_access_appointments_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera/appointments');
        $response->assertSuccessful();

        Livewire::test(ListAppointments::class)
            ->assertCanSeeTableRecords(Appointment::all());
    }

    public function test_admin_can_access_blocked_slots_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera/blocked-slots');
        $response->assertSuccessful();

        Livewire::test(ListBlockedSlots::class)
            ->assertCanSeeTableRecords(BlockedSlot::all());
    }

    public function test_admin_can_access_working_schedules_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera/working-schedules');
        $response->assertSuccessful();

        Livewire::test(ListWorkingSchedules::class)
            ->assertCanSeeTableRecords(WorkingSchedule::all());
    }

    public function test_admin_can_access_waitlist_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera/waitlists');
        $response->assertSuccessful();
    }

    public function test_nequi_payment_verification_flow(): void
    {
        $service = Service::first();

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'client_name' => 'Mariana Morales',
            'client_phone' => '3157778899',
            'client_email' => 'mariana@gmail.com',
            'service_id' => $service->id,
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'deposit_proof_image' => 'receipts/test_nequi_receipt.jpg',
            'status' => AppointmentStatus::PENDING_VERIFICATION,
        ]);

        $this->assertEquals(0, (float) $appointment->deposit_paid);
        $this->assertEquals(AppointmentStatus::PENDING_VERIFICATION, $appointment->status);

        // Paola approves the Nequi transfer with 1-click
        $appointment->update([
            'status' => AppointmentStatus::CONFIRMED,
            'deposit_paid' => 30000,
            'balance_due' => 70000,
            'verified_at' => now(),
            'verification_notes' => 'Aprobado 1-clic por Paola Aguilera',
        ]);

        $refreshed = $appointment->fresh();
        $this->assertEquals(30000, (float) $refreshed->deposit_paid);
        $this->assertEquals(70000, (float) $refreshed->balance_due);
        $this->assertEquals(AppointmentStatus::CONFIRMED, $refreshed->status);
        $this->assertNotNull($refreshed->verified_at);
    }

    public function test_admin_can_access_appointment_calendar_page(): void
    {
        $this->actingAs($this->admin);
        Filament::setTenant($this->tenant);

        $response = $this->get('/admin/paola-aguilera/calendario-citas');
        $response->assertSuccessful();

        $appointment = Appointment::where('status', AppointmentStatus::PENDING_VERIFICATION)->first();

        Livewire::test(\App\Filament\Pages\AppointmentCalendarPage::class)
            ->assertSet('viewMode', 'week')
            ->call('setViewMode', 'month')
            ->assertSet('viewMode', 'month')
            ->call('setViewMode', 'day')
            ->assertSet('viewMode', 'day')
            ->call('goToToday')
            ->call('selectAppointment', $appointment->id)
            ->assertSet('showDetailModal', true)
            ->assertSet('selectedAppointmentId', $appointment->id)
            ->call('approveNequiDeposit', $appointment->id)
            ->assertSet('showDetailModal', false)
            ->assertSuccessful();

        $this->assertEquals(AppointmentStatus::CONFIRMED, $appointment->fresh()->status);

        $service = Service::first();
        Livewire::test(\App\Filament\Pages\AppointmentCalendarPage::class)
            ->mountAction('newAppointment')
            ->setActionData([
                'client_name' => 'Catalina Restrepo',
                'client_phone' => '3119998877',
                'service_id' => $service->id,
                'appointment_date' => now()->addDays(3)->toDateString(),
                'start_time' => '14:00',
                'end_time' => '15:30',
                'status' => AppointmentStatus::CONFIRMED->value,
                'payment_method' => PaymentMethod::CASH_AT_LOCATION->value,
                'total_amount' => 120000,
                'deposit_amount' => 30000,
                'deposit_paid' => 30000,
                'balance_due' => 90000,
            ])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('appointments', [
            'client_name' => 'Catalina Restrepo',
            'client_phone' => '3119998877',
        ]);
    }
}