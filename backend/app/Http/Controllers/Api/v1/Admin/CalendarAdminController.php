<?php

namespace App\Http\Controllers\Api\v1\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarAdminController extends Controller
{
    public function __construct(
        protected BookingAvailabilityService $availabilityService
    ) {}

    /**
     * Obtener horarios disponibles para agendamiento administrativo divididos por jornada.
     */
    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
            'strict_anti_gaps' => 'nullable|boolean',
            'duration_minutes' => 'nullable|integer|min:15',
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $date = Carbon::parse($validated['date']);
        $strict = $request->boolean('strict_anti_gaps', false); // Por defecto flexible para administración
        $customDuration = $request->filled('duration_minutes') ? (int) $validated['duration_minutes'] : null;

        $rawSlots = $this->availabilityService->getAvailableSlots($service, $date, $strict, $customDuration);

        $morningSlots = [];
        $afternoonSlots = [];
        $allSlots = [];

        foreach ($rawSlots as $slot) {
            $durMinutes = $slot['duration_minutes'];
            if ($durMinutes >= 60) {
                $hours = floor($durMinutes / 60);
                $mins = $durMinutes % 60;
                $durText = $mins > 0 ? "{$hours}h {$mins}min" : "{$hours}h";
            } else {
                $durText = "{$durMinutes} min";
            }

            $formattedSlot = [
                'start_time' => $slot['start_time'],
                'end_time' => $slot['end_time'],
                'start_formatted' => $slot['start_formatted'],
                'end_formatted' => $slot['end_formatted'],
                'duration_minutes' => $slot['duration_minutes'],
                'label' => "{$slot['start_formatted']} – {$slot['end_formatted']} ({$durText})",
                'is_recommended' => $slot['is_recommended'] ?? false,
            ];

            $allSlots[] = $formattedSlot;

            if ($slot['start_time'] < '12:00') {
                $morningSlots[] = $formattedSlot;
            } else {
                $afternoonSlots[] = $formattedSlot;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'base_price' => (float) $service->base_price,
                    'deposit_amount' => (float) $service->deposit_amount,
                ],
                'date' => $date->toDateString(),
                'day_name' => $date->translatedFormat('l, d F Y'),
                'is_available' => count($allSlots) > 0,
                'total_slots' => count($allSlots),
                'groups' => [
                    'morning' => [
                        'label' => 'Mañana (8:00 AM – 12:00 PM)',
                        'count' => count($morningSlots),
                        'slots' => $morningSlots,
                    ],
                    'afternoon' => [
                        'label' => 'Tarde (12:00 PM – 6:00 PM)',
                        'count' => count($afternoonSlots),
                        'slots' => $afternoonSlots,
                    ],
                ],
                'slots' => $allSlots,
            ],
        ]);
    }

    /**
     * Resumen diario / KPIs de agenda y Spotlight de la cita en curso.
     */
    public function summary(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date_format:Y-m-d',
        ]);

        $date = ! empty($validated['date']) ? Carbon::parse($validated['date']) : Carbon::today();

        $appointments = Appointment::query()
            ->with(['service:id,name,duration_minutes,base_price', 'customer:id,name,phone,email,is_legacy'])
            ->whereDate('appointment_date', $date->toDateString())
            ->orderBy('start_time', 'asc')
            ->get();

        $activeAppointments = $appointments->filter(fn ($apt) => $apt->status !== AppointmentStatus::CANCELLED);

        $kpis = [
            'total_appointments' => $appointments->count(),
            'confirmed_count' => $appointments->where('status', AppointmentStatus::CONFIRMED)->count(),
            'pending_verification_count' => $appointments->where('status', AppointmentStatus::PENDING_VERIFICATION)->count(),
            'in_progress_count' => $appointments->where('status', AppointmentStatus::IN_PROGRESS)->count(),
            'completed_count' => $appointments->where('status', AppointmentStatus::COMPLETED)->count(),
            'cancelled_count' => $appointments->where('status', AppointmentStatus::CANCELLED)->count(),
            'financials' => [
                'expected_revenue' => (float) $activeAppointments->sum('total_amount'),
                'collected_deposits' => (float) $activeAppointments->sum('deposit_paid'),
                'pending_balance' => (float) $activeAppointments->sum('balance_due'),
            ],
        ];

        // Spotlight: Cita en atención actual o la próxima cita confirmada del día
        $nowTime = Carbon::now()->format('H:i:s');
        $spotlight = $appointments->firstWhere('status', AppointmentStatus::IN_PROGRESS);

        if (! $spotlight) {
            $spotlight = $appointments
                ->where('status', AppointmentStatus::CONFIRMED)
                ->first(fn ($apt) => $apt->start_time >= $nowTime);
        }

        if (! $spotlight) {
            $spotlight = $appointments->first();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $date->toDateString(),
                'date_formatted' => $date->translatedFormat('l, d \d\e F \d\e Y'),
                'kpis' => $kpis,
                'spotlight' => $spotlight ? [
                    'id' => $spotlight->id,
                    'appointment_number' => $spotlight->appointment_number,
                    'client_name' => $spotlight->client_name,
                    'client_phone' => $spotlight->customer?->getDisplayPhone() ?? $spotlight->client_phone,
                    'whatsapp_url' => $spotlight->customer?->getWhatsAppUrl(),
                    'service_name' => $spotlight->service?->name,
                    'start_time' => substr($spotlight->start_time, 0, 5),
                    'end_time' => substr($spotlight->end_time, 0, 5),
                    'status' => $spotlight->status?->value,
                    'status_label' => $spotlight->status?->label(),
                    'status_color' => $spotlight->status?->color(),
                    'loyalty_badge' => $spotlight->customer?->getLoyaltyBadge(),
                ] : null,
                'appointments_count' => $appointments->count(),
            ],
        ]);
    }
}
