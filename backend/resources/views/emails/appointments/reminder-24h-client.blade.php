@extends('emails.layouts.beauty', ['title' => 'Recordatorio de Cita — Mañana', 'tenant' => $tenant])

@section('content')
    <div style="margin-bottom: 24px;">
        <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; background-color: #fef3c7; color: #b45309; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
            Recordatorio 24 Horas
        </span>
        <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
            ¡Hola, {{ $appointment->client_name }}! Tu cita es mañana
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
            Te recordamos que tienes una cita programada para el tratamiento <strong>{{ $service->name }}</strong> mañana en <strong>{{ $tenant->displayName ?? 'nuestro estudio' }}</strong>.
        </p>
    </div>

    <!-- Highlight Time Box -->
    <div style="background-color: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px; padding: 22px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
            Horario de tu Cita
        </div>
        <div style="font-size: 26px; font-weight: 900; color: #0d9488; letter-spacing: -0.02em; margin-bottom: 4px;">
            {{ substr($appointment->start_time, 0, 5) }} - {{ substr($appointment->end_time, 0, 5) }}
        </div>
        <div style="font-size: 14px; font-weight: 600; color: #334155;">
            {{ $appointment->appointment_date ? $appointment->appointment_date->format('l, d \d\e F \d\e Y') : 'Mañana' }}
        </div>
        <div style="font-size: 12px; color: #64748b; margin-top: 8px;">
            Servicio: <strong>{{ $service->name }}</strong> ({{ $service->formatted_duration }})
        </div>
        @if($appointment->balance_due > 0)
            <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e2e8f0; font-size: 13px; color: #334155;">
                Saldo a pagar en local: <strong style="color: #0f172a;">${{ number_format($appointment->balance_due, 0, ',', '.') }} COP</strong>
            </div>
        @endif
    </div>

    <!-- PRIMARY WHATSAPP CONFIRMATION CTA (Requested by user) -->
    <div style="background-color: #f0fdf4; border: 1px solid #86efac; border-radius: 16px; padding: 22px; text-align: center; margin-bottom: 24px;">
        <div style="font-size: 15px; font-weight: 800; color: #166534; margin-bottom: 6px;">
            ¿Confirmas tu asistencia para mañana?
        </div>
        <p style="margin: 0 0 16px 0; font-size: 13px; color: #15803d; line-height: 1.5;">
            Por favor presiona el botón a continuación para avisarle directamente a <strong>{{ $ownerName }}</strong> por WhatsApp y asegurar tu espacio:
        </p>

        <a href="{{ $confirmWhatsappUrl }}" target="_blank" style="display: inline-block; background-color: #25D366; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 800; padding: 14px 32px; border-radius: 12px; box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35); text-align: center;">
            Confirmar Asistencia por WhatsApp
        </a>
    </div>

    <!-- Secondary Links -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 12px;">
        <tr>
            <td align="center">
                <a href="{{ $voucherUrl }}" target="_blank" style="display: inline-block; color: #475569; text-decoration: underline; font-size: 13px; font-weight: 600;">
                    Ver Detalles y Resumen de Mi Cita (#{{ $appointment->appointment_number }})
                </a>
            </td>
        </tr>
    </table>
@endsection
