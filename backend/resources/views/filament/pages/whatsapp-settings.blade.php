<x-filament-panels::page>
    <style>
        .nuvex-wa-wrapper {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: 24px;
            width: 100%;
            box-sizing: border-box;
        }

        .nuvex-wa-wrapper svg {
            width: 18px;
            height: 18px;
            max-width: 18px;
            max-height: 18px;
            flex-shrink: 0;
        }

        /* 1. Barra Superior de Estado en Vivo */
        .nuvex-wa-banner {
            background: #ffffff;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            padding: 22px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 18px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.03), 0 4px 10px -2px rgba(0, 0, 0, 0.02);
        }

        .nuvex-wa-banner-left {
            display: flex;
            align-items: center;
            gap: 18px;
        }

        .nuvex-wa-status-icon {
            width: 52px;
            height: 52px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }

        .nuvex-wa-status-icon svg {
            width: 26px;
            height: 26px;
            max-width: 26px;
            max-height: 26px;
        }

        .nuvex-wa-status-icon.is-connected {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .nuvex-wa-status-icon.is-disconnected {
            background: #fffbeb;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .nuvex-wa-pill-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 4px;
        }

        .nuvex-wa-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .nuvex-wa-pill.is-connected {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .nuvex-wa-pill.is-disconnected {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .nuvex-wa-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
            position: relative;
        }

        .nuvex-wa-dot.is-connected {
            background: #10b981;
            box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.3);
        }

        .nuvex-wa-dot.is-disconnected {
            background: #f59e0b;
        }

        .nuvex-wa-driver-tag {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 3px 12px;
            border-radius: 9999px;
            letter-spacing: 0.02em;
        }

        .nuvex-wa-banner-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
            margin: 4px 0 2px 0;
            letter-spacing: -0.015em;
        }

        .nuvex-wa-banner-sub {
            font-size: 12.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.45;
            font-weight: 500;
        }

        /* 2. Grid Principal de 2 Columnas */
        .nuvex-wa-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.85fr);
            gap: 24px;
            align-items: start;
        }

        @media (max-width: 1100px) {
            .nuvex-wa-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .nuvex-wa-col {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        /* 3. Tarjetas Elevadas Suaves Nuvex */
        .nuvex-wa-card {
            background: #ffffff;
            border-radius: 28px;
            border: 1px solid #e2e8f0;
            padding: 26px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.035), 0 4px 10px -2px rgba(0, 0, 0, 0.02);
            transition: box-shadow 0.25s ease;
        }

        .nuvex-wa-card:hover {
            box-shadow: 0 14px 30px -5px rgba(0, 0, 0, 0.05), 0 6px 12px -2px rgba(0, 0, 0, 0.025);
        }

        .nuvex-wa-card-header {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .nuvex-wa-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.015em;
        }

        .nuvex-wa-card-title svg {
            color: #0d9488;
        }

        .nuvex-wa-card-desc {
            font-size: 12.5px;
            color: #64748b;
            margin: 4px 0 0 0;
            line-height: 1.45;
        }

        /* 4. Controles de Formulario */
        .nuvex-wa-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .nuvex-wa-row-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        @media (max-width: 640px) {
            .nuvex-wa-row-2 {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        .nuvex-wa-label {
            display: block;
            font-size: 11px;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 7px;
        }

        .nuvex-wa-input,
        .nuvex-wa-select,
        .nuvex-wa-textarea {
            width: 100%;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 11px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            font-family: inherit;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }

        .nuvex-wa-input.is-mono,
        .nuvex-wa-textarea.is-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            background: #f8fafc;
        }

        .nuvex-wa-input:focus,
        .nuvex-wa-select:focus,
        .nuvex-wa-textarea:focus {
            background: #ffffff;
            border-color: #0d9488;
            outline: none;
            box-shadow: 0 0 0 3.5px rgba(13, 148, 136, 0.14);
        }

        .nuvex-wa-hint {
            display: block;
            font-size: 11.5px;
            color: #64748b;
            margin-top: 5px;
            line-height: 1.45;
        }

        .nuvex-wa-meta-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .nuvex-wa-pin-card {
            background: #f0fdf4;
            border: 1.5px solid #a7f3d0;
            border-radius: 20px;
            padding: 18px 20px;
            margin-top: 4px;
        }

        .nuvex-wa-evo-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 20px;
            padding: 18px;
            font-size: 12.5px;
            color: #92400e;
            line-height: 1.5;
        }

        /* 5. SISTEMA OFICIAL DE BOTONES NUVEX */
        
        /* Botón Primario de Guardar (Esmeralda Sólido) */
        .nuvex-wa-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 700;
            color: #ffffff;
            background: #0d9488;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.25);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.01em;
        }

        .nuvex-wa-btn-primary:hover {
            background: #0f766e;
            box-shadow: 0 6px 18px rgba(13, 148, 136, 0.35);
            transform: translateY(-2px);
        }

        .nuvex-wa-btn-primary:active {
            transform: translateY(0);
        }

        /* Botón Secundario Frosted Glass (Verificar Estado, Ver Plantillas) */
        .nuvex-wa-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 18px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            color: #1e293b;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .nuvex-wa-btn-secondary:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0d9488;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.06);
        }

        /* Botón de Activación de PIN (Píldora Esmeralda Suave) */
        .nuvex-wa-btn-pin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 700;
            color: #047857;
            background: #ffffff;
            border: 1.5px solid #86efac;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.12);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .nuvex-wa-btn-pin:hover {
            background: #ecfdf5;
            border-color: #4ade80;
            color: #065f46;
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(16, 185, 129, 0.2);
        }

        /* Botón de Ejecutar Test (Dark Slate Sólido) */
        .nuvex-wa-btn-dark {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 22px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 800;
            color: #ffffff;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12);
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.22);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.01em;
        }

        .nuvex-wa-btn-dark:hover {
            background: #0f172a;
            border-color: #0d9488;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.32);
            transform: translateY(-2px);
        }

        .nuvex-wa-btn-dark:active {
            transform: translateY(0);
        }

        /* 6. Selector segmentado Texto vs Plantilla (Estilo Cápsula Moderna) */
        .nuvex-wa-segmented {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
        }

        .nuvex-wa-seg-btn {
            padding: 9px 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            background: transparent;
            color: #64748b;
        }

        .nuvex-wa-seg-btn.is-active {
            background: #ffffff;
            color: #0d9488;
            font-weight: 800;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        /* 7. Pills de Plantillas Rápidas */
        .nuvex-wa-quick-tag {
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 9999px;
            background: #f0fdf4;
            color: #047857;
            border: 1.5px solid #bbf7d0;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .nuvex-wa-quick-tag:hover {
            background: #0d9488;
            color: #ffffff;
            border-color: #0d9488;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);
        }

        /* 8. Spinner y Estados */
        @keyframes nuvex-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .nuvex-wa-spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.35);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: nuvex-spin 0.65s linear infinite;
        }

        .nuvex-wa-spinner.is-dark {
            border: 2px solid rgba(30, 58, 95, 0.25);
            border-top-color: #1e3a5f;
        }

        .nuvex-wa-spinner.is-teal {
            border: 2px solid rgba(13, 148, 136, 0.25);
            border-top-color: #0d9488;
        }

        button:disabled {
            opacity: 0.65;
            cursor: not-allowed !important;
            transform: none !important;
        }

        .nuvex-wa-code-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 16px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            color: #0f172a;
            word-break: break-all;
            margin: 10px 0;
            font-weight: 600;
        }

        .nuvex-wa-result {
            border-radius: 20px;
            padding: 16px 18px;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 14px;
        }

        .nuvex-wa-result.is-ok {
            background: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            color: #14532d;
        }

        .nuvex-wa-result.is-err {
            background: #fef2f2;
            border: 1.5px solid #fecaca;
            color: #7f1d1d;
        }

        .nuvex-wa-result-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 800;
            margin-bottom: 6px;
            font-size: 13px;
        }

        /* 9. Lista de Flujos Automáticos */
        .nuvex-flow-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 6px 0;
            font-size: 12.5px;
            color: #475569;
            line-height: 1.5;
        }

        .nuvex-flow-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
            background: #0d9488;
            margin-top: 6px;
            flex-shrink: 0;
        }
    </style>

    <div class="nuvex-wa-wrapper">

        {{-- BARRA SUPERIOR DE ESTADO EN VIVO --}}
        <div class="nuvex-wa-banner">
            <div class="nuvex-wa-banner-left">
                <div class="nuvex-wa-status-icon {{ $accountStatus['connected'] ? 'is-connected' : 'is-disconnected' }}">
                    @if($accountStatus['connected'])
                        <x-heroicon-o-check-circle />
                    @else
                        <x-heroicon-o-exclamation-triangle />
                    @endif
                </div>

                <div>
                    <div class="nuvex-wa-pill-row">
                        <span class="nuvex-wa-pill {{ $accountStatus['connected'] ? 'is-connected' : 'is-disconnected' }}">
                            <span class="nuvex-wa-dot {{ $accountStatus['connected'] ? 'is-connected' : 'is-disconnected' }}"></span>
                            {{ $accountStatus['connected'] ? 'CONECTADO Y OPERATIVO' : 'PENDIENTE DE CREDENCIALES' }}
                        </span>
                        <span class="nuvex-wa-driver-tag">Driver Activo: {{ strtoupper($whatsapp_driver) }}</span>
                    </div>

                    <h2 class="nuvex-wa-banner-title">
                        {{ $accountStatus['verified_name'] ?? 'Canal Oficial de WhatsApp' }}
                        @if(!empty($accountStatus['display_phone_number']))
                            <span style="font-weight:600; color:#64748b; font-size:14px;">({{ $accountStatus['display_phone_number'] }})</span>
                        @endif
                    </h2>
                    <p class="nuvex-wa-banner-sub">{{ $accountStatus['message'] ?? 'Conectado correctamente a Meta WhatsApp Cloud API.' }}</p>
                </div>
            </div>

            <div>
                <button wire:click="checkConnection" type="button" class="nuvex-wa-btn-secondary" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="checkConnection" style="display:inline-flex; align-items:center; gap:8px;">
                        <x-heroicon-o-arrow-path />
                        <span>Verificar Estado en Vivo</span>
                    </span>
                    <span wire:loading.inline-flex wire:target="checkConnection" style="display:none; align-items:center; gap:8px;">
                        <span class="nuvex-wa-spinner is-dark"></span>
                        <span>Verificando...</span>
                    </span>
                </button>
            </div>
        </div>

        {{-- GRID PRINCIPAL DE 2 COLUMNAS --}}
        <div class="nuvex-wa-grid">

            {{-- COLUMNA IZQUIERDA: CONFIGURACIÓN Y WEBHOOK --}}
            <div class="nuvex-wa-col">
                <div class="nuvex-wa-card">
                    <div class="nuvex-wa-card-header">
                        <h3 class="nuvex-wa-card-title">
                            <x-heroicon-o-cog-6-tooth />
                            <span>Parámetros de Conexión</span>
                        </h3>
                        <p class="nuvex-wa-card-desc">
                            Configura las credenciales de Meta WhatsApp Cloud API o conmuta el proveedor del salón.
                        </p>
                    </div>

                    <form wire:submit.prevent="saveSettings" class="nuvex-wa-form">
                        <div>
                            <label class="nuvex-wa-label">Proveedor de Notificaciones</label>
                            <select wire:model.live="whatsapp_driver" class="nuvex-wa-select">
                                <option value="meta">Meta WhatsApp Cloud API (API Oficial de Meta Business - Recomendado)</option>
                                <option value="evolution">Evolution API (Gateway QR / Baileys)</option>
                                <option value="log">Modo Simulación (Log local para pruebas)</option>
                            </select>
                            <span class="nuvex-wa-hint">Meta Cloud API es la vía oficial y estable sin depender de un teléfono encendido 24/7.</span>
                        </div>

                        @if($whatsapp_driver === 'meta')
                            <div class="nuvex-wa-meta-box">
                                <div class="nuvex-wa-row-2">
                                    <div>
                                        <label class="nuvex-wa-label">Phone Number ID</label>
                                        <input type="text" wire:model="meta_phone_number_id" placeholder="Ej: 105928374650192" class="nuvex-wa-input is-mono">
                                        <span class="nuvex-wa-hint">Identificador del número en Meta for Developers.</span>
                                    </div>
                                    <div>
                                        <label class="nuvex-wa-label">WABA ID (Account ID)</label>
                                        <input type="text" wire:model="meta_waba_id" placeholder="Ej: 109482736451920" class="nuvex-wa-input is-mono">
                                        <span class="nuvex-wa-hint">Identificador de la cuenta de WhatsApp Business.</span>
                                    </div>
                                </div>

                                <div>
                                    <label class="nuvex-wa-label">Access Token (Temporal 24h o Permanente System User)</label>
                                    <textarea wire:model="meta_access_token" rows="3" placeholder="EAAG..." class="nuvex-wa-textarea is-mono"></textarea>
                                    <span class="nuvex-wa-hint">Token generado en Meta for Developers o Meta Business Suite con permiso <code>whatsapp_business_messaging</code>.</span>
                                </div>

                                <div>
                                    <label class="nuvex-wa-label">Webhook Verify Token</label>
                                    <input type="text" wire:model="meta_verify_token" placeholder="nuvex_meta_verify_2026" class="nuvex-wa-input is-mono">
                                    <span class="nuvex-wa-hint">Token de verificación para el handshake de Webhook con Meta.</span>
                                </div>

                                <div class="nuvex-wa-pin-card">
                                    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:8px;">
                                        <div style="font-size:12.5px; font-weight:800; color:#14532d; display:flex; align-items:center; gap:6px;">
                                            <x-heroicon-o-key style="width:16px; height:16px; color:#16a34a;" />
                                            <span>Activación del Número en Meta Cloud API (PIN de 6 Dígitos)</span>
                                        </div>
                                        <span style="font-size:11px; font-weight:700; color:#15803d; background:#dcfce7; padding:3px 10px; border-radius:9999px; border:1px solid #bbf7d0;">
                                            Resuelve Error #133010
                                        </span>
                                    </div>
                                    <p style="font-size:11.5px; color:#166534; line-height:1.45; margin:0 0 12px 0;">
                                        Meta exige registrar el número con un PIN de 6 dígitos antes de permitir envíos. Si el número está activo en la app de WhatsApp de un celular físico, debes eliminar la cuenta en la app móvil previamente.
                                    </p>
                                    <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                        <div style="flex:0 0 140px;">
                                            <input type="text" maxlength="6" wire:model="meta_pin" placeholder="123456" class="nuvex-wa-input is-mono" style="text-align:center; font-weight:800; letter-spacing:4px; background:#ffffff;">
                                        </div>
                                        <button type="button" wire:click="registerMetaPhoneNumber" wire:loading.attr="disabled" class="nuvex-wa-btn-pin">
                                            <span wire:loading.remove wire:target="registerMetaPhoneNumber" style="display:inline-flex; align-items:center; gap:6px;">
                                                <x-heroicon-o-shield-check style="width:16px; height:16px;" />
                                                <span>Activar Número con PIN</span>
                                            </span>
                                            <span wire:loading.inline-flex wire:target="registerMetaPhoneNumber" style="display:none; align-items:center; gap:6px;">
                                                <span class="nuvex-wa-spinner is-teal"></span>
                                                <span>Activando en Meta...</span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @elseif($whatsapp_driver === 'evolution')
                            <div class="nuvex-wa-evo-box">
                                <strong style="display:block; margin-bottom:4px;">Modo Evolution API (QR) seleccionado:</strong>
                                Este modo conecta mediante sesión de WhatsApp Web. Para escanear o reiniciar el código QR, abre la pantalla de vinculación:
                                <div style="margin-top:12px;">
                                    <a href="{{ url('/admin/whatsapp') }}" target="_blank" class="nuvex-wa-btn-secondary">
                                        <span>Abrir Pantalla de Vinculación QR</span>
                                        <x-heroicon-o-arrow-top-right-on-square />
                                    </a>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="nuvex-wa-label">Número de WhatsApp del Salón (Alertas Admin & Enlaces wa.me)</label>
                            <input type="text" wire:model="whatsapp_number" placeholder="Ej: 3106080402" class="nuvex-wa-input">
                            <span class="nuvex-wa-hint">Número al que llegan las alertas de nueva cita y al que apuntan los botones de contacto del portal.</span>
                        </div>

                        <div style="display:flex; justify-content:flex-end; padding-top:8px;">
                            <button type="submit" class="nuvex-wa-btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="saveSettings" style="display:inline-flex; align-items:center; gap:8px;">
                                    <x-heroicon-o-check />
                                    <span>Guardar Parámetros de Conexión</span>
                                </span>
                                <span wire:loading.inline-flex wire:target="saveSettings" style="display:none; align-items:center; gap:8px;">
                                    <span class="nuvex-wa-spinner"></span>
                                    <span>Guardando Parámetros...</span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TARJETA DE WEBHOOK META --}}
                <div class="nuvex-wa-card">
                    <h3 class="nuvex-wa-card-title" style="font-size:14px;">
                        <x-heroicon-o-globe-alt />
                        <span>Datos de Webhook para Meta for Developers</span>
                    </h3>
                    <p class="nuvex-wa-card-desc" style="margin-top:6px;">
                        En Meta for Developers (<code>WhatsApp &gt; Configuración &gt; Webhook</code>), registra esta URL de callback y token:
                    </p>
                    <div class="nuvex-wa-code-box">
                        {{ url('/api/v1/webhooks/whatsapp') }}
                    </div>
                    <div style="font-size:12.5px; color:#475569;">
                        <strong>Token de Verificación:</strong>
                        <code style="background:#e6f7f2; color:#0f766e; padding:3px 10px; border-radius:8px; font-weight:700;">{{ $meta_verify_token ?: 'nuvex_meta_verify_2026' }}</code>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: TEST DE CONEXIÓN EN VIVO --}}
            <div class="nuvex-wa-col">
                <div class="nuvex-wa-card">
                    <div class="nuvex-wa-card-header">
                        <h3 class="nuvex-wa-card-title">
                            <x-heroicon-o-paper-airplane />
                            <span>Test de Conexión en Vivo</span>
                        </h3>
                        <p class="nuvex-wa-card-desc">
                            Envía un mensaje de prueba inmediato al celular indicado para certificar la entrega.
                        </p>
                    </div>

                    <form wire:submit.prevent="sendTestMessage" class="nuvex-wa-form">
                        <div>
                            <label class="nuvex-wa-label">Celular de Destino</label>
                            <input type="text" wire:model="test_phone" placeholder="Ej: 3106080402" class="nuvex-wa-input">
                            <span class="nuvex-wa-hint">Se normalizará a formato internacional (+57). <strong>Importante:</strong> Debe ser un celular personal o de prueba diferente al número comercial del salón (Meta rechaza el auto-envío).</span>
                        </div>

                        @if($whatsapp_driver === 'meta')
                            <div>
                                <label class="nuvex-wa-label">Modo de Envío en Meta</label>
                                <div class="nuvex-wa-segmented">
                                    <button type="button" wire:click="$set('test_mode', 'template')" class="nuvex-wa-seg-btn {{ $test_mode === 'template' ? 'is-active' : '' }}">
                                        Plantilla Aprobada
                                    </button>
                                    <button type="button" wire:click="$set('test_mode', 'text')" class="nuvex-wa-seg-btn {{ $test_mode === 'text' ? 'is-active' : '' }}">
                                        Texto Libre (24h)
                                    </button>
                                </div>
                                <span class="nuvex-wa-hint">
                                    {{ $test_mode === 'template' ? 'Recomendado para la primera prueba: se entrega siempre aunque no exista conversación previa de 24h.' : 'El texto libre requiere que el destinatario haya escrito al número del salón en las últimas 24 horas.' }}
                                </span>
                            </div>

                            @if($test_mode === 'template')
                                <div class="nuvex-wa-meta-box" style="padding:16px;">
                                    <div class="nuvex-wa-row-2">
                                        <div>
                                            <label class="nuvex-wa-label">Nombre Plantilla</label>
                                            <input type="text" wire:model="test_template_name" placeholder="reserva_confirmada" class="nuvex-wa-input is-mono">
                                        </div>
                                        <div>
                                            <label class="nuvex-wa-label">Código Idioma</label>
                                            <input type="text" wire:model="test_template_lang" placeholder="es_CO" class="nuvex-wa-input is-mono">
                                        </div>
                                    </div>
                                    <div style="margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between;">
                                        <div style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                                            <span style="font-size: 11px; color: #64748b; font-weight:600;">Plantillas rápidas:</span>
                                            <button type="button" wire:click="$set('test_template_name', 'reserva_confirmada'); $set('test_template_lang', 'es_CO')" class="nuvex-wa-quick-tag">reserva_confirmada</button>
                                            <button type="button" wire:click="$set('test_template_name', 'cita_recibida'); $set('test_template_lang', 'es_CO')" class="nuvex-wa-quick-tag">cita_recibida</button>
                                            <button type="button" wire:click="$set('test_template_name', 'recordatorio_cita_24h'); $set('test_template_lang', 'es_CO')" class="nuvex-wa-quick-tag">recordatorio_cita_24h</button>
                                        </div>
                                        <button type="button" wire:click="syncMetaTemplates" class="nuvex-wa-btn-secondary" style="font-size: 11.5px; padding: 4px 12px;" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="syncMetaTemplates" style="display:inline-flex; align-items:center; gap:5px;">
                                                <x-heroicon-o-arrow-path style="width:13px; height:13px;" />
                                                <span>Ver Plantillas de mi Meta</span>
                                            </span>
                                            <span wire:loading.inline-flex wire:target="syncMetaTemplates" style="display:none; align-items:center; gap:5px;">
                                                <span class="nuvex-wa-spinner is-dark" style="width:11px; height:11px;"></span>
                                                <span>Consultando...</span>
                                            </span>
                                        </button>
                                    </div>

                                    @if(!empty($wabaTemplates))
                                        <div style="margin-top: 12px; padding: 12px; background: #ffffff; border: 1.5px solid #a7f3d0; border-radius: 16px;">
                                            <div style="font-size: 11px; font-weight: 800; color: #0f766e; margin-bottom: 8px;">
                                                Plantillas encontradas en tu WABA (haz clic para usarla):
                                            </div>
                                            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                                @foreach($wabaTemplates as $t)
                                                    <button type="button" wire:click="selectTemplate('{{ $t['name'] }}', '{{ $t['language'] }}')" style="font-size: 11px; padding: 4px 10px; border-radius: 9999px; background: {{ $test_template_name === $t['name'] ? '#0d9488' : '#f0fdf4' }}; color: {{ $test_template_name === $t['name'] ? '#ffffff' : '#047857' }}; border: 1px solid #a7f3d0; cursor: pointer; font-weight: 700;">
                                                        <strong>{{ $t['name'] }}</strong> ({{ $t['language'] }}) - {{ $t['status'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <span class="nuvex-wa-hint" style="margin-top:10px;">
                                        💡 <strong>Importante en el idioma:</strong> Tu plantilla en Meta está registrada como <em>Spanish (COL)</em>, por lo que el código obligatorio es <code>es_CO</code>. <strong>No uses <code>es_ES</code></strong> (España).
                                    </span>
                                </div>
                            @endif
                        @endif

                        @if($whatsapp_driver !== 'meta' || $test_mode === 'text')
                            <div>
                                <label class="nuvex-wa-label">Mensaje Personalizado (Opcional)</label>
                                <textarea wire:model="test_custom_message" rows="2" placeholder="Mensaje de prueba desde La Belle Nails Bogotá..." class="nuvex-wa-textarea"></textarea>
                            </div>
                        @endif

                        <button type="submit" class="nuvex-wa-btn-dark" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="sendTestMessage" style="display:inline-flex; align-items:center; gap:8px;">
                                <x-heroicon-o-paper-airplane />
                                <span>Ejecutar Test de Envío</span>
                            </span>
                            <span wire:loading.inline-flex wire:target="sendTestMessage" style="display:none; align-items:center; gap:8px;">
                                <span class="nuvex-wa-spinner"></span>
                                <span>Enviando mensaje a WhatsApp...</span>
                            </span>
                        </button>
                    </form>

                    @if(!is_null($lastTestSuccess))
                        <div class="nuvex-wa-result {{ $lastTestSuccess ? 'is-ok' : 'is-err' }}">
                            <div class="nuvex-wa-result-title">
                                @if($lastTestSuccess)
                                    <x-heroicon-o-check-circle />
                                    <span>Envío Confirmado Exitosamente</span>
                                @else
                                    <x-heroicon-o-x-circle />
                                    <span>Diagnóstico del Intento de Envío</span>
                                @endif
                            </div>
                            <div style="font-family:ui-monospace, monospace; font-size:11.5px; word-break:break-word;">
                                {{ $lastTestResult }}
                            </div>

                            @if(!$lastTestSuccess && str_contains($lastTestResult ?? '', '133010'))
                                <div style="margin-top:14px; padding:14px 16px; background:#fff; border-radius:16px; border:1px solid #fecaca; text-align:left;">
                                    <div style="font-weight:800; color:#991b1b; font-size:12px; margin-bottom:4px; display:flex; align-items:center; gap:6px;">
                                        <x-heroicon-o-wrench-screwdriver style="width:15px; height:15px;" />
                                        <span>Solución Rápida para Error #133010:</span>
                                    </div>
                                    <p style="font-size:11.5px; color:#7f1d1d; margin:0 0 10px 0; line-height:1.45;">
                                        Meta exige registrar el número con su PIN de 6 dígitos antes de enviar mensajes. Si el número está activo en la app de WhatsApp de tu celular físico, debes eliminar la cuenta en la app de WhatsApp del celular previamente. Luego presiona:
                                    </p>
                                    <button type="button" wire:click="registerMetaPhoneNumber" wire:loading.attr="disabled" class="nuvex-wa-btn-primary" style="font-size:11.5px; padding:8px 18px; background:#dc2626; border:none; color:#ffffff; box-shadow:0 6px 14px rgba(220, 38, 38, 0.35);">
                                        <span wire:loading.remove wire:target="registerMetaPhoneNumber">Activar Número Ahora con PIN ({{ $meta_pin ?: '123456' }})</span>
                                        <span wire:loading.inline-flex wire:target="registerMetaPhoneNumber" style="display:none; align-items:center; gap:6px;">
                                            <span class="nuvex-wa-spinner"></span>
                                            <span>Activando en Meta...</span>
                                        </span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- RESUMEN DE EVENTOS CUBIERTOS --}}
                <div class="nuvex-wa-card" style="background:#f8fafc;">
                    <h4 class="nuvex-wa-card-title" style="font-size:14px; margin-bottom:12px;">
                        <x-heroicon-o-shield-check />
                        <span>Flujos Automáticos Conectados</span>
                    </h4>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <div class="nuvex-flow-item">
                            <span class="nuvex-flow-dot"></span>
                            <div><strong>Solicitud Recibida (Nequi):</strong> Acuse a la clienta con enlace de voucher.</div>
                        </div>
                        <div class="nuvex-flow-item">
                            <span class="nuvex-flow-dot"></span>
                            <div><strong>Alerta a Administradora:</strong> Notificación inmediata al <code>{{ $whatsapp_number ?: '3106080402' }}</code> en cada reserva.</div>
                        </div>
                        <div class="nuvex-flow-item">
                            <span class="nuvex-flow-dot"></span>
                            <div><strong>Abono Verificado / Rechazado:</strong> Aviso automático al validar en Filament.</div>
                        </div>
                        <div class="nuvex-flow-item">
                            <span class="nuvex-flow-dot"></span>
                            <div><strong>Reagendamiento y Cancelación:</strong> Disparo automático desde portal o panel admin.</div>
                        </div>
                        <div class="nuvex-flow-item">
                            <span class="nuvex-flow-dot"></span>
                            <div><strong>Recordatorio 24h Antes:</strong> Con enlace de confirmación de asistencia en 1 clic.</div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
