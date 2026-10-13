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
        $rawBlockedSlots = BlockedSlot::query()
            ->where(function ($query) use ($targetDate) {
                $query->where('is_recurring', true)
                    ->orWhereDate('blocked_date', $targetDate);
            })
            ->get();

        $hasSpecificLunch = $rawBlockedSlots->contains(function ($b) use ($targetDate) {
            return ! $b->is_recurring &&
                   $b->blocked_date &&
                   Carbon::parse($b->blocked_date)->isSameDay($targetDate) &&
                   (str_contains(strtolower($b->title ?? ''), 'almuerzo') || str_contains(strtolower($b->reason ?? ''), 'almuerzo'));
        });

        $busyIntervals = [];
        $recurringLunchInterval = null;

        foreach ($rawBlockedSlots as $block) {
            $isLunch = (str_contains(strtolower($block->title ?? ''), 'almuerzo') || str_contains(strtolower($block->reason ?? ''), 'almuerzo'));

            // If a specific date lunch exists, skip recurring lunch for this date
            if ($block->is_recurring && $isLunch && $hasSpecificLunch) {
                continue;
            }

            $start = Carbon::parse($targetDate->toDateString() . ' ' . $block->start_time);
            $end = Carbon::parse($targetDate->toDateString() . ' ' . $block->end_time);

            if ($block->is_recurring && $isLunch) {
                $recurringLunchInterval = [
                    'start' => $start->copy(),
                    'end' => $end->copy(),
                    'duration' => (int) $start->diffInMinutes($end) ?: 60,
                ];
            }

            if ($end->gt($openDateTime) && $start->lt($closeDateTime)) {
                $busyIntervals[] = [
                    'start' => $start->max($openDateTime),
                    'end' => $end->min($closeDateTime),
                    'type' => 'blocked',
                    'title' => $block->title,
                    'is_lunch' => $isLunch,
                ];
            }
        }

        // 2. Gather busy intervals: Active Appointments (Confirmed, Pending Verification, In Progress)
        $appointments = Appointment::query()
            ->whereDate('appointment_date', $targetDate)
            ->where(function ($query) {
                $query->whereIn('status', [
                    AppointmentStatus::CONFIRMED->value,
                    AppointmentStatus::PENDING_VERIFICATION->value,
                    AppointmentStatus::IN_PROGRESS->value,
                    AppointmentStatus::COMPLETED->value,
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
                    'is_lunch' => false,
                ];
            }
        }

        $serviceDuration = (int) $service->duration_minutes;
        $stepMinutes = $schedule->slot_interval_minutes ?: 15;

        // 3. Generate candidate slots evaluating free windows and flexible lunch shift
        return $this->buildSlots(
            $busyIntervals,
            $recurringLunchInterval,
            $hasSpecificLunch,
            $openDateTime,
            $closeDateTime,
            $serviceDuration,
            $stepMinutes,
            $strictAntiGaps
        );
    }

    /**
     * Build candidate slots evaluating free windows and flexible lunch shift.
     */
    protected function buildSlots(
        array $busyIntervals,
        ?array $recurringLunchInterval,
        bool $hasSpecificLunch,
        Carbon $openDateTime,
        Carbon $closeDateTime,
        int $serviceDuration,
        int $stepMinutes,
        bool $strictAntiGaps
    ): array {
        // If lunch is already fixed by a specific block or no recurring lunch defined
        if ($hasSpecificLunch || ! $recurringLunchInterval) {
            $merged = $this->mergeIntervals($busyIntervals);
            $windows = $this->calculateFreeWindows($merged, $openDateTime, $closeDateTime);
            return $this->generateSlotsFromWindows($windows, $serviceDuration, $stepMinutes, $strictAntiGaps);
        }

        // Separate lunch block from other busy intervals (appointments & hard blocks)
        $nonLunchBusy = array_values(array_filter($busyIntervals, fn ($i) => empty($i['is_lunch'])));

        // Standard windows with fixed lunch (13:00 - 14:00)
        $standardBusy = $this->mergeIntervals($busyIntervals);
        $standardWindows = $this->calculateFreeWindows($standardBusy, $openDateTime, $closeDateTime);
        $standardSlots = $this->generateSlotsFromWindows($standardWindows, $serviceDuration, $stepMinutes, $strictAntiGaps);

        // Generate flexible lunch candidate slots:
        // Slots that start before lunch and extend into lunch (e.g. 12:00 to 13:30)
        $flexibleSlots = [];
        $lunchStart = $recurringLunchInterval['start'];
        $lunchDuration = $recurringLunchInterval['duration'];

        $earliestBridge = $lunchStart->copy()->subMinutes($serviceDuration);
        if ($earliestBridge->lt($openDateTime)) {
            $earliestBridge = $openDateTime->copy();
        }

        $candidateStarts = [];
        if ($strictAntiGaps) {
            foreach ($standardWindows as $w) {
                if ($w['end']->eq($lunchStart)) {
                    $cursor = $w['start']->copy();
                    while ($cursor->lt($lunchStart)) {
                        if ($cursor->copy()->addMinutes($serviceDuration)->gt($lunchStart)) {
                            $candidateStarts[] = $cursor->copy();
                        }
                        $cursor->addMinutes($serviceDuration);
                    }
                }
            }
        }

        // Step intervals between earliestBridge and lunchStart
        $cursor = $earliestBridge->copy();
        while ($cursor->lt($lunchStart)) {
            if ($cursor->copy()->addMinutes($serviceDuration)->gt($lunchStart)) {
                $candidateStarts[] = $cursor->copy();
            }
            $cursor->addMinutes($stepMinutes);
        }

        $uniqueStarts = [];
        foreach ($candidateStarts as $cs) {
            $key = $cs->format('H:i');
            $uniqueStarts[$key] = $cs;
        }

        foreach ($uniqueStarts as $candStart) {
            $candEnd = $candStart->copy()->addMinutes($serviceDuration);

            if ($candStart->lt($openDateTime)) {
                continue;
            }

            if ($candEnd->lte($lunchStart)) {
                continue;
            }

            $shiftedLunchStart = $candEnd->copy();
            $shiftedLunchEnd = $shiftedLunchStart->copy()->addMinutes($lunchDuration);

            // Shifted lunch cannot start after 15:00
            if ($shiftedLunchStart->hour >= 15 && $shiftedLunchStart->minute > 0) {
                continue;
            }

            // Shifted lunch must finish before salon closing
            if ($shiftedLunchEnd->gt($closeDateTime)) {
                continue;
            }

            // Check collision of [candStart, candEnd] with any non-lunch busy interval
            $collidesAppointment = false;
            foreach ($nonLunchBusy as $busy) {
                if ($candStart->lt($busy['end']) && $candEnd->gt($busy['start'])) {
                    $collidesAppointment = true;
                    break;
                }
            }
            if ($collidesAppointment) {
                continue;
            }

            // Check collision of shifted lunch [shiftedLunchStart, shiftedLunchEnd] with any appointment
            $collidesShiftedLunch = false;
            foreach ($nonLunchBusy as $busy) {
                if ($shiftedLunchStart->lt($busy['end']) && $shiftedLunchEnd->gt($busy['start'])) {
                    $collidesShiftedLunch = true;
                    break;
                }
            }
            if ($collidesShiftedLunch) {
                continue;
            }

            $flexibleSlots[] = [
                'start_time' => $candStart->format('H:i'),
                'end_time' => $candEnd->format('H:i'),
                'start_formatted' => $candStart->format('h:i A'),
                'end_formatted' => $candEnd->format('h:i A'),
                'duration_minutes' => $serviceDuration,
                'is_recommended' => true,
                'gap_after_minutes' => 0,
            ];
        }

        $allSlots = array_merge($standardSlots, $flexibleSlots);
        usort($allSlots, fn ($a, $b) => strcmp($a['start_time'], $b['start_time']));

        $result = [];
        $seen = [];
        foreach ($allSlots as $slot) {
            if (! isset($seen[$slot['start_time']])) {
                $seen[$slot['start_time']] = true;
                $result[] = $slot;
            }
        }

        return $result;
    }

    /**
     * Generate slots from free windows.
     */
    protected function generateSlotsFromWindows(array $freeWindows, int $serviceDuration, int $stepMinutes, bool $strictAntiGaps): array
    {
        $validSlots = [];

        foreach ($freeWindows as $window) {
            $winStart = $window['start'];
            $winEnd = $window['end'];
            $windowDuration = (int) $winStart->diffInMinutes($winEnd);

            if ($windowDuration < $serviceDuration) {
                continue;
            }

            if ($strictAntiGaps) {
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

                    $currentSlotStart->addMinutes($serviceDuration);
                }
            } else {
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

    /**
     * Get default recurring lunch slot for a tenant or globally.
     */
    public function getDefaultLunchSlot(?int $tenantId = null): ?BlockedSlot
    {
        $query = BlockedSlot::where('is_recurring', true)
            ->where(function ($q) {
                $q->where('title', 'LIKE', '%Almuerzo%')
                    ->orWhere('reason', 'LIKE', '%almuerzo%')
                    ->orWhere(function ($sub) {
                        $sub->where('start_time', '>=', '12:00:00')
                            ->where('start_time', '<=', '14:00:00');
                    });
            });

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->first();
    }

    /**
     * Adjust lunch slot for an appointment if it overlaps with the normal lunch window.
     * Moves lunch to start immediately after the appointment ends for that day only.
     */
    public function adjustLunchForAppointment(Appointment $appointment): ?BlockedSlot
    {
        if (in_array($appointment->status, [AppointmentStatus::CANCELLED, AppointmentStatus::NO_SHOW])) {
            $this->recalculateLunchForDate($appointment->appointment_date, $appointment->tenant_id);
            return null;
        }

        $lunch = $this->getDefaultLunchSlot($appointment->tenant_id);
        if (! $lunch) {
            return null;
        }

        $apptStart = substr($appointment->start_time, 0, 5);
        $apptEnd = substr($appointment->end_time, 0, 5);
        $lunchStart = substr($lunch->start_time, 0, 5);
        $lunchEnd = substr($lunch->end_time, 0, 5);

        // Does the appointment start before lunch end and end after lunch start?
        $overlapsLunch = ($apptStart < $lunchEnd && $apptEnd > $lunchStart);

        if (! $overlapsLunch) {
            return null;
        }

        $lunchDuration = (int) Carbon::parse($lunch->start_time)->diffInMinutes(Carbon::parse($lunch->end_time));
        if ($lunchDuration <= 0) {
            $lunchDuration = 60;
        }

        $dateStr = $appointment->appointment_date instanceof Carbon
            ? $appointment->appointment_date->toDateString()
            : Carbon::parse($appointment->appointment_date)->toDateString();

        $newStart = $apptEnd . (strlen($apptEnd) === 5 ? ':00' : '');
        $newEnd = Carbon::parse($dateStr . ' ' . $apptEnd)->addMinutes($lunchDuration)->format('H:i:s');

        return BlockedSlot::updateOrCreate(
            [
                'tenant_id' => $appointment->tenant_id,
                'blocked_date' => $dateStr,
                'is_recurring' => false,
                'title' => 'Almuerzo & Descanso (Flexible)',
            ],
            [
                'start_time' => $newStart,
                'end_time' => $newEnd,
                'reason' => "Almuerzo reprogramado automáticamente de {$newStart} a {$newEnd} por cita #{$appointment->appointment_number}",
            ]
        );
    }

    /**
     * Recalculate or restore lunch when an appointment is updated, cancelled or deleted.
     */
    public function recalculateLunchForDate(Carbon|string $date, ?int $tenantId = null): void
    {
        $dateStr = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();
        $lunch = $this->getDefaultLunchSlot($tenantId);

        if (! $lunch) {
            return;
        }

        $lunchStart = substr($lunch->start_time, 0, 5);
        $lunchEnd = substr($lunch->end_time, 0, 5);
        $lunchDuration = (int) Carbon::parse($lunch->start_time)->diffInMinutes(Carbon::parse($lunch->end_time)) ?: 60;

        // Check remaining active appointments on this date overlapping lunch
        $overlappingApt = Appointment::query()
            ->whereDate('appointment_date', $dateStr)
            ->whereNotIn('status', [AppointmentStatus::CANCELLED->value, AppointmentStatus::NO_SHOW->value])
            ->where('start_time', '<', $lunchEnd)
            ->where('end_time', '>', $lunchStart)
            ->orderBy('end_time', 'desc')
            ->first();

        if ($overlappingApt) {
            $apptEnd = substr($overlappingApt->end_time, 0, 5);
            $newStart = $apptEnd . (strlen($apptEnd) === 5 ? ':00' : '');
            $newEnd = Carbon::parse($dateStr . ' ' . $apptEnd)->addMinutes($lunchDuration)->format('H:i:s');

            BlockedSlot::updateOrCreate(
                [
                    'tenant_id' => $tenantId ?? $overlappingApt->tenant_id,
                    'blocked_date' => $dateStr,
                    'is_recurring' => false,
                    'title' => 'Almuerzo & Descanso (Flexible)',
                ],
                [
                    'start_time' => $newStart,
                    'end_time' => $newEnd,
                    'reason' => "Almuerzo reprogramado automáticamente de {$newStart} a {$newEnd} por cita #{$overlappingApt->appointment_number}",
                ]
            );
        } else {
            // Revert back: remove specific flexible lunch block
            BlockedSlot::whereDate('blocked_date', $dateStr)
                ->where('is_recurring', false)
                ->where('title', 'Almuerzo & Descanso (Flexible)')
                ->delete();
        }
    }
}