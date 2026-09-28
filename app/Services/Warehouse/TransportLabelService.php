<?php

namespace App\Services\Warehouse;

use App\Models\ShipmentItem;
use Illuminate\Support\Collection;

/**
 * Parcel labels for a batch on its way out.
 *
 * Two jobs that have to happen together. Rendering the sheet is obvious; the
 * other is creating the `warehouse_receipt_item_labels` rows. Having a label on
 * every parcel is what marks the box as packed and sealed, and departure is
 * gated on that — the label rows are the record of it, so a batch printed at the
 * hub can be taken by a driver without anyone re-scanning the contents.
 *
 * The sheet is ONE page: the batch label, for the box, with the parcel barcodes
 * listed underneath. A batch travels as a single box, so a page per parcel gave
 * a three-page sheet for a two-parcel batch and two of those pages had nothing
 * to stick to.
 *
 * Shared by the admin Outgoing Batches screen and any other screen that labels a
 * batch, which may list different models (OutgoingBatch and TransportManifest)
 * but label the same thing: the shipment items riding on the batch.
 */
class TransportLabelService
{
    public function __construct(private readonly BarcodeService $barcodes) {}

    /**
     * Make sure each parcel has a scannable label, and describe it for the sheet.
     *
     * Idempotent: an item that already has a label is left alone, so printing
     * twice cannot duplicate a barcode or bump a count.
     *
     * @param  Collection<int, ShipmentItem>  $items
     * @return array{parcels: array<int, array<string, mixed>>, labels_created: int, labelled: int, origin: ?string}
     */
    public function prepare(Collection $items): array
    {
        if (method_exists($items, 'load')) {
            $items->load([
                'warehouseReceiptItems.labels',
                'warehouseReceiptItems.receipt.warehouse:id,name',
            ]);
        }

        $parcels = [];
        $labelsCreated = 0;
        $origin = null;

        foreach ($items as $item) {
            // The parcel's receipt says which warehouse it is leaving from.
            $receiptItem = $item->warehouseReceiptItems->first();
            $origin ??= $receiptItem?->receipt?->warehouse?->name;

            if (! $receiptItem) {
                // No receipt means no barcode and nothing for the scanner to
                // match. Reported rather than quietly skipped.
                $parcels[] = [
                    'tracking_code' => $item->tracking_code,
                    'barcode' => null,
                    'barcode_svg' => null,
                    'labelled' => false,
                    'note' => 'No warehouse receipt — receipt this parcel before it can be loaded',
                ];

                continue;
            }

            if ($receiptItem->labels->isEmpty()) {
                // Labels are numbered `{receipt barcode}-001`, which is the
                // convention the receiving flow already uses. The barcode on the
                // receipt item is the parent; the tracking code is the fallback
                // for an item receipted before barcoding existed.
                $parent = $receiptItem->barcode_value ?: $item->tracking_code;

                if (filled($parent)) {
                    $receiptItem->labels()->create([
                        'barcode_value' => $parent . '-001',
                        'label_index' => 1,
                        'labels_total' => 1,
                        'label_type' => 'sealed',
                        'printed_at' => now(),
                        'print_count' => 1,
                    ]);

                    $labelsCreated++;
                    $receiptItem->load('labels');
                }
            }

            $label = $receiptItem->labels->first();

            $parcels[] = [
                'tracking_code' => $item->tracking_code,
                'barcode' => $label?->barcode_value,
                'barcode_svg' => $label
                    ? $this->barcodes->renderCode128Svg($label->barcode_value, 60, 2, 8, true)
                    : null,
                'labelled' => $label !== null,
                'note' => $label ? null : 'Could not derive a barcode for this parcel',
            ];
        }

        return [
            'parcels' => $parcels,
            'labels_created' => $labelsCreated,
            'labelled' => count(array_filter($parcels, fn ($parcel) => $parcel['labelled'])),
            'origin' => $origin,
        ];
    }

    /** The master batch barcode, as SVG. */
    public function batchBarcode(string $code): string
    {
        return $this->barcodes->renderCode128Svg($code, 80, 2, 12, true);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function renderSheet(array $context): string
    {
        return view('shared.transport-labels', $context)->render();
    }

    /** Says what happened, including when part of it could not be done. */
    public function message(int $created, int $labelled, int $total): string
    {
        if ($total === 0) {
            return 'This batch has no parcels yet, so there is nothing to label.';
        }

        $parts = [];

        if ($created > 0) {
            $parts[] = "{$created} parcel label" . ($created === 1 ? '' : 's') . ' created';
        }

        $parts[] = "{$labelled} of {$total} parcels labelled";

        if ($labelled < $total) {
            $parts[] = 'the rest need receipting at the warehouse before a driver can load them';
        }

        return ucfirst(implode(', ', $parts)) . '.';
    }
}
