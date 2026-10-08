@extends('emails.layouts.beauty', ['title' => 'Nueva Cita Agendada', 'tenant' => $tenant])

@section('content')
    <div style="margin-bottom: 24px;">
        <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 700; background-color: #f1f5f9; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;">
            Aviso para Administrador
        </span>
        <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">
            ¡Nueva Cita Agendada!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.6;">
            La clienta <strong>{{ $appointment->client_name }}</strong> ha programado una cita en tu plataforma. A continuación los detalles completos:
        </p>
    </div>

    <!-- Client Info Box -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Clienta:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">{{ $appointment->client_name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">WhatsApp / Teléfono:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 600;">+57 {{ $appointment->client_phone }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        @if($appointment->client_email)
            <tr>
                <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="font-size: 13px; color: #64748b; font-weight: 500;">Correo Electrónico:</td>
                            <td align="right" style="font-size: 13px; color: #334155; font-weight: 500;">{{ $appointment->client_email }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        @endif
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Servicio Solicitado:</td>
                        <td align="right" style="font-size: 14px; color: #0d9488; font-weight: 700;">{{ $service->name }}</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Fecha Programada:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">
                            {{ $appointment->appointment_date ? $appointment->appointment_date->format('d/m/Y') : '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Horario:</td>
                        <td align="right" style="font-size: 14px; color: #0f172a; font-weight: 700;">
                            {{ substr($appointment->start_time, 0, 5) }} - {{ substr($appointment->end_time, 0, 5) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 20px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; font-weight: 500;">Estado Actual:</td>
                        <td align="right" style="font-size: 13px; color: #0f172a; font-weight: 700;">
                            {{ $appointment->status->label() }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Admin CTA Buttons -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top: 10px;">
        <tr>
            <td align="center">
                <a href="{{ $adminPanelUrl }}" target="_blank" style="display: inline-block; background-color: #0d9488; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 12px; box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);">
                    Ver Cita en Panel de Control
                </a>
            </td>
        </tr>
        @if($clientWhatsappUrl)
            <tr>
                <td align="center" style="padding-top: 12px;">
                    <a href="{{ $clientWhatsappUrl }}" target="_blank" style="display: inline-block; color: #0f766e; text-decoration: underline; font-size: 13px; font-weight: 600;">
                        Escribir a la clienta por WhatsApp (+57 {{ $appointment->client_phone }})
                    </a>
                </td>
            </tr>
        @endif
    </table>
@endsection
