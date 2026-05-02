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
        @page { size: 4in 6in; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #fff; color: #020617; font-family: 'Segoe UI', Arial, sans-serif; padding: 10px; }
        .label { width: 4in; min-height: 5.75in; margin: 0 auto; border: 2px solid #111827; border-radius: 6px; overflow: hidden; }
        .header { padding: 14px 16px 12px; border-bottom: 2px solid #111827; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
        .brand { font-size: 18px; font-weight: 900; letter-spacing: 2px; }
        .kind { margin-top: 3px; font-size: 9px; font-weight: 800; color: #64748b; letter-spacing: 2px; text-transform: uppercase; }
        .badge { border: 1.5px solid #111827; border-radius: 999px; padding: 5px 9px; font-size: 10px; font-weight: 900; text-transform: uppercase; white-space: nowrap; }
        .main { padding: 16px; }
        .code { font-family: 'Courier New', monospace; font-size: 18px; line-height: 1.15; font-weight: 900; letter-spacing: 1.5px; text-align: center; word-break: break-word; }
        .barcode { margin: 14px auto 12px; text-align: center; }
        .barcode svg { max-width: 100%; height: 78px; }
        .section { border-top: 1px solid #cbd5e1; padding: 11px 0; }
        .section:first-of-type { border-top: 0; }
        .label-title { color: #64748b; font-size: 8px; font-weight: 900; letter-spacing: 1.6px; text-transform: uppercase; margin-bottom: 3px; }
        .value { font-size: 13px; font-weight: 800; line-height: 1.25; }
        .subvalue { margin-top: 2px; color: #475569; font-size: 10px; line-height: 1.35; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .box { border: 1px solid #cbd5e1; border-radius: 8px; padding: 9px; }
        .big { font-size: 22px; font-weight: 900; line-height: 1; }
        .footer { border-top: 2px solid #111827; padding: 10px 16px; font-size: 9px; color: #475569; font-weight: 700; display: flex; justify-content: space-between; gap: 10px; }
        @media print {
            body { padding: 0; }
            .label { border-radius: 0; margin: 0; page-break-after: always; }
        }
    </style>
</head>
<body>
    <div class="label">
        <div class="header">
            <div>
                <div class="brand">PARCELMAN</div>
                <div class="kind">Transport Container</div>
            </div>
            <div class="badge">{{ str($container->container_type)->replace('_', ' ')->title() }}</div>
        </div>

        <div class="main">
            <div class="code">{{ $container->container_code }}</div>
            <div class="barcode">{!! $barcodeSvg !!}</div>

            <div class="section">
                <div class="label-title">Route</div>
                <div class="value">{{ $routeLabel }}</div>
                <div class="subvalue">Manifest {{ $manifest->manifest_number }}</div>
            </div>

            <div class="section grid">
                <div class="box">
                    <div class="label-title">Package Lines</div>
                    <div class="big">{{ $itemCount }}</div>
                </div>
                <div class="box">
                    <div class="label-title">Item Qty</div>
                    <div class="big">{{ $itemQuantity }}</div>
                </div>
            </div>

            <div class="section">
                <div class="label-title">Driver Action</div>
                <div class="value">Scan this code to load the whole container.</div>
                <div class="subvalue">If items are loose and not physically packed, scan each item label instead.</div>
            </div>
        </div>

        <div class="footer">
            <span>Printed {{ now()->format('M d, Y H:i') }}</span>
            <span>{{ str($container->status)->replace('_', ' ')->title() }}</span>
        </div>
    </div>
</body>
</html>
