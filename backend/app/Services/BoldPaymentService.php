<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class BoldPaymentService
{
    protected string $apiKey;
    protected string $secretKey;
    protected string $webhookSecret;
    protected string $checkoutUrl;

    public function __construct()
    {
        $this->apiKey = config('payment.bold.api_key');
        $this->secretKey = config('payment.bold.secret_key');
        $this->webhookSecret = config('payment.bold.webhook_secret');
        $this->checkoutUrl = config('payment.bold.checkout_url');
    }

    /**
     * Generate checkout payment session for Bold.
     */
    public function generateCheckoutSession(Appointment $appointment): array
    {
        $reference = $appointment->appointment_number;
        $amountInCents = (int) round($appointment->deposit_amount * 100);
        $currency = 'COP';

        // Integrity hash for Bold checkout
        $integritySignature = hash('sha256', "{$reference}{$amountInCents}{$currency}{$this->secretKey}");

        $checkoutData = [
            'order_id' => $reference,
            'amount' => (float) $appointment->deposit_amount,
            'currency' => $currency,
            'description' => 'Anticipo Reserva - ' . $appointment->service->name . ' - ' . $appointment->client_name,
            'tax' => 0,
            'client_name' => $appointment->client_name,
            'client_email' => $appointment->client_email ?? 'cliente@reserva.com',
            'client_phone' => $appointment->client_phone,
            'redirect_url' => url("/reserva/confirmacion/{$reference}"),
            'integrity_signature' => $integritySignature,
            'checkout_url' => $this->checkoutUrl . '?ref=' . urlencode($reference) . '&sig=' . $integritySignature,
        ];

        // Save reference to appointment
        $appointment->update([
            'payment_gateway_reference' => $reference,
        ]);

        return $checkoutData;
    }

    /**
     * Validate incoming webhook signature from Bold.
     */
    public function verifyWebhookSignature(string $rawPayload, ?string $receivedSignature): bool
    {
        if (empty($receivedSignature)) {
            return false;
        }

        $calculatedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);

        return hash_equals($calculatedSignature, $receivedSignature);
    }

    /**
     * Process webhook notification from Bold.
     */
    public function processWebhookEvent(array $payload): array
    {
        $reference = $payload['order_id'] ?? $payload['reference'] ?? null;
        $status = strtoupper($payload['status'] ?? '');
        $transactionId = $payload['transaction_id'] ?? $payload['id'] ?? null;

        if (! $reference) {
            return [
                'success' => false,
                'message' => 'Falta el número de orden / referencia en el payload de Bold.',
            ];
        }

        $appointment = Appointment::where('appointment_number', $reference)
            ->orWhere('payment_gateway_reference', $reference)
            ->first();

        if (! $appointment) {
            return [
                'success' => false,
                'message' => "Cita no encontrada para la referencia {$reference}.",
            ];
        }

        if (in_array($status, ['APPROVED', 'PAID', 'SUCCESS'])) {
            $depositPaid = (float) ($payload['amount'] ?? $appointment->deposit_amount);
            $appointment->update([
                'status' => AppointmentStatus::CONFIRMED,
                'deposit_paid' => $depositPaid,
                'balance_due' => max(0, $appointment->total_amount - $depositPaid),
                'verified_at' => Carbon::now(),
                'verification_notes' => "Pago aprobado automáticamente por Pasarela Bold. ID Tx: {$transactionId}",
                'payment_gateway_payload' => $payload,
            ]);

            return [
                'success' => true,
                'status' => 'CONFIRMED',
                'appointment_number' => $appointment->appointment_number,
                'message' => 'Cita confirmada exitosamente tras pago en Bold.',
            ];
        }

        if (in_array($status, ['REJECTED', 'FAILED', 'DECLINED'])) {
            $appointment->update([
                'status' => AppointmentStatus::CANCELLED,
                'cancellation_reason' => "Pago rechazado por pasarela Bold. Motivo: " . ($payload['reason'] ?? 'Transacción declinada'),
                'cancelled_at' => Carbon::now(),
                'payment_gateway_payload' => $payload,
            ]);

            return [
                'success' => true,
                'status' => 'CANCELLED',
                'appointment_number' => $appointment->appointment_number,
                'message' => 'Cita cancelada por rechazo en pasarela de pagos.',
            ];
        }

        return [
            'success' => true,
            'status' => 'IGNORED',
            'message' => "Estado de webhook '{$status}' no requiere actualización de cita.",
        ];
    }
}