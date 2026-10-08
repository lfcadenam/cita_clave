<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentBookedClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function envelope(): Envelope
    {
        $tenantName = $this->appointment->tenant?->displayName ?? 'Estudio Paola Aguilera';
        $serviceName = $this->appointment->service?->name ?? 'Servicio de Belleza';

        return new Envelope(
            subject: "¡Cita Registrada! — {$serviceName} en {$tenantName}",
        );
    }

    public function content(): Content
    {
        $tenant = $this->appointment->tenant;
        $service = $this->appointment->service;

        $voucherUrl = url('/citas/' . $this->appointment->appointment_number);
        $phoneClean = preg_replace('/\D/', '', $tenant?->whatsapp_number ?: ($tenant?->phone ?: '3103248385'));
        $whatsappMsg = urlencode("Hola {$tenant?->displayName}, tengo una duda sobre mi cita #{$this->appointment->appointment_number} de {$service?->name}.");
        $whatsappUrl = "https://wa.me/57{$phoneClean}?text={$whatsappMsg}";

        return new Content(
            view: 'emails.appointments.booked-client',
            with: [
                'appointment' => $this->appointment,
                'tenant' => $tenant,
                'service' => $service,
                'voucherUrl' => $voucherUrl,
                'whatsappUrl' => $whatsappUrl,
            ],
        );
    }
}
