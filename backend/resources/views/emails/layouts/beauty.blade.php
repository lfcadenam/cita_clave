<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Notificación de Cita' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; line-height: 1.5; -webkit-font-smoothing: antialiased;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; padding: 32px 16px;">
        <tr>
            <td align="center">
                <!-- Main Email Card -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 600px; background-color: #ffffff; border-radius: 20px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #0d9488; padding: 28px 32px; text-align: center;">
                            <div style="color: #ffffff; font-size: 20px; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px;">
                                {{ $tenant->name ?? 'Estudio Paola Aguilera' }}
                            </div>
                            <div style="color: rgba(255, 255, 255, 0.9); font-size: 13px; font-weight: 500;">
                                {{ $tenant->specialties ?? 'Centro de Estética & Belleza' }}
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 32px 32px 24px 32px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Salon Contact Card inside Footer -->
                    <tr>
                        <td style="padding: 0 32px 24px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border-radius: 14px; border: 1px solid #e2e8f0; padding: 16px 20px;">
                                <tr>
                                    <td>
                                        <div style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                                            Ubicación y Contacto
                                        </div>
                                        <div style="font-size: 13px; color: #334155; margin-bottom: 2px;">
                                            <strong>Dirección:</strong> {{ $tenant->address ?? 'Carrera 15 # 93-75, Chico, Bogotá' }}
                                        </div>
                                        <div style="font-size: 13px; color: #334155;">
                                            <strong>WhatsApp:</strong> +57 {{ $tenant->whatsapp_number ?? ($tenant->phone ?? '3103248385') }}
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f1f5f9; padding: 20px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; font-size: 11px; color: #64748b; line-height: 1.5;">
                                Este es un mensaje automático de confirmación generado por el sistema de reservas de <strong>{{ $tenant->displayName ?? 'Paola Aguilera' }}</strong>.
                            </p>
                            <p style="margin: 6px 0 0 0; font-size: 10px; color: #94a3b8;">
                                Tecnología Nuvex &bull; Todos los derechos reservados
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
