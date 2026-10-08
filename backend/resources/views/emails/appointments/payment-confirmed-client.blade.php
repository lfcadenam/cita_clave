@extends('emails.layouts.beauty', ['title' => 'Pago Validado y Cita Confirmada', 'tenant' => $tenant])

@section('content')
    <div style="margin-bottom: 24px;">
        <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; background-color: #ecfdf5; color: #059669; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
            Pago Validado &bull; Cita Confirmada
        </span>
        <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
            ¡Excelente noticia, {{ $appointment->client_name }}!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
            Tu abono para el servicio <strong>{{ $service->name }}</strong> ha sido validado exitosamente. Tu cita <strong>#{{ $appointment->appointment_number }}</strong> se encuentra <strong>100% confirmada</strong>.
        </p>
    </div>

    <!-- Official Confirmation Badge Box -->
    <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 14px; padding: 20px; margin-bottom: 24px; text-align: center;">
        <div style="font-size: 12px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
            Tu Cita está Programada Para:
        </div>
        <div style="font-size: 20px; font-weight: 800; color: #14532d; margin-bottom: 4px;">
            {{ $appointment->appointment_date ? $appointment->appointment_date->format('l, d \d\e F \d\e Y') : '' }}
        </div>
        <div style="font-size: 16px; font-weight: 700; color: #059669;">
            {{ substr($appointment->start_time, 0, 5) }} - {{ substr($appointment->end_time, 0, 5) }}
        </div>
    </div>

    <!-- Financial Breakdown Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Servicio:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">{{ $service->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Valor Total del Tratamiento:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">${{ number_format($appointment->total_amount, 0, ',', '.') }} COP</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Abono Aprobado:</td>
                        <td align="right" style="font-size: 14px; color: #059669; font-weight: 800;">${{ number_format($appointment->deposit_paid ?: $appointment->deposit_amount, 0, ',', '.') }} COP</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Saldo Restante a Pagar en Local:</td>
                        <td align="right" style="font-size: 15px; color: #0d9488; font-weight: 800;">${{ number_format($appointment->balance_due, 0, ',', '.') }} COP</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Actions -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <a href="{{ $voucherUrl }}" target="_blank" style="display: inline-block; background-color: #0d9488; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 12px; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);">
                    Ver Mi Comprobante Oficial
                </a>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-top: 10px;">
                <a href="{{ $calendarIcsUrl }}" target="_blank" style="display: inline-block; background-color: #f1f5f9; color: #334155; text-decoration: none; font-size: 13px; font-weight: 600; padding: 8px 18px; border-radius: 10px; border: 1px solid #cbd5e1;">
                    Añadir a mi Calendario (.ics)
                </a>
            </td>
        </tr>
        <tr>
            <td align="center" style="padding-top: 12px;">
                <a href="{{ $whatsappUrl }}" target="_blank" style="display: inline-block; color: #0f766e; text-decoration: underline; font-size: 13px; font-weight: 600;">
                    Contactar al Centro de Estética por WhatsApp
                </a>
            </td>
        </tr>
    </table>
@endsection
