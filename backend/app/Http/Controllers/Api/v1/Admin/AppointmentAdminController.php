<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Service;
use App\Services\WhatsAppNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AppointmentAdminController extends Controller
{
    /**
     * Listado de citas para el salón activo con filtros avanzados.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d',
            'status' => 'nullable|string',
            'service_id' => 'nullable|integer|exists:services,id',
            'search' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:200',
            'all' => 'nullable|boolean',
            'exclude_cancelled' => 'nullable|boolean',
            'order_dir' => 'nullable|string|in:asc,desc',
        ]);

        $query = Appointment::query()
            ->with([
                'service:id,name,duration_minutes,base_price,deposit_amount',
                'customer' => fn ($q) => $q->select('id', 'tenant_id', 'name', 'phone', 'email', 'is_legacy')->withCount('appointments'),
            ]);

        // Filtro por fecha específica o rango
        if (! empty($validated['date'])) {
            $query->whereDate('appointment_date', $validated['date']);
        } elseif (! empty($validated['start_date']) && ! empty($validated['end_date'])) {
            $query->whereBetween('appointment_date', [$validated['start_date'], $validated['end_date']]);
        } elseif (! empty($validated['start_date'])) {
            $query->whereDate('appointment_date', '>=', $validated['start_date']);
        } elseif (! empty($validated['end_date'])) {
            $query->whereDate('appointment_date', '<=', $validated['end_date']);
        }

        // Filtro por estado (puede ser uno o varios separados por coma)
        if (! empty($validated['status'])) {
            $statuses = array_map('trim', explode(',', $validated['status']));
            $query->whereIn('status', $statuses);
        } elseif ($request->boolean('exclude_cancelled', false)) {
            $query->where('status', '!=', AppointmentStatus::CANCELLED->value);
        }

        // Filtro por servicio
        if (! empty($validated['service_id'])) {
            $query->where('service_id', $validated['service_id']);
        }

        // Búsqueda por nombre, teléfono o código de cita
        if (! empty($validated['search'])) {
            $term = '%' . trim($validated['search']) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('client_name', 'LIKE', $term)
                  ->orWhere('client_phone', 'LIKE', $term)
                  ->orWhere('appointment_number', 'LIKE', $term);
            });
        }

        // Ordenamiento: si es calendario (con fecha inicio), ascendente; si es listado tabular, descendente por defecto
        $defaultOrder = (! empty($validated['start_date']) || ! empty($validated['date'])) ? 'asc' : 'desc';
        $orderDir = $validated['order_dir'] ?? $defaultOrder;

        $query->orderBy('appointment_date', $orderDir)
              ->orderBy('start_time', $orderDir);

        if ($request->boolean('all', false)) {
            $appointments = $query->get();
            return response()->json([
                'success' => true,
                'data' => $appointments->map(fn ($apt) => $this->formatAppointment($apt)),
                'meta' => [
                    'total' => $appointments->count(),
                ],
            ]);
        }

        $perPage = (int) ($validated['per_page'] ?? 10);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($paginated->items())->map(fn ($apt) => $this->formatAppointment($apt)),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    /**
     * Crear una nueva cita administrativa con detección de conflictos y sincronización de cliente.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'nullable|integer|exists:services,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer|exists:services,id',
            'appointment_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|string',
            'end_time' => 'nullable|string',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'deposit_paid' => 'nullable|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => ['nullable', 'string', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'payment_method' => ['nullable', 'string', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'admin_notes' => 'nullable|string|max:1000',
            'client_notes' => 'nullable|string|max:1000',
        ]);

        // Resolver lista de servicios (soporta 1 o múltiples servicios)
        $serviceIds = [];
        if (!empty($validated['service_ids']) && is_array($validated['service_ids'])) {
            $serviceIds = array_values(array_unique(array_filter($validated['service_ids'])));
        } elseif (!empty($validated['service_id'])) {
            $serviceIds = [(int) $validated['service_id']];
        }

        if (empty($serviceIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar al menos un servicio o tratamiento.',
            ], 422);
        }

        $services = Service::whereIn('id', $serviceIds)->get();
        if ($services->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Los servicios seleccionados no existen o están inactivos.',
            ], 422);
        }

        // Servicio principal
        $primaryService = $services->firstWhere('id', $serviceIds[0]) ?: $services->first();
        $date = $validated['appointment_date'];

        // Duración acumulada de todos los servicios seleccionados
        $totalDurationMinutes = $services->sum('duration_minutes');

        // Normalización y cálculo de horas
        $startTime = Carbon::parse($validated['start_time'])->format('H:i:s');
        if (! empty($validated['end_time'])) {
            $endTime = Carbon::parse($validated['end_time'])->format('H:i:s');
        } else {
            $endTime = Carbon::parse($date . ' ' . $startTime)
                ->addMinutes($totalDurationMinutes)
                ->format('H:i:s');
        }

        // Validar conflicto de horario
        $conflict = Appointment::findConflicting($date, $startTime, $endTime);
        if ($conflict) {
            return response()->json([
                'success' => false,
                'message' => "Conflicto de horario: Ya existe una cita asignada ({$conflict->client_name} de " .
                    substr($conflict->start_time, 0, 5) . " a " . substr($conflict->end_time, 0, 5) . ").",
                'conflict' => [
                    'id' => $conflict->id,
                    'appointment_number' => $conflict->appointment_number,
                    'client_name' => $conflict->client_name,
                    'start_time' => $conflict->start_time,
                    'end_time' => $conflict->end_time,
                    'status' => $conflict->status?->value,
                ],
            ], 422);
        }

        // Cálculos financieros y regla de abono:
        // Clienta nueva: abono fijo de $50.000 COP independientemente de los servicios o cantidad.
        // Clienta antigua / recurrente: exenta de abono ($0 COP).
        $cleanPhone = preg_replace('/[^0-9]/', '', (string) $validated['client_phone']);
        $existingCustomer = Customer::where('phone', $cleanPhone)->first();
        $isNewCustomer = ! $existingCustomer || $existingCustomer->isNew();

        $ruleBasedDeposit = $isNewCustomer ? 50000.0 : 0.0;

        $totalAmount = isset($validated['total_amount']) && $validated['total_amount'] !== null
            ? (float) $validated['total_amount']
            : (float) $services->sum('base_price');

        $depositAmount = isset($validated['deposit_amount']) && $validated['deposit_amount'] !== null
            ? (float) $validated['deposit_amount']
            : $ruleBasedDeposit;

        $depositPaid = isset($validated['deposit_paid']) && $validated['deposit_paid'] !== null
            ? (float) $validated['deposit_paid']
            : ($isNewCustomer ? $depositAmount : 0.0);

        $balanceDue = max(0, $totalAmount - $depositPaid);

        // Estructura serializada de servicios seleccionados
        $servicesPayload = $services->map(fn ($s) => [
            'id' => $s->id,
            'name' => $s->name,
            'duration_minutes' => (int) $s->duration_minutes,
            'base_price' => (float) $s->base_price,
            'deposit_amount' => (float) $s->deposit_amount,
        ])->values()->toArray();

        // Notas administrativas automáticas si son múltiples servicios
        $adminNotes = $validated['admin_notes'] ?? null;
        if ($services->count() > 1) {
            $serviceSummary = "Servicios contratados (" . $services->count() . "): " . $services->pluck('name')->implode(' + ');
            $adminNotes = $adminNotes ? "{$serviceSummary}\n{$adminNotes}" : $serviceSummary;
        }

        // Estado por defecto para reservas creadas desde administración
        $status = $validated['status'] ?? AppointmentStatus::CONFIRMED->value;
        $paymentMethod = $validated['payment_method'] ?? PaymentMethod::NEQUI_TRANSFER->value;

        $appointment = new Appointment([
            'service_id' => $primaryService->id,
            'services_payload' => $servicesPayload,
            'user_id' => $request->user()->id,
            'client_name' => trim($validated['client_name']),
            'client_phone' => trim($validated['client_phone']),
            'client_email' => ! empty($validated['client_email']) ? trim($validated['client_email']) : null,
            'appointment_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => $status,
            'payment_method' => $paymentMethod,
            'total_amount' => $totalAmount,
            'deposit_amount' => $depositAmount,
            'deposit_paid' => $depositPaid,
            'balance_due' => $balanceDue,
            'admin_notes' => $adminNotes,
            'client_notes' => $validated['client_notes'] ?? null,
        ]);

        if (($depositPaid >= $depositAmount && $depositPaid > 0) || ($depositAmount == 0)) {
            $appointment->verified_at = now();
        }

        $appointment->save();

        // Sincronizar ficha de cliente
        try {
            $customer = Customer::syncFromAppointment($appointment);
            if (! $appointment->customer_id) {
                $appointment->customer_id = $customer->id;
                $appointment->saveQuietly();
            }
        } catch (\Throwable $e) {
            // Log de resiliencia
        }

        // Notificar por WhatsApp a la clienta sobre su cita creada/confirmada desde el panel administrativo
        try {
            $whatsAppService = app(WhatsAppNotificationService::class);
            if ($appointment->status === AppointmentStatus::CONFIRMED) {
                $whatsAppService->sendBookingConfirmation($appointment);
            } else {
                $whatsAppService->sendBookingCreated($appointment);
            }
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar WhatsApp a la clienta para cita administrativa #{$appointment->appointment_number}: " . $e->getMessage());
        }

        $appointment->load(['service', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Cita agendada exitosamente.',
            'data' => $this->formatAppointment($appointment),
        ], 201);
    }

    /**
     * Consultar detalle de una cita específica.
     */
    public function show(int $id): JsonResponse
    {
        $appointment = Appointment::query()
            ->with([
                'service',
                'customer',
                'user:id,name,email',
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $this->formatAppointment($appointment, true),
        ]);
    }

    /**
     * Actualizar datos de una cita existente.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::query()->findOrFail($id);

        $validated = $request->validate([
            'service_id' => 'nullable|integer|exists:services,id',
            'appointment_date' => 'nullable|date_format:Y-m-d',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'client_name' => 'nullable|string|max:255',
            'client_phone' => 'nullable|string|max:50',
            'client_email' => 'nullable|email|max:255',
            'deposit_paid' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => ['nullable', 'string', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'payment_method' => ['nullable', 'string', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'admin_notes' => 'nullable|string|max:1000',
            'client_notes' => 'nullable|string|max:1000',
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $serviceId = $validated['service_id'] ?? $appointment->service_id;
        $service = Service::findOrFail($serviceId);

        $date = $validated['appointment_date'] ?? $appointment->appointment_date->toDateString();
        $startTime = ! empty($validated['start_time'])
            ? Carbon::parse($validated['start_time'])->format('H:i:s')
            : $appointment->start_time;

        if (! empty($validated['end_time'])) {
            $endTime = Carbon::parse($validated['end_time'])->format('H:i:s');
        } elseif ($validated['start_time'] ?? null || $validated['service_id'] ?? null) {
            $endTime = Carbon::parse($date . ' ' . $startTime)
                ->addMinutes($service->duration_minutes)
                ->format('H:i:s');
        } else {
            $endTime = $appointment->end_time;
        }

        // Si cambian fecha u horas, verificar solapamiento excluyendo la cita actual
        if ($date !== $appointment->appointment_date->toDateString() || $startTime !== $appointment->start_time || $endTime !== $appointment->end_time) {
            $conflict = Appointment::findConflicting($date, $startTime, $endTime, $appointment->id);
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => "Conflicto de horario: Ya existe una cita asignada ({$conflict->client_name} de " .
                        substr($conflict->start_time, 0, 5) . " a " . substr($conflict->end_time, 0, 5) . ").",
                    'conflict' => [
                        'id' => $conflict->id,
                        'appointment_number' => $conflict->appointment_number,
                        'client_name' => $conflict->client_name,
                        'start_time' => $conflict->start_time,
                        'end_time' => $conflict->end_time,
                    ],
                ], 422);
            }
        }

        // Actualizar datos
        $appointment->service_id = $serviceId;
        $appointment->appointment_date = $date;
        $appointment->start_time = $startTime;
        $appointment->end_time = $endTime;

        if (isset($validated['client_name'])) {
            $appointment->client_name = trim($validated['client_name']);
        }
        if (isset($validated['client_phone'])) {
            $appointment->client_phone = trim($validated['client_phone']);
        }
        if (array_key_exists('client_email', $validated)) {
            $appointment->client_email = ! empty($validated['client_email']) ? trim($validated['client_email']) : null;
        }

        if (isset($validated['total_amount'])) {
            $appointment->total_amount = (float) $validated['total_amount'];
        }
        if (isset($validated['deposit_paid'])) {
            $appointment->deposit_paid = (float) $validated['deposit_paid'];
        }

        // Recalcular saldo pendiente
        $appointment->balance_due = max(0, (float) $appointment->total_amount - (float) $appointment->deposit_paid);

        if (! empty($validated['status'])) {
            $appointment->status = $validated['status'];
            if ($validated['status'] === AppointmentStatus::CANCELLED->value && ! $appointment->cancelled_at) {
                $appointment->cancelled_at = now();
                $appointment->cancellation_reason = $validated['cancellation_reason'] ?? 'Cancelada por administración';
            }
        }

        if (! empty($validated['payment_method'])) {
            $appointment->payment_method = $validated['payment_method'];
        }

        if (array_key_exists('admin_notes', $validated)) {
            $appointment->admin_notes = $validated['admin_notes'];
        }
        if (array_key_exists('client_notes', $validated)) {
            $appointment->client_notes = $validated['client_notes'];
        }

        $appointment->save();

        // Actualizar ficha de cliente si cambió teléfono o nombre sin duplicar registros
        try {
            $customer = Customer::syncFromAppointment($appointment);
            if ($customer && $appointment->customer_id !== $customer->id) {
                $appointment->customer_id = $customer->id;
                $appointment->saveQuietly();
            }
        } catch (\Throwable $e) {}

        $appointment->load(['service', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Cita actualizada correctamente.',
            'data' => $this->formatAppointment($appointment, true),
        ]);
    }

    /**
     * Transición rápida de estado (Confirmar, Iniciar atención, Completar, Cancelar).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::query()->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'cancellation_reason' => 'nullable|string|max:500',
        ]);

        $newStatus = AppointmentStatus::from($validated['status']);
        $appointment->status = $newStatus;

        if ($newStatus === AppointmentStatus::CONFIRMED && ! $appointment->verified_at) {
            $appointment->verified_at = now();
        } elseif ($newStatus === AppointmentStatus::CANCELLED) {
            $appointment->cancelled_at = now();
            $appointment->cancellation_reason = $validated['cancellation_reason'] ?? 'Cancelada por administración';
        }

        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => "Estado de la cita actualizado a {$newStatus->label()}.",
            'data' => $this->formatAppointment($appointment),
        ]);
    }

    /**
     * Validar y aprobar comprobante de transferencia Nequi / Daviplata.
     */
    public function approveNequi(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::query()->findOrFail($id);

        $validated = $request->validate([
            'deposit_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $depositAmount = $appointment->deposit_amount ?: ($appointment->service?->deposit_amount ?? 20000);
        $depositPaid = isset($validated['deposit_paid']) ? (float) $validated['deposit_paid'] : (float) $depositAmount;

        $appointment->status = AppointmentStatus::CONFIRMED;
        $appointment->verified_at = now();
        $appointment->deposit_paid = $depositPaid;
        $appointment->balance_due = max(0, (float) $appointment->total_amount - $depositPaid);

        if (! empty($validated['notes'])) {
            $appointment->verification_notes = trim(($appointment->verification_notes ? $appointment->verification_notes . "\n" : '') . $validated['notes']);
        }

        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => 'Comprobante Nequi verificado y cita confirmada exitosamente.',
            'data' => $this->formatAppointment($appointment, true),
        ]);
    }

    /**
     * Rechazar comprobante de transferencia Nequi y notificar a la clienta por WhatsApp.
     */
    public function rejectNequi(Request $request, int $id): JsonResponse
    {
        $appointment = Appointment::query()->findOrFail($id);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $reason = $validated['reason'] ?? 'Comprobante no válido o ilegible';
        $appointment->status = AppointmentStatus::PENDING_DEPOSIT;
        $appointment->deposit_proof_image = null;
        $appointment->verification_notes = trim(($appointment->verification_notes ? $appointment->verification_notes . "\n" : '') . "Rechazado: {$reason}");
        $appointment->save();

        try {
            $whatsAppService = app(WhatsAppNotificationService::class);
            $whatsAppService->sendDepositRejected($appointment, $reason);
        } catch (\Throwable $e) {
            Log::warning("No se pudo enviar WhatsApp de rechazo de abono para cita #{$appointment->appointment_number}: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Comprobante rechazado y notificación enviada a la clienta.',
            'data' => $this->formatAppointment($appointment, true),
        ]);
    }

    /**
     * Estructura limpia y enriquecida para el Frontend.
     */
    protected function formatAppointment(Appointment $appointment, bool $detailed = false): array
    {
        $startTime = Carbon::parse($appointment->start_time)->format('h:i A');
        $endTime = Carbon::parse($appointment->end_time)->format('h:i A');

        $data = [
            'id' => $appointment->id,
            'appointment_number' => $appointment->appointment_number,
            'date' => $appointment->appointment_date ? $appointment->appointment_date->toDateString() : null,
            'date_formatted' => $appointment->appointment_date ? $appointment->appointment_date->translatedFormat('d M, Y') : null,
            'start_time' => substr($appointment->start_time, 0, 5),
            'end_time' => substr($appointment->end_time, 0, 5),
            'time_range' => "{$startTime} – {$endTime}",
            'status' => $appointment->status?->value,
            'status_label' => $appointment->status?->label(),
            'status_color' => $appointment->status?->color(),
            'payment_method' => $appointment->payment_method?->value,
            'payment_method_label' => $appointment->payment_method?->label(),
            'financials' => [
                'total_amount' => (float) $appointment->total_amount,
                'deposit_amount' => (float) $appointment->deposit_amount,
                'deposit_paid' => (float) $appointment->deposit_paid,
                'balance_due' => (float) $appointment->balance_due,
            ],
            'client' => [
                'name' => $appointment->client_name,
                'phone' => $appointment->client_phone,
                'display_phone' => $appointment->customer?->getDisplayPhone() ?? $appointment->client_phone,
                'email' => $appointment->client_email,
                'whatsapp_url' => $appointment->customer?->getWhatsAppUrl() ?? "https://wa.me/" . preg_replace('/[^0-9]/', '', (string)$appointment->client_phone),
                'loyalty_badge' => $appointment->customer?->getLoyaltyBadge() ?? null,
                'is_new' => $appointment->isClientNew(),
            ],
            'service' => $appointment->service ? [
                'id' => $appointment->service->id,
                'name' => $appointment->services_names ?: $appointment->service->name,
                'duration_minutes' => $appointment->service->duration_minutes,
                'formatted_duration' => $appointment->service->formatted_duration,
                'base_price' => (float) $appointment->service->base_price,
            ] : null,
            'services' => !empty($appointment->services_payload) ? $appointment->services_payload : ($appointment->service ? [[
                'id' => $appointment->service->id,
                'name' => $appointment->service->name,
                'duration_minutes' => (int) $appointment->service->duration_minutes,
                'base_price' => (float) $appointment->service->base_price,
                'deposit_amount' => (float) $appointment->service->deposit_amount,
            ]] : []),
            'services_count' => !empty($appointment->services_payload) ? count($appointment->services_payload) : ($appointment->service ? 1 : 0),
            'notes' => [
                'admin_notes' => $appointment->admin_notes,
                'client_notes' => $appointment->client_notes,
            ],
            'created_at' => $appointment->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['verification'] = [
                'deposit_proof_image' => $appointment->deposit_proof_image ? asset('storage/' . ltrim($appointment->deposit_proof_image, '/')) : null,
                'verification_notes' => $appointment->verification_notes,
                'verified_at' => $appointment->verified_at?->toIso8601String(),
            ];
            $data['cancellation'] = [
                'cancellation_reason' => $appointment->cancellation_reason,
                'cancelled_at' => $appointment->cancelled_at?->toIso8601String(),
            ];
            $data['attendance_confirmed_at'] = $appointment->attendance_confirmed_at?->toIso8601String();
        }

        return $data;
    }
}
