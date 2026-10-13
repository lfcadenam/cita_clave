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
     * 2. Alerta inmediata a Paola (Administrador) sobre una nueva cita solicitada.
     */
    public function sendAdminNewBookingAlert(Appointment $appointment): bool
    {
        if (blank($this->adminPhone)) {
            return false;
        }

        $serviceName = $appointment->service->name;
        $dateFormatted = Carbon::parse($appointment->appointment_date)->locale('es')->isoFormat('dddd D [de] MMMM');
        $startTime = substr($appointment->start_time, 0, 5);
        $depositFormatted = number_format($appointment->deposit_amount, 0, ',', '.');
        $adminUrl = url("/admin/appointments");

        $message = "🔔 *Nueva Cita Solicitada — Paola Aguilera*\n\n"
            . "👤 *Clienta:* {$appointment->client_name}\n"
            . "📱 *Tel:* {$appointment->client_phone}\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted} a las {$startTime}\n"
            . "💵 *Abono:* \${$depositFormatted} COP\n"
            . "🔖 *Cita:* #{$appointment->appointment_number}\n\n"
            . "👉 *Ingresa al panel para verificar:*\n"
            . "{$adminUrl}";

        return $this->sendTextMessageSafe($this->adminPhone, $message);
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
        $baseUrl = $this->evolutionConfig['base_url'] ?? 'http://localhost:8080';
        $apiKey = $this->evolutionConfig['api_key'] ?? '';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';

        $endpoint = "{$baseUrl}/message/sendText/{$instance}";

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(8)->post($endpoint, [
                'number' => $phone,
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info("[WhatsApp Evolution] Mensaje enviado a {$phone}: " . substr($message, 0, 50) . "...");
                return true;
            }

            Log::error("[WhatsApp Evolution] Error en respuesta ({$response->status()}): " . $response->body());
            $this->sendViaLog($phone, $message);
            return false;
        } catch (\Throwable $e) {
            Log::warning("[WhatsApp Evolution] Gateway no accesible ({$baseUrl}), guardando en log como fallback: " . $e->getMessage());
            $this->sendViaLog($phone, $message);
            return false;
        }
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
        $baseUrl = $this->evolutionConfig['base_url'] ?? 'http://localhost:8080';
        $apiKey = $this->evolutionConfig['api_key'] ?? '';
        $instance = $this->evolutionConfig['instance_name'] ?? 'paola_estudio';

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
            ])->timeout(4)->get("{$baseUrl}/instance/connectionState/{$instance}");

            if ($response->successful()) {
                return $response->json();
            }

            return ['state' => 'disconnected', 'message' => $response->body()];
        } catch (\Throwable $e) {
            return ['state' => 'unreachable', 'message' => $e->getMessage()];
        }
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
