<?php

use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\PickupAssignment;
use App\Models\RiderAssignmentEvent;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\DirectDeliveryService;
use App\Services\DriverWorkloadService;
use App\Services\PickupAssignmentService;
use App\Services\PushNotificationService;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function riderOpsWarehouse(string $code): Warehouse
{
    return Warehouse::query()->create([
        'name' => "Rider Ops {$code}",
        'code' => $code,
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
}

function riderOpsDriver(string $phone, string $name): Driver
{
    return Driver::query()->create([
        'name' => $name,
        'phone' => $phone,
        'password' => bcrypt('secret123'),
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => [
            Driver::CAPABILITY_PICKUP,
            Driver::CAPABILITY_TRANSPORT,
            Driver::CAPABILITY_DELIVERY,
        ],
    ]);
}

function riderOpsShipment(string $suffix): Shipment
{
    $vendor = Vendor::query()->create([
        'name' => "Vendor {$suffix}",
        'business_name' => "Vendor {$suffix}",
        'phone' => '+23324'.str_pad($suffix, 7, '0'),
        'is_active' => true,
    ]);

    return Shipment::query()->create([
        'vendor_id' => $vendor->id,
        'status' => ShipmentStatus::PICKUP_ASSIGNED,
        'pickup_contact_name' => 'Review Vendor',
        'pickup_contact_phone' => '0240000000',
        'pickup_town' => 'Accra',
    ]);
}

test('workload summary covers pickup transport and delivery without falsely freeing the rider', function () {
    $warehouse = riderOpsWarehouse('ROW-1');
    $driver = riderOpsDriver('+233240000001', 'Multi Work Rider');
    $shipment = riderOpsShipment('1');

    $pickup = PickupAssignment::query()->create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'status' => PickupAssignmentStatus::ASSIGNED,
        'assigned_at' => now(),
    ]);
    TransportManifest::query()->create([
        'manifest_number' => 'TM-ROW-1',
        'origin_warehouse_id' => $warehouse->id,
        'destination_warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $driver->id,
        'status' => TransportManifest::STATUS_ASSIGNED,
    ]);
    DeliveryRun::query()->create([
        'run_number' => 'DR-ROW-1',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $driver->id,
        'status' => DeliveryRun::STATUS_ASSIGNED,
    ]);

    $workloads = app(DriverWorkloadService::class);
    expect($workloads->summary($driver))->toMatchArray([
        'pickups' => 1,
        'transports' => 1,
        'deliveries' => 1,
        'total' => 3,
        'is_busy' => true,
    ]);

    $workloads->syncStatus($driver);
    expect($driver->fresh()->status)->toBe('busy');

    $pickup->update(['status' => PickupAssignmentStatus::CANCELLED]);
    $workloads->syncStatus($driver);

    expect($workloads->summary($driver)['total'])->toBe(2)
        ->and($driver->fresh()->status)->toBe('busy');
});

test('finishing the final pickup releases the rider but finishing one of several jobs does not', function () {
    $warehouse = riderOpsWarehouse('ROW-5');
    $driver = riderOpsDriver('+233240000009', 'Pickup Completion Rider');
    $shipment = riderOpsShipment('5');
    ShipmentItem::query()->create([
        'shipment_id' => $shipment->id,
        'description' => 'Pickup completion parcel',
        'quantity' => 1,
    ]);
    $assignment = PickupAssignment::query()->create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => PickupAssignmentStatus::ARRIVED,
        'assigned_at' => now(),
        'arrived_at' => now(),
    ]);

    app(DriverWorkloadService::class)->syncStatus($driver);
    expect($driver->fresh()->status)->toBe('busy');

    $result = app(PickupAssignmentService::class)->finalizePickup($assignment, 1);

    expect($result['success'])->toBeTrue()
        ->and($assignment->fresh()->status)->toBe(PickupAssignmentStatus::COMPLETED)
        ->and($driver->fresh()->status)->toBe('available');

    $secondShipment = riderOpsShipment('6');
    ShipmentItem::query()->create([
        'shipment_id' => $secondShipment->id,
        'description' => 'Pickup with another active job',
        'quantity' => 1,
    ]);
    $secondAssignment = PickupAssignment::query()->create([
        'shipment_id' => $secondShipment->id,
        'driver_id' => $driver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => PickupAssignmentStatus::ARRIVED,
        'assigned_at' => now(),
        'arrived_at' => now(),
    ]);
    DeliveryRun::query()->create([
        'run_number' => 'DR-ROW-5',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $driver->id,
        'status' => DeliveryRun::STATUS_ASSIGNED,
    ]);

    $result = app(PickupAssignmentService::class)->finalizePickup($secondAssignment, 1);

    expect($result['success'])->toBeTrue()
        ->and($driver->fresh()->status)->toBe('busy');
});

