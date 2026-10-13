<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\WhatsAppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppWebController extends Controller
{
    public function __construct(
        protected WhatsAppNotificationService $whatsAppService
    ) {}

    /**
     * Muestra la pantalla oficial de vinculación de WhatsApp para la administradora.
     */
    public function showQr(Request $request): View
    {
        $tenant = Tenant::where('is_active', true)->first();

        // 1. Estado actual de la instancia
        $status = $this->whatsAppService->getEvolutionInstanceStatus();
        $state = $status['instance']['state'] ?? $status['state'] ?? 'disconnected';
        $isConnected = in_array(strtolower($state), ['open', 'connected']);

        $qrBase64 = null;
        $pairingCode = null;

        if (!$isConnected) {
            // Asegurar que la instancia existe
            $createRes = $this->whatsAppService->createEvolutionInstance();

            $qrBase64 = $createRes['qrcode']['base64'] 
                ?? $createRes['base64'] 
                ?? (is_string($createRes['qrcode'] ?? null) ? $createRes['qrcode'] : null);

            if ($qrBase64 && !str_starts_with($qrBase64, 'data:image')) {
                $qrBase64 = 'data:image/png;base64,' . $qrBase64;
            }

            // Si el usuario solicitó vincular por número de teléfono
            $phoneInput = $request->input('phone');
            if ($phoneInput) {
                $pairingCode = $this->whatsAppService->getPairingCode($phoneInput);
            }

            // Si create no trajo QR, consultarlo directamente
            if (!$qrBase64) {
                $qrBase64 = $this->whatsAppService->getEvolutionQrCode();
            }
        }

        return view('admin.whatsapp-connect', [
            'tenant' => $tenant,
            'isConnected' => $isConnected,
            'state' => $state,
            'qrBase64' => $qrBase64,
            'pairingCode' => $pairingCode,
            'adminPhone' => $tenant?->whatsapp_number ?? ($tenant?->phone ?? '3106080402'),
        ]);
    }

    /**
     * Consulta el estado de la conexión vía AJAX (para refresco automático sin recargar la página).
     */
    public function status(): JsonResponse
    {
        $status = $this->whatsAppService->getEvolutionInstanceStatus();
        $state = $status['instance']['state'] ?? $status['state'] ?? 'disconnected';
        $isConnected = in_array(strtolower($state), ['open', 'connected']);
        $qrBase64 = $isConnected ? null : $this->whatsAppService->getEvolutionQrCode();

        return response()->json([
            'connected' => $isConnected,
            'state' => $state,
            'qr' => $qrBase64,
        ]);
    }

    /**
     * Envía un mensaje de prueba al celular indicado desde la pantalla web.
     */
    public function testMessage(Request $request): JsonResponse
    {
        $phone = $request->input('phone', '3106080402');
        $msg = "✨ *¡Conexión Exitosa!* Tu WhatsApp oficial de *La Belle Nails Bogotá* ha quedado correctamente vinculado al sistema CitaClave.\n\nFecha y hora: " . now()->format('d/m/Y H:i:s');

        $result = $this->whatsAppService->sendTextMessageWithDetails($phone, $msg);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ]);
    }

    /**
     * Desvincula y resetea la instancia para generar un código QR fresco.
     */
    public function resetInstance(): JsonResponse
    {
        $this->whatsAppService->logoutEvolutionInstance();
        $this->whatsAppService->deleteEvolutionInstance();
        $createRes = $this->whatsAppService->createEvolutionInstance();

        $qr = $createRes['qrcode']['base64'] 
            ?? $createRes['base64'] 
            ?? (is_string($createRes['qrcode'] ?? null) ? $createRes['qrcode'] : null);

        if ($qr && !str_starts_with($qr, 'data:image')) {
            $qr = 'data:image/png;base64,' . $qr;
        }

        if (!$qr) {
            $qr = $this->whatsAppService->getEvolutionQrCode();
        }

        return response()->json([
            'success' => true,
            'message' => 'Instancia reiniciada correctamente. Escanea el nuevo código QR.',
            'qr' => $qr,
        ]);
    }
}
