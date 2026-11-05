<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Tenant;
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
     * Get Nequi and Daviplata transfer information for direct payments from active Tenant configuration.
     */
    public function getNequiInfo(Request $request): JsonResponse
    {
        // 1. Resolver Tenant activo
        $tenant = app()->has('active_tenant')
            ? app('active_tenant')
            : null;

        if (! $tenant && ($request->hasHeader('X-Tenant-Id') || $request->query('tenant_id'))) {
            $tenantId = $request->header('X-Tenant-Id') ?: $request->query('tenant_id');
            $tenant = Tenant::where('is_active', true)->find($tenantId);
        }

        if (! $tenant && ($request->hasHeader('X-Tenant-Slug') || $request->query('tenant') || $request->query('slug'))) {
            $slug = $request->header('X-Tenant-Slug') ?: ($request->query('tenant') ?: $request->query('slug'));
            $tenant = Tenant::where('is_active', true)->where('slug', $slug)->first();
        }

        if (! $tenant) {
            $tenant = Tenant::where('is_active', true)->first();
        }

        $nequiConfig = config('payment.nequi', []);

        // 2. Extraer datos Nequi directamente del Tenant (con fallback seguro a config)
        $rawAccount = $tenant?->nequi_phone ?: ($nequiConfig['account_number'] ?? '3103248385');
        $rawAccount = preg_replace('/\D/', '', (string) $rawAccount);

        $formattedAccount = strlen($rawAccount) === 10
            ? substr($rawAccount, 0, 3) . ' ' . substr($rawAccount, 3, 3) . ' ' . substr($rawAccount, 6)
            : $rawAccount;

        $holderName = $tenant?->nequi_account_holder 
            ?: ($tenant?->display_name ?: ($tenant?->name ?: ($nequiConfig['holder_name'] ?? 'Paola Andrea Aguilera Camacho')));

        $accountType = $tenant?->nequi_account_type ?: ($nequiConfig['account_type'] ?? 'Personal');

        $qrImageUrl = null;
        if (! empty($tenant?->nequi_qr_image)) {
            $qrImageUrl = asset('storage/' . $tenant->nequi_qr_image);
        } elseif (! empty($nequiConfig['qr_image_url'])) {
            $qrImageUrl = asset($nequiConfig['qr_image_url']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'holder_name' => $holderName,
                'account_holder' => $holderName,
                'account_number' => $formattedAccount,
                'raw_account_number' => $rawAccount,
                'account_type' => $accountType,
                'qr_image_url' => $qrImageUrl,
                'qr_code_url' => $qrImageUrl,
                'instructions' => $nequiConfig['instructions'] ?? [
                    '1. Realiza la transferencia del anticipo a este número de Nequi.',
                    '2. Toma una captura de pantalla del comprobante de transferencia.',
                    '3. Adjunta la captura abajo para confirmar tu turno.',
                ],
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