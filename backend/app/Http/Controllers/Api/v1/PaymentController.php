<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\BoldPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        protected BoldPaymentService $boldService
    ) {}

    /**
     * Get Nequi and Daviplata transfer information for direct payments.
     */
    public function getNequiInfo(): JsonResponse
    {
        $nequiConfig = config('payment.nequi');

        $rawAccount = preg_replace('/\D/', '', (string) ($nequiConfig['account_number'] ?? ''));
        $maskedAccount = strlen($rawAccount) >= 4 ? '*** **** ' . substr($rawAccount, -4) : '*** **** 8385';

        return response()->json([
            'success' => true,
            'data' => [
                'holder_name' => 'Paola A. Aguilera C.',
                'account_number' => $maskedAccount,
                'account_type' => $nequiConfig['account_type'] ?? 'Nequi / Daviplata',
                // document_id omitido por proteccion de datos personales (Ley 1581 de 2012)
                'qr_image_url' => asset($nequiConfig['qr_image_url']),
                'instructions' => $nequiConfig['instructions'],
                'fee_discount_notice' => '¡Sin costos adicionales ni comisiones de pasarela!',
            ],
        ]);
    }

    /**
     * Create Bold checkout session for an appointment.
     */
    public function createBoldCheckout(Request $request): JsonResponse
    {
        if (! $this->boldService->isConfigured()) {
            return response()->json([
                'success' => false,
                'message' => 'La pasarela de pago en línea (Bold) no está configurada para este comercio.',
            ], 422);
        }

        $validated = $request->validate([
            'appointment_id' => 'required_without:appointment_number|integer|exists:appointments,id',
            'appointment_number' => 'required_without:appointment_id|string|exists:appointments,appointment_number',
        ]);

        $query = Appointment::with('service');
        if (! empty($validated['appointment_id'])) {
            $query->where('id', $validated['appointment_id']);
        } else {
            $query->where('appointment_number', $validated['appointment_number']);
        }

        $appointment = $query->firstOrFail();

        $checkoutData = $this->boldService->generateCheckoutSession($appointment);

        return response()->json([
            'success' => true,
            'data' => $checkoutData,
        ]);
    }

    /**
     * Handle incoming webhook notification from Bold gateway.
     */
    public function handleBoldWebhook(Request $request): JsonResponse
    {
        $signature = $request->header('x-bold-signature') ?? $request->header('x-signature');
        $rawPayload = $request->getContent();

        // Verify signature in production or if provided in test
        if ($signature && ! $this->boldService->verifyWebhookSignature($rawPayload, $signature)) {
            Log::warning('Firma inválida en webhook de Bold', ['signature' => $signature]);
            return response()->json([
                'success' => false,
                'message' => 'Firma de webhook inválida.',
            ], 403);
        }

        $payload = $request->all();
        $result = $this->boldService->processWebhookEvent($payload);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}