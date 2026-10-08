<?php

namespace App\Http\Controllers\Api\v1;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AppointmentBookingController extends Controller
{
    public function __construct(
        protected BookingAvailabilityService $availabilityService
    ) {}

    /**
     * Look up client by phone to prefill details for returning clients.
     */
    public function lookupClient(Request $request): JsonResponse
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $request->query('phone', ''));

        if (strlen($phone) < 7) {
            return response()->json([
                'success' => false,
                'data' => null,
            ]);
        }

        $appointment = Appointment::where('client_phone', 'LIKE', "%{$phone}%")
            ->orderBy('id', 'desc')
            ->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'name' => $appointment->client_name,
                'phone' => $appointment->client_phone,
                'email' => $appointment->client_email,
            ],
        ]);
    }

    /**
     * Hold / Lock a slot temporarily (15 minutes) while client finishes checkout.
     */
    public function hold(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email|max:255',
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $date = Carbon::parse($validated['date']);
        $startTime = $validated['start_time'];

        return DB::transaction(function () use ($service, $date, $startTime, $validated) {
            // Lock and verify slot is available
            $isAvailable = $this->availabilityService->isSlotAvailable($service, $date, $startTime);

            if (! $isAvailable) {
                return response()->json([
                    'success' => false,
                    'message' => 'El horario seleccionado ya no se encuentra disponible. Por favor elige otro horario.',
                ], 422);
            }

            $endTime = Carbon::parse($date->toDateString() . ' ' . $startTime)
                ->addMinutes($service->duration_minutes)
                ->format('H:i:s');

            if (Appointment::hasConflict($date, $startTime, $endTime)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El horario seleccionado ya se encuentra ocupado por otra cita. Por favor elige otro horario.',
                ], 422);
            }

            $appointment = Appointment::create([
                'service_id' => $service->id,
                'client_name' => $validated['client_name'],
                'client_phone' => $validated['client_phone'],
                'client_email' => $validated['client_email'] ?? null,
                'appointment_date' => $date->toDateString(),
                'start_time' => $startTime . ':00',
                'end_time' => $endTime,
                'total_amount' => $service->base_price,
                'deposit_amount' => $service->deposit_amount,
                'deposit_paid' => 0,
                'balance_due' => $service->base_price,
                'payment_method' => PaymentMethod::NEQUI_TRANSFER,
                'status' => AppointmentStatus::PENDING_DEPOSIT,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cupo apartado temporalmente por 15 minutos.',
                'data' => [
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'expires_at' => $appointment->created_at->addMinutes(15)->toIso8601String(),
                    'service' => [
                        'name' => $service->name,
                        'duration_minutes' => $service->duration_minutes,
                        'total_amount' => (float) $appointment->total_amount,
                        'deposit_amount' => (float) $appointment->deposit_amount,
                    ],
                    'date' => $appointment->appointment_date->toDateString(),
                    'booking_date' => $appointment->appointment_date->toDateString(),
                    'start_time' => substr($appointment->start_time, 0, 5),
                    'end_time' => substr($appointment->end_time, 0, 5),
                ],
            ], 201);
        });
    }

    /**
     * Confirm booking with Nequi receipt upload or gateway selection.
     */
    public function book(Request $request): JsonResponse
    {
        // Normalize payload parameters for maximum client compatibility
        $inputs = $request->all();
        if (isset($inputs['booking_date']) && ! isset($inputs['date'])) {
            $inputs['date'] = $inputs['booking_date'];
        }
        if (isset($inputs['start_time'])) {
            $inputs['start_time'] = substr($inputs['start_time'], 0, 5);
        }
        if (isset($inputs['payment_method'])) {
            if ($inputs['payment_method'] === 'NEQUI') {
                $inputs['payment_method'] = 'NEQUI_TRANSFER';
            } elseif ($inputs['payment_method'] === 'BOLD') {
                $inputs['payment_method'] = 'BOLD_ONLINE';
            }
        }
        $request->merge($inputs);

        $validated = $request->validate([
            'appointment_id' => 'nullable|integer|exists:appointments,id',
            'service_id' => 'required_without:appointment_id|integer|exists:services,id',
            'date' => 'required_without:appointment_id|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required_without:appointment_id|date_format:H:i',
            'client_name' => 'required|string|max:255',
            'client_phone' => 'required|string|max:20',
            'client_email' => 'nullable|email|max:255',
            'client_notes' => 'nullable|string|max:1000',
            'payment_method' => ['required', Rule::in(['NEQUI_TRANSFER', 'BOLD_ONLINE'])],
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp,pdf|max:10240', // Max 10MB
        ]);

        $tenant = app()->has('active_tenant') ? app('active_tenant') : \App\Models\Tenant::where('is_active', true)->first();
        if ($validated['payment_method'] === 'BOLD_ONLINE' && (! $tenant || ! $tenant->isBoldConfigured())) {
            return response()->json([
                'success' => false,
                'message' => 'El método de pago en línea (Bold) no está disponible temporalmente.',
            ], 422);
        }

        return DB::transaction(function () use ($request, $validated) {
            $appointment = null;

            if (! empty($validated['appointment_id'])) {
                $appointment = Appointment::lockForUpdate()->find($validated['appointment_id']);
            }

            if (! $appointment) {
                $service = Service::findOrFail($validated['service_id']);
                $date = Carbon::parse($validated['date']);
                $startTime = $validated['start_time'];

                // Verify slot
                $isAvailable = $this->availabilityService->isSlotAvailable($service, $date, $startTime);
                if (! $isAvailable) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El horario seleccionado ya no está disponible.',
                    ], 422);
                }

                $endTime = Carbon::parse($date->toDateString() . ' ' . $startTime)
                    ->addMinutes($service->duration_minutes)
                    ->format('H:i:s');

                if (Appointment::hasConflict($date, $startTime, $endTime)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El horario seleccionado ya se encuentra ocupado por otra cita.',
                    ], 422);
                }

                $appointment = new Appointment([
                    'service_id' => $service->id,
                    'client_name' => $validated['client_name'],
                    'client_phone' => $validated['client_phone'],
                    'client_email' => $validated['client_email'] ?? null,
                    'appointment_date' => $date->toDateString(),
                    'start_time' => $startTime . ':00',
                    'end_time' => $endTime,
                    'total_amount' => $service->base_price,
                    'deposit_amount' => $service->deposit_amount,
                    'deposit_paid' => 0,
                    'balance_due' => $service->base_price,
                ]);
            } else {
                if (Appointment::hasConflict($appointment->appointment_date, $appointment->start_time, $appointment->end_time, $appointment->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El horario seleccionado ya no se encuentra disponible o fue ocupado por otra persona.',
                    ], 422);
                }
            }

            $appointment->client_name = $validated['client_name'];
            $appointment->client_phone = $validated['client_phone'];
            $appointment->client_email = $validated['client_email'] ?? $appointment->client_email;
            $appointment->client_notes = $validated['client_notes'] ?? $appointment->client_notes;
            $appointment->payment_method = PaymentMethod::from($validated['payment_method']);

            // If Nequi receipt is uploaded
            if ($request->hasFile('receipt')) {
                $path = $request->file('receipt')->store('receipts', 'public');
                $appointment->deposit_proof_image = $path;
                $appointment->status = AppointmentStatus::PENDING_VERIFICATION;
            } elseif ($validated['payment_method'] === 'NEQUI_TRANSFER') {
                $appointment->status = AppointmentStatus::PENDING_VERIFICATION;
            } else {
                $appointment->status = AppointmentStatus::PENDING_DEPOSIT;
            }

            $appointment->save();

            return response()->json([
                'success' => true,
                'message' => $appointment->status === AppointmentStatus::PENDING_VERIFICATION
                    ? '¡Tu cita ha sido apartada con éxito! Paola validará tu transferencia y te confirmará en breve.'
                    : 'Cita registrada. Procede con el pago en línea.',
                'data' => [
                    'appointment_number' => $appointment->appointment_number,
                    'status' => $appointment->status->value,
                    'status_label' => $appointment->status->label(),
                    'client_name' => $appointment->client_name,
                    'service_name' => $appointment->service->name,
                    'date' => $appointment->appointment_date->toDateString(),
                    'booking_date' => $appointment->appointment_date->toDateString(),
                    'start_time' => substr($appointment->start_time, 0, 5),
                    'end_time' => substr($appointment->end_time, 0, 5),
                    'total_amount' => (float) $appointment->total_amount,
                    'deposit_amount' => (float) $appointment->deposit_amount,
                    'balance_due' => (float) $appointment->balance_due,
                    'receipt_url' => $appointment->deposit_proof_image ? asset('storage/' . $appointment->deposit_proof_image) : null,
                ],
            ], 200);
        });
    }

    /**
     * Check appointment status by appointment number, ID, or client phone.
     */
    public function status(string $appointmentNumber): JsonResponse
    {
        $cleanSearch = trim($appointmentNumber);
        $cleanPhone = preg_replace('/\D/', '', $cleanSearch);

        $query = Appointment::with(['service', 'tenant']);

        if (strlen($cleanPhone) >= 7) {
            $query->where(function ($q) use ($cleanSearch, $cleanPhone) {
                $q->where('appointment_number', $cleanSearch)
                  ->orWhere('id', $cleanSearch)
                  ->orWhere('client_phone', 'LIKE', "%{$cleanPhone}%");
            });
        } else {
            $query->where(function ($q) use ($cleanSearch) {
                $q->where('appointment_number', $cleanSearch)
                  ->orWhere('id', $cleanSearch);
            });
        }

        $appointments = $query->orderBy('appointment_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(3)
            ->get();

        if ($appointments->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No encontramos ninguna cita con el código o número de celular ingresado.',
            ], 404);
        }

        $items = $appointments->map(function ($apt) {
            return [
                'id' => $apt->id,
                'appointment_number' => $apt->appointment_number,
                'status' => $apt->status->value,
                'status_label' => $apt->status->label(),
                'service_name' => $apt->service->name,
                'service' => [
                    'name' => $apt->service->name,
                    'duration_minutes' => $apt->service->duration_minutes,
                ],
                'client_name' => $apt->client_name,
                'client_phone' => $apt->client_phone,
                'date' => $apt->appointment_date->toDateString(),
                'booking_date' => $apt->appointment_date->toDateString(),
                'start_time' => substr($apt->start_time, 0, 5),
                'end_time' => substr($apt->end_time, 0, 5),
                'total_amount' => (float) $apt->total_amount,
                'deposit_amount' => (float) $apt->deposit_amount,
                'deposit_paid' => (float) $apt->deposit_paid,
                'balance_due' => (float) $apt->balance_due,
                'verified_at' => $apt->verified_at?->toIso8601String(),
                'attendance_confirmed_at' => $apt->attendance_confirmed_at?->toIso8601String(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'count' => count($items),
            'data' => $items[0],
            'appointments' => $items,
        ]);
    }

    /**
     * Confirm attendance for an appointment via API.
     */
    public function confirmAttendanceApi(string $appointmentNumber): JsonResponse
    {
        $appointment = Appointment::where('appointment_number', trim($appointmentNumber))->first();

        if (! $appointment) {
            return response()->json([
                'success' => false,
                'message' => 'Cita no encontrada.',
            ], 404);
        }

        $appointment->update([
            'attendance_confirmed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => '¡Asistencia confirmada exitosamente! Te esperamos el día de tu cita.',
            'data' => [
                'appointment_number' => $appointment->appointment_number,
                'attendance_confirmed_at' => $appointment->attendance_confirmed_at->toIso8601String(),
            ],
        ]);
    }
}