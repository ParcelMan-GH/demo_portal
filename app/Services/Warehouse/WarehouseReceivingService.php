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
use App\Services\Warehouse\PackageContactService;
use App\Services\StorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        $defaults = [
            'warehouse_id' => $warehouse->id,
            'status' => WarehouseReceipt::STATUS_DRAFT,
        ];

        if (Schema::hasColumn('warehouse_receipts', 'shipment_id')) {
            $defaults['shipment_id'] = $assignment->shipment_id;
        }

        if (Schema::hasColumn('warehouse_receipts', 'started_by_user_id')) {
            $defaults['started_by_user_id'] = $user->id;
        }

        if (Schema::hasColumn('warehouse_receipts', 'started_at')) {
            $defaults['started_at'] = now();
        }

        $receipt = WarehouseReceipt::query()->firstOrCreate(
            ['pickup_assignment_id' => $assignment->id],
            $defaults
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
        array $removePhotoIds = [],
        bool $allowAfterFinalization = false,
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
            $removePhotoIds,
            $allowAfterFinalization
        ) {
            $lockedAssignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse, $allowAfterFinalization)) {
                return $error;
            }

            $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);
            $receipt = WarehouseReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

            if ($receipt->isFinalized() && ! $allowAfterFinalization) {
                return [
                    'success' => false,
                    'message' => 'This receipt is already finalized.',
                ];
            }

            if ($receipt->isFinalized() && $allowAfterFinalization) {
                $receipt->update([
                    'status' => WarehouseReceipt::STATUS_DRAFT,
                    'finalized_by_user_id' => null,
                    'approved_by_user_id' => null,
                    'approval_reason' => null,
                    'finalized_at' => null,
                ]);
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

            $shipmentItem = $this->ensureShipmentItemTrackingCode($shipmentItem);

            $receiptItemValues = [
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
            ];

            $receiptItemValues = collect($receiptItemValues)
                ->filter(fn ($value, $column) => Schema::hasColumn('warehouse_receipt_items', $column))
                ->all();

            $receiptItem = WarehouseReceiptItem::query()->updateOrCreate(
                [
                    'warehouse_receipt_id' => $receipt->id,
                    'shipment_item_id' => $shipmentItem->id,
                ],
                $receiptItemValues
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

            $this->refreshReceiptStatus($receipt, true);

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

            // Find existing receipt — label reprinting is allowed even after finalization
            $receipt = WarehouseReceipt::query()
                ->where('pickup_assignment_id', $lockedAssignment->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            if (!$receipt) {
                // Only validate preconditions when creating a new receipt (first-time label)
                if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse)) {
                    return $error;
                }
                $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);
            }

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

            $shipmentItem = $this->ensureShipmentItemTrackingCode($shipmentItem);

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
                'pickupRegion:id,name',
                'pickupDistrict:id,name',
                'deliveryRegion:id,name',
                'deliveryDistrict:id,name',
            ])->first();

            $shipmentItem->load(['deliveryRegion:id,name', 'deliveryDistrict:id,name']);

            $labelCard = View::make('warehouse.receipts.partials.item-label', [
                'assignment' => $lockedAssignment,
                'shipment' => $shipment,
                'shipmentItem' => $shipmentItem,
                'receiptItem' => $receiptItem->fresh(),
                'barcodeSvg' => $barcodeSvg,
                'labelBarcode' => $barcodeValue,
            ])->render();

            $labelHtml = $this->buildLabelPageHtml($labelCard, $barcodeValue);

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

    public function generateLabels(
        PickupAssignment $assignment,
        ShipmentItem $shipmentItem,
        Warehouse $warehouse,
        User $user,
        int $labelCount = 1,
        string $labelType = 'sealed'
    ): array {
        if ($shipmentItem->shipment_id !== $assignment->shipment_id) {
            return ['success' => false, 'message' => 'Item does not belong to this pickup.'];
        }

        if ($labelCount < 1 || $labelCount > 500) {
            return ['success' => false, 'message' => 'Label count must be between 1 and 500.'];
        }

        return DB::transaction(function () use ($assignment, $shipmentItem, $warehouse, $user, $labelCount, $labelType) {
            $lockedAssignment = PickupAssignment::query()->lockForUpdate()->findOrFail($assignment->id);

            $receipt = WarehouseReceipt::query()
                ->where('pickup_assignment_id', $lockedAssignment->id)
                ->where('warehouse_id', $warehouse->id)
                ->first();

            if (!$receipt) {
                if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse)) {
                    return $error;
                }
                $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);
            }

            $receiptItem = WarehouseReceiptItem::query()
                ->where('warehouse_receipt_id', $receipt->id)
                ->where('shipment_item_id', $shipmentItem->id)
                ->lockForUpdate()
                ->first();

            if (!$receiptItem) {
                return ['success' => false, 'message' => 'Save this package first before printing labels.'];
            }

            $shipmentItem = $this->ensureShipmentItemTrackingCode($shipmentItem);

            $parentBarcode = $shipmentItem->tracking_code;

            // Update parent receipt item barcode
            $receiptItem->update([
                'barcode_value' => $parentBarcode,
                'barcode_format' => 'code128',
                'barcode_printed_at' => now(),
                'barcode_print_count' => (int) $receiptItem->barcode_print_count + 1,
            ]);

            // Delete existing labels and regenerate
            $receiptItem->labels()->delete();

            $labels = [];
            for ($i = 1; $i <= $labelCount; $i++) {
                $barcodeValue = $parentBarcode . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);

                $label = $receiptItem->labels()->create([
                    'barcode_value' => $barcodeValue,
                    'label_index' => $i,
                    'labels_total' => $labelCount,
                    'label_type' => $labelType,
                    'printed_at' => now(),
                    'print_count' => 1,
                ]);
                $labels[] = $label;
            }

            // Generate HTML for all labels
            $shipment = $lockedAssignment->shipment()->with([
                'vendor:id,name,business_name',
                'pickupRegion:id,name',
                'pickupDistrict:id,name',
                'deliveryRegion:id,name',
                'deliveryDistrict:id,name',
            ])->first();

            $shipmentItem->load(['deliveryRegion:id,name', 'deliveryDistrict:id,name']);

            $labelCards = '';
            foreach ($labels as $label) {
                $barcodeSvg = $this->barcodeService->renderCode128Svg($label->barcode_value);
                $labelCards .= View::make('warehouse.receipts.partials.item-label', [
                    'assignment' => $lockedAssignment,
                    'shipment' => $shipment,
                    'shipmentItem' => $shipmentItem,
                    'receiptItem' => $receiptItem,
                    'barcodeSvg' => $barcodeSvg,
                    'labelIndex' => $label->label_index,
                    'labelsTotal' => $label->labels_total,
                    'labelBarcode' => $label->barcode_value,
                ])->render();
            }

            $labelsHtml = $this->buildLabelPageHtml($labelCards, $parentBarcode);

            return [
                'success' => true,
                'message' => $labelCount . ' label(s) generated.',
                'data' => [
                    'barcode_value' => $parentBarcode,
                    'label_count' => $labelCount,
                    'label_type' => $labelType,
                    'print_count' => (int) $receiptItem->fresh()->barcode_print_count,
                    'labels' => collect($labels)->map(fn ($l) => [
                        'id' => $l->id,
                        'barcode_value' => $l->barcode_value,
                        'label_index' => $l->label_index,
                        'labels_total' => $l->labels_total,
                    ])->values(),
                    'label_html' => $labelsHtml,
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
            if ($error = $this->validateReceivingPreconditions($lockedAssignment, $warehouse, true)) {
                return $error;
            }

            $receipt = $this->getOrCreateReceipt($lockedAssignment, $warehouse, $user);
            $receipt = WarehouseReceipt::query()->with(['items'])->lockForUpdate()->findOrFail($receipt->id);

            if ($receipt->isFinalized()) {
                return [
                    'success' => true,
                    'message' => 'Receipt is already finalized.',
                    'data' => [
                        'receipt' => $receipt->fresh(['items.photos']),
                        'assignment' => $lockedAssignment->fresh([
                            'driver',
                            'targetWarehouse',
                            'receivedWarehouse',
                        ]),
                    ],
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
                        'message' => 'Only a warehouse manager or HQ administrator can finalize discrepancy receipts.',
                    ];
                }

                if (blank($approvalReason)) {
                    return [
                        'success' => false,
                        'message' => 'Approval reason is required for discrepancy finalization.',
                    ];
                }
            }

            $receipt->loadMissing('items.shipmentItem');
            $receipt->items->each(function (WarehouseReceiptItem $item) {
                if (! $item->shipmentItem) {
                    return;
                }

                $shipmentItem = $this->ensureShipmentItemTrackingCode($item->shipmentItem);

                if (empty($item->barcode_value)) {
                    $item->update([
                        'barcode_value' => $shipmentItem->tracking_code,
                        'barcode_format' => 'code128',
                    ]);
                }
            });
            $receipt->refresh()->load('items.photos', 'items.shipmentItem');

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

            $receiveResult = ['success' => true, 'data' => ['assignment' => $lockedAssignment]];
            if (is_null($lockedAssignment->received_at)) {
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
            'delivery_method' => $item->shipmentItem?->delivery_method ?? 'direct',
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

    private function refreshReceiptStatus(WarehouseReceipt $receipt, bool $allowFinalizedRefresh = false): void
    {
        if ($receipt->isFinalized() && ! $allowFinalizedRefresh) {
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

    private function ensureShipmentItemTrackingCode(ShipmentItem $shipmentItem): ShipmentItem
    {
        if (empty($shipmentItem->tracking_code)) {
            $shipmentItem->update(['tracking_code' => ShipmentItem::generateTrackingCode()]);
            $shipmentItem->refresh();
        }

        return $shipmentItem;
    }

    /**
     * @return Collection<int, array<string,mixed>>
     */
    public function serializeReceiptItems(Collection $items): Collection
    {
        return $items->map(fn (WarehouseReceiptItem $item) => $this->serializeReceiptItem($item));
    }

    private function validateReceivingPreconditions(PickupAssignment $assignment, Warehouse $warehouse, bool $allowAlreadyReceived = false): ?array
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

        if (! $allowAlreadyReceived && !is_null($assignment->received_at)) {
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

    private function buildLabelPageHtml(string $labelCards, string $title): string
    {
        $css = <<<'CSS'
@page { size: 4in 6in; margin: 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; padding: 10px; background: #fff; }
.label {
    width: 4in; margin: 0 auto 10px; border: 1.5px solid #333; border-radius: 4px;
    background: #fff; padding: 0; overflow: hidden;
}
.label-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 10px 14px; border-bottom: 1.5px solid #333;
}
.brand-name { font-size: 15px; font-weight: 900; letter-spacing: 2px; color: #000; }
.brand-sub { font-size: 8px; font-weight: 700; letter-spacing: 3px; color: #666; margin-top: 1px; }
.qr-container { padding: 0; }
.qr-code { width: 64px; height: 64px; }
.qr-code img { width: 64px !important; height: 64px !important; }
.qr-code canvas { width: 64px !important; height: 64px !important; }
.divider { height: 1px; background: #ccc; }
.addresses { padding: 8px 14px; }
.address-block { margin: 4px 0; }
.address-label { font-size: 8px; font-weight: 800; color: #666; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 1px; }
.address-name { font-size: 14px; font-weight: 800; color: #000; }
.address-detail { font-size: 10px; color: #333; margin-top: 1px; }
.address-phone { font-size: 10px; color: #555; margin-top: 1px; }
.address-divider { height: 1px; background: #ddd; margin: 6px 0; }
.pkg-info { padding: 6px 14px; border-top: 1px solid #ccc; }
.pkg-row { display: flex; justify-content: space-between; align-items: center; padding: 2px 0; font-size: 10px; }
.pkg-label { color: #888; font-weight: 700; text-transform: uppercase; font-size: 8px; letter-spacing: 0.5px; }
.pkg-value { color: #000; font-weight: 700; }
.pkg-bold { font-size: 12px; font-weight: 900; color: #000; }
.barcode-section { padding: 10px 14px 12px; text-align: center; border-top: 1.5px solid #333; }
.barcode-svg { margin: 0 auto; }
.barcode-svg svg { max-width: 100%; height: 50px; }
.barcode-text { font-size: 12px; font-weight: 800; font-family: 'Courier New', monospace; color: #000; margin-top: 3px; letter-spacing: 2px; }
@media print {
    body { padding: 0; }
    .label { border: 1.5px solid #000; margin: 0; page-break-after: always; }
}
CSS;

        return '<!doctype html><html><head><meta charset="utf-8">'
            . '<title>Labels - ' . e($title) . '</title>'
            . '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>'
            . '<style>' . $css . '</style>'
            . '</head><body>' . $labelCards . '</body></html>';
    }
}
