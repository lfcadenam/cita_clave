@php
    $loyalty = $customer->getLoyaltyBadge();
    $cleanPhone = $customer->getCleanPhone();
    $appointments = $customer->appointments()->with('service')->get();
    $totalCount = $appointments->count();
    $completedCount = $customer->getCompletedAppointmentsCount();
    $totalSpent = $customer->total_spent;
    $favoriteService = $customer->getFavoriteService();
    $lastAppointment = $customer->getLastAppointment();
@endphp

<div class="nuvex-customer-modal">
    <style>
        .nuvex-customer-modal {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1E293B;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .dark .nuvex-customer-modal {
            color: #F8FAFC;
        }

        /* Banner Superior del Cliente */
        .nuvex-cust-banner {
            background: linear-gradient(135deg, #F8FAFC 0%, #FFFFFF 100%);
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .dark .nuvex-cust-banner {
            background: linear-gradient(135deg, #1E293B 0%, #182234 100%);
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-cust-avatar {
            width: 52px;
            height: 52px;
            border-radius: 9999px;
            background: linear-gradient(135deg, #00C975 0%, #009E5C 100%);
            color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 800;
            box-shadow: 0 4px 14px rgba(0, 201, 117, 0.25);
            flex-shrink: 0;
        }

        .nuvex-cust-name {
            font-size: 1.25rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #0F172A;
            line-height: 1.2;
        }

        .dark .nuvex-cust-name {
            color: #FFFFFF;
        }

        .nuvex-cust-meta {
            font-size: 0.82rem;
            color: #64748B;
            font-weight: 500;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .dark .nuvex-cust-meta {
            color: #94A3B8;
        }

        /* Badge de Fidelidad */
        .nuvex-loyalty-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            border: 1px solid transparent;
        }

        .nuvex-loyalty-pill.amber {
            background-color: #FEF3C7;
            color: #B45309;
            border-color: rgba(245, 158, 11, 0.3);
        }
        .dark .nuvex-loyalty-pill.amber {
            background-color: rgba(245, 158, 11, 0.15);
            color: #FCD34D;
        }

        .nuvex-loyalty-pill.emerald {
            background-color: #E6FDF4;
            color: #047857;
            border-color: rgba(0, 201, 117, 0.3);
        }
        .dark .nuvex-loyalty-pill.emerald {
            background-color: rgba(0, 201, 117, 0.15);
            color: #34D399;
        }

        .nuvex-loyalty-pill.purple {
            background-color: #F3E8FF;
            color: #7E22CE;
            border-color: rgba(147, 51, 234, 0.3);
        }
        .dark .nuvex-loyalty-pill.purple {
            background-color: rgba(147, 51, 234, 0.15);
            color: #C084FC;
        }

        /* Botón WhatsApp */
        .nuvex-whatsapp-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #E6FDF4;
            color: #047857;
            border: 1px solid rgba(0, 201, 117, 0.3);
            border-radius: 12px;
            padding: 7px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nuvex-whatsapp-btn:hover {
            background-color: #00C975;
            color: #FFFFFF;
            transform: translateY(-1px);
        }

        .dark .nuvex-whatsapp-btn {
            background-color: rgba(0, 201, 117, 0.15);
            color: #34D399;
        }

        /* 4 Tarjetas Estadísticas KPI */
        .nuvex-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }

        @media (max-width: 768px) {
            .nuvex-kpi-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .nuvex-kpi-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 18px;
            padding: 1rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 4px;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
        }

        .dark .nuvex-kpi-card {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px -3px rgba(0, 0, 0, 0.2);
        }

        .nuvex-kpi-title {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
        }

        .dark .nuvex-kpi-title {
            color: #94A3B8;
        }

        .nuvex-kpi-value {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.02em;
            line-height: 1.2;
        }

        .dark .nuvex-kpi-value {
            color: #FFFFFF;
        }

        .nuvex-kpi-sub {
            font-size: 0.75rem;
            color: #94A3B8;
            font-weight: 500;
        }

        .dark .nuvex-kpi-sub {
            color: #64748B;
        }

        /* Tabla de Servicios */
        .nuvex-services-box {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.02);
        }

        .dark .nuvex-services-box {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px -3px rgba(0, 0, 0, 0.2);
        }

        .nuvex-table-title {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #0F172A;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 0.6rem;
        }

        .dark .nuvex-table-title {
            color: #F8FAFC;
            border-bottom-color: rgba(255, 255, 255, 0.06);
        }

        .nuvex-services-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        .nuvex-services-table th {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
            padding: 0.4rem 0.75rem;
            text-align: left;
        }

        .dark .nuvex-services-table th {
            color: #94A3B8;
        }

        .nuvex-services-table td {
            background: #F8FAFC;
            padding: 0.85rem 0.75rem;
            font-size: 0.82rem;
            border-top: 1px solid #F1F5F9;
            border-bottom: 1px solid #F1F5F9;
        }

        .nuvex-services-table tr td:first-child {
            border-left: 1px solid #F1F5F9;
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .nuvex-services-table tr td:last-child {
            border-right: 1px solid #F1F5F9;
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .dark .nuvex-services-table td {
            background: #182234;
            border-color: rgba(255, 255, 255, 0.04);
            color: #E2E8F0;
        }

        .nuvex-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .nuvex-status-pill.emerald {
            background-color: #E6FDF4;
            color: #047857;
        }
        .dark .nuvex-status-pill.emerald {
            background-color: rgba(0, 201, 117, 0.16);
            color: #34D399;
        }

        .nuvex-status-pill.teal {
            background-color: #CCFBF1;
            color: #0F766E;
        }
        .dark .nuvex-status-pill.teal {
            background-color: rgba(20, 184, 166, 0.16);
            color: #2DD4BF;
        }

        .nuvex-status-pill.amber {
            background-color: #FEF3C7;
            color: #B45309;
        }
        .dark .nuvex-status-pill.amber {
            background-color: rgba(245, 158, 11, 0.16);
            color: #FCD34D;
        }

        .nuvex-status-pill.rose {
            background-color: #FFE4E6;
            color: #BE123C;
        }
        .dark .nuvex-status-pill.rose {
            background-color: rgba(244, 63, 94, 0.16);
            color: #FB7185;
        }

        .nuvex-status-pill.slate {
            background-color: #F1F5F9;
            color: #475569;
        }
        .dark .nuvex-status-pill.slate {
            background-color: rgba(148, 163, 184, 0.16);
            color: #CBD5E1;
        }

        .nuvex-empty-state {
            padding: 2.5rem 1rem;
            text-align: center;
            color: #64748B;
            font-size: 0.9rem;
        }
        .dark .nuvex-empty-state {
            color: #94A3B8;
        }
    </style>

    <!-- 1. Banner de Cabecera del Cliente -->
    <div class="nuvex-cust-banner">
        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="nuvex-cust-avatar">
                {{ strtoupper(substr($customer->name, 0, 1)) }}
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="nuvex-cust-name">{{ $customer->name }}</span>
                    <span class="nuvex-loyalty-pill {{ $loyalty['color'] }}">
                        {{ $loyalty['label'] }}
                    </span>
                </div>
                <div class="nuvex-cust-meta">
                    <span>+57 {{ $customer->getFormattedPhone() }}</span>
                    @if ($customer->email)
                        <span>•</span>
                        <span>{{ $customer->email }}</span>
                    @endif
                    <span>•</span>
                    <span>Registrada: {{ $customer->registered_at ? \Carbon\Carbon::parse($customer->registered_at)->translatedFormat('d M Y') : 'Reciente' }}</span>
                </div>
            </div>
        </div>

        <div>
            @if ($cleanPhone)
                <a href="https://wa.me/57{{ $cleanPhone }}?text=Hola%20{{ urlencode($customer->name) }},%20te%20escribimos%20de%20Paola%20Aguilera%20(La%20Belle%20Nails)"
                   target="_blank"
                   class="nuvex-whatsapp-btn"
                   title="Escribir por WhatsApp">
                    <span>Escribir por WhatsApp</span>
                </a>
            @endif
        </div>
    </div>

    <!-- 2. Estadísticas de Servicios y Fidelidad (4 KPIs) -->
    <div class="nuvex-kpi-grid">
        <div class="nuvex-kpi-card">
            <span class="nuvex-kpi-title">Total de Citas</span>
            <span class="nuvex-kpi-value">{{ $totalCount }}</span>
            <span class="nuvex-kpi-sub">{{ $loyalty['description'] }}</span>
        </div>

        <div class="nuvex-kpi-card">
            <span class="nuvex-kpi-title">Completadas / Activas</span>
            <span class="nuvex-kpi-value" style="color: #00C975;">{{ $completedCount }}</span>
            <span class="nuvex-kpi-sub">Citas efectivas en salón</span>
        </div>

        <div class="nuvex-kpi-card">
            <span class="nuvex-kpi-title">Inversión Total</span>
            <span class="nuvex-kpi-value">$ {{ number_format($totalSpent, 0, ',', '.') }}</span>
            <span class="nuvex-kpi-sub">Valor en servicios facturados</span>
        </div>

        <div class="nuvex-kpi-card">
            <span class="nuvex-kpi-title">Servicio Favorito</span>
            <span class="nuvex-kpi-value" style="font-size: 1.05rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $favoriteService?->name ?? 'Varios' }}">
                {{ $favoriteService?->name ?? ($totalCount > 0 ? 'Sin preferencia' : 'Ninguno aún') }}
            </span>
            <span class="nuvex-kpi-sub">Tratamiento más solicitado</span>
        </div>
    </div>

    <!-- 3. Listado de Servicios / Historial de Citas -->
    <div class="nuvex-services-box">
        <div class="nuvex-table-title">
            <span>Historial de Servicios y Citas ({{ $totalCount }})</span>
            @if ($lastAppointment)
                <span style="font-size: 0.75rem; font-weight: 600; color: #64748B;">
                    Última visita: {{ \Carbon\Carbon::parse($lastAppointment->appointment_date)->translatedFormat('d M Y') }}
                </span>
            @endif
        </div>

        @if ($appointments->isEmpty())
            <div class="nuvex-empty-state">
                @if ($customer->is_legacy)
                    Clienta antigua del salón (Base histórica). Sus citas comenzarán a reflejarse aquí a medida que reserve en el nuevo sistema digital.
                @else
                    Esta clienta aún no tiene citas registradas en el sistema.
                @endif
            </div>
        @else
            <div style="overflow-x: auto;">
                <table class="nuvex-services-table">
                    <thead>
                        <tr>
                            <th>Cita</th>
                            <th>Fecha & Horario</th>
                            <th>Servicio / Tratamiento</th>
                            <th>Total</th>
                            <th>Abono</th>
                            <th>Método</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($appointments as $appt)
                            @php
                                $statusClass = match ($appt->status) {
                                    \App\Enums\AppointmentStatus::CONFIRMED => 'emerald',
                                    \App\Enums\AppointmentStatus::COMPLETED => 'teal',
                                    \App\Enums\AppointmentStatus::PENDING_VERIFICATION => 'amber',
                                    \App\Enums\AppointmentStatus::CANCELLED, \App\Enums\AppointmentStatus::NO_SHOW => 'rose',
                                    default => 'slate',
                                };
                            @endphp
                            <tr>
                                <td>
                                    <strong style="color: #0F172A;" class="dark:text-white">#{{ $appt->appointment_number }}</strong>
                                </td>
                                <td>
                                    <div>{{ \Carbon\Carbon::parse($appt->appointment_date)->translatedFormat('d M Y') }}</div>
                                    <div style="font-size: 0.72rem; color: #64748B;">{{ substr($appt->start_time, 0, 5) }} - {{ substr($appt->end_time, 0, 5) }}</div>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #0F172A;" class="dark:text-white">
                                        {{ $appt->service?->name ?? 'Servicio Personalizado' }}
                                    </span>
                                </td>
                                <td>
                                    <strong>$ {{ number_format($appt->total_amount, 0, ',', '.') }}</strong>
                                </td>
                                <td>
                                    <span style="color: #047857; font-weight: 600;" class="dark:text-emerald-400">
                                        $ {{ number_format($appt->deposit_paid, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td>
                                    <span style="font-size: 0.75rem; color: #64748B;">
                                        {{ $appt->payment_method?->label() ?? 'Transferencia' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="nuvex-status-pill {{ $statusClass }}">
                                        {{ $appt->status?->label() ?? 'Registrada' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
