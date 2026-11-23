<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class SuperAdminAnalyticsWidget extends Widget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected string $view = 'filament.super-admin.widgets.super-admin-analytics-widget';

    public function getViewData(): array
    {
        // 1. Métricas Globales de Plataforma
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        
        $totalAppointments = Appointment::withoutGlobalScopes()->count();
        $confirmedAppointments = Appointment::withoutGlobalScopes()->where('status', 'confirmed')->count();
        $totalDeposits = Appointment::withoutGlobalScopes()->where('status', 'confirmed')->sum('deposit_paid');
        $totalServices = Service::withoutGlobalScopes()->count();
        $totalUsers = User::count();

        // 2. Directorio de Salones con recuento de citas
        $tenants = Tenant::withCount([
            'appointments' => function ($q) {
                $q->withoutGlobalScopes();
            }
        ])->latest()->take(4)->get();

        // 3. Estado de Infraestructura & Conectividad
        $dbConnected = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbConnected = false;
        }

        $activeGateway = config('whatsapp.driver', 'meta_cloud');
        $serverTime = Carbon::now()->isoFormat('D [de] MMMM Y, h:mm A');

        // 4. Últimas citas gestionadas en cualquier salón
        $recentAppointments = Appointment::withoutGlobalScopes()
            ->with(['service', 'tenant'])
            ->latest()
            ->take(6)
            ->get();

        return [
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'totalAppointments' => $totalAppointments,
            'confirmedAppointments' => $confirmedAppointments,
            'totalDeposits' => $totalDeposits,
            'formattedDeposits' => '$' . number_format($totalDeposits, 0, ',', '.') . ' COP',
            'totalServices' => $totalServices,
            'totalUsers' => $totalUsers,
            'tenants' => $tenants,
            'dbConnected' => $dbConnected,
            'activeGateway' => $activeGateway,
            'serverTime' => $serverTime,
            'recentAppointments' => $recentAppointments,
        ];
    }
}
