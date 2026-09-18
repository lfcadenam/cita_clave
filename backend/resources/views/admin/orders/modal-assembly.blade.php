<div class="nuvex-modal-container">
    <style>
        .nuvex-modal-container {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1e293b;
            line-height: 1.5;
        }
        .nuvex-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 16px;
            padding: 16px 20px;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);
            margin-bottom: 16px;
        }
        .nuvex-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .nuvex-badge-rose { background: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
        .nuvex-badge-emerald { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .nuvex-badge-amber { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .nuvex-badge-sky { background: #e0f2fe; color: #075985; border: 1px solid #bae6fd; }

        .nuvex-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 16px;
        }
        @media (max-width: 640px) {
            .nuvex-grid-2 { grid-template-columns: 1fr; }
        }

        .nuvex-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        }
        .nuvex-card-header {
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

        .nuvex-item-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .nuvex-item-head {
            background: #fff1f2;
            border-bottom: 1px solid #ffe4e6;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nuvex-item-body {
            padding: 12px 14px;
            background: #fafafa;
        }
        .nuvex-option-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 12px;
        }
        .nuvex-option-row:last-child {
            border-bottom: none;
        }

        .nuvex-btn-print {
            background: #e11d48;
            color: white;
            padding: 8px 14px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 2px 6px rgba(225, 29, 72, 0.3);
        }
        .nuvex-btn-print:hover {
            background: #be123c;
            color: white;
            transform: translateY(-1px);
        }

        .nuvex-gift-box {
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border: 1.5px dashed #f43f5e;
            border-radius: 14px;
            padding: 14px;
            margin-top: 14px;
        }
    </style>

    <!-- Banner Superior -->
    <div class="nuvex-banner">
        <div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 20px;">📋</span>
                <span style="font-size: 17px; font-weight: 800; letter-spacing: -0.3px;">Ficha de Ensamble y Taller</span>
                <span class="nuvex-badge nuvex-badge-rose">#{{ $order->order_number }}</span>
            </div>
            <p style="font-size: 12px; color: #94a3b8; margin: 4px 0 0 28px;">
                Control de calidad de insumos, empaque y despacho
            </p>
        </div>
        <div>
            <a href="{{ route('orders.print.assembly', $order) }}" target="_blank" class="nuvex-btn-print">
                <span>🖨️ Imprimir Ficha</span>
            </a>
        </div>
    </div>

    <!-- Grid de Información Clave -->
    <div class="nuvex-grid-2">
        <!-- Tarjeta Destinatario y Entrega -->
        <div class="nuvex-card" style="border-left: 4px solid #e11d48;">
            <div class="nuvex-card-header">
                <span>📍</span> Destinatario & Ubicación Bogotá
            </div>
            <div style="font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 2px;">
                {{ $order->recipient_name }}
            </div>
            <div style="font-size: 12px; color: #475569; margin-bottom: 6px;">
                📞 <strong>Tel:</strong> {{ $order->recipient_phone ?: 'Sin registrar' }}
            </div>
            <div style="font-size: 12px; color: #334155; line-height: 1.4; background: #f8fafc; padding: 8px 10px; border-radius: 8px; border: 1px solid #f1f5f9;">
                <div><strong>Dirección:</strong> {{ $order->recipient_address }}</div>
                @if($order->recipient_address_details)
                    <div style="color: #64748b; font-size: 11px;">Torre/Apto: {{ $order->recipient_address_details }}</div>
                @endif
                <div style="margin-top: 4px;">
                    <span class="nuvex-badge nuvex-badge-sky">Zona: {{ $order->delivery_zone_name }}</span>
                </div>
            </div>

            @if($order->delivery_instructions)
                <div style="margin-top: 8px; padding: 8px 10px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; font-size: 11px; color: #92400e;">
                    <strong>⚠️ Nota para el Repartidor:</strong> {{ $order->delivery_instructions }}
                </div>
            @endif
        </div>

        <!-- Tarjeta de Programación y Repartidor -->
        <div class="nuvex-card" style="border-left: 4px solid #0284c7;">
            <div class="nuvex-card-header">
                <span>⏰</span> Programación Logística
            </div>
            <div style="display: flex; gap: 8px; margin-bottom: 10px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 120px; background: #f0f9ff; border: 1px solid #bae6fd; padding: 8px 10px; border-radius: 8px;">
                    <div style="font-size: 10px; font-weight: 700; color: #0369a1; text-transform: uppercase;">Fecha de Entrega</div>
                    <div style="font-size: 13px; font-weight: 800; color: #0c4a6e;">
                        📅 {{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }}
                    </div>
                </div>
                <div style="flex: 1; min-width: 120px; background: #fdf2f8; border: 1px solid #fbcfe8; padding: 8px 10px; border-radius: 8px;">
                    <div style="font-size: 10px; font-weight: 700; color: #9d174d; text-transform: uppercase;">Franja Horaria</div>
                    <div style="font-size: 13px; font-weight: 800; color: #831843;">
                        ⏰ {{ $order->time_slot_name }}
                    </div>
                </div>
            </div>

            <div style="background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #f1f5f9; font-size: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #64748b;">Repartidor Asignado:</span>
                    @if($order->driver)
                        <span class="nuvex-badge nuvex-badge-emerald">🛵 {{ $order->driver->name }}</span>
                    @else
                        <span class="nuvex-badge nuvex-badge-amber">⚠️ Sin Asignar</span>
                    @endif
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px; padding-top: 6px; border-top: 1px dashed #e2e8f0;">
                    <span style="color: #64748b;">Comprador:</span>
                    <strong style="color: #0f172a;">{{ $order->customer_name }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Chequeo de Insumos (Checklist de Taller) -->
    <div>
        <div style="font-size: 12px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #475569; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
            <span>🧺</span> Checklist de Insumos & Contenido a Empacar
        </div>

        @foreach($order->items as $item)
            <div class="nuvex-item-box">
                <div class="nuvex-item-head">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 16px;">🎁</span>
                        <span style="font-size: 13px; font-weight: 800; color: #9f1239;">{{ $item->product_name }}</span>
                    </div>
                    <span class="nuvex-badge nuvex-badge-rose">
                        Cant: {{ $item->quantity }}
                    </span>
                </div>
                <div class="nuvex-item-body">
                    @forelse($item->options as $opt)
                        <div class="nuvex-option-row">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <input type="checkbox" checked style="accent-color: #e11d48; width: 15px; height: 15px; cursor: pointer;">
                                <span style="color: #64748b; font-weight: 600;">{{ $opt->option_group_name }}:</span>
                                <span style="color: #0f172a; font-weight: 700;">{{ $opt->option_name }}</span>
                            </div>
                            @if($opt->additional_price > 0)
                                <span style="font-size: 11px; font-weight: 700; color: #be123c; font-family: monospace;">
                                    +${{ number_format($opt->additional_price, 0, ',', '.') }}
                                </span>
                            @else
                                <span style="font-size: 11px; color: #10b981; font-weight: 700;">✓ Incluido</span>
                            @endif
                        </div>
                    @empty
                        <div style="font-size: 12px; color: #94a3b8; font-style: italic; padding: 4px 0;">
                            ✨ Producto estándar sin opciones adicionales seleccionadas.
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <!-- Dedicatoria de la Tarjeta -->
    @if ($order->card_message)
        <div class="nuvex-gift-box">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.6px; color: #be123c; display: flex; align-items: center; gap: 6px;">
                    <span>💌</span> Mensaje Dedicatoria Impreso en la Tarjeta
                </span>
                <span style="font-size: 10px; color: #e11d48; font-weight: 600;">{{ strlen($order->card_message) }} caracteres</span>
            </div>
            <p style="font-family: Georgia, serif; font-style: italic; font-size: 13px; color: #4c0519; line-height: 1.6; margin: 0; background: rgba(255, 255, 255, 0.7); padding: 10px 14px; border-radius: 8px; border: 1px solid #fecdd3;">
                "{{ $order->card_message }}"
            </p>
        </div>
    @endif
</div>
