<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $totalAppointments = Appointment::withoutGlobalScopes()->count();
        $totalDeposits = Appointment::withoutGlobalScopes()->where('status', 'confirmed')->sum('deposit_paid');
        $totalServices = Service::withoutGlobalScopes()->count();

        return [
            Stat::make('Salones & Empresas', "{$activeTenants} / {$totalTenants}")
                ->description('Estudios de Belleza Activos en Nuvex')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),

            Stat::make('Citas Globales Gestionadas', number_format($totalAppointments, 0, ',', '.'))
                ->description('Total histórico en toda la plataforma')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Volumen de Abonos Recibidos', '$' . number_format($totalDeposits, 0, ',', '.') . ' COP')
                ->description('Recaudado por pasarela y transferencias')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Servicios en Catálogos', $totalServices)
                ->description('Tratamientos activos en plataforma')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
        ];
    }
}