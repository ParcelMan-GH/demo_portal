<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt PMR-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html {
            background: #f3f4f6;
            min-height: 100%;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            font-size: 11px;
            color: #111827;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 0;
        }

        .page {
            width: 80mm;
            background: #ffffff;
        }

        /* ── Header: logo left, receipt info right ── */
        .header {
            background: #111827;
            padding: 22px 28px;
            display: table;
            width: 100%;
        }
        .header-left {
            display: table-cell;
            vertical-align: middle;
            width: 55%;
        }
        .header-right {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 45%;
        }
        .header img {
            height: 38px;
            width: auto;
            background: white;
            border-radius: 4px;
            padding: 4px 10px;
            display: block;
        }
        .company-name {
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: -0.3px;
        }
        .company-tagline {
            font-size: 10px;
            color: rgba(255,255,255,0.4);
            margin-top: 2px;
        }
        .receipt-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.4);
            margin-bottom: 4px;
        }
        .receipt-number {
            font-size: 14px;
            font-weight: 800;
            color: #ffffff;
            font-family: 'Courier New', monospace;
            letter-spacing: 0.5px;
        }
        .receipt-date {
            font-size: 10px;
            color: rgba(255,255,255,0.5);
            margin-top: 3px;
        }

        /* ── Amount hero ── */
        .amount-hero {
            background: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
            padding: 20px 28px;
            display: table;
            width: 100%;
        }
        .amount-label-cell {
            display: table-cell;
            vertical-align: middle;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            font-weight: 600;
        }
        .amount-value-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
        }
        .amount-value {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            letter-spacing: -1px;
            line-height: 1;
        }
        .amount-currency {
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0;
            color: #6b7280;
            vertical-align: super;
            margin-right: 2px;
        }

        /* ── Details rows ── */
        .details-section {
            padding: 18px 28px;
            border-bottom: 1px dashed #e5e7eb;
        }
        .section-heading {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            margin-bottom: 12px;
        }
        .detail-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-key {
            display: table-cell;
            font-size: 11px;
            color: #6b7280;
            width: 45%;
        }
        .detail-val {
            display: table-cell;
            font-size: 11px;
            font-weight: 600;
            color: #111827;
            text-align: right;
        }
        .method-badge {
            display: inline-block;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 4px;
        }

        /* ── Reference block ── */
        .ref-section {
            padding: 14px 28px;
            background: #f9fafb;
            border-bottom: 1px dashed #e5e7eb;
            display: table;
            width: 100%;
        }
        .ref-label-cell {
            display: table-cell;
            vertical-align: middle;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #9ca3af;
            font-weight: 700;
        }
        .ref-value-cell {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            font-size: 13px;
            font-weight: 700;
            color: #111827;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        /* ── Notes ── */
        .notes-section {
            padding: 14px 28px;
            background: #fffbeb;
            border-bottom: 1px dashed #fde68a;
        }
        .notes-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #92400e;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .notes-text {
            font-size: 11px;
            color: #78350f;
            line-height: 1.5;
        }

        /* ── Footer ── */
        .footer {
            padding: 16px 28px;
            text-align: center;
        }
        .footer-thank {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 3px;
        }
        .footer-sub {
            font-size: 10px;
            color: #9ca3af;
        }
        .footer-generated {
            font-size: 9px;
            color: #d1d5db;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #f3f4f6;
            font-family: 'Courier New', monospace;
        }

        .tear-line {
            border: none;
            border-top: 2px dashed #e5e7eb;
            margin: 0;
        }

        .print-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 80mm;
            margin: 16px auto 0;
            padding: 11px 0;
            background: #111827;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            border: none;
            cursor: pointer;
        }
        .print-btn:hover { background: #1f2937; }

        @page {
            size: 80mm auto;
            margin: 0;
        }
        @media print {
            html, body { background: #fff; margin: 0; padding: 0; width: 80mm; display: block; min-height: unset; }
            .page { margin: 0; width: 80mm; box-shadow: none; }
            .print-btn { display: none; }
        }
    </style>
    @if(!empty($autoPrint))
    <script>
        window.addEventListener('load', function () {
            setTimeout(function () { window.print(); }, 400);
        });
    </script>
    @endif
</head>
<body>
<div class="page">

    {{-- Header: logo left, receipt info right --}}
    <div class="header">
        <div class="header-left">
            @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="ParcelMan Express">
            @else
                <div class="company-name">ParcelMan Express</div>
                <div class="company-tagline">Logistics &amp; Delivery Services</div>
            @endif
        </div>
        <div class="header-right">
            <div class="receipt-label">Payment Receipt</div>
            <div class="receipt-number">PMR-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div class="receipt-date">{{ $payment->payment_date?->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Amount Hero --}}
    <div class="amount-hero">
        <div class="amount-label-cell">Amount Paid</div>
        <div class="amount-value-cell">
            <div class="amount-value">
                <span class="amount-currency">{{ $payment->invoice?->currency ?: 'GHS' }}</span>{{ number_format((float) $payment->amount, 2) }}
            </div>
        </div>
    </div>

    <hr class="tear-line">

    {{-- Payment Details --}}
    <div class="details-section">
        <div class="section-heading">Payment Information</div>
        <div class="detail-row">
            <div class="detail-key">Payment Method</div>
            <div class="detail-val"><span class="method-badge">{{ $payment->methodLabel() }}</span></div>
        </div>
        <div class="detail-row">
            <div class="detail-key">Payment Date</div>
            <div class="detail-val">{{ $payment->payment_date?->format('d M Y, H:i') }}</div>
        </div>
        <div class="detail-row">
            <div class="detail-key">Recorded By</div>
            <div class="detail-val">{{ $payment->recordedBy?->name ?? 'Admin' }}</div>
        </div>
    </div>

    {{-- Linked Records --}}
    <div class="details-section">
        <div class="section-heading">Linked Records</div>
        <div class="detail-row">
            <div class="detail-key">Shipment</div>
            <div class="detail-val">{{ $payment->shipment?->shipment_number ?? '—' }}</div>
        </div>
        @if($payment->invoice)
        <div class="detail-row">
            <div class="detail-key">Invoice</div>
            <div class="detail-val">{{ $payment->invoice->invoice_number }}</div>
        </div>
        @endif
        @if($payment->shipment?->vendor)
        <div class="detail-row">
            <div class="detail-key">Vendor</div>
            <div class="detail-val">{{ $payment->shipment->vendor->business_name ?: $payment->shipment->vendor->name }}</div>
        </div>
        @endif
    </div>

    {{-- Reference Number --}}
    @if($payment->reference_number)
    <div class="ref-section">
        <div class="ref-label-cell">Reference Number</div>
        <div class="ref-value-cell">{{ $payment->reference_number }}</div>
    </div>
    @endif

    {{-- Notes --}}
    @if($payment->notes)
    <div class="notes-section">
        <div class="notes-label">Notes</div>
        <div class="notes-text">{{ $payment->notes }}</div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-thank">Thank you for your payment.</div>
        <div class="footer-sub">ParcelMan Express &mdash; Logistics &amp; Delivery Services</div>
        <div class="footer-generated">
            Generated {{ now()->format('d M Y H:i') }} &nbsp;|&nbsp; PMR-{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}
        </div>
    </div>

</div>

@if(!empty($autoPrint))
<button class="print-btn" onclick="window.print()">
    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
    Print
</button>
@endif
</body>
</html>
