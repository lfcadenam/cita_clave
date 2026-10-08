<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentBookedAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Appointment $appointment
    ) {}

    public function envelope(): Envelope
    {
        $serviceName = $this->appointment->service?->name ?? 'Servicio';
        $clientName = $this->appointment->client_name;

        return new Envelope(
            subject: "🔔 Nueva Cita Agendada: {$clientName} — {$serviceName}",
        );
    }

    public function content(): Content
    {
        $tenant = $this->appointment->tenant;
        $service = $this->appointment->service;

        $slug = $tenant?->slug ?? 'paola-aguilera';
        $adminPanelUrl = url("/admin/{$slug}/appointments");
        
        $clientPhoneClean = preg_replace('/\D/', '', (string) $this->appointment->client_phone);
        $clientWhatsappMsg = urlencode("Hola {$this->appointment->client_name}, te escribo de {$tenant?->displayName} respecto a tu cita #{$this->appointment->appointment_number}.");
        $clientWhatsappUrl = !empty($clientPhoneClean) ? "https://wa.me/57{$clientPhoneClean}?text={$clientWhatsappMsg}" : null;

        return new Content(
            view: 'emails.appointments.booked-admin',
            with: [
                'appointment' => $this->appointment,
                'tenant' => $tenant,
                'service' => $service,
                'adminPanelUrl' => $adminPanelUrl,
                'clientWhatsappUrl' => $clientWhatsappUrl,
            ],
        );
    }
}
