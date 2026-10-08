<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Enums\ServiceCategory;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminMultiTenantTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantPaola;
    protected User $superAdmin;
    protected User $salonAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->tenantPaola = Tenant::where('slug', 'paola-aguilera')->first();
        $this->superAdmin = User::where('email', 'admin@nuvex-tecnologia.com')->first();
        $this->salonAdmin = User::where('email', 'paola@nuvex-belleza.com')->first();
    }

    public function test_super_admin_can_access_superadmin_panel(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/superadmin');
        $response->assertSuccessful();

        $responseTenants = $this->get('/superadmin/tenants');
        $responseTenants->assertSuccessful();

        $responseUsers = $this->get('/superadmin/super-admin-users');
        $responseUsers->assertSuccessful();
    }

    public function test_salon_admin_cannot_access_superadmin_panel(): void
    {
        $this->actingAs($this->salonAdmin);

        $response = $this->get('/superadmin');
        $response->assertForbidden();

        $responseTenants = $this->get('/superadmin/tenants');
        $responseTenants->assertForbidden();
    }

    public function test_super_admin_can_create_new_tenant(): void
    {
        $this->actingAs($this->superAdmin);

        $newTenant = Tenant::create([
            'name' => 'Studio Glamour Bogota',
            'slug' => 'studio-glamour',
            'domain' => 'glamour.salonesgo.com',
            'phone' => '3118889900',
            'email' => 'contacto@studioglamour.co',
            'address' => 'Calle 85 # 12-40, Zona Rosa',
            'city' => 'Bogota',
            'primary_color' => '#6366f1',
            'nequi_phone' => '3118889900',
            'nequi_account_holder' => 'Studio Glamour SAS',
            'nequi_account_type' => 'Ahorros Nequi',
            'subscription_status' => 'active',
            'plan_name' => 'SaaS Pro SalonesGO',
            'max_appointments_per_month' => 500,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('tenants', [
            'slug' => 'studio-glamour',
            'name' => 'Studio Glamour Bogota',
        ]);

        $glamourAdmin = User::create([
            'tenant_id' => $newTenant->id,
            'name' => 'Directora Glamour',
            'email' => 'admin@studioglamour.co',
            'password' => Hash::make('Glamour2026!*'),
            'role' => UserRole::ADMIN,
            'phone' => '3118889900',
            'is_active' => true,
        ]);

        $this->assertTrue($glamourAdmin->canAccessTenant($newTenant));
        $this->assertFalse($glamourAdmin->canAccessTenant($this->tenantPaola));
    }

    public function test_multi_tenant_data_isolation(): void
    {
        $tenantGlamour = Tenant::create([
            'name' => 'Studio Glamour',
            'slug' => 'studio-glamour',
            'phone' => '3118889900',
            'email' => 'info@glamour.co',
            'address' => 'Calle 85 # 12-40',
            'city' => 'Bogota',
            'subscription_status' => 'active',
            'plan_name' => 'Pro',
            'is_active' => true,
        ]);

        $glamourService = Service::create([
            'tenant_id' => $tenantGlamour->id,
            'name' => 'Balayage Deluxe Glamour',
            'slug' => 'balayage-deluxe',
            'category' => ServiceCategory::CORPORAL_MASAJES,
            'description' => 'Diseno de color premium',
            'duration_minutes' => 180,
            'base_price' => 350000,
            'deposit_amount' => 100000,
            'is_active' => true,
        ]);

        $glamourAppointment = Appointment::create([
            'tenant_id' => $tenantGlamour->id,
            'service_id' => $glamourService->id,
            'client_name' => 'Sofia Vergara',
            'client_phone' => '3109990011',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '13:00:00',
            'total_amount' => 350000,
            'deposit_amount' => 100000,
            'deposit_paid' => 100000,
            'balance_due' => 250000,
            'payment_method' => PaymentMethod::BOLD_ONLINE,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        // 1. Paola's context
        $this->actingAs($this->salonAdmin);
        Filament::setTenant($this->tenantPaola);

        $paolaServices = Service::all()->pluck('name')->toArray();
        $paolaAppointments = Appointment::all()->pluck('client_name')->toArray();

        $this->assertNotContains('Balayage Deluxe Glamour', $paolaServices);
        $this->assertNotContains('Sofia Vergara', $paolaAppointments);
        $this->assertContains('Limpieza Facial Profunda con Vapor de Ozono e Hidratacion', $paolaServices);

        // 2. Glamour's context
        $glamourAdmin = User::create([
            'tenant_id' => $tenantGlamour->id,
            'name' => 'Directora Glamour',
            'email' => 'admin@studioglamour.co',
            'password' => Hash::make('Glamour2026!*'),
            'role' => UserRole::ADMIN,
            'phone' => '3118889900',
            'is_active' => true,
        ]);

        $this->actingAs($glamourAdmin);
        Filament::setTenant($tenantGlamour);

        $glamourServices = Service::all()->pluck('name')->toArray();
        $glamourAppointments = Appointment::all()->pluck('client_name')->toArray();

        $this->assertContains('Balayage Deluxe Glamour', $glamourServices);
        $this->assertContains('Sofia Vergara', $glamourAppointments);
        $this->assertNotContains('Limpieza Facial Profunda con Vapor de Ozono e Hidratacion', $glamourServices);
    }

    public function test_super_admin_can_access_email_templates_page(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get('/superadmin/plantillas-correo');
        $response->assertSuccessful();
        $response->assertSee('Plantillas de Correo');
        $response->assertSee('Recordatorio 24 Horas Antes');
    }

    public function test_email_preview_endpoint_renders_templates(): void
    {
        $this->actingAs($this->superAdmin);

        $templates = ['reminder-24h', 'booked-admin', 'booked-client', 'payment-confirmed'];

        foreach ($templates as $tpl) {
            $response = $this->get("/superadmin/email-preview/{$tpl}");
            $response->assertSuccessful();
            $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    public function test_super_admin_can_update_user_password(): void
    {
        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));

        \Livewire\Livewire::test(
            \App\Filament\SuperAdmin\Resources\SuperAdminUserResource\Pages\EditSuperAdminUser::class,
            ['record' => $this->salonAdmin->getRouteKey()]
        )
            ->assertSchemaStateSet([
                'password' => null, // Verified: password must be empty on load!
            ])
            ->fillForm([
                'name' => 'Paola Andrea Updated',
                'password' => 'NuevaClave2026!*',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(\App\Filament\SuperAdmin\Resources\SuperAdminUserResource::getUrl('index'));

        $this->salonAdmin->refresh();
        $this->assertEquals('Paola Andrea Updated', $this->salonAdmin->name);
        $this->assertTrue(Hash::check('NuevaClave2026!*', $this->salonAdmin->password));
    }

    public function test_super_admin_updating_user_without_password_keeps_existing_password(): void
    {
        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));
        $originalHash = $this->salonAdmin->password;

        \Livewire\Livewire::test(
            \App\Filament\SuperAdmin\Resources\SuperAdminUserResource\Pages\EditSuperAdminUser::class,
            ['record' => $this->salonAdmin->getRouteKey()]
        )
            ->fillForm([
                'name' => 'Paola Name Only',
                'password' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertRedirect(\App\Filament\SuperAdmin\Resources\SuperAdminUserResource::getUrl('index'));

        $this->salonAdmin->refresh();
        $this->assertEquals('Paola Name Only', $this->salonAdmin->name);
        $this->assertEquals($originalHash, $this->salonAdmin->password);
    }

    public function test_super_admin_can_delete_user_from_table(): void
    {
        $this->actingAs($this->superAdmin);
        Filament::setCurrentPanel(Filament::getPanel('superadmin'));

        $userToDelete = User::create([
            'tenant_id' => $this->tenantPaola->id,
            'name' => 'Usuario Temporal',
            'email' => 'temporal@nuvex.co',
            'password' => Hash::make('ClaveTemp123!'),
            'role' => UserRole::CLIENT,
            'is_active' => true,
        ]);

        \Livewire\Livewire::test(
            \App\Filament\SuperAdmin\Resources\SuperAdminUserResource\Pages\ListSuperAdminUsers::class
        )
            ->callTableAction('delete', $userToDelete)
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseMissing('users', [
            'id' => $userToDelete->id,
        ]);
    }
}