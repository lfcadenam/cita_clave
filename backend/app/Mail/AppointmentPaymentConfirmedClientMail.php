<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentPaymentConfirmedClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->appointment->tenant?->displayName ?? 'Estudio Paola Aguilera';

        return new Envelope(
            subject: "✅ ¡Pago Validado y Cita Confirmada! — {$tenantName}",
        );
    }

    public function content(): Content
    {
        $tenant = $this->appointment->tenant;
        $service = $this->appointment->service;

        $voucherUrl = url('/citas/' . $this->appointment->appointment_number);
        $calendarIcsUrl = url('/citas/' . $this->appointment->appointment_number . '/calendar');

        $phoneClean = preg_replace('/\D/', '', $tenant?->whatsapp_number ?: ($tenant?->phone ?: '3103248385'));
        $whatsappMsg = urlencode("Hola {$tenant?->displayName}, vi que mi pago fue aprobado para la cita #{$this->appointment->appointment_number}. ¡Nos vemos pronto!");
        $whatsappUrl = "https://wa.me/57{$phoneClean}?text={$whatsappMsg}";

        return new Content(
            view: 'emails.appointments.payment-confirmed-client',
            with: [
                'appointment' => $this->appointment,
                'tenant' => $tenant,
                'service' => $service,
                'voucherUrl' => $voucherUrl,
                'calendarIcsUrl' => $calendarIcsUrl,
                'whatsappUrl' => $whatsappUrl,
            ],
        );
    }
}
