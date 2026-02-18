<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Item Label - {{ $shipmentItem->tracking_code }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f8fafc; }
        .label {
            width: 430px;
            margin: 0 auto;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            background: #fff;
            padding: 14px;
            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.12);
        }
        .header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; }
        .title { font-size: 14px; font-weight: 700; color: #0f172a; }
        .meta { font-size: 11px; color: #64748b; }
        .line { display: flex; justify-content: space-between; margin: 6px 0; font-size: 12px; color: #1e293b; }
        .line span:first-child { color: #64748b; }
        .barcode { margin-top: 10px; text-align: center; }
        .barcode svg { max-width: 100%; height: auto; }
    </style>
</head>
<body onload="window.print()">
    <div class="label">
        <div class="header">
            <div class="title">Parcelman Item Label</div>
            <div class="meta">Print #{{ (int) $receiptItem->barcode_print_count }}</div>
        </div>

        <div class="line">
            <span>Shipment</span>
            <strong>{{ $shipment?->shipment_number }}</strong>
        </div>
        <div class="line">
            <span>Vendor</span>
            <strong>{{ $shipment?->vendor?->name }}</strong>
        </div>
        <div class="line">
            <span>Item</span>
            <strong>{{ $shipmentItem->description }}</strong>
        </div>
        <div class="line">
            <span>Expected / Received</span>
            <strong>{{ (int) $receiptItem->expected_quantity }} / {{ (int) $receiptItem->received_quantity }}</strong>
        </div>
        <div class="line">
            <span>Destination</span>
            <strong>
                @if($shipment?->isPerItemDestination())
                    {{ $shipmentItem->delivery_town ?: '-' }}
                @else
                    {{ $shipment?->delivery_town ?: '-' }}
                @endif
            </strong>
        </div>
        <div class="line">
            <span>Tracking</span>
            <strong>{{ $shipmentItem->tracking_code }}</strong>
        </div>

        <div class="barcode">{!! $barcodeSvg !!}</div>
    </div>
</body>
</html>

