<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $period = $data['period'];
        $periodTitle = $data['periodTitle'];
        $isCurrent = $data['isCurrentPeriod'];
        $series = $data['chartSeries'];
        $topServices = $data['topServices'];
    @endphp

    <style>
        .nuvex-analytics-container {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            box-sizing: border-box;
        }

        .nuvex-analytics-card {
            background: #FFFFFF;
            border-radius: 24px;
            border: 1px solid #E2E8F0;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04), 0 4px 10px -2px rgba(0, 0, 0, 0.02);
            position: relative;
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        .dark .nuvex-analytics-card {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
        }

        /* Barra de control superior */
        .nuvex-analytics-topbar {
            background: #FFFFFF;
            border-radius: 28px;
            border: 1px solid #E2E8F0;
            padding: 1rem 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .dark .nuvex-analytics-topbar {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-icon-badge {
            width: 42px;
            height: 42px;
            min-width: 42px;
            max-width: 42px;
            min-height: 42px;
            max-height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nuvex-icon-badge svg {
            width: 20px !important;
            height: 20px !important;
            max-width: 20px !important;
            max-height: 20px !important;
            min-width: 20px !important;
            min-height: 20px !important;
            display: block;
        }

        /* Píldoras de filtro */
        .nuvex-pill-group {
            display: inline-flex;
            background: #F1F5F9;
            padding: 4px;
            border-radius: 18px;
            border: 1px solid #E2E8F0;
            gap: 4px;
        }

        .dark .nuvex-pill-group {
            background: #0F172A;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-pill-btn {
            padding: 6px 14px;
            border-radius: 14px;
            font-size: 12px;
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

        .nuvex-pill-btn:hover {
            color: #0F172A;
        }

        .dark .nuvex-pill-btn:hover {
            color: #FFFFFF;
        }

        .nuvex-pill-btn.active {
            background: #FFFFFF;
            color: #0d9488;
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .dark .nuvex-pill-btn.active {
            background: #1E293B;
            color: #14b8a6;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .nuvex-nav-circle-btn {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: #F1F5F9;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #334155;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nuvex-nav-circle-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        .dark .nuvex-nav-circle-btn {
            background: #334155;
            color: #F8FAFC;
        }

        .dark .nuvex-nav-circle-btn:hover {
            background: #475569;
        }

        .nuvex-nav-circle-btn svg {
            width: 14px !important;
            height: 14px !important;
            max-width: 14px !important;
            max-height: 14px !important;
        }

        /* Grid de KPIs */
        .nuvex-kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 1rem;
            width: 100%;
        }

        /* Gráfico de barras */
        .nuvex-chart-canvas {
            height: 200px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 8px;
            padding: 1rem 0.5rem 0.5rem 0.5rem;
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
            gap: 4px;
            width: 100%;
            height: 100%;
        }

        .nuvex-bar-rev {
            width: 12px;
            max-width: 16px;
            background: linear-gradient(180deg, #0d9488 0%, #00C975 100%);
            border-radius: 6px 6px 0 0;
            transition: height 0.4s ease;
        }

        .nuvex-bar-count {
            width: 12px;
            max-width: 16px;
            background: #1E293B;
            border-radius: 6px 6px 0 0;
            transition: height 0.4s ease;
        }

        .dark .nuvex-bar-count {
            background: #F59E0B;
        }

        .nuvex-bar-tooltip {
            position: absolute;
            top: -55px;
            left: 50%;
            transform: translateX(-50%);
            background: #0F172A;
            color: #FFFFFF;
            padding: 4px 8px;
            border-radius: 10px;
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
            margin-top: 8px;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .dark .nuvex-bar-label {
            color: #94A3B8;
        }
    </style>

    <div class="nuvex-analytics-container">

        <!-- ========================================================================= -->
        <!-- 1. BARRA SUPERIOR DE CONTROL & FILTROS (PILLS INTERACTIVAS)               -->
        <!-- ========================================================================= -->
        <div class="nuvex-analytics-topbar">
            
            <!-- Título y Período Activo -->
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div class="nuvex-icon-badge" style="background: #e6f7f2; color: #0d9488;">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-size: 15px; font-weight: 700; color: #0F172A;" class="dark:text-white">Tablero Estadístico</span>
                        <span style="font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 9999px; background: #e6f7f2; color: #0d9488; text-transform: uppercase; letter-spacing: 0.05em;">
                            {{ $periodTitle }}
                        </span>
                    </div>
                    <span style="font-size: 11px; color: #64748B;" class="dark:text-slate-400">Rendimiento comercial y operativo en tiempo real</span>
                </div>
            </div>

            <!-- Controles de Período y Botonera -->
            <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                
                <!-- Píldoras de Frecuencia -->
                <div class="nuvex-pill-group">
                    <button type="button" 
                            wire:click="setPeriod('week')"
                            class="nuvex-pill-btn {{ $period === 'week' ? 'active' : '' }}">
                        Semanal
                    </button>
                    <button type="button" 
                            wire:click="setPeriod('month')"
                            class="nuvex-pill-btn {{ $period === 'month' ? 'active' : '' }}">
                        Mensual
                    </button>
                    <button type="button" 
                            wire:click="setPeriod('quarter')"
                            class="nuvex-pill-btn {{ $period === 'quarter' ? 'active' : '' }}">
                        Trimestral
                    </button>
                    <button type="button" 
                            wire:click="setPeriod('semester')"
                            class="nuvex-pill-btn {{ $period === 'semester' ? 'active' : '' }}">
                        Semestral
                    </button>
                </div>

                <!-- Navegación Temporal Anterior / Siguiente -->
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" 
                            wire:click="previousPeriod"
                            title="Período anterior"
                            class="nuvex-nav-circle-btn">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    @if(!$isCurrent)
                        <button type="button" 
                                wire:click="resetPeriod"
                                style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; background: #e6f7f2; color: #0d9488; border: none; cursor: pointer;">
                            Hoy
                        </button>
                    @endif

                    <button type="button" 
                            wire:click="nextPeriod"
                            title="Siguiente período"
                            class="nuvex-nav-circle-btn">
                        <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. GRID DE 4 TARJETAS KPI ELEVADAS                                        -->
        <!-- ========================================================================= -->
        <div class="nuvex-kpi-grid">

            <!-- KPI 1: Facturación Estimada -->
            <div class="nuvex-analytics-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">Facturación Estimada</span>
                    <div class="nuvex-icon-badge" style="background: #e6f7f2; color: #00C975;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div style="font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.02em;" class="dark:text-white">
                    {{ $data['formattedRevenue'] }}
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #F1F5F9; font-size: 11px;" class="dark:border-slate-800">
                    @if($data['revenueGrowth'] !== null)
                        <span style="font-weight: 700; color: {{ $data['revenueGrowth'] >= 0 ? '#0d9488' : '#e11d48' }};">
                            {{ $data['revenueGrowth'] >= 0 ? '↑ +' : '↓ ' }}{{ $data['revenueGrowth'] }}%
                        </span>
                        <span style="color: #94A3B8;">vs período anterior</span>
                    @else
                        <span style="color: #94A3B8;">Ticket prom: {{ $data['formattedTicket'] }}</span>
                    @endif
                </div>
            </div>

            <!-- KPI 2: Anticipos Recaudados -->
            <div class="nuvex-analytics-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">Anticipos Recaudados</span>
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
                    <span style="color: #64748B;" class="dark:text-slate-400">Total asegurado</span>
                    <span style="font-weight: 700; color: #0284c7;">{{ $data['effectiveTotalCount'] }} abonos</span>
                </div>
            </div>

            <!-- KPI 3: Por Recaudar en Local -->
            <div class="nuvex-analytics-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">Por Recaudar en Local</span>
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
                    <span style="color: #64748B;" class="dark:text-slate-400">Saldo tras atención</span>
                    <span style="font-weight: 700; color: #d97706;">{{ $data['totalRevenue'] > 0 ? round(($data['pendingBalance'] / $data['totalRevenue']) * 100) : 0 }}% pendiente</span>
                </div>
            </div>

            <!-- KPI 4: Total de Citas & Cumplimiento -->
            <div class="nuvex-analytics-card">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                    <span style="font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em;" class="dark:text-slate-400">Citas & Cumplimiento</span>
                    <div class="nuvex-icon-badge" style="background: #e0e7ff; color: #4f46e5;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
                <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                    <span style="font-size: 22px; font-weight: 800; color: #0F172A; letter-spacing: -0.02em;" class="dark:text-white">{{ $data['totalAppointments'] }}</span>
                    <span style="font-size: 11px; font-weight: 600; color: #64748B;" class="dark:text-slate-400">citas registradas</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #F1F5F9; font-size: 11px;" class="dark:border-slate-800">
                    <span style="font-weight: 700; color: #0d9488;">{{ $data['completionRate'] }}% efectivas</span>
                    <span style="color: #94A3B8;">{{ $data['cancelledCount'] }} canceladas</span>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- 3. GRÁFICO VISUAL DE EVOLUCIÓN + TOP SERVICIOS                            -->
        <!-- ========================================================================= -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.25rem; width: 100%;">

            <!-- Bloque Gráfico de Barras -->
            <div class="nuvex-analytics-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0;" class="dark:text-white">Evolución de Ingresos y Citas</h4>
                            <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;" class="dark:text-slate-400">Distribución cronológica del período</p>
                        </div>
                        
                        <!-- Leyenda -->
                        <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 11px;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: #00C975; display: inline-block;"></span>
                                <span style="color: #64748B;" class="dark:text-slate-400">Ingresos ($)</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span style="width: 10px; height: 10px; border-radius: 50%; background: #1E293B; display: inline-block;" class="dark:bg-amber-400"></span>
                                <span style="color: #64748B;" class="dark:text-slate-400">Citas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor del Gráfico -->
                    <div class="nuvex-chart-canvas">
                        @forelse($series as $bar)
                            <div class="nuvex-bar-column">
                                <!-- Tooltip -->
                                <div class="nuvex-bar-tooltip">
                                    <div style="font-weight: 700;">{{ $bar['label'] }}</div>
                                    <div style="color: #34d399; font-family: monospace;">{{ $bar['formatted_revenue'] }}</div>
                                    <div style="color: #cbd5e1;">{{ $bar['count'] }} cita(s)</div>
                                </div>

                                <!-- Barras duales -->
                                <div class="nuvex-bar-pair">
                                    <div class="nuvex-bar-rev" style="height: {{ $bar['revenue_height'] }}%;"></div>
                                    <div class="nuvex-bar-count" style="height: {{ $bar['count_height'] }}%;"></div>
                                </div>

                                <div class="nuvex-bar-label">{{ $bar['label'] }}</div>
                            </div>
                        @empty
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #94A3B8;">
                                Sin registros en este período.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Footer de Clientas -->
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem; margin-top: 1rem; padding-top: 0.75rem;">
                    <div style="padding: 0.6rem; border-radius: 16px; background: #F8FAFC; border: 1px solid #F1F5F9; text-align: center;" class="dark:bg-slate-900 dark:border-slate-800">
                        <span style="font-size: 10px; font-weight: 600; color: #64748B;" class="dark:text-slate-400">Clientas Totales</span>
                        <div style="font-size: 14px; font-weight: 800; color: #0F172A; margin-top: 2px;" class="dark:text-white">{{ $data['totalClientsInPeriod'] }}</div>
                    </div>
                    <div style="padding: 0.6rem; border-radius: 16px; background: #e6f7f2; border: 1px solid #ccfbf1; text-align: center;" class="dark:bg-emerald-950/30 dark:border-emerald-900/40">
                        <span style="font-size: 10px; font-weight: 600; color: #0f766e;" class="dark:text-emerald-400">Nuevas</span>
                        <div style="font-size: 14px; font-weight: 800; color: #115e59; margin-top: 2px;" class="dark:text-emerald-300">{{ $data['newClientsCount'] }}</div>
                    </div>
                    <div style="padding: 0.6rem; border-radius: 16px; background: #e0e7ff; border: 1px solid #c7d2fe; text-align: center;" class="dark:bg-indigo-950/30 dark:border-indigo-900/40">
                        <span style="font-size: 10px; font-weight: 600; color: #4338ca;" class="dark:text-indigo-400">Recurrentes</span>
                        <div style="font-size: 14px; font-weight: 800; color: #3730a3; margin-top: 2px;" class="dark:text-indigo-300">{{ $data['recurrentClientsCount'] }}</div>
                    </div>
                </div>
            </div>

            <!-- Bloque Top Tratamientos & Métodos de Abono -->
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                
                <!-- Top 5 Tratamientos -->
                <div class="nuvex-analytics-card">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem;">
                        <div>
                            <h4 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0;" class="dark:text-white">Tratamientos Más Solicitados</h4>
                            <p style="font-size: 11px; color: #64748B; margin: 2px 0 0 0;" class="dark:text-slate-400">Ranking por volumen y facturación</p>
                        </div>
                        <span style="font-size: 10px; font-weight: 700; color: #94A3B8;">TOP 5</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        @forelse($topServices as $service)
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px;">
                                    <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;">
                                        <span style="font-weight: 700; color: #1E293B;" class="dark:text-slate-200">{{ $service['name'] }}</span>
                                        <span style="font-size: 10px; color: #94A3B8; display: block;">{{ $service['count'] }} citas</span>
                                    </div>
                                    <span style="font-weight: 800; color: #0F172A; font-family: monospace;" class="dark:text-white">
                                        {{ $service['formatted_revenue'] }}
                                    </span>
                                </div>
                                <div style="width: 100%; height: 6px; border-radius: 9999px; background: #F1F5F9; overflow: hidden;" class="dark:bg-slate-800">
                                    <div style="height: 100%; border-radius: 9999px; background: linear-gradient(90deg, #00C975, #10B981); width: {{ $service['percentage'] }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p style="font-size: 11px; color: #94A3B8; text-align: center; padding: 1rem 0;">No hay servicios registrados en este período.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Desglose de Métodos de Abono -->
                <div class="nuvex-analytics-card">
                    <h4 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0 0 0.75rem 0;" class="dark:text-white">Métodos de Abono</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <!-- Nequi -->
                        <div style="padding: 0.75rem; border-radius: 16px; background: #faf5ff; border: 1px solid #f3e8ff;" class="dark:bg-purple-950/20 dark:border-purple-900/40">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                                <span style="font-size: 11px; font-weight: 700; color: #6b21a8;" class="dark:text-purple-300">Nequi Directo</span>
                                <span style="font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 9999px; background: #e9d5ff; color: #6b21a8;">0% Fee</span>
                            </div>
                            <div style="font-size: 16px; font-weight: 800; color: #581c87; font-family: monospace;" class="dark:text-purple-100">
                                ${{ number_format($data['nequiRevenue'], 0, ',', '.') }}
                            </div>
                            <span style="font-size: 10px; color: #7e22ce;" class="dark:text-purple-400">{{ $data['nequiCount'] }} transferencias</span>
                        </div>

                        <!-- Bold -->
                        <div style="padding: 0.75rem; border-radius: 16px; background: #f0fdfa; border: 1px solid #ccfbf1;" class="dark:bg-teal-950/20 dark:border-teal-900/40">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2px;">
                                <span style="font-size: 11px; font-weight: 700; color: #0f766e;" class="dark:text-teal-300">Bold Pasarela</span>
                                <span style="font-size: 9px; font-weight: 700; padding: 1px 6px; border-radius: 9999px; background: #99f6e4; color: #115e59;">PSE / TC</span>
                            </div>
                            <div style="font-size: 16px; font-weight: 800; color: #134e4a; font-family: monospace;" class="dark:text-teal-100">
                                ${{ number_format($data['boldRevenue'], 0, ',', '.') }}
                            </div>
                            <span style="font-size: 10px; color: #0f766e;" class="dark:text-teal-400">{{ $data['boldCount'] }} transacciones</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-filament-widgets::widget>
