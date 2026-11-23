<div class="nuvex-tenants-wrapper">
    <div class="nuvex-tenants-grid">
        @forelse($records as $tenant)
            <div class="nuvex-tenant-card">

                {{-- 1. Cabecera del Salón --}}
                <div class="nuvex-tenant-head">
                    <div class="nuvex-tenant-brand">
                        <div class="nuvex-tenant-avatar">
                            @if($tenant->logo_path && file_exists(public_path('storage/' . $tenant->logo_path)))
                                <img src="{{ asset('storage/' . $tenant->logo_path) }}" alt="{{ $tenant->name }}">
                            @else
                                <span>{{ strtoupper(substr($tenant->name, 0, 2)) }}</span>
                            @endif
                        </div>
                        <div class="nuvex-tenant-meta">
                            <h3 class="nuvex-tenant-title" title="{{ $tenant->name }}">
                                {{ $tenant->name }}
                            </h3>
                            <p class="nuvex-tenant-subtitle">
                                {{ $tenant->business_type ?: 'Estudio de Belleza' }} · {{ $tenant->city ?: 'Bogotá, Colombia' }}
                            </p>
                        </div>
                    </div>

                    {{-- Badge de Estado --}}
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

                {{-- 2. Grid de Métricas 2x2 --}}
                <div class="nuvex-tenant-metrics">
                    <div class="nuvex-metric-box">
                        <span class="nuvex-metric-label">Plan Contratado</span>
                        <span class="nuvex-metric-val nuvex-val-highlight">
                            {{ $tenant->plan_name ?: 'Pro Salon' }}
                        </span>
                    </div>
                    <div class="nuvex-metric-box">
                        <span class="nuvex-metric-label">Citas Totales</span>
                        <span class="nuvex-metric-val">
                            {{ $tenant->appointments()->count() }} citas
                        </span>
                    </div>
                    <div class="nuvex-metric-box">
                        <span class="nuvex-metric-label">Cupo Mensual</span>
                        <span class="nuvex-metric-val">
                            {{ $tenant->max_appointments_per_month ?: 500 }} máx
                        </span>
                    </div>
                    <div class="nuvex-metric-box">
                        <span class="nuvex-metric-label">Subruta Web</span>
                        <span class="nuvex-metric-val nuvex-val-slug">
                            /{{ $tenant->slug }}
                        </span>
                    </div>
                </div>

                {{-- 3. Información de Contacto & Dirección --}}
                <div class="nuvex-tenant-info-list">
                    @if($tenant->address)
                        <div class="nuvex-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="nuvex-truncate">{{ $tenant->address }}</span>
                        </div>
                    @endif
                    @if($tenant->whatsapp_number || $tenant->phone)
                        <div class="nuvex-info-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <span>{{ $tenant->whatsapp_number ?: $tenant->phone }}</span>
                        </div>
                    @endif
                </div>

                {{-- 4. Barra de Acciones del Salón --}}
                <div class="nuvex-tenant-actions">
                    @php
                        $rawPhone = $tenant->whatsapp_number ?: $tenant->phone;
                        $cleanPhone = preg_replace('/\D/', '', $rawPhone ?? '');
                        if (strlen($cleanPhone) === 10 && str_starts_with($cleanPhone, '3')) {
                            $cleanPhone = '57' . $cleanPhone;
                        }
                    @endphp

                    <div>
                        @if($cleanPhone)
                            <a href="https://wa.me/{{ $cleanPhone }}?text=Hola%20te%20escribimos%20desde%20la%20administracion%20de%20Cita%20Clave%20Nuvex"
                               target="_blank"
                               class="nuvex-btn-wa"
                               title="Abrir chat con el salón">
                                <svg viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.542 1.897.82 2.791.82h.001c3.181 0 5.768-2.586 5.768-5.766 0-3.18-2.587-5.766-5.769-5.766zm3.376 8.204c-.149.419-.747.781-1.026.832-.27.05-.62.072-1.808-.419-1.52-.628-2.502-2.18-2.578-2.282-.075-.102-.614-.817-.614-1.558 0-.741.388-1.106.526-1.255.138-.149.3-.186.4-.186.1 0 .2.001.288.006.094.005.218-.035.341.262.126.305.433 1.056.471 1.134.038.077.063.168.013.268-.05.101-.075.163-.15.251-.075.088-.158.197-.225.264-.075.075-.153.156-.066.305.087.149.387.639.83 1.033.57.507 1.051.664 1.2.739.149.075.237.063.325-.038.088-.101.376-.438.476-.588.1-.15.2-.125.338-.075.138.05.877.413 1.027.488.15.075.25.112.288.175.037.063.037.363-.112.782z"/>
                                </svg>
                                <span>WhatsApp</span>
                            </a>
                        @endif
                    </div>

                    <div class="nuvex-actions-right">
                        <a href="{{ url('/' . $tenant->slug) }}"
                           target="_blank"
                           class="nuvex-btn-web"
                           title="Ver portal web del salón">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            <span>Web</span>
                        </a>
                        <a href="{{ route('filament.superadmin.resources.tenants.edit', ['record' => $tenant->id]) }}"
                           class="nuvex-btn-edit">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                            <span>Gestionar</span>
                        </a>
                    </div>
                </div>

            </div>
        @empty
            <div class="nuvex-empty-tenants">
                <div class="nuvex-empty-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <h3 class="nuvex-empty-title">No hay empresas ni salones registrados</h3>
                <p class="nuvex-empty-desc">Registra el primer salón para habilitar su plataforma multi-tenant.</p>
                <a href="{{ route('filament.superadmin.resources.tenants.create') }}" class="nuvex-btn-create-empty">
                    <span>+ Registrar Nuevo Salón</span>
                </a>
            </div>
        @endforelse
    </div>
