<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class SalonAnalyticsDashboardWidget extends Widget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';
    protected string $view = 'filament.widgets.salon-analytics-dashboard-widget';

    public string $period = 'month'; // 'week', 'month', 'quarter', 'semester'
    public int $offset = 0; // Para navegar entre períodos (< Anterior / Siguiente >)

    public function setPeriod(string $newPeriod): void
    {
        $this->period = $newPeriod;
        $this->offset = 0;
    }

    public function previousPeriod(): void
    {
        $this->offset--;
    }

    public function nextPeriod(): void
    {
        $this->offset++;
    }

    public function resetPeriod(): void
    {
        $this->offset = 0;
    }

    public function getViewData(): array
    {
        $tenant = Filament::getTenant();
        $tenantId = $tenant?->id;
        $now = Carbon::now();

        // 1. Determinar rango de fechas según período y offset
        switch ($this->period) {
            case 'week':
                $refDate = $now->copy()->addWeeks($this->offset);
                $start = $refDate->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
                $end = $refDate->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
                $prevStart = $start->copy()->subWeek();
                $prevEnd = $end->copy()->subWeek();
                $periodTitle = 'Semana del ' . $start->isoFormat('D [de] MMM') . ' al ' . $end->isoFormat('D [de] MMM Y');
                break;

            case 'quarter':
                $refDate = $now->copy()->addQuarters($this->offset);
                $start = $refDate->copy()->startOfQuarter()->startOfDay();
                $end = $refDate->copy()->endOfQuarter()->endOfDay();
                $prevStart = $start->copy()->subQuarter();
                $prevEnd = $end->copy()->subQuarter();
                $quarterNum = $start->quarter;
                $periodTitle = "Trimestre Q{$quarterNum} ({$start->isoFormat('MMM')} - {$end->isoFormat('MMM Y')})";
                break;

            case 'semester':
                $currentSem = $now->month <= 6 ? 1 : 2;
                $targetSem = $currentSem + $this->offset;
                $yearShift = intdiv($targetSem - 1, 2);
                $normalizedSem = (($targetSem - 1) % 2 + 2) % 2 + 1;
                $targetYear = $now->year + $yearShift;

                if ($normalizedSem === 1) {
                    $start = Carbon::create($targetYear, 1, 1)->startOfDay();
                    $end = Carbon::create($targetYear, 6, 30)->endOfDay();
                } else {
                    $start = Carbon::create($targetYear, 7, 1)->startOfDay();
                    $end = Carbon::create($targetYear, 12, 31)->endOfDay();
                }
                $prevStart = $start->copy()->subMonths(6);
                $prevEnd = $end->copy()->subMonths(6);
                $periodTitle = "Semestre {$normalizedSem} de {$targetYear}";
                break;

            case 'month':
            default:
                $refDate = $now->copy()->addMonths($this->offset);
                $start = $refDate->copy()->startOfMonth()->startOfDay();
                $end = $refDate->copy()->endOfMonth()->endOfDay();
                $prevStart = $start->copy()->subMonth()->startOfMonth();
                $prevEnd = $start->copy()->subMonth()->endOfMonth();
                $periodTitle = ucfirst($start->isoFormat('MMMM [de] YYYY'));
                break;
        }

        // 2. Consulta Base de Citas en el Período
        $baseQuery = Appointment::where('tenant_id', $tenantId)
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()]);

        $totalAppointments = (clone $baseQuery)->count();

        // Estados de citas
        $completedCount = (clone $baseQuery)->where('status', AppointmentStatus::COMPLETED->value)->count();
        $confirmedCount = (clone $baseQuery)->where('status', AppointmentStatus::CONFIRMED->value)->count();
        $inProgressCount = (clone $baseQuery)->where('status', AppointmentStatus::IN_PROGRESS->value)->count();
        $pendingVerificationCount = (clone $baseQuery)->where('status', AppointmentStatus::PENDING_VERIFICATION->value)->count();
        $pendingDepositCount = (clone $baseQuery)->where('status', AppointmentStatus::PENDING_DEPOSIT->value)->count();
        $cancelledCount = (clone $baseQuery)->where('status', AppointmentStatus::CANCELLED->value)->count();
        $noShowCount = (clone $baseQuery)->where('status', AppointmentStatus::NO_SHOW->value)->count();

        $effectiveTotalCount = $completedCount + $confirmedCount + $inProgressCount;

        // Citas con valor comercial proyectado/cobrado
        $validAppointmentsQuery = (clone $baseQuery)->whereIn('status', [
            AppointmentStatus::CONFIRMED->value,
            AppointmentStatus::COMPLETED->value,
            AppointmentStatus::IN_PROGRESS->value,
            AppointmentStatus::PENDING_VERIFICATION->value,
        ]);

        $totalRevenue = (float) (clone $validAppointmentsQuery)->sum('total_amount');
        $depositRevenue = (float) (clone $validAppointmentsQuery)->sum('deposit_paid');
        $pendingBalance = (float) (clone $validAppointmentsQuery)->sum('balance_due');

        // Comparativa de período anterior
        $prevAppointmentsQuery = Appointment::where('tenant_id', $tenantId)
            ->whereBetween('appointment_date', [$prevStart->toDateString(), $prevEnd->toDateString()]);
        
        $prevTotalAppointments = (clone $prevAppointmentsQuery)->count();
        $prevTotalRevenue = (float) (clone $prevAppointmentsQuery)
            ->whereIn('status', [
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::IN_PROGRESS->value,
                AppointmentStatus::PENDING_VERIFICATION->value,
            ])
            ->sum('total_amount');

        $revenueGrowth = $prevTotalRevenue > 0 
            ? round((($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100, 1) 
            : null;

        $appointmentsGrowth = $prevTotalAppointments > 0 
            ? round((($totalAppointments - $prevTotalAppointments) / $prevTotalAppointments) * 100, 1) 
            : null;

        $averageTicket = $effectiveTotalCount > 0 ? round($totalRevenue / $effectiveTotalCount) : 0;
        $completionRate = $totalAppointments > 0 ? round(($effectiveTotalCount / $totalAppointments) * 100, 1) : 0;
        $cancellationRate = $totalAppointments > 0 ? round((($cancelledCount + $noShowCount) / $totalAppointments) * 100, 1) : 0;

        // 3. Serie de Gráfico de Barras según Período
        $chartSeries = [];
        if ($this->period === 'week') {
            $dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            for ($i = 0; $i < 7; $i++) {
                $dayDate = $start->copy()->addDays($i);
                $dayAppointments = (clone $baseQuery)->whereDate('appointment_date', $dayDate->toDateString())->get();
                $chartSeries[] = [
                    'label' => $dayNames[$i] . ' ' . $dayDate->format('d'),
                    'count' => $dayAppointments->count(),
                    'revenue' => (float) $dayAppointments->whereIn('status', [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED, AppointmentStatus::IN_PROGRESS])->sum('total_amount'),
                ];
            }
        } elseif ($this->period === 'month') {
            // 4 o 5 bloques semanales del mes
            $daysInMonth = $start->daysInMonth;
            $chunks = [
                ['label' => 'Días 1 - 7', 'start' => 1, 'end' => 7],
                ['label' => 'Días 8 - 14', 'start' => 8, 'end' => 14],
                ['label' => 'Días 15 - 21', 'start' => 15, 'end' => 21],
                ['label' => 'Días 22 - 28', 'start' => 22, 'end' => 28],
            ];
            if ($daysInMonth > 28) {
                $chunks[] = ['label' => "Días 29 - {$daysInMonth}", 'start' => 29, 'end' => $daysInMonth];
            }

            foreach ($chunks as $chunk) {
                $chunkStart = $start->copy()->day($chunk['start'])->startOfDay();
                $chunkEnd = $start->copy()->day($chunk['end'])->endOfDay();
                $chunkAppointments = (clone $baseQuery)->whereBetween('appointment_date', [$chunkStart->toDateString(), $chunkEnd->toDateString()])->get();
                $chartSeries[] = [
                    'label' => $chunk['label'],
                    'count' => $chunkAppointments->count(),
                    'revenue' => (float) $chunkAppointments->whereIn('status', [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED, AppointmentStatus::IN_PROGRESS])->sum('total_amount'),
                ];
            }
        } elseif ($this->period === 'quarter') {
            for ($m = 0; $m < 3; $m++) {
                $monthDate = $start->copy()->addMonths($m);
                $monthAppointments = (clone $baseQuery)
                    ->whereYear('appointment_date', $monthDate->year)
                    ->whereMonth('appointment_date', $monthDate->month)
                    ->get();
                $chartSeries[] = [
                    'label' => ucfirst($monthDate->isoFormat('MMM Y')),
                    'count' => $monthAppointments->count(),
                    'revenue' => (float) $monthAppointments->whereIn('status', [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED, AppointmentStatus::IN_PROGRESS])->sum('total_amount'),
                ];
            }
        } elseif ($this->period === 'semester') {
            for ($m = 0; $m < 6; $m++) {
                $monthDate = $start->copy()->addMonths($m);
                $monthAppointments = (clone $baseQuery)
                    ->whereYear('appointment_date', $monthDate->year)
                    ->whereMonth('appointment_date', $monthDate->month)
                    ->get();
                $chartSeries[] = [
                    'label' => ucfirst($monthDate->isoFormat('MMM')),
                    'count' => $monthAppointments->count(),
                    'revenue' => (float) $monthAppointments->whereIn('status', [AppointmentStatus::CONFIRMED, AppointmentStatus::COMPLETED, AppointmentStatus::IN_PROGRESS])->sum('total_amount'),
                ];
            }
        }

        // Normalizar alturas de barras para renderizado porcentual
        $maxRevenue = max(array_column($chartSeries, 'revenue') ?: [1]);
        $maxCount = max(array_column($chartSeries, 'count') ?: [1]);

        foreach ($chartSeries as &$item) {
            $item['revenue_height'] = $maxRevenue > 0 ? max(8, round(($item['revenue'] / $maxRevenue) * 100)) : 8;
            $item['count_height'] = $maxCount > 0 ? max(8, round(($item['count'] / $maxCount) * 100)) : 8;
            $item['formatted_revenue'] = '$ ' . number_format($item['revenue'], 0, ',', '.');
        }
        unset($item);

        // 4. Top 5 Servicios Más Solicitados
        $topServices = (clone $validAppointmentsQuery)
            ->selectRaw('service_id, count(*) as total_count, sum(total_amount) as total_rev')
            ->groupBy('service_id')
            ->orderByDesc('total_count')
            ->with('service')
            ->limit(5)
            ->get()
            ->map(function ($item) use ($totalRevenue) {
                return [
                    'name' => $item->service?->name ?? 'Tratamiento de Belleza',
                    'category' => $item->service?->category ?? 'Estética',
                    'count' => $item->total_count,
                    'revenue' => (float) $item->total_rev,
                    'formatted_revenue' => '$ ' . number_format($item->total_rev, 0, ',', '.') . ' COP',
                    'percentage' => $totalRevenue > 0 ? round(($item->total_rev / $totalRevenue) * 100, 1) : 0,
                ];
            });

        // 5. Métodos de Pago
        $nequiCount = (clone $validAppointmentsQuery)->where('payment_method', PaymentMethod::NEQUI_TRANSFER->value)->count();
        $nequiRevenue = (float) (clone $validAppointmentsQuery)->where('payment_method', PaymentMethod::NEQUI_TRANSFER->value)->sum('deposit_paid');

        $boldCount = (clone $validAppointmentsQuery)->where('payment_method', PaymentMethod::BOLD_ONLINE->value)->count();
        $boldRevenue = (float) (clone $validAppointmentsQuery)->where('payment_method', PaymentMethod::BOLD_ONLINE->value)->sum('deposit_paid');

        // 6. Análisis de Clientas (Nuevas vs Recurrentes en el período)
        $clientPhones = (clone $baseQuery)->pluck('client_phone')->unique()->filter();
        $totalClientsInPeriod = $clientPhones->count();
        $recurrentClientsCount = 0;

        foreach ($clientPhones as $phone) {
            $hasPast = Appointment::where('tenant_id', $tenantId)
                ->where('client_phone', $phone)
                ->where('appointment_date', '<', $start->toDateString())
                ->exists();
            if ($hasPast) {
                $recurrentClientsCount++;
            }
        }
        $newClientsCount = max(0, $totalClientsInPeriod - $recurrentClientsCount);

        return [
            'period' => $this->period,
            'periodTitle' => $periodTitle,
            'offset' => $this->offset,
            'isCurrentPeriod' => $this->offset === 0,
            
            // Financieros
            'totalRevenue' => $totalRevenue,
            'formattedRevenue' => '$ ' . number_format($totalRevenue, 0, ',', '.') . ' COP',
            'depositRevenue' => $depositRevenue,
            'formattedDeposit' => '$ ' . number_format($depositRevenue, 0, ',', '.') . ' COP',
            'pendingBalance' => $pendingBalance,
            'formattedPending' => '$ ' . number_format($pendingBalance, 0, ',', '.') . ' COP',
            'averageTicket' => $averageTicket,
            'formattedTicket' => '$ ' . number_format($averageTicket, 0, ',', '.') . ' COP',
            'revenueGrowth' => $revenueGrowth,

            // Volumen y Cumplimiento
            'totalAppointments' => $totalAppointments,
            'effectiveTotalCount' => $effectiveTotalCount,
            'completedCount' => $completedCount,
            'confirmedCount' => $confirmedCount,
            'inProgressCount' => $inProgressCount,
            'pendingVerificationCount' => $pendingVerificationCount,
            'pendingDepositCount' => $pendingDepositCount,
            'cancelledCount' => $cancelledCount,
            'noShowCount' => $noShowCount,
            'completionRate' => $completionRate,
            'cancellationRate' => $cancellationRate,
            'appointmentsGrowth' => $appointmentsGrowth,

            // Gráficos y Desgloses
            'chartSeries' => $chartSeries,
            'topServices' => $topServices,
            'nequiCount' => $nequiCount,
            'nequiRevenue' => $nequiRevenue,
            'boldCount' => $boldCount,
            'boldRevenue' => $boldRevenue,

            // Clientes
            'totalClientsInPeriod' => $totalClientsInPeriod,
            'newClientsCount' => $newClientsCount,
            'recurrentClientsCount' => $recurrentClientsCount,
        ];
    }
}
