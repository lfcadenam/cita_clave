<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\BoldPaymentService;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridPaymentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_nequi_info_endpoint_returns_banking_data_and_instructions(): void
    {
        $response = $this->getJson('/api/v1/payments/nequi-info');

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'success',
            'data' => [
                'holder_name',
                'account_number',
                'account_type',
                'document_id',
                'qr_image_url',
                'instructions',
                'fee_discount_notice',
            ],
        ]);
        $response->assertJson([
            'data' => [
                'holder_name' => 'Paola Andrea Aguilera Camacho',
            ],
        ]);
    }

    public function test_create_bold_checkout_session(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Sara Morales',
            'client_phone' => '3141112233',
            'client_email' => 'sara@gmail.com',
            'appointment_date' => Carbon::now()->next(Carbon::MONDAY)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'total_amount' => $service->base_price,
            'deposit_amount' => $service->deposit_amount,
            'deposit_paid' => 0,
            'balance_due' => $service->base_price,
            'payment_method' => PaymentMethod::BOLD_ONLINE,
            'status' => AppointmentStatus::PENDING_DEPOSIT,
        ]);

        $response = $this->postJson('/api/v1/payments/bold/checkout', [
            'appointment_id' => $appointment->id,
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'data' => [
                'order_id' => $appointment->appointment_number,
                'amount' => (float) $appointment->deposit_amount,
                'currency' => 'COP',
            ],
        ]);

        $this->assertNotEmpty($response->json('data.checkout_url'));
        $this->assertNotEmpty($response->json('data.integrity_signature'));
    }

    public function test_bold_webhook_approved_confirms_appointment(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Laura Restrepo',
            'client_phone' => '3119998877',
            'client_email' => 'laura@gmail.com',
            'appointment_date' => Carbon::now()->next(Carbon::TUESDAY)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'total_amount' => 120000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
            'balance_due' => 120000,
            'payment_method' => PaymentMethod::BOLD_ONLINE,
            'status' => AppointmentStatus::PENDING_DEPOSIT,
        ]);

        $webhookPayload = [
            'order_id' => $appointment->appointment_number,
            'status' => 'APPROVED',
            'transaction_id' => 'BOLD-TX-998877',
            'amount' => 30000,
            'payment_method' => 'PSE',
        ];

        $rawPayload = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $rawPayload, config('payment.bold.webhook_secret'));

        $response = $this->postJson('/api/v1/payments/bold/webhook', $webhookPayload, [
            'x-bold-signature' => $signature,
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'status' => 'CONFIRMED',
        ]);

        $refreshed = $appointment->fresh();
        $this->assertEquals(AppointmentStatus::CONFIRMED, $refreshed->status);
        $this->assertEquals(30000, (float) $refreshed->deposit_paid);
        $this->assertEquals(90000, (float) $refreshed->balance_due);
        $this->assertNotNull($refreshed->verified_at);
    }

    public function test_bold_webhook_rejected_cancels_appointment(): void
    {
        $service = Service::first();
        $appointment = Appointment::create([
            'service_id' => $service->id,
            'client_name' => 'Andrea Benitez',
            'client_phone' => '3104445566',
            'appointment_date' => Carbon::now()->next(Carbon::WEDNESDAY)->toDateString(),
            'start_time' => '15:30:00',
            'end_time' => '17:00:00',
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
            'balance_due' => 100000,
            'payment_method' => PaymentMethod::BOLD_ONLINE,
            'status' => AppointmentStatus::PENDING_DEPOSIT,
        ]);

        $webhookPayload = [
            'order_id' => $appointment->appointment_number,
            'status' => 'REJECTED',
            'transaction_id' => 'BOLD-TX-REJECTED',
            'reason' => 'Fondos insuficientes',
        ];

        $rawPayload = json_encode($webhookPayload);
        $signature = hash_hmac('sha256', $rawPayload, config('payment.bold.webhook_secret'));

        $response = $this->postJson('/api/v1/payments/bold/webhook', $webhookPayload, [
            'x-bold-signature' => $signature,
        ]);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'status' => 'CANCELLED',
        ]);

        $this->assertEquals(AppointmentStatus::CANCELLED, $appointment->fresh()->status);
        $this->assertNotNull($appointment->fresh()->cancelled_at);
    }

    public function test_bold_webhook_with_invalid_signature_is_rejected(): void
    {
        $webhookPayload = [
            'order_id' => 'PA-TEST-FAKE',
            'status' => 'APPROVED',
        ];

        $response = $this->postJson('/api/v1/payments/bold/webhook', $webhookPayload, [
            'x-bold-signature' => 'invalid_signature_hash',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Firma de webhook inválida.',
        ]);
    }
}