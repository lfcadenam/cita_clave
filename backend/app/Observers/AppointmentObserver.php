<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Services\AppointmentMailNotificationService;

class AppointmentObserver
{
    public function __construct(
        protected AppointmentMailNotificationService $mailService
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
    }

    /**
     * Se ejecuta cuando se actualiza la cita (ej. aprobación de abono Nequi o pago Bold).
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
    }
}
