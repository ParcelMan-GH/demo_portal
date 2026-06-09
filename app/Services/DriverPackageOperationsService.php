<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\NotificationLog;
use App\Models\RiderPackageLocationChange;
use App\Models\RiderPackageTransfer;
use App\Models\RiderTeamHandoverItem;
use App\Models\ShipmentItem;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverPackageOperationsService
{
    public function __construct(
        private readonly StorageService $storageService,
        private readonly PushNotificationService $pushNotificationService,
    ) {}

    public function changeLocation(Driver $driver, string $trackingCode, array $data, UploadedFile $proofPhoto): RiderPackageLocationChange
    {
        return DB::transaction(function () use ($driver, $trackingCode, $data, $proofPhoto) {
            $item = $this->heldShipmentItem($driver, $trackingCode);
            $this->assertEditableBeforeDelivery($item);
            $this->assertNoPendingTransfer($item);

            $oldLocation = $this->locationSnapshot($item);
            $newLocation = [
                'town' => trim((string) $data['delivery_town']),
                'region_id' => $data['delivery_region_id'] ?? null,
                'district_id' => $data['delivery_district_id'] ?? null,
                'latitude' => null,
                'longitude' => null,
                'gh_post_address' => null,
                'landmark' => $item->delivery_landmark,
                'instructions' => $item->delivery_instructions,
            ];

            $upload = $this->storageService->upload($proofPhoto, "rider-package-location-changes/{$item->id}");

            $item->forceFill([
                'delivery_town' => $newLocation['town'],
                'delivery_region_id' => $newLocation['region_id'],
                'delivery_district_id' => $newLocation['district_id'],
                'delivery_latitude' => null,
                'delivery_longitude' => null,
                'delivery_gh_post_address' => null,
            ])->save();

            return RiderPackageLocationChange::create([
                'shipment_item_id' => $item->id,
                'driver_id' => $driver->id,
                'old_location' => $oldLocation,
                'new_location' => $this->locationSnapshot($item->fresh(['deliveryRegion', 'deliveryDistrict'])),
                'proof_photo_path' => $upload['path'],
                'proof_photo_size' => $upload['size'] ?? null,
                'changed_at' => now(),
            ]);
        });
    }

    public function requestTransfer(Driver $driver, string $trackingCode, string $receiverPhone): RiderPackageTransfer
    {
        return DB::transaction(function () use ($driver, $trackingCode, $receiverPhone) {
            $phone = PhoneHelper::format($receiverPhone);
            if (! $phone) {
                throw ValidationException::withMessages(['receiver_phone' => 'Enter a valid Ghana phone number.']);
            }

            $receiver = Driver::query()
                ->where('phone', $phone)
                ->where('is_active', true)
                ->first();

            if (! $receiver) {
                throw ValidationException::withMessages(['receiver_phone' => 'No active rider was found with that phone number.']);
            }

            if ((int) $receiver->id === (int) $driver->id) {
                throw ValidationException::withMessages(['receiver_phone' => 'You cannot transfer a package to yourself.']);
            }

            $item = $this->heldShipmentItem($driver, $trackingCode);
            $this->assertEditableBeforeDelivery($item);
            $this->assertNoPendingTransfer($item);
            $this->assertDriverHoldsAllLabels($driver, $item);

            $transfer = RiderPackageTransfer::create([
                'shipment_item_id' => $item->id,
                'from_driver_id' => $driver->id,
                'to_driver_id' => $receiver->id,
                'status' => RiderPackageTransfer::STATUS_PENDING,
                'requested_at' => now(),
            ]);

            $title = 'Package transfer request';
            $body = "{$driver->name} wants to transfer {$item->tracking_code} to you.";
            $data = [
                'transfer_id' => (string) $transfer->id,
                'tracking_code' => (string) $item->tracking_code,
                'screen' => 'driver_package_transfers',
            ];

            if (! $receiver->fcm_token) {
                NotificationLog::create([
                    'notifiable_type' => Driver::class,
                    'notifiable_id' => $receiver->id,
                    'type' => 'package_transfer',
                    'channel' => 'app',
                    'title' => $title,
                    'body' => $body,
                    'data' => $data,
                    'status' => 'queued',
                ]);
            } else {
                $this->pushNotificationService->sendToDriver($receiver, $title, $body, $data, 'package_transfer');
            }

            return $transfer->fresh(['shipmentItem', 'fromDriver', 'toDriver']);
        });
    }

    public function acceptTransfer(Driver $driver, RiderPackageTransfer $transfer): RiderPackageTransfer
    {
        return DB::transaction(function () use ($driver, $transfer) {
            $transfer = RiderPackageTransfer::lockForUpdate()
                ->with(['shipmentItem', 'fromDriver', 'toDriver'])
                ->findOrFail($transfer->id);

            $this->assertTransferReceiver($driver, $transfer);
            $this->assertTransferPending($transfer);
            $this->assertEditableBeforeDelivery($transfer->shipmentItem);
            $this->assertDriverHoldsAllLabels($transfer->fromDriver, $transfer->shipmentItem);

            $labels = $this->labelsForItem($transfer->shipmentItem);
            foreach ($labels as $label) {
                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $label->id,
                    'event_type' => LabelCustodyEvent::TYPE_TRANSFERRED,
                    'driver_id' => $transfer->from_driver_id,
                    'notes' => "Transferred to {$driver->name}",
                ]);

                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $label->id,
                    'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
                    'driver_id' => $driver->id,
                    'notes' => "Accepted transfer from {$transfer->fromDriver?->name}",
                ]);

                if ($label->riderTeamHandoverItem && ! in_array($label->riderTeamHandoverItem->status, [
                    RiderTeamHandoverItem::STATUS_DELIVERED,
                    RiderTeamHandoverItem::STATUS_RETURNED,
                    RiderTeamHandoverItem::STATUS_RECALLED,
                ], true)) {
                    $label->riderTeamHandoverItem->update([
                        'allocated_to_driver_id' => $driver->id,
                        'status' => RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED,
                        'member_claimed_at' => $label->riderTeamHandoverItem->member_claimed_at ?: now(),
                    ]);
                }
            }

            $transfer->update([
                'status' => RiderPackageTransfer::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            return $transfer->fresh(['shipmentItem', 'fromDriver', 'toDriver']);
        });
    }

    public function rejectTransfer(Driver $driver, RiderPackageTransfer $transfer): RiderPackageTransfer
    {
        return DB::transaction(function () use ($driver, $transfer) {
            $transfer = RiderPackageTransfer::lockForUpdate()
                ->with(['shipmentItem', 'fromDriver', 'toDriver'])
                ->findOrFail($transfer->id);

            $this->assertTransferReceiver($driver, $transfer);
            $this->assertTransferPending($transfer);

            $transfer->update([
                'status' => RiderPackageTransfer::STATUS_REJECTED,
                'responded_at' => now(),
            ]);

            return $transfer->fresh(['shipmentItem', 'fromDriver', 'toDriver']);
        });
    }

    public function cancelTransfer(Driver $driver, RiderPackageTransfer $transfer): RiderPackageTransfer
    {
        return DB::transaction(function () use ($driver, $transfer) {
            $transfer = RiderPackageTransfer::lockForUpdate()
                ->with(['shipmentItem', 'fromDriver', 'toDriver'])
                ->findOrFail($transfer->id);

            $this->assertTransferSender($driver, $transfer);
            $this->assertTransferPending($transfer);

            $transfer->update([
                'status' => RiderPackageTransfer::STATUS_CANCELLED,
                'responded_at' => now(),
            ]);

            $this->notifyTransferDriver(
                $transfer->toDriver,
                'Transfer request cancelled',
                "{$driver->name} cancelled the transfer request for {$transfer->shipmentItem?->tracking_code}.",
                $transfer,
                'package_transfer_cancelled'
            );

            return $transfer->fresh(['shipmentItem', 'fromDriver', 'toDriver']);
        });
    }

    public function recallTransfer(Driver $driver, RiderPackageTransfer $transfer): RiderPackageTransfer
    {
        return DB::transaction(function () use ($driver, $transfer) {
            $transfer = RiderPackageTransfer::lockForUpdate()
                ->with(['shipmentItem', 'fromDriver', 'toDriver'])
                ->findOrFail($transfer->id);

            $this->assertTransferSender($driver, $transfer);

            if ($transfer->status !== RiderPackageTransfer::STATUS_ACCEPTED) {
                throw ValidationException::withMessages(['transfer' => 'Only accepted transfers can be recalled.']);
            }

            $this->assertEditableBeforeDelivery($transfer->shipmentItem);
            $this->assertDriverHoldsAllLabels($transfer->toDriver, $transfer->shipmentItem);

            $labels = $this->labelsForItem($transfer->shipmentItem);
            foreach ($labels as $label) {
                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $label->id,
                    'event_type' => LabelCustodyEvent::TYPE_TRANSFERRED,
                    'driver_id' => $transfer->to_driver_id,
                    'notes' => "Recalled by {$driver->name}",
                ]);

                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $label->id,
                    'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
                    'driver_id' => $driver->id,
                    'notes' => "Recalled from {$transfer->toDriver?->name}",
                ]);

                if ($label->riderTeamHandoverItem && ! in_array($label->riderTeamHandoverItem->status, [
                    RiderTeamHandoverItem::STATUS_DELIVERED,
                    RiderTeamHandoverItem::STATUS_RETURNED,
                    RiderTeamHandoverItem::STATUS_RECALLED,
                ], true)) {
                    $label->riderTeamHandoverItem->update([
                        'allocated_to_driver_id' => $driver->id,
                        'status' => RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED,
                    ]);
                }
            }

            $transfer->update([
                'status' => RiderPackageTransfer::STATUS_RECALLED,
                'responded_at' => now(),
            ]);

            $this->notifyTransferDriver(
                $transfer->toDriver,
                'Package transfer recalled',
                "{$driver->name} recalled {$transfer->shipmentItem?->tracking_code}.",
                $transfer,
                'package_transfer_recalled'
            );

            return $transfer->fresh(['shipmentItem', 'fromDriver', 'toDriver']);
        });
    }

    public function pendingTransferForItem(int $shipmentItemId): ?RiderPackageTransfer
    {
        return RiderPackageTransfer::query()
            ->with(['fromRider:id,name,phone', 'toRider:id,name,phone'])
            ->where('shipment_item_id', $shipmentItemId)
            ->where('status', RiderPackageTransfer::STATUS_PENDING)
            ->latest('requested_at')
            ->first();
    }

    public function hasPendingTransferFromDriver(int $driverId, Collection $shipmentItemIds): bool
    {
        return RiderPackageTransfer::query()
            ->whereIn('shipment_item_id', $shipmentItemIds)
            ->where('from_driver_id', $driverId)
            ->where('status', RiderPackageTransfer::STATUS_PENDING)
            ->exists();
    }

    private function heldShipmentItem(Driver $driver, string $trackingCode): ShipmentItem
    {
        $trackingCode = trim($trackingCode);

        $item = ShipmentItem::query()
            ->with(['shipment', 'deliveryRegion', 'deliveryDistrict'])
            ->where('tracking_code', $trackingCode)
            ->first();

        if (! $item) {
            $label = WarehouseReceiptItemLabel::query()
                ->with('receiptItem.shipmentItem.shipment')
                ->where('barcode_value', $trackingCode)
                ->first();
            $item = $label?->receiptItem?->shipmentItem;
        }

        if (! $item) {
            throw ValidationException::withMessages(['package' => 'Package not found.']);
        }

        $labels = $this->labelsForItem($item);
        $holdsAny = $labels->contains(fn (WarehouseReceiptItemLabel $label) => $this->driverHasEffectiveCustody($driver, $label));

        if (! $holdsAny) {
            throw ValidationException::withMessages(['package' => 'You do not currently hold this package.']);
        }

        return $item;
    }

    private function assertEditableBeforeDelivery(ShipmentItem $item): void
    {
        $active = DeliveryRunItem::query()
            ->where('shipment_item_id', $item->id)
            ->whereHas('run', fn ($query) => $query->whereNotIn('status', [
                DeliveryRun::STATUS_COMPLETED,
                DeliveryRun::STATUS_CANCELLED,
            ]))
            ->exists();

        if ($active) {
            throw ValidationException::withMessages([
                'package' => 'This package is already in an active delivery run.',
            ]);
        }

        $final = DeliveryRunItem::query()
            ->where('shipment_item_id', $item->id)
            ->whereIn('status', [
                DeliveryRunItem::STATUS_DELIVERED,
                DeliveryRunItem::STATUS_FAILED,
                DeliveryRunItem::STATUS_HANDED_OFF,
            ])
            ->exists();

        if ($final) {
            throw ValidationException::withMessages([
                'package' => 'This package has already reached a final delivery state.',
            ]);
        }
    }

    private function assertNoPendingTransfer(ShipmentItem $item): void
    {
        if ($this->pendingTransferForItem($item->id)) {
            throw ValidationException::withMessages([
                'package' => 'This package already has a pending rider transfer.',
            ]);
        }
    }

    private function assertDriverHoldsAllLabels(Driver $driver, ShipmentItem $item): void
    {
        $labels = $this->labelsForItem($item);

        if ($labels->isEmpty()) {
            throw ValidationException::withMessages(['package' => 'Package labels are missing.']);
        }

        $notHeld = $labels->contains(fn (WarehouseReceiptItemLabel $label) => ! $this->driverHasEffectiveCustody($driver, $label));

        if ($notHeld) {
            throw ValidationException::withMessages([
                'package' => 'You must hold all labels for this package before transferring it.',
            ]);
        }
    }

    private function assertTransferReceiver(Driver $driver, RiderPackageTransfer $transfer): void
    {
        if ((int) $transfer->to_driver_id !== (int) $driver->id) {
            throw ValidationException::withMessages(['transfer' => 'This transfer is not assigned to you.']);
        }
    }

    private function assertTransferSender(Driver $driver, RiderPackageTransfer $transfer): void
    {
        if ((int) $transfer->from_driver_id !== (int) $driver->id) {
            throw ValidationException::withMessages(['transfer' => 'This transfer was not sent by you.']);
        }
    }

    private function assertTransferPending(RiderPackageTransfer $transfer): void
    {
        if ($transfer->status !== RiderPackageTransfer::STATUS_PENDING) {
            throw ValidationException::withMessages(['transfer' => 'This transfer has already been resolved.']);
        }
    }

    private function labelsForItem(ShipmentItem $item): Collection
    {
        return WarehouseReceiptItemLabel::query()
            ->with(['latestCustody', 'riderTeamHandoverItem'])
            ->whereHas('receiptItem', fn ($query) => $query->where('shipment_item_id', $item->id))
            ->get();
    }

    private function notifyTransferDriver(?Driver $driver, string $title, string $body, RiderPackageTransfer $transfer, string $type): void
    {
        if (! $driver) {
            return;
        }

        $data = [
            'transfer_id' => (string) $transfer->id,
            'tracking_code' => (string) $transfer->shipmentItem?->tracking_code,
            'screen' => 'driver_package_transfers',
        ];

        if (! $driver->fcm_token) {
            NotificationLog::create([
                'notifiable_type' => Driver::class,
                'notifiable_id' => $driver->id,
                'type' => $type,
                'channel' => 'app',
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'status' => 'queued',
            ]);

            return;
        }

        $this->pushNotificationService->sendToDriver($driver, $title, $body, $data, $type);
    }

    private function driverHasEffectiveCustody(Driver $driver, WarehouseReceiptItemLabel $label): bool
    {
        $latest = $label->latestCustody;
        if ($latest && $latest->event_type === LabelCustodyEvent::TYPE_CLAIMED && (int) $latest->driver_id === (int) $driver->id) {
            return true;
        }

        $handoverItem = $label->riderTeamHandoverItem;

        return $handoverItem
            && (int) $handoverItem->allocated_to_driver_id === (int) $driver->id
            && $handoverItem->status === RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER;
    }

    private function locationSnapshot(ShipmentItem $item): array
    {
        $item->loadMissing(['shipment', 'deliveryRegion', 'deliveryDistrict']);
        $shipment = $item->shipment;

        return [
            'town' => $item->delivery_town ?: $shipment?->delivery_town,
            'region_id' => $item->delivery_region_id,
            'region' => $item->deliveryRegion?->name,
            'district_id' => $item->delivery_district_id,
            'district' => $item->deliveryDistrict?->name,
            'latitude' => $item->delivery_latitude,
            'longitude' => $item->delivery_longitude,
            'gh_post_address' => $item->delivery_gh_post_address,
            'landmark' => $item->delivery_landmark,
            'instructions' => $item->delivery_instructions,
        ];
    }
}
