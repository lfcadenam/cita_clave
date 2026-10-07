<x-filament-widgets::widget>
    @php
        $data = $this->getViewData();
        $user = $data['user'];
        $tenant = $data['tenant'];
        $schedule = $data['schedule'];
        $next = $data['nextAppointment'];
        $isAvailable = $data['isAvailableToday'];

        $openTime = $schedule?->open_time ? substr($schedule->open_time, 0, 5) : '08:00';
        $closeTime = $schedule?->close_time ? substr($schedule->close_time, 0, 5) : '18:00';
        $calendarUrl = \App\Filament\Pages\AppointmentCalendarPage::getUrl();
    @endphp

    <style>
        .nuvex-spotlight-card {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: #FFFFFF;
            border-radius: 28px;
            border: 1px solid #E2E8F0;
            padding: 1.5rem 2rem;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04), 0 4px 12px -2px rgba(0, 0, 0, 0.02);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            width: 100%;
            box-sizing: border-box;
        }

        .dark .nuvex-spotlight-card {
            background: #1E293B;
            border-color: rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.3);
        }

        .nuvex-spotlight-bg-glow {
            position: absolute;
            top: -40px;
            right: -40px;
            width: 260px;
            height: 260px;
            background: radial-gradient(circle, rgba(0, 201, 117, 0.12) 0%, rgba(0, 201, 117, 0) 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .dark .nuvex-spotlight-bg-glow {
            background: radial-gradient(circle, rgba(0, 201, 117, 0.08) 0%, rgba(0, 201, 117, 0) 70%);
        }

        .nuvex-spotlight-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .nuvex-spotlight-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.25rem;
            }
        }

        /* Profile Left Section */
        .nuvex-spotlight-left {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .nuvex-spotlight-avatar-box {
            position: relative;
            flex-shrink: 0;
        }

        .nuvex-spotlight-avatar {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: #FFFFFF;
            font-size: 1.4rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.3);
            border: 3px solid rgba(255, 255, 255, 0.9);
        }

        .dark .nuvex-spotlight-avatar {
            border-color: #1E293B;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
        }

        .nuvex-spotlight-status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #0d9488;
            border: 2.5px solid #FFFFFF;
        }

        .dark .nuvex-spotlight-status-dot {
            border-color: #1E293B;
        }

        .nuvex-spotlight-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .nuvex-spotlight-name-row {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .nuvex-spotlight-name {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -0.01em;
            margin: 0;
            line-height: 1.2;
        }

        .dark .nuvex-spotlight-name {
            color: #F8FAFC;
        }

        .nuvex-spotlight-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .nuvex-spotlight-badge.available {
            background: #e6f7f2;
            color: #0f766e;
            border: 1px solid rgba(13, 148, 136, 0.3);
        }

        .dark .nuvex-spotlight-badge.available {
            background: rgba(13, 148, 136, 0.15);
            color: #0d9488;
            border-color: rgba(13, 148, 136, 0.3);
        }

        .nuvex-spotlight-badge.off {
            background: #F1F5F9;
            color: #64748B;
            border: 1px solid #CBD5E1;
        }

        .dark .nuvex-spotlight-badge.off {
            background: rgba(255, 255, 255, 0.08);
            color: #94A3B8;
            border-color: rgba(255, 255, 255, 0.12);
        }

        .nuvex-spotlight-role {
            font-size: 0.825rem;
            font-weight: 600;
            color: #64748B;
        }

        .dark .nuvex-spotlight-role {
            color: #94A3B8;
        }

        .nuvex-spotlight-meta-row {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            margin-top: 6px;
            font-size: 0.78rem;
            color: #64748B;
            flex-wrap: wrap;
        }

        .dark .nuvex-spotlight-meta-row {
            color: #94A3B8;
        }

        .nuvex-spotlight-meta-item strong {
            color: #0F172A;
            font-weight: 700;
        }

        .dark .nuvex-spotlight-meta-item strong {
            color: #FFFFFF;
        }

        .nuvex-spotlight-meta-item strong.emerald {
            color: #0f766e;
        }

        .dark .nuvex-spotlight-meta-item strong.emerald {
            color: #0d9488;
        }

        /* Actions Right Section */
        .nuvex-spotlight-right {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-shrink: 0;
        }

        .nuvex-spotlight-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #0d9488;
            color: #FFFFFF !important;
            font-weight: 700;
            font-size: 0.8125rem;
            padding: 10px 20px;
            border-radius: 14px;
            text-decoration: none !important;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.28);
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .nuvex-spotlight-btn:hover {
            background: #0f766e;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(13, 148, 136, 0.38);
        }

        .nuvex-spotlight-carousel {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 4px 8px;
        }

        .dark .nuvex-spotlight-carousel {
            background: #182234;
            border-color: rgba(255, 255, 255, 0.08);
        }

        .nuvex-carousel-btn {
            width: 26px;
            height: 26px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #64748B;
            font-size: 0.85rem;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s ease;
        }

        .nuvex-carousel-btn:hover {
            background: #E2E8F0;
            color: #0F172A;
        }

        .dark .nuvex-carousel-btn {
            color: #94A3B8;
        }

        .dark .nuvex-carousel-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #FFFFFF;
        }

        .nuvex-carousel-count {
            font-size: 0.72rem;
            font-weight: 700;
            color: #94A3B8;
            padding: 0 4px;
        }
    </style>

    <div class="nuvex-spotlight-card">
        <div class="nuvex-spotlight-bg-glow"></div>

        <div class="nuvex-spotlight-content">
            <!-- Left: Profile & Schedule -->
            <div class="nuvex-spotlight-left">
                <div class="nuvex-spotlight-avatar-box">
                    <div class="nuvex-spotlight-avatar">
                        {{ strtoupper(substr($user->name ?? ($tenant->name ?? 'PA'), 0, 2)) }}
                    </div>
                    <span class="nuvex-spotlight-status-dot"></span>
                </div>

                <div class="nuvex-spotlight-info">
                    <div class="nuvex-spotlight-name-row">
                        <h2 class="nuvex-spotlight-name">
                            {{ $user->name ?? ($tenant->name ?? 'Paola Andrea Aguilera') }}
                        </h2>
                        @if($isAvailable)
                            <span class="nuvex-spotlight-badge available">Disponible Hoy</span>
                        @else
                            <span class="nuvex-spotlight-badge off">Fuera de Horario</span>
                        @endif
                    </div>

                    <div class="nuvex-spotlight-role">
                        Especialista Principal & Directora &bull; {{ $tenant->name ?? 'Estudio Paola Aguilera' }}
                    </div>

                    <div class="nuvex-spotlight-meta-row">
                        <div class="nuvex-spotlight-meta-item">
                            <span>Jornada:</span>
                            <strong>{{ $openTime }} &ndash; {{ $closeTime }}</strong>
                        </div>

                        <div class="nuvex-spotlight-meta-item">
                            <span>Próxima atención:</span>
                            @if($next)
                                <strong class="emerald">{{ substr($next->start_time, 0, 5) }} ({{ \Illuminate\Support\Str::limit($next->client_name, 16) }})</strong>
                            @else
                                <span>Sin citas pendientes hoy</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Actions & Carousel -->
            <div class="nuvex-spotlight-right">
                <a href="{{ $calendarUrl }}" class="nuvex-spotlight-btn">
                    <span>Ver Agenda Interactiva</span>
                </a>

                <div class="nuvex-spotlight-carousel">
                    <button type="button" title="Anterior especialista" class="nuvex-carousel-btn">&lsaquo;</button>
                    <span class="nuvex-carousel-count">1/1</span>
                    <button type="button" title="Siguiente especialista" class="nuvex-carousel-btn">&rsaquo;</button>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