test('direct delivery pipeline keeps its pickup rider busy with the new delivery run', function () {
    $warehouse = riderOpsWarehouse('ROW-6');
    $driver = riderOpsDriver('+233240000014', 'Direct Delivery Rider');
    $shipment = riderOpsShipment('8');
    $shipment->update([
        'destination_mode' => 'single',
        'delivery_recipient_name' => 'Review Recipient',
        'delivery_recipient_phone' => '0241111111',
        'delivery_town' => 'Tema',
    ]);
    ShipmentItem::query()->create([
        'shipment_id' => $shipment->id,
        'description' => 'Direct delivery parcel',
        'quantity' => 1,
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
    ]);

    $result = app(DirectDeliveryService::class)->createVirtualPipeline(
        $shipment->fresh('items'),
        $driver->id,
        $warehouse->id,
    );

    expect($result['delivery_run_id'])->toBeInt()
        ->and(DeliveryRun::query()->findOrFail($result['delivery_run_id'])->assigned_driver_id)->toBe($driver->id)
        ->and($driver->fresh()->status)->toBe('busy');

    $this->assertDatabaseHas('rider_assignment_events', [
        'job_type' => 'delivery',
        'job_id' => $result['delivery_run_id'],
        'event_type' => RiderAssignmentEvent::EVENT_ASSIGNED,
        'previous_driver_id' => null,
        'driver_id' => $driver->id,
    ]);
    $this->assertDatabaseHas('notification_logs', [
        'notifiable_type' => Driver::class,
        'notifiable_id' => $driver->id,
        'type' => 'delivery_assigned',
        'status' => 'logged',
    ]);
});

