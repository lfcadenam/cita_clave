<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\Waitlist;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();

        $todayAppointments = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->whereIn('status', [AppointmentStatus::CONFIRMED->value, AppointmentStatus::IN_PROGRESS->value])
            ->count();

        $pendingNequiCount = Appointment::query()
            ->where('status', AppointmentStatus::PENDING_VERIFICATION->value)
            ->where('payment_method', PaymentMethod::NEQUI_TRANSFER->value)
            ->count();

        $totalRevenueDeposits = Appointment::query()
            ->where('deposit_paid', '>', 0)
            ->sum('deposit_paid');

        $activeWaitlistCount = Waitlist::query()
            ->where('status', 'waiting')
            ->count();

        return [
            Stat::make('Citas para Hoy', $todayAppointments)
                ->description('Agendadas para el día de hoy')
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Nequi por Aprobar', $pendingNequiCount)
                ->description($pendingNequiCount > 0 ? 'Comprobantes pendientes de validación' : 'Al día, sin transferencias pendientes')
                ->icon('heroicon-o-camera')
                ->color($pendingNequiCount > 0 ? 'warning' : 'success'),

            Stat::make('Anticipos Recaudados', '$ ' . number_format($totalRevenueDeposits, 0, ',', '.') . ' COP')
                ->description('Total depósitos confirmados')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Lista de Espera Activa', $activeWaitlistCount)
                ->description('Clientas esperando cupo disponible')
                ->icon('heroicon-o-user-group')
                ->color('info'),
        ];
    }
}