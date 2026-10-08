@extends('emails.layouts.beauty', ['title' => 'Cita Registrada', 'tenant' => $tenant])

@section('content')
    <div style="margin-bottom: 24px;">
        <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; background-color: #e6f7f2; color: #0d9488; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
            Cita Registrada
        </span>
        <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
            ¡Hola, {{ $appointment->client_name }}!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
            Tu cita para el servicio <strong>{{ $service->name }}</strong> ha sido registrada en nuestro sistema con el código <strong>#{{ $appointment->appointment_number }}</strong>.
        </p>
    </div>

    <!-- Appointment Details Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
        <tr>
            <td style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Servicio:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">{{ $service->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Fecha y Hora:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">
                            {{ $appointment->appointment_date ? $appointment->appointment_date->format('d/m/Y') : '' }} &bull; {{ substr($appointment->start_time, 0, 5) }} - {{ substr($appointment->end_time, 0, 5) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Duración Estimada:</td>
                        <td align="right" style="font-size: 13px; color: #334155; font-weight: 600;">{{ $service->formatted_duration }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Valor Total:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 800;">${{ number_format($appointment->total_amount, 0, ',', '.') }} COP</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 16px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Abono Requerido:</td>
                        <td align="right" style="font-size: 14px; color: #0d9488; font-weight: 800;">${{ number_format($appointment->deposit_amount, 0, ',', '.') }} COP</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Status message -->
    @if($appointment->status === \App\Enums\AppointmentStatus::PENDING_VERIFICATION)
        <div style="background-color: #fefce8; border: 1px solid #fef08a; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;">
            <div style="font-size: 13px; font-weight: 700; color: #854d0e; margin-bottom: 4px;">Validación de Abono en Proceso</div>
            <div style="font-size: 12px; color: #713f12; line-height: 1.5;">
                Hemos recibido tu solicitud. Nuestro equipo validará el comprobante de transferencia y recibirás una notificación de confirmación en cuanto quede verificado.
            </div>
        </div>
    @elseif($appointment->status === \App\Enums\AppointmentStatus::CONFIRMED)
        <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 12px; padding: 14px 18px; margin-bottom: 24px;">
            <div style="font-size: 13px; font-weight: 700; color: #065f46; margin-bottom: 4px;">Cita Confirmada</div>
            <div style="font-size: 12px; color: #047857; line-height: 1.5;">
                Tu cita se encuentra confirmada. Te esperamos puntualmente en nuestras instalaciones.
            </div>
        </div>
    @endif

    <!-- Actions -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10px;">
        <tr>
            <td align="center">
                <a href="{{ $voucherUrl }}" target="_blank" style="display: inline-block; background-color: #0d9488; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 12px; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);">
                    Ver Comprobante de Mi Cita
                </a>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-top: 12px;">
                <a href="{{ $whatsappUrl }}" target="_blank" style="display: inline-block; color: #0f766e; text-decoration: underline; font-size: 13px; font-weight: 600;">
                    Escribir por WhatsApp al Centro de Estética
                </a>
            </td>
        </tr>
    </table>
@endsection