test('pickup reassignment warns for busy rider then audits and notifies both riders after confirmation', function () {
    $warehouse = riderOpsWarehouse('ROW-2');
    $actor = User::factory()->create(['warehouse_id' => $warehouse->id]);
    $oldDriver = riderOpsDriver('+233240000002', 'Old Rider');
    $newDriver = riderOpsDriver('+233240000003', 'Busy New Rider');
    $shipment = riderOpsShipment('2');

    $assignment = PickupAssignment::query()->create([
        'shipment_id' => $shipment->id,
        'driver_id' => $oldDriver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => PickupAssignmentStatus::ASSIGNED,
        'assigned_at' => now(),
    ]);
    DeliveryRun::query()->create([
        'run_number' => 'DR-ROW-2',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $newDriver->id,
        'status' => DeliveryRun::STATUS_ASSIGNED,
    ]);
    TransportManifest::query()->create([
        'manifest_number' => 'TM-ROW-2',
        'origin_warehouse_id' => $warehouse->id,
        'destination_warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $oldDriver->id,
        'status' => TransportManifest::STATUS_ASSIGNED,
    ]);

    $service = app(PickupAssignmentService::class);
    $warning = $service->updateAssignment(
        $assignment,
        $newDriver->id,
        null,
        app(PushNotificationService::class),
        $actor,
        false,
        'Balance today workload',
    );

    expect($warning['success'])->toBeFalse()
        ->and($warning['code'])->toBe('rider_busy')
        ->and($assignment->fresh()->driver_id)->toBe($oldDriver->id);

    $result = $service->updateAssignment(
        $assignment,
        $newDriver->id,
        null,
        app(PushNotificationService::class),
        $actor,
        true,
        'Balance today workload',
    );

    expect($result['success'])->toBeTrue()
        ->and($assignment->fresh()->driver_id)->toBe($newDriver->id)
        ->and($assignment->fresh()->target_warehouse_id)->toBe($warehouse->id)
        ->and($oldDriver->fresh()->status)->toBe('busy')
        ->and($newDriver->fresh()->status)->toBe('busy');

    $this->assertDatabaseHas('rider_assignment_events', [
        'job_type' => 'pickup',
        'job_id' => $assignment->id,
        'event_type' => RiderAssignmentEvent::EVENT_REASSIGNED,
        'previous_driver_id' => $oldDriver->id,
        'driver_id' => $newDriver->id,
        'performed_by_user_id' => $actor->id,
        'reason' => 'Balance today workload',
    ]);
    $this->assertDatabaseHas('notification_logs', [
        'notifiable_type' => Driver::class,
        'notifiable_id' => $oldDriver->id,
        'type' => 'pickup_unassigned',
        'title' => 'Pickup Assignment Removed',
        'status' => 'logged',
    ]);

    $pickupNotification = NotificationLog::query()
        ->where('notifiable_id', $newDriver->id)
        ->where('type', 'pickup_assigned')
        ->firstOrFail();
    expect($pickupNotification->data)->toMatchArray([
        'pickup_id' => (string) $assignment->id,
        'assignment_id' => (string) $assignment->id,
    ]);
    $this->assertDatabaseHas('notification_logs', [
        'notifiable_type' => Driver::class,
        'notifiable_id' => $newDriver->id,
        'type' => 'pickup_assigned',
        'status' => 'logged',
    ]);

    $notificationCount = \App\Models\NotificationLog::query()->count();
    $auditCount = RiderAssignmentEvent::query()->count();
    $noOp = $service->updateAssignment(
        $assignment->fresh(),
        $newDriver->id,
        null,
        app(PushNotificationService::class),
        $actor,
    );

    expect($noOp['success'])->toBeTrue()
        ->and($noOp['message'])->toBe('No changes were made.')
        ->and(\App\Models\NotificationLog::query()->count())->toBe($notificationCount)
        ->and(RiderAssignmentEvent::query()->count())->toBe($auditCount);
});

