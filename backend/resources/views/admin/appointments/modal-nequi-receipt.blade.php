<div class="nequi-modal-wrapper">
    <style>
        .nequi-modal-wrapper {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            color: #0f172a;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* Header Cierra Purple */
        .nequi-header-banner {
            background: linear-gradient(180deg, #faf5ff 0%, #ffffff 100%);
            border-radius: 16px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #f3e8ff;
            box-shadow: 0 2px 8px rgba(126, 34, 206, 0.04);
        }

        .nequi-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .nequi-icon-circle {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: #7e22ce;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(126, 34, 206, 0.25);
        }

        .nequi-header-title {
            font-size: 15px;
            font-weight: 800;
            margin: 0;
            color: #0f172a;
            line-height: 1.2;
        }

        .nequi-header-sub {
            font-size: 11px;
            color: #64748b;
            margin: 2px 0 0 0;
            font-weight: 600;
        }

        .nequi-badge-pill {
            background: #fffbeb;
            color: #d97706;
            font-weight: 700;
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 9999px;
            border: 1px solid #fde68a;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Information Boxes */
        .nequi-info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .nequi-card-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* 3-Column Financial Grid */
        .nequi-finance-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }
        @media (max-width: 480px) {
            .nequi-finance-grid {
                grid-template-columns: 1fr;
            }
        }

        .nequi-finance-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px 10px;
            text-align: center;
        }
        .nequi-finance-box.highlight {
            background: #fdf4ff;
            border-color: #f0abfc;
        }
        .nequi-finance-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }
        .nequi-finance-val {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 2px;
        }
        .nequi-finance-val.purple {
            color: #86198f;
        }

        /* Receipt Viewer */
        .nequi-receipt-viewer {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 14px;
        }

        .nequi-viewer-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .nequi-viewer-title {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
        }

        .nequi-image-container {
            background: #0f172a;
            border-radius: 12px;
            padding: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            max-height: 320px;
            overflow-y: auto;
        }

        .nequi-receipt-img {
            max-width: 100%;
            height: auto;
            max-height: 300px;
            border-radius: 8px;
            object-fit: contain;
        }

        .nequi-empty-box {
            padding: 24px;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            font-style: italic;
        }

        .nequi-action-link {
            font-size: 11px;
            font-weight: 700;
            color: #0d9488;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .nequi-action-link:hover {
            text-decoration: underline;
        }

        .nequi-tip-box {
            background: #f0fdf9;
            border: 1px solid #ccfbf1;
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 11px;
            color: #0f766e;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
    </style>

    <!-- 1. Encabezado Cierra Nequi -->
    <div class="nequi-header-banner">
        <div class="nequi-header-left">
            <div class="nequi-icon-circle">
                <svg style="width: 20px; height: 20px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="nequi-header-title">Comprobante de Transferencia Nequi</h3>
                <p class="nequi-header-sub">Cita #{{ $appointment->appointment_number }} • {{ $appointment->client_name }}</p>
            </div>
        </div>
        <span class="nequi-badge-pill">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
            <span>Por Verificar</span>
        </span>
    </div>

    <!-- 2. Resumen Conciso de la Reserva -->
    <div class="nequi-info-card">
        <div class="nequi-card-label">
            <span>Detalle del Tratamiento</span>
            <a href="https://wa.me/57{{ preg_replace('/[^0-9]/', '', $appointment->client_phone) }}?text=Hola%20{{ urlencode($appointment->client_name) }},%20te%20saluda%20Paola%20Aguilera%20sobre%20tu%20cita"
               target="_blank"
               style="font-weight: 700; color: #0d9488; text-decoration: none; font-size: 11px;">
                WhatsApp: {{ $appointment->client_phone }}
            </a>
        </div>
        <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 12px;">
            <div>
                <div style="font-weight: 800; font-size: 13px; color: #0f172a;">{{ $appointment->service?->name ?? 'Tratamiento Personalizado' }}</div>
                <div style="font-size: 11px; color: #64748b;">{{ \Carbon\Carbon::parse($appointment->appointment_date)->translatedFormat('d/m/Y') }} &nbsp;•&nbsp; {{ substr($appointment->start_time, 0, 5) }} — {{ substr($appointment->end_time, 0, 5) }}</div>
            </div>
        </div>
    </div>

    <!-- 3. Liquidación Financiera en 3 Pastillas -->
    <div class="nequi-finance-grid">
        <div class="nequi-finance-box">
            <div class="nequi-finance-label">Total Servicio</div>
            <div class="nequi-finance-val">${{ number_format($appointment->total_amount, 0, ',', '.') }}</div>
        </div>
        <div class="nequi-finance-box highlight">
            <div class="nequi-finance-label" style="color: #86198f;">Abono a Validar</div>
            <div class="nequi-finance-val purple">${{ number_format($appointment->deposit_amount, 0, ',', '.') }}</div>
        </div>
        <div class="nequi-finance-box">
            <div class="nequi-finance-label">Saldo en Estudio</div>
            <div class="nequi-finance-val">${{ number_format($appointment->balance_due, 0, ',', '.') }}</div>
        </div>
    </div>

    <!-- 4. Visor de Imagen del Comprobante -->
    <div class="nequi-receipt-viewer">
        @php
            $receiptUrl = null;
            if ($appointment->deposit_proof_image) {
                $receiptUrl = asset('storage/' . $appointment->deposit_proof_image);
            }
        @endphp

        <div class="nequi-viewer-header">
            <span class="nequi-viewer-title">Captura del Comprobante</span>
            @if ($receiptUrl)
                <a href="{{ $receiptUrl }}" target="_blank" class="nequi-action-link">
                    <span>Ver tamaño completo</span>
                </a>
            @endif
        </div>

        @if ($receiptUrl)
            <div class="nequi-image-container">
                <img src="{{ $receiptUrl }}"
                     alt="Comprobante Nequi"
                     class="nequi-receipt-img"
                     loading="eager"
                     onerror="this.onerror=null; this.src='/storage/samples/comprobante_nequi_muestra.png';">
            </div>
        @else
            <div class="nequi-image-container">
                <div class="nequi-empty-box">No se adjunto archivo de comprobante.</div>
            </div>
        @endif
    </div>

    <!-- 5. Tip Breve -->
    <div class="nequi-tip-box">
        <span>Verifica el valor y la cuenta en tu App Nequi antes de presionar confirmar.</span>
    </div>
</div>
