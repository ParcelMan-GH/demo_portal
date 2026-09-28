@php
    /*
     * The printable sheet a hub prints from the Outgoing Batches table.
     *
     * Deliberately one label per page at a fixed 100mm x 150mm, matching how the
     * container label already prints. Printing the whole batch as a grid would
     * need the staff to match labels to parcels by hand; one-per-page means a
     * label can be peeled and stuck without counting.
     *
     * Everything is black on white and drawn with rules rather than fills, so it
     * survives a thermal printer and a photocopier.
     */
    $createdAt = optional($batch->created_at ?? null)->format('d M Y, H:i');
    $packagesTotal = count($parcels);
    $shortfall = $packagesTotal - $labelledCount;
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Batch Labels - {{ $code }}</title>
    <style>
        @page { size: 100mm 150mm; margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #fff;
            color: #020617;
            font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .sheet {
            width: 100mm;
            height: 150mm;
            padding: 6mm;
            display: flex;
            flex-direction: column;
            gap: 3mm;
            page-break-after: always;
            overflow: hidden;
        }
        .sheet:last-child { page-break-after: auto; }

        .head {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            border-bottom: 0.6mm solid #020617;
            padding-bottom: 2mm;
        }
        .brand { font-size: 4.2mm; font-weight: 900; letter-spacing: 0.4mm; }
        .kind { font-size: 2.6mm; font-weight: 800; letter-spacing: 0.6mm; color: #334155; }

        .code {
            font-family: 'Menlo', 'Consolas', monospace;
            font-size: 7.4mm;
            font-weight: 800;
            letter-spacing: 0.2mm;
            text-align: center;
            word-break: break-all;
        }
        .code--parcel { font-size: 5.4mm; }

        .barcode { display: flex; justify-content: center; padding: 1mm 0; }
        .barcode svg { max-width: 100%; height: auto; }

        .route {
            border: 0.4mm solid #020617;
            border-radius: 1.5mm;
            padding: 3mm;
        }
        .route-row { display: flex; align-items: baseline; gap: 2mm; }
        .route-row + .route-row { margin-top: 2mm; }
        .route-arrow { font-size: 3.4mm; font-weight: 900; }
        .route-name { font-size: 3.6mm; font-weight: 800; }

        .facts { display: grid; grid-template-columns: 1fr 1fr; gap: 2mm 3mm; }
        .fact-label { font-size: 2.2mm; font-weight: 800; letter-spacing: 0.4mm; color: #475569; text-transform: uppercase; }
        .fact-value { font-size: 3.2mm; font-weight: 800; }

        .note {
            margin-top: auto;
            border-top: 0.3mm dashed #64748b;
            padding-top: 2mm;
            font-size: 2.4mm;
            font-weight: 700;
            color: #334155;
        }
    </style>
</head>
<body>

    {{-- Master batch label --}}
    <div class="sheet">
        <div class="head">
            <span class="brand">PARCELMAN EXPRESS</span>
            <span class="kind">BATCH LABEL</span>
        </div>

        <div class="code">{{ $code }}</div>

        <div class="barcode">{!! $batchBarcode !!}</div>

        <div class="route">
            <div class="route-row">
                <span class="fact-label">From</span>
                <span class="route-name">{{ $origin ?? 'Not recorded' }}</span>
            </div>
            <div class="route-row">
                <span class="route-arrow">&darr;</span>
                <span class="route-name">{{ $destination }}</span>
            </div>
        </div>

        <div class="facts">
            <div>
                <div class="fact-label">Packages</div>
                <div class="fact-value">{{ $packagesTotal }}</div>
            </div>
            <div>
                <div class="fact-label">Status</div>
                <div class="fact-value">{{ ucfirst(str_replace('_', ' ', $batch->status ?? '')) }}</div>
            </div>
            <div>
                <div class="fact-label">Created</div>
                <div class="fact-value">{{ $createdAt }}</div>
            </div>
            <div>
                <div class="fact-label">Labelled</div>
                <div class="fact-value">{{ $labelledCount }} / {{ $packagesTotal }}</div>
            </div>
        </div>

        <div class="note">
            @if ($shortfall > 0)
                {{ $shortfall }} of {{ $packagesTotal }} packages have no warehouse label yet, so no
                parcel label is printed for them. They must be receipted at the warehouse before a
                driver can load this batch.
            @else
                Every package in this batch carries a scannable label.
            @endif
        </div>
    </div>

    {{-- One label per parcel --}}
    @forelse ($parcels as $index => $parcel)
        <div class="sheet">
            <div class="head">
                <span class="brand">PARCELMAN EXPRESS</span>
                <span class="kind">PARCEL {{ $index + 1 }} / {{ $packagesTotal }}</span>
            </div>

            @if ($parcel['barcode'])
                <div class="code code--parcel">{{ $parcel['barcode'] }}</div>
                <div class="barcode">{!! $parcel['barcode_svg'] !!}</div>
            @else
                <div class="code code--parcel">{{ $parcel['tracking_code'] ?? '—' }}</div>
                <div class="note" style="margin-top:0; border-top:0;">
                    {{ $parcel['note'] ?? 'No printable label for this parcel.' }}
                </div>
            @endif

            <div class="facts">
                <div>
                    <div class="fact-label">Tracking</div>
                    <div class="fact-value">{{ $parcel['tracking_code'] ?? '—' }}</div>
                </div>
                <div>
                    <div class="fact-label">Batch</div>
                    <div class="fact-value">{{ $code }}</div>
                </div>
                <div>
                    <div class="fact-label">Destination</div>
                    <div class="fact-value">{{ $destination }}</div>
                </div>
                <div>
                    <div class="fact-label">Origin</div>
                    <div class="fact-value">{{ $origin ?? 'Not recorded' }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="sheet">
            <div class="head">
                <span class="brand">PARCELMAN EXPRESS</span>
                <span class="kind">NO PARCELS</span>
            </div>
            <div class="note" style="margin-top:auto; border-top:0;">
                This batch has no packages yet, so there are no parcel labels to print. Add packages
                to the batch first.
            </div>
        </div>
    @endforelse

</body>
</html>
