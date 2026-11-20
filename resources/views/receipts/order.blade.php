<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante interno {{ $receiptNumber }}</title>
    <style>
        @page { margin: 28px; }
        body { color: #242424; font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .receipt { margin: 0 auto; max-width: 760px; }
        .toolbar { display: flex; gap: 12px; margin: 0 auto 20px; max-width: 760px; }
        .toolbar a, .toolbar button { background: #7a2c20; border: 0; border-radius: 4px; color: #fff; cursor: pointer; font: inherit; padding: 10px 14px; text-decoration: none; }
        .receipt-header { border-bottom: 2px solid #7a2c20; margin-bottom: 20px; padding-bottom: 14px; }
        .receipt-header h1 { color: #7a2c20; margin: 0 0 8px; }
        .receipt-header p { margin: 4px 0; }
        .receipt-meta { border-collapse: collapse; margin-bottom: 20px; width: 100%; }
        .receipt-meta td { padding: 5px 0; vertical-align: top; }
        .items { border-collapse: collapse; margin: 16px 0; width: 100%; }
        .items th, .items td { border-bottom: 1px solid #ddd; padding: 9px 6px; text-align: left; }
        .items th:last-child, .items td:last-child { text-align: right; }
        .total { border-top: 2px solid #7a2c20; font-size: 16px; font-weight: bold; margin-top: 12px; padding-top: 12px; text-align: right; }
        .disclaimer { border-top: 1px solid #ddd; color: #555; margin-top: 26px; padding-top: 12px; }
        @media print { .toolbar { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    @php
        $paymentMethodLabels = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'yape' => 'Yape',
            'plin' => 'Plin',
        ];
    @endphp
    @unless($pdfDocument)
        <nav class="toolbar" aria-label="Opciones del comprobante">
            <button type="button" onclick="window.print()">Imprimir</button>
            <a href="{{ $pdfDownloadUrl }}">Descargar PDF</a>
        </nav>
    @endunless
    <main class="receipt">
        <header class="receipt-header">
            <h1>{{ $businessName }}</h1>
            <p>Comprobante interno de pago</p>
            <p>Número: <strong>{{ $receiptNumber }}</strong></p>
        </header>

        <table class="receipt-meta">
            <tr>
                <td><strong>Pedido:</strong> {{ $order->order_number }}</td>
                <td><strong>Fecha de aprobación:</strong> {{ ($approvedProof->reviewed_at ?? $approvedProof->submitted_at)->format('d/m/Y H:i') }}</td>
            </tr>
            <tr>
                <td><strong>Cliente:</strong> {{ $order->user->name }}</td>
                <td><strong>Método:</strong> {{ $paymentMethodLabels[$order->payment_method] ?? $order->payment_method }}</td>
            </tr>
            <tr>
                <td><strong>Estado del pago:</strong> PAGADO</td>
                <td></td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->food_name }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>S/ {{ number_format($item->unit_price, 2) }}</td>
                        <td>S/ {{ number_format($item->subtotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="total">Total pagado: S/ {{ number_format($order->total, 2) }}</p>
        <p class="disclaimer">Este es un comprobante interno de pago. No es una boleta ni una factura electrónica de SUNAT.</p>
    </main>
</body>
</html>
