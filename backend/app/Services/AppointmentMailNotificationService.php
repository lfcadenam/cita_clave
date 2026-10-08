<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\AppointmentBookedAdminMail;
use App\Mail\AppointmentBookedClientMail;
use App\Mail\AppointmentPaymentConfirmedClientMail;
use App\Mail\AppointmentReminder24hClientMail;
use App\Models\Appointment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class AppointmentMailNotificationService
{
    /**
     * Enviar correos al registrar una nueva cita:
     * 1. A la dueña/administradora del centro de estética.
     * 2. A la clienta que realizó el agendamiento (si proporcionó email).
     */
    public function sendAppointmentBookedEmails(Appointment $appointment): array
    {
        $appointment->loadMissing(['tenant', 'service']);
        $results = [
            'admin_sent' => false,
            'client_sent' => false,
        ];

        // 1. Notificar a la dueña / administradora del salón
        $ownerEmail = $this->resolveOwnerEmail($appointment);
        if ($ownerEmail) {
            try {
                Mail::to($ownerEmail)->send(new AppointmentBookedAdminMail($appointment));
                $results['admin_sent'] = true;
                Log::info("Correo de nueva cita enviado a la administradora ({$ownerEmail}) para cita #{$appointment->appointment_number}");
            } catch (Throwable $e) {
                Log::error("Error enviando correo a administradora ({$ownerEmail}): " . $e->getMessage());
            }
        }

        // 2. Notificar a la clienta
        if (!empty($appointment->client_email)) {
            try {
                Mail::to($appointment->client_email)->send(new AppointmentBookedClientMail($appointment));
                $results['client_sent'] = true;
                Log::info("Correo de confirmación de registro enviado a la clienta ({$appointment->client_email}) para cita #{$appointment->appointment_number}");
            } catch (Throwable $e) {
                Log::error("Error enviando correo a clienta ({$appointment->client_email}): " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Enviar correo a la clienta cuando se valida el pago y se confirma la cita.
     */
    public function sendPaymentConfirmedEmail(Appointment $appointment): bool
    {
        $appointment->loadMissing(['tenant', 'service']);

        if (empty($appointment->client_email)) {
            Log::info("Cita #{$appointment->appointment_number} confirmada, pero no tiene correo de clienta registrado.");
            return false;
        }

        try {
            Mail::to($appointment->client_email)->send(new AppointmentPaymentConfirmedClientMail($appointment));
            Log::info("Correo de pago validado y cita confirmada enviado a {$appointment->client_email} para cita #{$appointment->appointment_number}");
            return true;
        } catch (Throwable $e) {
            Log::error("Error enviando correo de pago validado a {$appointment->client_email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enviar correo recordatorio 24 horas antes con botón directo de confirmación por WhatsApp a la dueña.
     */
    public function sendReminder24hEmail(Appointment $appointment): bool
    {
        $appointment->loadMissing(['tenant', 'service']);

        if (empty($appointment->client_email)) {
            Log::info("Recordatorio 24h omitido para cita #{$appointment->appointment_number}: no tiene correo.");
            return false;
        }

        try {
            Mail::to($appointment->client_email)->send(new AppointmentReminder24hClientMail($appointment));
            
            // Marcar en BD para evitar duplicados en próximas ejecuciones del cron
            $appointment->updateQuietly([
                'reminder_24h_sent_at' => now(),
            ]);

            Log::info("Recordatorio 24h enviado exitosamente a {$appointment->client_email} para cita #{$appointment->appointment_number}");
            return true;
        } catch (Throwable $e) {
            Log::error("Error enviando recordatorio 24h a {$appointment->client_email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Resolver la dirección de correo de la dueña/administradora del centro de estética.
     */
    protected function resolveOwnerEmail(Appointment $appointment): ?string
    {
        $tenant = $appointment->tenant;

        if ($tenant && !empty($tenant->email)) {
            return $tenant->email;
        }

        if ($tenant) {
            $adminUser = $tenant->users()
                ->where('role', UserRole::ADMIN)
                ->whereNotNull('email')
                ->first();

            if ($adminUser && !empty($adminUser->email)) {
                return $adminUser->email;
            }
        }

        return config('mail.from.address');
    }
}
