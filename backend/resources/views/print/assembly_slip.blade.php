<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha de Ensamble - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 2px dashed #9ca3af;
            padding: 24px;
            border-radius: 8px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 16px;
            margin-bottom: 16px;
        }
        .badge {
            display: inline-block;
            background-color: #f43f5e;
            color: #fff;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: bold;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }
        .box {
            background-color: #f9fafb;
            padding: 12px 16px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
        }
        .box-title {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .box-value {
            font-size: 14px;
            font-weight: 500;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 20px;
        }
        .items-table th, .items-table td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }
        .items-table th {
            background-color: #f3f4f6;
            font-weight: 600;
        }
        .check-box {
            width: 18px;
            height: 18px;
            border: 2px solid #374151;
            display: inline-block;
            border-radius: 3px;
            margin-right: 8px;
            vertical-align: middle;
        }
        .print-btn {
            background-color: #111827;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 20px;
        }
        @media print {
            .print-btn { display: none; }
            body { padding: 0; }
            .container { border: 1px solid #000; }
        }
    </style>
</head>
<body>

    <div style="text-align: right; max-width: 800px; margin: 0 auto;">
        <button class="print-btn" onclick="window.print()">🖨️ Imprimir Ficha de Ensamble</button>
    </div>

    <div class="container">
        <div class="header">
            <div>
                <h1 style="margin: 0; font-size: 22px;">NUVEX DETALLES 🎁</h1>
                <div style="color: #6b7280; font-size: 13px; margin-top: 2px;">Ficha Operativa de Ensamble y Despacho</div>
            </div>
            <div style="text-align: right;">
                <div class="badge">{{ $order->order_number }}</div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">{{ $order->created_at->format('d/m/Y g:i A') }}</div>
            </div>
        </div>

        <div class="grid">
            <div class="box">
                <div class="box-title">Destinatario & Teléfono</div>
                <div class="box-value">{{ $order->recipient_name }} | 📞 {{ $order->recipient_phone }}</div>
            </div>
            <div class="box">
                <div class="box-title">Fecha & Franja de Entrega (Bogotá)</div>
                <div class="box-value" style="color: #be123c; font-weight: bold;">
                    📅 {{ \Carbon\Carbon::parse($order->delivery_date)->format('d/m/Y') }} ({{ $order->time_slot_name }})
                </div>
            </div>
            <div class="box" style="grid-column: span 2;">
                <div class="box-title">Dirección de Entrega</div>
                <div class="box-value">{{ $order->recipient_address }} {{ $order->recipient_address_details ? '('.$order->recipient_address_details.')' : '' }} — <strong>{{ $order->delivery_zone_name }}</strong></div>
                @if($order->delivery_instructions)
                    <div style="font-size: 12px; color: #b45309; margin-top: 4px;">⚠️ Instrucción: {{ $order->delivery_instructions }}</div>
                @endif
            </div>
        </div>

        <h3 style="font-size: 15px; margin-bottom: 8px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">
            Insumos y Elementos a Empacar (Checklist para el Armador):
        </h3>

        @foreach($order->items as $item)
            <div style="background-color: #fff1f2; padding: 10px; border-radius: 6px; font-weight: bold; margin-bottom: 8px; color: #9f1239;">
                📦 {{ $item->product_name }} (Cantidad: {{ $item->quantity }})
            </div>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">OK</th>
                        <th>Grupo / Insumo</th>
                        <th>Elemento Seleccionado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($item->options as $opt)
                        <tr>
                            <td style="text-align: center;"><span class="check-box"></span></td>
                            <td style="color: #4b5563;">{{ $opt->option_group_name }}</td>
                            <td><strong>{{ $opt->option_name }}</strong></td>
                        </tr>
                    @empty
                        <tr>
                            <td style="text-align: center;"><span class="check-box"></span></td>
                            <td colspan="2">Detalle estándar sin insumos variables</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach

        @if($order->card_message)
            <div class="box" style="border-left: 4px solid #f43f5e; margin-top: 15px;">
                <div class="box-title">Dedicatoria a Imprimir / Adjuntar</div>
                <div class="box-value" style="font-style: italic; font-size: 13px; color: #374151;">
                    "{{ $order->card_message }}"
                </div>
            </div>
        @endif

        <div style="display: flex; justify-content: space-between; margin-top: 30px; padding-top: 15px; border-top: 1px solid #e5e7eb; font-size: 12px; color: #6b7280;">
            <div>Armado por: _________________________</div>
            <div>Revisado por: _________________________</div>
            <div>Entregado a Mensajero: _________________</div>
        </div>
    </div>

</body>
</html>