test('transport and delivery assignments use the same busy override and reassignment windows', function () {
    $warehouse = riderOpsWarehouse('ROW-3');
    $actor = User::factory()->create(['warehouse_id' => $warehouse->id]);
    $oldDriver = riderOpsDriver('+233240000004', 'Original Rider');
    $busyDriver = riderOpsDriver('+233240000005', 'Override Rider');

    DeliveryRun::query()->create([
        'run_number' => 'DR-BUSY-3',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $busyDriver->id,
        'status' => DeliveryRun::STATUS_ASSIGNED,
    ]);

    $manifest = TransportManifest::query()->create([
        'manifest_number' => 'TM-ROW-3',
        'origin_warehouse_id' => $warehouse->id,
        'destination_warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $oldDriver->id,
        'status' => TransportManifest::STATUS_ASSIGNED,
    ]);
    $transport = app(WarehouseTransportService::class);

    expect($transport->assignDriver($manifest, $busyDriver, $warehouse, $actor, false)['code'])->toBe('rider_busy');
    expect($transport->assignDriver($manifest, $busyDriver, $warehouse, $actor, true, 'Cover the route')['success'])->toBeTrue();
    $this->assertDatabaseHas('notification_logs', ['notifiable_id' => $oldDriver->id, 'type' => 'transport_unassigned']);
    $this->assertDatabaseHas('notification_logs', ['notifiable_id' => $busyDriver->id, 'type' => 'transport_assigned']);
    $this->assertDatabaseHas('rider_assignment_events', [
        'job_type' => 'transport',
        'job_id' => $manifest->id,
        'event_type' => RiderAssignmentEvent::EVENT_REASSIGNED,
        'reason' => 'Cover the route',
    ]);
    $transportNotifications = NotificationLog::query()
        ->whereIn('type', ['transport_assigned', 'transport_unassigned'])
        ->get();
    expect($transportNotifications)->toHaveCount(2)
        ->and($transportNotifications->firstWhere('type', 'transport_assigned')?->data)->toMatchArray([
            'transport_id' => (string) $manifest->id,
            'manifest_id' => (string) $manifest->id,
        ]);

    $manifest->refresh()->update(['status' => TransportManifest::STATUS_IN_TRANSIT]);
    expect($transport->assignDriver($manifest->fresh(), $oldDriver, $warehouse, $actor, true)['success'])->toBeFalse();

    $run = DeliveryRun::query()->create([
        'run_number' => 'DR-ROW-3',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $oldDriver->id,
        'status' => DeliveryRun::STATUS_ASSIGNED,
    ]);
    $delivery = app(WarehouseDeliveryService::class);

    expect($delivery->assignDriver($run, $busyDriver, $warehouse, $actor, false)['code'])->toBe('rider_busy');
    expect($delivery->assignDriver($run, $busyDriver, $warehouse, $actor, true, 'Take over delivery')['success'])->toBeTrue();
    $this->assertDatabaseHas('notification_logs', ['notifiable_id' => $oldDriver->id, 'type' => 'delivery_unassigned']);
    $this->assertDatabaseHas('notification_logs', ['notifiable_id' => $busyDriver->id, 'type' => 'delivery_assigned']);
    $this->assertDatabaseHas('rider_assignment_events', [
        'job_type' => 'delivery',
        'job_id' => $run->id,
        'event_type' => RiderAssignmentEvent::EVENT_REASSIGNED,
        'reason' => 'Take over delivery',
    ]);
    $deliveryNotifications = NotificationLog::query()
        ->whereIn('type', ['delivery_assigned', 'delivery_unassigned'])
        ->get();
    expect($deliveryNotifications)->toHaveCount(2)
        ->and($deliveryNotifications->firstWhere('type', 'delivery_assigned')?->data)->toMatchArray([
            'delivery_run_id' => (string) $run->id,
        ]);

    $run->refresh()->update(['status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY]);
    expect($delivery->assignDriver($run->fresh(), $oldDriver, $warehouse, $actor, true)['success'])->toBeFalse();
});

test('assignment options expose only safe rider fields and computed workload', function () {
    $warehouse = riderOpsWarehouse('ROW-4');
    $driver = riderOpsDriver('+233240000006', 'Option Rider');
    DeliveryRun::query()->create([
        'run_number' => 'DR-ROW-4',
        'warehouse_id' => $warehouse->id,
        'assigned_driver_id' => $driver->id,
        'status' => DeliveryRun::STATUS_ASSIGNED,
    ]);

    $option = app(DriverWorkloadService::class)
        ->assignmentOptions(Driver::CAPABILITY_PICKUP)
        ->firstWhere('id', $driver->id);

    expect($option)->toMatchArray([
        'id' => $driver->id,
        'is_busy' => true,
        'active_work_count' => 1,
        'active_work' => ['pickups' => 0, 'transports' => 0, 'deliveries' => 1],
    ])->and(array_keys($option))->not->toContain('password', 'fcm_token', 'license_number');

    $inactiveCurrent = riderOpsDriver('+233240000008', 'Inactive Current Rider');
    $inactiveCurrent->update(['is_active' => false, 'task_capabilities' => []]);
    $included = app(DriverWorkloadService::class)
        ->assignmentOptions(Driver::CAPABILITY_DELIVERY, includeDriverIds: [$inactiveCurrent->id])
        ->firstWhere('id', $inactiveCurrent->id);

    expect($included['name'])->toBe('Inactive Current Rider')
        ->and(array_keys($included))->not->toContain('password', 'fcm_token', 'license_number');
});

test('legacy riders with null capabilities remain consistently pickup capable', function () {
    $legacyDriver = riderOpsDriver('+233240000012', 'Legacy Pickup Rider');
    $legacyDriver->forceFill(['task_capabilities' => null])->save();

    expect($legacyDriver->fresh()->getCapabilities())->toBe([Driver::CAPABILITY_PICKUP])
        ->and($legacyDriver->fresh()->hasCapability(Driver::CAPABILITY_PICKUP))->toBeTrue()
        ->and($legacyDriver->fresh()->hasCapability(Driver::CAPABILITY_DELIVERY))->toBeFalse();

    $options = app(DriverWorkloadService::class)->assignmentOptions(Driver::CAPABILITY_PICKUP);
    expect($options->firstWhere('id', $legacyDriver->id))->not->toBeNull();

    $explicitlyRestricted = riderOpsDriver('+233240000013', 'Restricted Rider');
    $explicitlyRestricted->forceFill(['task_capabilities' => []])->save();

    expect($explicitlyRestricted->fresh()->hasCapability(Driver::CAPABILITY_PICKUP))->toBeFalse()
        ->and(app(DriverWorkloadService::class)
            ->assignmentOptions(Driver::CAPABILITY_PICKUP)
            ->firstWhere('id', $explicitlyRestricted->id))->toBeNull();
});

test('workload reconciliation fixes stale statuses while preserving offline riders', function () {
    $driver = riderOpsDriver('+233240000010', 'Stale Available Rider');
    $offlineDriver = riderOpsDriver('+233240000011', 'Offline Rider');
    $offlineDriver->update(['status' => 'offline']);
    $shipment = riderOpsShipment('7');
    PickupAssignment::query()->create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'status' => PickupAssignmentStatus::ASSIGNED,
        'assigned_at' => now(),
    ]);

    $this->artisan('drivers:reconcile-workload-status')
        ->expectsOutputToContain('Updated 1 rider(s).')
        ->assertSuccessful();

    expect($driver->fresh()->status)->toBe('busy')
        ->and($offlineDriver->fresh()->status)->toBe('offline');
});

