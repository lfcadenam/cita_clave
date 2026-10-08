<x-filament-panels::page>
    @php
        $templates = $this->templates;
        $active = $this->activeTemplate;
        $smtp = $this->smtpStatus;
        $previewUrl = route('superadmin.email-preview', ['key' => $selectedTemplate, 'appointment_id' => $selectedAppointmentId]);
    @endphp

    <style>
        .nuvex-em-wrapper {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: 20px;
            width: 100%;
        }

        /* 1. SMTP Status Banner */
        .nuvex-em-banner {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            box-shadow: 0 4px 16px -2px rgba(13, 148, 136, 0.04);
        }

        .nuvex-em-banner-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .nuvex-em-banner-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #f0fdf4;
            color: #0d9488;
        }

        .nuvex-em-banner-icon svg {
            width: 20px;
            height: 20px;
            max-width: 20px;
            max-height: 20px;
        }

        .nuvex-em-banner-title {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nuvex-em-badge-status {
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 9999px;
            background: #dcfce7;
            color: #15803d;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .nuvex-em-banner-meta {
            font-size: 12px;
            color: #64748b;
            margin-top: 2px;
        }

        .nuvex-em-banner-meta strong {
            color: #334155;
            font-weight: 600;
        }

        .nuvex-em-banner-right {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }

        /* 2. Main 2-Column Grid Layout */
        .nuvex-em-grid {
            display: grid;
            grid-template-columns: 360px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        @media (max-width: 1080px) {
            .nuvex-em-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        /* 3. Left Column: Template Cards & Details */
        .nuvex-em-left-col {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .nuvex-em-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            box-shadow: 0 4px 18px -2px rgba(13, 148, 136, 0.04);
        }

        .nuvex-em-card-title {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 12px;
        }

        /* Template Selection Items */
        .nuvex-em-item-btn {
            width: 100%;
            text-align: left;
            border-radius: 14px;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.15s ease;
            margin-bottom: 8px;
            display: block;
        }

        .nuvex-em-item-btn:hover {
            border-color: #cbd5e1;
            background: #f8fafc;
        }

        .nuvex-em-item-btn.is-active {
            border: 2px solid #0d9488;
            background: #f0fdfa;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.08);
        }

        .nuvex-em-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .nuvex-em-item-name {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }

        .nuvex-em-item-btn.is-active .nuvex-em-item-name {
            color: #0f766e;
        }

        .nuvex-em-item-badge {
            font-size: 10px;
            font-weight: 800;
            padding: 2px 6px;
            border-radius: 6px;
            background: #f1f5f9;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .nuvex-em-item-btn.is-active .nuvex-em-item-badge {
            background: #0d9488;
            color: #ffffff;
        }

        .nuvex-em-item-desc {
            font-size: 11.5px;
            color: #64748b;
            margin-top: 4px;
        }

        /* Template Spec Field Group */
        .nuvex-em-spec-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
            font-size: 12.5px;
        }

        .nuvex-em-spec-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .nuvex-em-spec-value {
            font-weight: 500;
            color: #1e293b;
            line-height: 1.5;
        }

        .nuvex-em-subject-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            font-family: monospace;
            font-size: 11px;
            color: #334155;
            word-break: break-word;
        }

        .nuvex-em-var-list {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-top: 4px;
        }

        .nuvex-em-var-item {
            display: flex;
            align-items: baseline;
            gap: 6px;
            background: #f8fafc;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11.5px;
            border: 1px solid #f1f5f9;
        }

        .nuvex-em-var-item code {
            font-family: monospace;
            font-weight: 700;
            color: #0f766e;
            flex-shrink: 0;
        }

        .nuvex-em-var-item span {
            color: #64748b;
            font-size: 11px;
        }

        /* Test Email Sender Form */
        .nuvex-em-input {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            font-size: 12.5px;
            color: #0f172a;
            background: #f8fafc;
            outline: none;
            transition: all 0.15s ease;
        }

        .nuvex-em-input:focus {
            background: #ffffff;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .nuvex-em-send-btn {
            width: 100%;
            margin-top: 8px;
            background: linear-gradient(135deg, #0d9488 0%, #00C975 100%);
            color: #ffffff;
            font-weight: 700;
            font-size: 12.5px;
            padding: 10px 16px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px -2px rgba(13, 148, 136, 0.35);
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .nuvex-em-send-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px -2px rgba(13, 148, 136, 0.45);
        }

        .nuvex-em-send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* 4. Right Column: Live Interactive Preview */
        .nuvex-em-right-col {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* Preview Toolbar */
        .nuvex-em-toolbar {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
            box-shadow: 0 2px 10px rgba(13, 148, 136, 0.03);
        }

        .nuvex-em-toolbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #64748b;
        }

        .nuvex-em-select {
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            font-size: 12px;
            background: #f8fafc;
            color: #0f172a;
            outline: none;
            max-width: 260px;
        }

        .nuvex-em-toolbar-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nuvex-em-device-pills {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 2px;
            display: flex;
            gap: 2px;
        }

        .nuvex-em-device-btn {
            border: none;
            background: transparent;
            font-size: 11.5px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            color: #64748b;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .nuvex-em-device-btn.is-active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        .nuvex-em-tab-link {
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 4px 10px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
            transition: all 0.15s ease;
        }

        .nuvex-em-tab-link:hover {
            background: #f8fafc;
            color: #0f172a;
        }

        /* Preview Frame */
        .nuvex-em-frame-wrap {
            background: #f8fafc;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            display: flex;
            justify-content: center;
            min-height: 680px;
            overflow-x: auto;
        }

        .nuvex-em-frame-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: width 0.25s ease;
        }

        .nuvex-em-frame-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: #64748b;
        }

        .nuvex-em-frame-header-title {
            font-weight: 600;
            color: #334155;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 440px;
        }

        .nuvex-em-iframe {
            width: 100%;
            height: 640px;
            border: none;
            background: #f8fafc;
        }
    </style>

    <div class="nuvex-em-wrapper">
        <!-- 1. SMTP Header Banner -->
        <div class="nuvex-em-banner">
            <div class="nuvex-em-banner-left">
                <div class="nuvex-em-banner-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <div class="nuvex-em-banner-title">
                        <span>Servidor SMTP ({{ strtoupper($smtp['mailer']) }})</span>
                        <span class="nuvex-em-badge-status">
                            {{ $smtp['is_configured'] ? 'Conectado' : 'Sin Configurar' }}
                        </span>
                    </div>
                    <div class="nuvex-em-banner-meta">
                        Host: <strong>{{ $smtp['host'] }}:{{ $smtp['port'] }}</strong> &bull; 
                        Remitente: <strong>{{ $smtp['from_address'] ?: 'Sin configurar' }}</strong> &bull; 
                        Usuario: <strong>{{ $smtp['username'] ?: 'Sin usuario' }}</strong>
                    </div>
                </div>
            </div>
            <div class="nuvex-em-banner-right">
                Sistema de Notificaciones Transaccionales Nuvex
            </div>
        </div>

        <!-- 2. Main 2-Column Grid -->
        <div class="nuvex-em-grid">
            <!-- Left Column: Templates & Details -->
            <div class="nuvex-em-left-col">
                <!-- Templates Selector Card -->
                <div class="nuvex-em-card">
                    <div class="nuvex-em-card-title">Plantillas Disponibles</div>

                    @foreach($templates as $key => $tpl)
                        <button
                            type="button"
                            wire:click="selectTemplate('{{ $key }}')"
                            class="nuvex-em-item-btn {{ $selectedTemplate === $key ? 'is-active' : '' }}"
                        >
                            <div class="nuvex-em-item-header">
                                <span class="nuvex-em-item-name">{{ $tpl['title'] }}</span>
                                <span class="nuvex-em-item-badge">{{ $tpl['badge'] }}</span>
                            </div>
                            <div class="nuvex-em-item-desc">
                                Destinatario: <strong>{{ $tpl['recipient'] }}</strong>
                            </div>
                        </button>
                    @endforeach
                </div>

                <!-- Template Technical Specs Card -->
                <div class="nuvex-em-card">
                    <div class="nuvex-em-card-title">Ficha Técnica de la Plantilla</div>

                    <div class="nuvex-em-spec-group">
                        <div>
                            <div class="nuvex-em-spec-label">Evento Detonante (Trigger)</div>
                            <div class="nuvex-em-spec-value">{{ $active['trigger'] }}</div>
                        </div>

                        <div>
                            <div class="nuvex-em-spec-label">Asunto Predeterminado</div>
                            <div class="nuvex-em-subject-box">{{ $active['subject_example'] }}</div>
                        </div>

                        <div>
                            <div class="nuvex-em-spec-label">Descripción del Flujo</div>
                            <div class="nuvex-em-spec-value">{{ $active['description'] }}</div>
                        </div>

                        <div>
                            <div class="nuvex-em-spec-label">Variables Inyectadas</div>
                            <div class="nuvex-em-var-list">
                                @foreach($active['variables'] as $var => $desc)
                                    <div class="nuvex-em-var-item">
                                        <code>{{ $var }}</code>
                                        <span>{{ $desc }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Send Real Test Email Card -->
                <div class="nuvex-em-card">
                    <div class="nuvex-em-card-title">Enviar Correo de Prueba</div>
                    <p style="font-size: 11.5px; color: #64748b; margin-bottom: 10px; line-height: 1.4;">
                        Envía esta plantilla con datos de muestra a tu correo para verificar el diseño real en tu bandeja de entrada.
                    </p>

                    <div>
                        <input
                            type="email"
                            wire:model.defer="testEmail"
                            placeholder="tu-correo@ejemplo.com"
                            class="nuvex-em-input"
                        />
                        @error('testEmail')
                            <div style="color: #e11d48; font-size: 11px; margin-top: 4px;">{{ $message }}</div>
                        @enderror

                        <button
                            type="button"
                            wire:click="sendTestEmail"
                            wire:loading.attr="disabled"
                            class="nuvex-em-send-btn"
                        >
                            <span wire:loading.remove wire:target="sendTestEmail">
                                Enviar Correo de Prueba
                            </span>
                            <span wire:loading wire:target="sendTestEmail">
                                Enviando por SMTP...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Interactive Live Preview -->
            <div class="nuvex-em-right-col">
                <!-- Toolbar -->
                <div class="nuvex-em-toolbar">
                    <div class="nuvex-em-toolbar-left">
                        <span>Cita de prueba:</span>
                        <select
                            wire:model.live="selectedAppointmentId"
                            class="nuvex-em-select"
                        >
                            <option value="">Última cita registrada</option>
                            @foreach($this->recentAppointments as $aptItem)
                                <option value="{{ $aptItem['id'] }}">{{ $aptItem['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="nuvex-em-toolbar-right">
                        <!-- Desktop / Mobile Pill Switcher -->
                        <div class="nuvex-em-device-pills">
                            <button
                                type="button"
                                wire:click="setPreviewDevice('desktop')"
                                class="nuvex-em-device-btn {{ $previewDevice === 'desktop' ? 'is-active' : '' }}"
                            >
                                Escritorio (620px)
                            </button>
                            <button
                                type="button"
                                wire:click="setPreviewDevice('mobile')"
                                class="nuvex-em-device-btn {{ $previewDevice === 'mobile' ? 'is-active' : '' }}"
                            >
                                Móvil (380px)
                            </button>
                        </div>

                        <!-- Open in full browser tab -->
                        <a
                            href="{{ $previewUrl }}"
                            target="_blank"
                            class="nuvex-em-tab-link"
                            title="Abrir en pestaña nueva"
                        >
                            <span>Nueva pestaña</span>
                            <span style="font-size: 11px;">&UpperRightArrow;</span>
                        </a>
                    </div>
                </div>

                <!-- Preview Frame -->
                <div class="nuvex-em-frame-wrap">
                    <div
                        class="nuvex-em-frame-card"
                        style="width: {{ $previewDevice === 'mobile' ? '380px' : '620px' }};"
                    >
                        <div class="nuvex-em-frame-header">
                            <span class="nuvex-em-frame-header-title">
                                {{ $active['subject_example'] }}
                            </span>
                            <span style="font-family: monospace; font-size: 10px; color: #94a3b8;">
                                {{ $previewDevice === 'mobile' ? '380px' : '620px' }}
                            </span>
                        </div>

                        <iframe
                            src="{{ $previewUrl }}"
                            class="nuvex-em-iframe"
                            title="Vista Previa de Correo"
                        ></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
