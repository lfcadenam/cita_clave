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
        $useTemplates = (bool) ($this->metaConfig['use_templates'] ?? true);
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $depositFormatted = number_format($appointment->deposit_amount, 0, ',', '.');
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "✨ *¡Solicitud de Cita Recibida!* Hola {$clientName},\n\n"
            . "Hemos recibido tu solicitud de cita para *{$serviceName}* el día *{$dateFormatted} a las {$startTime}*:\n\n"
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $serviceName = $appointment->service?->name ?? 'Tratamiento Especializado';
        $clientNickname = $appointment->customer?->nickname;
        $clientName = !empty($clientNickname)
            ? trim($appointment->client_name) . " ({$clientNickname})"
            : trim($appointment->client_name);
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $salonName = $appointment->tenant?->name ?: 'Estudio Paola Aguilera';
        $salonCity = $appointment->tenant?->city ?: 'Bogotá';
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $depositFormatted = number_format($appointment->deposit_amount, 0, ',', '.');
        $balanceFormatted = number_format($appointment->balance_due, 0, ',', '.');
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🌸 *¡Abono Verificado y Cita Confirmada!* Hola {$clientName},\n\n"
            . "¡Buenas noticias! Hemos validado con éxito tu abono de *\${$depositFormatted} COP* para tu cita de *{$serviceName}*.\n\n"
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "⚠️ *Novedad con tu Comprobante de Abono* Hola {$clientName},\n\n"
            . "Revisamos tu solicitud de cita para *{$serviceName}*, pero no pudimos validar el comprobante de transferencia.\n\n"
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $bookingUrl = url("/");

        $message = "🚫 *Cita Cancelada* Hola {$clientName},\n\n"
            . "Te informamos que tu cita para *{$serviceName}* programada para el *{$dateFormatted} a las {$startTime}* ha sido cancelada.\n\n"
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
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                $reason ?: 'Cancelación registrada',
                $bookingUrl,
            ],
            extraContext: ['reason' => $reason]
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🗓️ *¡Cita Reagendada con Éxito!* Hola {$clientName},\n\n"
            . "Hemos reprogramado tu cita para *{$serviceName}*:\n\n"
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
                $serviceName,
                "{$dateFormatted} a las {$startTime}",
                "{$oldDate} a las {$oldTime}",
                $voucherUrl,
            ],
            extraContext: ['previous_date' => "{$oldDate} a las {$oldTime}"]
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $salonName = $appointment->tenant?->name ?: 'Paola Aguilera Belleza & Estética';
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $balanceFormatted = number_format($appointment->balance_due, 0, ',', '.');
        $confirmationUrl = url("/reserva/confirmar/{$appointment->appointment_number}?token=" . $appointment->generateSecurityToken());
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

        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $salonName = $appointment->tenant?->name ?: 'Estudio Paola Aguilera';
        $salonCity = $appointment->tenant?->city ?: 'Bogotá';
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🌸 *¡Reserva Confirmada!* Hola {$clientName},\n\n"
            . "Tu cita para *{$serviceName}* ha sido confirmada exitosamente:\n\n"
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
        $appointment->loadMissing(['tenant', 'service', 'customer']);
        $salonName = $appointment->tenant?->name ?: 'La Belle Nails Bogotá';
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $sentClient = false;
        if (!blank($appointment->client_phone)) {
            $clientMsg = "✅ *¡Asistencia Confirmada!* Hola {$clientName},\n\n"
                . "Hemos registrado tu confirmación de asistencia para tu cita de *{$serviceName}*:\n\n"
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
        array $templateParams = [],
        array $extraContext = []
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
                if (empty($templateName)) {
                    $templateName = $this->resolveTemplateKey($templateKey);
                }

                // Resolver dinámicamente los parámetros para evitar error #100
                $dynamicParams = $this->resolveDynamicTemplateParameters(
                    appointment: $appointment,
                    templateKey: $templateKey,
                    fallbackParams: $templateParams,
                    extraContext: $extraContext
                );

                // Anti auto-envío (Meta Error #100): si el destinatario es el mismo teléfono comercial del salón
                $businessPhoneClean = $this->sanitizePhoneNumber((string) ($appointment->tenant?->whatsapp_number ?: $this->resolveAdminPhone($appointment)));
                if (!empty($businessPhoneClean) && $sanitizedPhone === $businessPhoneClean) {
                    Log::warning("[WhatsApp Meta] Se omitió envío a +{$sanitizedPhone}: es el mismo número comercial del salón (Meta prohíbe auto-mensajes a su propia cuenta).");
                    return false;
                }

                // Estrategia 1: Si use_templates está activo y existe nombre de plantilla, intentar en es_CO y es
                if ($metaCreds['use_templates'] && !empty($templateName)) {
                    $candidateNames = [$templateName];
                    if ($templateName === 'confirmacion_cita') {
                        $candidateNames[] = 'reserva_confirmada';
                    } elseif ($templateName === 'reserva_confirmada') {
                        $candidateNames[] = 'confirmacion_cita';
                    }

                    $langsToTry = array_values(array_unique([$metaCreds['language'], 'es_CO', 'es', 'es_ES']));
                    foreach ($candidateNames as $candName) {
                        foreach ($langsToTry as $langCode) {
                            $tplRes = $this->sendTemplateViaMetaWithDetails(
                                phone: $sanitizedPhone,
                                templateName: $candName,
                                bodyParameters: $dynamicParams,
                                languageCode: $langCode,
                                tenant: $appointment->tenant,
                                buttonParameters: []
                            );

                            if ($tplRes['success']) {
                                return true;
                            }
                        }
                    }

                    $tplErrMsg = $tplRes['message'] ?? 'falló';
                    Log::warning("[WhatsApp Meta] Plantilla '{$templateName}' no entregada ({$tplErrMsg}). Intentando plantilla preaprobada de respaldo con link...");
                }

                // Estrategia 2: Fallback oficial a la plantilla pre-aprobada en Meta para producción (confirmacion_cita / reserva_confirmada)
                $appointment->loadMissing(['service', 'customer']);
                $apptNum = (string) $appointment->appointment_number;
                $clientName = $appointment->getNotificationClientName();
                $serviceName = $appointment->service?->name ?? 'Servicio';
                $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM') . ' a las ' . substr((string) $appointment->start_time, 0, 5);
                $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

                $fallbackParams = [
                    $clientName,
                    $serviceName,
                    $dateFormatted,
                    $voucherUrl,
                ];

                $fallbackDynamic = $this->resolveDynamicTemplateParameters(
                    appointment: $appointment,
                    templateKey: 'confirmacion_cita',
                    fallbackParams: $fallbackParams
                );

                $fallbackCandidateNames = ['confirmacion_cita', 'reserva_confirmada'];
                $fallbackLangs = array_values(array_unique([$metaCreds['language'], 'es_CO', 'es', 'es_ES']));

                foreach ($fallbackCandidateNames as $fbName) {
                    foreach ($fallbackLangs as $fallbackLang) {
                        $fallbackRes = $this->sendTemplateViaMetaWithDetails(
                            phone: $sanitizedPhone,
                            templateName: $fbName,
                            bodyParameters: $fallbackDynamic,
                            languageCode: $fallbackLang,
                            tenant: $appointment->tenant,
                            buttonParameters: []
                        );

                        if ($fallbackRes['success']) {
                            return true;
                        }
                    }
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
     * Cliente HTTP optimizado para Meta Graph API (fuerza IPv4 para evitar error cURL 7 en Docker Alpine).
     */
    protected function metaHttp(?string $token = null, int $timeout = 15): \Illuminate\Http\Client\PendingRequest
    {
        $request = Http::acceptJson()
            ->timeout($timeout)
            ->withOptions([
                'ip_resolve' => 'v4',
                'connect_timeout' => 8,
            ]);

        if (! empty($token)) {
            $request->withToken($token);
        }

        return $request;
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
            $response = $this->metaHttp($token, 12)->post($endpoint, $payload);

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
            if (config('whatsapp.driver') === 'log' || config('app.env') === 'testing') {
                Log::info("[WhatsApp Log/Testing] Plantilla '{$templateName}' enviada a +{$sanitizedPhone}.", [
                    'params' => $bodyParameters,
                ]);
                return [
                    'success' => true,
                    'message' => "Plantilla '{$templateName}' simulada exitosamente a +{$sanitizedPhone}.",
                    'wamid' => 'SIMULATED_' . uniqid(),
                ];
            }

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
            $response = $this->metaHttp($token, 12)->post($endpoint, $payload);

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

            // Si la plantilla NO tiene botones y se enviaron parámetros de botón por error (#100 Invalid parameter), auto-reintentar sin botones
            if (!empty($buttonParameters) && ($response->status() === 400 && (str_contains($errorDetails, 'parameter') || str_contains($errorDetails, 'button') || str_contains($json['error']['message'] ?? '', '(#100)')))) {
                Log::info("[WhatsApp Meta] Reintentando plantilla '{$templateName}' sin parámetros de botón...");
                return $this->sendTemplateViaMetaWithDetails(
                    $phone,
                    $templateName,
                    $bodyParameters,
                    $languageCode,
                    $tenant,
                    []
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
            $response = $this->metaHttp($token, 8)->get("{$apiUrl}/{$phoneId}", [
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
            $response = $this->metaHttp($token, 10)->get("{$apiUrl}/{$wabaId}/message_templates", [
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
     * Catálogo maestro de definiciones de plantillas oficiales del sistema.
     * 100% alineado con Meta WhatsApp Cloud API y PLANTILLAS_WHATSAPP_META.md.
     */
    public function getDefaultTemplatesDefinitions(): array
    {
        return [
            'confirmacion_cita' => [
                'key' => 'confirmacion_cita',
                'name' => 'confirmacion_cita',
                'display_name' => 'Confirmación de Cita',
                'description' => 'Notificación enviada cuando una cita queda agendada o confirmada en el sistema.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, te confirmamos que tu cita para {{2}} ha sido agendada con éxito para el día {{3}}.\n\nPuedes consultar tu comprobante aquí: {{4}}\n\n¡Te esperamos puntualmente!",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'Viernes 28 de Noviembre a las 3:00 PM',
                    'https://labellenailsbog.com/reserva/confirmacion/PA-261125-ABCD',
                ],
                'variable_keys' => ['apodo', 'servicio', 'fecha_hora', 'link_comprobante'],
                'trigger' => 'Al reservar sin requerir abono o al confirmar manualmente una cita.',
            ],
            'cita_cancelada' => [
                'key' => 'cita_cancelada',
                'name' => 'cita_cancelada',
                'display_name' => 'Cancelación de Cita',
                'description' => 'Aviso oficial enviado a la clienta cuando una cita es cancelada por el salón o la clienta.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, te informamos que tu cita para {{2}} programada para el día {{3}} ha sido cancelada.\n\nMotivo: {{4}}\n\nSi deseas programar un nuevo horario, puedes ingresar nuevamente aquí: {{5}}\n\n¡Esperamos atenderte pronto!",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'Viernes 28 de Noviembre a las 3:00 PM',
                    'Cancelación solicitada por la clienta',
                    'https://labellenailsbog.com',
                ],
                'variable_keys' => ['apodo', 'servicio', 'fecha_hora', 'motivo', 'link_comprobante'],
                'trigger' => 'Al cancelar una cita desde el portal de la clienta o desde el panel administrativo.',
            ],
            'cita_confirmada_abono' => [
                'key' => 'cita_confirmada_abono',
                'name' => 'cita_confirmada_abono',
                'display_name' => 'Abono Verificado y Confirmado',
                'description' => 'Confirmación enviada cuando el administrador valida y aprueba el comprobante de transferencia Nequi.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, hemos validado exitosamente el abono de tu cita para {{2}} del día {{3}}.\n\nSaldo restante a pagar en el salón: {{4}}\nTu reserva está 100% confirmada. Puedes ver tu comprobante aquí: {{5}}\n\n¡Te esperamos puntualmente!",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'Viernes 28 de Noviembre a las 3:00 PM',
                    '$25.000 COP',
                    'https://labellenailsbog.com/reserva/confirmacion/PA-261125-ABCD',
                ],
                'variable_keys' => ['apodo', 'servicio', 'fecha_hora', 'saldo_restante', 'link_comprobante'],
                'trigger' => 'Al presionar "Aprobar Nequi" o aprobar el abono en el panel.',
            ],
            'recordatorio_cita_24h' => [
                'key' => 'recordatorio_cita_24h',
                'name' => 'recordatorio_cita_24h',
                'display_name' => 'Recordatorio 24 Horas Antes',
                'description' => 'Recordatorio preventivo enviado 24h antes del turno con enlace de confirmación en 1 clic.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, te recordamos que tienes una cita programada para {{2}} el día {{3}}.\n\nSaldo restante a pagar en el salón: {{4}}.\n👉 Por favor confirma tu asistencia ingresando a este enlace: {{5}}\n\n¡Te esperamos puntualmente!",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'Mañana Viernes a las 3:00 PM',
                    '$25.000 COP',
                    'https://labellenailsbog.com/reserva/confirmar/PA-261125-ABCD?token=xyz123',
                ],
                'variable_keys' => ['apodo', 'servicio', 'fecha_hora', 'saldo_restante', 'link_comprobante'],
                'trigger' => 'Envío automático por cron o comando diario a citas del día siguiente.',
            ],
            'cita_reagendada' => [
                'key' => 'cita_reagendada',
                'name' => 'cita_reagendada',
                'display_name' => 'Cita Reagendada / Reprogramada',
                'description' => 'Notificación cuando se cambia la fecha u hora de la cita manteniendo el abono.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, tu cita para {{2}} ha sido reagendada con éxito para el día {{3}} (horario previo: {{4}}).\n\nTu abono se mantiene vigente. Puedes consultar tu comprobante actualizado aquí: {{5}}\n\n¡Te esperamos puntualmente!",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'Sábado 29 de Noviembre a las 4:00 PM',
                    'Viernes 28 de Noviembre a las 3:00 PM',
                    'https://labellenailsbog.com/reserva/confirmacion/PA-261125-ABCD',
                ],
                'variable_keys' => ['apodo', 'servicio', 'fecha_hora', 'horario_previo', 'link_comprobante'],
                'trigger' => 'Al mover una cita de horario o fecha en el calendario o portal.',
            ],
            'novedad_comprobante_cita' => [
                'key' => 'novedad_comprobante_cita',
                'name' => 'novedad_comprobante_cita',
                'display_name' => 'Novedad / Rechazo de Comprobante',
                'description' => 'Aviso cuando el comprobante es ilegible o el monto transferido no corresponde.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, tenemos una novedad con el comprobante de tu cita para {{2}}.\n\nMotivo: {{3}}\n\nPor favor sube nuevamente tu comprobante desde el enlace para no perder tu turno: {{4}}\n\nQuedamos atentos para confirmar tu reserva.",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'El valor transferido no coincide con el anticipo requerido',
                    'https://labellenailsbog.com/reserva/confirmacion/PA-261125-ABCD',
                ],
                'variable_keys' => ['apodo', 'servicio', 'motivo', 'link_comprobante'],
                'trigger' => 'Al rechazar comprobante de transferencia con motivo específico.',
            ],
            'cita_recibida' => [
                'key' => 'cita_recibida',
                'name' => 'cita_recibida',
                'display_name' => 'Solicitud de Cita Recibida',
                'description' => 'Acuse de recibo inicial solicitando el comprobante de pago Nequi para apartar cupo.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, hemos recibido tu solicitud de cita para {{2}} el día {{3}}.\n\nAbono requerido para asegurar tu turno: {{4}}\nRecuerda adjuntar tu comprobante de transferencia en el siguiente enlace: {{5}}\n\n¡Gracias por tu reserva!",
                'samples' => [
                    'Cami',
                    'Manicura Semipermanente',
                    'Viernes 28 de Noviembre a las 3:00 PM',
                    '$30.000 COP',
                    'https://labellenailsbog.com/reserva/confirmacion/PA-261125-ABCD',
                ],
                'variable_keys' => ['apodo', 'servicio', 'fecha_hora', 'abono', 'link_comprobante'],
                'trigger' => 'Inmediatamente al agendar una cita que exige anticipo.',
            ],
            'alerta_nueva_cita_admin' => [
                'key' => 'alerta_nueva_cita_admin',
                'name' => 'alerta_nueva_cita_admin',
                'display_name' => 'Alerta de Cita a Administradora',
                'description' => 'Notificación interna enviada al WhatsApp del negocio alertando sobre la nueva reserva.',
                'category' => 'UTILITY',
                'language' => 'es_CO',
                'body' => "Nueva cita registrada. Cliente: {{1}} (Tel: {{2}}). Servicio: {{3}} para el día {{4}}.\n\nRevisa los detalles en el panel de administración: {{5}}\n\nNotificación interna del sistema.",
                'samples' => [
                    'Camila Morales (Cami)',
                    '3194086814',
                    'Manicura Semipermanente',
                    'Viernes 28 de Noviembre a las 3:00 PM',
                    'https://labellenailsbog.com/admin/appointments',
                ],
                'variable_keys' => ['cliente', 'telefono_cliente', 'servicio', 'fecha_hora', 'link_admin'],
                'trigger' => 'Al registrarse cualquier reserva en la plataforma.',
            ],
            'promocion_salon' => [
                'key' => 'promocion_salon',
                'name' => 'promocion_salon',
                'display_name' => 'Promoción Especial del Salón',
                'description' => 'Difusión masiva a clientas anunciando descuentos, promociones de temporada o beneficios.',
                'category' => 'MARKETING',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, en {{2}} tenemos una promoción especial para ti: {{3}}.\n\nReserva tu cita en el siguiente enlace: {{4}}\n\n¡Esperamos verte pronto!",
                'samples' => [
                    'Cami',
                    'La Belle Nails',
                    '20% de descuento en Manicura Semipermanente este fin de semana',
                    'https://labellenailsbog.com',
                ],
                'variable_keys' => ['apodo', 'salon', 'mensaje_promo', 'link_portal'],
                'trigger' => 'Envío manual desde Listas de Difusión y Campañas.',
            ],
            'reactivacion_clientas' => [
                'key' => 'reactivacion_clientas',
                'name' => 'reactivacion_clientas',
                'display_name' => 'Reactivación de Clientas Inactivas',
                'description' => 'Difusión a clientas con más de 30 días sin visitas invitándolas a consentirse y volver.',
                'category' => 'MARKETING',
                'language' => 'es_CO',
                'body' => "Hola {{1}}, te extrañamos en {{2}}. Hace días que no te consentimos y queremos invitarte a renovar tu look.\n\nAgenda tu próxima cita aquí: {{3}}\n\n¡Te esperamos con una atención especial!",
                'samples' => [
                    'Cami',
                    'La Belle Nails',
                    'https://labellenailsbog.com',
                ],
                'variable_keys' => ['apodo', 'salon', 'link_portal'],
                'trigger' => 'Envío manual desde Listas de Difusión a clientas inactivas.',
            ],
        ];
    }

    /**
     * Diccionario semántico de variables dinámicas disponibles para personalización.
     */
    public function getAvailableVariables(): array
    {
        return [
            ['key' => 'apodo', 'label' => 'Apodo de Clienta (o Nombre Corto)', 'sample' => 'Cami', 'token' => '{{apodo}}'],
            ['key' => 'cliente', 'label' => 'Nombre / Apodo de Clienta', 'sample' => 'Cami', 'token' => '{{cliente}}'],
            ['key' => 'salon', 'label' => 'Nombre del Salón / Negocio', 'sample' => 'La Belle Nails', 'token' => '{{salon}}'],
            ['key' => 'mensaje_promo', 'label' => 'Texto de la Promoción', 'sample' => '20% de descuento en Manicura', 'token' => '{{mensaje_promo}}'],
            ['key' => 'link_portal', 'label' => 'Enlace para Agendar Cita', 'sample' => 'https://labellenailsbog.com', 'token' => '{{link_portal}}'],
            ['key' => 'servicio', 'label' => 'Servicio / Tratamiento', 'sample' => 'Manicura Semipermanente', 'token' => '{{servicio}}'],
            ['key' => 'fecha_hora', 'label' => 'Fecha y Hora Cita', 'sample' => 'Viernes 28 de Noviembre a las 3:00 PM', 'token' => '{{fecha_hora}}'],
            ['key' => 'abono', 'label' => 'Monto del Abono / Anticipo', 'sample' => '$30.000 COP', 'token' => '{{abono}}'],
            ['key' => 'saldo_restante', 'label' => 'Saldo Restante en Salón', 'sample' => '$25.000 COP', 'token' => '{{saldo_restante}}'],
            ['key' => 'precio_total', 'label' => 'Precio Total', 'sample' => '$55.000 COP', 'token' => '{{precio_total}}'],
            ['key' => 'link_comprobante', 'label' => 'Enlace al Comprobante / Portal', 'sample' => 'https://labellenailsbog.com/reserva/confirmacion/PA-261125-ABCD', 'token' => '{{link_comprobante}}'],
            ['key' => 'motivo', 'label' => 'Motivo Cancelación / Novedad', 'sample' => 'Cancelación solicitada por la clienta', 'token' => '{{motivo}}'],
            ['key' => 'horario_previo', 'label' => 'Horario Previo Reagendado', 'sample' => 'Viernes 28 de Noviembre a las 3:00 PM', 'token' => '{{horario_previo}}'],
            ['key' => 'telefono_cliente', 'label' => 'Teléfono Clienta', 'sample' => '3194086814', 'token' => '{{telefono_cliente}}'],
            ['key' => 'telefono_salon', 'label' => 'WhatsApp del Salón', 'sample' => '3103248385', 'token' => '{{telefono_salon}}'],
            ['key' => 'link_admin', 'label' => 'Enlace al Panel de Administración', 'sample' => 'https://labellenailsbog.com/admin/appointments', 'token' => '{{link_admin}}'],
        ];
    }

    /**
     * Ruta del archivo de personalizaciones de plantillas por Tenant (aislado y seguro).
     */
    public function getCustomTemplatesFilePath(?Tenant $tenant = null): string
    {
        $tenantId = $tenant?->id ?? 'global';
        return storage_path("app/tenants/{$tenantId}/whatsapp_templates.json");
    }

    /**
     * Obtiene las personalizaciones locales de plantillas para el Tenant.
     */
    public function getTenantCustomTemplates(?Tenant $tenant = null): array
    {
        $file = $this->getCustomTemplatesFilePath($tenant);
        if (!file_exists($file)) {
            return [];
        }

        try {
            $content = file_get_contents($file);
            return json_decode($content, true) ?: [];
        } catch (\Throwable $e) {
            Log::warning("[WhatsApp] Error leyendo plantillas personalizadas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Mapea alias de eventos a los nombres canónicos de las plantillas oficiales.
     */
    public function resolveTemplateKey(string $key): string
    {
        $aliasMap = [
            'booking_created' => 'cita_recibida',
            'admin_new_booking' => 'alerta_nueva_cita_admin',
            'deposit_approved' => 'cita_confirmada_abono',
            'deposit_rejected' => 'novedad_comprobante_cita',
            'appointment_cancelled' => 'cita_cancelada',
            'appointment_rescheduled' => 'cita_reagendada',
            'reminder_24h' => 'recordatorio_cita_24h',
            'booking_confirmed' => 'confirmacion_cita',
            'attendance_confirmed' => 'confirmacion_cita',
        ];

        return $aliasMap[$key] ?? $key;
    }

    /**
     * Obtiene la definición efectiva de una plantilla (fusiona defaults con personalizaciones).
     */
    public function getEffectiveTemplateDefinition(string $templateKey, ?Tenant $tenant = null): ?array
    {
        $canonicalKey = $this->resolveTemplateKey($templateKey);
        $defaults = $this->getDefaultTemplatesDefinitions();
        $def = $defaults[$canonicalKey] ?? $defaults[$templateKey] ?? null;
        if (!$def) {
            return null;
        }

        $customs = $this->getTenantCustomTemplates($tenant);
        $override = $customs[$canonicalKey] ?? $customs[$templateKey] ?? null;
        if (!empty($override)) {
            $def['body'] = $override['body'] ?? $def['body'];
            $def['samples'] = $override['samples'] ?? $def['samples'];
            $def['variable_keys'] = $override['variable_keys'] ?? $def['variable_keys'];
            $def['is_customized'] = true;
            $def['original_body'] = $defaults[$canonicalKey]['body'] ?? $defaults[$templateKey]['body'] ?? $def['body'];
        } else {
            $def['is_customized'] = false;
            $def['original_body'] = $def['body'];
        }

        $def['preview_text'] = $this->renderTemplatePreview($def['body'], $def['samples']);
        return $def;
    }

    /**
     * Guarda la personalización de una plantilla y opcionalmente la sincroniza en Meta.
     */
    public function saveTenantCustomTemplate(string $templateKey, array $data, ?Tenant $tenant = null): array
    {
        $canonicalKey = $this->resolveTemplateKey($templateKey);
        $defaults = $this->getDefaultTemplatesDefinitions();
        if (!isset($defaults[$canonicalKey])) {
            return [
                'success' => false,
                'message' => "La plantilla '{$templateKey}' no forma parte del catálogo oficial.",
            ];
        }

        $body = trim($data['body'] ?? '');
        if (empty($body)) {
            return [
                'success' => false,
                'message' => 'El cuerpo del mensaje no puede estar vacío.',
            ];
        }

        // Validación estricta Meta: no leading ni trailing variables (Error 2388299)
        if (preg_match('/^\s*\{\{\d+\}\}/', $body)) {
            return [
                'success' => false,
                'message' => 'Meta no permite variables al inicio de la plantilla. Debes comenzar con un saludo o texto fijo (ej: "Hola {{1}}").',
            ];
        }

        if (preg_match('/\{\{\d+\}\}\s*$/', $body)) {
            return [
                'success' => false,
                'message' => 'Meta no permite variables al final de la plantilla (Error #2388299). Debes incluir una frase o texto de cierre después de la última variable.',
            ];
        }

        // Extraer placeholders
        preg_match_all('/\{\{(\d+)\}\}/', $body, $matches);
        $foundVars = $matches[1] ?? [];
        $varCount = count($foundVars);

        // Validar numeración secuencial 1, 2, 3...
        for ($i = 1; $i <= $varCount; $i++) {
            if (!in_array((string) $i, $foundVars, true)) {
                return [
                    'success' => false,
                    'message' => "Las variables deben ser secuenciales iniciando en {{1}} (falta la variable {{' . $i . '}}).",
                ];
            }
        }

        // Completar samples
        $samples = $data['samples'] ?? [];
        if (count($samples) < $varCount) {
            $defaultSamples = $defaults[$templateKey]['samples'] ?? [];
            for ($i = count($samples); $i < $varCount; $i++) {
                $samples[] = $defaultSamples[$i] ?? "Dato {$i}";
            }
        }
        $samples = array_slice($samples, 0, $varCount);

        // Guardar en JSON por Tenant
        $file = $this->getCustomTemplatesFilePath($tenant);
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $allCustom = $this->getTenantCustomTemplates($tenant);
        $allCustom[$templateKey] = [
            'key' => $templateKey,
            'body' => $body,
            'samples' => $samples,
            'variable_keys' => $data['variable_keys'] ?? $defaults[$templateKey]['variable_keys'] ?? [],
            'updated_at' => now()->toIso8601String(),
        ];

        file_put_contents($file, json_encode($allCustom, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $metaResult = null;
        if (!empty($data['sync_to_meta'])) {
            $effectiveDef = $this->getEffectiveTemplateDefinition($templateKey, $tenant);
            $metaResult = $this->createMetaTemplate($effectiveDef, $tenant);
        }

        $metaMsg = "";
        if ($metaResult) {
            if ($metaResult['updated'] ?? false) {
                $metaMsg = " Contenido actualizado exitosamente en Meta Cloud API.";
            } elseif ($metaResult['already_exists'] ?? false) {
                $metaMsg = " La plantilla ya está registrada y aprobada en Meta WhatsApp Manager.";
            } elseif ($metaResult['success'] ?? false) {
                $metaMsg = " Registrada con éxito en Meta Cloud API.";
            } else {
                $metaMsg = " (Nota Meta: " . ($metaResult['message'] ?? 'Pendiente de sincronización') . ")";
            }
        }

        return [
            'success' => true,
            'message' => "Plantilla '{$defaults[$templateKey]['display_name']}' guardada correctamente." . $metaMsg,
            'meta_result' => $metaResult,
            'template' => $this->getEffectiveTemplateDefinition($templateKey, $tenant),
        ];
    }

    /**
     * Restablece una plantilla a su versión oficial original.
     */
    public function resetTenantCustomTemplate(string $templateKey, ?Tenant $tenant = null): array
    {
        $file = $this->getCustomTemplatesFilePath($tenant);
        if (file_exists($file)) {
            $allCustom = $this->getTenantCustomTemplates($tenant);
            if (isset($allCustom[$templateKey])) {
                unset($allCustom[$templateKey]);
                file_put_contents($file, json_encode($allCustom, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }

        return [
            'success' => true,
            'message' => "Plantilla restablecida a su versión oficial por defecto.",
            'template' => $this->getEffectiveTemplateDefinition($templateKey, $tenant),
        ];
    }

    /**
     * Mecanismo Inteligente de Adaptación Dinámica (Dynamic Variable Resolver).
     * Resuelve los parámetros requeridos por la plantilla activa a partir de los datos reales de la cita,
     * garantizando que la cantidad y formato coincidan exactamente con la definición en Meta (Evita Error #100).
     */
    public function resolveDynamicTemplateParameters(
        Appointment $appointment,
        string $templateKey,
        array $fallbackParams = [],
        array $extraContext = []
    ): array {
        $appointment->loadMissing(['service', 'customer', 'tenant']);

        $apptNum = (string) ($appointment->appointment_number ?? $appointment->id);
        $clientName = $appointment->getNotificationClientName();
        $serviceName = $appointment->service?->name ?? 'Servicio';
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $timeFormatted = substr((string) $appointment->start_time, 0, 5);
        $dateTime = "{$dateFormatted} a las {$timeFormatted}";
        $voucherUrl = url("/reserva/confirmacion/{$apptNum}");
        $deposit = number_format((float) ($appointment->deposit_amount ?? 0), 0, ',', '.');
        $price = number_format((float) ($appointment->service?->price ?? 0), 0, ',', '.');
        $remaining = number_format(max(0, (float) ($appointment->service?->price ?? 0) - (float) ($appointment->deposit_amount ?? 0)), 0, ',', '.');
        $salonPhone = $appointment->tenant?->phone ?? $appointment->tenant?->whatsapp_number ?? '';
        $clientPhone = $appointment->customer?->phone ?? $appointment->client_phone ?? '';
        $reason = $extraContext['reason'] ?? $appointment->cancellation_reason ?? 'Cancelación de cita';
        $prevDate = $extraContext['previous_date'] ?? '';

        $dict = [
            'apodo' => $clientName,
            'cliente' => $clientName,
            'codigo_cita' => $apptNum,
            'servicio' => $serviceName,
            'fecha_hora' => $dateTime,
            'fecha' => $dateFormatted,
            'hora' => $timeFormatted,
            'abono' => "\${$deposit} COP",
            'precio_total' => "\${$price} COP",
            'saldo_restante' => "\${$remaining} COP",
            'link_comprobante' => $voucherUrl,
            'link_portal' => url('/'),
            'motivo' => $reason,
            'horario_previo' => $prevDate,
            'telefono_cliente' => $clientPhone,
            'telefono_salon' => $salonPhone,
            'link_admin' => url('/admin/appointments'),
        ];

        $canonicalKey = $this->resolveTemplateKey($templateKey);
        $effectiveDef = $this->getEffectiveTemplateDefinition($canonicalKey, $appointment->tenant);
        $body = $effectiveDef['body'] ?? '';
        preg_match_all('/\{\{(\d+)\}\}/', $body, $matches);
        $varCount = count($matches[1] ?? []);

        if ($varCount === 0) {
            if (!empty($fallbackParams)) {
                return array_values(array_map(function ($val) {
                    $clean = preg_replace('/[\r\n\t]+/', ' ', (string) $val);
                    $clean = preg_replace('/\s{2,}/', ' ', trim($clean));
                    return mb_substr($clean ?: '-', 0, 1024);
                }, $fallbackParams));
            }
            return [];
        }

        $variableKeys = $effectiveDef['variable_keys'] ?? [];
        $resolved = [];

        for ($i = 0; $i < $varCount; $i++) {
            $key = $variableKeys[$i] ?? null;
            if ($key && isset($dict[$key])) {
                $val = $dict[$key];
            } elseif (isset($fallbackParams[$i])) {
                $val = $fallbackParams[$i];
            } else {
                $val = '-';
            }

            // Sanitización estricta para Meta Cloud API (no saltos de línea ni tabuladores en parámetros)
            $clean = preg_replace('/[\r\n\t]+/', ' ', (string) $val);
            $clean = preg_replace('/\s{2,}/', ' ', trim($clean));
            $resolved[] = mb_substr($clean ?: '-', 0, 1024);
        }

        return $resolved;
    }

    /**
     * Reemplaza variables {{1}}, {{2}}... por los valores de muestra para previsualización realista.
     */
    public function renderTemplatePreview(string $body, array $samples): string
    {
        $preview = $body;
        foreach ($samples as $index => $value) {
            $placeholder = '{{' . ($index + 1) . '}}';
            $preview = str_replace($placeholder, (string) $value, $preview);
        }
        return $preview;
    }

    /**
     * Crea una plantilla en Meta WhatsApp Cloud API mediante POST /{waba_id}/message_templates.
     */
    public function createMetaTemplate(array $templateDefinition, ?Tenant $tenant = null): array
    {
        $creds = $this->resolveMetaCredentials(null, $tenant);
        $apiUrl = $creds['api_url'];
        $wabaId = $creds['waba_id'];
        $token = $creds['access_token'];

        if (empty($wabaId) || empty($token)) {
            return [
                'success' => false,
                'message' => 'Faltan credenciales de Meta (WABA ID o Access Token) para crear plantillas.',
                'error_code' => 'MISSING_CREDENTIALS',
            ];
        }

        $name = trim($templateDefinition['name'] ?? '');
        $category = strtoupper(trim($templateDefinition['category'] ?? 'UTILITY'));
        $language = trim($templateDefinition['language'] ?? 'es_CO');
        $bodyText = trim($templateDefinition['body'] ?? '');
        $samples = $templateDefinition['samples'] ?? [];

        if (empty($name) || empty($bodyText)) {
            return [
                'success' => false,
                'message' => 'Nombre y cuerpo de la plantilla son obligatorios.',
                'error_code' => 'INVALID_PAYLOAD',
            ];
        }

        // Meta exige example.body_text como array de arrays de strings
        $bodyComponent = [
            'type' => 'BODY',
            'text' => $bodyText,
        ];

        if (! empty($samples)) {
            $bodyComponent['example'] = [
                'body_text' => [
                    array_values(array_map('strval', $samples)),
                ],
            ];
        }

        $payload = [
            'name' => $name,
            'category' => $category,
            'language' => $language,
            'components' => [
                $bodyComponent,
            ],
        ];

        $endpoint = "{$apiUrl}/{$wabaId}/message_templates";

        try {
            $response = $this->metaHttp($token, 15)->post($endpoint, $payload);

            $json = $response->json() ?? [];

            if ($response->successful()) {
                $templateId = $json['id'] ?? null;
                $status = $json['status'] ?? 'PENDING';
                Log::info("[WhatsApp Meta] Plantilla '{$name}' ({$language}) creada exitosamente. ID Meta: {$templateId}, Status: {$status}.");

                return [
                    'success' => true,
                    'message' => "Plantilla '{$name}' creada exitosamente en Meta Cloud API.",
                    'template_id' => $templateId,
                    'name' => $name,
                    'status' => $status,
                    'category' => $category,
                    'language' => $language,
                    'data' => $json,
                ];
            }

            // Detección de plantilla ya existente (Subcódigo 2388024: A template with this name already exists)
            $errorMsg = $json['error']['message'] ?? '';
            $subcode = $json['error']['error_subcode'] ?? null;

            if (str_contains(strtolower($errorMsg), 'already exists') || in_array((int) $subcode, [2388024, 2388043], true)) {
                // Intentar actualizar la plantilla existente en Meta vía POST /{template_id}
                try {
                    $existingList = $this->getMetaTemplates($tenant)['templates'] ?? [];
                    $matched = collect($existingList)->first(fn ($t) => strtolower($t['name'] ?? '') === strtolower($name));
                    if ($matched && !empty($matched['id'])) {
                        $templateId = $matched['id'];
                        $updateRes = $this->metaHttp($token, 15)->post("{$apiUrl}/{$templateId}", [
                                'components' => [
                                    $bodyComponent,
                                ],
                            ]);

                        if ($updateRes->successful()) {
                            Log::info("[WhatsApp Meta] Plantilla '{$name}' (ID: {$templateId}) actualizada con éxito en Meta Cloud API.");
                            return [
                                'success' => true,
                                'already_exists' => true,
                                'updated' => true,
                                'message' => "La plantilla '{$name}' ya existía en Meta y se actualizó correctamente con el nuevo contenido.",
                                'name' => $name,
                                'template_id' => $templateId,
                                'status' => 'PENDING',
                                'data' => $updateRes->json(),
                            ];
                        }
                    }
                } catch (\Throwable $updateEx) {
                    Log::warning("[WhatsApp Meta] No se pudo auto-actualizar plantilla existente '{$name}': " . $updateEx->getMessage());
                }

                return [
                    'success' => true,
                    'already_exists' => true,
                    'message' => "La plantilla '{$name}' ya está registrada en tu cuenta de Meta WhatsApp Manager y guardada en el sistema.",
                    'name' => $name,
                    'status' => 'APPROVED',
                    'data' => $json,
                ];
            }

            return $this->formatMetaError($response->status(), $json);
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Meta] Excepción al crear plantilla '{$name}': " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Excepción de red al crear plantilla en Meta: ' . $e->getMessage(),
                'error_code' => 'NETWORK_EXCEPTION',
            ];
        }
    }

    /**
     * Crea una plantilla del catálogo oficial por su clave.
     */
    public function createOfficialTemplate(string $templateKey, ?Tenant $tenant = null): array
    {
        $definitions = $this->getDefaultTemplatesDefinitions();

        if (! isset($definitions[$templateKey])) {
            return [
                'success' => false,
                'message' => "La plantilla '{$templateKey}' no forma parte del catálogo oficial del sistema.",
                'error_code' => 'TEMPLATE_NOT_FOUND',
            ];
        }

        $def = $this->getEffectiveTemplateDefinition($templateKey, $tenant) ?? $definitions[$templateKey];
        return $this->createMetaTemplate($def, $tenant);
    }

    /**
     * Crea todas las plantillas oficiales del catálogo en Meta con 1 solo clic.
     */
    public function createAllOfficialTemplates(?Tenant $tenant = null): array
    {
        $definitions = $this->getDefaultTemplatesDefinitions();
        $results = [];
        $createdCount = 0;
        $alreadyExistedCount = 0;
        $failedCount = 0;

        foreach ($definitions as $key => $definition) {
            $effectiveDef = $this->getEffectiveTemplateDefinition($key, $tenant) ?? $definition;
            $res = $this->createMetaTemplate($effectiveDef, $tenant);
            $results[$key] = $res;

            if ($res['success'] ?? false) {
                if ($res['already_exists'] ?? false) {
                    $alreadyExistedCount++;
                } else {
                    $createdCount++;
                }
            } else {
                $failedCount++;
            }
        }

        return [
            'success' => $failedCount === 0,
            'message' => "Proceso completado: {$createdCount} creadas, {$alreadyExistedCount} ya existentes, {$failedCount} fallidas.",
            'summary' => [
                'created' => $createdCount,
                'already_exists' => $alreadyExistedCount,
                'failed' => $failedCount,
                'total' => count($definitions),
            ],
            'details' => $results,
        ];
    }

    /**
     * Obtiene el catálogo completo de plantillas oficiales cruzado con el estado real en Meta.
     */
    public function getOfficialTemplatesCatalog(?Tenant $tenant = null): array
    {
        $definitions = $this->getDefaultTemplatesDefinitions();
        $metaTemplatesRes = $this->getMetaTemplates($tenant);
        $wabaTemplates = $metaTemplatesRes['templates'] ?? [];

        // Mapear por nombre en minúsculas
        $wabaMap = [];
        foreach ($wabaTemplates as $wt) {
            $wtName = strtolower(trim($wt['name'] ?? ''));
            $wabaMap[$wtName] = $wt;
        }

        $catalog = [];
        foreach ($definitions as $key => $defaultDef) {
            $effectiveDef = $this->getEffectiveTemplateDefinition($key, $tenant) ?? $defaultDef;
            $nameLower = strtolower($effectiveDef['name']);
            $metaData = $wabaMap[$nameLower] ?? null;

            // Soporte de alias: confirmacion_cita también puede estar como reserva_confirmada
            if (! $metaData && $key === 'confirmacion_cita' && isset($wabaMap['reserva_confirmada'])) {
                $metaData = $wabaMap['reserva_confirmada'];
            }

            $previewText = $this->renderTemplatePreview($effectiveDef['body'], $effectiveDef['samples']);

            $catalog[] = array_merge($effectiveDef, [
                'exists_in_meta' => ! is_null($metaData),
                'meta_status' => $metaData['status'] ?? 'NOT_REGISTERED',
                'meta_id' => $metaData['id'] ?? null,
                'meta_language' => $metaData['language'] ?? $effectiveDef['language'],
                'meta_category' => $metaData['category'] ?? $effectiveDef['category'],
                'preview_text' => $previewText,
            ]);
        }

        return [
            'success' => true,
            'meta_connected' => ($metaTemplatesRes['success'] ?? false),
            'meta_error_message' => ($metaTemplatesRes['success'] ?? false) ? null : ($metaTemplatesRes['message'] ?? null),
            'available_variables' => $this->getAvailableVariables(),
            'templates' => $catalog,
        ];
    }

    /**
     * Registra / activa el número de teléfono en Meta Cloud API con un PIN de 6 dígitos.
     * Requerido por Meta para números reales (resuelve el error 133010: Account not registered).
     */
    public function registerMetaPhoneNumber(string $pin = '123456', ?Tenant $tenant = null): array
    {
        $creds = $this->resolveMetaCredentials(null, $tenant);
        $apiUrl = $creds['api_url'];
        $phoneId = $creds['phone_number_id'];
        $token = $creds['access_token'];

        if (empty($phoneId) || empty($token)) {
            return [
                'success' => false,
                'message' => 'Faltan credenciales de Meta (Phone Number ID o Access Token).',
            ];
        }

        try {
            $response = $this->metaHttp($token, 15)->post("{$apiUrl}/{$phoneId}/register", [
                'messaging_product' => 'whatsapp',
                'pin' => $pin,
            ]);

            $json = $response->json() ?? [];

            if ($response->successful() && ($json['success'] ?? false)) {
                Log::info("[WhatsApp Meta] Número Phone ID {$phoneId} registrado exitosamente en Cloud API.");
                return [
                    'success' => true,
                    'message' => 'Número registrado y activado con éxito en Meta WhatsApp Cloud API.',
                    'data' => $json,
                ];
            }

            return $this->formatMetaError($response->status(), $json);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Excepción al registrar número en Meta: ' . $e->getMessage(),
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
            131009, 100 => match ((int) $subcode) {
                2388024 => 'La plantilla ya existe registrada con este nombre e idioma en tu cuenta de Meta WhatsApp Manager. Los cambios se guardaron localmente en el sistema.',
                2388299 => 'Meta no permite variables al inicio o al final del texto de la plantilla (Error #2388299: Leading or trailing parameters not allowed). Toda plantilla debe comenzar y terminar siempre con texto o palabras fijas.',
                default => 'Parámetro inválido en la petición a Meta Graph API (#100). Causas principales: 1) Si estás creando una plantilla, verifica que no termine en una variable. 2) Si estás enviando un mensaje, el número destinatario no puede ser el mismo número comercial del salón (Meta prohíbe auto-mensajes), o la cantidad de variables enviadas no coincide con las registradas en Meta.',
            },
            133010 => 'El número de teléfono está en tu cuenta de Meta pero no ha sido registrado/activado en Cloud API (Error #133010: Account not registered). Para resolverlo: 1) Si este número está actualmente activo en la app de WhatsApp de tu celular físico, debes eliminar la cuenta en la app móvil de WhatsApp (Meta prohíbe operar simultáneamente en app móvil y en Cloud API). 2) Registra y activa el número usando el botón "Activar Número con PIN" en este panel o ejecutando en terminal: php artisan whatsapp:register 123456.',
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
