<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\BoldPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BoldWebhookController extends Controller
{
    public function __construct(
        protected BoldPaymentService $boldService
    ) {}

    /**
     * Endpoint receptor del Webhook de Bold
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Bold Webhook recibido:', ['payload' => $payload]);

        $orderNumber = $request->input('reference') ?? $request->input('order_number') ?? $request->input('data.reference');
        $amount = (float) ($request->input('amount') ?? $request->input('data.amount') ?? 0);
        $status = strtoupper($request->input('status') ?? $request->input('data.status') ?? 'PENDING');
        $transactionId = $request->input('transaction_id') ?? $request->input('data.id') ?? 'TX-'.time();
        $paymentMethod = $request->input('payment_method') ?? $request->input('data.payment_method_type') ?? 'BOLD_PSE';
        $receivedSignature = $request->input('signature') ?? $request->input('integrity_signature') ?? $request->header('x-bold-signature');

        if (!$orderNumber) {
            return response()->json(['success' => false, 'message' => 'Falta referencia de orden en el payload'], 400);
        }

        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        // 1. Verificación Criptográfica de la Firma (Seguridad contra fraudes)
        if ($receivedSignature) {
            $isValid = $this->boldService->verifyWebhookSignature($orderNumber, (float) $order->total_amount, $receivedSignature);
            if (!$isValid) {
                Log::warning("Firma de Webhook Bold INVÁLIDA para orden: {$orderNumber}");
                return response()->json(['success' => false, 'message' => 'Firma criptográfica inválida'], 401);
            }
        }

        // 2. Control de Idempotencia (Evita procesar dos veces el mismo evento)
        if ($order->status === OrderStatus::PAID && $status === 'APPROVED') {
            Log::info("Orden {$orderNumber} ya se encuentra PAGADA. Evento ignorado por idempotencia.");
            return response()->json(['success' => true, 'message' => 'Orden ya procesada previamente (Idempotente)']);
        }

        // 3. Procesamiento Transaccional del Pago
        DB::transaction(function () use ($order, $status, $transactionId, $paymentMethod, $payload, $amount) {
            $payment = Payment::firstOrNew(['order_id' => $order->id]);
            $payment->gateway = 'BOLD';
            $payment->bold_transaction_id = $transactionId;
            $payment->amount = $amount ?: $order->total_amount;
            $payment->currency = 'COP';
            $payment->payment_method = $paymentMethod;
            $payment->webhook_payload = $payload;

            if ($status === 'APPROVED' || $status === 'PAID') {
                $order->status = OrderStatus::PAID;
                $order->payment_status = PaymentStatus::APPROVED;
                $order->payment_method = $paymentMethod;
                $order->paid_at = now();
                $payment->status = PaymentStatus::APPROVED;

                Log::info("🎉 Orden {$order->order_number} PAGADA y APROBADA exitosamente vía Bold.");

                // Disparar notificaciones asíncronas de WhatsApp
                \App\Jobs\SendWhatsAppOrderConfirmationJob::dispatch($order);
                \App\Jobs\SendWhatsAppAdminAlertJob::dispatch($order);
            } elseif ($status === 'REJECTED' || $status === 'FAILED') {
                $order->payment_status = PaymentStatus::REJECTED;
                $payment->status = PaymentStatus::REJECTED;
                Log::warning("Pago rechazado para orden {$order->order_number}");
            }

            $order->save();
            $payment->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Webhook procesado con éxito',
            'order_number' => $order->order_number,
            'status' => $order->status->value,
        ]);
    }
}
