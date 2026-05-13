@php
    $routeLabel = trim(($manifest->originWarehouse?->name ?? 'Origin') . ' to ' . ($manifest->destinationWarehouse?->name ?? 'Destination'));
    $itemCount = $container->items->count();
    $itemQuantity = $container->items->sum('expected_quantity');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Container Label - {{ $container->container_code }}</title>
    <style>
        @page { size: 4in 3in; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fff; color: #020617; font-family: 'Segoe UI', Arial, sans-serif; padding: 0; }
        .label { width: 4in; margin: 0 auto; border: 1.5px solid #111827; overflow: hidden; }
        .top { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding: 8px 10px 6px; border-bottom: 1.5px solid #111827; }
        .brand { font-size: 14px; font-weight: 900; letter-spacing: 1.8px; }
        .kind { margin-top: 1px; color: #64748b; font-size: 7px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
        .type { color: #475569; font-size: 8px; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; white-space: nowrap; padding-top: 2px; }
        .main { padding: 8px 10px 7px; }
        .code { font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.1; font-weight: 900; letter-spacing: .8px; text-align: center; word-break: break-word; }
        .barcode { margin: 7px auto 4px; text-align: center; }
        .barcode svg { display: block; max-width: 100%; height: 56px; margin: 0 auto; }
        .route { border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 6px 0; }
        .label-title { color: #64748b; font-size: 7px; font-weight: 900; letter-spacing: 1.4px; text-transform: uppercase; }
        .value { margin-top: 2px; font-size: 10.5px; font-weight: 900; line-height: 1.18; }
        .subvalue { margin-top: 2px; color: #475569; font-size: 8px; font-weight: 700; line-height: 1.15; }
        .stats { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 6px 0 0; font-size: 10px; font-weight: 900; }
        .stats span { white-space: nowrap; }
        .footer { border-top: 1.5px solid #111827; padding: 4px 10px; color: #475569; font-size: 7.5px; font-weight: 800; display: flex; justify-content: space-between; gap: 8px; }
        @media print {
            body { padding: 0; }
            .label { margin: 0; page-break-after: always; }
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

        <div class="main">
            <div class="code">{{ $container->container_code }}</div>
            <div class="barcode">{!! $barcodeSvg !!}</div>

            <div class="route">
                <div class="label-title">Route</div>
                <div class="value">{{ $routeLabel }}</div>
                <div class="subvalue">Manifest {{ $manifest->manifest_number }}</div>
            </div>

            <div class="stats">
                <span>{{ $itemCount }} {{ $itemCount === 1 ? 'line' : 'lines' }}</span>
                <span>{{ $itemQuantity }} qty</span>
            </div>
        </div>

        <div class="footer">
            <span>Printed {{ now()->format('d M Y, h:i A') }}</span>
            <span>{{ $container->container_code }}</span>
        </div>
    </div>
</body>
</html>
