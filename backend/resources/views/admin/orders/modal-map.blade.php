<div class="nuvex-map-modal">
    <style>
        .nuvex-map-modal {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
        }
        .nuvex-map-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .nuvex-map-frame {
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            height: 300px;
            background: #f1f5f9;
            margin-bottom: 16px;
        }
        .nuvex-actions-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }
        @media (max-width: 640px) {
            .nuvex-actions-grid { grid-template-columns: 1fr; }
        }

        .nuvex-info-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .nuvex-info-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nuvex-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
        }
        .nuvex-btn-wa { background: #10b981; color: white; border: none; }
        .nuvex-btn-wa:hover { background: #059669; color: white; transform: translateY(-1px); }
        .nuvex-btn-call { background: #2563eb; color: white; border: none; }
        .nuvex-btn-call:hover { background: #1d4ed8; color: white; transform: translateY(-1px); }
        .nuvex-btn-gmaps { background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; }
        .nuvex-btn-gmaps:hover { background: #f8fafc; border-color: #94a3b8; transform: translateY(-1px); }
        .nuvex-btn-waze { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .nuvex-btn-waze:hover { background: #bae6fd; color: #0284c7; transform: translateY(-1px); }
    </style>

    <div class="nuvex-map-head">
        <div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 6px;">
                <span>📍</span> Ubicación de Entrega en Bogotá
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">
                {{ $order->recipient_address }} • <strong style="color: #e11d48;">{{ $order->delivery_zone_name }}</strong>
            </p>
        </div>
        <div>
            <span style="font-size: 11px; font-weight: 700; background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; padding: 4px 10px; border-radius: 9999px;">
                Orden #{{ $order->order_number }}
            </span>
        </div>
    </div>

    <!-- Mapa Interactivo Embebido -->
    <div class="nuvex-map-frame">
        <iframe
            width="100%"
            height="100%"
            frameborder="0"
            scrolling="no"
            marginheight="0"
            marginwidth="0"
            src="https://maps.google.com/maps?q={{ urlencode($order->recipient_address . ', Bogotá, Colombia') }}&t=&z=15&ie=UTF8&iwloc=&output=embed">
        </iframe>
    </div>

    <!-- Panel de Acciones y Contacto -->
    <div class="nuvex-actions-grid">
        <!-- Contacto Directo Destinatario -->
        <div class="nuvex-info-card" style="border-left: 4px solid #10b981;">
            <div class="nuvex-info-title">
                <span>📞</span> Contactar al Destinatario
            </div>
            <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                {{ $order->recipient_name }}
            </div>
            <div style="font-size: 12px; color: #64748b; margin-bottom: 10px;">
                Teléfono: <strong style="color: #334155;">{{ $order->recipient_phone ?: 'No registrado' }}</strong>
            </div>

            @php
                $cleanPhone = preg_replace('/[^0-9]/', '', $order->recipient_phone);
                if (!str_starts_with($cleanPhone, '57') && strlen($cleanPhone) === 10) {
                    $cleanPhone = '57' . $cleanPhone;
                }
            @endphp
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <a href="https://wa.me/{{ $cleanPhone }}?text=Hola%20{{ urlencode($order->recipient_name) }},%20somos%20de%20Nuvex%20Detalles.%20Llevamos%20tu%20sorpresa%20en%20camino!"
                   target="_blank" class="nuvex-btn nuvex-btn-wa">
                    <span>💬 WhatsApp</span>
                </a>
                <a href="tel:{{ $order->recipient_phone }}" class="nuvex-btn nuvex-btn-call">
                    <span>📞 Llamar</span>
                </a>
            </div>
        </div>

        <!-- Navegación GPS Rápida -->
        <div class="nuvex-info-card" style="border-left: 4px solid #0284c7;">
            <div class="nuvex-info-title">
                <span>🚗</span> Navegación Satelital (GPS)
            </div>
            <div style="font-size: 12px; color: #475569; margin-bottom: 12px; line-height: 1.4;">
                Abrir coordenadas exactas en la app de tráfico de tu preferencia:
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->recipient_address . ', Bogotá, Colombia') }}"
                   target="_blank" class="nuvex-btn nuvex-btn-gmaps">
                    <span>🗺️ Google Maps</span>
                </a>
                <a href="https://waze.com/ul?q={{ urlencode($order->recipient_address . ', Bogotá, Colombia') }}&navigate=yes"
                   target="_blank" class="nuvex-btn nuvex-btn-waze">
                    <span>🚗 Waze</span>
                </a>
            </div>
        </div>
    </div>
</div>
