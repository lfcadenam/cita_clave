<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemOption;
use App\Models\Payment;
use App\Services\OrderCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function __construct(
        protected OrderCalculationService $calculationService
    ) {}

    /**
     * Endpoint para calcular cotización en tiempo real desde Angular
     */
    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:50',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer|exists:product_options,id',
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'time_slot_id' => 'nullable|exists:time_slots,id',
        ]);

        $quote = $this->calculationService->calculateQuote($validated);

        return response()->json([
            'success' => true,
            'data' => $quote,
        ]);
    }

    /**
     * Crear una orden formal lista para pasar a pago con Bold
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'nullable|integer|min:1|max:50',
            'selected_option_ids' => 'nullable|array',
            'selected_option_ids.*' => 'integer|exists:product_options,id',
            
            // Comprador
            'customer_name' => 'required|string|max:150',
            'customer_email' => 'required|email|max:150',
            'customer_phone' => 'required|string|max:30',
            'customer_document_id' => 'nullable|string|max:30',

            // Destinatario y Entrega en Bogotá
            'recipient_name' => 'required|string|max:150',
            'recipient_phone' => 'required|string|max:30',
            'recipient_address' => 'required|string|max:255',
            'recipient_address_details' => 'nullable|string|max:255',
            'delivery_zone_id' => 'required|exists:delivery_zones,id',
            'delivery_date' => 'required|date|after_or_equal:today',
            'time_slot_id' => 'required|exists:time_slots,id',
            'delivery_instructions' => 'nullable|string|max:500',

            // Personalización
            'card_message' => 'nullable|string|max:1000',
            'card_font_style' => 'nullable|string|max:50',
            'card_photo_url' => 'nullable|url|max:500',
            'order_notes' => 'nullable|string|max:500',
        ]);

        $quote = $this->calculationService->calculateQuote($validated);

        $order = DB::transaction(function () use ($validated, $quote, $request) {
            $orderNumber = 'NVX-' . strtoupper(Str::random(4)) . '-' . date('dmy');

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_name' => strip_tags(trim($validated['customer_name'])),
                'customer_email' => filter_var($validated['customer_email'], FILTER_SANITIZE_EMAIL),
                'customer_phone' => preg_replace('/[^0-9+ ]/', '', $validated['customer_phone']),
                'customer_document_id' => isset($validated['customer_document_id']) ? strip_tags(trim($validated['customer_document_id'])) : null,

                'recipient_name' => strip_tags(trim($validated['recipient_name'])),
                'recipient_phone' => preg_replace('/[^0-9+ ]/', '', $validated['recipient_phone']),
                'recipient_address' => strip_tags(trim($validated['recipient_address'])),
                'recipient_address_details' => isset($validated['recipient_address_details']) ? strip_tags(trim($validated['recipient_address_details'])) : null,
                'delivery_zone_id' => $validated['delivery_zone_id'],
                'delivery_zone_name' => $quote['delivery_zone']['name'],
                'delivery_date' => $validated['delivery_date'],
                'time_slot_id' => $validated['time_slot_id'],
                'time_slot_name' => $quote['time_slot']['name'],
                'delivery_instructions' => isset($validated['delivery_instructions']) ? strip_tags(trim($validated['delivery_instructions'])) : null,

                'card_message' => isset($validated['card_message']) ? strip_tags(trim($validated['card_message'])) : null,
                'card_font_style' => $validated['card_font_style'] ?? 'classic',
                'card_photo_url' => $validated['card_photo_url'] ?? null,
                'order_notes' => isset($validated['order_notes']) ? strip_tags(trim($validated['order_notes'])) : null,

                'subtotal' => $quote['subtotal'],
                'delivery_fee' => $quote['delivery_zone']['fee'],
                'time_slot_surcharge' => $quote['time_slot']['surcharge'],
                'discount_amount' => $quote['discount_amount'],
                'total_amount' => $quote['total_amount'],

                'status' => OrderStatus::PENDING,
                'payment_status' => PaymentStatus::PENDING,
            ]);

            // Crear ítem de la orden
            $item = OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $quote['product']['id'],
                'product_name' => $quote['product']['name'],
                'product_type' => $quote['product']['type'],
                'unit_base_price' => $quote['product']['base_price'],
                'quantity' => $quote['quantity'],
                'customizations_total' => $quote['customizations_total'],
                'item_total' => $quote['subtotal'],
            ]);

            // Guardar opciones seleccionadas para el ensamble
            foreach ($quote['selected_options'] as $opt) {
                OrderItemOption::create([
                    'order_item_id' => $item->id,
                    'product_option_id' => $opt['product_option_id'],
                    'option_group_name' => $opt['option_group_name'],
                    'option_name' => $opt['option_name'],
                    'additional_price' => $opt['additional_price'],
                ]);
            }

            // Crear registro de pago inicial
            Payment::create([
                'order_id' => $order->id,
                'gateway' => 'BOLD',
                'amount' => $order->total_amount,
                'currency' => 'COP',
                'status' => PaymentStatus::PENDING,
                'customer_ip' => $request->ip(),
            ]);

            return $order;
        });

        return response()->json([
            'success' => true,
            'message' => 'Pedido generado con éxito. Listo para proceder al pago.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => (float) $order->total_amount,
                'currency' => 'COP',
                'status' => $order->status->value,
            ],
        ], 201);
    }

    /**
     * Consultar estado del pedido
     */
    public function show(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.options', 'deliveryZone', 'timeSlot'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    /**
     * Generar sesión de pago y URL de Checkout de Bold
     */
    public function checkout(string $orderNumber, \App\Services\BoldPaymentService $boldService): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items'])
            ->firstOrFail();

        $session = $boldService->generatePaymentSession($order);

        return response()->json([
            'success' => true,
            'data' => $session,
        ]);
    }
}

