<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder24hClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->appointment->tenant?->displayName ?? 'Estudio Paola Aguilera';
        $timeStr = substr($this->appointment->start_time, 0, 5);

        return new Envelope(
            subject: "⏰ Recordatorio: Tu cita es mañana a las {$timeStr} en {$tenantName} — Confirma tu asistencia",
        );
    }

    public function content(): Content
    {
        $tenant = $this->appointment->tenant;
        $service = $this->appointment->service;

        $ownerName = $tenant?->displayName ?? 'Paola Aguilera';
        $phoneClean = preg_replace('/\D/', '', $tenant?->whatsapp_number ?: ($tenant?->phone ?: '3103248385'));
        $dateFormatted = $this->appointment->appointment_date ? $this->appointment->appointment_date->format('d/m/Y') : 'mañana';
        $timeFormatted = substr($this->appointment->start_time, 0, 5);

        // Pre-filled WhatsApp message for 1-click confirmation directly to the salon owner
        $confirmMsg = urlencode("Hola {$ownerName}, confirmo mi asistencia a mi cita de {$service?->name} para mañana {$dateFormatted} a las {$timeFormatted}. (Cita #{$this->appointment->appointment_number})");
        $confirmWhatsappUrl = "https://wa.me/57{$phoneClean}?text={$confirmMsg}";

        $voucherUrl = url('/citas/' . $this->appointment->appointment_number);
        $calendarIcsUrl = url('/citas/' . $this->appointment->appointment_number . '/calendar');

        return new Content(
            view: 'emails.appointments.reminder-24h-client',
            with: [
                'appointment' => $this->appointment,
                'tenant' => $tenant,
                'service' => $service,
                'ownerName' => $ownerName,
                'confirmWhatsappUrl' => $confirmWhatsappUrl,
                'voucherUrl' => $voucherUrl,
                'calendarIcsUrl' => $calendarIcsUrl,
            ],
        );
    }
}
