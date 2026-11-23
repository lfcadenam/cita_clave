<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $totalTenants = $data['totalTenants'];
        $activeTenants = $data['activeTenants'];
        $totalAppointments = $data['totalAppointments'];
        $confirmedAppointments = $data['confirmedAppointments'];
        $totalDeposits = $data['totalDeposits'];
        $formattedDeposits = $data['formattedDeposits'];
        $totalServices = $data['totalServices'];
        $totalUsers = $data['totalUsers'];
        $tenants = $data['tenants'];
        $dbConnected = $data['dbConnected'];
        $activeGateway = $data['activeGateway'];
        $serverTime = $data['serverTime'];
        $recentAppointments = $data['recentAppointments'];
    @endphp

    <div class="nuvex-sa-dashboard">
        
        {{-- =========================================================================
             1. ENCABEZADO SUPERADMIN NUVEX SAAS (HERO BANNER)
             ========================================================================= --}}
        <div class="nuvex-sa-banner">
            <div class="nuvex-sa-banner-text">
                <div class="nuvex-sa-badge-top">
                    <span class="nuvex-sa-dot"></span>
                    <span>NUVEX SAAS CORE • CONTROL MULTI-TENANT</span>
                </div>
                <h1 class="nuvex-sa-title">Escritorio Global SaaS</h1>
                <p class="nuvex-sa-subtitle">
                    Supervisión integral de empresas, actividad de reservas y pasarelas de pago en tiempo real.
                </p>
            </div>

            <div class="nuvex-sa-header-actions">
                <div class="nuvex-sa-server-time">
                    <span class="nuvex-sa-dot-pulse"></span>
                    <span>Servidor Activo · {{ $serverTime }}</span>
                </div>
                <a href="{{ route('filament.superadmin.resources.tenants.create') }}" class="nuvex-sa-btn-primary">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Nuevo Salón</span>
                </a>
            </div>
        </div>

        {{-- =========================================================================
             2. GRID DE 4 TARJETAS KPI (NUVEX MODERN CURVE UI)
             ========================================================================= --}}
        <div class="nuvex-sa-kpi-grid">
            
            {{-- KPI 1: Salones & Empresas --}}
            <div class="nuvex-sa-kpi-card">
                <div class="nuvex-sa-kpi-head">
                    <div>
                        <span class="nuvex-sa-kpi-label">Salones & Empresas</span>
                        <div class="nuvex-sa-kpi-value">{{ $activeTenants }} / {{ $totalTenants }}</div>
                    </div>
                    <div class="nuvex-sa-kpi-badge badge-emerald">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div class="nuvex-sa-kpi-foot">
                    <span class="nuvex-sa-kpi-sub">Estudios Activos</span>
                    <span class="nuvex-sa-tag-emerald">
                        {{ $totalTenants > 0 ? round(($activeTenants / $totalTenants) * 100) : 0 }}% Operativos
                    </span>
                </div>
            </div>

            {{-- KPI 2: Citas Gestionadas --}}
            <div class="nuvex-sa-kpi-card">
                <div class="nuvex-sa-kpi-head">
                    <div>
                        <span class="nuvex-sa-kpi-label">Citas Gestionadas</span>
                        <div class="nuvex-sa-kpi-value">{{ number_format($totalAppointments, 0, ',', '.') }}</div>
                    </div>
                    <div class="nuvex-sa-kpi-badge badge-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
                <div class="nuvex-sa-kpi-foot">
                    <span class="nuvex-sa-kpi-sub">Confirmadas</span>
                    <span class="nuvex-sa-tag-cyan">
                        {{ $confirmedAppointments }} En local
                    </span>
                </div>
            </div>

            {{-- KPI 3: Volumen de Abonos --}}
            <div class="nuvex-sa-kpi-card">
                <div class="nuvex-sa-kpi-head">
                    <div>
                        <span class="nuvex-sa-kpi-label">Abonos Recibidos</span>
                        <div class="nuvex-sa-kpi-value nuvex-val-money">{{ $formattedDeposits }}</div>
                    </div>
                    <div class="nuvex-sa-kpi-badge badge-amber">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="nuvex-sa-kpi-foot">
                    <span class="nuvex-sa-kpi-sub">Canales de Pago</span>
                    <span class="nuvex-sa-tag-amber">
                        Nequi & Pasarela
                    </span>
                </div>
            </div>

            {{-- KPI 4: Servicios en Catálogo --}}
            <div class="nuvex-sa-kpi-card">
                <div class="nuvex-sa-kpi-head">
                    <div>
                        <span class="nuvex-sa-kpi-label">Servicios en Catálogo</span>
                        <div class="nuvex-sa-kpi-value">{{ $totalServices }}</div>
                    </div>
                    <div class="nuvex-sa-kpi-badge badge-violet">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                        </svg>
                    </div>
                </div>
                <div class="nuvex-sa-kpi-foot">
                    <span class="nuvex-sa-kpi-sub">Tratamientos</span>
                    <span class="nuvex-sa-tag-violet">
                        Activos en Portal
                    </span>
                </div>
            </div>

        </div>

        {{-- =========================================================================
             3. FILA MEDIA: SPOTLIGHT INFRAESTRUCTURA & SALONES REGISTRADOS
             ========================================================================= --}}
        <div class="nuvex-sa-mid-grid">
            
            {{-- Tarjeta 1: Spotlight Hero de Infraestructura (Charcoal / Slate) --}}
            <div class="nuvex-sa-health-card">
                <div>
                    <div class="nuvex-sa-health-head">
                        <span class="nuvex-sa-health-tag">
                            <span class="nuvex-sa-dot-emerald"></span>
                            NUVEX CLOUD HEALTH
                        </span>
                        <span class="nuvex-sa-version">v2.4 SaaS</span>
                    </div>

                    <h3 class="nuvex-sa-health-title">Estado del Ecosistema</h3>
                    <p class="nuvex-sa-health-desc">
                        Monitoreo continuo de servicios críticos, pasarelas y microservicios de notificación en tiempo real.
                    </p>

                    <div class="nuvex-sa-service-list">
                        <div class="nuvex-sa-service-row">
                            <div class="nuvex-sa-srv-left">
                                <span class="nuvex-sa-srv-dot {{ $dbConnected ? 'srv-dot-ok' : 'srv-dot-err' }}"></span>
                                <span class="nuvex-sa-srv-name">Base de Datos MySQL</span>
                            </div>
                            <span class="nuvex-sa-srv-val {{ $dbConnected ? 'srv-val-ok' : 'srv-val-err' }}">
                                {{ $dbConnected ? 'Conectada (PDO OK)' : 'Error Conexión' }}
                            </span>
                        </div>

                        <div class="nuvex-sa-service-row">
                            <div class="nuvex-sa-srv-left">
                                <span class="nuvex-sa-srv-dot srv-dot-ok"></span>
                                <span class="nuvex-sa-srv-name">Notificador WhatsApp</span>
                            </div>
                            <span class="nuvex-sa-srv-val srv-val-ok">
                                Meta Cloud API
                            </span>
                        </div>

                        <div class="nuvex-sa-service-row">
                            <div class="nuvex-sa-srv-left">
                                <span class="nuvex-sa-srv-dot srv-dot-ok"></span>
                                <span class="nuvex-sa-srv-name">Recordatorios 24h Cron</span>
                            </div>
                            <span class="nuvex-sa-srv-val">
                                Programado Diario
                            </span>
                        </div>
                    </div>
                </div>

                <div class="nuvex-sa-health-foot">
                    <span>Aislamiento Multi-Tenant</span>
                    <span class="nuvex-sa-bold">100% Blindado</span>
                </div>
            </div>

            {{-- Tarjeta 2: Directorio de Salones en Modo Cards --}}
            <div class="nuvex-sa-tenants-box">
                <div>
                    <div class="nuvex-sa-box-head">
                        <div>
                            <h3 class="nuvex-sa-box-title">Empresas & Salones Registrados</h3>
                            <p class="nuvex-sa-box-subtitle">Estudios de belleza operando bajo la plataforma Nuvex Cita Clave</p>
                        </div>
                        <a href="{{ route('filament.superadmin.resources.tenants.index') }}" class="nuvex-sa-link-all">
                            <span>Ver catálogo completo</span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>

                    {{-- Cards Grid de Salones dentro del Widget --}}
                    <div class="nuvex-sa-tenants-subgrid">
                        @forelse($tenants as $tenant)
                            <div class="nuvex-sa-mini-card">
                                <div>
                                    <div class="nuvex-sa-mc-head">
                                        <div class="nuvex-sa-mc-brand">
                                            <div class="nuvex-sa-mc-avatar">
                                                @if($tenant->logo_path && file_exists(public_path('storage/' . $tenant->logo_path)))
                                                    <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}">
                                                @else
                                                    <span>{{ strtoupper(substr($tenant->name, 0, 2)) }}</span>
                                                @endif
                                            </div>
                                            <div class="nuvex-sa-mc-meta">
                                                <h4 class="nuvex-sa-mc-title" title="{{ $tenant->name }}">
                                                    {{ $tenant->name }}
                                                </h4>
                                                <p class="nuvex-sa-mc-city">
                                                    {{ $tenant->city ?: 'Bogotá, Colombia' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div>
                                            @if($tenant->is_active)
                                                <span class="nuvex-pill-active">
                                                    <span class="nuvex-dot-active"></span> Activo
                                                </span>
                                            @else
                                                <span class="nuvex-pill-paused">
                                                    <span class="nuvex-dot-paused"></span> Pausado
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="nuvex-sa-mc-chips">
                                        <div class="nuvex-sa-mc-chip">
                                            <span class="nuvex-sa-chip-label">Plan SaaS</span>
                                            <span class="nuvex-sa-chip-val nuvex-val-highlight">{{ $tenant->plan_name ?: 'Pro Salon' }}</span>
                                        </div>
                                        <div class="nuvex-sa-mc-chip">
                                            <span class="nuvex-sa-chip-label">Citas Totales</span>
                                            <span class="nuvex-sa-chip-val">{{ $tenant->appointments_count ?? 0 }} citas</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="nuvex-sa-mc-foot">
                                    <span class="nuvex-sa-mc-slug">/{{ $tenant->slug }}</span>
                                    <div class="nuvex-sa-mc-btns">
                                        <a href="{{ url('/' . $tenant->slug) }}" target="_blank" class="nuvex-btn-web">
                                            <span>Web</span>
                                        </a>
                                        <a href="{{ route('filament.superadmin.resources.tenants.edit', ['record' => $tenant->id]) }}" class="nuvex-btn-edit">
                                            <span>Gestionar</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="nuvex-sa-empty">
                                No se han registrado empresas aún.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="nuvex-sa-box-foot">
                    <span>Directorio Activo</span>
                    <span>Total: {{ $totalTenants }} Salón(es) en catálogo</span>
                </div>
            </div>

        </div>

        {{-- =========================================================================
             4. ACTIVIDAD RECIENTE DE RESERVAS (RED MULTI-TENANT)
             ========================================================================= --}}
        <div class="nuvex-sa-recent-box">
            <div class="nuvex-sa-box-head">
                <div>
                    <h3 class="nuvex-sa-box-title">Actividad Reciente de Reservas</h3>
                    <p class="nuvex-sa-box-subtitle">Flujo en tiempo real de citas agendadas en cualquier estudio de la red</p>
                </div>
                <div class="nuvex-sa-server-time">
                    <span class="nuvex-sa-dot-pulse"></span>
                    <span>En Vivo</span>
                </div>
            </div>

            <div class="nuvex-sa-table-wrap">
                <table class="nuvex-sa-table">
                    <thead>
                        <tr>
                            <th>Salón / Estudio</th>
                            <th>Cliente</th>
                            <th>Tratamiento</th>
                            <th>Abono</th>
                            <th>Fecha & Hora</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAppointments as $apt)
                            <tr>
                                <td>
                                    <div class="nuvex-sa-cell-salon">
                                        <span class="nuvex-sa-mini-avatar">{{ strtoupper(substr($apt->tenant?->name ?? 'CC', 0, 2)) }}</span>
                                        <span class="nuvex-sa-salon-name">{{ $apt->tenant?->name ?? 'Salón Nuvex' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div class="nuvex-sa-cell-client">
                                        <span class="nuvex-sa-client-name">{{ $apt->customer_name }}</span>
                                        <span class="nuvex-sa-client-phone">{{ $apt->customer_phone }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="nuvex-sa-service-name">{{ $apt->service?->name ?? 'Servicio Personalizado' }}</span>
                                </td>
                                <td>
                                    <span class="nuvex-sa-price-val">${{ number_format($apt->deposit_paid ?? 0, 0, ',', '.') }}</span>
                                </td>
                                <td>
                                    <span class="nuvex-sa-date-val">{{ \Carbon\Carbon::parse($apt->appointment_date)->format('d/m/Y') }} · {{ substr($apt->start_time, 0, 5) }}</span>
                                </td>
                                <td>
                                    @if($apt->status === 'confirmed')
                                        <span class="nuvex-pill-active"><span class="nuvex-dot-active"></span> Confirmada</span>
                                    @elseif($apt->status === 'completed')
                                        <span class="nuvex-pill-active" style="background:#EFF6FF;color:#1D4ED8;border-color:#BFDBFE;"><span class="nuvex-dot-active" style="background:#2563EB;"></span> Completada</span>
                                    @elseif($apt->status === 'cancelled')
                                        <span class="nuvex-pill-paused"><span class="nuvex-dot-paused"></span> Cancelada</span>
                                    @else
                                        <span class="nuvex-pill-active" style="background:#FFFBEB;color:#B45309;border-color:#FDE68A;"><span class="nuvex-dot-active" style="background:#F59E0B;"></span> Pendiente</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="nuvex-sa-empty">
                                    No hay reservas registradas recientemente en la plataforma.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- =========================================================================
             5. ACCESOS DIRECTOS NUVEX SUPERADMIN
             ========================================================================= --}}
        <div class="nuvex-sa-shortcuts-grid">
            
            <a href="{{ route('filament.superadmin.resources.tenants.index') }}" class="nuvex-sa-shortcut">
                <div class="nuvex-sa-sc-icon sc-emerald">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div>
                    <span class="nuvex-sa-sc-title">Salones & Empresas</span>
                    <span class="nuvex-sa-sc-sub">Crear y auditar estudios</span>
                </div>
            </a>

            <a href="{{ route('filament.superadmin.resources.super-admin-users.index') }}" class="nuvex-sa-shortcut">
                <div class="nuvex-sa-sc-icon sc-cyan">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <span class="nuvex-sa-sc-title">Usuarios de Plataforma</span>
                    <span class="nuvex-sa-sc-sub">Roles y accesos globales</span>
                </div>
            </a>

            <a href="{{ route('filament.superadmin.pages.plantillas-correo') }}" class="nuvex-sa-shortcut">
                <div class="nuvex-sa-sc-icon sc-amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <span class="nuvex-sa-sc-title">Plantillas de Correo</span>
                    <span class="nuvex-sa-sc-sub">Editor y remitentes SMTP</span>
                </div>
            </a>

            <div class="nuvex-sa-shortcut">
                <div class="nuvex-sa-sc-icon sc-violet">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <span class="nuvex-sa-sc-title">Infraestructura Nuvex</span>
                    <span class="nuvex-sa-sc-sub">{{ $totalUsers }} cuentas registradas</span>
                </div>
            </div>

        </div>

        {{-- =========================================================================
             ESTILOS NUVEX MODERN CURVE UI (SCOPED DENTRO DEL WIDGET)
             ========================================================================= --}}
        <style>
            .nuvex-sa-dashboard {
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
                display: flex;
                flex-direction: column;
                gap: 22px;
                width: 100%;
                box-sizing: border-box;
            }

            .nuvex-sa-dashboard svg {
                box-sizing: border-box;
            }

            /* 1. Header Banner */
            .nuvex-sa-banner {
                background: #FFFFFF;
                border-radius: 24px;
                padding: 24px 28px;
                border: 1px solid #EFEAE6;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.035), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 16px;
                box-sizing: border-box;
            }

            @media (min-width: 768px) {
                .nuvex-sa-banner {
                    flex-direction: row;
                    align-items: center;
                }
            }

            .nuvex-sa-badge-top {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 4px 12px;
                border-radius: 9999px;
                background: #E6F7F2;
                color: #0D9488;
                font-size: 11px;
                font-weight: 700;
                letter-spacing: 0.04em;
                border: 1px solid #A7F3D0;
                margin-bottom: 8px;
            }

            .nuvex-sa-dot {
                width: 7px;
                height: 7px;
                border-radius: 9999px;
                background: #00C975;
                flex-shrink: 0;
            }

            .nuvex-sa-title {
                font-size: 24px;
                font-weight: 800;
                color: #1E293B;
                margin: 0;
                letter-spacing: -0.02em;
                line-height: 1.2;
            }

            @media (min-width: 640px) {
                .nuvex-sa-title {
                    font-size: 28px;
                }
            }

            .nuvex-sa-subtitle {
                font-size: 13px;
                color: #64748B;
                margin: 5px 0 0 0;
                font-weight: 500;
            }

            .nuvex-sa-header-actions {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .nuvex-sa-server-time {
                padding: 8px 14px;
                border-radius: 9999px;
                background: #F8FAFC;
                border: 1px solid #CBD5E1;
                font-size: 11.5px;
                color: #475569;
                font-weight: 600;
                display: flex;
                align-items: center;
                gap: 8px;
                white-space: nowrap;
            }

            .nuvex-sa-dot-pulse {
                width: 8px;
                height: 8px;
                border-radius: 9999px;
                background: #10B981;
                flex-shrink: 0;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
            }

            .nuvex-sa-btn-primary {
                display: inline-flex;
                align-items: center;
                gap: 8px;
                padding: 9px 20px;
                border-radius: 9999px;
                background: #00C975 !important;
                color: #FFFFFF !important;
                font-size: 12.5px;
                font-weight: 700;
                text-decoration: none;
                border: none;
                box-shadow: 0 4px 14px rgba(0, 201, 117, 0.28);
                transition: all 0.2s ease;
                white-space: nowrap;
            }

            .nuvex-sa-btn-primary:hover {
                background: #00B066 !important;
                transform: translateY(-1px);
                box-shadow: 0 6px 18px rgba(0, 201, 117, 0.38);
            }

            .nuvex-sa-btn-primary svg {
                width: 15px !important;
                height: 15px !important;
                min-width: 15px !important;
                min-height: 15px !important;
            }

            /* 2. KPI Cards Grid */
            .nuvex-sa-kpi-grid {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
                gap: 18px;
                width: 100%;
                box-sizing: border-box;
            }

            @media (min-width: 640px) {
                .nuvex-sa-kpi-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (min-width: 1024px) {
                .nuvex-sa-kpi-grid {
                    grid-template-columns: repeat(4, 1fr);
                }
            }

            .nuvex-sa-kpi-card {
                background: #FFFFFF;
                border-radius: 24px;
                padding: 22px 24px;
                border: 1px solid #EFEAE6;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.035), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
                box-sizing: border-box;
            }

            .nuvex-sa-kpi-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 14px 28px -6px rgba(0, 0, 0, 0.06);
                border-color: #A7F3D0;
            }

            .nuvex-sa-kpi-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 16px;
            }

            .nuvex-sa-kpi-label {
                font-size: 11px;
                font-weight: 700;
                color: #94A3B8;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                display: block;
            }

            .nuvex-sa-kpi-value {
                font-size: 27px;
                font-weight: 800;
                color: #1E293B;
                margin-top: 4px;
                line-height: 1.1;
                letter-spacing: -0.02em;
            }

            .nuvex-val-money {
                font-size: 23px;
            }

            .nuvex-sa-kpi-badge {
                width: 48px;
                height: 48px;
                border-radius: 9999px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .nuvex-sa-kpi-badge svg {
                width: 22px !important;
                height: 22px !important;
                min-width: 22px !important;
                min-height: 22px !important;
                max-width: 22px !important;
                max-height: 22px !important;
            }

            .badge-emerald { background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; }
            .badge-cyan    { background: #ECFEFF; color: #0891B2; border: 1px solid #A5F3FC; }
            .badge-amber   { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
            .badge-violet  { background: #F5F3FF; color: #7C3AED; border: 1px solid #DDD6FE; }

            .nuvex-sa-kpi-foot {
                padding-top: 12px;
                border-top: 1px solid #F1F5F9;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 12px;
            }

            .nuvex-sa-kpi-sub {
                color: #64748B;
                font-weight: 500;
            }

            .nuvex-sa-tag-emerald { font-weight: 700; color: #047857; background: #ECFDF5; padding: 2px 8px; border-radius: 9999px; }
            .nuvex-sa-tag-cyan    { font-weight: 700; color: #0891B2; background: #ECFEFF; padding: 2px 8px; border-radius: 9999px; }
            .nuvex-sa-tag-amber   { font-weight: 700; color: #B45309; background: #FFFBEB; padding: 2px 8px; border-radius: 9999px; }
            .nuvex-sa-tag-violet  { font-weight: 700; color: #6D28D9; background: #F5F3FF; padding: 2px 8px; border-radius: 9999px; }

            /* 3. Mid Grid */
            .nuvex-sa-mid-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 22px;
                width: 100%;
                box-sizing: border-box;
            }

            @media (min-width: 1024px) {
                .nuvex-sa-mid-grid {
                    grid-template-columns: 1fr 1.8fr;
                }
            }

            /* Health Card (Slate Dark) */
            .nuvex-sa-health-card {
                background: #1E293B;
                color: #FFFFFF;
                border-radius: 24px;
                padding: 26px 28px;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-sizing: border-box;
            }

            .nuvex-sa-health-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 16px;
            }

            .nuvex-sa-health-tag {
                font-size: 11px;
                font-weight: 700;
                color: #34D399;
                letter-spacing: 0.08em;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .nuvex-sa-dot-emerald {
                width: 7px;
                height: 7px;
                border-radius: 9999px;
                background: #34D399;
                flex-shrink: 0;
            }

            .nuvex-sa-version {
                font-size: 11px;
                padding: 3px 10px;
                border-radius: 9999px;
                background: #0F172A;
                color: #94A3B8;
                font-family: monospace;
            }

            .nuvex-sa-health-title {
                font-size: 18px;
                font-weight: 800;
                color: #FFFFFF;
                margin: 0 0 8px 0;
            }

            .nuvex-sa-health-desc {
                font-size: 12px;
                color: #94A3B8;
                line-height: 1.5;
                margin: 0 0 20px 0;
            }

            .nuvex-sa-service-list {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }

            .nuvex-sa-service-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 14px;
                border-radius: 14px;
                background: rgba(15, 23, 42, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.06);
            }

            .nuvex-sa-srv-left {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .nuvex-sa-srv-dot {
                width: 8px;
                height: 8px;
                border-radius: 9999px;
                flex-shrink: 0;
            }

            .srv-dot-ok { background: #34D399; }
            .srv-dot-err { background: #F43F5E; }

            .nuvex-sa-srv-name {
                font-size: 12px;
                font-weight: 600;
                color: #E2E8F0;
            }

            .nuvex-sa-srv-val {
                font-size: 11px;
                font-family: monospace;
                color: #94A3B8;
            }

            .srv-val-ok { color: #34D399; }
            .srv-val-err { color: #F87171; }

            .nuvex-sa-health-foot {
                padding-top: 18px;
                margin-top: 20px;
                border-top: 1px solid rgba(255, 255, 255, 0.08);
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 12px;
                color: #94A3B8;
            }

            .nuvex-sa-bold {
                font-weight: 700;
                color: #F8FAFC;
            }

            /* Tenants Box */
            .nuvex-sa-tenants-box {
                background: #FFFFFF;
                border-radius: 24px;
                padding: 24px 28px;
                border: 1px solid #EFEAE6;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.035), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                box-sizing: border-box;
            }

            .nuvex-sa-box-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 12px;
                margin-bottom: 18px;
            }

            .nuvex-sa-box-title {
                font-size: 16px;
                font-weight: 800;
                color: #1E293B;
                margin: 0;
            }

            .nuvex-sa-box-subtitle {
                font-size: 12px;
                color: #64748B;
                margin: 3px 0 0 0;
            }

            .nuvex-sa-link-all {
                font-size: 12px;
                font-weight: 700;
                color: #00C975;
                text-decoration: none;
                display: flex;
                align-items: center;
                gap: 4px;
                white-space: nowrap;
                transition: color 0.15s ease;
            }

            .nuvex-sa-link-all:hover {
                color: #00B066;
            }

            .nuvex-sa-link-all svg {
                width: 14px !important;
                height: 14px !important;
                min-width: 14px !important;
                min-height: 14px !important;
            }

            .nuvex-sa-tenants-subgrid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 14px;
            }

            @media (min-width: 640px) {
                .nuvex-sa-tenants-subgrid {
                    grid-template-columns: 1fr 1fr;
                }
            }

            .nuvex-sa-mini-card {
                border-radius: 18px;
                padding: 16px;
                border: 1px solid #EFEAE6;
                background: #F8FAFC;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                transition: all 0.2s ease;
                box-sizing: border-box;
            }

            .nuvex-sa-mini-card:hover {
                background: #FFFFFF;
                border-color: #A7F3D0;
                box-shadow: 0 6px 18px -4px rgba(0,0,0,0.05);
            }

            .nuvex-sa-mc-head {
                display: flex;
                align-items: flex-start;
                justify-content: space-between;
                gap: 10px;
                margin-bottom: 12px;
            }

            .nuvex-sa-mc-brand {
                display: flex;
                align-items: center;
                gap: 10px;
                min-width: 0;
            }

            .nuvex-sa-mc-avatar {
                width: 44px;
                height: 44px;
                border-radius: 14px;
                background: #1E293B;
                color: #FFFFFF;
                font-weight: 800;
                font-size: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                flex-shrink: 0;
                border: 2px solid #FFFFFF;
                box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            }

            .nuvex-sa-mc-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .nuvex-sa-mc-meta {
                min-width: 0;
            }

            .nuvex-sa-mc-title {
                font-size: 14px;
                font-weight: 700;
                color: #1E293B;
                margin: 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .nuvex-sa-mc-city {
                font-size: 11px;
                color: #64748B;
                margin: 2px 0 0 0;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .nuvex-sa-mc-chips {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin: 10px 0;
                padding: 10px;
                border-radius: 12px;
                background: #FFFFFF;
                border: 1px solid #EDF2F7;
            }

            .nuvex-sa-mc-chip {
                display: flex;
                flex-direction: column;
                gap: 1px;
            }

            .nuvex-sa-chip-label {
                font-size: 9px;
                font-weight: 700;
                text-transform: uppercase;
                color: #94A3B8;
                letter-spacing: 0.04em;
            }

            .nuvex-sa-chip-val {
                font-size: 11.5px;
                font-weight: 700;
                color: #1E293B;
            }

            .nuvex-val-highlight {
                color: #047857;
            }

            .nuvex-sa-mc-foot {
                padding-top: 10px;
                border-top: 1px solid #E2E8F0;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 8px;
                font-size: 11px;
            }

            .nuvex-sa-mc-slug {
                font-family: monospace;
                color: #64748B;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .nuvex-sa-mc-btns {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .nuvex-btn-web {
                display: inline-flex;
                align-items: center;
                padding: 5px 11px;
                border-radius: 9999px;
                font-size: 11px;
                font-weight: 600;
                background: #FFFFFF;
                color: #334155;
                border: 1px solid #CBD5E1;
                text-decoration: none;
                transition: all 0.2s;
            }

            .nuvex-btn-web:hover {
                background: #F8FAFC;
                border-color: #94A3B8;
            }

            .nuvex-btn-edit {
                display: inline-flex;
                align-items: center;
                padding: 5px 14px;
                border-radius: 9999px;
                font-size: 11px;
                font-weight: 700;
                background: #00C975 !important;
                color: #FFFFFF !important;
                border: none;
                text-decoration: none;
                box-shadow: 0 3px 10px rgba(0, 201, 117, 0.25);
                transition: all 0.2s;
            }

            .nuvex-btn-edit:hover {
                background: #00B066 !important;
                transform: translateY(-1px);
            }

            .nuvex-pill-active {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 9px;
                border-radius: 9999px;
                font-size: 10.5px;
                font-weight: 700;
                background: #ECFDF5;
                color: #047857;
                border: 1px solid #A7F3D0;
                white-space: nowrap;
            }

            .nuvex-dot-active {
                width: 6px;
                height: 6px;
                border-radius: 9999px;
                background: #10B981;
                flex-shrink: 0;
            }

            .nuvex-pill-paused {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 3px 9px;
                border-radius: 9999px;
                font-size: 10.5px;
                font-weight: 700;
                background: #FFF1F2;
                color: #BE123C;
                border: 1px solid #FECDD3;
                white-space: nowrap;
            }

            .nuvex-dot-paused {
                width: 6px;
                height: 6px;
                border-radius: 9999px;
                background: #F43F5E;
                flex-shrink: 0;
            }

            .nuvex-sa-box-foot {
                padding-top: 14px;
                margin-top: 14px;
                border-top: 1px solid #F1F5F9;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 12px;
                color: #94A3B8;
            }

            /* 4. Recent Activity Box */
            .nuvex-sa-recent-box {
                background: #FFFFFF;
                border-radius: 24px;
                padding: 24px 28px;
                border: 1px solid #EFEAE6;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.035), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
                width: 100%;
                box-sizing: border-box;
            }

            .nuvex-sa-table-wrap {
                width: 100%;
                overflow-x: auto;
                margin-top: 14px;
            }

            .nuvex-sa-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 12px;
                text-align: left;
            }

            .nuvex-sa-table th {
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #94A3B8;
                padding: 10px 14px;
                border-bottom: 1px solid #E2E8F0;
                white-space: nowrap;
            }

            .nuvex-sa-table td {
                padding: 14px;
                border-bottom: 1px solid #F1F5F9;
                color: #334155;
                vertical-align: middle;
            }

            .nuvex-sa-table tr:last-child td {
                border-bottom: none;
            }

            .nuvex-sa-table tr:hover td {
                background-color: #F8FAFC;
            }

            .nuvex-sa-cell-salon {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .nuvex-sa-mini-avatar {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                background: #1E293B;
                color: #FFFFFF;
                font-size: 10px;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .nuvex-sa-salon-name {
                font-weight: 700;
                color: #1E293B;
            }

            .nuvex-sa-cell-client {
                display: flex;
                flex-direction: column;
                gap: 2px;
            }

            .nuvex-sa-client-name {
                font-weight: 600;
                color: #1E293B;
            }

            .nuvex-sa-client-phone {
                font-size: 11px;
                color: #64748B;
            }

            .nuvex-sa-service-name {
                font-weight: 600;
                color: #0F172A;
            }

            .nuvex-sa-price-val {
                font-weight: 700;
                color: #047857;
                font-size: 12.5px;
            }

            .nuvex-sa-date-val {
                font-size: 11.5px;
                color: #64748B;
                white-space: nowrap;
            }

            /* 5. Shortcuts Grid */
            .nuvex-sa-shortcuts-grid {
                display: grid;
                grid-template-columns: repeat(1, 1fr);
                gap: 16px;
                width: 100%;
                box-sizing: border-box;
            }

            @media (min-width: 640px) {
                .nuvex-sa-shortcuts-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
            }

            @media (min-width: 1024px) {
                .nuvex-sa-shortcuts-grid {
                    grid-template-columns: repeat(4, 1fr);
                }
            }

            .nuvex-sa-shortcut {
                background: #FFFFFF;
                border-radius: 18px;
                padding: 16px 18px;
                border: 1px solid #EFEAE6;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.02);
                display: flex;
                align-items: center;
                gap: 14px;
                text-decoration: none;
                transition: all 0.2s ease;
                box-sizing: border-box;
            }

            .nuvex-sa-shortcut:hover {
                border-color: #A7F3D0;
                box-shadow: 0 8px 18px rgba(0, 0, 0, 0.04);
                transform: translateY(-1px);
            }

            .nuvex-sa-sc-icon {
                width: 42px;
                height: 42px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .nuvex-sa-sc-icon svg {
                width: 20px !important;
                height: 20px !important;
                min-width: 20px !important;
                min-height: 20px !important;
                max-width: 20px !important;
                max-height: 20px !important;
            }

            .sc-emerald { background: #ECFDF5; color: #00C975; }
            .sc-cyan    { background: #ECFEFF; color: #0891B2; }
            .sc-amber   { background: #FFFBEB; color: #D97706; }
            .sc-violet  { background: #F5F3FF; color: #7C3AED; }

            .nuvex-sa-sc-title {
                font-size: 12.5px;
                font-weight: 700;
                color: #1E293B;
                display: block;
            }

            .nuvex-sa-sc-sub {
                font-size: 11px;
                color: #64748B;
                display: block;
                margin-top: 1px;
            }

            .nuvex-sa-empty {
                padding: 24px;
                text-align: center;
                color: #94A3B8;
                font-size: 12.5px;
            }
        </style>

    </div>
</x-filament-widgets::widget>
