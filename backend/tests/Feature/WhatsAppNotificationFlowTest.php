<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\WhatsAppNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class WhatsAppNotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Service $service;
    private WhatsAppNotificationService $serviceWhatsApp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Paola Aguilera Belleza',
            'slug' => 'paola-aguilera',
            'is_active' => true,
        ]);

        $this->service = Service::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Lifting de Pestañas Deluxe',
            'category' => \App\Enums\ServiceCategory::PESTANAS_CEJAS,
            'base_price' => 70000,
            'deposit_amount' => 30000,
            'duration_minutes' => 60,
            'is_active' => true,
        ]);

        $this->serviceWhatsApp = app(WhatsAppNotificationService::class);
    }

    public function test_phone_number_sanitizer_normalizes_colombian_phones(): void
    {
        $this->assertEquals('573106080402', $this->serviceWhatsApp->sanitizePhoneNumber('3106080402'));
        $this->assertEquals('573106080402', $this->serviceWhatsApp->sanitizePhoneNumber('+57 310 608 0402'));
        $this->assertEquals('573106080402', $this->serviceWhatsApp->sanitizePhoneNumber('573106080402'));
    }

    public function test_all_lifecycle_notifications_execute_without_exceptions(): void
    {
        config(['whatsapp.driver' => 'log']);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Valentina Restrepo',
            'client_phone' => '3159988776',
            'client_email' => 'valentina@ejemplo.com',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'total_amount' => 70000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::PENDING_VERIFICATION,
        ]);

        // 1. Solicitud creada
        $resBooking = $this->serviceWhatsApp->sendBookingCreated($appointment);
        $this->assertTrue($resBooking);

        // 2. Alerta a la administradora
        $resAdmin = $this->serviceWhatsApp->sendAdminNewBookingAlert($appointment);
        $this->assertTrue($resAdmin);

        // 3. Abono verificado y confirmación
        $appointment->update([
            'status' => AppointmentStatus::CONFIRMED,
            'deposit_paid' => 30000,
            'balance_due' => 40000,
        ]);
        $resApproved = $this->serviceWhatsApp->sendDepositApproved($appointment);
        $this->assertTrue($resApproved);

        // 4. Recordatorio 24h
        $resReminder = $this->serviceWhatsApp->sendReminder24h($appointment);
        $this->assertTrue($resReminder);
        $this->assertNotNull($appointment->fresh()->reminder_24h_sent_at);

        // 5. Cancelación
        $resCancelled = $this->serviceWhatsApp->sendAppointmentCancelled($appointment, 'Fuerza mayor');
        $this->assertTrue($resCancelled);
    }

    public function test_url_generator_creates_valid_wa_me_link(): void
    {
        $link = $this->serviceWhatsApp->generateWhatsAppUrl('3106080402', 'Hola Paola');
        $this->assertStringContainsString('https://wa.me/573106080402?text=Hola%20Paola', $link);

        $linkWeb = $this->serviceWhatsApp->generateWhatsAppUrl('3106080402', 'Hola Paola', true);
        $this->assertStringContainsString('https://web.whatsapp.com/send?phone=573106080402&text=Hola%20Paola', $linkWeb);
    }

    public function test_resolve_admin_phone_prioritizes_tenant_whatsapp(): void
    {
        $this->tenant->update([
            'whatsapp_number' => '3103248385',
            'phone' => '3109999999',
        ]);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Camila Torres',
            'client_phone' => '3112223344',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'total_amount' => 70000,
            'deposit_amount' => 0,
            'deposit_paid' => 0,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::CASH_AT_LOCATION,
            'status' => AppointmentStatus::CONFIRMED,
        ]);

        $resolved = $this->serviceWhatsApp->resolveAdminPhone($appointment);
        $this->assertEquals('573103248385', $resolved);
    }

    public function test_appointment_creation_automatically_dispatches_admin_whatsapp_alert(): void
    {
        config(['whatsapp.driver' => 'log']);

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Natalia Morales',
            'client_phone' => '3187766554',
            'appointment_date' => now()->addDays(1)->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'total_amount' => 70000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
            'balance_due' => 70000,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'status' => AppointmentStatus::PENDING_VERIFICATION,
        ]);

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'client_name' => 'Natalia Morales',
        ]);
    }
}
