<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tarjeta de Regalo - {{ $order->order_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Alex+Brush&family=Montserrat:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 40px;
            background-color: #f3f4f6;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-family: 'Montserrat', sans-serif;
        }
        .card-container {
            width: 140mm;
            height: 100mm;
            background: #fff;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
            padding: 30px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background-image: radial-gradient(#ffe4e6 0.75px, transparent 0.75px);
            background-size: 16px 16px;
        }
        .inner-border {
            position: absolute;
            top: 12px;
            left: 12px;
            right: 12px;
            bottom: 12px;
            border: 1px solid #fecdd3;
            border-radius: 8px;
            pointer-events: none;
        }
        .card-header {
            text-align: center;
        }
        .card-header h2 {
            margin: 0;
            font-family: 'Playfair Display', serif;
            color: #be123c;
            font-size: 20px;
            letter-spacing: 1px;
        }
        .recipient {
            font-size: 13px;
            color: #4b5563;
            margin-top: 4px;
            font-weight: 500;
        }
        .card-body {
            text-align: center;
            padding: 10px 15px;
        }
        .message {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 15px;
            line-height: 1.6;
            color: #1f2937;
            margin: 0;
        }
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 11px;
            color: #9ca3af;
            border-top: 1px solid #ffe4e6;
            padding-top: 8px;
        }
        .print-btn {
            background-color: #be123c;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 25px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        @media print {
            body { padding: 0; background: none; }
            .print-btn { display: none; }
            .card-container {
                box-shadow: none;
                border: 1px solid #e5e7eb;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <button class="print-btn" onclick="window.print()">🖨️ Imprimir Tarjeta de Felicitación</button>

    <div class="card-container">
        <div class="inner-border"></div>

        <div class="card-header">
            <h2>Un Detalle Especial Para Ti</h2>
            <div class="recipient">De: <strong>{{ $order->customer_name }}</strong> &nbsp;|&nbsp; Para: <strong>{{ $order->recipient_name }}</strong></div>
        </div>

        <div class="card-body">
            <p class="message">
                "{{ $order->card_message ?: 'Con todo mi cariño para celebrar este día tan especial.' }}"
            </p>
        </div>

        <div class="card-footer">
            <span>✨ Nuvex Detalles</span>
            <span>Ref: {{ $order->order_number }}</span>
        </div>
    </div>

</body>
</html>
