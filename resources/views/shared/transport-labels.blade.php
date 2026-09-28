@php
    /*
     * The box label a hub prints for a batch.
     *
     * ONE page, deliberately. A batch travels as a single box, so the label goes
     * on that box — printing a page per parcel produced a three-page sheet for a
     * two-parcel batch, and two of those pages had nothing to stick to.
     *
     * The barcode is the batch code, which is what the driver's scanner sees and
     * what loads the box as a whole. The parcel barcodes are listed underneath so
     * the contents of the box can be checked against the sheet without printing
     * them again — capped, because a label that overflows the page is no use on a
     * carton.
     *
     * Black on white with rules rather than fills, so it survives a thermal
     * printer and a photocopier.
     */
    $createdAt = optional($batch->created_at ?? null)->format('d M Y, H:i');
    $packagesTotal = count($parcels);
    $shortfall = $packagesTotal - $labelledCount;
    $barcodes = collect($parcels)->pluck('barcode')->filter()->values();
    $shown = $barcodes->take(8);
@endphp
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label {{ $code }}</title>
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
            overflow: hidden;
        }
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

        .contents {
            border-top: 0.3mm solid #94a3b8;
            padding-top: 2mm;
        }
        .contents-list {
            font-family: 'Menlo', 'Consolas', monospace;
            font-size: 2.4mm;
            line-height: 1.5;
            word-break: break-all;
        }

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

        @if ($barcodes->isNotEmpty())
            {{-- One label covers the box, so the contents are listed here instead
                 of being printed as pages of their own. --}}
            <div class="contents">
                <div class="fact-label" style="margin-bottom:1mm;">Parcel barcodes in this box</div>
                <div class="contents-list">
                    {{ $shown->implode(' · ') }}@if ($barcodes->count() > $shown->count())
                        · +{{ $barcodes->count() - $shown->count() }} more
                    @endif
                </div>
            </div>
        @endif

        <div class="note">
            @if ($packagesTotal === 0)
                This batch has no packages yet.
            @elseif ($shortfall > 0)
                {{ $shortfall }} of {{ $packagesTotal }} packages have no warehouse label yet.
                Receipt them at the warehouse before this box is despatched.
            @else
                This label covers the whole batch. Scan it to load the box.
            @endif
        </div>
    </div>

</body>
</html>
