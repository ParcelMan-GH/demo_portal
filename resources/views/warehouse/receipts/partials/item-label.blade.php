<div class="label" style="page-break-after: always;">
    <div class="header">
        <div class="title">Parcelman Package Label</div>
        <div class="meta">
            @if(isset($labelIndex) && isset($labelsTotal) && $labelsTotal > 1)
                {{ $labelIndex }} of {{ $labelsTotal }}
            @else
                Print #{{ (int) $receiptItem->barcode_print_count }}
            @endif
        </div>
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
        <span>Package</span>
        <strong>{{ $shipmentItem->description ?: '-' }}</strong>
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
        <strong>{{ $labelBarcode ?? $shipmentItem->tracking_code }}</strong>
    </div>

    <div class="barcode">{!! $barcodeSvg !!}</div>
</div>
