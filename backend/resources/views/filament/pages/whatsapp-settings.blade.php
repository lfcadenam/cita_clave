<x-filament-panels::page>
    <style>
        .nuvex-wa-wrapper {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #1e293b;
            display: flex;
            flex-direction: column;
            gap: 22px;
            width: 100%;
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
            border-radius: 24px;
            border: 1px solid #e2f0ea;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            box-shadow: 0 10px 25px -5px rgba(13, 148, 136, 0.05);
        }

        .nuvex-wa-banner-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .nuvex-wa-status-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .nuvex-wa-status-icon svg {
            width: 24px;
            height: 24px;
            max-width: 24px;
            max-height: 24px;
        }

        .nuvex-wa-status-icon.is-connected {
            background: #e6f7f2;
            color: #0d9488;
            border: 1px solid #b5e8d8;
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
        }

        .nuvex-wa-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .nuvex-wa-pill.is-connected {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
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
        }

        .nuvex-wa-dot.is-connected {
            background: #10b981;
        }

        .nuvex-wa-dot.is-disconnected {
            background: #f59e0b;
        }

        .nuvex-wa-driver-tag {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            padding: 3px 10px;
            border-radius: 9999px;
        }

        .nuvex-wa-banner-title {
            font-size: 16px;
            font-weight: 800;
            color: #1e3a5f;
            margin: 4px 0 2px 0;
        }

        .nuvex-wa-banner-sub {
            font-size: 12.5px;
            color: #64748b;
            margin: 0;
            line-height: 1.45;
        }

        /* 2. Grid Principal de 2 Columnas */
        .nuvex-wa-grid {
            display: grid;
            grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
            gap: 22px;
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
            gap: 20px;
        }

        /* 3. Tarjetas Elevadas Suaves Nuvex */
        .nuvex-wa-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e2f0ea;
            padding: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.03);
        }

        .nuvex-wa-card-header {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        .nuvex-wa-card-title {
            font-size: 15px;
            font-weight: 800;
            color: #1e3a5f;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nuvex-wa-card-title svg {
            color: #0d9488;
        }

        .nuvex-wa-card-desc {
            font-size: 12px;
            color: #64748b;
            margin: 4px 0 0 0;
            line-height: 1.45;
        }

        /* 4. Controles de Formulario */
        .nuvex-wa-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
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
            font-size: 11.5px;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-bottom: 6px;
        }

        .nuvex-wa-input,
        .nuvex-wa-select,
        .nuvex-wa-textarea {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 14px;
            padding: 10px 14px;
            font-size: 12.5px;
            font-weight: 600;
            color: #0f172a;
            font-family: inherit;
            transition: all 0.18s ease;
            box-sizing: border-box;
        }

        .nuvex-wa-input.is-mono,
        .nuvex-wa-textarea.is-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
        }

        .nuvex-wa-input:focus,
        .nuvex-wa-select:focus,
        .nuvex-wa-textarea:focus {
            background: #ffffff;
            border-color: #0d9488;
            outline: none;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.14);
        }

        .nuvex-wa-hint {
            display: block;
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
            line-height: 1.4;
        }

        .nuvex-wa-meta-box {
            background: #f4fbf9;
            border: 1px solid #ccece3;
            border-radius: 18px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .nuvex-wa-evo-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 18px;
            padding: 16px;
            font-size: 12px;
            color: #92400e;
            line-height: 1.5;
        }

        /* 5. Botones */
        .nuvex-wa-btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 22px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 800;
            color: #ffffff;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.22);
            transition: all 0.2s ease;
        }

        .nuvex-wa-btn-primary:hover {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            transform: translateY(-1px);
        }

        .nuvex-wa-btn-secondary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            color: #1e3a5f;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.18s ease;
            text-decoration: none;
        }

        .nuvex-wa-btn-secondary:hover {
            background: #e2e8f0;
        }

        .nuvex-wa-btn-dark {
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 9999px;
            font-size: 12.5px;
            font-weight: 800;
            color: #ffffff;
            background: #1e3a5f;
            border: none;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .nuvex-wa-btn-dark:hover {
            background: #152c4a;
        }

        /* 6. Selector segmentado Texto vs Plantilla */
        .nuvex-wa-segmented {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 9999px;
        }

        .nuvex-wa-seg-btn {
            padding: 8px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all 0.18s ease;
            background: transparent;
            color: #64748b;
        }

        .nuvex-wa-seg-btn.is-active {
            background: #0d9488;
            color: #ffffff;
            font-weight: 800;
            box-shadow: 0 2px 6px rgba(13, 148, 136, 0.25);
        }

        .nuvex-wa-code-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 11px 14px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 12px;
            color: #0f172a;
            word-break: break-all;
            margin: 10px 0;
        }

        .nuvex-wa-result {
            border-radius: 16px;
            padding: 14px 16px;
            font-size: 12px;
            line-height: 1.5;
            margin-top: 12px;
        }

        .nuvex-wa-result.is-ok {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #14532d;
        }

        .nuvex-wa-result.is-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #7f1d1d;
        }

        .nuvex-wa-result-title {
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 800;
            margin-bottom: 4px;
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
                            <span style="font-weight:600; color:#64748b; font-size:13.5px;">({{ $accountStatus['display_phone_number'] }})</span>
                        @endif
                    </h2>
                    <p class="nuvex-wa-banner-sub">{{ $accountStatus['message'] ?? '' }}</p>
                </div>
            </div>

            <div>
                <button wire:click="checkConnection" type="button" class="nuvex-wa-btn-secondary">
                    <x-heroicon-o-arrow-path />
                    <span>Verificar Estado en Vivo</span>
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
                            </div>
                        @elseif($whatsapp_driver === 'evolution')
                            <div class="nuvex-wa-evo-box">
                                <strong style="display:block; margin-bottom:4px;">Modo Evolution API (QR) seleccionado:</strong>
                                Este modo conecta mediante sesión de WhatsApp Web. Para escanear o reiniciar el código QR, abre la pantalla de vinculación:
                                <div style="margin-top:10px;">
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

                        <div style="display:flex; justify-content:flex-end; padding-top:6px;">
                            <button type="submit" class="nuvex-wa-btn-primary">
                                <x-heroicon-o-check />
                                <span>Guardar Parámetros de Conexión</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- TARJETA DE WEBHOOK META --}}
                <div class="nuvex-wa-card">
                    <h3 class="nuvex-wa-card-title" style="font-size:13.5px;">
                        <x-heroicon-o-globe-alt />
                        <span>Datos de Webhook para Meta for Developers</span>
                    </h3>
                    <p class="nuvex-wa-card-desc" style="margin-top:6px;">
                        En Meta for Developers (<code>WhatsApp &gt; Configuración &gt; Webhook</code>), registra esta URL de callback y token:
                    </p>
                    <div class="nuvex-wa-code-box">
                        {{ url('/api/v1/webhooks/whatsapp') }}
                    </div>
                    <div style="font-size:12px; color:#475569;">
                        <strong>Token de Verificación:</strong>
                        <code style="background:#e6f7f2; color:#0f766e; padding:2px 8px; border-radius:6px; font-weight:700;">{{ $meta_verify_token ?: 'nuvex_meta_verify_2026' }}</code>
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
                                <div class="nuvex-wa-meta-box" style="padding:14px;">
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
                                    <div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px; align-items: center;">
                                        <span style="font-size: 11px; color: var(--nuvex-wa-text-dim, #64748b);">Plantillas rápidas:</span>
                                        <button type="button" wire:click="$set('test_template_name', 'reserva_confirmada'); $set('test_template_lang', 'es_CO')" style="font-size: 11px; padding: 3px 8px; border-radius: 9999px; background: rgba(0,201,117,0.12); color: #00874e; border: 1px solid rgba(0,201,117,0.25); cursor: pointer; font-weight: 500;">reserva_confirmada</button>
                                        <button type="button" wire:click="$set('test_template_name', 'cita_recibida'); $set('test_template_lang', 'es_CO')" style="font-size: 11px; padding: 3px 8px; border-radius: 9999px; background: rgba(0,201,117,0.12); color: #00874e; border: 1px solid rgba(0,201,117,0.25); cursor: pointer; font-weight: 500;">cita_recibida</button>
                                        <button type="button" wire:click="$set('test_template_name', 'recordatorio_cita_24h'); $set('test_template_lang', 'es_CO')" style="font-size: 11px; padding: 3px 8px; border-radius: 9999px; background: rgba(0,201,117,0.12); color: #00874e; border: 1px solid rgba(0,201,117,0.25); cursor: pointer; font-weight: 500;">recordatorio_cita_24h</button>
                                    </div>
                                    <span class="nuvex-wa-hint" style="margin-top:8px;">
                                        💡 <strong>Nota sobre números reales:</strong> Meta bloquea la plantilla <code>hello_world</code> en números de producción (Error #131058). Para realizar pruebas en vivo en tu línea real, usa una plantilla aprobada en tu WhatsApp Manager (como <code>reserva_confirmada</code>) en idioma <code>es_CO</code> o <code>es</code>.
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

                        <button type="submit" class="nuvex-wa-btn-dark">
                            <x-heroicon-o-paper-airplane />
                            <span>Ejecutar Test de Envío</span>
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
                        </div>
                    @endif
                </div>

                {{-- RESUMEN DE EVENTOS CUBIERTOS --}}
                <div class="nuvex-wa-card" style="background:#f8fafc;">
                    <h4 class="nuvex-wa-card-title" style="font-size:13.5px; margin-bottom:10px;">
                        <x-heroicon-o-shield-check />
                        <span>Flujos Automáticos Conectados</span>
                    </h4>
                    <div style="font-size:12px; color:#475569; line-height:1.65; display:flex; flex-direction:column; gap:4px;">
                        <div>• <strong>Solicitud Recibida (Nequi):</strong> Acuse a la clienta con enlace de voucher.</div>
                        <div>• <strong>Alerta a Administradora:</strong> Notificación inmediata al <code>{{ $whatsapp_number ?: '3106080402' }}</code> en cada reserva.</div>
                        <div>• <strong>Abono Verificado / Rechazado:</strong> Aviso automático al validar en Filament.</div>
                        <div>• <strong>Reagendamiento y Cancelación:</strong> Disparo automático desde portal o panel admin.</div>
                        <div>• <strong>Recordatorio 24h Antes:</strong> Con enlace de confirmación de asistencia en 1 clic.</div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
