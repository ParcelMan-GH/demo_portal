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

    $formatPhone = function ($phone) {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        if (strlen($digits) === 12 && str_starts_with($digits, '233')) {
            $digits = substr($digits, 3);
            return '+233 '.substr($digits, 0, 2).' '.substr($digits, 2, 3).' '.substr($digits, 5, 2).' '.substr($digits, 7, 2);
        }
        return $phone ?: '';
    };

    $supportPhone = \App\Models\PlatformSetting::getValue('platform_phone', '+233 30 000 0000');
    $tracking = $labelBarcode ?? $shipmentItem->tracking_code;
@endphp

<div class="label" style="page-break-after: always;">

    {{-- Branded orange header --}}
    <div class="header-band">
        <div class="header-row">
            <div class="logo-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="#ea580c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
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

    {{-- From / To cards --}}
    <div class="addr-grid">
        <div class="addr-card">
            <div class="addr-head">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
                <span class="addr-label">From</span>
            </div>
            <div class="addr-name">{{ $shipment?->vendor?->name ?: '-' }}</div>
            <div class="addr-sub">{{ $shipment?->vendor?->business_name ?: 'Sender' }}</div>
            @if($shipment?->vendor?->phone)
                <div class="addr-phone">{{ $formatPhone($shipment->vendor->phone) }}</div>
            @endif
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
            @if($destSource?->delivery_recipient_phone)
                <div class="addr-phone">{{ $formatPhone($destSource->delivery_recipient_phone) }}</div>
            @endif
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
        <span class="dash"></span>
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
        <span>ParcelMan Express - {{ $originName }}</span>
        <span class="foot-right">
            <span class="support-label">Support</span><br>
            {{ $supportPhone }}
        </span>
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
