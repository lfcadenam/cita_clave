<x-filament-panels::page>
    @php
        $data = $this->getCalendarData();
        $selectedApt = $this->selectedAppointment;
        $services = $this->services;
    @endphp

    <style>
        .salonesgo-container {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            width: 100%;
        }

        /* 2-Column SaaS Layout */
        .salonesgo-grid-layout {
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
            gap: 24px;
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2f0ea;
            box-shadow: 0 4px 20px -2px rgba(13, 148, 136, 0.04);
            padding: 24px;
            min-width: 0;
        }

        @media (max-width: 1100px) {
            .salonesgo-grid-layout {
                grid-template-columns: minmax(0, 1fr);
                gap: 20px;
            }
        }

        @media (max-width: 640px) {
            .salonesgo-grid-layout {
                padding: 14px;
                border-radius: 14px;
                gap: 16px;
            }
        }

        /* LEFT SIDEBAR PALETTE */
        .salonesgo-left-panel {
            display: flex;
            flex-direction: column;
            gap: 18px;
            border-right: 1px solid #e2f0ea;
            padding-right: 24px;
            min-width: 0;
        }
        @media (max-width: 1100px) {
            .salonesgo-left-panel {
                border-right: none;
                border-bottom: 1px solid #e2f0ea;
                padding-right: 0;
                padding-bottom: 20px;
            }
        }

        .btn-nueva-reserva {
            background-color: #0d9488;
            color: #ffffff;
            font-weight: 700;
            font-size: 13px;
            padding: 12px 18px;
            border-radius: 12px;
            border: none;
            width: 100%;
            text-align: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-nueva-reserva:hover {
            background-color: #0f766e;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.35);
        }

        .palette-instruction {
            font-size: 12px;
            color: #64748b;
            line-height: 1.4;
            font-weight: 500;
        }

        /* Filter Card Styles */
        .filter-card {
            background: #f8fafc;
            border: 1px solid #e2f0ea;
            border-radius: 14px;
            padding: 14px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .filter-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-card-title {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-card-badge {
            font-size: 10px;
            font-weight: 700;
            background: #e2f0ea;
            color: #0f766e;
            padding: 2px 6px;
            border-radius: 6px;
        }

        .filter-pills-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-pill-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 10px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
            text-align: left;
            width: 100%;
        }

        .filter-pill-btn:hover {
            background: #f0fdf9;
            border-color: #a7f3d0;
            color: #0d9488;
        }

        .filter-pill-btn.active-filter {
            background: #e6f7f2;
            border-color: #99f6e4;
            color: #0d9488;
            font-weight: 700;
            box-shadow: 0 1px 3px rgba(13, 148, 136, 0.1);
        }

        .filter-count-badge {
            font-size: 11px;
            font-weight: 700;
            background: #f1f5f9;
            color: #475569;
            padding: 1px 7px;
            border-radius: 6px;
        }

        .active-filter .filter-count-badge {
            background: #ffffff;
            color: #0d9488;
        }

        .count-emerald { color: #059669; }
        .count-amber { color: #d97706; }

        /* Select Input */
        .filter-select-wrapper {
            position: relative;
        }

        .filter-select-input {
            width: 100%;
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px 10px;
            cursor: pointer;
            outline: none;
            transition: border-color 0.15s ease;
        }

        .filter-select-input:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.15);
        }

        .reset-filter-btn {
            font-size: 11px;
            font-weight: 600;
            color: #0d9488;
            background: transparent;
            border: none;
            cursor: pointer;
            text-align: left;
            padding: 2px 0;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .reset-filter-btn:hover {
            text-decoration: underline;
        }

        /* Summary Card */
        .agenda-mini-stats {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 12px;
        }

        .mini-stat-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #e2e8f0;
        }
        .mini-stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            color: #64748b;
            font-weight: 500;
        }

        .stat-value {
            font-weight: 700;
            color: #1e293b;
        }

        /* RIGHT CALENDAR VIEW */
        .salonesgo-calendar-panel {
            display: flex;
            flex-direction: column;
            gap: 16px;
            min-width: 0;
        }

        .cal-header-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        @media (max-width: 640px) {
            .cal-header-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            .cal-header-bar > * {
                display: flex;
                justify-content: center;
            }
            .cal-month-title {
                text-align: center;
                font-size: 16px !important;
            }
            .cal-nav-group, .cal-view-group {
                width: 100%;
                justify-content: space-between;
            }
            .cal-view-btn, .cal-nav-btn {
                flex: 1;
                text-align: center;
            }
        }

        .cal-nav-group {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 4px;
        }
        .cal-nav-btn {
            background: transparent;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .cal-nav-btn:hover {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .cal-month-title {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cal-view-group {
            display: inline-flex;
            background: #f1f5f9;
            border-radius: 12px;
            padding: 4px;
            border: 1px solid #e2e8f0;
            gap: 4px;
        }
        .cal-view-btn {
            background: transparent;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .cal-view-btn.active {
            background: #ffffff;
            color: #0d9488;
            font-weight: 800;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }

        /* MONTH VIEW WRAPPER & TABLE */
        .month-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 6px;
            -webkit-overflow-scrolling: touch;
        }

        .salonesgo-month-table {
            width: 100%;
            min-width: 650px;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .salonesgo-month-table th {
            padding: 10px 6px;
            text-align: center;
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            background: #f8fafc;
        }
        .salonesgo-month-table td {
            height: 110px;
            vertical-align: top;
            padding: 6px;
            border: 1px solid #e2e8f0;
            position: relative;
            background: #ffffff;
            transition: background 0.15s ease;
        }
        .salonesgo-month-table td.out-month {
            background: #f8fafc;
            color: #cbd5e1;
        }
        .salonesgo-month-table td.today-cell {
            background: #f0fdf9;
            border: 2px solid #0d9488;
        }

        .day-header-num {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 4px;
        }
        .day-num-text {
            font-size: 12px;
            font-weight: 800;
            color: #475569;
        }
        .today-cell .day-num-text {
            color: #0d9488;
            background: #ccfbf1;
            padding: 1px 6px;
            border-radius: 6px;
        }

        /* Event Pill inside Cell */
        .salonesgo-event-pill {
            padding: 4px 8px;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            align-items: center;
            gap: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .salonesgo-event-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0,0,0,0.12);
        }
        .pill-purple { background: #8b5cf6; color: #ffffff; }
        .pill-blue { background: #0284c7; color: #ffffff; }
        .pill-rose { background: #e11d48; color: #ffffff; }
        .pill-amber { background: #d97706; color: #ffffff; }
        .pill-emerald { background: #0d9488; color: #ffffff; }

        /* WEEK VIEW WRAPPER & GRID */
        .week-scroll-wrapper {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 8px;
            -webkit-overflow-scrolling: touch;
        }

        .week-grid-layout {
            display: grid;
            grid-template-columns: repeat(7, minmax(135px, 1fr));
            gap: 10px;
            min-width: 680px;
        }

        .week-day-column {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 10px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            transition: all 0.15s ease;
        }

        .week-day-column.today-col {
            border: 2px solid #0d9488;
            background: #f0fdf9;
        }

        .week-day-header {
            text-align: center;
            padding-bottom: 8px;
            border-bottom: 1px solid #f1f5f9;
            margin-bottom: 8px;
        }

        .week-day-name {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
        }

        .week-day-num {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        .today-col .week-day-num {
            color: #0d9488;
        }

        .week-appointment-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #0d9488;
            border-radius: 10px;
            padding: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
            margin-bottom: 6px;
        }

        .week-appointment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            border-color: #cbd5e1;
        }

        .week-appointment-card.status-pending_verification {
            border-left-color: #f59e0b;
            background: #fffbeb;
        }

        .week-appointment-card.status-cancelled {
            border-left-color: #ef4444;
            background: #fef2f2;
            opacity: 0.7;
        }

        .week-appointment-card.status-completed {
            border-left-color: #3b82f6;
        }

        .week-apt-time {
            font-size: 11px;
            font-weight: 800;
            color: #0d9488;
            margin-bottom: 2px;
        }

        .status-pending_verification .week-apt-time {
            color: #d97706;
        }

        .status-cancelled .week-apt-time {
            color: #ef4444;
        }

        .status-completed .week-apt-time {
            color: #2563eb;
        }

        .week-apt-name {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .week-apt-service {
            font-size: 10px;
            font-weight: 500;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }

        /* CIERRA-STYLE MODALS */
        .cal-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }

        .cal-modal-container {
            background: #ffffff;
            border-radius: 22px;
            max-width: 540px;
            width: 100%;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(226, 240, 234, 0.8);
            border: 1px solid #e2f0ea;
            animation: cierraModalPop 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }

        @keyframes cierraModalPop {
            from { opacity: 0; transform: scale(0.96) translateY(6px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        /* Modal Header (Cierra Style) */
        .cierra-modal-header {
            padding: 18px 22px;
            background: linear-gradient(180deg, #f0fdf9 0%, #ffffff 100%);
            border-bottom: 1px solid #e2f0ea;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .cierra-modal-header.purple-theme {
            background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
            border-bottom-color: #f3e8ff;
        }

        .cierra-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cierra-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #0d9488;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);
            flex-shrink: 0;
        }

        .cierra-icon-box.purple {
            background: #7e22ce;
            box-shadow: 0 4px 10px rgba(126, 34, 206, 0.25);
        }

        .cierra-header-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .cierra-header-title-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .cierra-header-title {
            font-size: 15px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
        }

        .cierra-status-pill {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .cierra-status-pill.confirmed {
            background: #e6f7f2;
            color: #0d9488;
            border: 1px solid #a7f3d0;
        }
        .cierra-status-pill.pending_verification {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }
        .cierra-status-pill.completed {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }
        .cierra-status-pill.cancelled {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .cierra-header-sub {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
        }

        .cierra-modal-close {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #64748b;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            transition: all 0.15s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .cierra-modal-close:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }

        /* Modal Body */
        .cierra-modal-body {
            padding: 18px 22px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 72vh;
            overflow-y: auto;
        }

        /* Cierra Sub-Card Box */
        .cierra-section-box {
            background: #f8fafc;
            border: 1px solid #e2f0ea;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .cierra-section-header {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* 3-Column Financial Grid */
        .cierra-finance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        @media (max-width: 480px) {
            .cierra-finance-grid {
                grid-template-columns: 1fr;
            }
        }

        .cierra-finance-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            text-align: center;
        }
        .cierra-finance-box.highlight {
            background: #f0fdf9;
            border-color: #a7f3d0;
        }
        .cierra-finance-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }
        .cierra-finance-val {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
        .cierra-finance-val.green {
            color: #0d9488;
        }

        /* Modal Footer */
        .cierra-modal-footer {
            padding: 14px 22px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            flex-wrap: wrap;
        }
    </style>

    <div class="salonesgo-container">
        
        <!-- 2-COLUMN MAIN WORKSPACE -->
        <div class="salonesgo-grid-layout">
            
            <!-- LEFT CONTROL & FILTERS PANEL (ORGANIZED SAAS CARDS) -->
            <div class="salonesgo-left-panel">
                <!-- Primary Action Button -->
                <button wire:click="mountAction('newAppointment')" type="button" class="btn-nueva-reserva">
                    <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span>Nueva Reserva</span>
                </button>

                <!-- 1. Filter Card: Estado de Citas -->
                <div class="filter-card">
                    <div class="filter-card-header">
                        <span class="filter-card-title">Estado de Citas</span>
                        <span class="filter-card-badge">{{ $data['totalAppointments'] }} Total</span>
                    </div>

                    <div class="filter-pills-container">
                        <button wire:click="$set('statusFilter', 'all')" 
                                type="button"
                                class="filter-pill-btn {{ $statusFilter === 'all' ? 'active-filter' : '' }}">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #64748b; display: inline-block;"></span>
                                <span>Todas</span>
                            </div>
                            <span class="filter-count-badge">{{ $data['totalAppointments'] }}</span>
                        </button>

                        <button wire:click="$set('statusFilter', 'confirmed')" 
                                type="button"
                                class="filter-pill-btn {{ $statusFilter === 'confirmed' ? 'active-filter' : '' }}">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #0d9488; display: inline-block;"></span>
                                <span>Confirmadas</span>
                            </div>
                            <span class="filter-count-badge count-emerald">{{ $data['confirmedCount'] }}</span>
                        </button>

                        <button wire:click="$set('statusFilter', 'pending_verification')" 
                                type="button"
                                class="filter-pill-btn {{ $statusFilter === 'pending_verification' ? 'active-filter' : '' }}">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b; display: inline-block;"></span>
                                <span>Pendientes Nequi</span>
                            </div>
                            <span class="filter-count-badge count-amber">{{ $data['pendingCount'] }}</span>
                        </button>
                    </div>
                </div>

                <!-- 2. Filter Card: Tratamiento / Servicio -->
                <div class="filter-card">
                    <div class="filter-card-header">
                        <span class="filter-card-title">Tratamiento</span>
                        <span class="filter-card-badge">{{ count($services) }} Serv.</span>
                    </div>

                    <div class="filter-select-wrapper">
                        <select wire:model.live="serviceFilter" class="filter-select-input">
                            <option value="all">Todos los Servicios ({{ $data['totalAppointments'] }})</option>
                            @foreach($services as $svc)
                                <option value="{{ $svc->id }}">{{ $svc->name }} ({{ $svc->duration_minutes }}m)</option>
                            @endforeach
                        </select>
                    </div>

                    @if($serviceFilter !== 'all')
                        <button wire:click="$set('serviceFilter', 'all')" type="button" class="reset-filter-btn">
                            Restablecer a todos los servicios
                        </button>
                    @endif
                </div>

                <!-- 3. Summary Card: Actividad de la Agenda -->
                <div class="filter-card">
                    <div class="filter-card-header">
                        <span class="filter-card-title">Resumen de Agenda</span>
                    </div>
                    
                    <div class="agenda-mini-stats">
                        <div class="mini-stat-row">
                            <span class="stat-label">Citas del Período:</span>
                            <span class="stat-value">{{ $data['totalAppointments'] }}</span>
                        </div>
                        <div class="mini-stat-row">
                            <span class="stat-label">Por Validar:</span>
                            <span class="stat-value" style="color: #d97706; font-weight: 800;">{{ $data['pendingCount'] }}</span>
                        </div>
                        <div class="mini-stat-row">
                            <span class="stat-label">Confirmadas:</span>
                            <span class="stat-value" style="color: #0d9488; font-weight: 800;">{{ $data['confirmedCount'] }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RIGHT CALENDAR WORKSPACE -->
            <div class="salonesgo-calendar-panel">
                
                <!-- Toolbar: Nav, Title, Views -->
                <div class="cal-header-bar">
                    <div class="cal-nav-group">
                        <button wire:click="previousPeriod" type="button" class="cal-nav-btn">&lt;</button>
                        <button wire:click="nextPeriod" type="button" class="cal-nav-btn">&gt;</button>
                        <button wire:click="goToToday" type="button" class="cal-nav-btn" style="color: #0d9488; font-weight: 800;">Hoy</button>
                    </div>

                    <h2 class="cal-month-title">
                        {{ $data['title'] }}
                    </h2>

                    <div class="cal-view-group">
                        <button wire:click="setViewMode('month')" type="button" class="cal-view-btn {{ $viewMode === 'month' ? 'active' : '' }}">
                            Mes
                        </button>
                        <button wire:click="setViewMode('week')" type="button" class="cal-view-btn {{ $viewMode === 'week' ? 'active' : '' }}">
                            Semana
                        </button>
                        <button wire:click="setViewMode('day')" type="button" class="cal-view-btn {{ $viewMode === 'day' ? 'active' : '' }}">
                            Día
                        </button>
                    </div>
                </div>

                <!-- MONTH VIEW TABLE -->
                @if($viewMode === 'month')
                    <div class="month-scroll-wrapper">
                        <table class="salonesgo-month-table">
                            <thead>
                                <tr>
                                    <th>DOM</th>
                                    <th>LUN</th>
                                    <th>MAR</th>
                                    <th>MIÉ</th>
                                    <th>JUE</th>
                                    <th>VIE</th>
                                    <th>SÁB</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $dayChunks = array_chunk($data['days'], 7);
                                @endphp
                                @foreach($dayChunks as $week)
                                    <tr>
                                        @foreach($week as $day)
                                            <td class="{{ !$day['isCurrentMonth'] ? 'out-month' : '' }} {{ $day['isToday'] ? 'today-cell' : '' }}">
                                                <div class="day-header-num">
                                                    <span class="day-num-text">{{ $day['dayNumber'] }}</span>
                                                </div>

                                                <!-- Appointment Chips -->
                                                <div style="display: flex; flex-direction: column; gap: 3px; max-height: 80px; overflow-y: auto;">
                                                    @foreach($day['appointments'] as $aptIndex => $apt)
                                                        @php
                                                            $pillColors = ['pill-purple', 'pill-blue', 'pill-rose', 'pill-amber', 'pill-emerald'];
                                                            $pillClass = $pillColors[$aptIndex % count($pillColors)];
                                                        @endphp
                                                        <div wire:click="selectAppointment({{ $apt->id }})" 
                                                             class="salonesgo-event-pill {{ $pillClass }}"
                                                             title="{{ $apt->service?->name }} • {{ $apt->client_name }}">
                                                            <span style="font-size: 9px; opacity: 0.9;">{{ substr($apt->start_time, 0, 5) }}</span>
                                                            <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $apt->client_name }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <!-- WEEK VIEW -->
                @if($viewMode === 'week')
                    <div class="week-scroll-wrapper">
                        <div class="week-grid-layout">
                            @foreach($data['days'] as $day)
                                <div class="week-day-column {{ $day['isToday'] ? 'today-col' : '' }}">
                                    <div class="week-day-header">
                                        <span class="week-day-name">{{ $day['dayName'] }}</span>
                                        <div class="week-day-num">{{ $day['dayNumber'] }}</div>
                                    </div>

                                    <div style="display: flex; flex-direction: column; flex: 1;">
                                        @forelse($day['appointments'] as $apt)
                                            <div wire:click="selectAppointment({{ $apt->id }})" 
                                                 class="week-appointment-card status-{{ $apt->status->value }}"
                                                 title="{{ $apt->service?->name }} • {{ $apt->client_name }}">
                                                <div class="week-apt-time">{{ substr($apt->start_time, 0, 5) }} - {{ substr($apt->end_time, 0, 5) }}</div>
                                                <div class="week-apt-name">{{ $apt->client_name }}</div>
                                                <div class="week-apt-service">{{ $apt->service?->name }}</div>
                                            </div>
                                        @empty
                                            <div style="font-size: 11px; color: #94a3b8; text-align: center; margin: auto 0; padding: 20px 0;">
                                                Sin citas
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- DAY VIEW -->
                <!-- DAY VIEW (CIERRA SAAS DATA LIST STYLE) -->
                @if($viewMode === 'day')
                    @php 
                        $singleDay = $data['days'][0] ?? null; 
                        $dayAppointments = $singleDay ? $singleDay['appointments'] : collect();
                        $confirmedDayCount = $dayAppointments->where('status.value', 'confirmed')->count();
                        $pendingDayCount = $dayAppointments->where('status.value', 'pending_verification')->count();
                    @endphp
                    @if($singleDay)
                        <div style="background: #ffffff; border: 1px solid #e2f0ea; border-radius: 20px; padding: 22px; box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.04);">
                            <!-- Header Section Cierra Style -->
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 20px; padding-bottom: 14px; border-bottom: 1px solid #e2f0ea;">
                                <div>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <span style="width: 4px; height: 16px; background: #0d9488; border-radius: 9999px; display: inline-block;"></span>
                                        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">
                                            Agenda de Citas del Día
                                        </h3>
                                    </div>
                                    <p style="font-size: 12px; color: #64748b; font-weight: 600; margin-top: 3px; padding-left: 12px;">
                                        {{ $singleDay['fullDayName'] }}
                                    </p>
                                </div>
                                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;">
                                        {{ $dayAppointments->count() }} Total
                                    </span>
                                    @if($confirmedDayCount > 0)
                                        <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; background: #e6f7f2; color: #0d9488; border: 1px solid #a7f3d0;">
                                            {{ $confirmedDayCount }} Confirmadas
                                        </span>
                                    @endif
                                    @if($pendingDayCount > 0)
                                        <span style="font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; background: #fdf4ff; color: #7e22ce; border: 1px solid #f0abfc;">
                                            {{ $pendingDayCount }} Por Verificar
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Data List Rows -->
                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                @forelse($dayAppointments as $apt)
                                    @php
                                        $isPending = $apt->status->value === 'pending_verification';
                                        $rowBg = $isPending ? '#faf5ff' : '#ffffff';
                                        $rowBorder = $isPending ? '#e9d5ff' : '#e2e8f0';
                                    @endphp
                                    <div wire:click="selectAppointment({{ $apt->id }})" 
                                         style="background: {{ $rowBg }}; border: 1px solid {{ $rowBorder }}; border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between; cursor: pointer; transition: all 0.15s ease; gap: 14px; flex-wrap: wrap;"
                                         onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.06)';"
                                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                        
                                        <!-- Left Info -->
                                        <div style="display: flex; align-items: center; gap: 14px; min-width: 240px;">
                                            <div style="background: {{ $isPending ? '#7e22ce' : '#0d9488' }}; color: #ffffff; font-weight: 800; padding: 8px 12px; border-radius: 10px; font-size: 12px; text-align: center; min-width: 76px; box-shadow: 0 2px 6px {{ $isPending ? 'rgba(126, 34, 206, 0.25)' : 'rgba(13, 148, 136, 0.25)' }};">
                                                <div>{{ substr($apt->start_time, 0, 5) }}</div>
                                                <div style="font-size: 9px; opacity: 0.85; font-weight: 600;">{{ substr($apt->end_time, 0, 5) }}</div>
                                            </div>
                                            <div>
                                                <div style="font-weight: 800; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                                    <span>{{ $apt->client_name }}</span>
                                                    <span style="font-size: 10px; color: #64748b; font-weight: 700;">#{{ $apt->appointment_number }}</span>
                                                </div>
                                                <div style="font-size: 12px; color: #64748b; font-weight: 500; margin-top: 2px;">
                                                    {{ $apt->service?->name }} • {{ $apt->service?->duration_minutes }} min
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Right Metrics & Actions -->
                                        <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                                            <div style="text-align: right;">
                                                <div style="font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase;">Valor</div>
                                                <div style="font-size: 13px; font-weight: 800; color: #0f172a;">${{ number_format($apt->service?->price ?: $apt->total_amount, 0, ',', '.') }}</div>
                                            </div>

                                            <span class="cierra-status-pill {{ $apt->status->value }}">
                                                <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
                                                <span>{{ $apt->status->label() }}</span>
                                            </span>

                                            <button type="button" 
                                                    style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; font-weight: 700; font-size: 11px; padding: 6px 12px; border-radius: 8px; cursor: pointer; transition: all 0.15s ease;">
                                                Ver Ficha
                                            </button>
                                        </div>
                                    </div>
                                @empty
                                    <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 14px; padding: 40px 20px; text-align: center; color: #64748b;">
                                        <p style="font-size: 13px; font-weight: 600; margin: 0;">No hay citas agendadas para esta fecha.</p>
                                        <p style="font-size: 11px; margin-top: 4px; color: #94a3b8;">Usa el botón "Nueva Reserva" para programar una cita.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endif
                @endif

            </div>

        </div>

    </div>

    <!-- DETAIL MODAL (CIERRA SAAS NODE STYLE) -->
    @if($showDetailModal && $selectedApt)
        <div class="cal-modal-overlay" wire:keydown.escape="closeModal">
            <div class="cal-modal-container">
                <!-- Header Cierra -->
                <div class="cierra-modal-header">
                    <div class="cierra-header-left">
                        <div class="cierra-icon-box">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="cierra-header-info">
                            <div class="cierra-header-title-row">
                                <h3 class="cierra-header-title">Cita #{{ $selectedApt->appointment_number }}</h3>
                                <span class="cierra-status-pill {{ $selectedApt->status->value }}">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
                                    <span>{{ $selectedApt->status->label() }}</span>
                                </span>
                            </div>
                            <span class="cierra-header-sub">{{ $selectedApt->client_name }}</span>
                        </div>
                    </div>
                    <button wire:click="closeModal" type="button" class="cierra-modal-close">✕</button>
                </div>

                <!-- Body (Concise, Ordered & Structured) -->
                <div class="cierra-modal-body">
                    
                    <!-- 1. Clienta & Servicio Box -->
                    <div class="cierra-section-box">
                        <div class="cierra-section-header">
                            <span>Reserva & Clienta</span>
                            <a href="https://wa.me/57{{ $selectedApt->client_phone }}?text={{ urlencode('Hola ' . $selectedApt->client_name . ', te escribimos de Paola Aguilera Belleza sobre tu cita #' . $selectedApt->appointment_number) }}" 
                               target="_blank" 
                               style="color: #0d9488; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 4px; font-size: 11px;">
                                WhatsApp: +57 {{ $selectedApt->client_phone }}
                            </a>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                            <div>
                                <div style="font-weight: 800; font-size: 14px; color: #0f172a;">{{ $selectedApt->service?->name }}</div>
                                <div style="font-size: 11px; color: #64748b; font-weight: 600;">{{ $selectedApt->service?->category ? ($selectedApt->service->category instanceof \App\Enums\ServiceCategory ? $selectedApt->service->category->label() : $selectedApt->service->category) . ' • ' : '' }}{{ $selectedApt->service?->duration_minutes }} min</div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; font-weight: 700; color: #64748b;">TOTAL</div>
                                <div style="font-weight: 800; font-size: 14px; color: #0f172a;">${{ number_format($selectedApt->service?->price ?: $selectedApt->total_amount, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Programación (Fecha & Horario) -->
                    <div class="cierra-section-box">
                        <div class="cierra-section-header">
                            <span>Fecha & Horario Programado</span>
                        </div>
                        <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="background: #e6f7f2; color: #0d9488; font-weight: 800; padding: 6px 10px; border-radius: 8px; font-size: 12px;">
                                    {{ $selectedApt->appointment_date->format('d/m/Y') }}
                                </div>
                                <div>
                                    <div style="font-size: 13px; font-weight: 800; color: #0f172a;">
                                        {{ substr($selectedApt->start_time, 0, 5) }} — {{ substr($selectedApt->end_time, 0, 5) }}
                                    </div>
                                    <div style="font-size: 10px; color: #64748b;">Horario confirmado en agenda</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Liquidación Financiera en 3 Pastillas -->
                    <div class="cierra-finance-grid">
                        <div class="cierra-finance-box">
                            <div class="cierra-finance-label">Valor Servicio</div>
                            <div class="cierra-finance-val">${{ number_format($selectedApt->service?->price ?: $selectedApt->total_amount, 0, ',', '.') }}</div>
                        </div>
                        <div class="cierra-finance-box {{ $selectedApt->status->value === 'pending_verification' ? 'highlight-purple' : 'highlight' }}" style="{{ $selectedApt->status->value === 'pending_verification' ? 'background: #faf5ff; border-color: #e9d5ff;' : '' }}">
                            <div class="cierra-finance-label" style="{{ $selectedApt->status->value === 'pending_verification' ? 'color: #7e22ce;' : 'color: #0d9488;' }}">
                                {{ $selectedApt->status->value === 'pending_verification' ? 'Abono a Validar' : 'Abono Recibido' }}
                            </div>
                            <div class="cierra-finance-val {{ $selectedApt->status->value === 'pending_verification' ? '' : 'green' }}" style="{{ $selectedApt->status->value === 'pending_verification' ? 'color: #7e22ce;' : '' }}">
                                ${{ number_format($selectedApt->deposit_amount ?: $selectedApt->deposit_paid, 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="cierra-finance-box">
                            <div class="cierra-finance-label">Saldo en Estudio</div>
                            <div class="cierra-finance-val">${{ number_format($selectedApt->balance_due, 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <!-- 4. Sección de Comprobante Nequi Directa -->
                    @if($selectedApt->status->value === 'pending_verification' || $selectedApt->deposit_proof_image)
                        @php
                            $proofUrl = $selectedApt->deposit_proof_image ? asset('storage/' . $selectedApt->deposit_proof_image) : null;
                        @endphp
                        <div class="cierra-section-box" style="background: #faf5ff; border-color: #f3e8ff;">
                            <div class="cierra-section-header">
                                <span style="color: #7e22ce;">Comprobante de Transferencia Nequi</span>
                                @if($proofUrl)
                                    <a href="{{ $proofUrl }}" target="_blank" 
                                       style="color: #7e22ce; font-weight: 700; text-decoration: none; font-size: 11px;">
                                        Ver tamaño completo
                                    </a>
                                @endif
                            </div>

                            @if($proofUrl)
                                <div style="background: #0f172a; padding: 8px; border-radius: 10px; text-align: center;">
                                    <img src="{{ $proofUrl }}" 
                                         style="max-height: 200px; max-width: 100%; border-radius: 6px; margin: 0 auto; display: block; object-fit: contain;" 
                                         alt="Comprobante Nequi"
                                         onerror="this.onerror=null; this.src='/storage/samples/comprobante_nequi_muestra.png';">
                                </div>
                            @else
                                <div style="background: #ffffff; border: 1px dashed #d8b4fe; border-radius: 10px; padding: 12px; text-align: center; color: #7e22ce; font-size: 11px;">
                                    No se adjunto archivo de comprobante.
                                </div>
                            @endif

                            @if($selectedApt->status->value === 'pending_verification')
                                <div style="margin-top: 4px;">
                                    <input type="text" 
                                           wire:model="verificationNotes" 
                                           placeholder="Notas de validación interna (opcional)..." 
                                           style="width: 100%; border: 1px solid #e9d5ff; border-radius: 8px; font-size: 12px; padding: 7px 10px; background: #ffffff; outline: none; color: #1e293b;">
                                </div>
                            @endif
                        </div>
                    @endif

                </div>

                <!-- Footer (Acciones Puntuales y Aprobación Directa) -->
                <div class="cierra-modal-footer">
                    <button wire:click="closeModal" type="button" class="cal-nav-btn" style="border: 1px solid #cbd5e1; background: white; font-size: 12px; padding: 8px 16px;">
                        Cerrar
                    </button>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @if($selectedApt->status->value === 'pending_verification')
                            <button wire:click="rejectNequiDeposit({{ $selectedApt->id }})" type="button" 
                                    style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">
                                Rechazar
                            </button>
                            <button wire:click="approveNequiDeposit({{ $selectedApt->id }})" type="button" class="btn-nueva-reserva" 
                                    style="padding: 8px 18px; font-size: 12px;">
                                Aprobar Abono y Confirmar
                            </button>
                        @elseif($selectedApt->status->value === 'confirmed')
                            <button wire:click="cancelAppointment({{ $selectedApt->id }})" type="button" 
                                    style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; padding: 8px 14px; border-radius: 10px; font-size: 12px; font-weight: 800; cursor: pointer;">
                                Cancelar Cita
                            </button>
                            <button wire:click="markAsCompleted({{ $selectedApt->id }})" type="button" class="btn-nueva-reserva" 
                                    style="padding: 8px 16px; font-size: 12px;">
                                Marcar Completada
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- NEQUI VALIDATION MODAL (CIERRA SAAS NODE STYLE) -->
    @if($showNequiModal && $selectedApt)
        <div class="cal-modal-overlay" wire:keydown.escape="closeModal">
            <div class="cal-modal-container" style="max-width: 520px;">
                <!-- Header Cierra Purple -->
                <div class="cierra-modal-header purple-theme">
                    <div class="cierra-header-left">
                        <div class="cierra-icon-box purple">
                            <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="cierra-header-info">
                            <div class="cierra-header-title-row">
                                <h3 class="cierra-header-title">Validación de Abono Nequi</h3>
                                <span class="cierra-status-pill pending_verification">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
                                    <span>Por Verificar</span>
                                </span>
                            </div>
                            <span class="cierra-header-sub">#{{ $selectedApt->appointment_number }} • {{ $selectedApt->client_name }}</span>
                        </div>
                    </div>
                    <button wire:click="closeModal" type="button" class="cierra-modal-close">✕</button>
                </div>

                <!-- Body -->
                <div class="cierra-modal-body">
                    
                    <!-- Resumen Requerido -->
                    <div style="background: #fdf4ff; border: 1px solid #f0abfc; border-radius: 12px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 11px; font-weight: 800; color: #86198f; text-transform: uppercase;">Monto de Abono Requerido:</span>
                        <span style="font-size: 15px; font-weight: 900; color: #86198f;">${{ number_format($selectedApt->deposit_amount, 0, ',', '.') }} COP</span>
                    </div>

                    <!-- Image Preview -->
                    <div style="background: #0f172a; padding: 10px; border-radius: 14px; text-align: center;">
                        @if($selectedApt->deposit_proof_image)
                            <img src="{{ asset('storage/' . $selectedApt->deposit_proof_image) }}" 
                                 style="max-height: 260px; max-width: 100%; border-radius: 8px; margin: 0 auto; display: block; object-fit: contain;" 
                                 alt="Comprobante Nequi">
                        @else
                            <p style="color: #94a3b8; font-size: 12px; padding: 20px;">No se encontró archivo adjunto.</p>
                        @endif
                    </div>

                    <!-- Notas -->
                    <textarea wire:model="verificationNotes" placeholder="Notas internas de validación (opcional)..." 
                              style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12px; padding: 10px; outline: none;"></textarea>
                </div>

                <!-- Footer -->
                <div class="cierra-modal-footer">
                    <button wire:click="rejectNequiDeposit({{ $selectedApt->id }})" type="button" 
                            style="background: #fee2e2; color: #dc2626; border: none; padding: 9px 16px; border-radius: 10px; font-weight: 800; font-size: 12px; cursor: pointer;">
                        Rechazar
                    </button>
                    <button wire:click="approveNequiDeposit({{ $selectedApt->id }})" type="button" class="btn-nueva-reserva" 
                            style="padding: 9px 20px; font-size: 12px;">
                        Aprobar Abono y Confirmar
                    </button>
                </div>
            </div>
        </div>
    @endif

</x-filament-panels::page>
