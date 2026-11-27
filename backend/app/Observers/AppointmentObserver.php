<?php

namespace App\Observers;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
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
        // 1. Notificaciones por correo (desactivadas por defecto; canal oficial es WhatsApp)
        if ($this->mailService->isEnabled()) {
            try {
                $this->mailService->sendAppointmentBookedEmails($appointment);

                // 2. Si la cita nace directamente en estado CONFIRMADO
                if ($appointment->status === AppointmentStatus::CONFIRMED) {
                    $this->mailService->sendPaymentConfirmedEmail($appointment);
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudieron enviar correos de agendamiento para cita #{$appointment->appointment_number}: " . $e->getMessage());
            }
        }

        // 3. Enviar notificación instantánea de "Nueva Cita Agendada" al WhatsApp del administrador
        try {
            $this->whatsAppService->sendAdminNewBookingAlert($appointment);
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar WhatsApp de Nueva Cita Agendada al administrador para cita #{$appointment->appointment_number}: " . $e->getMessage());
        }

        // 4. Notificación por WhatsApp a la clienta (si no fue despachada explícitamente por el controlador)
        if (empty($appointment->skipObserverClientWhatsApp)) {
            try {
                if ($appointment->status === AppointmentStatus::CONFIRMED) {
                    $this->whatsAppService->sendBookingConfirmation($appointment);
                } else {
                    $this->whatsAppService->sendBookingCreated($appointment);
                }
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar WhatsApp de agendamiento a la clienta para cita #{$appointment->appointment_number}: " . $e->getMessage());
            }
        }

        // 5. Ajustar almuerzo flexible si la cita se traslapa con la ventana normal de almuerzo
        $this->availabilityService->adjustLunchForAppointment($appointment);
    }

    /**
     * Se ejecuta cuando se actualiza la cita (ej. aprobación de abono Nequi o pago Bold, cambio de estado u horario).
     */
    public function updated(Appointment $appointment): void
    {
        // 1. Detectar si el estado transitó a CONFIRMADO (Abono verificado o cita confirmada)
        if (
            !$appointment->skipObserverConfirmWhatsApp
            && $appointment->wasChanged('status')
            && $appointment->status === AppointmentStatus::CONFIRMED
        ) {
            $originalStatus = $appointment->getOriginal('status');
            
            // Si el estado previo no era CONFIRMED, notificamos que el pago fue validado y la cita confirmada
            if ($originalStatus !== AppointmentStatus::CONFIRMED && $originalStatus !== AppointmentStatus::CONFIRMED->value) {
                // Notificación por correo a la clienta (solo si está habilitado)
                if ($this->mailService->isEnabled()) {
                    try {
                        $this->mailService->sendPaymentConfirmedEmail($appointment);
                    } catch (\Throwable $e) {
                        Log::warning("No se pudo enviar correo de confirmación de cita #{$appointment->appointment_number}: " . $e->getMessage());
                    }
                }

                // Notificación por WhatsApp a la clienta
                try {
                    if ($appointment->payment_method === PaymentMethod::NEQUI_TRANSFER) {
                        $this->whatsAppService->sendDepositApproved($appointment);
                    } else {
                        $this->whatsAppService->sendBookingConfirmation($appointment);
                    }
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar WhatsApp de confirmación/abono aprobado para cita #{$appointment->appointment_number}: " . $e->getMessage());
                }
            }
        }

        // 2. Detectar si el estado transitó a CANCELADO
        if (
            !$appointment->skipObserverCancelWhatsApp
            && $appointment->wasChanged('status')
            && $appointment->status === AppointmentStatus::CANCELLED
        ) {
            $originalStatus = $appointment->getOriginal('status');
            if ($originalStatus !== AppointmentStatus::CANCELLED && $originalStatus !== AppointmentStatus::CANCELLED->value) {
                try {
                    $this->whatsAppService->sendAppointmentCancelled($appointment, $appointment->cancellation_reason);
                } catch (\Throwable $e) {
                    Log::warning("No se pudo enviar WhatsApp de cancelación para cita #{$appointment->appointment_number}: " . $e->getMessage());
                }
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

        // Si cambió la fecha o la hora de inicio (ej. reagendado desde el panel Filament) y no fue disparado ya por el controlador
        if (
            !$appointment->skipObserverRescheduleWhatsApp
            && $appointment->wasChanged(['appointment_date', 'start_time'])
            && $appointment->status !== AppointmentStatus::CANCELLED
        ) {
            try {
                $origDateRaw = $appointment->getOriginal('appointment_date');
                $oldDateFormatted = $origDateRaw
                    ? \Carbon\Carbon::parse($origDateRaw)->locale('es')->isoFormat('dddd D [de] MMMM')
                    : '';
                $origTimeRaw = (string) ($appointment->getOriginal('start_time') ?? '');
                $oldTime = substr($origTimeRaw, 0, 5);

                $this->whatsAppService->sendAppointmentRescheduled($appointment, $oldDateFormatted, $oldTime);
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar WhatsApp de reagendamiento en Observer para cita #{$appointment->appointment_number}: " . $e->getMessage());
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
