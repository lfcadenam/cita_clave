<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ProductionTenantSeeder;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionTenantSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_tenant_seeder_provisions_la_belle_nails_salon(): void
    {
        $this->seed(SuperAdminSeeder::class);
        $this->seed(ProductionTenantSeeder::class);

        $tenant = Tenant::where('slug', 'labellenails')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('labellenailsbog.com', $tenant->domain);
        $this->assertEquals('3103248385', $tenant->phone);
        $this->assertTrue($tenant->is_active);

        // Verificar servicios creados
        $servicesCount = Service::where('tenant_id', $tenant->id)->count();
        $this->assertGreaterThanOrEqual(7, $servicesCount);

        // Verificar administradora
        $admin = User::where('email', 'paola@labellenailsbog.com')->first();
        $this->assertNotNull($admin);
        $this->assertEquals($tenant->id, $admin->tenant_id);
    }

    public function test_identify_tenant_middleware_resolves_tenant_by_domain(): void
    {
        $this->seed(ProductionTenantSeeder::class);

        // Petición con el Host labellenailsbog.com
        $response = $this->withServerVariables(['HTTP_HOST' => 'labellenailsbog.com'])
            ->getJson('/api/v1/services');

        $response->assertSuccessful();
        $this->assertTrue(app()->has('active_tenant_id'));

        $tenant = Tenant::where('slug', 'labellenails')->first();
        $this->assertEquals($tenant->id, app('active_tenant_id'));
    }

    public function test_identify_tenant_middleware_fallback_to_active_tenant(): void
    {
        $this->seed(ProductionTenantSeeder::class);

        // Petición con un Host genérico no registrado
        $response = $this->withServerVariables(['HTTP_HOST' => 'random-domain.com'])
            ->getJson('/api/v1/services');

        $response->assertSuccessful();
        $this->assertTrue(app()->has('active_tenant_id'));
    }
}
