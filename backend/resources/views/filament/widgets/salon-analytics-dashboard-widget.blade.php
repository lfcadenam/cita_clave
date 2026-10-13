<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $period = $data['period'];
        $periodTitle = $data['periodTitle'];
        $isCurrent = $data['isCurrentPeriod'];
        $series = $data['chartSeries'];
        $topServices = $data['topServices'];

        $todayCount = $data['todayAppointmentsCount'];
        $pendingNequi = $data['pendingNequiGlobalCount'];
    @endphp

    <style>
        .nuvex-analytics-container {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            box-sizing: border-box;
        }

        /* Barra de control superior limpia */
        .nuvex-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 0.25rem 0.5rem;
        }

        .nuvex-header-title {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .nuvex-badge-period {
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 9999px;
            background: #e6f7f2;
            color: #0d9488;
            letter-spacing: 0.02em;
        }

        .dark .nuvex-badge-period {
            background: rgba(13, 148, 136, 0.2);
            color: #2dd4bf;
        }

        /* Controles de filtro */
        .nuvex-pill-group {
            display: inline-flex;
            background: #F1F5F9;
            padding: 3px;
            border-radius: 16px;
            border: 1px solid #E2E8F0;
            gap: 2px;
        }

        .dark .nuvex-pill-group {
            background: #0F172A;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-pill-btn {
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            outline: none;
            transition: all 0.2s ease;
            color: #64748B;
            background: transparent;
        }

        .dark .nuvex-pill-btn {
            color: #94A3B8;
        }

        .nuvex-pill-btn.active {
            background: #FFFFFF;
            color: #0d9488;
            font-weight: 700;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        }

        .dark .nuvex-pill-btn.active {
            background: #1E293B;
            color: #14b8a6;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        .nuvex-nav-circle-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nuvex-nav-circle-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        .dark .nuvex-nav-circle-btn {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            color: #94A3B8;
        }

        .dark .nuvex-nav-circle-btn:hover {
            background: #334155;
            color: #F8FAFC;
        }

        /* Tarjetas Elevadas Estilo Nuvex Modern Curve */
        .nuvex-card {
            background: #FFFFFF;
            border-radius: 24px;
            border: 1px solid #E2E8F0;
            padding: 1.25rem 1.4rem;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.03), 0 2px 6px -2px rgba(0, 0, 0, 0.02);
            position: relative;
            box-sizing: border-box;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .dark .nuvex-card {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 8px 24px -4px rgba(0, 0, 0, 0.25);
        }

        /* Grid de KPIs única fila */
        .nuvex-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
            width: 100%;
        }

        .nuvex-icon-badge {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nuvex-icon-badge svg {
            width: 18px !important;
            height: 18px !important;
        }

        /* Gráfico de barras */
        .nuvex-chart-canvas {
            height: 180px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
            padding: 0.75rem 0.25rem 0.25rem 0.25rem;
            border-bottom: 1px solid #F1F5F9;
            box-sizing: border-box;
        }

        .dark .nuvex-chart-canvas {
            border-color: rgba(255, 255, 255, 0.06);
        }

        .nuvex-bar-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            position: relative;
        }

        .nuvex-bar-pair {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 3px;
            width: 100%;
            height: 100%;
        }

        .nuvex-bar-rev {
            width: 10px;
            max-width: 14px;
            background: linear-gradient(180deg, #0d9488 0%, #00C975 100%);
            border-radius: 4px 4px 0 0;
            transition: height 0.3s ease;
        }

        .nuvex-bar-count {
            width: 10px;
            max-width: 14px;
            background: #1E293B;
            border-radius: 4px 4px 0 0;
            transition: height 0.3s ease;
        }

        .dark .nuvex-bar-count {
            background: #F59E0B;
        }

        .nuvex-bar-tooltip {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
            background: #0F172A;
            color: #FFFFFF;
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 10px;
            white-space: nowrap;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .nuvex-bar-column:hover .nuvex-bar-tooltip {
            opacity: 1;
        }

        .nuvex-bar-label {
            font-size: 10px;
            font-weight: 600;
            color: #64748B;
            margin-top: 6px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .dark .nuvex-bar-label {
            color: #94A3B8;
        }

        .nuvex-split-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 1.25rem;
            width: 100%;
        }

        @media (max-width: 1024px) {
            .nuvex-split-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="nuvex-analytics-container">

        <!-- ========================================================================= -->
        <!-- 1. CABECERA EJECUTIVA Y SELECTOR DE PERÍODO                               -->
        <!-- ========================================================================= -->
        <div class="nuvex-header-bar">
            <div class="nuvex-header-title">
                <span style="font-size: 15px; font-weight: 700; color: #0F172A;" class="dark:text-white">
                    Resumen del Negocio
                </span>
                <span class="nuvex-badge-period">
                    {{ $periodTitle }}
                </span>
            </div>

            <div style="display: flex; align-items: center; gap: 0.6rem; flex-wrap: wrap;">
                <div class="nuvex-pill-group">
                    <button type="button" wire:click="setPeriod('week')" class="nuvex-pill-btn {{ $period === 'week' ? 'active' : '' }}">
                        Semanal
                    </button>
                    <button type="button" wire:click="setPeriod('month')" class="nuvex-pill-btn {{ $period === 'month' ? 'active' : '' }}">
                        Mensual
                    </button>
                    <button type="button" wire:click="setPeriod('quarter')" class="nuvex-pill-btn {{ $period === 'quarter' ? 'active' : '' }}">
                        Trimestral
                    </button>
                    <button type="button" wire:click="setPeriod('semester')" class="nuvex-pill-btn {{ $period === 'semester' ? 'active' : '' }}">
                        Semestral
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" wire:click="previousPeriod" title="Anterior" class="nuvex-nav-circle-btn">
                        <svg style="width: 13px; height: 13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    @if(!$isCurrent)
                        <button type="button" wire:click="resetPeriod" style="font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 9999px; background: #e6f7f2; color: #0d9488; border: none; cursor: pointer;">
                            Hoy
                        </button>
                    @endif
                    <button type="button" wire:click="nextPeriod" title="Siguiente" class="nuvex-nav-circle-btn">
                        <svg style="width: 13px; height: 13px;" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. ÚNICA FILA DE 4 TARJETAS KPI ESENCIALES (CERO DUPLICADOS)             -->
        <!-- ========================================================================= -->
        <div class="nuvex-kpi-grid">

            <!-- KPI 1: Operativa del Día & Validaciones -->
            <div class="nuvex-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">
                        Citas para Hoy
                    </span>
                    <div class="nuvex-icon-badge" style="background: #e6f7f2; color: #0d9488;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
                <div style="font-size: 24px; font-weight: 800; color: #0F172A;" class="dark:text-white">
                    {{ $todayCount }}
                </div>
                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #F1F5F9; font-size: 11px;" class="dark:border-slate-800">
                    @if($pendingNequi > 0)
                        <span style="display: inline-flex; align-items: center; gap: 4px; color: #d97706; font-weight: 700;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                            {{ $pendingNequi }} Nequi por verificar
                        </span>
                    @else
                        <span style="color: #0d9488; font-weight: 600;">✓ Al día, sin pendientes</span>
                    @endif
                </div>
            </div>

            <!-- KPI 2: Facturación Estimada del Período -->
            <div class="nuvex-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">
                        Facturación Estimada
                    </span>
                    <div class="nuvex-icon-badge" style="background: #e6f7f2; color: #00C975;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div style="font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.02em;" class="dark:text-white">
                    {{ $data['formattedRevenue'] }}
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #F1F5F9; font-size: 11px;" class="dark:border-slate-800">
                    <span style="color: #64748B;" class="dark:text-slate-400">{{ $data['totalAppointments'] }} citas registradas</span>
                    @if($data['revenueGrowth'] !== null)
                        <span style="font-weight: 700; color: {{ $data['revenueGrowth'] >= 0 ? '#0d9488' : '#e11d48' }};">
                            {{ $data['revenueGrowth'] >= 0 ? '↑ +' : '↓ ' }}{{ $data['revenueGrowth'] }}%
                        </span>
                    @endif
                </div>
            </div>

            <!-- KPI 3: Anticipos Asegurados -->
            <div class="nuvex-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">
                        Anticipos Asegurados
                    </span>
                    <div class="nuvex-icon-badge" style="background: #e0f2fe; color: #0284c7;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div style="font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.02em;" class="dark:text-white">
                    {{ $data['formattedDeposit'] }}
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #F1F5F9; font-size: 11px;" class="dark:border-slate-800">
                    <span style="color: #64748B;" class="dark:text-slate-400">Depósitos confirmados</span>
                    <span style="font-weight: 700; color: #0284c7;">{{ $data['effectiveTotalCount'] }} abonos</span>
                </div>
            </div>

            <!-- KPI 4: Por Recaudar en Local -->
            <div class="nuvex-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.6rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">
                        Por Recaudar en Local
                    </span>
                    <div class="nuvex-icon-badge" style="background: #fef3c7; color: #d97706;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v8.25m0-8.25a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v8.25m-19.5 0H3a.75.75 0 00.75.75v.75m18-9.75v.75a.75.75 0 01-.75.75h-.75m.75 0v8.25m0-8.25a2.25 2.25 0 00-2.25-2.25h-15A2.25 2.25 0 002.25 6v8.25" />
                        </svg>
                    </div>
                </div>
                <div style="font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.02em;" class="dark:text-white">
                    {{ $data['formattedPending'] }}
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #F1F5F9; font-size: 11px;" class="dark:border-slate-800">
                    <span style="color: #64748B;" class="dark:text-slate-400">Saldo pendiente</span>
                    <span style="font-weight: 700; color: #d97706;">{{ $data['totalRevenue'] > 0 ? round(($data['pendingBalance'] / $data['totalRevenue']) * 100) : 0 }}% en salón</span>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- 3. SECCIÓN PRINCIPAL EN 2 COLUMNAS ARMÓNICAS Y DESPEJADAS                -->
        <!-- ========================================================================= -->
        <div class="nuvex-split-grid">

            <!-- Columna Izquierda: Evolución Temporal e Indicador de Clientas -->
            <div class="nuvex-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <h4 style="font-size: 13px; font-weight: 700; color: #0F172A; margin: 0;" class="dark:text-white">Evolución de Ingresos y Citas</h4>
                            <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;" class="dark:text-slate-400">Comportamiento en {{ $periodTitle }}</p>
                        </div>
                        
                        <!-- Leyenda Minimalista -->
                        <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 11px;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #00C975; display: inline-block;"></span>
                                <span style="color: #64748B;" class="dark:text-slate-400">Ingresos</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #1E293B; display: inline-block;" class="dark:bg-amber-400"></span>
                                <span style="color: #64748B;" class="dark:text-slate-400">Citas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico -->
                    <div class="nuvex-chart-canvas">
                        @forelse($series as $bar)
                            <div class="nuvex-bar-column">
                                <div class="nuvex-bar-tooltip">
                                    <div style="font-weight: 700;">{{ $bar['label'] }}</div>
                                    <div style="color: #34d399; font-family: monospace;">{{ $bar['formatted_revenue'] }}</div>
                                    <div style="color: #cbd5e1;">{{ $bar['count'] }} cita(s)</div>
                                </div>
                                <div class="nuvex-bar-pair">
                                    <div class="nuvex-bar-rev" style="height: {{ $bar['revenue_height'] }}%;"></div>
                                    <div class="nuvex-bar-count" style="height: {{ $bar['count_height'] }}%;"></div>
                                </div>
                                <div class="nuvex-bar-label">{{ $bar['label'] }}</div>
                            </div>
                        @empty
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #94A3B8;">
                                Sin datos para este período
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Resumen de Clientas (Limpio y en una sola fila sobria) -->
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #F1F5F9; font-size: 11px; flex-wrap: wrap; gap: 0.5rem;" class="dark:border-slate-800">
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-weight: 700; color: #0F172A;" class="dark:text-white">{{ $data['totalClientsInPeriod'] }}</span>
                        <span style="color: #64748B;" class="dark:text-slate-400">clientas atendidas</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <span style="color: #0f766e; font-weight: 600;" class="dark:text-emerald-400">
                            • {{ $data['newClientsCount'] }} nuevas
                        </span>
                        <span style="color: #4338ca; font-weight: 600;" class="dark:text-indigo-400">
                            • {{ $data['recurrentClientsCount'] }} recurrentes
                        </span>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Top Servicios & Desglose de Pagos -->
            <div class="nuvex-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem;">
                        <div>
                            <h4 style="font-size: 13px; font-weight: 700; color: #0F172A; margin: 0;" class="dark:text-white">Tratamientos Más Solicitados</h4>
                            <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;" class="dark:text-slate-400">Top por volumen de atención</p>
                        </div>
                    </div>

                    <!-- Lista Top Servicios -->
                    <div style="display: flex; flex-direction: column; gap: 0.7rem;">
                        @forelse($topServices as $service)
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11px;">
                                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 190px;">
                                        <span style="font-weight: 700; color: #1E293B;" class="dark:text-slate-200">{{ $service['name'] }}</span>
                                        <span style="font-size: 10px; color: #94A3B8; margin-left: 4px;">({{ $service['count'] }})</span>
                                    </div>
                                    <span style="font-weight: 800; color: #0F172A; font-family: monospace;" class="dark:text-white">
                                        {{ $service['formatted_revenue'] }}
                                    </span>
                                </div>
                                <div style="width: 100%; height: 5px; border-radius: 9999px; background: #F1F5F9; overflow: hidden;" class="dark:bg-slate-800">
                                    <div style="height: 100%; border-radius: 9999px; background: #00C975; width: {{ $service['percentage'] }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p style="font-size: 11px; color: #94A3B8; text-align: center; padding: 1rem 0;">Sin servicios en este período.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Desglose de Canales de Anticipo (Sobrio y elegante) -->
                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid #F1F5F9;" class="dark:border-slate-800">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.4rem;">
                        <span style="font-size: 10px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">
                            Canales de Anticipo
                        </span>
                        <span style="font-size: 10px; color: #94A3B8;">{{ $data['effectiveTotalCount'] }} transacciones</span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div style="padding: 0.5rem 0.75rem; border-radius: 12px; background: #F8FAFC; border: 1px solid #F1F5F9;" class="dark:bg-slate-900 dark:border-slate-800">
                            <div style="font-size: 10px; font-weight: 600; color: #64748B;" class="dark:text-slate-400">Nequi Directo</div>
                            <div style="font-size: 13px; font-weight: 800; color: #0F172A; font-family: monospace; margin-top: 1px;" class="dark:text-white">
                                ${{ number_format($data['nequiRevenue'], 0, ',', '.') }}
                            </div>
                            <div style="font-size: 9px; color: #94A3B8;">{{ $data['nequiCount'] }} pagos</div>
                        </div>

                        <div style="padding: 0.5rem 0.75rem; border-radius: 12px; background: #F8FAFC; border: 1px solid #F1F5F9;" class="dark:bg-slate-900 dark:border-slate-800">
                            <div style="font-size: 10px; font-weight: 600; color: #64748B;" class="dark:text-slate-400">Bold Pasarela</div>
                            <div style="font-size: 13px; font-weight: 800; color: #0F172A; font-family: monospace; margin-top: 1px;" class="dark:text-white">
                                ${{ number_format($data['boldRevenue'], 0, ',', '.') }}
                            </div>
                            <div style="font-size: 9px; color: #94A3B8;">{{ $data['boldCount'] }} pagos</div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-filament-widgets::widget>
