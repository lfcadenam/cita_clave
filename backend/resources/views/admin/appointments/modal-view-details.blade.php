@php
    $statusColor = match ($appointment->status) {
        \App\Enums\AppointmentStatus::CONFIRMED => 'emerald',
        \App\Enums\AppointmentStatus::PENDING_VERIFICATION => 'amber',
        \App\Enums\AppointmentStatus::PENDING_DEPOSIT => 'slate',
        \App\Enums\AppointmentStatus::IN_PROGRESS => 'blue',
        \App\Enums\AppointmentStatus::COMPLETED => 'teal',
        \App\Enums\AppointmentStatus::CANCELLED, \App\Enums\AppointmentStatus::NO_SHOW => 'rose',
        default => 'slate',
    };

    $receiptUrl = null;
    if ($appointment->deposit_proof_image) {
        $receiptUrl = asset('storage/' . $appointment->deposit_proof_image);
    }
    $cleanPhone = preg_replace('/\D/', '', $appointment->client_phone ?? '');
@endphp

<div class="nuvex-detail-modal">
    <style>
        .nuvex-detail-modal {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1E293B;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .dark .nuvex-detail-modal {
            color: #F8FAFC;
        }

        /* Banner Superior */
        .nuvex-detail-banner {
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

        .dark .nuvex-detail-banner {
            background: linear-gradient(135deg, #1E293B 0%, #182234 100%);
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-detail-code {
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: -0.01em;
            color: #0F172A;
        }

        .dark .nuvex-detail-code {
            color: #FFFFFF;
        }

        .nuvex-detail-schedule {
            font-size: 0.85rem;
            color: #64748B;
            font-weight: 500;
            margin-top: 2px;
        }

        .dark .nuvex-detail-schedule {
            color: #94A3B8;
        }

        /* Badge de Estado */
        .nuvex-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .nuvex-status-badge.emerald {
            background-color: #e6f7f2;
            color: #0f766e;
            border: 1px solid rgba(0, 201, 117, 0.25);
        }
        .dark .nuvex-status-badge.emerald {
            background-color: rgba(0, 201, 117, 0.16);
            color: #0d9488;
        }

        .nuvex-status-badge.amber {
            background-color: #FEF3C7;
            color: #B45309;
            border: 1px solid rgba(245, 158, 11, 0.25);
        }
        .dark .nuvex-status-badge.amber {
            background-color: rgba(245, 158, 11, 0.16);
            color: #FBBF24;
        }

        .nuvex-status-badge.slate {
            background-color: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
        }
        .dark .nuvex-status-badge.slate {
            background-color: rgba(255, 255, 255, 0.08);
            color: #CBD5E1;
        }

        .nuvex-status-badge.rose {
            background-color: #FFE4E6;
            color: #E11D48;
            border: 1px solid rgba(225, 29, 72, 0.25);
        }
        .dark .nuvex-status-badge.rose {
            background-color: rgba(225, 29, 72, 0.16);
            color: #FB7185;
        }

        .nuvex-status-badge.blue {
            background-color: #E0F2FE;
            color: #0369A1;
            border: 1px solid rgba(2, 132, 199, 0.25);
        }
        .dark .nuvex-status-badge.blue {
            background-color: rgba(2, 132, 199, 0.16);
            color: #38BDF8;
        }

        /* 2-Column Info Grid */
        .nuvex-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.25rem;
        }

        @media (max-width: 768px) {
            .nuvex-info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Tarjeta de Información */
        .nuvex-info-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.02);
        }

        .dark .nuvex-info-card {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 4px 20px -3px rgba(0, 0, 0, 0.2);
        }

        .nuvex-card-header-label {
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748B;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #F1F5F9;
            padding-bottom: 0.5rem;
        }

        .dark .nuvex-card-header-label {
            color: #94A3B8;
            border-bottom-color: rgba(255, 255, 255, 0.06);
        }

        .nuvex-field-row {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .nuvex-field-title {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #94A3B8;
        }

        .dark .nuvex-field-title {
            color: #64748B;
        }

        .nuvex-field-value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #0F172A;
        }

        .dark .nuvex-field-value {
            color: #F8FAFC;
        }

        .nuvex-field-value-lg {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.01em;
        }

        .dark .nuvex-field-value-lg {
            color: #FFFFFF;
        }

        /* Botón WhatsApp */
        .nuvex-whatsapp-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background-color: #e6f7f2;
            color: #0f766e;
            border: 1px solid rgba(0, 201, 117, 0.3);
            border-radius: 12px;
            padding: 5px 12px;
            font-size: 0.75rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .nuvex-whatsapp-btn:hover {
            background-color: #0d9488;
            color: #FFFFFF;
            transform: translateY(-1px);
        }

        .dark .nuvex-whatsapp-btn {
            background-color: rgba(0, 201, 117, 0.15);
            color: #0d9488;
        }

        /* Métricas Financieras (3 Pastillas) */
        .nuvex-metrics-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
        }

        .nuvex-metric-box {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 0.75rem 0.85rem;
            text-align: center;
        }

        .dark .nuvex-metric-box {
            background: #182234;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-metric-box.highlight {
            background: #F0FDF4;
            border-color: rgba(0, 201, 117, 0.35);
        }

        .dark .nuvex-metric-box.highlight {
            background: rgba(0, 201, 117, 0.1);
            border-color: rgba(0, 201, 117, 0.3);
        }

        .nuvex-metric-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748B;
        }

        .dark .nuvex-metric-label {
            color: #94A3B8;
        }

        .nuvex-metric-val {
            font-size: 1rem;
            font-weight: 800;
            color: #0F172A;
            margin-top: 3px;
        }

        .dark .nuvex-metric-val {
            color: #FFFFFF;
        }

        .nuvex-metric-val.emerald {
            color: #0f766e;
        }

        .dark .nuvex-metric-val.emerald {
            color: #0d9488;
        }

        /* Tarjeta de Saldo a Cobrar en Local */
        .nuvex-balance-box {
            background: #F8FAFC;
            border: 1px dashed #CBD5E1;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .dark .nuvex-balance-box {
            background: #182234;
            border-color: rgba(255, 255, 255, 0.15);
        }

        .nuvex-balance-box.pending {
            background: #FEF3C7;
            border-color: #F59E0B;
        }

        .dark .nuvex-balance-box.pending {
            background: rgba(245, 158, 11, 0.12);
            border-color: rgba(245, 158, 11, 0.4);
        }

        /* Visor de Comprobante Nequi */
        .nuvex-proof-card {
            background: #FFFFFF;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .dark .nuvex-proof-card {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-proof-container {
            background: #0F172A;
            border-radius: 14px;
            padding: 12px;
            display: flex;
            justify-content: center;
            align-items: center;
            max-height: 280px;
            overflow: hidden;
        }

        .nuvex-proof-img {
            max-width: 100%;
            height: auto;
            max-height: 260px;
            border-radius: 8px;
            object-fit: contain;
        }

        .nuvex-notes-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 16px;
            padding: 1rem 1.25rem;
            font-size: 0.85rem;
            color: #475569;
        }

        .dark .nuvex-notes-card {
            background: #182234;
            border-color: rgba(255, 255, 255, 0.08);
            color: #CBD5E1;
        }
    </style>

    <!-- 1. Encabezado / Resumen Superior -->
    <div class="nuvex-detail-banner">
        <div>
            <div class="nuvex-detail-code">Cita #{{ $appointment->appointment_number }}</div>
            <div class="nuvex-detail-schedule">
                {{ \Carbon\Carbon::parse($appointment->appointment_date)->translatedFormat('l, d \d\e F \d\e Y') }}
                &nbsp;•&nbsp;
                {{ substr($appointment->start_time, 0, 5) }} — {{ substr($appointment->end_time, 0, 5) }}
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px;">
            @if ($appointment->attendance_confirmed_at)
                <span class="nuvex-status-badge emerald" style="font-size: 0.72rem;" title="Confirmada el {{ $appointment->attendance_confirmed_at->format('d/m/Y H:i') }}">
                    ✓ Asistencia Confirmada
                </span>
            @endif
            <span class="nuvex-status-badge {{ $statusColor }}">
                {{ $appointment->status->label() }}
            </span>
        </div>
    </div>

    <!-- 2. Rejilla de Información en 2 Columnas -->
    <div class="nuvex-info-grid">
        <!-- Columna 1: Clienta y Servicio -->
        <div class="nuvex-info-card">
            <div class="nuvex-card-header-label">
                <span>Información de la Clienta</span>
                @if ($cleanPhone)
                    <a href="https://wa.me/57{{ $cleanPhone }}?text=Hola%20{{ urlencode($appointment->client_name) }},%20te%20escribimos%20de%20Paola%20Aguilera%20sobre%20tu%20cita%20%23{{ $appointment->appointment_number }}"
                       target="_blank"
                       class="nuvex-whatsapp-btn"
                       title="Abrir chat en WhatsApp">
                        <span>WhatsApp</span>
                    </a>
                @endif
            </div>

            <div class="nuvex-field-row">
                <span class="nuvex-field-title">Nombre Completo</span>
                <span class="nuvex-field-value-lg">{{ $appointment->client_name }}</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="nuvex-field-row">
                    <span class="nuvex-field-title">Celular</span>
                    <span class="nuvex-field-value">+57 {{ $appointment->client_phone }}</span>
                </div>
                <div class="nuvex-field-row">
                    <span class="nuvex-field-title">Correo Electrónico</span>
                    <span class="nuvex-field-value" style="font-size: 0.85rem; word-break: break-all;">
                        {{ $appointment->client_email ?: 'No registrado' }}
                    </span>
                </div>
            </div>

            <div style="border-top: 1px solid #F1F5F9; padding-top: 0.75rem;" class="dark:border-white/5">
                <div class="nuvex-field-title">Tratamiento Reservado</div>
                <div class="nuvex-field-value" style="color: #0f766e; font-weight: 700; margin-top: 2px;">
                    {{ $appointment->service?->name ?? 'Servicio Personalizado' }}
                </div>
                <div style="font-size: 0.8rem; color: #64748B; margin-top: 2px;">
                    Duración: {{ $appointment->service?->formatted_duration ?? ($appointment->service?->duration_minutes ? $appointment->service->duration_minutes . ' min' : 'Estándar') }}
                </div>
            </div>

            @if ($appointment->client_notes)
                <div style="border-top: 1px solid #F1F5F9; padding-top: 0.5rem;" class="dark:border-white/5">
                    <div class="nuvex-field-title">Solicitud de la Clienta</div>
                    <div style="font-size: 0.85rem; font-style: italic; color: #475569; margin-top: 2px;">
                        "{{ $appointment->client_notes }}"
                    </div>
                </div>
            @endif
        </div>

        <!-- Columna 2: Liquidación de Pagos y Saldos -->
        <div class="nuvex-info-card">
            <div class="nuvex-card-header-label">
                <span>Estado Financiero & Abono</span>
                <span style="font-size: 0.75rem; font-weight: 600; color: #64748B;">
                    {{ $appointment->payment_method?->label() ?? 'Transferencia Directa' }}
                </span>
            </div>

            <!-- Métricas -->
            <div class="nuvex-metrics-row">
                <div class="nuvex-metric-box">
                    <div class="nuvex-metric-label">Total</div>
                    <div class="nuvex-metric-val">${{ number_format($appointment->total_amount, 0, ',', '.') }}</div>
                </div>
                <div class="nuvex-metric-box highlight">
                    <div class="nuvex-metric-label">Abono</div>
                    <div class="nuvex-metric-val emerald">${{ number_format($appointment->deposit_paid ?? $appointment->deposit_amount, 0, ',', '.') }}</div>
                </div>
                <div class="nuvex-metric-box">
                    <div class="nuvex-metric-label">Saldo</div>
                    <div class="nuvex-metric-val">${{ number_format($appointment->balance_due, 0, ',', '.') }}</div>
                </div>
            </div>

            <!-- Caja de Saldo en Local -->
            @if ($appointment->balance_due > 0)
                <div class="nuvex-balance-box pending">
                    <div>
                        <div style="font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #B45309;">
                            Saldo a cobrar en el salón
                        </div>
                        <div style="font-size: 0.78rem; color: #92400E; margin-top: 2px;">
                            Cobrar al culminar la atención en el local
                        </div>
                    </div>
                    <div style="font-size: 1.25rem; font-weight: 800; color: #92400E;">
                        ${{ number_format($appointment->balance_due, 0, ',', '.') }}
                    </div>
                </div>
            @else
                <div class="nuvex-balance-box">
                    <div>
                        <div style="font-size: 0.75rem; font-weight: 700; color: #0f766e;">
                            Servicio Totalmente Cancelado
                        </div>
                        <div style="font-size: 0.75rem; color: #64748B;">
                            No hay saldo pendiente por cobrar
                        </div>
                    </div>
                    <div style="font-size: 1rem; font-weight: 800; color: #0f766e;">
                        $0
                    </div>
                </div>
            @endif

            <div style="display: flex; flex-direction: column; gap: 4px; font-size: 0.78rem; color: #64748B; border-top: 1px solid #F1F5F9; padding-top: 0.75rem;" class="dark:border-white/5">
                <div style="display: flex; justify-content: space-between;">
                    <span>Fecha de registro:</span>
                    <strong style="color: #0F172A;" class="dark:text-white">{{ $appointment->created_at?->format('d/m/Y h:i A') ?? '—' }}</strong>
                </div>
                @if ($appointment->verified_at)
                    <div style="display: flex; justify-content: space-between;">
                        <span>Verificación de abono:</span>
                        <strong style="color: #0f766e;">{{ $appointment->verified_at->format('d/m/Y h:i A') }}</strong>
                    </div>
                @endif
                @if ($appointment->payment_gateway_reference)
                    <div style="display: flex; justify-content: space-between;">
                        <span>Referencia pasarela:</span>
                        <span style="font-family: monospace; font-size: 0.75rem;">{{ $appointment->payment_gateway_reference }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 3. Comprobante Nequi (Si fue adjuntado) -->
    @if ($receiptUrl)
        <div class="nuvex-proof-card">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span class="nuvex-card-header-label" style="border: none; padding: 0;">
                    Comprobante de Transferencia Adjunto
                </span>
                <a href="{{ $receiptUrl }}"
                   target="_blank"
                   style="font-size: 0.75rem; font-weight: 700; color: #0f766e; text-decoration: underline;">
                    Ver tamaño completo
                </a>
            </div>
            <div class="nuvex-proof-container">
                <img src="{{ $receiptUrl }}"
                     alt="Comprobante de Abono"
                     class="nuvex-proof-img"
                     loading="lazy">
            </div>
        </div>
    @endif

    <!-- 4. Notas de Verificación o Cancelación -->
    @if ($appointment->verification_notes || $appointment->admin_notes || $appointment->cancellation_reason)
        <div class="nuvex-notes-card">
            @if ($appointment->verification_notes)
                <div><strong>Nota de Aprobación:</strong> {{ $appointment->verification_notes }}</div>
            @endif
            @if ($appointment->admin_notes)
                <div><strong>Nota Interna:</strong> {{ $appointment->admin_notes }}</div>
            @endif
            @if ($appointment->cancellation_reason)
                <div style="color: #E11D48;"><strong>Motivo de Cancelación:</strong> {{ $appointment->cancellation_reason }}</div>
            @endif
        </div>
    @endif
</div>
