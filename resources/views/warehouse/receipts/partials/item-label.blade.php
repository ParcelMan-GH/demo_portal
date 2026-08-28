@php
    $isPerItem = $shipment?->isPerItemDestination();
    $destSource = $isPerItem ? $shipmentItem : $shipment;
    $originName = $receiptItem?->receipt?->warehouse?->name
        ?? $shipment?->pickup_town
        ?? '—';
    $destinationName = $destSource?->deliveryRegion?->name
        ?? $destSource?->delivery_town
        ?? '—';

    $status = $shipmentItem->status ?? null;
    $statusLabel = $status instanceof \App\Enums\ItemStatus
        ? $status->label()
        : ucwords(str_replace('_', ' ', (string) ($status ?: 'in_transit')));

    $tracking = $labelBarcode ?? $shipmentItem->tracking_code;
@endphp

<div class="label" style="page-break-after: always;">

    {{-- Branded header (solid dark band — B/W & thermal safe) --}}
    <div class="header-band">
        <div class="header-row">
            <div class="logo-box">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 1.8l8.4 4.85v9.7L12 21.2l-8.4-4.85v-9.7L12 1.8z" fill="#fff"/>
                    <path d="M7.2 9.4l4.8-2.77 4.8 2.77v5.54l-4.8 2.77-4.8-2.77V9.4z" fill="#0f172a"/>
                    <path d="M12 6.63l4.8 2.77-4.8 2.77-4.8-2.77 4.8-2.77z" fill="#334155"/>
                    <path d="M7.2 9.4l4.8 2.77v5.54L7.2 14.94V9.4z" fill="#1e293b"/>
                    <path d="M16.8 9.4l-4.8 2.77v5.54l4.8-2.77V9.4z" fill="#0f172a"/>
                </svg>
            </div>
            <div>
                <div class="brand-name">PARCELMAN</div>
                <div class="brand-sub">EXPRESS</div>
            </div>
            <div class="qr-wrap">
                <div class="qr-code" id="qr-{{ $tracking }}"></div>
            </div>
        </div>
        <div class="status-bar">
            <span class="pill-doctype">Delivery Receipt</span>
            <span class="pill-status"><span class="dot"></span>{{ $statusLabel }}</span>
        </div>
    </div>

    {{-- From / To cards (no phone numbers — privacy on a travelling label) --}}
    <div class="addr-grid">
        <div class="addr-card">
            <div class="addr-head">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                <span class="addr-label">From</span>
            </div>
            <div class="addr-name">{{ $shipment?->vendor?->name ?: '-' }}</div>
            <div class="addr-sub">Sender · {{ $shipment?->vendor?->business_name ?: 'ParcelMan pickup' }}</div>
        </div>
        <div class="addr-card">
            <div class="addr-head">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                <span class="addr-label">To</span>
            </div>
            <div class="addr-name">{{ $destSource?->delivery_recipient_name ?: '-' }}</div>
            <div class="addr-line">
                @if($destSource?->delivery_town){{ $destSource->delivery_town }}@endif
                @if($destSource?->deliveryDistrict), {{ $destSource->deliveryDistrict->name }}@endif
                @if($destSource?->deliveryRegion), {{ $destSource->deliveryRegion->name }}@endif
            </div>
        </div>
    </div>

    {{-- Details grid --}}
    <div class="details-grid">
        <div class="detail-box">
            <span class="detail-label">Package</span>
            <div class="detail-value">{{ $shipmentItem->description ?: 'Package' }}@if($shipmentItem->quantity) · {{ $shipmentItem->quantity }} pc{{ $shipmentItem->quantity > 1 ? 's' : '' }}@endif</div>
        </div>
        <div class="detail-box">
            <span class="detail-label">Shipment</span>
            <div class="detail-value">{{ $shipment?->shipment_number ?: '-' }}</div>
        </div>
        <div class="detail-box">
            <span class="detail-label">Origin</span>
            <div class="detail-value">{{ $originName }}</div>
        </div>
        <div class="detail-box">
            <span class="detail-label">Destination</span>
            <div class="detail-value">{{ $destinationName }}</div>
        </div>
    </div>

    {{-- Tracking --}}
    <div class="scan-to-track">
        <svg class="scan-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10"/>
        </svg>
        <span class="scan-text">Scan to Track</span>
        <span class="dash"></span>
    </div>
    <div class="barcode-card">
        <div class="barcode-svg">{!! $barcodeSvg !!}</div>
        <div class="barcode-text">{{ $tracking }}</div>
        <div class="barcode-sub">Tracking Number</div>
        @if(isset($labelsTotal) && $labelsTotal > 1)
            <div class="label-count">Label {{ $labelIndex }} of {{ $labelsTotal }}</div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="label-foot">
        <span>ParcelMan Express · {{ $originName }}</span>
        <span class="foot-right">Delivery Receipt · {{ $shipment?->shipment_number }}</span>
    </div>
</div>

<script>
(function(){
    var code = '{{ $tracking }}';
    var el = document.getElementById('qr-' + code);
    if (el && typeof QRCode !== 'undefined') {
        new QRCode(el, { text: code, width: 64, height: 64, colorDark: '#0f172a', colorLight: '#ffffff', correctLevel: QRCode.CorrectLevel.M });
    }
})();
</script>
