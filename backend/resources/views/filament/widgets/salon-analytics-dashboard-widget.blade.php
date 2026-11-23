<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $period = $data['period'];
        $periodTitle = $data['periodTitle'];
        $isCurrent = $data['isCurrentPeriod'];
        $series = $data['chartSeries'];
        $topServices = $data['topServices'];

        $formattedRevenue = $data['formattedRevenue'];
        $formattedDeposit = $data['formattedDeposit'];
        $formattedPending = $data['formattedPending'];
        $formattedTicket = $data['formattedTicket'];
        $revenueGrowth = $data['revenueGrowth'];

        $totalAppointments = $data['totalAppointments'];
        $completedCount = $data['completedCount'];
        $confirmedCount = $data['confirmedCount'];
        $todayCount = $data['todayAppointmentsCount'];
        $pendingNequi = $data['pendingNequiGlobalCount'];

        $miniCalendar = $data['miniCalendar'];
        $recentAppointments = $data['recentAppointments'];
        $recentNovedades = $data['recentNovedades'];
        $topContacts = $data['topContacts'];

        $chartColors = ['#00C975', '#06B6D4', '#F59E0B', '#EC4899', '#3B82F6', '#8B5CF6', '#10B981'];
    @endphp

    <div class="nuvex-dashboard-wrapper">
        {{-- ============================================================
             1. ENCABEZADO PRINCIPAL (DASHBOARD HEADER)
             ============================================================ --}}
        <div class="nuvex-dash-header">
            <div class="nuvex-dash-title-wrap">
                <h1 class="nuvex-dash-title">DASHBOARD</h1>
                <p class="nuvex-dash-subtitle">
                    Bienvenida de nuevo, <span class="nuvex-user-highlight">{{ auth()->user()->name ?? 'Paola' }}</span> · Panel de Control en tiempo real
                </p>
            </div>

            {{-- Selector de Períodos y Navegación --}}
            <div class="nuvex-dash-controls">
                <div class="nuvex-period-capsule">
                    <button type="button" wire:click="setPeriod('week')" class="nuvex-capsule-btn {{ $period === 'week' ? 'active' : '' }}">Semana</button>
                    <button type="button" wire:click="setPeriod('month')" class="nuvex-capsule-btn {{ $period === 'month' ? 'active' : '' }}">Mes</button>
                    <button type="button" wire:click="setPeriod('quarter')" class="nuvex-capsule-btn {{ $period === 'quarter' ? 'active' : '' }}">Trimestre</button>
                    <button type="button" wire:click="setPeriod('semester')" class="nuvex-capsule-btn {{ $period === 'semester' ? 'active' : '' }}">Semestre</button>
                </div>

                <div class="nuvex-period-nav">
                    <button type="button" wire:click="previousPeriod" class="nuvex-nav-arrow" title="Período anterior">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <span class="nuvex-period-label">{{ $periodTitle }}</span>
                    <button type="button" wire:click="nextPeriod" class="nuvex-nav-arrow" title="Período siguiente">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ============================================================
             2. FILA SUPERIOR: INCOME CARD + MINI-CALENDAR
             ============================================================ --}}
        <div class="nuvex-grid-top">
            {{-- TARJETA 1: INCOME CARD (FONDO SLATE OSCURO ELEGANTE) --}}
            <div class="nuvex-card nuvex-card-dark">
                <div class="nuvex-income-head">
                    <div>
                        <span class="nuvex-dark-badge">INGRESOS DEL PERÍODO</span>
                        <div class="nuvex-income-amount-wrap">
                            <h2 class="nuvex-income-amount">{{ $formattedRevenue }}</h2>
                            @if(!is_null($revenueGrowth))
                                <span class="nuvex-growth-tag {{ $revenueGrowth >= 0 ? 'growth-pos' : 'growth-neg' }}">
                                    {{ $revenueGrowth >= 0 ? '+' : '' }}{{ $revenueGrowth }}%
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="nuvex-stat-pill">
                        <span class="text-xs text-slate-400 font-medium">Citas Proyectadas</span>
                        <span class="text-base font-bold text-white">{{ $totalAppointments }}</span>
                    </div>
                </div>

                {{-- Gráfico de Barras Verticales Estilizadas --}}
                <div class="nuvex-chart-wrap">
                    <div class="nuvex-bars-container">
                        @foreach($series as $idx => $item)
                            @php
                                $barColor = $chartColors[$idx % count($chartColors)];
                                $height = max(12, min(100, $item['height_pct'] ?? ($item['revenue_height'] ?? 15)));
                            @endphp
                            <div class="nuvex-bar-col">
                                <div class="nuvex-bar-track">
                                    <div class="nuvex-bar-fill" style="height: {{ $height }}%; background: {{ $barColor }};" title="{{ $item['label'] ?? '' }}: ${{ number_format($item['revenue'] ?? 0, 0, ',', '.') }}"></div>
                                </div>
                                <span class="nuvex-bar-lbl">{{ $item['label'] ?? '' }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Métricas Desglosadas en Mini-Pills --}}
                <div class="nuvex-income-breakdown">
                    <div class="nuvex-breakdown-item">
                        <span class="nuvex-dot-indicator bg-emerald-400"></span>
                        <div>
                            <p class="nuvex-breakdown-title">Abonos Recibidos</p>
                            <p class="nuvex-breakdown-val text-emerald-400">{{ $formattedDeposit }}</p>
                        </div>
                    </div>
                    <div class="nuvex-breakdown-item">
                        <span class="nuvex-dot-indicator bg-amber-400"></span>
                        <div>
                            <p class="nuvex-breakdown-title">Saldo por Cobrar</p>
                            <p class="nuvex-breakdown-val text-amber-300">{{ $formattedPending }}</p>
                        </div>
                    </div>
                    <div class="nuvex-breakdown-item">
                        <span class="nuvex-dot-indicator bg-pink-400"></span>
                        <div>
                            <p class="nuvex-breakdown-title">Ticket Promedio</p>
                            <p class="nuvex-breakdown-val text-pink-300">{{ $formattedTicket }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TARJETA 2: MINI-CALENDAR WIDGET (ESTILO ATELIER CON HEADER ESMERALDA) --}}
            <div class="nuvex-card nuvex-card-calendar">
                <div class="nuvex-cal-header-ribbon">
                    <button type="button" wire:click="previousPeriod" class="nuvex-cal-arrow">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <span class="nuvex-cal-title">{{ $miniCalendar['month_name'] }}</span>
                    <button type="button" wire:click="nextPeriod" class="nuvex-cal-arrow">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="nuvex-cal-body">
                    {{-- Días de la semana --}}
                    <div class="nuvex-cal-weekdays">
                        <span>LU</span><span>MA</span><span>MI</span><span>JU</span><span>VI</span><span>SÁ</span><span>DO</span>
                    </div>

                    {{-- Matriz del Calendario --}}
                    <div class="nuvex-cal-grid">
                        @foreach($miniCalendar['days'] as $calDay)
                            @php
                                $isCurrent = $calDay['is_current_month'] ?? (!is_null($calDay['day'] ?? null));
                                $dayNum = $calDay['day'] ?? null;
                                $hasApp = $calDay['has_appointments'] ?? false;
                                $isToday = $calDay['is_today'] ?? false;
                                $calColor = $calDay['color'] ?? ($calDay['badge_color'] ?? '#EC4899');
                                $calCount = $calDay['count'] ?? 0;
                            @endphp
                            <div class="nuvex-cal-cell {{ !$isCurrent ? 'nuvex-cal-outside' : '' }}">
                                @if($dayNum)
                                    @if($hasApp)
                                        <span class="nuvex-cal-badge" style="background-color: {{ $calColor }};" title="{{ $calCount }} citas">
                                            {{ $dayNum }}
                                        </span>
                                    @elseif($isToday)
                                        <span class="nuvex-cal-today">{{ $dayNum }}</span>
                                    @else
                                        <span class="nuvex-cal-number">{{ $dayNum }}</span>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- Pie del calendario --}}
                    <div class="nuvex-cal-footer">
                        <div class="nuvex-cal-legend">
                            <span class="nuvex-mini-dot bg-pink-500"></span>
                            <span class="text-xs text-slate-500 font-medium">Días con citas</span>
                        </div>
                        <div class="nuvex-cal-legend">
                            <span class="nuvex-mini-dot bg-teal-600"></span>
                            <span class="text-xs text-slate-500 font-medium">Hoy</span>
                        </div>
                        <div class="ml-auto text-xs font-bold text-teal-700 dark:text-teal-400">
                            {{ $totalAppointments }} citas este mes
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
             3. FILA INFERIOR: CITAS RECIENTES (60%) + NOVEDADES/CONTACTOS (40%)
             ============================================================ --}}
        <div class="nuvex-grid-bottom">
            {{-- COLUMNA IZQUIERDA: CITAS RECIENTES (RECENT ORDERS) --}}
            <div class="nuvex-card nuvex-card-white">
                <div class="nuvex-card-header">
                    <div>
                        <h3 class="nuvex-card-heading">Citas Recientes</h3>
                        <p class="nuvex-card-subheading">Últimas reservas recibidas y agendadas</p>
                    </div>
                    <a href="{{ url('/admin/' . (Filament\Facades\Filament::getTenant()?->slug ?? 'la-belle') . '/appointments') }}" class="nuvex-card-link">
                        Ver todas
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <div class="nuvex-table-wrap">
                    <table class="nuvex-table">
                        <thead>
                            <tr>
                                <th>CLIENTA / TRATAMIENTO</th>
                                <th>CÓDIGO</th>
                                <th>FECHA Y HORA</th>
                                <th class="text-right">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentAppointments as $app)
                                <tr>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="nuvex-table-avatar">
                                                {{ mb_substr($app['client_name'] ?? 'C', 0, 1) }}
                                            </div>
                                            <div class="flex flex-col">
                                                <span class="nuvex-client-name">{{ $app['client_name'] ?? 'Clienta' }}</span>
                                                <span class="nuvex-service-desc">{{ $app['service_name'] ?? 'Tratamiento' }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="nuvex-code-pill">{{ $app['code'] ?? ($app['appointment_number'] ?? '#CC-0000') }}</span>
                                    </td>
                                    <td>
                                        <div class="flex flex-col">
                                            <span class="nuvex-date-txt">{{ $app['date_formatted'] ?? ($app['date'] ?? '') }}</span>
                                            <span class="nuvex-time-txt">{{ $app['time_formatted'] ?? '' }}</span>
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <span class="nuvex-status-pill" style="background-color: {{ $app['status_bg'] ?? 'rgba(16, 185, 129, 0.12)' }}; color: {{ $app['status_color'] ?? '#10B981' }};">
                                            <span class="nuvex-status-dot" style="background-color: {{ $app['status_color'] ?? '#10B981' }};"></span>
                                            {{ $app['status_label'] ?? 'Registrada' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-8 text-slate-400 text-sm">
                                        No hay citas registradas en este período.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- COLUMNA DERECHA: NOVEDADES & COMPROBANTES + CLIENTAS FRECUENTES --}}
            <div class="nuvex-col-stack">
                {{-- CARD A: NOVEDADES & COMPROBANTES (RECENT COMMENTS) --}}
                <div class="nuvex-card nuvex-card-white">
                    <div class="nuvex-card-header">
                        <div>
                            <h3 class="nuvex-card-heading">Novedades & Comprobantes</h3>
                            <p class="nuvex-card-subheading">Mensajes y actualizaciones de citas</p>
                        </div>
                    </div>

                    <div class="nuvex-activity-list">
                        @forelse($recentNovedades as $nov)
                            <div class="nuvex-activity-item">
                                <div class="nuvex-act-avatar" style="background-color: {{ $nov['avatar_color'] ?? '#EC4899' }};">
                                    {{ mb_substr($nov['client_name'] ?? 'C', 0, 1) }}
                                </div>
                                <div class="nuvex-act-content">
                                    <div class="flex items-center justify-between">
                                        <h4 class="nuvex-act-name">{{ $nov['client_name'] ?? 'Clienta' }}</h4>
                                        <span class="nuvex-act-time">{{ $nov['time_ago'] ?? 'Reciente' }}</span>
                                    </div>
                                    <p class="nuvex-act-text">{{ Str::limit($nov['note'] ?? '', 75) }}</p>
                                    <span class="nuvex-act-service">{{ $nov['service'] ?? 'Tratamiento' }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-slate-400 text-xs">
                                Sin novedades recientes.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- CARD B: CLIENTAS FRECUENTES (CONTACTS) --}}
                <div class="nuvex-card nuvex-card-white">
                    <div class="nuvex-card-header">
                        <div>
                            <h3 class="nuvex-card-heading">Clientas Frecuentes</h3>
                            <p class="nuvex-card-subheading">Mayor número de agendamientos</p>
                        </div>
                    </div>

                    <div class="nuvex-contacts-grid">
                        @forelse($topContacts as $contact)
                            <div class="nuvex-contact-card">
                                <div class="nuvex-contact-avatar" style="background-color: {{ $contact['avatar_color'] ?? '#EC4899' }};">
                                    {{ mb_substr($contact['name'] ?? 'C', 0, 1) }}
                                </div>
                                <div class="nuvex-contact-info">
                                    <h5 class="nuvex-contact-name">{{ Str::limit($contact['name'] ?? 'Clienta', 15) }}</h5>
                                    <span class="nuvex-contact-stat">{{ $contact['total_appointments'] ?? 1 }} citas</span>
                                    <span class="nuvex-contact-phone">{{ $contact['phone'] ?? '' }}</span>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-2 text-center py-4 text-slate-400 text-xs">
                                Sin clientas recurrentes aún.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         ESTILOS NUVEX MODERN CURVE UI EN LÍNEA
         ============================================================ --}}
    <style>
        .nuvex-dashboard-wrapper {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
            box-sizing: border-box;
        }

        /* 1. Header */
        .nuvex-dash-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            padding: 0.25rem 0.25rem 0.5rem 0.25rem;
        }

        .nuvex-dash-title {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
            line-height: 1.2;
            margin: 0;
        }

        .dark .nuvex-dash-title {
            color: #F8FAFC;
        }

        .nuvex-dash-subtitle {
            font-size: 0.825rem;
            color: #64748B;
            margin: 0.2rem 0 0 0;
            font-weight: 500;
        }

        .dark .nuvex-dash-subtitle {
            color: #94A3B8;
        }

        .nuvex-user-highlight {
            font-weight: 700;
            color: #0D9488;
        }

        .dark .nuvex-user-highlight {
            color: #2DD4BF;
        }

        .nuvex-dash-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .nuvex-period-capsule {
            display: inline-flex;
            background: #E2E8F0;
            padding: 3px;
            border-radius: 9999px;
            gap: 3px;
        }

        .dark .nuvex-period-capsule {
            background: #1E293B;
        }

        .nuvex-capsule-btn {
            background: transparent;
            border: none;
            outline: none;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748B;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .dark .nuvex-capsule-btn {
            color: #94A3B8;
        }

        .nuvex-capsule-btn.active {
            background: #FFFFFF;
            color: #0D9488;
            font-weight: 700;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
        }

        .dark .nuvex-capsule-btn.active {
            background: #0F172A;
            color: #2DD4BF;
        }

        .nuvex-period-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 9999px;
            padding: 3px 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        .dark .nuvex-period-nav {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-nav-arrow {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: transparent;
            border: none;
            color: #64748B;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .nuvex-nav-arrow:hover {
            background: #F1F5F9;
            color: #0F172A;
        }

        .dark .nuvex-nav-arrow:hover {
            background: #334155;
            color: #FFFFFF;
        }

        .nuvex-period-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #1E293B;
            padding: 0 6px;
        }

        .dark .nuvex-period-label {
            color: #F8FAFC;
        }

        /* 2. Tarjetas Base Nuvex Curve */
        .nuvex-card {
            border-radius: 28px;
            box-sizing: border-box;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .nuvex-card-white {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 4px 10px -2px rgba(0, 0, 0, 0.02);
            padding: 1.5rem;
        }

        .dark .nuvex-card-white {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.35);
        }

        /* Layout Grid */
        .nuvex-grid-top {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 1.25rem;
        }

        @media (max-width: 1024px) {
            .nuvex-grid-top {
                grid-template-columns: 1fr;
            }
        }

        .nuvex-grid-bottom {
            display: grid;
            grid-template-columns: 1.25fr 0.75fr;
            gap: 1.25rem;
        }

        @media (max-width: 1024px) {
            .nuvex-grid-bottom {
                grid-template-columns: 1fr;
            }
        }

        .nuvex-col-stack {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        /* 3. Tarjeta Oscura de Ingresos (Dark Slate Income) */
        .nuvex-card-dark {
            background: #1E293B;
            color: #FFFFFF;
            padding: 1.75rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-shadow: 0 16px 36px -6px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
        }

        .nuvex-income-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
        }

        .nuvex-dark-badge {
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            color: #94A3B8;
            text-transform: uppercase;
        }

        .nuvex-income-amount-wrap {
            display: flex;
            align-items: baseline;
            gap: 0.65rem;
            margin-top: 0.35rem;
        }

        .nuvex-income-amount {
            font-size: 1.95rem;
            font-weight: 800;
            color: #FFFFFF;
            letter-spacing: -0.03em;
            margin: 0;
            line-height: 1.15;
        }

        .nuvex-growth-tag {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 9999px;
        }

        .growth-pos {
            background: rgba(16, 185, 129, 0.2);
            color: #34D399;
        }

        .growth-neg {
            background: rgba(239, 68, 68, 0.2);
            color: #F87171;
        }

        .nuvex-stat-pill {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 0.5rem 0.85rem;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        /* Barras verticales del gráfico */
        .nuvex-chart-wrap {
            margin: 1.5rem 0 1.25rem 0;
            padding-top: 0.5rem;
        }

        .nuvex-bars-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            height: 110px;
            gap: 0.5rem;
            padding: 0 0.5rem;
        }

        .nuvex-bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.45rem;
            flex: 1;
            height: 100%;
            justify-content: flex-end;
        }

        .nuvex-bar-track {
            width: 10px;
            height: 85px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 9999px;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
        }

        .nuvex-bar-fill {
            width: 100%;
            border-radius: 9999px;
            transition: height 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nuvex-bar-lbl {
            font-size: 0.65rem;
            font-weight: 600;
            color: #94A3B8;
            text-transform: capitalize;
        }

        /* Desglose de ingresos */
        .nuvex-income-breakdown {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (max-width: 640px) {
            .nuvex-income-breakdown {
                grid-template-columns: 1fr;
            }
        }

        .nuvex-breakdown-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nuvex-dot-indicator {
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        .nuvex-breakdown-title {
            font-size: 0.68rem;
            color: #94A3B8;
            margin: 0;
            font-weight: 500;
        }

        .nuvex-breakdown-val {
            font-size: 0.8rem;
            font-weight: 700;
            margin: 0.1rem 0 0 0;
        }

        /* 4. Mini-Calendar Widget */
        .nuvex-card-calendar {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .dark .nuvex-card-calendar {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-cal-header-ribbon {
            background: #0d9488;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nuvex-cal-title {
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: #FFFFFF;
            text-transform: uppercase;
        }

        .nuvex-cal-arrow {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            width: 26px;
            height: 26px;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .nuvex-cal-arrow:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .nuvex-cal-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            flex: 1;
            justify-content: space-between;
        }

        .nuvex-cal-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            text-align: center;
            font-size: 0.68rem;
            font-weight: 800;
            color: #94A3B8;
            letter-spacing: 0.03em;
        }

        .nuvex-cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            text-align: center;
        }

        .nuvex-cal-cell {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #1E293B;
        }

        .dark .nuvex-cal-cell {
            color: #F8FAFC;
        }

        .nuvex-cal-outside {
            opacity: 0.25;
        }

        .nuvex-cal-number {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
        }

        .nuvex-cal-today {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            background: #E6F7F2;
            color: #0D9488;
            font-weight: 800;
            border: 1.5px solid #0D9488;
        }

        .dark .nuvex-cal-today {
            background: rgba(13, 148, 136, 0.2);
            color: #2DD4BF;
        }

        .nuvex-cal-badge {
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 9999px;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 0.72rem;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        }

        .nuvex-cal-footer {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding-top: 0.75rem;
            border-top: 1px solid #F1F5F9;
        }

        .dark .nuvex-cal-footer {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .nuvex-cal-legend {
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .nuvex-mini-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
        }

        /* 5. Citas Recientes (Table) */
        .nuvex-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.15rem;
        }

        .nuvex-card-heading {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0F172A;
            margin: 0;
            letter-spacing: -0.01em;
        }

        .dark .nuvex-card-heading {
            color: #F8FAFC;
        }

        .nuvex-card-subheading {
            font-size: 0.72rem;
            color: #64748B;
            margin: 0.15rem 0 0 0;
        }

        .dark .nuvex-card-subheading {
            color: #94A3B8;
        }

        .nuvex-card-link {
            font-size: 0.75rem;
            font-weight: 700;
            color: #0D9488;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: color 0.15s ease;
        }

        .nuvex-card-link:hover {
            color: #0F766E;
        }

        .nuvex-table-wrap {
            overflow-x: auto;
        }

        .nuvex-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }

        .nuvex-table thead th {
            text-align: left;
            font-size: 0.65rem;
            font-weight: 800;
            color: #94A3B8;
            letter-spacing: 0.05em;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #F1F5F9;
        }

        .dark .nuvex-table thead th {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .nuvex-table tbody td {
            padding: 0.85rem 0;
            border-bottom: 1px solid #F8FAFC;
            vertical-align: middle;
        }

        .dark .nuvex-table tbody td {
            border-color: rgba(255, 255, 255, 0.04);
        }

        .nuvex-table-avatar {
            width: 32px;
            height: 32px;
            border-radius: 9999px;
            background: #fce7f3;
            color: #be185d;
            font-weight: 800;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nuvex-client-name {
            font-weight: 700;
            color: #1E293B;
            font-size: 0.825rem;
        }

        .dark .nuvex-client-name {
            color: #F8FAFC;
        }

        .nuvex-service-desc {
            font-size: 0.7rem;
            color: #64748B;
        }

        .dark .nuvex-service-desc {
            color: #94A3B8;
        }

        .nuvex-code-pill {
            font-family: monospace;
            font-size: 0.72rem;
            font-weight: 700;
            color: #475569;
            background: #F1F5F9;
            padding: 2px 7px;
            border-radius: 6px;
        }

        .dark .nuvex-code-pill {
            background: #334155;
            color: #E2E8F0;
        }

        .nuvex-date-txt {
            font-weight: 600;
            color: #1E293B;
            font-size: 0.75rem;
        }

        .dark .nuvex-date-txt {
            color: #F8FAFC;
        }

        .nuvex-time-txt {
            font-size: 0.68rem;
            color: #94A3B8;
        }

        .nuvex-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .nuvex-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 9999px;
        }

        /* 6. Novedades y Comprobantes (Recent Comments) */
        .nuvex-activity-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
        }

        .nuvex-activity-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #F8FAFC;
        }

        .dark .nuvex-activity-item {
            border-color: rgba(255, 255, 255, 0.04);
        }

        .nuvex-activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .nuvex-act-avatar {
            width: 34px;
            height: 34px;
            border-radius: 9999px;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
        }

        .nuvex-act-content {
            flex: 1;
            min-width: 0;
        }

        .nuvex-act-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #1E293B;
            margin: 0;
        }

        .dark .nuvex-act-name {
            color: #F8FAFC;
        }

        .nuvex-act-time {
            font-size: 0.65rem;
            color: #94A3B8;
        }

        .nuvex-act-text {
            font-size: 0.72rem;
            color: #475569;
            margin: 0.2rem 0 0.25rem 0;
            line-height: 1.35;
        }

        .dark .nuvex-act-text {
            color: #CBD5E1;
        }

        .nuvex-act-service {
            font-size: 0.65rem;
            font-weight: 700;
            color: #0D9488;
            background: #E6F7F2;
            padding: 1px 6px;
            border-radius: 4px;
        }

        .dark .nuvex-act-service {
            background: rgba(13, 148, 136, 0.2);
            color: #2DD4BF;
        }

        /* 7. Clientas Frecuentes (Contacts Grid) */
        .nuvex-contacts-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        @media (max-width: 640px) {
            .nuvex-contacts-grid {
                grid-template-columns: 1fr;
            }
        }

        .nuvex-contact-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.65rem;
            transition: transform 0.15s ease;
        }

        .dark .nuvex-contact-card {
            background: #0F172A;
            border-color: rgba(255, 255, 255, 0.06);
        }

        .nuvex-contact-card:hover {
            transform: translateY(-2px);
        }

        .nuvex-contact-avatar {
            width: 32px;
            height: 32px;
            border-radius: 9999px;
            color: #FFFFFF;
            font-weight: 800;
            font-size: 0.72rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nuvex-contact-info {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
        }

        .nuvex-contact-name {
            font-size: 0.75rem;
            font-weight: 700;
            color: #1E293B;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dark .nuvex-contact-name {
            color: #F8FAFC;
        }

        .nuvex-contact-stat {
            font-size: 0.65rem;
            font-weight: 700;
            color: #EC4899;
        }

        .nuvex-contact-phone {
            font-size: 0.62rem;
            color: #94A3B8;
        }
    </style>
</x-filament-widgets::widget>
