<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\TransportManifest;
use App\Models\TransportManifestItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseTransportReceivingService
{
    public function scanReceive(
        TransportManifest $manifest,
        ShipmentItem $shipmentItem,
        Warehouse $warehouse,
        User $user,
        int $receivedQuantity,
        ?string $lineStatus = null,
        ?string $notes = null
    ): array {
        if ((int) $manifest->destination_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot receive items for another warehouse manifest.'];
        }

        if (!in_array($manifest->status, [TransportManifest::STATUS_ARRIVED, TransportManifest::STATUS_RECEIVED], true)) {
            return ['success' => false, 'message' => 'Manifest has not arrived for receiving.'];
        }

        if ($manifest->status === TransportManifest::STATUS_RECEIVED) {
            return ['success' => false, 'message' => 'Manifest already finalized as received.'];
        }

        if ($receivedQuantity < 0) {
            return ['success' => false, 'message' => 'Received quantity must be zero or greater.'];
        }

        return DB::transaction(function () use ($manifest, $shipmentItem, $warehouse, $user, $receivedQuantity, $lineStatus, $notes) {
            $line = TransportManifestItem::query()
                ->where('transport_manifest_id', $manifest->id)
                ->where('shipment_item_id', $shipmentItem->id)
                ->lockForUpdate()
                ->first();

            if (!$line) {
                return ['success' => false, 'message' => 'Item is not part of this manifest.'];
            }

            $resolvedStatus = $lineStatus ?: $this->resolveLineStatus((int) $line->expected_quantity, $receivedQuantity);

            $line->update([
                'received_quantity' => $receivedQuantity,
                'received_at' => now(),
                'scan_in_count' => ((int) $line->scan_in_count) + 1,
                'line_status' => $resolvedStatus,
                'notes' => $notes,
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

            if ($manifest->assignedDriver) {
                $manifest->assignedDriver->update(['status' => 'available']);
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

