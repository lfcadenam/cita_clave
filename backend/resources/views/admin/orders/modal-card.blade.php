<div class="nuvex-card-modal">
    <style>
        .nuvex-card-modal {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
        }
        .nuvex-card-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .nuvex-btn-action {
            background: #e11d48;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 6px rgba(225, 29, 72, 0.25);
            transition: all 0.2s;
        }
        .nuvex-btn-action:hover {
            background: #be123c;
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Physical Card Design */
        .nuvex-greeting-card {
            position: relative;
            background: #ffffff;
            border-radius: 20px;
            padding: 32px 28px;
            border: 2px solid #fecdd3;
            box-shadow: 0 10px 25px -5px rgba(244, 63, 94, 0.1), 0 8px 10px -6px rgba(244, 63, 94, 0.05);
            background-image: radial-gradient(#ffe4e6 1px, transparent 1px);
            background-size: 18px 18px;
            overflow: hidden;
        }
        .nuvex-card-inner-frame {
            position: absolute;
            inset: 10px;
            border: 1px solid #f43f5e;
            border-radius: 14px;
            pointer-events: none;
            opacity: 0.35;
        }
        .nuvex-card-ribbon {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 6px 14px;
            border-radius: 9999px;
            box-shadow: 0 2px 8px rgba(244, 63, 94, 0.3);
        }
        .nuvex-card-message {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 17px;
            line-height: 1.8;
            color: #334155;
            font-style: italic;
            text-align: center;
            margin: 24px 0;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 12px;
            border: 1px solid rgba(254, 205, 211, 0.6);
            backdrop-filter: blur(4px);
        }
        .nuvex-card-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px dashed #fecdd3;
            font-size: 11px;
            color: #64748b;
        }
    </style>

    <div class="nuvex-card-head">
        <div>
            <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">
                💌 Dedicatoria & Tarjeta de Regalo
            </h3>
            <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">
                Orden #{{ $order->order_number }} • Lista para impresión en alta calidad
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="{{ route('orders.print.card', $order) }}" target="_blank" class="nuvex-btn-action">
                <span>🖨️ Imprimir Tarjeta</span>
            </a>
        </div>
    </div>

    <!-- Tarjeta Visual Estilizada -->
    <div class="nuvex-greeting-card">
        <div class="nuvex-card-inner-frame"></div>

        <div style="text-align: center;">
            <span class="nuvex-card-ribbon">
                ✨ Un Detalle Especial Para Ti
            </span>

            <div style="margin-top: 14px; font-size: 12px; color: #475569; display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <div>
                    <span style="color: #94a3b8; font-weight: 600;">De:</span>
                    <strong style="color: #0f172a;">{{ $order->customer_name ?: 'Alguien Especial' }}</strong>
                </div>
                <div style="color: #cbd5e1;">•</div>
                <div>
                    <span style="color: #94a3b8; font-weight: 600;">Para:</span>
                    <strong style="color: #e11d48;">{{ $order->recipient_name }}</strong>
                </div>
            </div>
        </div>

        <div class="nuvex-card-message">
            “{{ $order->card_message ?: 'Con todo mi cariño para celebrar este día tan especial. ¡Que disfrutes mucho este momento!' }}”
        </div>

        <div class="nuvex-card-meta">
            <span style="font-weight: 700; color: #e11d48; display: flex; align-items: center; gap: 4px;">
                <span>🌸</span> Nuvex Regalos Bogotá
            </span>
            <span>
                Fecha de entrega: <strong>{{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}</strong>
            </span>
        </div>
    </div>
</div>
