@php
    $originLabel = $manifest->originWarehouse?->name ?? 'Origin';
    $destinationLabel = $manifest->destinationWarehouse?->name ?? 'Destination';
    $routeLabel = trim($originLabel . ' -> ' . $destinationLabel);
    $routeLength = mb_strlen($routeLabel);
    $routeSizeClass = $routeLength > 64
        ? 'route-value--compact'
        : ($routeLength > 38 ? 'route-value--medium' : '');
    $itemCount = $container->items->count();
    $itemQuantity = $container->items->sum('expected_quantity');
    $containerSequence = $container->sequence_number
        ? 'C' . str_pad((string) $container->sequence_number, 2, '0', STR_PAD_LEFT)
        : 'C--';
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Container Label - {{ $container->container_code }}</title>
    <style>
        @page { size: 80mm 120mm; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { width: 80mm; min-width: 80mm; height: 120mm; min-height: 120mm; }
        body {
            background: #fff;
            color: #020617;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .label {
            display: grid;
            grid-template-rows: 16mm 43mm 28.5mm 20mm 12.5mm;
            width: 80mm;
            height: 120mm;
            overflow: hidden;
            background: #fff;
        }
        .top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3mm;
            padding: 2.8mm 3.5mm 2.5mm;
            border-bottom: 0.4mm solid #111827;
        }
        .brand {
            color: #111827;
            font-size: 4.5mm;
            line-height: 1;
            font-weight: 900;
            letter-spacing: 0.6mm;
        }
        .kind {
            margin-top: 1mm;
            color: #475569;
            font-size: 2mm;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.45mm;
            text-transform: uppercase;
        }
        .type {
            max-width: 23mm;
            padding: 1.5mm 2.7mm;
            border: 0.4mm solid #111827;
            border-radius: 0.8mm;
            color: #111827;
            font-size: 2.3mm;
            line-height: 1.1;
            font-weight: 900;
            letter-spacing: 0.2mm;
            text-align: center;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }
        .identity {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 3mm 3.5mm 2.5mm;
        }
        .label-title {
            color: #475569;
            font-size: 2mm;
            line-height: 1.1;
            font-weight: 800;
            letter-spacing: 0.4mm;
            text-transform: uppercase;
        }
        .code {
            width: 100%;
            margin-top: 1.4mm;
            color: #020617;
            font-family: 'Courier New', monospace;
            font-size: 2.9mm;
            line-height: 1.15;
            font-weight: 900;
            text-align: center;
            white-space: nowrap;
        }
        .barcode {
            width: 100%;
            height: 21mm;
            margin-top: 2mm;
            overflow: hidden;
            text-align: center;
        }
        .barcode svg {
            display: block;
            width: 100%;
            height: 21mm;
            margin: 0 auto;
        }
        .scan-hint {
            margin-top: 1.5mm;
            color: #475569;
            font-size: 2mm;
            line-height: 1;
            font-weight: 700;
            letter-spacing: 0.2mm;
            text-align: center;
            text-transform: uppercase;
        }
        .route {
            padding: 3.2mm 3.5mm 2.8mm;
            border-top: 0.3mm solid #111827;
            border-bottom: 0.3mm solid #111827;
            overflow: hidden;
        }
        .route-value {
            margin-top: 1.4mm;
            color: #020617;
            font-size: 4.1mm;
            line-height: 1.12;
            font-weight: 900;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }
        .route-value--medium { font-size: 3.5mm; }
        .route-value--compact { font-size: 2.8mm; line-height: 1.08; }
        .manifest-number {
            margin-top: 2mm;
            color: #475569;
            font-family: 'Courier New', monospace;
            font-size: 2.4mm;
            line-height: 1.15;
            font-weight: 800;
            white-space: nowrap;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }
        .stat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
        }
        .stat + .stat { border-left: 0.25mm solid #cbd5e1; }
        .stat-value {
            color: #020617;
            font-size: 5.5mm;
            line-height: 1;
            font-weight: 900;
        }
        .stat-label {
            margin-top: 1.5mm;
            color: #475569;
            font-size: 1.85mm;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.25mm;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 3mm;
            padding: 1.8mm 3.5mm;
            border-top: 0.4mm solid #111827;
        }
        .footer-label {
            color: #475569;
            font-size: 1.65mm;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.25mm;
            text-transform: uppercase;
        }
        .footer-value {
            margin-top: 0.7mm;
            color: #111827;
            font-size: 2.2mm;
            line-height: 1;
            font-weight: 700;
            white-space: nowrap;
        }
        .footer-sequence { text-align: right; }
        .footer-sequence-value {
            margin-top: 0.3mm;
            color: #020617;
            font-family: 'Courier New', monospace;
            font-size: 3.4mm;
            line-height: 1;
            font-weight: 900;
        }
        @media screen {
            body { padding: 12px; background: #f1f5f9; }
            .label { outline: 1.5px solid #111827; }
        }
        @media print {
            html, body { width: 80mm; height: 120mm; }
            body { padding: 0; }
            .label { page-break-after: always; break-after: page; }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="top">
            <div>
                <div class="brand">PARCELMAN</div>
                <div class="kind">Transport Container</div>
            </div>
            <div class="type">{{ str($container->container_type)->replace('_', ' ')->title() }}</div>
        </div>

        <div class="identity">
            <div class="label-title">Container ID</div>
            <div class="code">{{ $container->container_code }}</div>
            <div class="barcode">{!! $barcodeSvg !!}</div>
            <div class="scan-hint">Scan at each warehouse handoff</div>
        </div>

        <div class="route">
            <div class="label-title">Route</div>
            <div class="route-value {{ $routeSizeClass }}">{{ $routeLabel }}</div>
            <div class="manifest-number">Manifest&nbsp;&nbsp;{{ $manifest->manifest_number }}</div>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-value">{{ $itemCount }}</div>
                <div class="stat-label">{{ $itemCount === 1 ? 'Line' : 'Lines' }}</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ $itemQuantity }}</div>
                <div class="stat-label">Total Qty</div>
            </div>
            <div class="stat">
                <div class="stat-value">{{ str_pad((string) ($container->sequence_number ?? 0), 2, '0', STR_PAD_LEFT) }}</div>
                <div class="stat-label">Container</div>
            </div>
        </div>

        <div class="footer">
            <div>
                <div class="footer-label">Printed</div>
                <div class="footer-value">{{ now()->format('d M Y, h:i A') }}</div>
            </div>
            <div class="footer-sequence">
                <div class="footer-label">Container</div>
                <div class="footer-sequence-value">{{ $containerSequence }}</div>
            </div>
        </div>
    </div>
</body>
</html>
