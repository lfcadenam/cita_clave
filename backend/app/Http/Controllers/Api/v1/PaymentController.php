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
     * Get Nequi transfer information directly from active Tenant configuration.
     */
    public function getNequiInfo(Request $request): JsonResponse
    {
        // 1. Resolver Tenant activo
        $tenant = app()->has('active_tenant')
            ? app('active_tenant')
            : null;

        if (! $tenant && ($request->hasHeader('X-Tenant-Id') || $request->query('tenant_id'))) {
            $tenantId = $request->header('X-Tenant-Id') ?: $request->query('tenant_id');
            $tenant = Tenant::find($tenantId);
        }

        if (! $tenant && ($request->hasHeader('X-Tenant-Slug') || $request->query('tenant') || $request->query('slug'))) {
            $slug = $request->header('X-Tenant-Slug') ?: ($request->query('tenant') ?: $request->query('slug'));
            $tenant = Tenant::where('slug', $slug)->first();
        }

        if (! $tenant) {
            $tenant = Tenant::where('is_active', true)->latest('id')->first()
                ?: Tenant::where('is_active', true)->first();
        }

        // 2. Extraer datos Nequi directamente de la configuración guardada del Tenant
        $rawAccount = ! empty($tenant?->nequi_phone)
            ? $tenant->nequi_phone
            : (string) config('payment.nequi.account_number', '3103248385');
        $rawAccount = preg_replace('/\D/', '', (string) $rawAccount);

        $formattedAccount = strlen($rawAccount) === 10
            ? substr($rawAccount, 0, 3) . ' ' . substr($rawAccount, 3, 3) . ' ' . substr($rawAccount, 6)
            : $rawAccount;

        $holderName = ! empty($tenant?->nequi_account_holder)
            ? $tenant->nequi_account_holder
            : ($tenant?->name ?: (string) config('payment.nequi.holder_name', 'Titular del Salón'));

        $accountType = ! empty($tenant?->nequi_account_type)
            ? $tenant->nequi_account_type
            : 'Personal';

        return response()->json([
            'success' => true,
            'data' => [
                'holder_name' => $holderName,
                'account_holder' => $holderName,
                'account_number' => $formattedAccount,
                'raw_account_number' => $rawAccount,
                'account_type' => $accountType,
                'instructions' => 'Por favor realiza la transferencia del anticipo a este número de Nequi y a continuación adjunta la captura del comprobante abajo para confirmar y apartar tu turno.',
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