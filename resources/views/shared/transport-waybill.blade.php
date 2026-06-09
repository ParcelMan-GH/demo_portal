@php
    $waybillNumber = 'WB-' . preg_replace('/^TM-/', '', (string) $manifest->manifest_number);
    $containers = $manifest->containers->sortBy('sequence_number')->values();
    $manifestContainerLineIds = $containers
        ->flatMap(fn ($container) => $container->items->pluck('transport_manifest_item_id'))
        ->map(fn ($id) => (int) $id)
        ->all();
    $looseLines = $manifest->items->filter(fn ($line) => !in_array((int) $line->id, $manifestContainerLineIds, true))->values();
    $totalQty = (int) $manifest->items->sum('expected_quantity');
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Waybill - {{ $waybillNumber }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { background: #e5e7eb; color: #020617; font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; line-height: 1.35; margin: 0; }
        .sheet { background: #fff; min-height: 297mm; margin: 18px auto; padding: 12mm; width: 210mm; box-shadow: 0 12px 40px rgba(15, 23, 42, .18); }
        .top { align-items: flex-start; border-bottom: 2px solid #111827; display: flex; gap: 18px; justify-content: space-between; padding-bottom: 10px; }
        .brand { font-size: 23px; font-weight: 900; letter-spacing: 4px; line-height: 1; }
        .kind { color: #64748b; font-size: 9px; font-weight: 900; letter-spacing: 2px; margin-top: 5px; text-transform: uppercase; }
        .number { font-family: 'Courier New', monospace; font-size: 14px; font-weight: 900; text-align: right; }
        .printed { color: #475569; font-size: 9px; font-weight: 700; margin-top: 4px; text-align: right; }
        .meta { border-bottom: 1px solid #cbd5e1; display: grid; gap: 8px 18px; grid-template-columns: 1.3fr 1fr; padding: 10px 0; }
        .label { color: #64748b; font-size: 8px; font-weight: 900; letter-spacing: 1.4px; text-transform: uppercase; }
        .value { font-size: 12px; font-weight: 900; margin-top: 2px; }
        .sub { color: #475569; font-size: 9px; font-weight: 700; margin-top: 1px; }
        .counts { border-bottom: 1px solid #cbd5e1; display: flex; gap: 18px; padding: 7px 0; }
        .counts span { font-size: 11px; font-weight: 900; white-space: nowrap; }
        .counts b { color: #64748b; font-size: 8px; letter-spacing: 1px; margin-right: 4px; text-transform: uppercase; }
        h2 { font-size: 11px; letter-spacing: 1.4px; margin: 14px 0 7px; text-transform: uppercase; }
        .container-block { border-top: 1.5px solid #111827; page-break-inside: avoid; padding-top: 7px; margin-top: 10px; }
        .container-head { align-items: baseline; display: flex; flex-wrap: wrap; gap: 8px 14px; justify-content: space-between; margin-bottom: 5px; }
        .container-title { font-family: 'Courier New', monospace; font-size: 11px; font-weight: 900; }
        .container-meta { color: #475569; font-size: 9px; font-weight: 800; }
        table { border-collapse: collapse; width: 100%; }
        th { background: #f8fafc; color: #475569; font-size: 8px; font-weight: 900; letter-spacing: .9px; padding: 4px 5px; text-align: left; text-transform: uppercase; }
        td { border-bottom: 1px solid #e2e8f0; padding: 4px 5px; vertical-align: top; }
        .mono { font-family: 'Courier New', monospace; font-weight: 900; }
        .right { text-align: right; }
        .notes { border-left: 2px solid #cbd5e1; color: #334155; font-size: 9.5px; font-weight: 700; margin: 5px 0 6px; padding-left: 6px; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 34px; page-break-inside: avoid; }
        .sig-title { font-weight: 900; margin-bottom: 12px; }
        .sig-line { border-top: 1.3px solid #111827; color: #64748b; font-size: 8.5px; font-weight: 800; margin-top: 18px; padding-top: 4px; text-transform: uppercase; }
        @media print {
            body { background: #fff; print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .sheet { box-shadow: none; margin: 0; min-height: auto; padding: 0; width: auto; }
        }
    </style>
</head>
<body>
    <main class="sheet">
        <section class="top">
            <div>
                <div class="brand">PARCELMAN</div>
                <div class="kind">Outgoing Transfer Waybill</div>
            </div>
            <div>
                <div class="number">{{ $waybillNumber }}</div>
                <div class="printed">Printed {{ now()->format('d M Y, h:i A') }}</div>
            </div>
        </section>

        <section class="meta">
            <div>
                <div class="label">Route</div>
                <div class="value">{{ $manifest->originWarehouse?->name ?? 'Origin' }} to {{ $manifest->destinationWarehouse?->name ?? 'Destination' }}</div>
                <div class="sub">Manifest {{ $manifest->manifest_number }}</div>
            </div>
            <div>
                <div class="label">Rider</div>
                <div class="value">{{ $manifest->assignedDriver?->name ?? 'Not assigned' }}</div>
                <div class="sub">{{ collect([$manifest->assignedDriver?->phone, $manifest->assignedDriver?->vehicle_type, $manifest->assignedDriver?->vehicle_number])->filter()->implode(' / ') ?: 'No rider details' }}</div>
            </div>
        </section>

        <section class="counts">
            <span><b>Containers</b>{{ $containers->count() }}</span>
            <span><b>Lines</b>{{ $manifest->items->count() }}</span>
            <span><b>Qty</b>{{ $totalQty }}</span>
            <span><b>Dispatch</b>{{ $manifest->dispatched_at?->format('d M, h:i A') ?? 'Prepared' }}</span>
        </section>

        <h2>Container Contents</h2>
        @forelse($containers as $container)
            <section class="container-block">
                <div class="container-head">
                    <div class="container-title">{{ $container->container_code }}</div>
                    <div class="container-meta">
                        {{ str($container->container_type)->replace('_', ' ')->title() }}
                        / {{ $container->items->count() }} {{ $container->items->count() === 1 ? 'line' : 'lines' }}
                        / {{ (int) $container->items->sum('expected_quantity') }} qty
                    </div>
                </div>
                <p class="notes">Notes: {{ filled($container->notes) ? $container->notes : 'None' }}</p>
                <table>
                    <thead>
                        <tr>
                            <th>Tracking</th>
                            <th>Description</th>
                            <th>Recipient</th>
                            <th class="right">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($container->items as $containerItem)
                            @php $shipmentItem = $containerItem->shipmentItem; @endphp
                            <tr>
                                <td class="mono">{{ $shipmentItem?->tracking_code ?? '—' }}</td>
                                <td>{{ $shipmentItem?->description ?? 'Package' }}</td>
                                <td>
                                    {{ $shipmentItem?->delivery_recipient_name ?? '—' }}
                                    @if($shipmentItem?->delivery_recipient_phone)
                                        <div class="sub">{{ $shipmentItem->delivery_recipient_phone }}</div>
                                    @endif
                                </td>
                                <td class="right">{{ (int) $containerItem->expected_quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @empty
            <p class="sub">No containers.</p>
        @endforelse

        @if($looseLines->isNotEmpty())
            <section class="container-block">
                <div class="container-head">
                    <div class="container-title">Loose / Unassigned</div>
                    <div class="container-meta">{{ $looseLines->count() }} {{ $looseLines->count() === 1 ? 'line' : 'lines' }} / {{ (int) $looseLines->sum('expected_quantity') }} qty</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Tracking</th>
                            <th>Description</th>
                            <th>Recipient</th>
                            <th class="right">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($looseLines as $line)
                            @php $shipmentItem = $line->shipmentItem; @endphp
                            <tr>
                                <td class="mono">{{ $shipmentItem?->tracking_code ?? '—' }}</td>
                                <td>{{ $shipmentItem?->description ?? 'Package' }}</td>
                                <td>
                                    {{ $shipmentItem?->delivery_recipient_name ?? '—' }}
                                    @if($shipmentItem?->delivery_recipient_phone)
                                        <div class="sub">{{ $shipmentItem->delivery_recipient_phone }}</div>
                                    @endif
                                </td>
                                <td class="right">{{ (int) $line->expected_quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif

        <section class="signatures">
            <div class="sig">
                <div class="sig-title">Prepared By</div>
                <div class="sig-line">Name</div>
                <div class="sig-line">Signature</div>
                <div class="sig-line">Date</div>
            </div>
            <div class="sig">
                <div class="sig-title">Rider Received</div>
                <div class="sig-line">Name</div>
                <div class="sig-line">Signature</div>
                <div class="sig-line">Date</div>
            </div>
            <div class="sig">
                <div class="sig-title">Destination Received</div>
                <div class="sig-line">Name</div>
                <div class="sig-line">Signature</div>
                <div class="sig-line">Date</div>
            </div>
        </section>
    </main>
</body>
</html>
