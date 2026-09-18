<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Services\BookingAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(
        protected BookingAvailabilityService $availabilityService
    ) {}

    /**
     * Get available appointment slots for a service on a given date.
     */
    public function slots(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'date' => 'required|date_format:Y-m-d',
            'strict_anti_gaps' => 'nullable|boolean',
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $date = Carbon::parse($validated['date']);
        $strict = $request->boolean('strict_anti_gaps', true);

        $slots = $this->availabilityService->getAvailableSlots($service, $date, $strict);

        return response()->json([
            'success' => true,
            'data' => [
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'duration_minutes' => $service->duration_minutes,
                    'deposit_amount' => $service->deposit_amount,
                ],
                'date' => $date->toDateString(),
                'day_name' => $date->translatedFormat('l'),
                'is_available' => count($slots) > 0,
                'total_slots' => count($slots),
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * Get calendar month overview showing which days have available slots.
     */
    public function month(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service_id' => 'required|integer|exists:services,id',
            'year' => 'nullable|integer|min:2026|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $service = Service::findOrFail($validated['service_id']);
        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);

        $calendar = $this->availabilityService->getMonthAvailability($service, $year, $month);

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'calendar' => $calendar,
            ],
        ]);
    }
}