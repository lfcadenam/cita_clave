<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentMailNotificationService;

class AppointmentObserver
{
    public function __construct(
        protected AppointmentMailNotificationService $mailService,
        protected \App\Services\BookingAvailabilityService $availabilityService
    ) {}

    /**
     * Se ejecuta cuando se crea una nueva cita (desde la web, API o panel Filament).
     */
    public function created(Appointment $appointment): void
    {
        // Enviar correos iniciales de agendamiento (a la dueña y a la clienta)
        $this->mailService->sendAppointmentBookedEmails($appointment);

        // Si la cita nace directamente en estado CONFIRMADO (ej. creada en el local por Paola)
        if ($appointment->status === AppointmentStatus::CONFIRMED) {
            $this->mailService->sendPaymentConfirmedEmail($appointment);
        }

        // Ajustar almuerzo flexible si la cita se traslapa con la ventana normal de almuerzo
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
