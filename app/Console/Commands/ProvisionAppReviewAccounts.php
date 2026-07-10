<?php

namespace App\Console\Commands;

use App\Enums\FulfillmentType;
use App\Enums\ItemStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\OtpCode;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\AppReviewAccessService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class ProvisionAppReviewAccounts extends Command
{
    private const NOTE_PREFIX = '[APP REVIEW:';

    protected $signature = 'app-review:provision
                            {--reset : Recreate fictional sample records and revoke existing review tokens}
                            {--cleanup : Remove fictional sample records and deactivate the review accounts}';

    protected $description = 'Provision or reset the production App Store review accounts';

    public function handle(AppReviewAccessService $accessService): int
    {
        if ($this->option('reset') && $this->option('cleanup')) {
            $this->error('Use either --reset or --cleanup, not both.');

            return self::INVALID;
        }

        try {
            $credentials = $accessService->credentials();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($this->option('cleanup')) {
            DB::transaction(fn () => $this->cleanup($credentials, deactivate: true));
            $this->info('App Review sample records were removed and both accounts were deactivated.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($credentials): void {
            [$vendor, $rider] = $this->provisionAccounts($credentials);

            if ($this->option('reset')) {
                $this->removeSampleRecords($vendor, $rider);
                $vendor->tokens()->delete();
                $rider->tokens()->delete();
                OtpCode::where('phone', $vendor->phone)->delete();
            }

            $shipments = $this->provisionShipments($vendor, $rider);
            $this->provisionNotifications($vendor, $rider, $shipments);
        });

        $this->info('App Review accounts and fictional sample records are ready.');

        return self::SUCCESS;
    }

    /**
     * @param  array{vendor_phone: string, vendor_otp: string, rider_phone: string, rider_password: string}  $credentials
     * @return array{Vendor, Driver}
     */
    private function provisionAccounts(array $credentials): array
    {
        $vendor = Vendor::withTrashed()->firstOrNew(['phone' => $credentials['vendor_phone']]);
        $vendor->fill([
            'name' => 'Apple Review Vendor',
            'business_name' => 'ParcelMan App Review Shop',
            'email' => null,
            'is_active' => true,
            'fcm_token' => null,
        ]);
        $vendor->deleted_at = null;
        $vendor->save();

        $rider = Driver::firstOrNew(['phone' => $credentials['rider_phone']]);
        $rider->fill([
            'name' => 'Apple Review Rider',
            'email' => null,
            'password' => Hash::make($credentials['rider_password']),
            'vehicle_type' => 'motorcycle',
            'vehicle_number' => 'REVIEW-01',
            'license_number' => 'APP-REVIEW',
            'base_location' => 'Accra Review Zone',
            'status' => 'available',
            'is_active' => true,
            'task_capabilities' => Driver::CAPABILITIES,
            'fcm_token' => null,
        ]);
        $rider->save();

        return [$vendor, $rider];
    }

    /**
     * @return array<string, Shipment>
     */
    private function provisionShipments(Vendor $vendor, Driver $rider): array
    {
        $definitions = [
            'draft' => [ShipmentStatus::DRAFT, ItemStatus::PENDING, 7, null],
            'submitted' => [ShipmentStatus::SUBMITTED, ItemStatus::PENDING, 6, null],
            'pickup' => [ShipmentStatus::PICKUP_ASSIGNED, ItemStatus::PENDING, 4, PickupAssignmentStatus::ASSIGNED],
            'picked-up' => [ShipmentStatus::PICKED_UP, ItemStatus::PICKED_UP, 3, PickupAssignmentStatus::COMPLETED],
            'in-transit' => [ShipmentStatus::IN_TRANSIT, ItemStatus::IN_TRANSIT, 2, PickupAssignmentStatus::COMPLETED],
            'delivered' => [ShipmentStatus::DELIVERED, ItemStatus::DELIVERED, 1, PickupAssignmentStatus::COMPLETED],
        ];

        $warehouseId = Warehouse::query()->where('is_active', true)->value('id');
        $shipments = [];

        foreach ($definitions as $index => $definition) {
            [$shipmentStatus, $itemStatus, $daysAgo, $assignmentStatus] = $definition;
            $key = (string) $index;
            $note = self::NOTE_PREFIX.strtoupper($key).'] DO NOT PROCESS';
            $createdAt = now()->subDays($daysAgo)->setTime(10, 0);

            $shipment = Shipment::query()
                ->where('vendor_id', $vendor->id)
                ->where('sender_notes', $note)
                ->first();

            Shipment::withoutEvents(function () use (&$shipment, $vendor, $shipmentStatus, $note, $createdAt, $key): void {
                $shipment ??= new Shipment([
                    'vendor_id' => $vendor->id,
                    'shipment_number' => Shipment::generateShipmentNumber(),
                ]);

                $shipment->fill([
                    'status' => $shipmentStatus,
                    'source' => ShipmentSource::VENDOR_APP,
                    'fulfillment_type' => FulfillmentType::WAREHOUSE,
                    'destination_mode' => ShipmentDestinationMode::SINGLE,
                    'pickup_contact_name' => 'Apple Review Vendor',
                    'pickup_contact_phone' => $vendor->phone,
                    'pickup_town' => 'Osu',
                    'pickup_landmark' => 'APP REVIEW - fictional pickup point',
                    'pickup_instructions' => 'App Review sample only. Do not dispatch.',
                    'delivery_recipient_name' => 'Review Recipient '.ucfirst($key),
                    'delivery_recipient_phone' => '+233200000099',
                    'delivery_town' => 'Airport Residential Area',
                    'delivery_landmark' => 'APP REVIEW - fictional destination',
                    'delivery_instructions' => 'Fictional App Review record. Do not deliver.',
                    'delivery_preference' => 'deliver',
                    'sender_notes' => $note,
                    'vendor_declared_quantity' => 1,
                    'submitted_at' => $shipmentStatus === ShipmentStatus::DRAFT ? null : $createdAt->copy()->addMinutes(5),
                ]);

                if (! $shipment->exists) {
                    $shipment->created_at = $createdAt;
                }

                $shipment->save();
            });

            $item = ShipmentItem::query()->firstOrNew([
                'shipment_id' => $shipment->id,
                'description' => 'App Review sample package',
            ]);

            ShipmentItem::withoutEvents(function () use ($item, $itemStatus, $shipment, $key): void {
                $item->fill([
                    'quantity' => 1,
                    'delivery_recipient_name' => $shipment->delivery_recipient_name,
                    'delivery_recipient_phone' => $shipment->delivery_recipient_phone,
                    'delivery_town' => $shipment->delivery_town,
                    'delivery_landmark' => $shipment->delivery_landmark,
                    'delivery_instructions' => $shipment->delivery_instructions,
                    'fulfillment_type' => FulfillmentType::WAREHOUSE,
                    'delivery_preference' => 'deliver',
                    'status' => $itemStatus,
                    'tracking_code' => $itemStatus === ItemStatus::PENDING
                        ? null
                        : sprintf('RVW%06d%02d', $shipment->vendor_id, array_search($key, ['draft', 'submitted', 'pickup', 'picked-up', 'in-transit', 'delivered'], true)),
                ]);
                $item->save();
            });

            if ($assignmentStatus) {
                $assignment = PickupAssignment::query()->firstOrNew([
                    'shipment_id' => $shipment->id,
                    'driver_id' => $rider->id,
                ]);

                PickupAssignment::withoutEvents(function () use ($assignment, $assignmentStatus, $warehouseId, $createdAt): void {
                    $isCompleted = $assignmentStatus === PickupAssignmentStatus::COMPLETED;
                    $assignment->fill([
                        'target_warehouse_id' => $warehouseId,
                        'status' => $assignmentStatus,
                        'assigned_at' => $createdAt->copy()->addMinutes(15),
                        'en_route_at' => $isCompleted ? $createdAt->copy()->addMinutes(30) : null,
                        'arrived_at' => $isCompleted ? $createdAt->copy()->addMinutes(45) : null,
                        'picked_up_at' => $isCompleted ? $createdAt->copy()->addHour() : null,
                        'completed_at' => $isCompleted ? $createdAt->copy()->addHour() : null,
                        'pickup_latitude' => $isCompleted ? 5.55602000 : null,
                        'pickup_longitude' => $isCompleted ? -0.18274000 : null,
                        'driver_picked_quantity' => $isCompleted ? 1 : null,
                        'notes' => '[APP REVIEW] Fictional assignment. Do not process.',
                    ]);
                    $assignment->save();
                });
            }

            $shipments[$key] = $shipment;
        }

        return $shipments;
    }

    /**
     * @param  array<string, Shipment>  $shipments
     */
    private function provisionNotifications(Vendor $vendor, Driver $rider, array $shipments): void
    {
        $notifications = [
            [$vendor, 'shipment_status', '[App Review] Parcel submitted', 'Your fictional review parcel is ready to inspect.', $shipments['submitted']],
            [$vendor, 'shipment_status', '[App Review] Parcel in transit', 'Your fictional review parcel is in transit.', $shipments['in-transit']],
            [$vendor, 'shipment_status', '[App Review] Parcel delivered', 'Your fictional review parcel was delivered.', $shipments['delivered']],
            [$rider, 'pickup_assigned', '[App Review] Pickup assigned', 'A fictional review pickup is ready to inspect.', $shipments['pickup']],
            [$rider, 'shipment_status', '[App Review] Package update', 'Review package history and available rider tools.', $shipments['picked-up']],
        ];

        foreach ($notifications as [$notifiable, $type, $title, $body, $shipment]) {
            NotificationLog::query()->updateOrCreate(
                [
                    'notifiable_type' => $notifiable::class,
                    'notifiable_id' => $notifiable->id,
                    'title' => $title,
                ],
                [
                    'type' => $type,
                    'channel' => 'push',
                    'body' => $body,
                    'data' => [
                        'app_review_demo' => true,
                        'shipment_id' => $shipment->id,
                    ],
                    'status' => 'logged',
                    'error' => null,
                    'read_at' => null,
                ]
            );
        }
    }

    /**
     * @param  array{vendor_phone: string, vendor_otp: string, rider_phone: string, rider_password: string}  $credentials
     */
    private function cleanup(array $credentials, bool $deactivate): void
    {
        $vendor = Vendor::withTrashed()->where('phone', $credentials['vendor_phone'])->first();
        $rider = Driver::where('phone', $credentials['rider_phone'])->first();

        if ($vendor && $rider) {
            $this->removeSampleRecords($vendor, $rider);
        } elseif ($vendor) {
            $this->removeSampleRecords($vendor, new Driver);
        } elseif ($rider) {
            $this->removeReviewNotifications($rider);
        }

        OtpCode::where('phone', $credentials['vendor_phone'])->delete();

        if ($deactivate) {
            $vendor?->tokens()->delete();
            $rider?->tokens()->delete();
            $vendor?->forceFill(['is_active' => false])->save();
            $rider?->forceFill(['is_active' => false, 'status' => 'offline'])->save();
        }
    }

    private function removeSampleRecords(Vendor $vendor, Driver $rider): void
    {
        Shipment::query()
            ->where('vendor_id', $vendor->id)
            ->where('sender_notes', 'like', self::NOTE_PREFIX.'%')
            ->get()
            ->each(fn (Shipment $shipment) => Shipment::withoutEvents(fn () => $shipment->forceDelete()));

        $this->removeReviewNotifications($vendor);

        if ($rider->exists) {
            $this->removeReviewNotifications($rider);
        }
    }

    private function removeReviewNotifications(Vendor|Driver $notifiable): void
    {
        NotificationLog::query()
            ->where('notifiable_type', $notifiable::class)
            ->where('notifiable_id', $notifiable->id)
            ->where('title', 'like', '[App Review]%')
            ->delete();
    }
}
