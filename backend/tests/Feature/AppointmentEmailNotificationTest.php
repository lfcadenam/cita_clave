<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Mail\AppointmentBookedAdminMail;
use App\Mail\AppointmentBookedClientMail;
use App\Mail\AppointmentPaymentConfirmedClientMail;
use App\Mail\AppointmentReminder24hClientMail;
use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AppointmentEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\SuperAdminSeeder::class);
        $this->seed(\Database\Seeders\DemoDataSeeder::class);

        $this->tenant = Tenant::first();
        $this->service = Service::first();
    }

    public function test_booking_creation_sends_email_to_admin_and_client(): void
    {
        Mail::fake();

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Valentina Gomez',
            'client_phone' => '3123456789',
            'client_email' => 'valentina@example.com',
            'appointment_date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:30:00',
            'total_amount' => 80000,
            'deposit_amount' => 20000,
            'deposit_paid' => 0,
            'balance_due' => 80000,
            'status' => AppointmentStatus::PENDING_VERIFICATION,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        // Verificamos que se envió correo a la administradora/dueña
        Mail::assertSent(AppointmentBookedAdminMail::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id
                && $mail->hasTo($this->tenant->email);
        });

        // Verificamos que se envió correo a la clienta
        Mail::assertSent(AppointmentBookedClientMail::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id
                && $mail->hasTo('valentina@example.com');
        });
    }

    public function test_payment_approval_sends_confirmation_email_to_client(): void
    {
        Mail::fake();

        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Laura Sofia Perez',
            'client_phone' => '3109876543',
            'client_email' => 'laura@example.com',
            'appointment_date' => now()->addDays(3)->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:30:00',
            'total_amount' => 100000,
            'deposit_amount' => 30000,
            'deposit_paid' => 0,
            'balance_due' => 100000,
            'status' => AppointmentStatus::PENDING_VERIFICATION,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
        ]);

        // Paola valida el abono en Filament o en el Calendario
        $appointment->update([
            'status' => AppointmentStatus::CONFIRMED,
            'deposit_paid' => 30000,
            'balance_due' => 70000,
            'verified_at' => now(),
            'verification_notes' => 'Aprobado por Paola',
        ]);

        // Verificamos que se envió el correo oficial de confirmación y pago validado a la clienta
        Mail::assertSent(AppointmentPaymentConfirmedClientMail::class, function ($mail) use ($appointment) {
            return $mail->appointment->id === $appointment->id
                && $mail->hasTo('laura@example.com');
        });
    }

    public function test_reminder_cron_sends_24h_email_with_whatsapp_button_and_marks_database(): void
    {
        Mail::fake();

        // Creamos una cita confirmada programada para MAÑANA
        $tomorrow = Carbon::tomorrow()->toDateString();
        $appointment = Appointment::create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'client_name' => 'Camila Duque',
            'client_phone' => '3151112233',
            'client_email' => 'camila@example.com',
            'appointment_date' => $tomorrow,
            'start_time' => '09:00:00',
            'end_time' => '10:30:00',
            'total_amount' => 120000,
            'deposit_amount' => 30000,
            'deposit_paid' => 30000,
            'balance_due' => 90000,
            'status' => AppointmentStatus::CONFIRMED,
            'payment_method' => PaymentMethod::NEQUI_TRANSFER,
            'reminder_24h_sent_at' => null,
        ]);

        // Ejecutar el comando Artisan del cron
        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('Proceso completado.')
            ->assertExitCode(0);

        // Verificamos que se despachó el mailable de recordatorio 24h
        Mail::assertSent(AppointmentReminder24hClientMail::class, function ($mail) use ($appointment) {
            // Verificar contenido del correo y que contenga enlace a WhatsApp con el número del salón
            $content = $mail->content();
            $whatsappUrl = $content->with['confirmWhatsappUrl'] ?? '';

            return $mail->appointment->id === $appointment->id
                && $mail->hasTo('camila@example.com')
                && str_contains($whatsappUrl, 'https://wa.me/57');
        });

        // Verificamos que se actualizó el campo reminder_24h_sent_at en la base de datos
        $this->assertNotNull($appointment->fresh()->reminder_24h_sent_at);

        // Ejecutar el cron nuevamente: no debe volver a enviar (idempotencia)
        $this->artisan('appointments:send-reminders')
            ->expectsOutputToContain('No se encontraron citas pendientes')
            ->assertExitCode(0);
    }
}
