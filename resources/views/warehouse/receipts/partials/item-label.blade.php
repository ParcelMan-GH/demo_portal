<div class="label" style="page-break-after: always;">
    {{-- Header band (prints as solid black on B/W thermal printers) --}}
    <div class="brandbar">
        <div class="brandbar-row">
            <div class="brand">
                <div class="brand-name">PARCELMAN</div>
                <div class="brand-sub">EXPRESS</div>
            </div>
            <div class="qr-container">
                <div class="qr-code" id="qr-{{ $labelBarcode ?? $shipmentItem->tracking_code }}"></div>
            </div>
        </div>
        <div class="brandbar-meta">
            <span class="doctype">Delivery Receipt</span>
            <span class="status-chip">{{ $shipmentItem->status?->value ?? 'In Transit' }}</span>
        </div>
    </div>

    {{-- From / To (no phone numbers — privacy on a travelling label) --}}
    <div class="addresses">
        <div class="address-block">
            <div class="address-label">From</div>
            <div class="address-name">{{ $shipment?->vendor?->name ?? '-' }}</div>
            <div class="address-detail">
                @if($shipment?->pickup_town){{ $shipment->pickup_town }}@endif
                @if($shipment?->pickupDistrict), {{ $shipment->pickupDistrict->name }}@endif
                @if($shipment?->pickupRegion), {{ $shipment->pickupRegion->name }}@endif
            </div>
        </div>
        <div class="address-divider"></div>
        <div class="address-block address-block-to">
            <div class="address-label">To</div>
            @if($shipment?->isPerItemDestination())
                <div class="address-name">{{ $shipmentItem->delivery_recipient_name ?: '-' }}</div>
                <div class="address-detail">
                    @if($shipmentItem->delivery_town){{ $shipmentItem->delivery_town }}@endif
                    @if($shipmentItem->deliveryDistrict), {{ $shipmentItem->deliveryDistrict->name }}@endif
                    @if($shipmentItem->deliveryRegion), {{ $shipmentItem->deliveryRegion->name }}@endif
                </div>
            @else
                <div class="address-name">{{ $shipment?->delivery_recipient_name ?: '-' }}</div>
                <div class="address-detail">
                    @if($shipment?->delivery_town){{ $shipment->delivery_town }}@endif
                    @if($shipment?->deliveryDistrict), {{ $shipment->deliveryDistrict->name }}@endif
                    @if($shipment?->deliveryRegion), {{ $shipment->deliveryRegion->name }}@endif
                </div>
            @endif
        </div>
    </div>

    <div class="divider"></div>

    {{-- Package / Shipment meta --}}
    <div class="pkg-info">
        <div class="pkg-row">
            <span class="pkg-label">Package</span>
            <span class="pkg-value">{{ $shipmentItem->description ?: 'Package' }}</span>
        </div>
        <div class="pkg-row">
            <span class="pkg-label">Shipment</span>
            <span class="pkg-value">{{ $shipment?->shipment_number }}</span>
        </div>
        @if(isset($labelsTotal) && $labelsTotal > 1)
        <div class="pkg-row">
            <span class="pkg-label">Label</span>
            <span class="pkg-value pkg-bold">{{ $labelIndex }} of {{ $labelsTotal }}</span>
        </div>
        @endif
    </div>

    {{-- Barcode --}}
    <div class="barcode-section">
        <div class="barcode-svg">{!! $barcodeSvg !!}</div>
        <div class="barcode-text">{{ $labelBarcode ?? $shipmentItem->tracking_code }}</div>
        <div class="barcode-sub">Tracking Number</div>
    </div>

    {{-- Footer --}}
    <div class="label-foot">
        <span>ParcelMan Express</span>
        <span>{{ $shipment?->shipment_number }}</span>
    </div>
</div>

<script>
(function(){
    var code = '{{ $labelBarcode ?? $shipmentItem->tracking_code }}';
    var el = document.getElementById('qr-' + code);
    if (el && typeof QRCode !== 'undefined') {
        new QRCode(el, { text: code, width: 72, height: 72, colorDark: '#0f172a', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
    }
})();
</script>
