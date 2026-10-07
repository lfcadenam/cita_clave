<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use App\Models\WorkingSchedule;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class SalonSpotlightWidget extends Widget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full';
    protected string $view = 'filament.widgets.salon-spotlight-widget';

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $user = auth()->user();
        $today = Carbon::today();
        $dayOfWeek = $today->dayOfWeek;

        $schedule = WorkingSchedule::where('tenant_id', $tenant?->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        $nextAppointment = Appointment::where('tenant_id', $tenant?->id)
            ->whereDate('appointment_date', $today)
            ->where('start_time', '>=', Carbon::now()->format('H:i:s'))
            ->whereIn('status', [\App\Enums\AppointmentStatus::CONFIRMED, \App\Enums\AppointmentStatus::IN_PROGRESS, \App\Enums\AppointmentStatus::PENDING_VERIFICATION])
            ->orderBy('start_time')
            ->first();

        if (!$nextAppointment) {
            $nextAppointment = Appointment::where('tenant_id', $tenant?->id)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', [\App\Enums\AppointmentStatus::CONFIRMED, \App\Enums\AppointmentStatus::IN_PROGRESS, \App\Enums\AppointmentStatus::PENDING_VERIFICATION])
                ->orderBy('start_time')
                ->first();
        }

        return [
            'tenant' => $tenant,
            'user' => $user,
            'schedule' => $schedule,
            'nextAppointment' => $nextAppointment,
            'isAvailableToday' => $schedule ? (bool) $schedule->is_working_day : true,
        ];
    }
}
