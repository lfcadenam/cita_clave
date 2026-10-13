<?php

namespace App\Services;

use App\Models\Appointment;
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
     * 1. Solicitud de Cita Creada (Acuse de recibo y pendiente de abono Nequi).
     */
    public function sendBookingCreated(Appointment $appointment): bool
    {
        $clientPhone = $appointment->client_phone;
        if (blank($clientPhone)) {
            return false;
        }

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
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

        return $this->sendTextMessageSafe($clientPhone, $message);
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
        $tenant = $appointment->tenant;
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

        return $this->sendTextMessageSafe($adminPhone, $message);
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

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
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
            . "📍 *Lugar:* Estudio Paola Aguilera, Bogotá\n"
            . "💵 *Saldo a pagar en local:* *\${$balanceFormatted} COP*\n\n"
            . "🎟️ *Comprobante digital actualizado:*\n"
            . "{$voucherUrl}\n\n"
            . "_Te esperamos puntualmente. Recibirás un recordatorio 24h antes._";

        return $this->sendTextMessageSafe($clientPhone, $message);
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

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "⚠️ *Novedad con tu Comprobante de Abono* Hola {$clientName},\n\n"
            . "Revisamos tu solicitud de cita *#{$appointment->appointment_number}* ({$serviceName}), pero no pudimos validar el comprobante de transferencia.\n\n"
            . ($reason ? "📝 *Motivo:* {$reason}\n\n" : "")
            . "👉 *Por favor sube nuevamente tu comprobante aquí para asegurar tu cupo:*\n"
            . "{$voucherUrl}\n\n"
            . "_O comunícate con nosotros si tienes alguna duda._";

        return $this->sendTextMessageSafe($clientPhone, $message);
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

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $bookingUrl = url("/");

        $message = "🚫 *Cita Cancelada* Hola {$clientName},\n\n"
            . "Te informamos que tu cita *#{$appointment->appointment_number}* ({$serviceName}) programada para el *{$dateFormatted} a las {$startTime}* ha sido cancelada.\n\n"
            . ($reason ? "📝 *Motivo:* {$reason}\n\n" : "")
            . "Tu horario ha sido liberado. Si deseas agendar para otra ocasión, puedes hacerlo aquí:\n"
            . "{$bookingUrl}\n\n"
            . "_¡Esperamos verte pronto!_";

        return $this->sendTextMessageSafe($clientPhone, $message);
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

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
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

        return $this->sendTextMessageSafe($clientPhone, $message);
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

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $balanceFormatted = number_format($appointment->balance_due, 0, ',', '.');
        $confirmationUrl = url("/reserva/confirmar/{$appointment->appointment_number}");
        $portalUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "✨ *¡Hola {$clientName}!* Te saludamos de *Paola Aguilera Belleza & Estética*.\n\n"
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

        $sent = $this->sendTextMessageSafe($clientPhone, $message);

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

        $clientName = explode(' ', trim($appointment->client_name))[0];
        $serviceName = $appointment->service->name;
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $voucherUrl = url("/reserva/confirmacion/{$appointment->appointment_number}");

        $message = "🌸 *¡Reserva Confirmada!* Hola {$clientName},\n\n"
            . "Tu cita *#{$appointment->appointment_number}* ha sido confirmada exitosamente:\n\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "📍 *Lugar:* Estudio Paola Aguilera, Bogotá\n\n"
            . "🎟️ *Ver comprobante digital:*\n"
            . "{$voucherUrl}\n\n"
            . "_¡Gracias por elegirnos! Recibirás un recordatorio 24h antes de tu cita._";

        return $this->sendTextMessageSafe($clientPhone, $message);
    }

    /**
     * Envío seguro con manejo de excepciones (nunca arroja error fatal).
     */
    public function sendTextMessageSafe(string $phone, string $message): bool
    {
        try {
            return $this->sendTextMessage($phone, $message);
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Exception] Error enviando mensaje a {$phone}: " . $e->getMessage());
            $this->sendViaLog($phone, $message);
            return false;
        }
    }

    /**
     * Envía un mensaje de texto plano a un número de teléfono.
     */
    public function sendTextMessage(string $phone, string $message): bool
    {
        if (!$this->enabled) {
            Log::info("[WhatsApp Disabled] Mensaje no enviado a {$phone}: {$message}");
            return true;
        }

        $sanitizedPhone = $this->sanitizePhoneNumber($phone);

        return match ($this->driver) {
            'evolution' => $this->sendViaEvolution($sanitizedPhone, $message),
            'meta' => $this->sendViaMeta($sanitizedPhone, $message),
            default => $this->sendViaLog($sanitizedPhone, $message),
        };
    }

    /**
     * Envío a través de Evolution API (Gateway QR / Baileys).
     */
    protected function sendViaEvolution(string $phone, string $message): bool
    {
        $baseUrl = $this->evolutionConfig['base_url'] ?? 'http://localhost:8085';
        $apiKey = $this->evolutionConfig['api_key'] ?? '';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';

        $candidateUrls = array_unique(array_filter([
            $baseUrl,
            str_replace([':8080', ':8085'], ':8085', $baseUrl),
            str_contains($baseUrl, 'localhost') ? str_replace('localhost', 'host.docker.internal', $baseUrl) : null,
            'http://host.docker.internal:8085',
            'http://localhost:8085',
            'http://host.docker.internal:8080',
            'http://localhost:8080',
            'http://nuvex_evolution_whatsapp:8080',
        ]));

        foreach ($candidateUrls as $url) {
            $endpoint = "{$url}/message/sendText/{$instance}";
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(8)->post($endpoint, [
                    'number' => $phone,
                    'text' => $message,
                ]);

                if ($response->successful()) {
                    Log::info("[WhatsApp Evolution] Mensaje enviado a {$phone} vía {$url}: " . substr($message, 0, 50) . "...");
                    return true;
                }

                Log::error("[WhatsApp Evolution] Error en respuesta de {$url} ({$response->status()}): " . $response->body());
            } catch (\Throwable $e) {
                Log::warning("[WhatsApp Evolution] No se pudo conectar a {$url}: " . $e->getMessage());
            }
        }

        // Si fallaron los endpoints, guardar en log como respaldo
        $this->sendViaLog($phone, $message);
        return false;
    }

    /**
     * Envío a través de Meta Cloud API.
     */
    protected function sendViaMeta(string $phone, string $message): bool
    {
        $apiUrl = $this->metaConfig['api_url'] ?? 'https://graph.facebook.com/v20.0';
        $phoneId = $this->metaConfig['phone_number_id'] ?? '';
        $token = $this->metaConfig['access_token'] ?? '';

        if (empty($phoneId) || empty($token)) {
            Log::warning("[WhatsApp Meta] Faltan credenciales de Meta en .env.");
            $this->sendViaLog($phone, $message);
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(8)
                ->post("{$apiUrl}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Meta] Excepción: " . $e->getMessage());
            $this->sendViaLog($phone, $message);
            return false;
        }
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
        $baseUrl = $this->evolutionConfig['base_url'] ?? 'http://localhost:8085';
        $apiKey = $this->evolutionConfig['api_key'] ?? '';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';

        $candidateUrls = array_unique(array_filter([
            $baseUrl,
            str_replace([':8080', ':8085'], ':8085', $baseUrl),
            str_contains($baseUrl, 'localhost') ? str_replace('localhost', 'host.docker.internal', $baseUrl) : null,
            'http://host.docker.internal:8085',
            'http://localhost:8085',
            'http://host.docker.internal:8080',
            'http://localhost:8080',
            'http://nuvex_evolution_whatsapp:8080',
        ]));

        foreach ($candidateUrls as $url) {
            try {
                $response = Http::withHeaders([
                    'apikey' => $apiKey,
                ])->timeout(4)->get("{$url}/instance/connectionState/{$instance}");

                if ($response->successful()) {
                    return $response->json();
                }
            } catch (\Throwable $e) {
                // Continuar probando los demás candidatos
            }
        }

        return ['state' => 'unreachable', 'message' => 'No se pudo conectar a Evolution API en ningún endpoint'];
    }

    /**
     * Normaliza el número al formato internacional estándar (E.164 sin signo +).
     * Ejemplo para Colombia: 3106080402 -> 573106080402
     */
    public function sanitizePhoneNumber(string $phone): string
    {
        $clean = preg_replace('/\D/', '', $phone);

        // Si tiene 10 dígitos (celular de Colombia ej: 310...), anteponer '57'
        if (strlen($clean) === 10 && str_starts_with($clean, '3')) {
            return '57' . $clean;
        }

        return $clean;
    }
}
