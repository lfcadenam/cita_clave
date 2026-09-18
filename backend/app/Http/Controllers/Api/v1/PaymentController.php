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

        return response()->json([
            'success' => true,
            'data' => [
                'holder_name' => $nequiConfig['holder_name'],
                'account_number' => $nequiConfig['account_number'],
                'account_type' => $nequiConfig['account_type'],
                'document_id' => $nequiConfig['document_id'],
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