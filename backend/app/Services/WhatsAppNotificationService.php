<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    protected bool $enabled;
    protected string $driver;
    protected array $evolutionConfig;
    protected array $metaConfig;
    protected string $adminPhone;

    public function __construct()
    {
        $this->enabled = (bool) config('whatsapp.enabled', true);
        $this->driver = config('whatsapp.driver', 'evolution');
        $this->evolutionConfig = config('whatsapp.evolution', []);
        $this->metaConfig = config('whatsapp.meta', []);
        $this->adminPhone = config('whatsapp.admin_phone', '573106080402');
    }

    /**
     * Resuelve el driver activo ('meta', 'evolution', 'log') considerando configuración del Tenant o global.
     */
    public function resolveDriver(?Appointment $appointment = null, ?Tenant $tenant = null): string
    {
        $resolvedTenant = $tenant ?? $appointment?->tenant ?? (app()->has('active_tenant') ? app('active_tenant') : null);

        if ($resolvedTenant && ! empty($resolvedTenant->whatsapp_driver)) {
            return strtolower(trim($resolvedTenant->whatsapp_driver));
        }

        // Si el tenant tiene credenciales de Meta configuradas explícitamente, usar 'meta' si el driver global no fue forzado
        if ($resolvedTenant && ! empty($resolvedTenant->meta_phone_number_id) && ! empty($resolvedTenant->meta_access_token)) {
            return 'meta';
        }

        return strtolower(trim($this->driver ?: 'evolution'));
    }

    /**
     * Resuelve las credenciales de Meta Cloud API priorizando las del Tenant y haciendo fallback a .env.
     */
    public function resolveMetaCredentials(?Appointment $appointment = null, ?Tenant $tenant = null): array
    {
        $resolvedTenant = $tenant ?? $appointment?->tenant ?? (app()->has('active_tenant') ? app('active_tenant') : Tenant::where('is_active', true)->first());

        $apiUrl = rtrim($this->metaConfig['api_url'] ?? 'https://graph.facebook.com/v21.0', '/');
        $phoneId = ($resolvedTenant?->meta_phone_number_id ?: ($this->metaConfig['phone_number_id'] ?? ''));
        $wabaId = ($resolvedTenant?->meta_waba_id ?: ($this->metaConfig['waba_id'] ?? ''));
        $token = ($resolvedTenant?->meta_access_token ?: ($this->metaConfig['access_token'] ?? ''));
        $verifyToken = ($resolvedTenant?->meta_verify_token ?: ($this->metaConfig['verify_token'] ?? 'nuvex_meta_verify_2026'));
        $language = $this->metaConfig['language'] ?? 'es';
        $useTemplates = (bool) ($this->metaConfig['use_templates'] ?? false);
        $templates = $this->metaConfig['templates'] ?? [];

        return [
            'api_url' => $apiUrl,
            'phone_number_id' => trim((string) $phoneId),
            'waba_id' => trim((string) $wabaId),
            'access_token' => trim((string) $token),
            'verify_token' => trim((string) $verifyToken),
            'language' => trim((string) $language),
            'use_templates' => $useTemplates,
            'templates' => $templates,
        ];
    }

    /**
     * 1. Solicitud de Cita Creada (Acuse de recibo y pendiente de abono Nequi).
     */
    public function sendBookingCreated(Appointment $appointment): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $depositFormatted = number_format($appointment->deposit_amount, 0, ',', '.');
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "✨ *¡Solicitud de Cita Recibida!* Hola {$clientName},\n\n"
            . "Hemos recibido tu solicitud de cita *#{$appointment->appointment_number}*:\n\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "💵 *Abono requerido:* \${$depositFormatted} COP\n\n"
            . "📋 *Estado:* Pendiente de verificación de transferencia.\n"
            . "Paola validará tu comprobante de abono y te confirmaremos tu cita por este medio.\n\n"
            . "📄 *Consulta los detalles aquí:*\n"
            . "{$voucherUrl}\n\n"
            . "_¡Gracias por elegirnos!_";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'booking_created',
            templateParams: [
                $clientName,
                (string) $appointment->appointment_number,
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                "\${$depositFormatted} COP",
                $voucherUrl,
            ]
        );
    }

    /**
     * Resuelve dinámicamente el número de WhatsApp de la administradora/dueña del salón para la cita.
     */
    public function resolveAdminPhone(?Appointment $appointment = null): ?string
    {
        if ($appointment) {
            $appointment->loadMissing('tenant');
            $tenant = $appointment->tenant;

            if ($tenant) {
                // 1. WhatsApp directo configurado en el salón (Tenant)
                if (!empty($tenant->whatsapp_number)) {
                    return $this->sanitizePhoneNumber($tenant->whatsapp_number);
                }

                // 2. Teléfono principal del salón (Tenant)
                if (!empty($tenant->phone)) {
                    return $this->sanitizePhoneNumber($tenant->phone);
                }

                // 3. Teléfono del usuario con rol ADMIN del salón
                try {
                    $adminUserPhone = $tenant->users()
                        ->where('role', \App\Enums\UserRole::ADMIN)
                        ->whereNotNull('phone')
                        ->value('phone');

                    if (!empty($adminUserPhone)) {
                        return $this->sanitizePhoneNumber($adminUserPhone);
                    }
                } catch (\Throwable $e) {
                    // Fallback silencioso
                }
            }
        }

        // 4. Configuración global en .env / config('whatsapp.admin_phone')
        if (!empty($this->adminPhone)) {
            return $this->sanitizePhoneNumber($this->adminPhone);
        }

        return null;
    }

    /**
     * 2. Alerta inmediata a la administradora sobre una "Nueva Cita Agendada".
     */
    public function sendAdminNewBookingAlert(Appointment $appointment): bool
    {
        $adminPhone = $this->resolveAdminPhone($appointment);
        if (blank($adminPhone)) {
            Log::warning("No se pudo enviar WhatsApp de Nueva Cita Agendada: teléfono de administradora no configurado.");
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $serviceName = $appointment->service?->name ?? 'Tratamiento Especializado';
        $clientName = trim($appointment->client_name);
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY');
        $startTime = substr($appointment->start_time, 0, 5);
        $totalFormatted = number_format($appointment->total_amount, 0, ',', '.');
        $depositFormatted = number_format($appointment->deposit_amount, 0, ',', '.');
        $balanceFormatted = number_format($appointment->balance_due, 0, ',', '.');
        $adminUrl = url("/admin/appointments");

        // Estado descriptivo de la cita
        $statusText = match ($appointment->status) {
            \App\Enums\AppointmentStatus::CONFIRMED => '✅ Confirmada' . ($appointment->payment_method === \App\Enums\PaymentMethod::CASH_AT_LOCATION ? ' (Clienta Habitual - Pago en Salón)' : ''),
            \App\Enums\AppointmentStatus::PENDING_VERIFICATION => '⏳ Pendiente de Verificación de Abono (Nequi)',
            \App\Enums\AppointmentStatus::PENDING_DEPOSIT => '⏳ Pendiente de Pago en Línea',
            default => $appointment->status->label(),
        };

        // Enlace de WhatsApp directo para escribirle a la clienta en 1 toque
        $clientPhoneClean = preg_replace('/\D/', '', (string) $appointment->client_phone);
        $clientWaUrl = !empty($clientPhoneClean) ? "https://wa.me/57{$clientPhoneClean}" : null;
        $clientContact = !empty($clientWaUrl)
            ? "+57 {$appointment->client_phone} ({$clientWaUrl})"
            : ($appointment->client_phone ?: 'No registrado');

        $message = "🔔 *Nueva Cita Agendada: {$clientName} — {$serviceName}*\n\n"
            . "✨ *Detalles de la Cita:*\n"
            . "👤 *Clienta:* {$clientName}\n"
            . "📱 *WhatsApp:* {$clientContact}\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "💰 *Total Servicio:* \${$totalFormatted} COP\n"
            . "💵 *Abono:* \${$depositFormatted} COP\n"
            . "🏷️ *Saldo en Salón:* \${$balanceFormatted} COP\n"
            . "🔖 *Cita:* #{$appointment->appointment_number}\n"
            . "📋 *Estado:* {$statusText}\n\n"
            . "👉 *Ver y gestionar en el panel administrativo:*\n"
            . "{$adminUrl}";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $adminPhone,
            message: $message,
            templateKey: 'admin_new_booking',
            templateParams: [
                $clientName,
                (string) ($appointment->client_phone ?: 'N/A'),
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                "#{$appointment->appointment_number} ({$statusText})",
                $adminUrl,
            ]
        );
    }

    /**
     * 3. Abono Nequi Verificado y Cita Confirmada (disparado desde Filament).
     */
    public function sendDepositApproved(Appointment $appointment): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $salonName = $appointment->tenant?->name ?: 'Estudio Paola Aguilera';
        $salonCity = $appointment->tenant?->city ?: 'Bogotá';
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $depositFormatted = number_format($appointment->deposit_amount, 0, ',', '.');
        $balanceFormatted = number_format($appointment->balance_due, 0, ',', '.');
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🌸 *¡Abono Verificado y Cita Confirmada!* Hola {$clientName},\n\n"
            . "¡Buenas noticias! Hemos validado con éxito tu abono de *\${$depositFormatted} COP* para tu cita *#{$appointment->appointment_number}*.\n\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "📍 *Lugar:* {$salonName} ({$salonCity})\n"
            . "💵 *Saldo a pagar en local:* *\${$balanceFormatted} COP*\n\n"
            . "🎟️ *Comprobante digital actualizado:*\n"
            . "{$voucherUrl}\n\n"
            . "_Te esperamos puntualmente. Recibirás un recordatorio 24h antes._";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'deposit_approved',
            templateParams: [
                $clientName,
                (string) $appointment->appointment_number,
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                "\${$balanceFormatted} COP",
                $voucherUrl,
            ]
        );
    }

    /**
     * 4. Abono Nequi Rechazado (Comprobante no válido o no coincide).
     */
    public function sendDepositRejected(Appointment $appointment, ?string $reason = null): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "⚠️ *Novedad con tu Comprobante de Abono* Hola {$clientName},\n\n"
            . "Revisamos tu solicitud de cita *#{$appointment->appointment_number}* ({$serviceName}), pero no pudimos validar el comprobante de transferencia.\n\n"
            . ($reason ? "📝 *Motivo:* {$reason}\n\n" : "")
            . "👉 *Por favor sube nuevamente tu comprobante aquí para asegurar tu cupo:*\n"
            . "{$voucherUrl}\n\n"
            . "_O comunícate con nosotros si tienes alguna duda._";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'deposit_rejected',
            templateParams: [
                $clientName,
                (string) $appointment->appointment_number,
                $serviceName,
                $reason ?: 'Comprobante no legible o pendiente de validación',
                $voucherUrl,
            ]
        );
    }

    /**
     * 5. Cita Cancelada.
     */
    public function sendAppointmentCancelled(Appointment $appointment, ?string $reason = null): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $bookingUrl = url("/");

        $message = "🚫 *Cita Cancelada* Hola {$clientName},\n\n"
            . "Te informamos que tu cita *#{$appointment->appointment_number}* ({$serviceName}) programada para el *{$dateFormatted} a las {$startTime}* ha sido cancelada.\n\n"
            . ($reason ? "📝 *Motivo:* {$reason}\n\n" : "")
            . "Tu horario ha sido liberado. Si deseas agendar para otra ocasión, puedes hacerlo aquí:\n"
            . "{$bookingUrl}\n\n"
            . "_¡Esperamos verte pronto!_";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'appointment_cancelled',
            templateParams: [
                $clientName,
                (string) $appointment->appointment_number,
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                $reason ?: 'Cancelación registrada',
                $bookingUrl,
            ]
        );
    }

    /**
     * 5.1. Cita Reagendada / Reprogramada.
     */
    public function sendAppointmentRescheduled(Appointment $appointment, string $oldDate, string $oldTime): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🗓️ *¡Cita Reagendada con Éxito!* Hola {$clientName},\n\n"
            . "Hemos reprogramado tu cita *#{$appointment->appointment_number}*:\n\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Nueva Fecha:* {$dateFormatted}\n"
            . "⏰ *Nuevo Horario:* {$startTime}\n"
            . "📌 *Horario anterior:* {$oldDate} a las {$oldTime}\n\n"
            . "Tu abono y reserva se mantienen totalmente vigentes.\n\n"
            . "📄 *Consulta tu voucher digital actualizado:*\n"
            . "{$voucherUrl}\n\n"
            . "_¡Te esperamos con gusto en nuestro estudio!_";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'appointment_rescheduled',
            templateParams: [
                $clientName,
                (string) $appointment->appointment_number,
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                "{$oldDate} a las {$oldTime}",
                $voucherUrl,
            ]
        );
    }

    /**
     * 6. Recordatorio de cita con 24 horas de anticipación a la clienta (Cron).
     */
    public function sendReminder24h(Appointment $appointment): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            Log::warning("No se puede enviar WhatsApp para cita #{$appointment->appointment_number}: teléfono vacío.");
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $salonName = $appointment->tenant?->name ?: 'Paola Aguilera Belleza & Estética';
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $balanceFormatted = number_format($appointment->balance_due, 0, ',', '.');
        $confirmationUrl = url("/reserva/confirmar/{$appointment->appointment_number}");
        $portalUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "✨ *¡Hola {$clientName}!* Te saludamos de *{$salonName}*.\n\n"
            . "Te recordamos que tienes una cita programada para mañana:\n\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "💆‍♀️ *Servicio:* {$serviceName}\n"
            . "💵 *Saldo pendiente en local:* \${$balanceFormatted} COP\n\n"
            . "¿Nos confirmas tu asistencia el día de mañana?\n\n"
            . "👉 *Toca aquí para CONFIRMAR tu cita en 1 clic:*\n"
            . "{$confirmationUrl}\n\n"
            . "📄 *O consulta tu comprobante digital aquí:*\n"
            . "{$portalUrl}\n\n"
            . "_¡Te esperamos puntualmente para brindarte la mejor experiencia!_";

        $sent = $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'reminder_24h',
            templateParams: [
                $clientName,
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                "\${$balanceFormatted} COP",
                $confirmationUrl,
            ]
        );

        if ($sent) {
            $appointment->update([
                'reminder_24h_sent_at' => now(),
            ]);
        }

        return $sent;
    }

    /**
     * 7. Confirmación estándar de reserva (Bold u otros pagos instantáneos).
     */
    public function sendBookingConfirmation(Appointment $appointment): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $appointment->loadMissing(['tenant', 'service']);
        $salonName = $appointment->tenant?->name ?: 'Estudio Paola Aguilera';
        $salonCity = $appointment->tenant?->city ?: 'Bogotá';
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🌸 *¡Reserva Confirmada!* Hola {$clientName},\n\n"
            . "Tu cita *#{$appointment->appointment_number}* ha sido confirmada exitosamente:\n\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "📍 *Lugar:* {$salonName} ({$salonCity})\n\n"
            . "✅ *Confirma tu asistencia y consulta tu comprobante digital aquí:*\n"
            . "{$voucherUrl}\n\n"
            . "_¡Gracias por elegirnos! Recibirás un recordatorio 24h antes de tu cita._";

        return $this->dispatchAppointmentNotification(
            appointment: $appointment,
            phone: $clientPhone,
            message: $message,
            templateKey: 'booking_confirmed',
            templateParams: [
                $clientName,
                (string) $appointment->appointment_number,
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                $voucherUrl,
            ]
        );
    }

    /**
     * 8. Notificación cuando la clienta confirma su asistencia desde el enlace web.
     */
    public function sendAttendanceConfirmedNotification(Appointment $appointment): bool
    {
        $appointment->loadMissing(['tenant', 'service']);
        $salonName = $appointment->tenant?->name ?: 'La Belle Nails Bogotá';
        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $sentClient = false;
        if (!blank($appointment->client_phone)) {
            $clientMsg = "✅ *¡Asistencia Confirmada!* Hola {$clientName},\n\n"
                . "Hemos registrado tu confirmación de asistencia para la cita *#{$appointment->appointment_number}*:\n\n"
                . "💅 *Servicio:* {$serviceName}\n"
                . "📅 *Fecha:* {$dateFormatted}\n"
                . "⏰ *Hora:* {$startTime}\n"
                . "📍 *Lugar:* {$salonName}\n\n"
                . "📄 *Tu comprobante digital:*\n{$voucherUrl}\n\n"
                . "_¡Te esperamos puntualmente!_";

            $sentClient = $this->dispatchAppointmentNotification(
                appointment: $appointment,
                phone: $appointment->client_phone,
                message: $clientMsg,
                templateKey: 'attendance_confirmed',
                templateParams: [
                    $clientName,
                    (string) $appointment->appointment_number,
                    $serviceName,
                    "{$dateFormatted} a las {$startTime}",
                    $voucherUrl,
                ]
            );
        }

        return $sentClient;
    }

    /**
     * Orquesta el envío de una notificación de cita adaptándose automáticamente a Meta Cloud API (Plantillas + Texto) o Evolution API.
     */
    protected function dispatchAppointmentNotification(
        Appointment $appointment,
        string $phone,
        string $message,
        string $templateKey,
        array $templateParams = []
    ): bool {
        if (!$this->enabled) {
            Log::info("[WhatsApp Disabled] Mensaje no enviado a {$phone}: {$message}");
            return true;
        }

        $sanitizedPhone = $this->sanitizePhoneNumber($phone);
        $activeDriver = $this->resolveDriver($appointment);

        try {
            if ($activeDriver === 'meta') {
                $metaCreds = $this->resolveMetaCredentials($appointment);
                $templateName = $metaCreds['templates'][$templateKey] ?? null;

                // Estrategia 1: Si use_templates está activo y existe nombre de plantilla, intentar en es_CO y es
                if ($metaCreds['use_templates'] && !empty($templateName)) {
                    $langsToTry = array_values(array_unique([$metaCreds['language'], 'es_CO', 'es', 'es_ES']));
                    foreach ($langsToTry as $langCode) {
                        $tplRes = $this->sendTemplateViaMetaWithDetails(
                            phone: $sanitizedPhone,
                            templateName: $templateName,
                            bodyParameters: $templateParams,
                            languageCode: $langCode,
                            tenant: $appointment->tenant,
                            buttonParameters: [(string) ($appointment->appointment_number ?? '1')]
                        );

                        if ($tplRes['success']) {
                            return true;
                        }
                    }

                    Log::warning("[WhatsApp Meta] Plantilla '{$templateName}' aún en revisión o no disponible ({$tplRes['message']}). Usando plantilla preaprobada de respaldo con link...");
                }

                // Estrategia 2: Fallback garantizado a la plantilla preaprobada de Meta (jaspers_market_order_confirmation_v1)
                // porque Meta bloquea mensajes de texto libre (type: text) si no hay ventana de 24h abierta con el destinatario.
                $appointment->loadMissing('service');
                $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");
                $sandboxParams = [
                    trim((string) ($appointment->client_name ?: 'Clienta')),
                    "#{$appointment->appointment_number} (" . ($appointment->service?->name ?? 'Cita') . ")",
                    Carbon::parse($appointment->appointment_date)->format('d/m/Y') . ' ' . substr((string) $appointment->start_time, 0, 5) . " - Confirma tu asistencia en: {$voucherUrl}",
                ];

                $sandboxRes = $this->sendTemplateViaMetaWithDetails(
                    phone: $sanitizedPhone,
                    templateName: 'jaspers_market_order_confirmation_v1',
                    bodyParameters: $sandboxParams,
                    languageCode: 'en_US',
                    tenant: $appointment->tenant
                );

                if ($sandboxRes['success']) {
                    return true;
                }

                // Estrategia 3: Enviar mensaje de texto enriquecido (si hay ventana de 24h abierta)
                $textRes = $this->sendViaMetaWithDetails($sanitizedPhone, $message, $appointment->tenant);
                if ($textRes['success']) {
                    return true;
                }

                $this->sendViaLog($sanitizedPhone, $message);
                return false;
            }

            if ($activeDriver === 'evolution') {
                return $this->sendViaEvolution($sanitizedPhone, $message);
            }

            return $this->sendViaLog($sanitizedPhone, $message);
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Exception] Error enviando notificación ({$templateKey}) a {$sanitizedPhone}: " . $e->getMessage());
            $this->sendViaLog($sanitizedPhone, $message);
            return false;
        }
    }

    /**
     * Envío seguro con manejo de excepciones (nunca arroja error fatal).
     */
    public function sendTextMessageSafe(string $phone, string $message, ?Tenant $tenant = null): bool
    {
        try {
            return $this->sendTextMessage($phone, $message, $tenant);
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Exception] Error enviando mensaje a {$phone}: " . $e->getMessage());
            $this->sendViaLog($phone, $message);
            return false;
        }
    }

    /**
     * Envía un mensaje de texto plano a un número de teléfono.
     */
    public function sendTextMessage(string $phone, string $message, ?Tenant $tenant = null): bool
    {
        if (!$this->enabled) {
            Log::info("[WhatsApp Disabled] Mensaje no enviado a {$phone}: {$message}");
            return true;
        }

        $sanitizedPhone = $this->sanitizePhoneNumber($phone);
        $activeDriver = $this->resolveDriver(null, $tenant);

        return match ($activeDriver) {
            'evolution' => $this->sendViaEvolution($sanitizedPhone, $message),
            'meta' => $this->sendViaMeta($sanitizedPhone, $message, $tenant),
            default => $this->sendViaLog($sanitizedPhone, $message),
        };
    }

    /**
     * Envía un mensaje con retorno detallado del resultado (útil para pruebas en UI y diagnósticos).
     */
    public function sendTextMessageWithDetails(string $phone, string $message, ?Tenant $tenant = null): array
    {
        if (!$this->enabled) {
            return [
                'success' => false,
                'message' => 'WhatsApp está deshabilitado en configuración (WHATSAPP_ENABLED=false).',
            ];
        }

        $sanitizedPhone = $this->sanitizePhoneNumber($phone);
        $activeDriver = $this->resolveDriver(null, $tenant);

        if ($activeDriver === 'evolution') {
            return $this->sendViaEvolutionWithDetails($sanitizedPhone, $message);
        }

        if ($activeDriver === 'meta') {
            return $this->sendViaMetaWithDetails($sanitizedPhone, $message, $tenant);
        }

        $sent = $this->sendViaLog($sanitizedPhone, $message);
        return [
            'success' => $sent,
            'message' => "Mensaje registrado en el log local para +{$sanitizedPhone} (driver: log).",
        ];
    }

    /**
     * Envío a través de Evolution API (Gateway QR / Baileys).
     */
    protected function sendViaEvolution(string $phone, string $message): bool
    {
        $result = $this->sendViaEvolutionWithDetails($phone, $message);
        return $result['success'];
    }

    /**
     * Obtiene la lista de URLs candidatas ordenadas por probabilidad de éxito.
     */
    public function getEvolutionCandidateUrls(): array
    {
        $baseUrl = $this->evolutionConfig['base_url'] ?? 'http://host.docker.internal:8085';

        return array_unique(array_filter([
            'http://host.docker.internal:8085',
            $baseUrl,
            str_replace([':8080', ':8085'], ':8085', $baseUrl),
            str_contains($baseUrl, 'localhost') ? str_replace('localhost', 'host.docker.internal', $baseUrl) : null,
            'http://localhost:8085',
            'http://host.docker.internal:8080',
            'http://localhost:8080',
        ]));
    }

    /**
     * Ejecuta el envío vía Evolution API con soporte dual v1/v2 y diagnóstico detallado.
     */
    public function sendViaEvolutionWithDetails(string $phone, string $message): array
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $sanitizedPhone = $this->sanitizePhoneNumber($phone);

        // 1. Detectar la URL que realmente está respondiendo
        $candidateUrls = $this->getEvolutionCandidateUrls();
        $targetUrl = null;

        foreach ($candidateUrls as $url) {
            try {
                $check = Http::withHeaders(['apikey' => $apiKey])->timeout(3)->get("{$url}/instance/connectionState/{$instance}");
                if ($check->successful()) {
                    $targetUrl = $url;
                    break;
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        if (!$targetUrl) {
            $targetUrl = 'http://host.docker.internal:8085';
        }

        $endpoint = "{$targetUrl}/message/sendText/{$instance}";

        // 2. Variantes de payload aceptadas por Evolution API v2 y Baileys
        $payloadVariants = [
            // Variante 1: Formato directo sin delay artificial (rápido e inmediato)
            [
                'number' => $sanitizedPhone,
                'text' => $message,
                'linkPreview' => false,
            ],
            // Variante 2: Anidado textMessage Evolution API v2
            [
                'number' => $sanitizedPhone,
                'textMessage' => [
                    'text' => $message,
                ],
            ],
            // Variante 3: Texto plano mínimo
            [
                'number' => $sanitizedPhone,
                'text' => $message,
            ],
            // Variante 4: Con sufijo WhatsApp JID
            [
                'number' => "{$sanitizedPhone}@s.whatsapp.net",
                'text' => $message,
            ],
        ];

        $lastError = 'No se obtuvo respuesta exitosa de Evolution API';
        $lastStatus = 500;

        foreach ($payloadVariants as $idx => $payload) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(25)->post($endpoint, $payload);

                if ($response->successful()) {
                    Log::info("[WhatsApp Evolution] Mensaje enviado a {$sanitizedPhone} vía {$targetUrl} (variante {$idx}): " . substr($message, 0, 50) . "...");
                    return [
                        'success' => true,
                        'message' => "Mensaje enviado exitosamente a +{$sanitizedPhone}.",
                        'url' => $targetUrl,
                        'data' => $response->json(),
                    ];
                }

                $body = $response->json() ?? $response->body();
                $errorMsg = is_array($body)
                    ? ($body['response']['message'][0] ?? $body['message'] ?? $body['error'] ?? json_encode($body))
                    : (string) $body;

                $lastError = "{$targetUrl} HTTP {$response->status()}: {$errorMsg}";
                $lastStatus = $response->status();
                Log::warning("[WhatsApp Evolution] Intento payload variante {$idx} falló en {$endpoint} ({$response->status()}): " . (is_string($body) ? $body : json_encode($body)));
            } catch (\Throwable $e) {
                $lastError = "{$targetUrl}: " . $e->getMessage();
                Log::warning("[WhatsApp Evolution] Excepción al enviar a {$endpoint}: " . $e->getMessage());
            }
        }

        // Si fallaron los intentos, registrar en log de respaldo
        $this->sendViaLog($sanitizedPhone, $message);

        return [
            'success' => false,
            'message' => "No se pudo enviar el mensaje. Detalle: {$lastError}",
            'status' => $lastStatus,
        ];
    }

    /**
     * Envío de mensaje de texto libre a través de Meta Cloud API.
     */
    protected function sendViaMeta(string $phone, string $message, ?Tenant $tenant = null): bool
    {
        $res = $this->sendViaMetaWithDetails($phone, $message, $tenant);
        return $res['success'];
    }

    /**
     * Envío de mensaje de texto libre a través de Meta Cloud API con diagnóstico detallado.
     */
    public function sendViaMetaWithDetails(string $phone, string $message, ?Tenant $tenant = null): array
    {
        $creds = $this->resolveMetaCredentials(null, $tenant);
        $apiUrl = $creds['api_url'];
        $phoneId = $creds['phone_number_id'];
        $token = $creds['access_token'];
        $sanitizedPhone = $this->sanitizePhoneNumber($phone);

        if (empty($phoneId) || empty($token)) {
            $msg = 'Faltan credenciales de Meta Cloud API (WHATSAPP_PHONE_NUMBER_ID o WHATSAPP_ACCESS_TOKEN).';
            Log::warning("[WhatsApp Meta] {$msg}");
            $this->sendViaLog($sanitizedPhone, $message);
            return [
                'success' => false,
                'message' => $msg,
                'error_code' => 'MISSING_CREDENTIALS',
            ];
        }

        $endpoint = "{$apiUrl}/{$phoneId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $sanitizedPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message,
            ],
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(12)
                ->post($endpoint, $payload);

            $json = $response->json() ?? [];

            if ($response->successful()) {
                $wamid = $json['messages'][0]['id'] ?? 'OK';
                Log::info("[WhatsApp Meta] Mensaje de texto enviado a +{$sanitizedPhone} (wamid: {$wamid}).");
                return [
                    'success' => true,
                    'message' => "Mensaje enviado exitosamente a +{$sanitizedPhone} vía Meta Cloud API (ID: {$wamid}).",
                    'wamid' => $wamid,
                    'data' => $json,
                ];
            }

            $parsedError = $this->formatMetaError($response->status(), $json);
            Log::warning("[WhatsApp Meta] Error HTTP {$response->status()} enviando texto a +{$sanitizedPhone}: {$parsedError['message']}");
            return $parsedError;
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Meta] Excepción enviando texto a +{$sanitizedPhone}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Excepción de red al conectar con Meta Graph API: ' . $e->getMessage(),
                'error_code' => 'NETWORK_EXCEPTION',
            ];
        }
    }

    /**
     * Envía una Plantilla Oficial aprobada en Meta Cloud API (type = "template").
     * Indispensable para notificaciones iniciadas por el negocio fuera de la ventana de 24 horas.
     */
    public function sendTemplateViaMetaWithDetails(
        string $phone,
        string $templateName,
        array $bodyParameters = [],
        ?string $languageCode = null,
        ?Tenant $tenant = null,
        array $buttonParameters = []
    ): array {
        $creds = $this->resolveMetaCredentials(null, $tenant);
        $apiUrl = $creds['api_url'];
        $phoneId = $creds['phone_number_id'];
        $token = $creds['access_token'];
        $lang = $languageCode ?: ($templateName === 'hello_world' ? 'en_US' : $creds['language']);
        $sanitizedPhone = $this->sanitizePhoneNumber($phone);

        if (empty($phoneId) || empty($token)) {
            $msg = 'Faltan credenciales de Meta Cloud API (WHATSAPP_PHONE_NUMBER_ID o WHATSAPP_ACCESS_TOKEN).';
            Log::warning("[WhatsApp Meta] {$msg}");
            return [
                'success' => false,
                'message' => $msg,
                'error_code' => 'MISSING_CREDENTIALS',
            ];
        }

        $templatePayload = [
            'name' => trim($templateName),
            'language' => [
                'code' => trim($lang),
            ],
        ];

        $components = [];

        if (!empty($bodyParameters)) {
            $formattedParams = [];
            foreach ($bodyParameters as $param) {
                // Meta no permite saltos de línea (\n), tabulaciones (\t) ni más de 4 espacios seguidos dentro de parámetros de plantilla
                $cleanText = preg_replace('/[\r\n\t]+/', ' ', (string) $param);
                $cleanText = preg_replace('/\s{2,}/', ' ', trim($cleanText));
                $formattedParams[] = [
                    'type' => 'text',
                    'text' => mb_substr($cleanText ?: '-', 0, 1024),
                ];
            }

            $components[] = [
                'type' => 'body',
                'parameters' => $formattedParams,
            ];
        }

        if (!empty($buttonParameters)) {
            foreach ($buttonParameters as $btnIdx => $btnVal) {
                $components[] = [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => (string) $btnIdx,
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => (string) $btnVal,
                        ],
                    ],
                ];
            }
        }

        if (!empty($components)) {
            $templatePayload['components'] = $components;
        }

        $endpoint = "{$apiUrl}/{$phoneId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $sanitizedPhone,
            'type' => 'template',
            'template' => $templatePayload,
        ];

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(12)
                ->post($endpoint, $payload);

            $json = $response->json() ?? [];

            if ($response->successful()) {
                $wamid = $json['messages'][0]['id'] ?? 'OK';
                Log::info("[WhatsApp Meta] Plantilla '{$templateName}' ({$lang}) enviada a +{$sanitizedPhone} (wamid: {$wamid}).");
                return [
                    'success' => true,
                    'message' => "Plantilla '{$templateName}' enviada exitosamente a +{$sanitizedPhone} vía Meta Cloud API (ID: {$wamid}).",
                    'wamid' => $wamid,
                    'data' => $json,
                ];
            }

            // Si la plantilla requiere botón dinámico URL y no se incluyó, auto-reintentar con el ID/número de cita
            $errorDetails = $json['error']['error_data']['details'] ?? '';
            if (str_contains($errorDetails, 'Button at index 0 of type Url requires a parameter') && empty($buttonParameters)) {
                $fallbackBtn = $bodyParameters[1] ?? '1';
                Log::info("[WhatsApp Meta] Reintentando plantilla '{$templateName}' con parámetro de botón URL index 0 ({$fallbackBtn})...");
                return $this->sendTemplateViaMetaWithDetails(
                    $phone,
                    $templateName,
                    $bodyParameters,
                    $languageCode,
                    $tenant,
                    [$fallbackBtn]
                );
            }

            $parsedError = $this->formatMetaError($response->status(), $json);
            Log::warning("[WhatsApp Meta] Error HTTP {$response->status()} enviando plantilla '{$templateName}' a +{$sanitizedPhone}: {$parsedError['message']}");
            return $parsedError;
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Meta] Excepción enviando plantilla '{$templateName}' a +{$sanitizedPhone}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Excepción de red al conectar con Meta Graph API: ' . $e->getMessage(),
                'error_code' => 'NETWORK_EXCEPTION',
            ];
        }
    }

    /**
     * Consulta en vivo el estado del número y cuenta en Meta Graph API.
     */
    public function getMetaAccountStatus(?Tenant $tenant = null): array
    {
        $creds = $this->resolveMetaCredentials(null, $tenant);
        $apiUrl = $creds['api_url'];
        $phoneId = $creds['phone_number_id'];
        $token = $creds['access_token'];

        if (empty($phoneId) || empty($token)) {
            return [
                'connected' => false,
                'state' => 'unconfigured',
                'message' => 'Falta configurar WHATSAPP_PHONE_NUMBER_ID y WHATSAPP_ACCESS_TOKEN en .env o en el panel SuperAdmin.',
            ];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(8)
                ->get("{$apiUrl}/{$phoneId}", [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,name_status',
                ]);

            $json = $response->json() ?? [];

            if ($response->successful() && !empty($json['id'])) {
                return [
                    'connected' => true,
                    'state' => 'connected',
                    'phone_number_id' => $json['id'],
                    'display_phone_number' => $json['display_phone_number'] ?? 'No disponible',
                    'verified_name' => $json['verified_name'] ?? 'WhatsApp Business API',
                    'quality_rating' => $json['quality_rating'] ?? 'GREEN',
                    'code_verification_status' => $json['code_verification_status'] ?? 'VERIFIED',
                    'message' => 'Conectado correctamente a Meta WhatsApp Cloud API.',
                ];
            }

            $parsed = $this->formatMetaError($response->status(), $json);
            return [
                'connected' => false,
                'state' => 'error',
                'message' => $parsed['message'],
                'error_code' => $parsed['error_code'] ?? $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'state' => 'unreachable',
                'message' => 'Error conectando a Meta Graph API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Consulta las plantillas oficiales registradas y aprobadas en la WABA de Meta.
     */
    public function getMetaTemplates(?Tenant $tenant = null): array
    {
        $creds = $this->resolveMetaCredentials(null, $tenant);
        $apiUrl = $creds['api_url'];
        $wabaId = $creds['waba_id'];
        $token = $creds['access_token'];

        if (empty($wabaId) || empty($token)) {
            return [
                'success' => false,
                'message' => 'Falta WABA ID o Access Token para consultar las plantillas en Meta.',
                'templates' => [],
            ];
        }

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->get("{$apiUrl}/{$wabaId}/message_templates", [
                    'limit' => 50,
                ]);

            if ($response->successful()) {
                $data = $response->json()['data'] ?? [];
                return [
                    'success' => true,
                    'templates' => $data,
                ];
            }

            $parsed = $this->formatMetaError($response->status(), $response->json());
            return [
                'success' => false,
                'message' => $parsed['message'],
                'templates' => [],
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Excepción conectando con Meta: ' . $e->getMessage(),
                'templates' => [],
            ];
        }
    }

    /**
     * Traduce errores de Meta Graph API a diagnósticos claros en español.
     */
    protected function formatMetaError(int $httpStatus, mixed $body): array
    {
        $err = is_array($body) ? ($body['error'] ?? []) : [];
        $code = $err['code'] ?? $httpStatus;
        $subcode = $err['error_subcode'] ?? null;
        $rawMsg = $err['message'] ?? (is_string($body) ? $body : json_encode($body));
        $details = $err['error_data']['details'] ?? null;

        $hint = match ((int) $code) {
            190 => 'El Access Token de Meta ha expirado o es inválido. Genera un Token Permanente de Usuario del Sistema en Meta Business Suite.',
            131030 => 'El número destinatario no está en la lista de números de prueba permitidos (Modo Sandbox de Meta). Agrégalo en Meta for Developers > WhatsApp > Configuración de la API (campo "Para" / Manage phone number list) o pasa el número a Producción.',
            131047, 131026 => 'Han pasado más de 24 horas desde el último mensaje del destinatario. Para iniciar conversaciones fuera de la ventana de 24h, Meta exige usar una Plantilla aprobada (type: template).',
            131058 => 'La plantilla "hello_world" sólo está permitida por Meta en números públicos de prueba (sandbox). Al estar usando un número real o Phone Number ID de producción, Meta bloquea "hello_world". Debes usar una plantilla propia aprobada en tu Meta WhatsApp Manager (por ejemplo: "reserva_confirmada", "recordatorio_cita_24h" o "cita_recibida") con su idioma correspondiente (ej: "es_CO" o "es").',
            132000, 132001 => 'La plantilla solicitada no existe o aún no está aprobada en el idioma indicado dentro de Meta WhatsApp Manager.',
            131009, 100 => 'Parámetro inválido en la petición a Meta Graph API (#100). Causas principales: 1) El número destinatario es el mismo número comercial de WhatsApp del salón (Meta no permite auto-mensajes). 2) La cantidad de variables enviadas no coincide con las definidas en la plantilla en WhatsApp Manager (ej. enviar 5 variables a una plantilla de 4 o sin variables).',
            default => null,
        };

        $fullMessage = "Meta API Error (#{$code}" . ($subcode ? "/{$subcode}" : "") . "): {$rawMsg}"
            . ($details ? " — Detalle: {$details}" : "")
            . ($hint ? " | Sugerencia: {$hint}" : "");

        return [
            'success' => false,
            'status' => $httpStatus,
            'error_code' => $code,
            'error_subcode' => $subcode,
            'message' => $fullMessage,
            'raw' => $body,
        ];
    }

    /**
     * Driver Log para desarrollo o pruebas.
     */
    protected function sendViaLog(string $phone, string $message): bool
    {
        Log::info("================ [WHATSAPP SIMULADO] ================\n"
            . "Para: {$phone}\n"
            . "Mensaje:\n{$message}\n"
            . "======================================================");
        return true;
    }

    /**
     * Genera enlace dinámico directo a WhatsApp para abrir en navegador o app móvil.
     */
    public function generateWhatsAppUrl(string $phone, string $message, bool $webOnly = false): string
    {
        $sanitizedPhone = $this->sanitizePhoneNumber($phone);
        $encodedText = rawurlencode($message);

        if ($webOnly) {
            return "https://web.whatsapp.com/send?phone={$sanitizedPhone}&text={$encodedText}";
        }

        return "https://wa.me/{$sanitizedPhone}?text={$encodedText}";
    }

    /**
     * Consulta el estado de conexión de la instancia en Evolution API.
     */
    public function getEvolutionInstanceStatus(): array
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $candidateUrls = $this->getEvolutionCandidateUrls();

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                ])->timeout(4)->get("{$url}/instance/connectionState/{$instance}");

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        return ['state' => 'unreachable', 'message' => 'No se pudo conectar a Evolution API en ningún endpoint'];
    }

    /**
     * Crea la instancia en Evolution API si aún no existe.
     */
    public function createEvolutionInstance(): array
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $candidateUrls = $this->getEvolutionCandidateUrls();

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(8)->post("{$url}/instance/create", [
                    'instanceName' => $instance,
                    'qrcode' => true,
                    'integration' => 'WHATSAPP-BAILEYS',
                ]);

                if ($response->successful() || $response->status() === 403 || $response->status() === 400) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        return ['error' => 'No se pudo conectar con el servicio Evolution API'];
    }

    /**
     * Obtiene el código QR en base64 para vincular la instancia.
     */
    public function getEvolutionQrCode(): ?string
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $candidateUrls = $this->getEvolutionCandidateUrls();

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                ])->timeout(8)->get("{$url}/instance/connect/{$instance}");

                if ($response->successful()) {
                    $json = $response->json();
                    $base64 = $json['base64'] 
                        ?? $json['qrcode']['base64'] 
                        ?? (is_string($json['qrcode'] ?? null) ? $json['qrcode'] : null);

                    if (!empty($base64)) {
                        return str_starts_with($base64, 'data:image')
                            ? $base64
                            : 'data:image/png;base64,' . $base64;
                    }
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        return null;
    }

    /**
     * Obtiene el código de emparejamiento numérico (8 dígitos) para vincular por teléfono sin cámara.
     */
    public function getPairingCode(string $phone): ?string
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $sanitizedPhone = $this->sanitizePhoneNumber($phone);
        $candidateUrls = $this->getEvolutionCandidateUrls();

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                ])->timeout(8)->get("{$url}/instance/connect/{$instance}?number={$sanitizedPhone}");

                if ($response->successful()) {
                    $json = $response->json();
                    return $json['pairingCode'] ?? null;
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        return null;
    }

    /**
     * Normaliza el número al formato internacional estándar (E.164 sin signo +).
     * Ejemplo para Colombia: 3106080402 -> 573106080402
     */
    public function sanitizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Si empieza por 0 y luego 3 (ej: 0310...), quitar el 0 inicial
        if (strlen($clean) === 11 && str_starts_with($clean, '03')) {
            $clean = substr($clean, 1);
        }

        // Si tiene 10 dígitos (celular de Colombia ej: 310...), anteponer '57'
        if (strlen($clean) === 10 && str_starts_with($clean, '3')) {
            return '57' . $clean;
        }

        return $clean;
    }

    /**
     * Cierra la sesión activa en Evolution API (Logout).
     */
    public function logoutEvolutionInstance(): bool
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $candidateUrls = $this->getEvolutionCandidateUrls();

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders(['apikey' => $apiKey])->timeout(5)->delete("{$url}/instance/logout/{$instance}");
                if ($response->successful()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        return false;
    }

    /**
     * Elimina por completo la instancia de Evolution API para permitir recrear una limpia desde cero.
     */
    public function deleteEvolutionInstance(): bool
    {
        $apiKey = $this->evolutionConfig['api_key'] ?? 'nuvex_evolution_key_2026';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';
        $candidateUrls = $this->getEvolutionCandidateUrls();

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders(['apikey' => $apiKey])->timeout(5)->delete("{$url}/instance/delete/{$instance}");
                if ($response->successful()) {
                    return true;
                }
            } catch (\Throwable $e) {
                // Siguiente candidato
            }
        }

        return false;
    }
}