test('invalid rider push tokens are cleared while the in-app notification remains', function () {
    $driver = riderOpsDriver('+233240000007', 'Expired Token Rider');
    $driver->update(['fcm_token' => 'expired-token']);

    Http::fake([
        'https://fcm.googleapis.com/*' => Http::response([
            'error' => [
                'status' => 'INVALID_ARGUMENT',
                'message' => 'The registration token is not a valid FCM registration token',
                'details' => [['errorCode' => 'UNREGISTERED']],
            ],
        ], 400),
    ]);

    $service = new class extends PushNotificationService
    {
        protected function isPushEnabled(): bool
        {
            return true;
        }

        protected function getProjectId(): ?string
        {
            return 'parcelman-test';
        }

        protected function getAccessToken(): ?string
        {
            return 'test-access-token';
        }
    };

    expect($service->sendToDriver($driver, 'Assignment', 'Test message', [], 'pickup_assigned'))->toBeFalse()
        ->and($driver->fresh()->fcm_token)->toBeNull();

    $this->assertDatabaseHas('notification_logs', [
        'notifiable_type' => Driver::class,
        'notifiable_id' => $driver->id,
        'type' => 'pickup_assigned',
        'status' => 'failed',
    ]);
});

test('assignment notification listeners are registered exactly once', function () {
    expect(app('events')->getListeners(\App\Events\DriverAssignedToPickup::class))->toHaveCount(2)
        ->and(app('events')->getListeners(\App\Events\DriverUnassignedFromPickup::class))->toHaveCount(1)
        ->and(app('events')->getListeners(\App\Events\DriverAssignedToTransport::class))->toHaveCount(1)
        ->and(app('events')->getListeners(\App\Events\DriverUnassignedFromTransport::class))->toHaveCount(1)
        ->and(app('events')->getListeners(\App\Events\DriverAssignedToDelivery::class))->toHaveCount(1)
        ->and(app('events')->getListeners(\App\Events\DriverUnassignedFromDelivery::class))->toHaveCount(1);
});

test('admin assignment pickers expose searchable accessible rider listboxes', function () {
    $sources = [
        resource_path('views/admin/pickups/index.blade.php'),
        resource_path('views/admin/shipments/show.blade.php'),
        resource_path('views/admin/shipments/edit.blade.php'),
        resource_path('views/shared/transport-manifests-show.blade.php'),
        resource_path('views/admin/delivery-runs/show.blade.php'),
    ];

    foreach ($sources as $source) {
        $contents = file_get_contents($source);
        expect($contents)
            ->toContain('type="search"')
            ->toContain('role="combobox"')
            ->toContain('aria-autocomplete="list"')
            ->toContain('role="listbox"')
            ->toContain('role="option"')
            ->toContain('aria-selected')
            ->toContain('keydown.escape.stop.prevent')
            ->toContain('Assigned here');
    }
});
