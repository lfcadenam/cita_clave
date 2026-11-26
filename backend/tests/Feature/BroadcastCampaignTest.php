<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastCampaignTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'La Belle Nails Test',
            'slug' => 'la-belle-test',
            'city' => 'Bogotá',
            'is_active' => true,
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin Salon',
            'email' => 'admin@labellenails.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::ADMIN,
        ]);

        // Crear clientas con y sin apodo
        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Camila Restrepo',
            'nickname' => 'Cami',
            'phone' => '3101112233',
            'is_active' => true,
            'is_legacy' => true,
        ]);

        Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Valentina Gómez',
            'nickname' => 'Vale',
            'phone' => '3104445566',
            'is_active' => true,
            'is_legacy' => false,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/*' => \Illuminate\Support\Facades\Http::response([
                'messages' => [['id' => 'wamid.TEST_CAMPAIGN_123']]
            ], 200),
        ]);
    }

    public function test_admin_can_preview_audience_counts(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/admin/broadcasts/audience?segment=all');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('counts.all', 2)
            ->assertJsonPath('counts.recurring', 1);
    }

    public function test_admin_can_create_and_execute_broadcast_campaign(): void
    {
        config(['whatsapp.driver' => 'log']);

        $payload = [
            'name' => 'Campaña Promo Test',
            'segment' => 'all',
            'template_key' => 'promocion_salon',
            'promo_text' => '20% de descuento en lifting',
            'target_url' => 'https://labellenailsbog.com',
            'message_body' => 'Hola {{1}}, tenemos una promo para ti.',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/broadcasts', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total_recipients', 2)
            ->assertJsonPath('summary.sent_count', 2);

        $this->assertDatabaseHas('broadcast_campaigns', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Campaña Promo Test',
            'status' => 'completed',
            'sent_count' => 2,
        ]);

        $this->assertDatabaseCount('broadcast_recipients', 2);
    }

    public function test_admin_can_send_test_broadcast_message(): void
    {
        config(['whatsapp.driver' => 'log']);

        $payload = [
            'phone' => '3194086814',
            'template_key' => 'promocion_salon',
            'promo_text' => 'Prueba de difusión',
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/admin/broadcasts/test', $payload);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
