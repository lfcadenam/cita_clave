<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $period = $data['period'];
        $periodTitle = $data['periodTitle'];
        $isCurrent = $data['isCurrentPeriod'];
        $series = $data['chartSeries'];
        $topServices = $data['topServices'];
    @endphp

    <div class="space-y-6 font-['Plus_Jakarta_Sans',system-ui,sans-serif]">

        <!-- ========================================================================= -->
        <!-- 1. BARRA SUPERIOR DE CONTROL & FILTROS (NUVEX MODERN CURVE PILLS)         -->
        <!-- ========================================================================= -->
        <div class="bg-white dark:bg-[#1E293B] rounded-[28px] border border-slate-200/80 dark:border-slate-800 p-4 sm:p-5 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)] flex flex-col md:flex-row items-center justify-between gap-4 transition-all duration-300">
            
            <!-- Título y Período Activo -->
            <div class="flex items-center gap-3.5 w-full md:w-auto">
                <div class="w-11 h-11 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0 shadow-inner">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight">Tablero Estadístico</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 uppercase tracking-wider">
                            {{ $periodTitle }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Métricas consolidadas de rendimiento comercial y operativo</p>
                </div>
            </div>

            <!-- Filtros de Frecuencia y Navegación Temporal -->
            <div class="flex flex-wrap items-center gap-2 w-full md:w-auto justify-end">
                
                <!-- Botonera de Filtro de Frecuencia (Píldoras) -->
                <div class="inline-flex p-1 rounded-2xl bg-slate-100 dark:bg-slate-900/80 border border-slate-200/60 dark:border-slate-800 text-xs font-semibold">
                    <button type="button" 
                            wire:click="setPeriod('week')"
                            class="px-3 py-1.5 rounded-xl transition-all duration-200 {{ $period === 'week' ? 'bg-white dark:bg-[#1E293B] text-emerald-600 dark:text-emerald-400 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        Semanal
                    </button>
                    <button type="button" 
                            wire:click="setPeriod('month')"
                            class="px-3 py-1.5 rounded-xl transition-all duration-200 {{ $period === 'month' ? 'bg-white dark:bg-[#1E293B] text-emerald-600 dark:text-emerald-400 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        Mensual
                    </button>
                    <button type="button" 
                            wire:click="setPeriod('quarter')"
                            class="px-3 py-1.5 rounded-xl transition-all duration-200 {{ $period === 'quarter' ? 'bg-white dark:bg-[#1E293B] text-emerald-600 dark:text-emerald-400 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        Trimestral
                    </button>
                    <button type="button" 
                            wire:click="setPeriod('semester')"
                            class="px-3 py-1.5 rounded-xl transition-all duration-200 {{ $period === 'semester' ? 'bg-white dark:bg-[#1E293B] text-emerald-600 dark:text-emerald-400 shadow-sm font-bold' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' }}">
                        Semestral
                    </button>
                </div>

                <!-- Botones de Navegación Temporal Anterior / Siguiente -->
                <div class="inline-flex items-center gap-1">
                    <button type="button" 
                            wire:click="previousPeriod"
                            title="Período anterior"
                            class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors cursor-pointer text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>

                    @if(!$isCurrent)
                        <button type="button" 
                                wire:click="resetPeriod"
                                class="px-2.5 py-1.5 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-[11px] font-bold hover:bg-emerald-100 transition-colors">
                            Hoy
                        </button>
                    @endif

                    <button type="button" 
                            wire:click="nextPeriod"
                            title="Siguiente período"
                            class="w-9 h-9 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 flex items-center justify-center transition-colors cursor-pointer text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. GRID DE 4 TARJETAS KPI ELEVADAS (NUVEX METRIC CARDS)                   -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            <!-- KPI 1: Facturación Total -->
            <div class="bg-white dark:bg-[#1E293B] rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)] relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Facturación Estimada</span>
                    <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-[#00C975] flex items-center justify-center shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ $data['formattedRevenue'] }}
                </div>
                <div class="flex items-center gap-2 mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
                    @if($data['revenueGrowth'] !== null)
                        <span class="font-bold flex items-center gap-0.5 {{ $data['revenueGrowth'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600' }}">
                            {{ $data['revenueGrowth'] >= 0 ? '↑ +' : '↓ ' }}{{ $data['revenueGrowth'] }}%
                        </span>
                        <span class="text-slate-400 dark:text-slate-500 text-[11px]">vs período anterior</span>
                    @else
                        <span class="text-slate-400 dark:text-slate-500 text-[11px]">Ticket promedio: {{ $data['formattedTicket'] }}</span>
                    @endif
                </div>
            </div>

            <!-- KPI 2: Anticipos Recaudados -->
            <div class="bg-white dark:bg-[#1E293B] rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)] relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Anticipos Recaudados</span>
                    <div class="w-10 h-10 rounded-full bg-teal-50 dark:bg-teal-950/50 text-teal-600 dark:text-teal-400 flex items-center justify-center shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ $data['formattedDeposit'] }}
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Pagos ya asegurados</span>
                    <span class="font-semibold text-teal-700 dark:text-teal-300 text-[11px]">{{ $data['effectiveTotalCount'] }} abonos validados</span>
                </div>
            </div>

            <!-- KPI 3: Saldo Pendiente por Cobrar -->
            <div class="bg-white dark:bg-[#1E293B] rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)] relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Por Recaudar en Local</span>
                    <div class="w-10 h-10 rounded-full bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6H2.25m0 0v8.25m0-8.25a2.25 2.25 0 012.25-2.25h15A2.25 2.25 0 0121.75 6v8.25m-19.5 0H3a.75.75 0 00.75.75v.75m18-9.75v.75a.75.75 0 01-.75.75h-.75m.75 0v8.25m0-8.25a2.25 2.25 0 00-2.25-2.25h-15A2.25 2.25 0 002.25 6v8.25" />
                        </svg>
                    </div>
                </div>
                <div class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
                    {{ $data['formattedPending'] }}
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
                    <span class="text-slate-500 dark:text-slate-400 text-[11px]">Cobro al finalizar servicio</span>
                    <span class="text-amber-700 dark:text-amber-300 font-semibold text-[11px]">{{ $data['totalRevenue'] > 0 ? round(($data['pendingBalance'] / $data['totalRevenue']) * 100) : 0 }}% del total</span>
                </div>
            </div>

            <!-- KPI 4: Volumen de Citas & Cumplimiento -->
            <div class="bg-white dark:bg-[#1E293B] rounded-2xl sm:rounded-3xl border border-slate-200/80 dark:border-slate-800 p-5 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)] relative overflow-hidden transition-all hover:shadow-md">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Citas & Cumplimiento</span>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <span class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">{{ $data['totalAppointments'] }}</span>
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">agendadas</span>
                </div>
                <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 text-xs">
                    <span class="text-emerald-600 dark:text-emerald-400 font-bold text-[11px]">{{ $data['completionRate'] }}% efectivas</span>
                    <span class="text-slate-400 text-[11px]">{{ $data['cancelledCount'] }} canceladas</span>
                </div>
            </div>

        </div>

        <!-- ========================================================================= -->
        <!-- 3. FILA DE ANALÍTICA VISUAL (GRÁFICO TEMPORAL + TOP SERVICIOS)            -->
        <!-- ========================================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- BLOQUE IZQUIERDO (7 Columnas): Gráfico de Evolución con Barras Slim Nuvex -->
            <div class="lg:col-span-7 bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)] flex flex-col justify-between">
                
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Evolución de Ingresos y Citas</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Distribución cronológica en el período seleccionado</p>
                        </div>
                        
                        <!-- Leyenda Minimalista -->
                        <div class="flex items-center gap-3 text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#00C975]"></span>
                                <span class="text-slate-600 dark:text-slate-400 text-[11px]">Ingresos ($)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full bg-[#1E293B] dark:bg-amber-400"></span>
                                <span class="text-slate-600 dark:text-slate-400 text-[11px]">Citas</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contenedor del Gráfico de Barras -->
                    <div class="h-56 pt-6 pb-2 flex items-end justify-between gap-2 sm:gap-4 border-b border-slate-100 dark:border-slate-800">
                        @forelse($series as $bar)
                            <div class="flex-1 flex flex-col items-center h-full justify-end group relative cursor-pointer">
                                
                                <!-- Tooltip Flotante al Hover -->
                                <div class="absolute -top-12 opacity-0 group-hover:opacity-100 transition-opacity bg-slate-900 dark:bg-slate-800 text-white text-[10px] py-1 px-2.5 rounded-xl shadow-lg pointer-events-none whitespace-nowrap z-20">
                                    <p class="font-bold">{{ $bar['label'] }}</p>
                                    <p class="text-emerald-400 font-mono">{{ $bar['formatted_revenue'] }}</p>
                                    <p class="text-slate-300">{{ $bar['count'] }} cita(s)</p>
                                </div>

                                <!-- Contenedor de Barras Duales Slim Rounded -->
                                <div class="w-full flex items-end justify-center gap-1 sm:gap-1.5 h-full max-w-[48px]">
                                    <!-- Barra 1: Ingresos (Esmeralda) -->
                                    <div class="w-2.5 sm:w-3.5 bg-gradient-to-t from-[#00C975] to-[#10B981] rounded-t-lg transition-all duration-500 group-hover:brightness-110"
                                         style="height: {{ $bar['revenue_height'] }}%;">
                                    </div>
                                    <!-- Barra 2: Citas (Pizarra / Ámbar) -->
                                    <div class="w-2.5 sm:w-3.5 bg-slate-800 dark:bg-amber-400 rounded-t-lg transition-all duration-500 group-hover:brightness-110"
                                         style="height: {{ $bar['count_height'] }}%;">
                                    </div>
                                </div>

                                <!-- Etiqueta Inferior -->
                                <span class="text-[10px] sm:text-[11px] font-medium text-slate-500 dark:text-slate-400 mt-2 text-center truncate w-full">
                                    {{ $bar['label'] }}
                                </span>
                            </div>
                        @empty
                            <div class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                Sin registros suficientes en este período.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Resumen de Clientas (Footer del Gráfico) -->
                <div class="grid grid-cols-3 gap-3 pt-4 mt-2">
                    <div class="p-3 rounded-2xl bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800/80 text-center">
                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">Clientas Totales</span>
                        <p class="text-sm sm:text-base font-bold text-slate-900 dark:text-white mt-0.5">{{ $data['totalClientsInPeriod'] }}</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100/60 dark:border-emerald-900/30 text-center">
                        <span class="text-[11px] text-emerald-700 dark:text-emerald-400 font-medium">Nuevas Clientas</span>
                        <p class="text-sm sm:text-base font-bold text-emerald-800 dark:text-emerald-300 mt-0.5">{{ $data['newClientsCount'] }}</p>
                    </div>
                    <div class="p-3 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/20 border border-indigo-100/60 dark:border-indigo-900/30 text-center">
                        <span class="text-[11px] text-indigo-700 dark:text-indigo-400 font-medium">Recurrentes</span>
                        <p class="text-sm sm:text-base font-bold text-indigo-800 dark:text-indigo-300 mt-0.5">{{ $data['recurrentClientsCount'] }}</p>
                    </div>
                </div>

            </div>

            <!-- BLOQUE DERECHO (5 Columnas): Top Servicios & Métodos de Abono -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Tarjeta: Top 5 Tratamientos Estrella -->
                <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)]">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tratamientos Más Solicitados</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ranking por volumen y facturación</p>
                        </div>
                        <span class="text-[11px] font-semibold text-slate-400">Top 5</span>
                    </div>

                    <div class="space-y-3.5">
                        @forelse($topServices as $service)
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="truncate max-w-[200px]">
                                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ $service['name'] }}</span>
                                        <span class="text-[10px] text-slate-400 block">{{ $service['count'] }} citas agendadas</span>
                                    </div>
                                    <span class="font-mono font-bold text-slate-900 dark:text-white shrink-0">
                                        {{ $service['formatted_revenue'] }}
                                    </span>
                                </div>
                                <!-- Barra de Progreso Relativo -->
                                <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-[#00C975] to-[#10B981] rounded-full transition-all duration-500"
                                         style="width: {{ $service['percentage'] }}%;"></div>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-4">No hay servicios registrados en este período.</p>
                        @endforelse
                    </div>
                </div>

                <!-- Tarjeta: Métodos de Pago y Desglose de Operación -->
                <div class="bg-white dark:bg-[#1E293B] rounded-3xl border border-slate-200/80 dark:border-slate-800 p-6 shadow-[0_10px_25px_-5px_rgba(0,0,0,0.04)]">
                    <h4 class="text-sm font-bold text-slate-900 dark:text-white mb-3.5">Métodos de Abono</h4>

                    <div class="grid grid-cols-2 gap-3">
                        <!-- Nequi Directo -->
                        <div class="p-3.5 rounded-2xl bg-purple-50/70 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-purple-900 dark:text-purple-300">Nequi Directo</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-purple-200 text-purple-800 uppercase">0% Fee</span>
                            </div>
                            <p class="text-base font-bold text-purple-950 dark:text-purple-100 font-mono">
                                ${{ number_format($data['nequiRevenue'], 0, ',', '.') }}
                            </p>
                            <span class="text-[10px] text-purple-700 dark:text-purple-400">{{ $data['nequiCount'] }} transferencias</span>
                        </div>

                        <!-- Bold Online -->
                        <div class="p-3.5 rounded-2xl bg-teal-50/70 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/40">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-teal-900 dark:text-teal-300">Bold Pasarela</span>
                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-teal-200 text-teal-800 uppercase">PSE / TC</span>
                            </div>
                            <p class="text-base font-bold text-teal-950 dark:text-teal-100 font-mono">
                                ${{ number_format($data['boldRevenue'], 0, ',', '.') }}
                            </p>
                            <span class="text-[10px] text-teal-700 dark:text-teal-400">{{ $data['boldCount'] }} transacciones</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-filament-widgets::widget>
