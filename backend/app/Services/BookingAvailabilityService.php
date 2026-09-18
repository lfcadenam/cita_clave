<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Models\Service;
use App\Models\WorkingSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class BookingAvailabilityService
{
    /**
     * Minimum duration (in minutes) of any bookable service.
     * Any free gap smaller than this is considered a dead time / orphan gap.
     */
    protected const MIN_VIABLE_SLOT_MINUTES = 30;

    /**
     * Get available appointment slots for a service on a given date.
     *
     * @param Service $service
     * @param Carbon|string $date
     * @param bool $strictAntiGaps
     * @return array
     */
    public function getAvailableSlots(Service $service, Carbon|string $date, bool $strictAntiGaps = true): array
    {
        $targetDate = is_string($date) ? Carbon::parse($date)->startOfDay() : $date->copy()->startOfDay();

        // Past dates cannot be booked
        if ($targetDate->lt(Carbon::today())) {
            return [];
        }

        $dayOfWeek = $targetDate->dayOfWeek; // 0 = Sunday, 1 = Monday, ..., 6 = Saturday
        $schedule = WorkingSchedule::where('day_of_week', $dayOfWeek)->first();

        // If no schedule or closed day, return empty
        if (! $schedule || ! $schedule->is_working_day) {
            return [];
        }

        $openDateTime = Carbon::parse($targetDate->toDateString() . ' ' . $schedule->open_time);
        $closeDateTime = Carbon::parse($targetDate->toDateString() . ' ' . $schedule->close_time);

        // If today, cannot book slots in the past (minimum 30 minutes ahead)
        $now = Carbon::now();
        if ($targetDate->isToday() && $now->gt($openDateTime)) {
            $minBookingTime = $now->copy()->addMinutes(30)->ceilMinutes(15);
            if ($minBookingTime->gte($closeDateTime)) {
                return [];
            }
            if ($minBookingTime->gt($openDateTime)) {
                $openDateTime = $minBookingTime;
            }
        }

        // 1. Gather busy intervals: Blocked Slots (specific date + recurring)
        $busyIntervals = [];

        $blockedSlots = BlockedSlot::query()
            ->where(function ($query) use ($targetDate) {
                $query->where('is_recurring', true)
                    ->orWhereDate('blocked_date', $targetDate);
            })
            ->get();

        foreach ($blockedSlots as $block) {
            $start = Carbon::parse($targetDate->toDateString() . ' ' . $block->start_time);
            $end = Carbon::parse($targetDate->toDateString() . ' ' . $block->end_time);
            if ($end->gt($openDateTime) && $start->lt($closeDateTime)) {
                $busyIntervals[] = [
                    'start' => $start->max($openDateTime),
                    'end' => $end->min($closeDateTime),
                    'type' => 'blocked',
                    'title' => $block->title,
                ];
            }
        }

        // 2. Gather busy intervals: Active Appointments (Confirmed, Pending Verification, In Progress)
        // Also hold appointments booked in the last 15 minutes that are pending deposit
        $appointments = Appointment::query()
            ->whereDate('appointment_date', $targetDate)
            ->where(function ($query) {
                $query->whereIn('status', [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::PENDING_VERIFICATION->value,
                    AppointmentStatus::IN_PROGRESS->value,
                ])
                ->orWhere(function ($q) {
                    $q->where('status', AppointmentStatus::PENDING_DEPOSIT->value)
                        ->where('created_at', '>=', Carbon::now()->subMinutes(15));
                });
            })
            ->get();

        foreach ($appointments as $apt) {
            $start = Carbon::parse($targetDate->toDateString() . ' ' . $apt->start_time);
            $end = Carbon::parse($targetDate->toDateString() . ' ' . $apt->end_time);
            if ($end->gt($openDateTime) && $start->lt($closeDateTime)) {
                $busyIntervals[] = [
                    'start' => $start->max($openDateTime),
                    'end' => $end->min($closeDateTime),
                    'type' => 'appointment',
                    'title' => 'Cita: ' . $apt->client_name,
                ];
            }
        }

        // 3. Sort and merge overlapping busy intervals
        $mergedBusy = $this->mergeIntervals($busyIntervals);

        // 4. Calculate continuous free windows throughout the day
        $freeWindows = $this->calculateFreeWindows($mergedBusy, $openDateTime, $closeDateTime);

        // 5. Generate candidate slots that fit the service duration
        $serviceDuration = (int) $service->duration_minutes;
        $stepMinutes = $schedule->slot_interval_minutes ?: 15;
        $validSlots = [];

        foreach ($freeWindows as $window) {
            $winStart = $window['start'];
            $winEnd = $window['end'];
            $windowDuration = (int) $winStart->diffInMinutes($winEnd);

            if ($windowDuration < $serviceDuration) {
                continue; // Cannot fit service in this window
            }

            if ($strictAntiGaps) {
                // Modo Anti-Huecos Estricto:
                // Empaqueta los turnos de manera inmediatamente consecutiva a partir de $winStart
                // (después de la última cita agendada, apertura del local o reinicio de la tarde)
                $currentSlotStart = $winStart->copy();

                while ($currentSlotStart->copy()->addMinutes($serviceDuration)->lte($winEnd)) {
                    $currentSlotEnd = $currentSlotStart->copy()->addMinutes($serviceDuration);
                    $gapBefore = (int) $winStart->diffInMinutes($currentSlotStart);
                    $gapAfter = (int) $currentSlotEnd->diffInMinutes($winEnd);

                    $validSlots[] = [
                        'start_time' => $currentSlotStart->format('H:i'),
                        'end_time' => $currentSlotEnd->format('H:i'),
                        'start_formatted' => $currentSlotStart->format('h:i A'),
                        'end_formatted' => $currentSlotEnd->format('h:i A'),
                        'duration_minutes' => $serviceDuration,
                        'is_recommended' => ($gapBefore === 0),
                        'gap_after_minutes' => $gapAfter,
                    ];

                    // Avanza por la duración exacta del servicio para que los turnos queden 100% contiguos
                    $currentSlotStart->addMinutes($serviceDuration);
                }
            } else {
                // Modo Flexible / Manual:
                $currentSlotStart = $winStart->copy();

                while ($currentSlotStart->copy()->addMinutes($serviceDuration)->lte($winEnd)) {
                    $currentSlotEnd = $currentSlotStart->copy()->addMinutes($serviceDuration);
                    $gapBefore = (int) $winStart->diffInMinutes($currentSlotStart);
                    $gapAfter = (int) $currentSlotEnd->diffInMinutes($winEnd);

                    $validSlots[] = [
                        'start_time' => $currentSlotStart->format('H:i'),
                        'end_time' => $currentSlotEnd->format('H:i'),
                        'start_formatted' => $currentSlotStart->format('h:i A'),
                        'end_formatted' => $currentSlotEnd->format('h:i A'),
                        'duration_minutes' => $serviceDuration,
                        'is_recommended' => ($gapBefore === 0 || $gapAfter === 0),
                        'gap_after_minutes' => $gapAfter,
                    ];

                    $currentSlotStart->addMinutes($stepMinutes);
                }
            }
        }

        return $validSlots;
    }

    /**
     * Check availability for every day in a given month.
     * Returns an associative array of date string => has_availability (bool) and count.
     *
     * @param Service $service
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getMonthAvailability(Service $service, int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $daysInMonth = $startDate->daysInMonth;
        $result = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($year, $month, $day)->startOfDay();
            $dateString = $date->toDateString();

            if ($date->lt(Carbon::today())) {
                $result[$dateString] = [
                    'date' => $dateString,
                    'day_name' => $date->translatedFormat('l'),
                    'is_open' => false,
                    'is_available' => false,
                    'available_slots_count' => 0,
                ];
                continue;
            }

            $slots = $this->getAvailableSlots($service, $date, true);
            $count = count($slots);

            $result[$dateString] = [
                'date' => $dateString,
                'day_name' => $date->translatedFormat('l'),
                'is_open' => $count > 0,
                'is_available' => $count > 0,
                'available_slots_count' => $count,
            ];
        }

        return $result;
    }

    /**
     * Verify if a specific start time on a date is available for a service.
     *
     * @param Service $service
     * @param Carbon|string $date
     * @param string $startTime
     * @return bool
     */
    public function isSlotAvailable(Service $service, Carbon|string $date, string $startTime): bool
    {
        $slots = $this->getAvailableSlots($service, $date, false);
        $formattedStart = substr($startTime, 0, 5);

        foreach ($slots as $slot) {
            if (substr($slot['start_time'], 0, 5) === $formattedStart) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge overlapping or adjacent busy intervals.
     */
    protected function mergeIntervals(array $intervals): array
    {
        if (empty($intervals)) {
            return [];
        }

        usort($intervals, fn ($a, $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        $merged = [];
        $current = $intervals[0];

        for ($i = 1; $i < count($intervals); $i++) {
            $next = $intervals[$i];

            if ($next['start']->lte($current['end'])) {
                // Overlapping or touching
                if ($next['end']->gt($current['end'])) {
                    $current['end'] = $next['end'];
                }
            } else {
                $merged[] = $current;
                $current = $next;
            }
        }

        $merged[] = $current;
        return $merged;
    }

    /**
     * Calculate free windows between busy intervals.
     */
    protected function calculateFreeWindows(array $busyIntervals, Carbon $openDateTime, Carbon $closeDateTime): array
    {
        $freeWindows = [];
        $cursor = $openDateTime->copy();

        foreach ($busyIntervals as $busy) {
            $busyStart = $busy['start'];
            $busyEnd = $busy['end'];

            if ($busyStart->gt($cursor)) {
                $freeWindows[] = [
                    'start' => $cursor->copy(),
                    'end' => $busyStart->copy(),
                ];
            }

            if ($busyEnd->gt($cursor)) {
                $cursor = $busyEnd->copy();
            }
        }

        if ($cursor->lt($closeDateTime)) {
            $freeWindows[] = [
                'start' => $cursor->copy(),
                'end' => $closeDateTime->copy(),
            ];
        }

        return $freeWindows;
    }
}