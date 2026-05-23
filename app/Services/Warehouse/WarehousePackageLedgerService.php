<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\LabelCustodyEvent;
use App\Models\RecipientPaymentGroup;
use App\Models\RecipientPaymentTask;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\TransportManifest;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Services\StorageService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class WarehousePackageLedgerService
{
    public function query(Warehouse $warehouse): Builder
    {
        $relations = [
            'receipt:id,warehouse_id,status,pickup_assignment_id,finalized_at,transport_manifest_id',
            'receipt.pickupAssignment:id,driver_id,received_warehouse_id,received_at',
            'receipt.pickupAssignment.driver:id,name,phone',
            'receipt.pickupAssignment.photos:id,pickup_assignment_id,shipment_item_id,path,original_name,size,type',
            'receivedBy:id,name',
            'labels.latestCustody.driver:id,name,phone',
            'shipmentItem:id,shipment_id,description,quantity,tracking_code,status,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark,delivery_instructions,delivery_method,delivery_preference,fulfillment_type',
            'shipmentItem.images:id,shipment_item_id,path,original_name,size,sort_order',
            'shipmentItem.shipment:id,vendor_id,shipment_number,status,source,created_by_user_id,destination_mode,fulfillment_type,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_landmark,submitted_at,created_at',
            'shipmentItem.shipment.vendor:id,name,business_name,phone',
            'shipmentItem.shipment.createdByUser:id,name',
            'shipmentItem.shipment.collection:id,shipment_id,warehouse_id,status,collected_by_name,collected_by_phone,collected_by_id_type,collected_by_id_number,ready_at,collected_at,handed_over_by_user_id,notes,signature_path',
            'shipmentItem.shipment.collection.warehouse:id,name,code',
            'shipmentItem.shipment.collection.handedOverBy:id,name',
            'shipmentItem.deliveryRegion:id,name',
            'shipmentItem.deliveryDistrict:id,name',
            'shipmentItem.shipment.deliveryRegion:id,name',
            'shipmentItem.shipment.deliveryDistrict:id,name',
            'photos:id,warehouse_receipt_item_id,path,original_name,size,photo_type',
            'sortBatchItems.addedBy:id,name',
            'sortBatchItems.sortBatch:id,batch_number,status,dispatch_mode,origin_warehouse_id,destination_warehouse_id,created_by_user_id,sealed_by_user_id,sealed_at',
            'sortBatchItems.sortBatch.originWarehouse:id,name,code',
            'sortBatchItems.sortBatch.destinationWarehouse:id,name,code',
            'sortBatchItems.sortBatch.createdBy:id,name',
            'sortBatchItems.sortBatch.sealedBy:id,name',
        ];

        if (Schema::hasTable('transport_manifests')) {
            $relations[] = 'sortBatchItems.sortBatch.transportManifest:id,sort_batch_id,manifest_number,status,assigned_driver_id,origin_warehouse_id,destination_warehouse_id,created_by_user_id,received_by_user_id,assigned_at,dispatched_at,arrived_at,received_at';
            $relations[] = 'sortBatchItems.sortBatch.transportManifest.assignedDriver:id,name,phone';
            $relations[] = 'sortBatchItems.sortBatch.transportManifest.originWarehouse:id,name,code';
            $relations[] = 'sortBatchItems.sortBatch.transportManifest.destinationWarehouse:id,name,code';
            $relations[] = 'sortBatchItems.sortBatch.transportManifest.createdBy:id,name';
            $relations[] = 'sortBatchItems.sortBatch.transportManifest.receivedBy:id,name';
        }

        if (Schema::hasTable('delivery_runs')) {
            $relations[] = 'sortBatchItems.sortBatch.deliveryRun:id,sort_batch_id,run_number,status,assigned_driver_id';
            $relations[] = 'sortBatchItems.sortBatch.deliveryRun.assignedDriver:id,name,phone';
        }

        if ($this->hasRecipientPaymentTables()) {
            $relations[] = 'sortBatchItems.recipientPaymentTask.assignedTo:id,name';
            $relations[] = 'sortBatchItems.recipientPaymentTask.paymentGroupRecord.paidBy:id,name';
            $relations[] = 'sortBatchItems.recipientPaymentTask.paymentGroupRecord.sessionEntries.recordedBy:id,name';
            $relations[] = 'sortBatchItems.recipientPaymentTask.paymentWallet:id,name,provider,phone_number';
            $relations[] = 'sortBatchItems.recipientPaymentTask.shipmentCharge.recordedByAdmin:id,name';
            $relations[] = 'shipmentItem.recipientPaymentTasks.assignedTo:id,name';
            $relations[] = 'shipmentItem.recipientPaymentTasks.paymentGroupRecord.paidBy:id,name';
            $relations[] = 'shipmentItem.recipientPaymentTasks.paymentGroupRecord.sessionEntries.recordedBy:id,name';
            $relations[] = 'shipmentItem.recipientPaymentTasks.paymentWallet:id,name,provider,phone_number';
            $relations[] = 'shipmentItem.recipientPaymentTasks.shipmentCharge.recordedByAdmin:id,name';
        }

        if ($this->hasTransportTables()) {
            $relations[] = 'shipmentItem.transportManifestItems.manifest.assignedDriver:id,name,phone';
            $relations[] = 'shipmentItem.transportManifestItems.manifest.originWarehouse:id,name,code';
            $relations[] = 'shipmentItem.transportManifestItems.manifest.destinationWarehouse:id,name,code';
            $relations[] = 'shipmentItem.transportManifestItems.manifest.createdBy:id,name';
            $relations[] = 'shipmentItem.transportManifestItems.manifest.receivedBy:id,name';
        }

        if ($this->hasDeliveryTables()) {
            $relations[] = 'shipmentItem.deliveryRunItems.run.assignedDriver:id,name,phone';
            $relations[] = 'shipmentItem.deliveryRunItems.run.createdBy:id,name';
            $relations[] = 'shipmentItem.deliveryRunItems.stop.region:id,name';
            $relations[] = 'shipmentItem.deliveryRunItems.stop.district:id,name';
            $relations[] = 'shipmentItem.deliveryRunItems.stop.confirmedBy:id,name';
            $relations[] = 'shipmentItem.deliveryRunItems.stop.verificationAttempts.driver:id,name,phone';
            $relations[] = 'shipmentItem.deliveryRunItems.busHandoffConfirmation.reason:id,label,type';
            $relations[] = 'shipmentItem.deliveryRunItems.busHandoffConfirmation.handoffDriver:id,name,phone';
            $relations[] = 'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByDriver:id,name,phone';
            $relations[] = 'shipmentItem.deliveryRunItems.busHandoffConfirmation.confirmedByAdmin:id,name';
        }

        return WarehouseReceiptItem::query()
            ->with($relations)
            ->whereHas('receipt', function (Builder $query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('status', WarehouseReceipt::STATUS_FINALIZED);
            });
    }

    public function applyFilters(Builder $query, Request $request): Builder
    {
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('barcode_value', 'like', "%{$search}%")
                    ->orWhereHas('labels', fn (Builder $q) => $q->where('barcode_value', 'like', "%{$search}%"))
                    ->orWhereHas('receipt.pickupAssignment.driver', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', function (Builder $q) use ($search) {
                        $q->where('description', 'like', "%{$search}%")
                            ->orWhere('tracking_code', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                            ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                            ->orWhere('delivery_town', 'like', "%{$search}%")
                            ->orWhereHas('shipment', fn (Builder $sq) => $sq
                                ->where('shipment_number', 'like', "%{$search}%")
                                ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                                ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                                ->orWhere('delivery_town', 'like', "%{$search}%")
                                ->orWhereHas('vendor', fn (Builder $vq) => $vq
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('business_name', 'like', "%{$search}%")
                                    ->orWhere('phone', 'like', "%{$search}%")));
                    })
                    ->orWhereHas('sortBatchItems.sortBatch', fn (Builder $q) => $q->where('batch_number', 'like', "%{$search}%"));

                if ($this->hasTransportTables()) {
                    $builder->orWhereHas('shipmentItem.transportManifestItems.manifest', fn (Builder $q) => $q->where('manifest_number', 'like', "%{$search}%"));
                }

                if ($this->hasDeliveryTables()) {
                    $builder->orWhereHas('shipmentItem.deliveryRunItems.run', fn (Builder $q) => $q->where('run_number', 'like', "%{$search}%"));
                }
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('received_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('received_at', '<=', $dateTo);
        }

        if ($status = trim((string) $request->input('status'))) {
            $query->whereHas('shipmentItem', fn (Builder $q) => $q->where('status', $status));
        }

        if ($deliveryMethod = trim((string) $request->input('delivery_method'))) {
            $query->whereHas('shipmentItem', function (Builder $q) use ($deliveryMethod) {
                if ($deliveryMethod === 'pickup') {
                    $q->where('delivery_preference', 'pickup')
                        ->orWhereHas('shipment', fn (Builder $sq) => $sq->where('fulfillment_type', 'self_pickup'));
                    return;
                }

                if ($deliveryMethod === ShipmentItem::DELIVERY_METHOD_DIRECT) {
                    $q->where(fn (Builder $inner) => $inner->where('delivery_method', ShipmentItem::DELIVERY_METHOD_DIRECT)->orWhereNull('delivery_method'));
                    return;
                }
                $q->where('delivery_method', $deliveryMethod);
            });
        }

        if ($vendor = trim((string) $request->input('vendor'))) {
            $query->whereHas('shipmentItem.shipment.vendor', fn (Builder $q) => $q
                ->where('name', 'like', "%{$vendor}%")
                ->orWhere('business_name', 'like', "%{$vendor}%")
                ->orWhere('phone', 'like', "%{$vendor}%"));
        }

        $this->applyDeliveredDateFilter($query, $request);
        $this->applySortBatchFilter($query, $request);
        $this->applyManifestFilter($query, $request);
        $this->applyDeliveryFilter($query, $request);
        $this->applyCustodyFilter($query, $request);
        $this->applyPaymentFilter($query, $request);
        $this->applyStaffFilters($query, $request);
        $this->applyDeliveryFeeRange($query, $request);

        return $query;
    }

    public function summary(Builder $baseQuery): array
    {
        $total = (clone $baseQuery)->count();

        return [
            'total' => $total,
            'at_warehouse' => (clone $baseQuery)->whereHas('shipmentItem', fn (Builder $q) => $q->whereIn('status', [
                ItemStatus::AT_WAREHOUSE->value,
                ItemStatus::AT_DESTINATION->value,
            ]))->count(),
            'in_transit' => (clone $baseQuery)->whereHas('shipmentItem', fn (Builder $q) => $q->where('status', ItemStatus::IN_TRANSIT->value))->count(),
            'out_for_delivery' => (clone $baseQuery)->whereHas('shipmentItem', fn (Builder $q) => $q->where('status', ItemStatus::OUT_FOR_DELIVERY->value))->count(),
            'delivered' => (clone $baseQuery)->whereHas('shipmentItem', fn (Builder $q) => $q->where('status', ItemStatus::DELIVERED->value))->count(),
            'payment_due' => $this->hasRecipientPaymentTables()
                ? (clone $baseQuery)->whereHas('shipmentItem.recipientPaymentTasks', fn (Builder $q) => $q->whereIn('status', [
                    RecipientPaymentTask::STATUS_PENDING,
                    RecipientPaymentTask::STATUS_ASSIGNED,
                    RecipientPaymentTask::STATUS_IN_PROGRESS,
                ]))->count()
                : 0,
            'total_paid' => $this->totalPaidAmount($baseQuery),
        ];
    }

    public function map(WarehouseReceiptItem $receiptItem): array
    {
        $shipmentItem = $receiptItem->shipmentItem;
        $shipment = $shipmentItem?->shipment;
        $sortBatchItem = $receiptItem->sortBatchItems
            ->whereNull('removed_at')
            ->sortByDesc('id')
            ->first();
        $sortBatch = $sortBatchItem?->sortBatch;
        $manifestItems = $shipmentItem && $shipmentItem->relationLoaded('transportManifestItems')
            ? $shipmentItem->transportManifestItems
            : collect();
        $manifestItem = $manifestItems->sortByDesc('id')->first();
        $sortBatchManifest = $sortBatch && $sortBatch->relationLoaded('transportManifest')
            ? $sortBatch->transportManifest
            : null;
        $manifest = $manifestItem?->manifest ?: $sortBatchManifest;
        $deliveryRunItems = $shipmentItem && $shipmentItem->relationLoaded('deliveryRunItems')
            ? $shipmentItem->deliveryRunItems
            : collect();
        $deliveryRunItem = $deliveryRunItems->sortByDesc('id')->first();
        $sortBatchDeliveryRun = $sortBatch && $sortBatch->relationLoaded('deliveryRun')
            ? $sortBatch->deliveryRun
            : null;
        $deliveryRun = $deliveryRunItem?->run ?: $sortBatchDeliveryRun;
        $deliveryStop = $deliveryRunItem?->stop;
        $payment = $this->paymentSnapshot($sortBatchItem, $shipmentItem);
        $custody = $this->custodySnapshot($receiptItem, $deliveryRunItem, $deliveryStop, $deliveryRun);
        $stage = $this->stageSnapshot($shipmentItem, $sortBatch, $manifest, $deliveryRunItem, $deliveryStop);
        $recipient = $this->recipientSnapshot($shipmentItem);
        $photos = $this->photoSnapshot($receiptItem, $shipmentItem);
        $locks = $this->editLocks($sortBatch, $manifest, $deliveryRunItem, $deliveryStop);
        $labels = $receiptItem->labels ?? collect();

        return [
            'id' => $receiptItem->id,
            'warehouse_receipt_id' => $receiptItem->warehouse_receipt_id,
            'shipment_item_id' => $shipmentItem?->id,
            'shipment_id' => $shipment?->id,
            'shipment_number' => $shipment?->shipment_number,
            'vendor_name' => $shipment?->vendor?->business_name ?: $shipment?->vendor?->name,
            'tracking_code' => $shipmentItem?->tracking_code,
            'barcode_value' => $receiptItem->barcode_value ?: $labels->first()?->barcode_value,
            'label_count' => max((int) $labels->count(), (int) $receiptItem->barcode_print_count),
            'item_description' => $shipmentItem?->description,
            'quantity' => (int) ($shipmentItem?->quantity ?? $receiptItem->received_quantity ?? 0),
            'received_quantity' => (int) $receiptItem->received_quantity,
            'damaged_quantity' => (int) $receiptItem->damaged_quantity,
            'discrepancy_type' => $receiptItem->discrepancy_type,
            'recipient_name' => $recipient['name'],
            'recipient_phone' => $recipient['phone'],
            'destination' => $recipient['destination'],
            'delivery_location' => [
                'region_id' => $shipmentItem?->delivery_region_id,
                'district_id' => $shipmentItem?->delivery_district_id,
                'town' => $shipmentItem?->delivery_town,
                'display' => collect([
                    $shipmentItem?->delivery_town,
                    $shipmentItem?->deliveryDistrict?->name,
                    $shipmentItem?->deliveryRegion?->name,
                ])->filter()->join(', '),
            ],
            'delivery_landmark' => $shipmentItem?->delivery_landmark,
            'delivery_instructions' => $shipmentItem?->delivery_instructions,
            'delivery_method' => $shipmentItem?->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
            'delivery_method_label' => ($shipment?->fulfillment_type?->value ?? $shipment?->getRawOriginal('fulfillment_type')) === 'self_pickup'
                ? 'Self pickup'
                : ($shipmentItem?->delivery_method === ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF ? 'Bus handoff' : 'Direct'),
            'received_at' => $this->formatDateTime($receiptItem->received_at),
            'received_by' => $receiptItem->receivedBy?->name,
            'receipt_source' => $receiptItem->receipt?->transport_manifest_id ? 'Incoming manifest' : 'Warehouse receipt',
            'pickup_driver' => $receiptItem->receipt?->pickupAssignment?->driver?->name,
            'current_stage' => $stage,
            'custody' => $custody,
            'sort_batch' => $sortBatch ? [
                'id' => $sortBatch->id,
                'number' => $sortBatch->batch_number,
                'status' => $this->formatStatus($sortBatch->status),
                'dispatch_mode' => $sortBatch->dispatch_mode,
                'destination' => $sortBatch->destinationWarehouse?->name,
            ] : null,
            'transport_manifest' => $manifest ? [
                'id' => $manifest->id,
                'number' => $manifest->manifest_number,
                'status' => $this->formatStatus($manifest->status),
                'driver' => $manifest->assignedDriver?->name,
            ] : null,
            'delivery_run' => $deliveryRun ? [
                'id' => $deliveryRun->id,
                'number' => $deliveryRun->run_number,
                'status' => $this->formatStatus($deliveryRun->status),
                'driver' => $deliveryRun->assignedDriver?->name,
                'stop_status' => $this->formatStatus($deliveryStop?->status),
            ] : null,
            'payment' => $payment,
            'photos' => $photos,
            'forward_to_warehouse_id' => $sortBatch?->dispatch_mode === SortBatch::DISPATCH_TRANSFER ? $sortBatch->destination_warehouse_id : null,
            'can_edit_bus_handoff' => $locks['can_edit_bus_handoff'],
            'bus_handoff_lock_reason' => $locks['bus_handoff_lock_reason'],
            'can_forward_to_warehouse' => $locks['can_forward_to_warehouse'],
            'forward_lock_reason' => $locks['forward_lock_reason'],
            'can_move_sort_batch' => $this->canMoveSortBatch($shipmentItem, $sortBatch),
            'sort_lock_reason' => $this->sortLockReason($shipmentItem, $sortBatch),
            'view_url' => route('warehouse.packages.show', $receiptItem),
            'shipment_url' => $shipment ? route('admin.shipments.show', $shipment) : null,
            'sort_batch_url' => $sortBatch ? route('warehouse.sorting.show', $sortBatch) : null,
            'manifest_url' => $manifest ? route('warehouse.manifests.transport.show', $manifest) : null,
            'delivery_run_url' => $deliveryRun ? route('warehouse.deliveries.runs.show', $deliveryRun) : null,
            'payment_url' => route('warehouse.recipient-payments.index'),
        ];
    }

    public function sortLockReason(?ShipmentItem $shipmentItem, ?SortBatch $currentBatch): ?string
    {
        if (!$shipmentItem) {
            return 'Package record is missing.';
        }

        $status = $shipmentItem->status?->value ?? $shipmentItem->getRawOriginal('status');
        if (in_array($status, [ItemStatus::DELIVERED->value, ItemStatus::RETURNED->value, ItemStatus::IN_TRANSIT->value, ItemStatus::OUT_FOR_DELIVERY->value, ItemStatus::HANDED_TO_COURIER->value], true)) {
            return 'Package has already moved beyond warehouse sorting.';
        }

        if ($currentBatch && $currentBatch->status !== SortBatch::STATUS_OPEN) {
            return 'Current sort batch is sealed.';
        }

        $manifestItems = $shipmentItem->relationLoaded('transportManifestItems')
            ? $shipmentItem->transportManifestItems
            : collect();
        $hasActiveManifest = $manifestItems->contains(fn ($item) => !in_array($item->manifest?->status, [
            TransportManifest::STATUS_RECEIVED,
            TransportManifest::STATUS_CANCELLED,
        ], true));

        if ($hasActiveManifest) {
            return 'Package is linked to an active transport manifest.';
        }

        $deliveryRunItems = $shipmentItem->relationLoaded('deliveryRunItems')
            ? $shipmentItem->deliveryRunItems
            : collect();
        $hasActiveRun = $deliveryRunItems->contains(fn ($item) => $item->run?->status !== DeliveryRun::STATUS_CANCELLED);
        if ($hasActiveRun) {
            return 'Package is linked to a delivery run.';
        }

        return null;
    }

    public function canMoveSortBatch(?ShipmentItem $shipmentItem, ?SortBatch $currentBatch): bool
    {
        return $this->sortLockReason($shipmentItem, $currentBatch) === null;
    }

    private function applySortBatchFilter(Builder $query, Request $request): void
    {
        $filter = trim((string) $request->input('sort_batch'));
        if ($filter === 'none') {
            $query->whereDoesntHave('sortBatchItems', fn (Builder $q) => $q->whereNull('removed_at'));
        } elseif ($filter === 'open' || $filter === 'sealed') {
            $query->whereHas('sortBatchItems', fn (Builder $q) => $q
                ->whereNull('removed_at')
                ->whereHas('sortBatch', fn (Builder $batchQuery) => $batchQuery->where('status', $filter)));
        }

        if ($batchId = $request->input('sort_batch_id')) {
            $query->whereHas('sortBatchItems', fn (Builder $q) => $q->whereNull('removed_at')->where('sort_batch_id', (int) $batchId));
        }
    }

    private function applyManifestFilter(Builder $query, Request $request): void
    {
        $filter = trim((string) $request->input('manifest_status'));
        if ($filter === '') {
            return;
        }

        if (!$this->hasTransportTables()) {
            if ($filter !== 'none') {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($filter === 'none') {
            $query->whereDoesntHave('shipmentItem.transportManifestItems');
            return;
        }

        $query->whereHas('shipmentItem.transportManifestItems.manifest', fn (Builder $q) => $q->where('status', $filter));
    }

    private function applyDeliveredDateFilter(Builder $query, Request $request): void
    {
        $from = $request->input('delivered_date_from');
        $to = $request->input('delivered_date_to');

        if (!$from && !$to) {
            return;
        }

        $query->where(function (Builder $builder) use ($from, $to) {
            if ($this->hasDeliveryTables()) {
                $builder->whereHas('shipmentItem.deliveryRunItems', function (Builder $q) use ($from, $to) {
                    $q->where(function (Builder $dateQuery) use ($from, $to) {
                        $dateQuery->whereNotNull('delivered_at');
                        if ($from) $dateQuery->whereDate('delivered_at', '>=', $from);
                        if ($to) $dateQuery->whereDate('delivered_at', '<=', $to);
                    })->orWhereHas('stop', function (Builder $stopQuery) use ($from, $to) {
                        $stopQuery->whereNotNull('delivered_at');
                        if ($from) $stopQuery->whereDate('delivered_at', '>=', $from);
                        if ($to) $stopQuery->whereDate('delivered_at', '<=', $to);
                    });
                });
            }

            $builder->orWhereHas('labels.custodyEvents', function (Builder $q) use ($from, $to) {
                $q->where('event_type', LabelCustodyEvent::TYPE_DELIVERED);
                if ($from) $q->whereDate('created_at', '>=', $from);
                if ($to) $q->whereDate('created_at', '<=', $to);
            });
        });
    }

    private function applyDeliveryFilter(Builder $query, Request $request): void
    {
        $filter = trim((string) $request->input('delivery_status'));
        if ($filter === '') {
            return;
        }

        if (!$this->hasDeliveryTables()) {
            if ($filter !== 'not_assigned') {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($filter === 'not_assigned') {
            $query->whereDoesntHave('shipmentItem.deliveryRunItems');
            return;
        }

        if ($filter === 'bus_handoff') {
            $query->whereHas('shipmentItem.deliveryRunItems.stop', fn (Builder $q) => $q->where('delivery_method', DeliveryRunStop::METHOD_BUS_HANDOFF));
            return;
        }

        $query->whereHas('shipmentItem.deliveryRunItems', fn (Builder $q) => $q
            ->where('status', $filter)
            ->orWhereHas('stop', fn (Builder $sq) => $sq->where('status', $filter)));
    }

    private function applyCustodyFilter(Builder $query, Request $request): void
    {
        $filter = trim((string) $request->input('custody'));
        if ($filter === '') {
            return;
        }

        if ($filter === 'bus_handoff') {
            if (!$this->hasDeliveryTables()) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->whereHas('shipmentItem.deliveryRunItems.stop', fn (Builder $q) => $q
                ->where('delivery_method', DeliveryRunStop::METHOD_BUS_HANDOFF)
                ->whereNotNull('handoff_at'));
            return;
        }

        if ($filter === 'delivered') {
            $query->whereHas('shipmentItem', fn (Builder $q) => $q->where('status', ItemStatus::DELIVERED->value));
            return;
        }

        if ($filter === 'with_driver') {
            $query->whereHas('labels.latestCustody', fn (Builder $q) => $q->where('event_type', LabelCustodyEvent::TYPE_CLAIMED));
            return;
        }

        if ($filter === 'at_warehouse') {
            $query->whereHas('shipmentItem', fn (Builder $q) => $q->whereIn('status', [
                ItemStatus::AT_WAREHOUSE->value,
                ItemStatus::AT_DESTINATION->value,
                ItemStatus::SORTED->value,
            ]))->whereDoesntHave('labels.latestCustody', fn (Builder $q) => $q->where('event_type', LabelCustodyEvent::TYPE_CLAIMED));
        }
    }

    private function applyPaymentFilter(Builder $query, Request $request): void
    {
        $filter = trim((string) $request->input('payment_status'));
        if ($filter === '') {
            return;
        }

        if (!$this->hasRecipientPaymentTables()) {
            if ($filter !== 'no_fee') {
                $query->whereRaw('1 = 0');
            }
            return;
        }

        if ($filter === 'no_fee') {
            $query->whereDoesntHave('shipmentItem.recipientPaymentTasks')
                ->when($this->hasChargesTable(), fn (Builder $q) => $q->whereDoesntHave('shipmentItem.charges', fn (Builder $chargeQuery) => $chargeQuery->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)));
            return;
        }

        if ($filter === 'due') {
            $query->whereHas('shipmentItem.recipientPaymentTasks', fn (Builder $q) => $q->whereIn('status', [
                RecipientPaymentTask::STATUS_PENDING,
                RecipientPaymentTask::STATUS_ASSIGNED,
                RecipientPaymentTask::STATUS_IN_PROGRESS,
            ]));
            return;
        }

        $status = $filter === 'overridden' ? RecipientPaymentTask::STATUS_OVERRIDDEN : $filter;
        $query->whereHas('shipmentItem.recipientPaymentTasks', fn (Builder $q) => $q->where('status', $status));
    }

    private function applyStaffFilters(Builder $query, Request $request): void
    {
        if (($deliveryStaffId = (int) $request->input('delivery_staff_id')) > 0) {
            if (!$this->hasDeliveryTables()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('shipmentItem.deliveryRunItems.stop', fn (Builder $q) => $q->where('confirmed_by_admin_id', $deliveryStaffId));
            }
        }

        if (($paymentStaffId = (int) $request->input('payment_staff_id')) > 0) {
            if (!$this->hasRecipientPaymentTables()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereHas('shipmentItem.recipientPaymentTasks', function (Builder $q) use ($paymentStaffId) {
                    $q->whereHas('paymentGroupRecord', fn (Builder $groupQuery) => $groupQuery->where('paid_by_user_id', $paymentStaffId))
                        ->orWhereHas('paymentGroupRecord.sessionEntries', fn (Builder $entryQuery) => $entryQuery->where('recorded_by_user_id', $paymentStaffId));
                });
            }
        }
    }

    private function applyDeliveryFeeRange(Builder $query, Request $request): void
    {
        $min = $request->input('amount_min');
        $max = $request->input('amount_max');

        if ($min === null && $max === null) {
            return;
        }

        if (!$this->hasRecipientPaymentTables()) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->whereHas('shipmentItem.recipientPaymentTasks', function (Builder $q) use ($min, $max) {
            $q->where(function (Builder $amountQuery) use ($min, $max) {
                $amountQuery->whereNotNull('negotiated_amount');
                if ($min !== null && $min !== '') $amountQuery->where('negotiated_amount', '>=', (float) $min);
                if ($max !== null && $max !== '') $amountQuery->where('negotiated_amount', '<=', (float) $max);
            })->orWhereHas('paymentGroupRecord', function (Builder $groupQuery) use ($min, $max) {
                if ($min !== null && $min !== '') $groupQuery->where('amount', '>=', (float) $min);
                if ($max !== null && $max !== '') $groupQuery->where('amount', '<=', (float) $max);
            });
        });
    }

    private function recipientSnapshot(?ShipmentItem $shipmentItem): array
    {
        $shipment = $shipmentItem?->shipment;
        $isPerItem = $shipment?->isPerItemDestination() ?? false;
        $name = $isPerItem ? $shipmentItem?->delivery_recipient_name : $shipment?->delivery_recipient_name;
        $phone = $isPerItem ? $shipmentItem?->delivery_recipient_phone : $shipment?->delivery_recipient_phone;
        $town = $isPerItem ? $shipmentItem?->delivery_town : $shipment?->delivery_town;
        $region = $isPerItem ? $shipmentItem?->deliveryRegion?->name : $shipment?->deliveryRegion?->name;
        $district = $isPerItem ? $shipmentItem?->deliveryDistrict?->name : $shipment?->deliveryDistrict?->name;

        return [
            'name' => $name,
            'phone' => $phone,
            'destination' => collect([$town, $district, $region])->filter()->join(', '),
        ];
    }

    private function stageSnapshot(?ShipmentItem $shipmentItem, ?SortBatch $sortBatch, ?TransportManifest $manifest, ?DeliveryRunItem $deliveryRunItem, ?DeliveryRunStop $deliveryStop): array
    {
        $status = $shipmentItem?->status?->value ?? $shipmentItem?->getRawOriginal('status') ?? 'unknown';
        $label = ItemStatus::tryFrom($status)?->label() ?? ucfirst(str_replace('_', ' ', $status));

        if ($deliveryStop?->status === DeliveryRunStop::STATUS_HANDED_OFF || $deliveryRunItem?->status === DeliveryRunItem::STATUS_HANDED_OFF) {
            return ['value' => 'handed_to_courier', 'label' => 'Handed to Courier', 'tone' => 'amber'];
        }

        if ($deliveryRunItem?->status === DeliveryRunItem::STATUS_DELIVERED || $deliveryStop?->status === DeliveryRunStop::STATUS_DELIVERED) {
            return ['value' => ItemStatus::DELIVERED->value, 'label' => 'Delivered', 'tone' => 'emerald'];
        }

        if ($manifest && in_array($manifest->status, [TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING, TransportManifest::STATUS_IN_TRANSIT], true)) {
            return ['value' => ItemStatus::IN_TRANSIT->value, 'label' => 'In Transport', 'tone' => 'blue'];
        }

        if ($sortBatch) {
            return ['value' => ItemStatus::SORTED->value, 'label' => $sortBatch->status === SortBatch::STATUS_OPEN ? 'In Open Sort Batch' : 'Sorted / Sealed', 'tone' => 'violet'];
        }

        return ['value' => $status, 'label' => $label, 'tone' => match ($status) {
            ItemStatus::DELIVERED->value => 'emerald',
            ItemStatus::IN_TRANSIT->value, ItemStatus::OUT_FOR_DELIVERY->value => 'blue',
            ItemStatus::RETURNED->value => 'rose',
            default => 'slate',
        }];
    }

    private function custodySnapshot(WarehouseReceiptItem $receiptItem, ?DeliveryRunItem $deliveryRunItem, ?DeliveryRunStop $deliveryStop, ?DeliveryRun $deliveryRun): array
    {
        if ($deliveryStop?->delivery_method === DeliveryRunStop::METHOD_BUS_HANDOFF && ($deliveryStop->handoff_at || $deliveryStop->status === DeliveryRunStop::STATUS_HANDED_OFF)) {
            return [
                'type' => 'bus_handoff',
                'label' => 'Bus station / courier',
                'holder' => $deliveryStop->handoff_courier_name ?: $deliveryStop->bus_station_name ?: 'Bus station / courier',
                'detail' => collect([$deliveryStop->handoff_courier_phone, $deliveryStop->handoff_vehicle_number])->filter()->join(' / '),
                'at' => $this->formatDateTime($deliveryStop->handoff_at),
            ];
        }

        if ($deliveryRun?->assignedDriver && in_array($deliveryRunItem?->status, [DeliveryRunItem::STATUS_PENDING, DeliveryRunItem::STATUS_PARTIAL], true)) {
            return [
                'type' => 'with_driver',
                'label' => 'With delivery driver',
                'holder' => $deliveryRun->assignedDriver->name,
                'detail' => $deliveryRun->assignedDriver->phone,
                'at' => $this->formatDateTime($deliveryRun->dispatched_at),
            ];
        }

        $latest = $receiptItem->labels
            ->map(fn ($label) => $label->latestCustody)
            ->filter()
            ->sortByDesc('created_at')
            ->first();

        if ($latest?->event_type === LabelCustodyEvent::TYPE_CLAIMED && $latest->driver) {
            return [
                'type' => 'with_driver',
                'label' => 'With driver',
                'holder' => $latest->driver->name,
                'detail' => $latest->driver->phone,
                'at' => $this->formatDateTime($latest->created_at),
            ];
        }

        if ($latest?->event_type === LabelCustodyEvent::TYPE_DELIVERED) {
            return [
                'type' => 'delivered',
                'label' => 'Delivered',
                'holder' => $latest->driver?->name,
                'detail' => $latest->location_note,
                'at' => $this->formatDateTime($latest->created_at),
            ];
        }

        return [
            'type' => 'at_warehouse',
            'label' => 'At warehouse',
            'holder' => null,
            'detail' => null,
            'at' => $this->formatDateTime($receiptItem->received_at),
        ];
    }

    private function paymentSnapshot($sortBatchItem, ?ShipmentItem $shipmentItem): array
    {
        $recipientPaymentTasks = $shipmentItem && $shipmentItem->relationLoaded('recipientPaymentTasks')
            ? $shipmentItem->recipientPaymentTasks
            : collect();
        $sortBatchTask = $sortBatchItem && $sortBatchItem->relationLoaded('recipientPaymentTask')
            ? $sortBatchItem->recipientPaymentTask
            : null;
        $task = $sortBatchTask
            ?: $recipientPaymentTasks->sortByDesc('id')->first();
        $group = $task?->paymentGroupRecord;
        $charge = $task?->shipmentCharge;

        if (!$task && !$charge && !$group) {
            return [
                'status' => 'no_fee',
                'status_label' => 'No delivery fee',
                'amount' => null,
                'amount_label' => null,
                'paid_at' => null,
                'paid_by' => null,
                'payment_wallet_id' => null,
                'wallet' => null,
                'reference' => null,
            ];
        }

        $status = $group?->status === RecipientPaymentGroup::STATUS_PAID
            ? RecipientPaymentTask::STATUS_PAID
            : ($task?->status ?? $charge?->status ?? 'due');
        $amount = $group?->amount ?? $task?->negotiated_amount ?? $charge?->amount;
        $paidBy = $group?->paidBy?->name
            ?? $group?->sessionEntries?->sortByDesc('id')->first()?->recordedBy?->name
            ?? $charge?->recordedByAdmin?->name;

        return [
            'status' => $status,
            'status_label' => match ($status) {
                RecipientPaymentTask::STATUS_PAID, ShipmentCharge::STATUS_PAID => 'Paid',
                RecipientPaymentTask::STATUS_WAIVED, ShipmentCharge::STATUS_WAIVED => 'Waived',
                RecipientPaymentTask::STATUS_OVERRIDDEN => 'Override',
                default => $amount === null ? 'No delivery fee' : 'Due',
            },
            'amount' => $amount !== null ? (float) $amount : null,
            'amount_label' => $amount !== null ? 'GHS ' . number_format((float) $amount, 2) : null,
            'paid_at' => $this->formatDateTime($group?->paid_at ?? $task?->paid_at ?? $charge?->paid_at),
            'paid_by' => $paidBy,
            'payment_wallet_id' => $group?->payment_wallet_id ?? $task?->payment_wallet_id,
            'wallet' => $task?->paymentWallet?->name,
            'reference' => $group?->payment_reference ?? $task?->payment_reference ?? $charge?->payment_reference,
        ];
    }

    private function photoSnapshot(WarehouseReceiptItem $receiptItem, ?ShipmentItem $shipmentItem): array
    {
        $vendorPhotos = $shipmentItem && $shipmentItem->relationLoaded('images')
            ? $shipmentItem->images->map(fn ($photo) => $this->formatPhoto($photo, 'Vendor'))->values()
            : collect();

        $pickupPhotos = $receiptItem->receipt?->pickupAssignment && $receiptItem->receipt->pickupAssignment->relationLoaded('photos')
            ? $receiptItem->receipt->pickupAssignment->photos
                ->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $shipmentItem?->id)
                ->map(fn ($photo) => $this->formatPhoto($photo, 'Pickup'))
                ->values()
            : collect();

        $receiptPhotos = $receiptItem->relationLoaded('photos')
            ? $receiptItem->photos->map(fn ($photo) => $this->formatPhoto($photo, 'Receipt'))->values()
            : collect();

        $primary = $vendorPhotos->isNotEmpty()
            ? ['source' => 'vendor', 'label' => 'Vendor photos', 'items' => $vendorPhotos]
            : ($pickupPhotos->isNotEmpty()
                ? ['source' => 'pickup', 'label' => 'Pickup photos', 'items' => $pickupPhotos]
                : ['source' => 'receipt', 'label' => 'Receipt photos', 'items' => $receiptPhotos]);

        return [
            'vendor' => $vendorPhotos,
            'pickup' => $pickupPhotos,
            'receipt' => $receiptPhotos,
            'primary_source' => $primary['source'],
            'primary_label' => $primary['label'],
            'primary' => $primary['items'],
            'total' => $vendorPhotos->count() + $pickupPhotos->count() + $receiptPhotos->count(),
        ];
    }

    private function formatPhoto($photo, string $source): array
    {
        $storage = app(StorageService::class);
        $url = $storage->getUrl($photo->path);

        if (!$storage->exists($photo->path) && Storage::disk('public')->exists($photo->path)) {
            $url = url(Storage::disk('public')->url($photo->path));
        }

        return [
            'id' => $photo->id,
            'url' => $url,
            'name' => $photo->original_name ?: $source . ' photo',
            'source' => $source,
            'size' => $photo->size,
        ];
    }

    private function editLocks(?SortBatch $sortBatch, ?TransportManifest $manifest, ?DeliveryRunItem $deliveryRunItem, ?DeliveryRunStop $deliveryStop): array
    {
        $handedOff = $deliveryStop?->status === DeliveryRunStop::STATUS_HANDED_OFF || filled($deliveryStop?->handoff_at);
        $delivered = $deliveryStop?->status === DeliveryRunStop::STATUS_DELIVERED || $deliveryRunItem?->status === DeliveryRunItem::STATUS_DELIVERED;
        $hasActiveDelivery = $deliveryRunItem !== null;
        $batchLocked = $sortBatch && $sortBatch->status !== SortBatch::STATUS_OPEN;

        return [
            'can_edit_bus_handoff' => !$handedOff && !$delivered,
            'bus_handoff_lock_reason' => $handedOff
                ? 'Already handed off to bus station / courier.'
                : ($delivered ? 'Delivered packages cannot change bus handoff mode.' : null),
            'can_forward_to_warehouse' => !$manifest && !$hasActiveDelivery && !$batchLocked,
            'forward_lock_reason' => $manifest
                ? 'Package is already on a transport manifest.'
                : ($hasActiveDelivery
                    ? 'Package is already assigned to delivery.'
                    : ($batchLocked ? 'Sort batch is sealed.' : null)),
        ];
    }

    private function hasTransportTables(): bool
    {
        return Schema::hasTable('transport_manifest_items') && Schema::hasTable('transport_manifests');
    }

    private function hasDeliveryTables(): bool
    {
        return Schema::hasTable('delivery_run_items') && Schema::hasTable('delivery_runs') && Schema::hasTable('delivery_run_stops');
    }

    private function hasRecipientPaymentTables(): bool
    {
        return Schema::hasTable('recipient_payment_tasks');
    }

    private function hasChargesTable(): bool
    {
        return Schema::hasTable('shipment_charges');
    }

    private function totalPaidAmount(Builder $baseQuery): float
    {
        if (!$this->hasRecipientPaymentTables() || !Schema::hasTable('recipient_payment_groups')) {
            return 0.0;
        }

        $itemIds = (clone $baseQuery)
            ->pluck('shipment_item_id')
            ->filter()
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return 0.0;
        }

        $groupTotal = RecipientPaymentGroup::query()
            ->where('status', RecipientPaymentGroup::STATUS_PAID)
            ->whereHas('tasks', fn (Builder $q) => $q->whereIn('shipment_item_id', $itemIds))
            ->sum('amount');

        $ungroupedTaskTotal = RecipientPaymentTask::query()
            ->whereNull('recipient_payment_group_id')
            ->where('status', RecipientPaymentTask::STATUS_PAID)
            ->whereIn('shipment_item_id', $itemIds)
            ->sum('negotiated_amount');

        return (float) $groupTotal + (float) $ungroupedTaskTotal;
    }

    private function formatDateTime(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format('M j, Y g:i A');
        }

        return (string) $value;
    }

    private function formatStatus(?string $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return collect(explode(' ', str_replace(['_', '-'], ' ', $status)))
            ->filter()
            ->map(fn (string $word) => ucfirst(strtolower($word)))
            ->implode(' ');
    }
}
