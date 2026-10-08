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
     * Envía recordatorio de cita con 24 horas de anticipación a la clienta.
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

        $sent = $this->sendTextMessage($clientPhone, $message);

        if ($sent) {
            $appointment->update([
                'reminder_24h_sent_at' => now(),
            ]);
        }

        return $sent;
    }

    /**
     * Envía confirmación de reserva recién agendada.
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
            . "Tu cita *#{$appointment->appointment_number}* ha sido registrada exitosamente:\n\n"
            . "💅 *Servicio:* {$serviceName}\n"
            . "📅 *Fecha:* {$dateFormatted}\n"
            . "⏰ *Hora:* {$startTime}\n"
            . "📍 *Lugar:* Estudio Paola Aguilera, Bogotá\n\n"
            . "🎟️ *Ver comprobante digital:*\n"
            . "{$voucherUrl}\n\n"
            . "_¡Gracias por elegirnos! Recibirás un recordatorio 24h antes de tu cita._";

        return $this->sendTextMessage($clientPhone, $message);
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
            ])->timeout(10)->post($endpoint, [
                'number' => $phone,
                'text' => $message,
            ]);

            if ($response->successful()) {
                Log::info("[WhatsApp Evolution] Mensaje enviado a {$phone}: " . substr($message, 0, 50) . "...");
                return true;
            }

            Log::error("[WhatsApp Evolution] Error en respuesta ({$response->status()}): " . $response->body());
            return false;
        } catch (\Throwable $e) {
            Log::warning("[WhatsApp Evolution] No se pudo contactar el Gateway ({$baseUrl}): " . $e->getMessage());
            // Si el gateway no está levantado, registramos en log como fallback
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
            return false;
        }

        try {
            $response = Http::withToken($token)
                ->post("{$apiUrl}/{$phoneId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $message],
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error("[WhatsApp Meta] Excepción: " . $e->getMessage());
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
            ])->timeout(5)->get("{$baseUrl}/instance/connectionState/{$instance}");

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
