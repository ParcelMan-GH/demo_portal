<?php

namespace App\Services\Warehouse;

use App\Helpers\PhoneHelper;
use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\WarehouseReceiptItemLabel;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SmsService;
use App\Services\StorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseDeliveryService
{
    public function __construct(
        private DeliveryVerificationService $verificationService,
        private StorageService $storageService,
        private SmsService $smsService,
        private \App\Services\VendorCommissionService $commissionService,
    ) {
    }

    public function runsQuery(Warehouse $warehouse): Builder
    {
        return DeliveryRun::query()
            ->with([
                'warehouse:id,name,code',
                'assignedDriver:id,name,phone,vehicle_type,vehicle_number',
                'stops:id,delivery_run_id,status,total_packages,recipient_name,recipient_phone,verification_code_sent_at,verification_code_expires_at,verification_attempts,max_attempts',
                'items:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,expected_quantity,delivered_quantity,status',
            ])
            ->where('warehouse_id', $warehouse->id);
    }

    /**
     * Create a delivery run directly from eligible receipt items.
     * Auto-creates a sealed local-delivery sort batch behind the scenes.
     *
     * @param array<int, int|string> $warehouseReceiptItemIds
     */
    public function createRunFromItems(
        Warehouse $warehouse,
        User $user,
        array $warehouseReceiptItemIds,
        WarehouseSortingService $sortingService
    ): array {
        if (empty($warehouseReceiptItemIds)) {
            return ['success' => false, 'message' => 'Select at least one item.'];
        }

        return DB::transaction(function () use ($warehouse, $user, $warehouseReceiptItemIds, $sortingService) {
            $batchResult = $sortingService->createBatch(
                originWarehouse: $warehouse,
                destinationWarehouse: null,
                user: $user,
                dispatchMode: SortBatch::DISPATCH_LOCAL_DELIVERY,
                notes: 'Auto-created for direct delivery run.',
            );

            if (!$batchResult['success']) {
                return $batchResult;
            }

            $batch = $batchResult['data']['batch'];

            $addResult = $sortingService->addItems(
                batch: $batch,
                warehouse: $warehouse,
                user: $user,
                warehouseReceiptItemIds: $warehouseReceiptItemIds,
            );

            if (!$addResult['success']) {
                return $addResult;
            }

            $sealResult = $sortingService->sealBatch(
                batch: $batch->fresh(),
                warehouse: $warehouse,
                user: $user,
            );

            if (!$sealResult['success']) {
                return $sealResult;
            }

            return $this->createRun($batch->fresh(), $warehouse, $user);
        });
    }

    /**
     * Create a delivery run from a driver's claimed package labels.
     * Groups packages into stops by recipient phone → town.
     */
    public function createRunFromClaims(
        Driver $driver,
        Warehouse $warehouse,
        ?User $admin = null
    ): array {
        // Get all labels currently claimed by this driver (reliable per-label check)
        $driverClaimedLabelIds = LabelCustodyEvent::query()
            ->where('driver_id', $driver->id)
            ->where('event_type', LabelCustodyEvent::TYPE_CLAIMED)
            ->distinct()
            ->pluck('warehouse_receipt_item_label_id');

        $claimedLabelIds = collect();
        foreach ($driverClaimedLabelIds as $labelId) {
            $latest = LabelCustodyEvent::where('warehouse_receipt_item_label_id', $labelId)
                ->latest('id')
                ->first();
            if ($latest && $latest->event_type === LabelCustodyEvent::TYPE_CLAIMED && $latest->driver_id === $driver->id) {
                $claimedLabelIds->push($labelId);
            }
        }

        if ($claimedLabelIds->isEmpty()) {
            return ['success' => false, 'message' => 'Driver has no claimed packages.'];
        }

        $labels = WarehouseReceiptItemLabel::whereIn('id', $claimedLabelIds)
            ->with(['receiptItem.shipmentItem.shipment.vendor', 'receiptItem.shipmentItem.busStation'])
            ->get();

        // Collect unique shipment items (a package may have multiple labels)
        $allItems = $labels->map(fn ($l) => $l->receiptItem?->shipmentItem)->filter();
        $shipmentItems = $allItems->unique('id');

        // Filter out items already in an active delivery run
        $existingRunItemIds = DeliveryRunItem::whereHas('run', fn ($q) => $q->whereNotIn('status', [DeliveryRun::STATUS_COMPLETED, DeliveryRun::STATUS_CANCELLED]))
            ->pluck('shipment_item_id');
        $shipmentItems = $shipmentItems->reject(fn ($item) => $existingRunItemIds->contains($item->id));

        if ($shipmentItems->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No valid packages found.',
                'debug' => [
                    'claimed_label_ids' => $claimedLabelIds->values(),
                    'labels_found' => $labels->count(),
                    'labels_with_receipt_item' => $labels->filter(fn ($l) => $l->receiptItem)->count(),
                    'labels_with_shipment_item' => $labels->filter(fn ($l) => $l->receiptItem?->shipmentItem)->count(),
                ],
            ];
        }

        return DB::transaction(function () use ($driver, $warehouse, $admin, $shipmentItems, $claimedLabelIds) {
            $run = DeliveryRun::query()->create([
                'run_number' => $this->generateRunNumber($warehouse),
                'warehouse_id' => $warehouse->id,
                'assigned_driver_id' => $driver->id,
                'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
                'dispatched_at' => now(),
                'created_by_user_id' => $admin?->id,
            ]);

            // Separate bus station items from direct delivery items
            $busStationItems = $shipmentItems->filter(fn ($item) => !empty($item->bus_station_id));
            $directItems = $shipmentItems->reject(fn ($item) => !empty($item->bus_station_id));

            $stopsCount = 0;

            // Create bus station stops — group by bus_station_id
            $busGroups = $busStationItems->groupBy('bus_station_id');
            foreach ($busGroups as $stationId => $items) {
                $station = $items->first()->busStation;
                $stop = DeliveryRunStop::query()->create([
                    'delivery_run_id' => $run->id,
                    'recipient_name' => $station?->name ?? 'Bus Station',
                    'recipient_phone' => '',
                    'town' => $station?->name,
                    'total_packages' => $items->count(),
                    'status' => DeliveryRunStop::STATUS_PENDING,
                    'delivery_method' => DeliveryRunStop::METHOD_BUS_HANDOFF,
                ]);

                foreach ($items as $shipmentItem) {
                    DeliveryRunItem::query()->create([
                        'delivery_run_id' => $run->id,
                        'delivery_run_stop_id' => $stop->id,
                        'shipment_item_id' => $shipmentItem->id,
                        'expected_quantity' => (int) ($shipmentItem->quantity ?: 1),
                        'status' => DeliveryRunItem::STATUS_PENDING,
                    ]);
                    $shipmentItem->update(['status' => 'out_for_delivery']);
                }
                $stopsCount++;
            }

            // Create direct delivery stops — one per package
            $sortedDirect = $directItems->sortBy(function ($item) {
                $destination = $this->resolveDeliveryDestination($item);
                $phone = preg_replace('/\D/', '', (string) ($destination['recipient_phone'] ?? ''));
                $town = mb_strtolower(trim((string) ($destination['town'] ?? '')));
                return $phone . '|' . $town;
            })->values();

            foreach ($sortedDirect as $shipmentItem) {
                $destination = $this->resolveDeliveryDestination($shipmentItem);

                $stop = DeliveryRunStop::query()->create([
                    'delivery_run_id' => $run->id,
                    'recipient_name' => (string) ($destination['recipient_name'] ?? 'Unknown Recipient'),
                    'recipient_phone' => (string) ($destination['recipient_phone'] ?? ''),
                    'region_id' => $destination['region_id'] ?? null,
                    'district_id' => $destination['district_id'] ?? null,
                    'town' => $destination['town'] ?? null,
                    'latitude' => $destination['latitude'] ?? null,
                    'longitude' => $destination['longitude'] ?? null,
                    'gh_post_address' => $destination['gh_post_address'] ?? null,
                    'landmark' => $destination['landmark'] ?? null,
                    'total_packages' => 1,
                    'status' => DeliveryRunStop::STATUS_PENDING,
                    'delivery_method' => DeliveryRunStop::METHOD_DIRECT,
                ]);

                DeliveryRunItem::query()->create([
                    'delivery_run_id' => $run->id,
                    'delivery_run_stop_id' => $stop->id,
                    'shipment_item_id' => $shipmentItem->id,
                    'expected_quantity' => (int) ($shipmentItem->quantity ?: 1),
                    'status' => DeliveryRunItem::STATUS_PENDING,
                ]);

                $shipmentItem->update(['status' => 'out_for_delivery']);
                $stopsCount++;
            }

            // Update shipment statuses to out_for_delivery
            $shipmentIds = $shipmentItems->pluck('shipment_id')->unique();
            Shipment::whereIn('id', $shipmentIds)
                ->whereNotIn('status', ['out_for_delivery', 'delivered', 'cancelled'])
                ->update(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);

            return [
                'success' => true,
                'message' => 'Delivery run created with ' . $stopsCount . ' stop(s).',
                'data' => [
                    'delivery_run_id' => $run->id,
                    'run_number' => $run->run_number,
                    'stops_count' => $stopsCount,
                    'packages_count' => $shipmentItems->count(),
                    'claimed_labels_count' => $claimedLabelIds->count(),
                    'unique_items_count' => $shipmentItems->count(),
                ],
            ];
        });
    }

    public function createRun(SortBatch $batch, Warehouse $warehouse, User $user): array
    {
        if ((int) $batch->origin_warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot create delivery run for another warehouse batch.'];
        }

        if ($batch->dispatch_mode !== SortBatch::DISPATCH_LOCAL_DELIVERY) {
            return ['success' => false, 'message' => 'Only local delivery batches can create delivery runs.'];
        }

        if ($batch->status !== SortBatch::STATUS_SEALED) {
            return ['success' => false, 'message' => 'Batch must be sealed before creating delivery run.'];
        }

        if ($batch->deliveryRun()->exists()) {
            return ['success' => false, 'message' => 'Delivery run already exists for this batch.'];
        }

        return DB::transaction(function () use ($batch, $warehouse, $user) {
            $batch = SortBatch::query()
                ->with(['activeItems.shipmentItem.shipment'])
                ->lockForUpdate()
                ->findOrFail($batch->id);

            if ($batch->deliveryRun()->exists()) {
                return ['success' => false, 'message' => 'Delivery run already exists for this batch.'];
            }

            $activeItems = $batch->activeItems;
            if ($activeItems->isEmpty()) {
                return ['success' => false, 'message' => 'Cannot create delivery run from an empty batch.'];
            }

            $run = DeliveryRun::query()->create([
                'run_number' => $this->generateRunNumber($warehouse),
                'sort_batch_id' => $batch->id,
                'warehouse_id' => $warehouse->id,
                'status' => DeliveryRun::STATUS_DRAFT,
                'created_by_user_id' => $user->id,
            ]);

            $grouped = [];
            foreach ($activeItems as $batchItem) {
                $shipmentItem = $batchItem->shipmentItem;
                if (!$shipmentItem || !$shipmentItem->shipment) {
                    continue;
                }

                $destination = $this->resolveDeliveryDestination($shipmentItem);
                $key = implode('|', [
                    mb_strtolower((string) ($destination['recipient_name'] ?? '')),
                    preg_replace('/\D/', '', (string) ($destination['recipient_phone'] ?? '')),
                    $destination['region_id'] ?? '',
                    $destination['district_id'] ?? '',
                    mb_strtolower((string) ($destination['town'] ?? '')),
                    (string) ($destination['latitude'] ?? ''),
                    (string) ($destination['longitude'] ?? ''),
                    mb_strtolower((string) ($destination['gh_post_address'] ?? '')),
                    mb_strtolower((string) ($destination['landmark'] ?? '')),
                ]);

                if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                        'destination' => $destination,
                        'items' => [],
                    ];
                }

                $grouped[$key]['items'][] = $batchItem;
            }

            foreach ($grouped as $group) {
                $destination = $group['destination'];

                $stop = DeliveryRunStop::query()->create([
                    'delivery_run_id' => $run->id,
                    'recipient_name' => (string) ($destination['recipient_name'] ?? 'Recipient'),
                    'recipient_phone' => (string) ($destination['recipient_phone'] ?? ''),
                    'region_id' => $destination['region_id'],
                    'district_id' => $destination['district_id'],
                    'town' => $destination['town'],
                    'latitude' => $destination['latitude'],
                    'longitude' => $destination['longitude'],
                    'gh_post_address' => $destination['gh_post_address'],
                    'landmark' => $destination['landmark'],
                    'status' => DeliveryRunStop::STATUS_PENDING,
                ]);

                foreach ($group['items'] as $batchItem) {
                    DeliveryRunItem::query()->create([
                        'delivery_run_id' => $run->id,
                        'delivery_run_stop_id' => $stop->id,
                        'shipment_item_id' => $batchItem->shipment_item_id,
                        'expected_quantity' => (int) $batchItem->quantity_allocated,
                        'status' => DeliveryRunItem::STATUS_PENDING,
                    ]);
                }
            }

            return [
                'success' => true,
                'message' => 'Delivery run created successfully.',
                'data' => [
                    'run' => $run->fresh([
                        'warehouse',
                        'stops.region',
                        'stops.district',
                        'items.shipmentItem.shipment',
                    ]),
                ],
            ];
        });
    }

    public function assignDriver(DeliveryRun $run, Driver $driver, Warehouse $warehouse): array
    {
        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot assign driver for another warehouse run.'];
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_DRAFT, DeliveryRun::STATUS_ASSIGNED], true)) {
            return ['success' => false, 'message' => 'Delivery run cannot be reassigned in its current state.'];
        }

        if (!$driver->is_active) {
            return ['success' => false, 'message' => 'Driver is inactive.'];
        }

        if (!$driver->hasCapability(Driver::CAPABILITY_DELIVERY)) {
            return ['success' => false, 'message' => 'Driver is not configured for delivery assignments.'];
        }

        return DB::transaction(function () use ($run, $driver) {
            $run = DeliveryRun::query()->lockForUpdate()->findOrFail($run->id);
            $previousDriverId = $run->assigned_driver_id;

            $run->update([
                'assigned_driver_id' => $driver->id,
                'assigned_at' => now(),
                'status' => DeliveryRun::STATUS_ASSIGNED,
            ]);

            if ($previousDriverId && (int) $previousDriverId !== (int) $driver->id) {
                Driver::query()->whereKey($previousDriverId)->update(['status' => 'available']);
            }

            $driver->update(['status' => 'busy']);

            return [
                'success' => true,
                'message' => 'Delivery driver assigned successfully.',
                'data' => [
                    'run' => $run->fresh(['assignedDriver', 'stops', 'items']),
                ],
            ];
        });
    }

    public function dispatch(DeliveryRun $run, Warehouse $warehouse, User $user): array
    {
        if ((int) $run->warehouse_id !== (int) $warehouse->id) {
            return ['success' => false, 'message' => 'Cannot dispatch another warehouse run.'];
        }

        if ($run->status !== DeliveryRun::STATUS_ASSIGNED) {
            return ['success' => false, 'message' => 'Only assigned runs can be dispatched.'];
        }

        if (!$run->assigned_driver_id) {
            return ['success' => false, 'message' => 'Assign a delivery driver before dispatch.'];
        }

        return DB::transaction(function () use ($run, $warehouse, $user) {
            $run = DeliveryRun::query()
                ->with(['stops', 'items.shipmentItem.shipment'])
                ->lockForUpdate()
                ->findOrFail($run->id);

            $run->update([
                'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
                'dispatched_at' => now(),
            ]);

            foreach ($run->stops as $stop) {
                // Notify recipient their items are on the way (OTP sent on driver arrival)
                $this->smsService->send(
                    (string) $stop->recipient_phone,
                    sprintf(
                        'Parcelman: Your parcel (run %s) is on its way! A delivery person will contact you shortly. A verification code will be sent when they arrive.',
                        $run->run_number
                    )
                );
            }

            $now = now();
            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();

            foreach ($run->items as $runItem) {
                $item = $runItem->shipmentItem;
                if (!$item) {
                    continue;
                }

                $item->update(['status' => ItemStatus::OUT_FOR_DELIVERY]);

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $item->id,
                    'status' => ItemStatus::OUT_FOR_DELIVERY->value,
                    'location' => $warehouse->name,
                    'notes' => 'Item dispatched for delivery run ' . $run->run_number . '.',
                    'meta' => [
                        'delivery_run_id' => $run->id,
                        'delivery_run_number' => $run->run_number,
                        'event' => 'out_for_delivery',
                    ],
                    'created_by' => "user:{$user->id}",
                    'created_at' => $now,
                ]);

                if ($item->shipment) {
                    $shipments->push($item->shipment);
                }
            }

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentOutForDeliveryStatus($shipment);
            });

            return [
                'success' => true,
                'message' => 'Delivery run dispatched successfully.',
                'data' => [
                    'run' => $run->fresh(['assignedDriver', 'stops', 'items.shipmentItem']),
                ],
            ];
        });
    }

    public function resendStopCode(DeliveryRun $run, DeliveryRunStop $stop, Warehouse $warehouse): array
    {
        if ((int) $run->warehouse_id !== (int) $warehouse->id || (int) $stop->delivery_run_id !== (int) $run->id) {
            return ['success' => false, 'message' => 'Stop not found for this warehouse run.'];
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_ASSIGNED, DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED], true)) {
            return ['success' => false, 'message' => 'Cannot resend code in the current run status.'];
        }

        return $this->verificationService->issueCode($stop, $run->run_number);
    }

    public function driverArriveStop(DeliveryRun $run, DeliveryRunStop $stop, Driver $driver): array
    {
        if ((int) $run->assigned_driver_id !== (int) $driver->id || (int) $stop->delivery_run_id !== (int) $run->id) {
            return ['success' => false, 'message' => 'Delivery stop not found.'];
        }

        // Auto-promote assigned → out_for_delivery when driver arrives at first stop
        if ($run->status === DeliveryRun::STATUS_ASSIGNED) {
            $run->update(['status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY]);
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED], true)) {
            return ['success' => false, 'message' => 'Delivery run is not active.'];
        }

        if ($stop->status === DeliveryRunStop::STATUS_DELIVERED) {
            return ['success' => false, 'message' => 'Stop already delivered.'];
        }

        $stop->update([
            'status' => DeliveryRunStop::STATUS_ARRIVED,
            'arrived_at' => now(),
        ]);

        if ($stop->delivery_method === DeliveryRunStop::METHOD_BUS_HANDOFF) {
            return [
                'success' => true,
                'message' => 'Arrival recorded. Ready for bus courier handoff.',
            ];
        }

        $this->verificationService->issueCode($stop, $run->run_number);

        return [
            'success' => true,
            'message' => 'Arrival recorded. Verification code sent to recipient.',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $linePayloads
     */
    public function driverConfirmStop(
        DeliveryRun $run,
        DeliveryRunStop $stop,
        Driver $driver,
        ?string $verificationCode,
        float $latitude,
        float $longitude,
        UploadedFile $proofPhoto,
        array $linePayloads,
        ?string $ipAddress = null,
        bool $skipVerification = false,
        ?string $skipReason = null,
        ?string $deliveryNotes = null
    ): array {
        if ((int) $run->assigned_driver_id !== (int) $driver->id || (int) $stop->delivery_run_id !== (int) $run->id) {
            return ['success' => false, 'message' => 'Delivery stop not found.'];
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED], true)) {
            return ['success' => false, 'message' => 'Delivery run is not active.'];
        }

        if ($stop->status === DeliveryRunStop::STATUS_DELIVERED) {
            return ['success' => false, 'message' => 'Stop already delivered.'];
        }

        if ($skipVerification) {
            $stop->update([
                'verification_skipped' => true,
                'verification_skip_reason' => $skipReason,
                'verification_skipped_at' => now(),
            ]);
        } else {
            $verifyResult = $this->verificationService->verifyCode(
                stop: $stop,
                code: (string) $verificationCode,
                driver: $driver,
                ipAddress: $ipAddress
            );
            if (!$verifyResult['success']) {
                return $verifyResult;
            }
        }

        return DB::transaction(function () use ($run, $stop, $driver, $latitude, $longitude, $proofPhoto, $linePayloads, $deliveryNotes) {
            $run = DeliveryRun::query()
                ->with(['items.shipmentItem.shipment', 'stops'])
                ->lockForUpdate()
                ->findOrFail($run->id);
            $stop = DeliveryRunStop::query()
                ->whereKey($stop->id)
                ->where('delivery_run_id', $run->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payloadByItemId = collect($linePayloads)
                ->filter(fn ($line) => isset($line['shipment_item_id']))
                ->keyBy(fn ($line) => (int) $line['shipment_item_id']);

            $runItems = DeliveryRunItem::query()
                ->where('delivery_run_id', $run->id)
                ->where('delivery_run_stop_id', $stop->id)
                ->with('shipmentItem.shipment')
                ->lockForUpdate()
                ->get();

            if ($runItems->isEmpty()) {
                return ['success' => false, 'message' => 'No delivery items found for this stop.'];
            }

            $upload = $this->storageService->upload(
                $proofPhoto,
                "deliveries/runs/{$run->id}/stops/{$stop->id}"
            );

            $allDelivered = true;
            $now = now();
            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();

            foreach ($runItems as $runItem) {
                $lineInput = $payloadByItemId->get((int) $runItem->shipment_item_id, []);
                $deliveredQuantity = isset($lineInput['delivered_quantity'])
                    ? (int) $lineInput['delivered_quantity']
                    : (int) $runItem->expected_quantity;

                $expected = (int) $runItem->expected_quantity;
                if ($deliveredQuantity < 0) {
                    return ['success' => false, 'message' => 'Delivered quantity cannot be negative.'];
                }

                $lineStatus = DeliveryRunItem::STATUS_DELIVERED;
                if ($deliveredQuantity <= 0) {
                    $lineStatus = DeliveryRunItem::STATUS_FAILED;
                    $allDelivered = false;
                } elseif ($deliveredQuantity < $expected) {
                    $lineStatus = DeliveryRunItem::STATUS_PARTIAL;
                    $allDelivered = false;
                }

                $runItem->update([
                    'delivered_quantity' => $deliveredQuantity,
                    'status' => $lineStatus,
                    'notes' => $lineInput['notes'] ?? $runItem->notes,
                    'delivered_at' => $now,
                ]);

                $shipmentItem = $runItem->shipmentItem;
                if ($shipmentItem) {
                    if ($lineStatus === DeliveryRunItem::STATUS_DELIVERED) {
                        $shipmentItem->update(['status' => ItemStatus::DELIVERED]);
                    } else {
                        $shipmentItem->update(['status' => ItemStatus::AT_DESTINATION]);
                    }

                    ShipmentItemTracking::query()->create([
                        'shipment_item_id' => $shipmentItem->id,
                        'status' => $lineStatus === DeliveryRunItem::STATUS_DELIVERED
                            ? ItemStatus::DELIVERED->value
                            : ItemStatus::AT_DESTINATION->value,
                        'location' => $stop->town ?: $stop->gh_post_address ?: $stop->landmark,
                        'notes' => $lineStatus === DeliveryRunItem::STATUS_DELIVERED
                            ? 'Delivery confirmed with verification code for run ' . $run->run_number . '.'
                            : 'Delivery partially/unsuccessfully confirmed; item returned to destination warehouse queue.',
                        'meta' => [
                            'delivery_run_id' => $run->id,
                            'delivery_run_number' => $run->run_number,
                            'delivery_run_stop_id' => $stop->id,
                            'expected_quantity' => $expected,
                            'delivered_quantity' => $deliveredQuantity,
                            'line_status' => $lineStatus,
                        ],
                        'created_by' => "driver:{$driver->id}",
                        'created_at' => $now,
                    ]);

                    if ($shipmentItem->shipment) {
                        $shipments->push($shipmentItem->shipment);
                    }
                }
            }

            $stop->update([
                'status' => $allDelivered ? DeliveryRunStop::STATUS_DELIVERED : DeliveryRunStop::STATUS_FAILED,
                'arrived_at' => $stop->arrived_at ?? $now,
                'delivered_at' => $now,
                'delivery_latitude' => $latitude,
                'delivery_longitude' => $longitude,
                'proof_photo_path' => $upload['path'],
                'proof_photo_size' => $upload['size'],
                'failure_reason' => $allDelivered ? null : ($stop->failure_reason ?: 'partial_quantity'),
                'failure_notes' => $allDelivered ? null : ($stop->failure_notes ?: 'One or more line items were not fully delivered.'),
                'delivery_notes' => $deliveryNotes,
            ]);

            $this->refreshRunStatus($run);
            $run->refresh();

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentDeliveryStatus($shipment);
            });

            if ($allDelivered) {
                $this->commissionService->createEarningsForStop($stop);
            }

            if ($run->status === DeliveryRun::STATUS_COMPLETED && $run->assignedDriver) {
                $run->assignedDriver->update(['status' => 'available']);
            }

            return [
                'success' => true,
                'message' => $allDelivered
                    ? 'Delivery stop confirmed successfully.'
                    : 'Delivery recorded with partial/failed line items.',
            ];
        });
    }

    public function driverConfirmStopByPackage(
        DeliveryRun $run,
        DeliveryRunStop $stop,
        Driver $driver,
        ?string $verificationCode,
        int $packagesDelivered,
        float $latitude,
        float $longitude,
        UploadedFile $proofPhoto,
        ?string $ipAddress = null,
        bool $skipVerification = false,
        ?string $skipReason = null,
        ?string $deliveryNotes = null
    ): array {
        if ((int) $run->assigned_driver_id !== (int) $driver->id || (int) $stop->delivery_run_id !== (int) $run->id) {
            return ['success' => false, 'message' => 'Delivery stop not found.'];
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED], true)) {
            return ['success' => false, 'message' => 'Delivery run is not active.'];
        }

        if ($stop->status === DeliveryRunStop::STATUS_DELIVERED) {
            return ['success' => false, 'message' => 'Stop already delivered.'];
        }

        if ($skipVerification) {
            $stop->update([
                'verification_skipped' => true,
                'verification_skip_reason' => $skipReason,
                'verification_skipped_at' => now(),
            ]);
        } else {
            $verifyResult = $this->verificationService->verifyCode(
                stop: $stop,
                code: (string) $verificationCode,
                driver: $driver,
                ipAddress: $ipAddress
            );
            if (!$verifyResult['success']) {
                return $verifyResult;
            }
        }

        return DB::transaction(function () use ($run, $stop, $driver, $packagesDelivered, $latitude, $longitude, $proofPhoto, $deliveryNotes) {
            $run = DeliveryRun::query()
                ->with(['items.shipmentItem.shipment', 'stops'])
                ->lockForUpdate()
                ->findOrFail($run->id);
            $stop = DeliveryRunStop::query()
                ->whereKey($stop->id)
                ->where('delivery_run_id', $run->id)
                ->lockForUpdate()
                ->firstOrFail();

            $runItems = DeliveryRunItem::query()
                ->where('delivery_run_id', $run->id)
                ->where('delivery_run_stop_id', $stop->id)
                ->with('shipmentItem.shipment')
                ->lockForUpdate()
                ->get();

            if ($runItems->isEmpty()) {
                return ['success' => false, 'message' => 'No delivery items found for this stop.'];
            }

            $totalPackages = (int) $stop->total_packages ?: $runItems->count();

            $upload = $this->storageService->upload(
                $proofPhoto,
                "deliveries/runs/{$run->id}/stops/{$stop->id}"
            );

            $allDelivered = $packagesDelivered >= $totalPackages;
            $noneDelivered = $packagesDelivered <= 0;
            $now = now();
            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();

            foreach ($runItems as $runItem) {
                $expected = (int) $runItem->expected_quantity;

                if ($allDelivered) {
                    $lineStatus = DeliveryRunItem::STATUS_DELIVERED;
                    $deliveredQuantity = $expected;
                } elseif ($noneDelivered) {
                    $lineStatus = DeliveryRunItem::STATUS_FAILED;
                    $deliveredQuantity = 0;
                } else {
                    // Partial — we don't know which packages, flag all for warehouse review
                    $lineStatus = DeliveryRunItem::STATUS_PARTIAL;
                    $deliveredQuantity = $expected; // Assume delivered, warehouse reviews
                }

                $runItem->update([
                    'delivered_quantity' => $deliveredQuantity,
                    'status' => $lineStatus,
                    'notes' => $noneDelivered ? 'Package not delivered.' : ($allDelivered ? null : "Partial delivery: {$packagesDelivered}/{$totalPackages} packages handed over. Flagged for warehouse review."),
                    'delivered_at' => $now,
                ]);

                $shipmentItem = $runItem->shipmentItem;
                if ($shipmentItem) {
                    if ($lineStatus === DeliveryRunItem::STATUS_DELIVERED) {
                        $shipmentItem->update(['status' => ItemStatus::DELIVERED]);
                    } else {
                        $shipmentItem->update(['status' => ItemStatus::AT_DESTINATION]);
                    }

                    $trackingNotes = match (true) {
                        $allDelivered => 'Package delivered to recipient for run ' . $run->run_number . '.',
                        $noneDelivered => 'Package could not be delivered; returned to destination warehouse.',
                        default => "Partial package delivery ({$packagesDelivered}/{$totalPackages}); flagged for warehouse review.",
                    };

                    ShipmentItemTracking::query()->create([
                        'shipment_item_id' => $shipmentItem->id,
                        'status' => $lineStatus === DeliveryRunItem::STATUS_DELIVERED
                            ? ItemStatus::DELIVERED->value
                            : ItemStatus::AT_DESTINATION->value,
                        'location' => $stop->town ?: $stop->gh_post_address ?: $stop->landmark,
                        'notes' => $trackingNotes,
                        'meta' => [
                            'delivery_run_id' => $run->id,
                            'delivery_run_number' => $run->run_number,
                            'delivery_run_stop_id' => $stop->id,
                            'packages_delivered' => $packagesDelivered,
                            'total_packages' => $totalPackages,
                            'confirmation_mode' => 'package_level',
                        ],
                        'created_by' => "driver:{$driver->id}",
                        'created_at' => $now,
                    ]);

                    if ($shipmentItem->shipment) {
                        $shipments->push($shipmentItem->shipment);
                    }
                }
            }

            $stopStatus = $allDelivered
                ? DeliveryRunStop::STATUS_DELIVERED
                : DeliveryRunStop::STATUS_FAILED;

            $stop->update([
                'status' => $stopStatus,
                'arrived_at' => $stop->arrived_at ?? $now,
                'delivered_at' => $now,
                'delivery_latitude' => $latitude,
                'delivery_longitude' => $longitude,
                'proof_photo_path' => $upload['path'],
                'proof_photo_size' => $upload['size'],
                'failure_reason' => $allDelivered ? null : ($noneDelivered ? 'no_packages_delivered' : 'partial_packages'),
                'failure_notes' => $allDelivered ? null : "{$packagesDelivered} of {$totalPackages} packages delivered. Requires warehouse review.",
                'delivery_notes' => $deliveryNotes,
            ]);

            $this->refreshRunStatus($run);
            $run->refresh();

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentDeliveryStatus($shipment);
            });

            if ($allDelivered) {
                $this->commissionService->createEarningsForStop($stop);
            }

            if ($run->status === DeliveryRun::STATUS_COMPLETED && $run->assignedDriver) {
                $run->assignedDriver->update(['status' => 'available']);
            }

            return [
                'success' => true,
                'message' => $allDelivered
                    ? 'All packages delivered successfully.'
                    : "{$packagesDelivered} of {$totalPackages} packages delivered. Flagged for warehouse review.",
            ];
        });
    }

    public function driverConfirmHandoff(
        Driver $driver,
        DeliveryRun $run,
        DeliveryRunStop $stop,
        array $data,
        $request = null,
    ): array {
        if ((int) $run->assigned_driver_id !== (int) $driver->id || (int) $stop->delivery_run_id !== (int) $run->id) {
            return ['success' => false, 'message' => 'Delivery stop not found.'];
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED, DeliveryRun::STATUS_ASSIGNED], true)) {
            return ['success' => false, 'message' => 'Delivery run is not active.'];
        }

        if ($stop->delivery_method !== DeliveryRunStop::METHOD_BUS_HANDOFF) {
            return ['success' => false, 'message' => 'This stop is not a bus handoff stop.'];
        }

        $courierName = isset($data['courier_name']) && trim((string) $data['courier_name']) !== ''
            ? trim((string) $data['courier_name'])
            : null;

        $courierPhone = isset($data['courier_phone']) && trim((string) $data['courier_phone']) !== ''
            ? (PhoneHelper::format($data['courier_phone']) ?? trim((string) $data['courier_phone']))
            : null;

        $vehicleNumber = isset($data['vehicle_number']) && trim((string) $data['vehicle_number']) !== ''
            ? strtoupper(trim((string) $data['vehicle_number']))
            : null;

        return DB::transaction(function () use ($run, $stop, $driver, $data, $request, $courierName, $courierPhone, $vehicleNumber) {
            $run = DeliveryRun::query()->with(['items.shipmentItem.shipment', 'stops'])->lockForUpdate()->findOrFail($run->id);
            $stop = DeliveryRunStop::query()->whereKey($stop->id)->where('delivery_run_id', $run->id)->lockForUpdate()->firstOrFail();

            if ($run->status === DeliveryRun::STATUS_ASSIGNED) {
                $run->update(['status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY, 'dispatched_at' => now()]);
            }

            $now = now();

            $upload = $this->storageService->upload(
                $request->file('proof_photo'),
                "deliveries/runs/{$run->id}/stops/{$stop->id}"
            );

            $stop->update([
                'status' => DeliveryRunStop::STATUS_HANDED_OFF,
                'arrived_at' => $stop->arrived_at ?? $now,
                'delivered_at' => $now,
                'delivery_latitude' => $data['latitude'] ?? null,
                'delivery_longitude' => $data['longitude'] ?? null,
                'proof_photo_path' => $upload['path'],
                'proof_photo_size' => $upload['size'],
                'handoff_courier_name' => $courierName,
                'handoff_courier_phone' => $courierPhone,
                'handoff_vehicle_number' => $vehicleNumber,
                'handoff_at' => $now,
            ]);

            $courierLabel = $this->buildCourierLabel($courierName, $vehicleNumber, $courierPhone);
            $itemNote = $courierLabel !== ''
                ? "Handed to courier: {$courierLabel}"
                : 'Handed to courier (details on proof photo)';

            $trackingNote = $courierLabel !== ''
                ? "Handed to bus courier — {$courierLabel}"
                : 'Handed to bus courier — details on proof photo';

            $runItems = DeliveryRunItem::query()
                ->where('delivery_run_id', $run->id)
                ->where('delivery_run_stop_id', $stop->id)
                ->with('shipmentItem.shipment')
                ->get();

            $shipments = collect();

            foreach ($runItems as $runItem) {
                $runItem->update([
                    'delivered_quantity' => $runItem->expected_quantity,
                    'status' => DeliveryRunItem::STATUS_HANDED_OFF,
                    'notes' => $itemNote,
                    'delivered_at' => $now,
                ]);

                if ($runItem->shipmentItem) {
                    $runItem->shipmentItem->update(['status' => ItemStatus::HANDED_TO_COURIER]);

                    ShipmentItemTracking::query()->create([
                        'shipment_item_id' => $runItem->shipmentItem->id,
                        'status' => ShipmentStatus::HANDED_TO_COURIER->value,
                        'location' => $stop->town ?: $stop->landmark,
                        'notes' => $trackingNote,
                        'meta' => [
                            'delivery_run_id' => $run->id,
                            'delivery_run_number' => $run->run_number,
                            'delivery_run_stop_id' => $stop->id,
                            'courier_name' => $courierName,
                            'courier_phone' => $courierPhone,
                            'vehicle_number' => $vehicleNumber,
                        ],
                        'created_at' => $now,
                    ]);

                    if ($runItem->shipmentItem->shipment) {
                        $shipments->push($runItem->shipmentItem->shipment);
                    }
                }
            }

            foreach ($shipments->unique('id') as $shipment) {
                $allHandedOff = $shipment->items()->whereNotIn('status', [
                    ItemStatus::HANDED_TO_COURIER->value,
                    ItemStatus::DELIVERED->value,
                ])->doesntExist();

                if ($allHandedOff) {
                    $shipment->update(['status' => ShipmentStatus::HANDED_TO_COURIER]);
                }
            }

            $this->refreshRunStatus($run);

            $message = $courierLabel !== ''
                ? "Packages handed to courier {$courierLabel} successfully."
                : 'Packages handed to courier successfully.';

            return [
                'success' => true,
                'message' => $message,
            ];
        });
    }

    public function driverFailStop(
        DeliveryRun $run,
        DeliveryRunStop $stop,
        Driver $driver,
        string $reason,
        ?string $notes = null
    ): array {
        if ((int) $run->assigned_driver_id !== (int) $driver->id || (int) $stop->delivery_run_id !== (int) $run->id) {
            return ['success' => false, 'message' => 'Delivery stop not found.'];
        }

        if (!in_array($run->status, [DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED], true)) {
            return ['success' => false, 'message' => 'Delivery run is not active.'];
        }

        return DB::transaction(function () use ($run, $stop, $driver, $reason, $notes) {
            $run = DeliveryRun::query()
                ->with(['items.shipmentItem.shipment', 'stops'])
                ->lockForUpdate()
                ->findOrFail($run->id);

            $stop = DeliveryRunStop::query()
                ->whereKey($stop->id)
                ->where('delivery_run_id', $run->id)
                ->lockForUpdate()
                ->firstOrFail();

            $runItems = DeliveryRunItem::query()
                ->where('delivery_run_id', $run->id)
                ->where('delivery_run_stop_id', $stop->id)
                ->with('shipmentItem.shipment')
                ->lockForUpdate()
                ->get();

            $now = now();
            /** @var Collection<int, Shipment> $shipments */
            $shipments = collect();

            foreach ($runItems as $runItem) {
                $runItem->update([
                    'delivered_quantity' => 0,
                    'status' => DeliveryRunItem::STATUS_FAILED,
                    'notes' => $notes,
                    'delivered_at' => $now,
                ]);

                if ($runItem->shipmentItem) {
                    $runItem->shipmentItem->update(['status' => ItemStatus::AT_DESTINATION]);

                    ShipmentItemTracking::query()->create([
                        'shipment_item_id' => $runItem->shipmentItem->id,
                        'status' => ItemStatus::AT_DESTINATION->value,
                        'location' => $stop->town ?: $stop->gh_post_address ?: $stop->landmark,
                        'notes' => 'Delivery failed for run ' . $run->run_number . ': ' . $reason,
                        'meta' => [
                            'delivery_run_id' => $run->id,
                            'delivery_run_number' => $run->run_number,
                            'delivery_run_stop_id' => $stop->id,
                            'failure_reason' => $reason,
                            'failure_notes' => $notes,
                        ],
                        'created_by' => "driver:{$driver->id}",
                        'created_at' => $now,
                    ]);

                    if ($runItem->shipmentItem->shipment) {
                        $shipments->push($runItem->shipmentItem->shipment);
                    }
                }
            }

            $stop->update([
                'status' => DeliveryRunStop::STATUS_FAILED,
                'failure_reason' => $reason,
                'failure_notes' => $notes,
            ]);

            $this->refreshRunStatus($run);

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentDeliveryStatus($shipment);
            });

            return [
                'success' => true,
                'message' => 'Delivery stop marked as failed.',
            ];
        });
    }

    private function resolveDeliveryDestination(ShipmentItem $shipmentItem): array
    {
        $shipment = $shipmentItem->shipment;
        $perItem = $shipment?->destination_mode === ShipmentDestinationMode::PER_ITEM;

        if ($perItem) {
            return [
                'recipient_name' => $shipmentItem->delivery_recipient_name,
                'recipient_phone' => $shipmentItem->delivery_recipient_phone,
                'region_id' => $shipmentItem->delivery_region_id,
                'district_id' => $shipmentItem->delivery_district_id,
                'town' => $shipmentItem->delivery_town,
                'latitude' => $shipmentItem->delivery_latitude,
                'longitude' => $shipmentItem->delivery_longitude,
                'gh_post_address' => $shipmentItem->delivery_gh_post_address,
                'landmark' => $shipmentItem->delivery_landmark,
            ];
        }

        return [
            'recipient_name' => $shipment?->delivery_recipient_name,
            'recipient_phone' => $shipment?->delivery_recipient_phone,
            'region_id' => $shipment?->delivery_region_id,
            'district_id' => $shipment?->delivery_district_id,
            'town' => $shipment?->delivery_town,
            'latitude' => $shipment?->delivery_latitude,
            'longitude' => $shipment?->delivery_longitude,
            'gh_post_address' => $shipment?->delivery_gh_post_address,
            'landmark' => $shipment?->delivery_landmark,
        ];
    }

    private function generateRunNumber(Warehouse $warehouse): string
    {
        $year = now()->format('Y');
        $warehouseCode = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($warehouse->code ?: $warehouse->id)));
        $prefix = "DR-{$year}-{$warehouseCode}-";

        $last = DeliveryRun::query()
            ->where('run_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $next = 1;
        if ($last) {
            $parts = explode('-', $last->run_number);
            $next = ((int) end($parts)) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function buildCourierLabel(?string $name, ?string $vehicle, ?string $phone): string
    {
        $head = $name ?: '';
        $bits = array_filter([$vehicle, $phone], fn($v) => $v !== null && $v !== '');

        if ($head !== '' && !empty($bits)) {
            return $head . ' (' . implode(', ', $bits) . ')';
        }

        if ($head !== '') {
            return $head;
        }

        return implode(', ', $bits);
    }

    private function refreshRunStatus(DeliveryRun $run): void
    {
        $run->load('stops');

        $totalStops = $run->stops->count();
        $deliveredStops = $run->stops->where('status', DeliveryRunStop::STATUS_DELIVERED)->count();
        $failedStops = $run->stops->where('status', DeliveryRunStop::STATUS_FAILED)->count();
        $handedOffStops = $run->stops->where('status', DeliveryRunStop::STATUS_HANDED_OFF)->count();
        // handed_off counts as "driver done" but NOT "delivery complete" — run stays partially_delivered
        $driverDoneStops = $deliveredStops + $failedStops + $handedOffStops;
        $completedStops = $deliveredStops + $failedStops;

        if ($totalStops > 0 && $completedStops === $totalStops) {
            $run->update([
                'status' => DeliveryRun::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return;
        }

        if ($completedStops > 0) {
            $run->update(['status' => DeliveryRun::STATUS_PARTIALLY_DELIVERED]);
            return;
        }

        if ($run->status !== DeliveryRun::STATUS_OUT_FOR_DELIVERY) {
            $run->update(['status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY]);
        }
    }

    private function syncShipmentOutForDeliveryStatus(Shipment $shipment): void
    {
        $allOutOrBeyond = !$shipment->items()
            ->whereNotIn('status', [
                ItemStatus::OUT_FOR_DELIVERY->value,
                ItemStatus::DELIVERED->value,
                ItemStatus::RETURNED->value,
            ])
            ->exists();

        if ($allOutOrBeyond && $shipment->status !== ShipmentStatus::OUT_FOR_DELIVERY) {
            $shipment->update(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);
        }
    }

    private function syncShipmentDeliveryStatus(Shipment $shipment): void
    {
        $allDelivered = !$shipment->items()
            ->whereNotIn('status', [
                ItemStatus::DELIVERED->value,
                ItemStatus::RETURNED->value,
            ])
            ->exists();

        if ($allDelivered) {
            if ($shipment->status !== ShipmentStatus::DELIVERED) {
                $shipment->update(['status' => ShipmentStatus::DELIVERED]);
            }
            return;
        }

        $anyOutForDelivery = $shipment->items()
            ->where('status', ItemStatus::OUT_FOR_DELIVERY->value)
            ->exists();

        if ($anyOutForDelivery) {
            if ($shipment->status !== ShipmentStatus::OUT_FOR_DELIVERY) {
                $shipment->update(['status' => ShipmentStatus::OUT_FOR_DELIVERY]);
            }
            return;
        }

        if ($shipment->status === ShipmentStatus::DELIVERED || $shipment->status === ShipmentStatus::OUT_FOR_DELIVERY) {
            $shipment->update(['status' => ShipmentStatus::AT_DESTINATION]);
        }
    }
}

