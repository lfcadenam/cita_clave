<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Webhook oficial de Meta WhatsApp Cloud API.
 *
 * GET  /api/v1/webhooks/whatsapp  -> Verificación (hub.challenge) al registrar el webhook en Meta.
 * POST /api/v1/webhooks/whatsapp  -> Recepción de estados (sent/delivered/read/failed) y mensajes entrantes.
 */
class MetaWhatsAppWebhookController extends Controller
{
    /**
     * Verificación del webhook solicitada por Meta.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $validTokens = array_filter(array_merge(
            [config('whatsapp.meta.verify_token')],
            Tenant::query()->whereNotNull('meta_verify_token')->pluck('meta_verify_token')->all()
        ));

        if ($mode === 'subscribe' && $token && in_array($token, $validTokens, true)) {
            Log::info('[WhatsApp Meta Webhook] Verificación exitosa del webhook.');
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('[WhatsApp Meta Webhook] Verificación rechazada: token inválido.');
        return response('Forbidden', 403);
    }

    /**
     * Recepción de eventos enviados por Meta.
     */
    public function handle(Request $request): JsonResponse
    {
        if (! $this->hasValidSignature($request)) {
            Log::warning('[WhatsApp Meta Webhook] Firma X-Hub-Signature-256 inválida.');
            return response()->json(['success' => false], 401);
        }

        $payload = $request->json()->all();

        try {
            foreach ($payload['entry'] ?? [] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    // Estados de entrega de mensajes enviados por el sistema
                    foreach ($value['statuses'] ?? [] as $status) {
                        $state = $status['status'] ?? 'unknown';
                        $recipient = $status['recipient_id'] ?? '';
                        $wamid = $status['id'] ?? '';

                        if ($state === 'failed') {
                            $error = $status['errors'][0] ?? [];
                            Log::warning("[WhatsApp Meta Webhook] Mensaje {$wamid} a +{$recipient} FALLÓ: (#" . ($error['code'] ?? '?') . ') ' . ($error['title'] ?? $error['message'] ?? 'Sin detalle'));
                        } else {
                            Log::info("[WhatsApp Meta Webhook] Mensaje {$wamid} a +{$recipient}: {$state}");
                        }
                    }

                    // Mensajes entrantes (abren la ventana de servicio de 24h)
                    foreach ($value['messages'] ?? [] as $message) {
                        $from = $message['from'] ?? '';
                        $type = $message['type'] ?? 'text';
                        $text = $message['text']['body']
                            ?? $message['button']['text']
                            ?? $message['interactive']['button_reply']['title']
                            ?? "[{$type}]";

                        Log::info("[WhatsApp Meta Webhook] Mensaje entrante de +{$from}: {$text}");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Nunca devolver error a Meta para evitar reintentos masivos
            Log::error('[WhatsApp Meta Webhook] Error procesando payload: ' . $e->getMessage());
        }

        return response()->json(['success' => true]);
    }

    /**
     * Valida la firma HMAC SHA-256 enviada por Meta (solo si WHATSAPP_APP_SECRET está configurado).
     */
    protected function hasValidSignature(Request $request): bool
    {
        $appSecret = (string) config('whatsapp.meta.app_secret');
        if ($appSecret === '') {
            return true;
        }

        $signature = (string) $request->header('X-Hub-Signature-256', '');
        $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $appSecret);

        return $signature !== '' && hash_equals($expected, $signature);
    }
}
