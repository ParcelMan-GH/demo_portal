<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemPhoto;
use App\Services\PickupAssignmentService;
use App\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class WarehouseReceivingService
{
    public function __construct(
        private StorageService $storageService,
        private BarcodeService $barcodeService,
        private PickupAssignmentService $pickupAssignmentService
    ) {
    }

    public function getOrCreateReceipt(PickupAssignment $assignment, Warehouse $warehouse, User $user): WarehouseReceipt
    {
        $receipt = WarehouseReceipt::query()->firstOrCreate(
            ['pickup_assignment_id' => $assignment->id],
            [
                'warehouse_id' => $warehouse->id,
                'status' => WarehouseReceipt::STATUS_DRAFT,
                'started_by_user_id' => $user->id,
                'started_at' => now(),
            ]
        );

        if ((int) $receipt->warehouse_id !== (int) $warehouse->id) {
            abort(422, 'This receipt belongs to a different warehouse.');
        }

        return $receipt;
    }

    /**
     * @param array<int, UploadedFile> $photos
     * @param array<int, int|string> $removePhotoIds
     * @return array{success:bool,message:string,data?:array<string,mixed>}
     */
    public function upsertReceiptItem(
        PickupAssignment $assignment,
        ShipmentItem $shipmentItem,
        Warehouse $warehouse,
        User $user,
        int $receivedQuantity,
        int $damagedQuantity = 0,
        ?string $conditionStatus = null,
        ?string $notes = null,
        array $photos = [],
        array $removePhotoIds = []
    ): array {
        if ($shipmentItem->shipment_id !== $assignment->shipment_id) {
            return [
                'success' => false,
                'message' => 'Shipment item does not belong to this pickup assignment.',
            ];
        }

        if ($receivedQuantity < 0 || $damagedQuantity < 0) {
            return [
                'success' => false,
                'message' => 'Quantities must be zero or greater.',
            ];
        }

        $allowedConditions = ['ok', 'damaged', 'partial'];
        if ($conditionStatus && !in_array($conditionStatus, $allowedConditions, true)) {
            return [
                'success' => false,
                'message' => 'Invalid condition status.',
            ];
        }

        return DB::transaction(function () use (
            $assignment,
            $shipmentItem,
            $warehouse,
            $user,
            $receivedQuantity,
            $damagedQuantity,
            $conditionStatus,
            $notes,
            $photos,
            $removePhotoIds
        ) {
            $lockedAssignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse)) {
                return $error;
            }

            $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);
            $receipt = WarehouseReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if ($receipt->isFinalized()) {
                return [
                    'success' => false,
                    'message' => 'This receipt is already finalized.',
                ];
            }

            $expectedQuantity = $this->resolveExpectedQuantity($lockedAssignment, $shipmentItem);
            $effectiveConditionStatus = $conditionStatus ?: $this->deriveConditionStatus(
                $expectedQuantity,
                $receivedQuantity,
                $damagedQuantity
            );

            $discrepancyType = $this->determineDiscrepancyType(
                $expectedQuantity,
                $receivedQuantity,
                $damagedQuantity
            );

            $receiptItem = WarehouseReceiptItem::query()->updateOrCreate(
                [
                    'warehouse_receipt_id' => $receipt->id,
                    'shipment_item_id' => $shipmentItem->id,
                ],
                [
                    'expected_quantity' => $expectedQuantity,
                    'received_quantity' => $receivedQuantity,
                    'damaged_quantity' => $damagedQuantity,
                    'discrepancy_type' => $discrepancyType,
                    'condition_status' => $effectiveConditionStatus,
                    'notes' => $notes,
                    'received_by_user_id' => $user->id,
                    'received_at' => now(),
                    'barcode_value' => $shipmentItem->tracking_code,
                    'barcode_format' => 'code128',
                ]
            );

            $removeIds = collect($removePhotoIds)
                ->map(fn ($value) => (int) $value)
                ->filter(fn ($value) => $value > 0)
                ->unique()
                ->values();

            if ($removeIds->isNotEmpty()) {
                WarehouseReceiptItemPhoto::query()
                    ->where('warehouse_receipt_item_id', $receiptItem->id)
                    ->whereIn('id', $removeIds->all())
                    ->get()
                    ->each(function (WarehouseReceiptItemPhoto $photo) {
                        $this->storageService->delete($photo->path);
                        $photo->delete();
                    });
            }

            foreach ($photos as $photo) {
                if (!$photo instanceof UploadedFile) {
                    continue;
                }

                $upload = $this->storageService->upload(
                    $photo,
                    "warehouse/receipts/{$receipt->id}/items/{$shipmentItem->id}"
                );

                WarehouseReceiptItemPhoto::query()->create([
                    'warehouse_receipt_item_id' => $receiptItem->id,
                    'path' => $upload['path'],
                    'original_name' => $upload['original_name'],
                    'size' => $upload['size'],
                    'photo_type' => $effectiveConditionStatus === 'damaged' ? 'damage' : 'condition',
                    'created_by_user_id' => $user->id,
                ]);
            }

            $this->refreshReceiptStatus($receipt);

            $loadedItem = $receiptItem->fresh(['photos', 'shipmentItem.shipment']);

            return [
                'success' => true,
                'message' => 'Receipt item saved successfully.',
                'data' => [
                    'receipt' => $receipt->fresh(['items.photos']),
                    'item' => $this->serializeReceiptItem($loadedItem),
                ],
            ];
        });
    }

    public function printItemLabel(
        PickupAssignment $assignment,
        ShipmentItem $shipmentItem,
        Warehouse $warehouse,
        User $user
    ): array {
        if ($shipmentItem->shipment_id !== $assignment->shipment_id) {
            return [
                'success' => false,
                'message' => 'Shipment item does not belong to this pickup assignment.',
            ];
        }

        return DB::transaction(function () use ($assignment, $shipmentItem, $warehouse, $user) {
            $lockedAssignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse)) {
                return $error;
            }

            $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);

            $receiptItem = WarehouseReceiptItem::query()
                ->where('warehouse_receipt_id', $receipt->id)
                ->where('shipment_item_id', $shipmentItem->id)
                ->lockForUpdate()
                ->first();

            if (!$receiptItem) {
                return [
                    'success' => false,
                    'message' => 'Save this item first before printing barcode label.',
                ];
            }

            if (empty($shipmentItem->tracking_code)) {
                $shipmentItem->update([
                    'tracking_code' => ShipmentItem::generateTrackingCode(),
                ]);
                $shipmentItem->refresh();
            }

            $barcodeValue = $shipmentItem->tracking_code;
            $barcodeSvg = $this->barcodeService->renderCode128Svg($barcodeValue);

            $receiptItem->update([
                'barcode_value' => $barcodeValue,
                'barcode_format' => 'code128',
                'barcode_printed_at' => now(),
                'barcode_print_count' => (int) $receiptItem->barcode_print_count + 1,
            ]);

            $shipment = $lockedAssignment->shipment()->with([
                'vendor:id,name,business_name',
                'deliveryRegion:id,name',
                'deliveryDistrict:id,name',
            ])->first();

            $labelHtml = View::make('warehouse.receipts.partials.item-label', [
                'assignment' => $lockedAssignment,
                'shipment' => $shipment,
                'shipmentItem' => $shipmentItem,
                'receiptItem' => $receiptItem->fresh(),
                'barcodeSvg' => $barcodeSvg,
            ])->render();

            return [
                'success' => true,
                'message' => 'Barcode label generated.',
                'data' => [
                    'barcode_value' => $barcodeValue,
                    'barcode_format' => 'code128',
                    'print_count' => (int) $receiptItem->fresh()->barcode_print_count,
                    'label_html' => $labelHtml,
                ],
            ];
        });
    }

    public function finalizeReceipt(
        PickupAssignment $assignment,
        Warehouse $warehouse,
        User $user,
        ?string $notes = null,
        ?string $approvalReason = null
    ): array {
        return DB::transaction(function () use ($assignment, $warehouse, $user, $notes, $approvalReason) {
            $lockedAssignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse)) {
                return $error;
            }

            $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);
            $receipt = WarehouseReceipt::query()->with(['items'])->lockForUpdate()->findOrFail($receipt->id);

            if ($receipt->isFinalized()) {
                return [
                    'success' => false,
                    'message' => 'Receipt is already finalized.',
                ];
            }

            $shipmentItemIds = $lockedAssignment->shipment()->firstOrFail()
                ->items()
                ->pluck('id');

            $missingRows = $shipmentItemIds->diff($receipt->items->pluck('shipment_item_id'));
            if ($missingRows->isNotEmpty()) {
                return [
                    'success' => false,
                    'message' => 'All shipment items must be received or marked before finalization.',
                ];
            }

            $hasDiscrepancies = $receipt->items->contains(
                fn (WarehouseReceiptItem $item) => $item->discrepancy_type !== 'none'
            );

            if ($hasDiscrepancies) {
                if (!$user->hasPermission('warehouse.receiving.approve_discrepancy')) {
                    return [
                        'success' => false,
                        'message' => 'Only warehouse manager/super admin can finalize discrepancy receipts.',
                    ];
                }

                if (blank($approvalReason)) {
                    return [
                        'success' => false,
                        'message' => 'Approval reason is required for discrepancy finalization.',
                    ];
                }
            }

            $trackingMetaByItem = $receipt->items->mapWithKeys(function (WarehouseReceiptItem $item) use ($receipt) {
                return [
                    $item->shipment_item_id => [
                        'warehouse_receipt_id' => $receipt->id,
                        'warehouse_receipt_item_id' => $item->id,
                        'expected_quantity' => (int) $item->expected_quantity,
                        'received_quantity' => (int) $item->received_quantity,
                        'damaged_quantity' => (int) $item->damaged_quantity,
                        'discrepancy_type' => $item->discrepancy_type,
                        'condition_status' => $item->condition_status,
                    ],
                ];
            })->all();

            $receiveResult = $this->pickupAssignmentService->receiveAtWarehouse(
                assignment: $lockedAssignment,
                receivedByUserId: $user->id,
                receivedWarehouseId: $warehouse->id,
                receiveNotes: $notes,
                trackingMetaByItem: $trackingMetaByItem
            );

            if (!$receiveResult['success']) {
                return $receiveResult;
            }

            $receipt->update([
                'status' => WarehouseReceipt::STATUS_FINALIZED,
                'notes' => $notes,
                'finalized_by_user_id' => $user->id,
                'approved_by_user_id' => $hasDiscrepancies ? $user->id : null,
                'approval_reason' => $hasDiscrepancies ? $approvalReason : null,
                'finalized_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Receipt finalized successfully.',
                'data' => [
                    'receipt' => $receipt->fresh(['items.photos']),
                    'assignment' => $receiveResult['data']['assignment'] ?? null,
                ],
            ];
        });
    }

    public function serializeReceiptItem(WarehouseReceiptItem $item): array
    {
        $item->loadMissing(['photos', 'shipmentItem']);

        return [
            'id' => $item->id,
            'shipment_item_id' => $item->shipment_item_id,
            'expected_quantity' => (int) $item->expected_quantity,
            'received_quantity' => (int) $item->received_quantity,
            'damaged_quantity' => (int) $item->damaged_quantity,
            'discrepancy_type' => $item->discrepancy_type,
            'condition_status' => $item->condition_status,
            'notes' => $item->notes,
            'barcode_value' => $item->barcode_value,
            'barcode_format' => $item->barcode_format,
            'barcode_printed_at' => optional($item->barcode_printed_at)?->toIso8601String(),
            'barcode_print_count' => (int) $item->barcode_print_count,
            'received_at' => optional($item->received_at)?->toIso8601String(),
            'photos' => $item->photos->map(function (WarehouseReceiptItemPhoto $photo) {
                return [
                    'id' => $photo->id,
                    'photo_type' => $photo->photo_type,
                    'path' => $photo->path,
                    'url' => $this->storageService->getUrl($photo->path),
                    'original_name' => $photo->original_name,
                    'size' => $photo->size,
                    'created_at' => optional($photo->created_at)?->toIso8601String(),
                ];
            })->values(),
        ];
    }

    private function resolveExpectedQuantity(PickupAssignment $assignment, ShipmentItem $shipmentItem): int
    {
        $confirmation = $assignment->relationLoaded('itemConfirmations')
            ? $assignment->itemConfirmations->firstWhere('shipment_item_id', $shipmentItem->id)
            : PickupItemConfirmation::query()
                ->where('pickup_assignment_id', $assignment->id)
                ->where('shipment_item_id', $shipmentItem->id)
                ->first();

        if ($confirmation) {
            return (int) $confirmation->confirmed_quantity;
        }

        return (int) $shipmentItem->quantity;
    }

    private function deriveConditionStatus(int $expected, int $received, int $damaged): string
    {
        if ($damaged > 0 && $received === 0) {
            return 'damaged';
        }

        if ($damaged > 0) {
            return 'partial';
        }

        if ($received !== $expected) {
            return 'partial';
        }

        return 'ok';
    }

    private function determineDiscrepancyType(int $expected, int $received, int $damaged): string
    {
        $totalObserved = $received + $damaged;
        $hasMissing = $totalObserved < $expected;
        $hasExcess = $totalObserved > $expected;
        $hasDamaged = $damaged > 0;

        if (!$hasMissing && !$hasExcess && !$hasDamaged) {
            return 'none';
        }

        if ($hasMissing && !$hasDamaged && !$hasExcess) {
            return 'missing';
        }

        if ($hasExcess && !$hasDamaged && !$hasMissing) {
            return 'excess';
        }

        if ($hasDamaged && !$hasMissing && !$hasExcess) {
            return 'damaged';
        }

        return 'mixed';
    }

    private function refreshReceiptStatus(WarehouseReceipt $receipt): void
    {
        if ($receipt->isFinalized()) {
            return;
        }

        $hasDiscrepancies = WarehouseReceiptItem::query()
            ->where('warehouse_receipt_id', $receipt->id)
            ->where('discrepancy_type', '!=', 'none')
            ->exists();

        $receipt->update([
            'status' => $hasDiscrepancies
                ? WarehouseReceipt::STATUS_DISCREPANCY_OPEN
                : WarehouseReceipt::STATUS_DRAFT,
        ]);
    }

    /**
     * @return Collection<int, array<string,mixed>>
     */
    public function serializeReceiptItems(Collection $items): Collection
    {
        return $items->map(fn (WarehouseReceiptItem $item) => $this->serializeReceiptItem($item));
    }

    private function validateReceivingPreconditions(PickupAssignment $assignment, Warehouse $warehouse): ?array
    {
        $status = $assignment->status?->value ?? $assignment->getRawOriginal('status');

        if ($status === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Cancelled pickup assignments cannot be received.',
            ];
        }

        if (is_null($assignment->picked_up_at)) {
            return [
                'success' => false,
                'message' => 'Pickup must be finalized before warehouse receiving.',
            ];
        }

        if (!is_null($assignment->received_at)) {
            return [
                'success' => false,
                'message' => 'This pickup has already been received at warehouse.',
            ];
        }

        if (!is_null($assignment->target_warehouse_id) && (int) $assignment->target_warehouse_id !== (int) $warehouse->id) {
            return [
                'success' => false,
                'message' => 'This pickup is assigned to a different warehouse.',
            ];
        }

        return null;
    }
}
