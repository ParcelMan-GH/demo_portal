<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\TransportContainer;
use App\Models\TransportContainerItem;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\TransportManifestReceiptLabelScan;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\DriverWorkloadService;

class WarehouseTransportReceivingService
{
    public function __construct(private DriverWorkloadService $workloads) {}

    public function scanReceive(
        TransportManifest $manifest,
        ShipmentItem $shipmentItem,
        Warehouse $warehouse,
        User $user,
        int $receivedQuantity,
        ?string $lineStatus = null,
        ?string $notes = null,
        ?string $scannedLabelBarcode = null,
        ?string $description = null
    ): array {
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot receive items for another warehouse manifest.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_ARRIVED) {
            return ['success' => false, 'message' => 'Manifest has not arrived for receiving.'];
        }

        if ($receivedQuantity < 0) {
            return ['success' => false, 'message' => 'Received quantity must be zero or greater.'];
        }

        return DB::transaction(function () use ($manifest, $shipmentItem, $warehouse, $user, $receivedQuantity, $lineStatus, $notes, $scannedLabelBarcode, $description) {
            $line = TransportManifestItem::query()
                ->where('transport_manifest_id', $manifest->id)
                ->where('shipment_item_id', $shipmentItem->id)
                ->lockForUpdate()
                ->first();

            if (!$line) {
                return ['success' => false, 'message' => 'Item is not part of this manifest.'];
            }

            if ($scannedLabelBarcode) {
                $label = WarehouseReceiptItemLabel::query()
                    ->where('barcode_value', $scannedLabelBarcode)
                    ->whereHas('receiptItem', fn ($query) => $query->where('shipment_item_id', $shipmentItem->id))
                    ->lockForUpdate()
                    ->first();

                if (!$label) {
                    return ['success' => false, 'message' => 'Scanned label does not belong to this package line.'];
                }

                $alreadyScanned = TransportManifestReceiptLabelScan::query()
                    ->where('transport_manifest_id', $manifest->id)
                    ->where('warehouse_receipt_item_label_id', $label->id)
                    ->exists();

                if ($alreadyScanned) {
                    return ['success' => false, 'message' => 'This label has already been received.'];
                }

                TransportManifestReceiptLabelScan::query()->create([
                    'transport_manifest_id' => $manifest->id,
                    'transport_manifest_item_id' => $line->id,
                    'warehouse_receipt_item_label_id' => $label->id,
                    'scanned_by_user_id' => $user->id,
                    'barcode_value' => $label->barcode_value,
                    'scanned_at' => now(),
                ]);
            }

            $quantityStatus = $this->resolveLineStatus((int) $line->expected_quantity, $receivedQuantity);
            $resolvedStatus = $lineStatus ?: $quantityStatus;

            if ($lineStatus !== TransportManifestItem::LINE_DAMAGED && $quantityStatus !== TransportManifestItem::LINE_RECEIVED) {
                $resolvedStatus = $quantityStatus;
            }

            if (
                in_array($resolvedStatus, [
                    TransportManifestItem::LINE_SHORT,
                    TransportManifestItem::LINE_EXCESS,
                    TransportManifestItem::LINE_DAMAGED,
                ], true)
                && blank($notes)
            ) {
                return ['success' => false, 'message' => 'Add discrepancy notes before saving this receiving line.'];
            }

            if ($description !== null) {
                $shipmentItem->update([
                    'description' => filled($description) ? trim($description) : null,
                ]);
            }

            $line->update([
                'received_quantity' => $receivedQuantity,
                'received_at' => now(),
                'scan_in_count' => ((int) $line->scan_in_count) + 1,
                'line_status' => $resolvedStatus,
                'notes' => $notes,
            ]);

            TransportContainerItem::query()
                ->where('transport_manifest_item_id', $line->id)
                ->update([
                    'received_quantity' => $receivedQuantity,
                    'status' => match ($resolvedStatus) {
                        TransportManifestItem::LINE_RECEIVED => TransportContainerItem::STATUS_RECEIVED,
                        TransportManifestItem::LINE_DAMAGED => TransportContainerItem::STATUS_DAMAGED,
                        TransportManifestItem::LINE_EXCESS => TransportContainerItem::STATUS_EXTRA,
                        default => TransportContainerItem::STATUS_MISSING,
                    },
                ]);

            return [
                'success' => true,
                'message' => 'Manifest line received.',
                'data' => [
                    'line' => $line->fresh('shipmentItem'),
                ],
            ];
        });
    }

    public function finalizeReceipt(TransportManifest $manifest, Warehouse $warehouse, User $user, ?string $notes = null): array
    {
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot finalize another warehouse manifest receipt.'];
        }

        if ($manifest->status !== TransportManifest::STATUS_ARRIVED) {
            return ['success' => false, 'message' => 'Only arrived manifests can be finalized.'];
        }

        return DB::transaction(function () use ($manifest, $warehouse, $user, $notes) {
            $manifest = TransportManifest::query()
                ->with(['items.shipmentItem.shipment', 'assignedDriver'])
                ->lockForUpdate()
                ->findOrFail($manifest->id);

            $unscannedCount = $manifest->items->filter(
                fn (TransportManifestItem $line) => is_null($line->received_at)
            )->count();

            if ($unscannedCount > 0) {
                return ['success' => false, 'message' => 'All manifest lines must be scanned before finalizing.'];
            }

            $manifest->update([
                'status' => TransportManifest::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by_user_id' => $user->id,
                'notes' => $notes ?: $manifest->notes,
            ]);

            $manifest->containers()->update([
                'status' => TransportContainer::STATUS_RECEIVED,
                'received_at' => now(),
                'received_by_user_id' => $user->id,
            ]);

            $now = now();
            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();

            foreach ($manifest->items as $line) {
                $item = $line->shipmentItem;
                if (!$item) {
                    continue;
                }

                $item->update(['status' => ItemStatus::AT_DESTINATION]);

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::AT_DESTINATION->value,
                    'location' => $warehouse->name,
                    'notes' => 'Item received at destination warehouse from manifest ' . $manifest->manifest_number . '.',
                    'meta' => [
                        'transport_manifest_id' => $manifest->id,
                        'transport_manifest_number' => $manifest->manifest_number,
                        'expected_quantity' => (int) $line->expected_quantity,
                        'loaded_quantity' => (int) $line->loaded_quantity,
                        'received_quantity' => (int) $line->received_quantity,
                        'line_status' => $line->line_status,
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => $now,
                ]);

                if ($item->shipment) {
                    $shipments->push($item->shipment);
                }
            }

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentAtDestinationStatus($shipment);
            });

            // Auto-create WarehouseReceipt + items so they appear in the sorting system
            $this->createWarehouseReceiptFromManifest($manifest, $warehouse, $user);

            if ($manifest->assignedDriver) {
                $this->workloads->syncStatus($manifest->assignedDriver);
            }

            return [
                'success' => true,
                'message' => 'Destination manifest receipt finalized successfully.',
                'data' => [
                    'manifest' => $manifest->fresh([
                        'items.shipmentItem.shipment',
                        'originWarehouse',
                        'destinationWarehouse',
                        'assignedDriver',
                    ]),
                ],
            ];
        });
    }

    private function createWarehouseReceiptFromManifest(TransportManifest $manifest, Warehouse $warehouse, User $user): void
    {
        $now = now();

        $receipt = WarehouseReceipt::query()->create([
            'transport_manifest_id' => $manifest->id,
            'warehouse_id' => $warehouse->id,
            'status' => WarehouseReceipt::STATUS_FINALIZED,
            'started_by_user_id' => $user->id,
            'finalized_by_user_id' => $user->id,
            'notes' => 'Auto-created from transport manifest ' . $manifest->manifest_number . '.',
            'started_at' => $now,
            'finalized_at' => $now,
        ]);

        foreach ($manifest->items as $line) {
            if (!$line->shipmentItem) {
                continue;
            }

            $received = (int) $line->received_quantity;
            $expected = (int) $line->expected_quantity;
            $discrepancy = 'none';

            if ($received < $expected) {
                $discrepancy = 'missing';
            } elseif ($received > $expected) {
                $discrepancy = 'excess';
            }

            if ($line->line_status === TransportManifestItem::LINE_DAMAGED) {
                $discrepancy = $discrepancy === 'none' ? 'damaged' : 'mixed';
            }

            WarehouseReceiptItem::query()->create([
                'warehouse_receipt_id' => $receipt->id,
                'shipment_item_id' => $line->shipment_item_id,
                'expected_quantity' => $expected,
                'received_quantity' => $received,
                'damaged_quantity' => $line->line_status === TransportManifestItem::LINE_DAMAGED ? $received : 0,
                'discrepancy_type' => $discrepancy,
                'condition_status' => $line->line_status === TransportManifestItem::LINE_DAMAGED ? 'damaged' : 'ok',
                'notes' => $line->notes,
                'received_by_user_id' => $user->id,
                'received_at' => $line->received_at ?? $now,
            ]);
        }
    }

    private function resolveLineStatus(int $expected, int $received): string
    {
        if ($received === $expected) {
            return TransportManifestItem::LINE_RECEIVED;
        }

        if ($received < $expected) {
            return TransportManifestItem::LINE_SHORT;
        }

        return TransportManifestItem::LINE_EXCESS;
    }

    private function syncShipmentAtDestinationStatus(Shipment $shipment): void
    {
        $allAtDestinationOrBeyond = !$shipment->items()
            ->whereNotIn('status', [
                ItemStatus::AT_DESTINATION->value,
                ItemStatus::OUT_FOR_DELIVERY->value,
                ItemStatus::DELIVERED->value,
                ItemStatus::RETURNED->value,
            ])
            ->exists();

        if ($allAtDestinationOrBeyond && $shipment->status !== ShipmentStatus::AT_DESTINATION) {
            $shipment->update(['status' => ShipmentStatus::AT_DESTINATION]);
        }
    }
}
