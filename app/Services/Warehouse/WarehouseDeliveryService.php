<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StorageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WarehouseDeliveryService
{
    public function __construct(
        private DeliveryVerificationService $verificationService,
        private StorageService $storageService
    ) {
    }

    public function runsQuery(Warehouse $warehouse): Builder
    {
        return DeliveryRun::query()
            ->with([
                'warehouse:id,name,code',
                'assignedDriver:id,name,phone,vehicle_type,vehicle_number',
                'stops:id,delivery_run_id,status,recipient_name,recipient_phone,verification_code_sent_at,verification_code_expires_at,verification_attempts,max_attempts',
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

        if ($driver->status === 'busy' && (int) $run->assigned_driver_id !== (int) $driver->id) {
            return ['success' => false, 'message' => 'Driver is currently busy.'];
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
                $this->verificationService->issueCode($stop, $run->run_number);
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

        return [
            'success' => true,
            'message' => 'Arrival at recipient stop recorded.',
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $linePayloads
     */
    public function driverConfirmStop(
        DeliveryRun $run,
        DeliveryRunStop $stop,
        Driver $driver,
        string $verificationCode,
        float $latitude,
        float $longitude,
        UploadedFile $proofPhoto,
        array $linePayloads,
        ?string $ipAddress = null
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

        $verifyResult = $this->verificationService->verifyCode(
            stop: $stop,
            code: $verificationCode,
            driver: $driver,
            ipAddress: $ipAddress
        );
        if (!$verifyResult['success']) {
            return $verifyResult;
        }

        return DB::transaction(function () use ($run, $stop, $driver, $latitude, $longitude, $proofPhoto, $linePayloads) {
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
            ]);

            $this->refreshRunStatus($run);
            $run->refresh();

            $shipments->unique('id')->each(function (Shipment $shipment) {
                $this->syncShipmentDeliveryStatus($shipment);
            });

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

    private function refreshRunStatus(DeliveryRun $run): void
    {
        $run->load('stops');

        $totalStops = $run->stops->count();
        $deliveredStops = $run->stops->where('status', DeliveryRunStop::STATUS_DELIVERED)->count();
        $failedStops = $run->stops->where('status', DeliveryRunStop::STATUS_FAILED)->count();

        if ($totalStops > 0 && $deliveredStops === $totalStops) {
            $run->update([
                'status' => DeliveryRun::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
            return;
        }

        if ($deliveredStops > 0 || $failedStops > 0) {
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