</div>

<style>
    /* ==========================================================================
       NUVEX TENANTS CARD GRID — SCOPED STYLING
       ========================================================================== */
    /* Ocultar barra redundante de ordenamiento y checkbox de tabla */
    .fi-ta-content-header,
    .fi-ta-sort,
    .fi-ta-page-checkbox {
        display: none !important;
    }

    .nuvex-tenants-wrapper {
        width: 100%;
        padding: 12px 4px 24px 4px;
        box-sizing: border-box;
    }

    .nuvex-tenants-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
        gap: 22px;
        width: 100%;
        box-sizing: border-box;
    }

    @media (max-width: 640px) {
        .nuvex-tenants-grid {
            grid-template-columns: 1fr;
            gap: 16px;
        }
    }

    .nuvex-tenant-card {
        background: #FFFFFF;
        border-radius: 24px;
        border: 1px solid #EFEAE6;
        padding: 22px 24px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.035), 0 8px 10px -6px rgba(0, 0, 0, 0.02);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        box-sizing: border-box;
    }

    .nuvex-tenant-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px -6px rgba(0, 0, 0, 0.07), 0 10px 14px -5px rgba(0, 0, 0, 0.03);
        border-color: #A7F3D0;
    }

    /* Head */
    .nuvex-tenant-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 14px;
    }

    .nuvex-tenant-brand {
        display: flex;
        align-items: center;
        gap: 14px;
        min-width: 0;
    }

    .nuvex-tenant-avatar {
        width: 52px;
        height: 52px;
        border-radius: 16px;
        background: #1E293B;
        color: #FFFFFF;
        font-weight: 800;
        font-size: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid #FFFFFF;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }

    .nuvex-tenant-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .nuvex-tenant-meta {
        min-width: 0;
    }

    .nuvex-tenant-title {
        font-size: 15.5px;
        font-weight: 800;
        color: #1E293B;
        margin: 0;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nuvex-tenant-subtitle {
        font-size: 11.5px;
        color: #64748B;
        font-weight: 500;
        margin: 3px 0 0 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Badges */
    .nuvex-pill-active {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        background: #ECFDF5;
        color: #047857;
        border: 1px solid #A7F3D0;
        white-space: nowrap;
    }

    .nuvex-dot-active {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        background: #10B981;
    }

    .nuvex-pill-paused {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 11px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        background: #FFF1F2;
        color: #BE123C;
        border: 1px solid #FECDD3;
        white-space: nowrap;
    }

    .nuvex-dot-paused {
        width: 7px;
        height: 7px;
        border-radius: 9999px;
        background: #F43F5E;
    }

    /* Metrics Grid */
    .nuvex-tenant-metrics {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin: 14px 0;
        padding: 12px 14px;
        border-radius: 16px;
        background: #F8FAFC;
        border: 1px solid #EDF2F7;
    }

    .nuvex-metric-box {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .nuvex-metric-label {
        font-size: 9.5px;
        text-transform: uppercase;
        font-weight: 700;
        color: #94A3B8;
        letter-spacing: 0.04em;
    }

    .nuvex-metric-val {
        font-size: 12.5px;
        font-weight: 700;
        color: #1E293B;
    }

    .nuvex-val-highlight {
        color: #047857;
    }

    .nuvex-val-slug {
        font-family: monospace;
        font-size: 11.5px;
        color: #475569;
    }

    /* Info List */
    .nuvex-tenant-info-list {
        display: flex;
        flex-direction: column;
        gap: 6px;
        font-size: 12px;
        color: #64748B;
        margin-bottom: 14px;
        padding: 0 2px;
    }

    .nuvex-info-item {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nuvex-info-item svg {
        width: 14px;
        height: 14px;
        min-width: 14px;
        min-height: 14px;
        max-width: 14px;
        max-height: 14px;
        color: #94A3B8;
        flex-shrink: 0;
    }

    .nuvex-truncate {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Actions */
    .nuvex-tenant-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding-top: 14px;
        border-top: 1px solid #F1F5F9;
    }

    .nuvex-actions-right {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .nuvex-btn-wa {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 13px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 700;
        background: #ECFDF5;
        color: #047857;
        border: 1px solid #A7F3D0;
        text-decoration: none;
        transition: all 0.2s;
    }

    .nuvex-btn-wa:hover {
        background: #D1FAE5;
        transform: translateY(-1px);
    }

    .nuvex-btn-wa svg {
        width: 14px;
        height: 14px;
        min-width: 14px;
        min-height: 14px;
        max-width: 14px;
        max-height: 14px;
        fill: currentColor;
    }

    .nuvex-btn-web {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 9999px;
        font-size: 11.5px;
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

    .nuvex-btn-web svg {
        width: 13px;
        height: 13px;
        min-width: 13px;
        min-height: 13px;
        max-width: 13px;
        max-height: 13px;
    }

    .nuvex-btn-edit {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 11.5px;
        font-weight: 700;
        background: #00C975;
        color: #FFFFFF;
        border: none;
        text-decoration: none;
        box-shadow: 0 4px 12px rgba(0, 201, 117, 0.25);
        transition: all 0.2s;
    }

    .nuvex-btn-edit:hover {
        background: #00B066;
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(0, 201, 117, 0.35);
    }

    .nuvex-btn-edit svg {
        width: 13px;
        height: 13px;
        min-width: 13px;
        min-height: 13px;
        max-width: 13px;
        max-height: 13px;
    }

    /* Empty State */
    .nuvex-empty-tenants {
        grid-column: 1 / -1;
        padding: 60px 20px;
        text-align: center;
        background: #FFFFFF;
        border-radius: 24px;
        border: 1px solid #EFEAE6;
    }

    .nuvex-empty-icon {
        width: 60px;
        height: 60px;
        border-radius: 20px;
        background: #F1F5F9;
        color: #94A3B8;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px auto;
    }

    .nuvex-empty-icon svg {
        width: 28px;
        height: 28px;
    }

    .nuvex-empty-title {
        font-size: 16px;
        font-weight: 700;
        color: #1E293B;
        margin: 0 0 6px 0;
    }

    .nuvex-empty-desc {
        font-size: 13px;
        color: #64748B;
        margin: 0 auto 20px auto;
        max-width: 380px;
    }

    .nuvex-btn-create-empty {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 9px 22px;
        border-radius: 9999px;
        font-size: 13px;
        font-weight: 700;
        background: #00C975;
        color: #FFFFFF;
        text-decoration: none;
        box-shadow: 0 4px 14px rgba(0, 201, 117, 0.3);
    }

    .nuvex-btn-create-empty:hover {
        background: #00B066;
    }
</style>
