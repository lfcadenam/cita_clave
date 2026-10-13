<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentMailNotificationService;
use App\Services\WhatsAppNotificationService;
use Illuminate\Support\Facades\Log;

class AppointmentObserver
{
    public function __construct(
        protected AppointmentMailNotificationService $mailService,
        protected \App\Services\BookingAvailabilityService $availabilityService,
        protected WhatsAppNotificationService $whatsAppService
    ) {}

    /**
     * Se ejecuta cuando se crea una nueva cita (desde la web, API o panel Filament).
     */
    public function created(Appointment $appointment): void
    {
        // 1. Enviar correos iniciales de agendamiento (a la dueña y a la clienta)
        $this->mailService->sendAppointmentBookedEmails($appointment);

        // 2. Si la cita nace directamente en estado CONFIRMADO (ej. clienta habitual o creada en el local por Paola)
        if ($appointment->status === AppointmentStatus::CONFIRMED) {
            $this->mailService->sendPaymentConfirmedEmail($appointment);
        }

        // 3. Enviar notificación instantánea de "Nueva Cita Agendada" al WhatsApp del administrador
        try {
            $this->whatsAppService->sendAdminNewBookingAlert($appointment);
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar WhatsApp de Nueva Cita Agendada al administrador para cita #{$appointment->appointment_number}: " . $e->getMessage());
        }

        // 4. Ajustar almuerzo flexible si la cita se traslapa con la ventana normal de almuerzo
        $this->availabilityService->adjustLunchForAppointment($appointment);
    }

    /**
     * Se ejecuta cuando se actualiza la cita (ej. aprobación de abono Nequi o pago Bold, cambio de estado u horario).
     */
    public function updated(Appointment $appointment): void
    {
        // Detectar si el estado transitó a CONFIRMADO
        if ($appointment->wasChanged('status') && $appointment->status === AppointmentStatus::CONFIRMED) {
            $originalStatus = $appointment->getOriginal('status');
            
            // Si el estado previo no era CONFIRMED, notificamos que el pago fue validado y la cita confirmada
            if ($originalStatus !== AppointmentStatus::CONFIRMED && $originalStatus !== AppointmentStatus::CONFIRMED->value) {
                $this->mailService->sendPaymentConfirmedEmail($appointment);
            }
        }

        // Si cambió el horario, fecha o estado, recalcular el almuerzo de la fecha afectada
        if ($appointment->wasChanged(['status', 'start_time', 'end_time', 'appointment_date'])) {
            $this->availabilityService->recalculateLunchForDate($appointment->appointment_date, $appointment->tenant_id);

            // Si cambió la fecha, recalcular también la fecha anterior
            if ($appointment->wasChanged('appointment_date')) {
                $originalDate = $appointment->getOriginal('appointment_date');
                if ($originalDate) {
                    $this->availabilityService->recalculateLunchForDate($originalDate, $appointment->tenant_id);
                }
            }
        }
    }

    /**
     * Se ejecuta cuando se elimina una cita.
     */
    public function deleted(Appointment $appointment): void
    {
        $this->availabilityService->recalculateLunchForDate($appointment->appointment_date, $appointment->tenant_id);
    }
}
