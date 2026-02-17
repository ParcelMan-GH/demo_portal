<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #1f2937; line-height: 1.5; }

        .header { padding: 30px 40px; background: #f97316; color: white; }
        .header-row { display: table; width: 100%; }
        .header-left { display: table-cell; vertical-align: top; width: 60%; }
        .header-right { display: table-cell; vertical-align: top; width: 40%; text-align: right; }
        .company-name { font-size: 20px; font-weight: bold; margin-bottom: 4px; }
        .invoice-title { font-size: 24px; font-weight: bold; }
        .invoice-number { font-size: 14px; opacity: 0.9; margin-top: 2px; }

        .meta-bar { padding: 15px 40px; background: #fff7ed; border-bottom: 2px solid #fed7aa; }
        .meta-row { display: table; width: 100%; }
        .meta-cell { display: table-cell; width: 25%; }
        .meta-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; font-weight: 600; }
        .meta-value { font-size: 12px; font-weight: 600; color: #1f2937; margin-top: 2px; }

        .body { padding: 30px 40px; }

        .section-title { font-size: 13px; font-weight: 700; color: #374151; margin-bottom: 12px; padding-bottom: 6px; border-bottom: 2px solid #f3f4f6; }

        .fee-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .fee-table th { text-align: left; padding: 8px 12px; background: #f9fafb; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; border-bottom: 1px solid #e5e7eb; }
        .fee-table td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
        .fee-table .fee-name { font-weight: 600; color: #1f2937; }
        .fee-table .fee-desc { font-size: 10px; color: #9ca3af; margin-top: 1px; }
        .fee-table .fee-amount { text-align: right; font-weight: 700; color: #1f2937; font-size: 13px; }

        .total-row { background: #fff7ed; border-top: 2px solid #f97316; }
        .total-row td { padding: 14px 12px; }
        .total-label { font-size: 14px; font-weight: 700; color: #1f2937; }
        .total-amount { text-align: right; font-size: 18px; font-weight: 800; color: #c2410c; }

        .details-grid { display: table; width: 100%; margin-top: 24px; }
        .details-col { display: table-cell; width: 50%; vertical-align: top; padding-right: 20px; }
        .details-col:last-child { padding-right: 0; padding-left: 20px; }

        .detail-block { margin-bottom: 16px; }
        .detail-block-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #f97316; margin-bottom: 8px; }
        .detail-row { display: table; width: 100%; margin-bottom: 4px; }
        .detail-label { display: table-cell; width: 35%; font-size: 10px; color: #6b7280; padding: 2px 0; }
        .detail-value { display: table-cell; font-size: 11px; font-weight: 600; color: #1f2937; padding: 2px 0; }

        .notes-section { margin-top: 20px; }
        .note-block { padding: 10px 14px; background: #f9fafb; border-left: 3px solid #d1d5db; margin-bottom: 8px; border-radius: 0 4px 4px 0; }
        .note-block.danger { border-left-color: #ef4444; background: #fef2f2; }
        .note-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; font-weight: 600; margin-bottom: 3px; }
        .note-text { font-size: 11px; color: #374151; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fff7ed; color: #ea580c; }
        .status-sent { background: #fff7ed; color: #c2410c; }
        .status-accepted { background: #ecfdf5; color: #059669; }
        .status-rejected { background: #fef2f2; color: #dc2626; }
        .status-cancelled { background: #fef2f2; color: #dc2626; }

        .footer { padding: 20px 40px; border-top: 1px solid #e5e7eb; text-align: center; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <div class="header-row">
            <div class="header-left">
                @if(!empty($logoBase64))
                <img src="{{ $logoBase64 }}" alt="ParcelMan Express" style="height: 50px; width: auto; margin-bottom: 4px; background: white; border-radius: 6px; padding: 4px 8px;">
                @else
                <div class="company-name">ParcelMan Express</div>
                @endif
                <div style="font-size: 10px; opacity: 0.8;">Logistics & Delivery Services</div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            </div>
        </div>
    </div>

    {{-- Meta bar --}}
    <div class="meta-bar">
        <div class="meta-row">
            <div class="meta-cell">
                <div class="meta-label">Status</div>
                <div class="meta-value">
                    @php
                        $vendorStatusMap = ['pending' => 'Pending', 'sent' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
                        $vendorStatus = $vendorStatusMap[$invoice->status->value] ?? ucfirst($invoice->status->value);
                    @endphp
                    <span class="status-badge status-{{ $invoice->status->value }}">{{ $vendorStatus }}</span>
                </div>
            </div>
            <div class="meta-cell">
                <div class="meta-label">Date Issued</div>
                <div class="meta-value">{{ $invoice->created_at->format('M d, Y') }}</div>
            </div>
            <div class="meta-cell">
                <div class="meta-label">Currency</div>
                <div class="meta-value">{{ $invoice->currency ?: 'GHS' }}</div>
            </div>
            <div class="meta-cell">
                <div class="meta-label">Shipment</div>
                <div class="meta-value">{{ $shipment->shipment_number }}</div>
            </div>
        </div>
    </div>

    <div class="body">
        {{-- Vendor info --}}
        <div class="details-grid">
            <div class="details-col">
                <div class="detail-block">
                    <div class="detail-block-title">Bill To</div>
                    <div class="detail-row">
                        <div class="detail-label">Vendor</div>
                        <div class="detail-value">{{ $vendor->business_name ?: $vendor->name }}</div>
                    </div>
                    @if($vendor->name && $vendor->business_name)
                    <div class="detail-row">
                        <div class="detail-label">Contact</div>
                        <div class="detail-value">{{ $vendor->name }}</div>
                    </div>
                    @endif
                    @if($vendor->phone)
                    <div class="detail-row">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value">{{ $vendor->phone }}</div>
                    </div>
                    @endif
                    @if($vendor->email)
                    <div class="detail-row">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">{{ $vendor->email }}</div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="details-col">
                <div class="detail-block">
                    <div class="detail-block-title">Invoice Details</div>
                    <div class="detail-row">
                        <div class="detail-label">Invoice #</div>
                        <div class="detail-value">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Created</div>
                        <div class="detail-value">{{ $invoice->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @if($invoice->sent_at)
                    <div class="detail-row">
                        <div class="detail-label">Sent</div>
                        <div class="detail-value">{{ $invoice->sent_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                    @if($invoice->accepted_at)
                    <div class="detail-row">
                        <div class="detail-label">Accepted</div>
                        <div class="detail-value" style="color: #059669;">{{ $invoice->accepted_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                    @if($invoice->rejected_at)
                    <div class="detail-row">
                        <div class="detail-label">Rejected</div>
                        <div class="detail-value" style="color: #dc2626;">{{ $invoice->rejected_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Fee Breakdown --}}
        <div class="section-title">Fee Breakdown</div>
        <table class="fee-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="fee-name">Pickup Fee</div>
                        <div class="fee-desc">Collection from sender</div>
                    </td>
                    <td class="fee-amount">{{ number_format((float) $invoice->pickup_fee, 2) }} {{ $invoice->currency ?: 'GHS' }}</td>
                </tr>
                <tr>
                    <td>
                        <div class="fee-name">Transport Fee</div>
                        <div class="fee-desc">Shipment transportation</div>
                    </td>
                    <td class="fee-amount">{{ number_format((float) $invoice->transport_fee, 2) }} {{ $invoice->currency ?: 'GHS' }}</td>
                </tr>
                <tr>
                    <td>
                        <div class="fee-name">Handling Fee</div>
                        <div class="fee-desc">Package handling &amp; processing</div>
                    </td>
                    <td class="fee-amount">{{ number_format((float) $invoice->handling_fee, 2) }} {{ $invoice->currency ?: 'GHS' }}</td>
                </tr>
                <tr>
                    <td>
                        <div class="fee-name">Other Fee</div>
                        <div class="fee-desc">Additional charges</div>
                    </td>
                    <td class="fee-amount">{{ number_format((float) $invoice->other_fee, 2) }} {{ $invoice->currency ?: 'GHS' }}</td>
                </tr>
                <tr class="total-row">
                    <td class="total-label">Total Amount</td>
                    <td class="total-amount">{{ number_format((float) $invoice->total_amount, 2) }} {{ $invoice->currency ?: 'GHS' }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Notes --}}
        @if($invoice->notes || $invoice->vendor_notes || $invoice->rejection_reason || $invoice->cancel_reason)
        <div class="notes-section">
            <div class="section-title">Notes</div>

            @if($invoice->notes)
            <div class="note-block">
                <div class="note-label">Admin Notes</div>
                <div class="note-text">{{ $invoice->notes }}</div>
            </div>
            @endif

            @if($invoice->vendor_notes)
            <div class="note-block">
                <div class="note-label">Vendor Notes</div>
                <div class="note-text">{{ $invoice->vendor_notes }}</div>
            </div>
            @endif

            @if($invoice->rejection_reason)
            <div class="note-block danger">
                <div class="note-label">Rejection Reason</div>
                <div class="note-text">{{ $invoice->rejection_reason }}</div>
            </div>
            @endif

            @if($invoice->cancel_reason)
            <div class="note-block danger">
                <div class="note-label">Cancel Reason</div>
                <div class="note-text">{{ $invoice->cancel_reason }}</div>
            </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Footer --}}
    <div class="footer">
        <p>Generated on {{ now()->format('M d, Y h:i A') }} &mdash; ParcelMan Express</p>
    </div>
</body>
</html>
