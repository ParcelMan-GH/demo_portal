<?php

use App\Enums\ItemStatus;
use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentStatus;
use App\Models\AdminNotification;
use App\Models\Driver;
use App\Models\Permission;
use App\Models\PickupAssignment;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\PickupAssignmentService;
use App\Services\Warehouse\WarehouseReceivingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function bflowAdminWithPermissions(array $permissionNames, ?Warehouse $warehouse = null): User
{
    $warehouse ??= Warehouse::create([
        'name' => 'Business Flow HQ',
        'code' => 'BF-HQ',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $admin = User::factory()->create([
        'name' => 'Business Flow Admin',
        'email' => 'business-flow-admin@example.test',
        'is_active' => true,
        'warehouse_id' => $warehouse->id,
    ]);

    $role = Role::create([
        'name' => 'Business Flow Role '.$admin->id,
        'slug' => 'business-flow-role-'.$admin->id,
        'description' => 'Business flow test role',
        'is_system_role' => false,
        'is_warehouse_role' => false,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissionNames as $permissionName) {
        [$module, $action] = array_pad(explode('.', $permissionName, 2), 2, 'view');

        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            [
                'module' => $module,
                'action' => $action,
                'description' => $permissionName,
            ],
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $admin->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $admin->id,
    ]);
    $admin->flushPermissionCache();

    return $admin->fresh();
}

function bflowVendor(): Vendor
{
    $vendor = Vendor::create([
        'name' => 'Business Flow Vendor',
        'business_name' => 'Business Flow Logistics',
        'phone' => '+233240101010',
        'email' => 'business-flow-vendor@example.test',
        'is_active' => true,
    ]);

    return $vendor;
}

function bflowDriver(): Driver
{
    return Driver::create([
        'name' => 'Business Flow Rider',
        'email' => 'business-flow-rider@example.test',
        'phone' => '+233240202020',
        'password' => bcrypt('secret123'),
        'vehicle_type' => 'Motorbike',
        'vehicle_number' => 'BF-2026',
        'license_number' => 'BF-LIC-2026',
        'base_location' => 'Accra',
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => [Driver::CAPABILITY_PICKUP],
    ]);
}

test('vendor order can be assigned to a rider, picked up, and finalized at warehouse', function () {
    Storage::fake('public');

    $warehouse = Warehouse::create([
        'name' => 'Accra Intake Warehouse',
        'code' => 'BF-ACC',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
    $admin = bflowAdminWithPermissions([
        'shipments.view',
        'warehouse.receiving.manage',
    ], $warehouse);
    $vendor = bflowVendor();
    $driver = bflowDriver();

    Sanctum::actingAs($vendor);

    $createResponse = $this->post('/api/v1/vendor/shipments', [
        'destination_mode' => ShipmentDestinationMode::PER_ITEM->value,
        'fulfillment_type' => 'warehouse',
        'pickup_contact_name' => 'Ricky Vendor',
        'pickup_contact_phone' => '0241112222',
        'pickup_town' => 'Lapaz',
        'vendor_declared_quantity' => 2,
        'items' => [
            [
                'description' => 'Laptop charger',
                'quantity' => 1,
                'delivery_recipient_name' => 'Ama Mensah',
                'delivery_recipient_phone' => '0541112222',
                'delivery_town' => 'Tema',
                'images' => [
                    UploadedFile::fake()->image('charger.jpg', 640, 480),
                ],
                'phones' => ['0541112222'],
            ],
            [
                'description' => 'Phone case',
                'quantity' => 1,
                'delivery_recipient_name' => 'Kojo Boateng',
                'delivery_recipient_phone' => '0203334444',
                'delivery_town' => 'Madina',
                'images' => [
                    UploadedFile::fake()->image('case.jpg', 640, 480),
                ],
                'phones' => ['0203334444'],
            ],
        ],
    ]);

    $createResponse->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.shipment.status', ShipmentStatus::SUBMITTED->value);

    $shipmentId = $createResponse->json('data.shipment.id');
    $shipment = Shipment::query()->with('items')->findOrFail($shipmentId);

    expect($shipment->status)->toBe(ShipmentStatus::SUBMITTED)
        ->and($shipment->items)->toHaveCount(2)
        ->and(AdminNotification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'shipment_submitted')
            ->where('data->shipment_id', $shipment->id)
            ->exists())->toBeTrue();

    $assignResult = app(PickupAssignmentService::class)->assign(
        shipment: $shipment,
        driver: $driver,
        admin: $admin,
        notes: 'Business flow assignment',
        targetWarehouseId: $warehouse->id,
    );

    expect($assignResult['success'])->toBeTrue();

    $assignment = PickupAssignment::query()
        ->where('shipment_id', $shipment->id)
        ->firstOrFail();

    Sanctum::actingAs($driver);

    $this->getJson('/api/v1/driver/pickups')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->getJson("/api/v1/driver/pickups/{$assignment->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pickup.id', $assignment->id);

    $this->postJson("/api/v1/driver/pickups/{$assignment->id}/en-route")
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->postJson("/api/v1/driver/pickups/{$assignment->id}/arrive", [
        'latitude' => 5.603717,
        'longitude' => -0.186964,
    ])->assertOk()
        ->assertJsonPath('success', true);

    foreach ($shipment->items as $item) {
        $this->post("/api/v1/driver/pickups/{$assignment->id}/items/{$item->id}/confirm", [
            'confirmed_quantity' => (int) $item->quantity,
            'notes' => 'Package checked by rider.',
            'photos' => [
                UploadedFile::fake()->image("pickup-item-{$item->id}.jpg", 640, 480),
            ],
        ])->assertOk()
            ->assertJsonPath('success', true);
    }

    $this->postJson("/api/v1/driver/pickups/{$assignment->id}/confirm-pickup", [
        'driver_picked_quantity' => 2,
        'latitude' => 5.603717,
        'longitude' => -0.186964,
        'notes' => 'Picked up successfully.',
    ])->assertOk()
        ->assertJsonPath('success', true);

    $assignment->refresh();
    expect($assignment->status)->toBe(PickupAssignmentStatus::COMPLETED)
        ->and($assignment->picked_up_at)->not->toBeNull();

    $receivingService = app(WarehouseReceivingService::class);
    foreach ($shipment->fresh('items')->items as $item) {
        $receiveResult = $receivingService->upsertReceiptItem(
            assignment: $assignment,
            shipmentItem: $item,
            warehouse: $warehouse,
            user: $admin,
            receivedQuantity: (int) $item->quantity,
            damagedQuantity: 0,
            conditionStatus: 'ok',
            notes: 'Received at warehouse.',
        );

        expect($receiveResult['success'])->toBeTrue();

        $labelResult = $receivingService->generateLabels(
            assignment: $assignment,
            shipmentItem: $item,
            warehouse: $warehouse,
            user: $admin,
            labelCount: (int) $item->quantity,
        );

        expect($labelResult['success'])->toBeTrue();
    }

    $finalizeResult = $receivingService->finalizeReceipt(
        assignment: $assignment,
        warehouse: $warehouse,
        user: $admin,
        notes: 'Warehouse intake complete.',
    );

    expect($finalizeResult['success'])->toBeTrue();

    $assignment->refresh();
    $shipment->refresh();
    $driver->refresh();

    expect($shipment->status)->toBe(ShipmentStatus::AT_WAREHOUSE)
        ->and($shipment->items()->where('status', ItemStatus::AT_WAREHOUSE->value)->count())->toBe(2)
        ->and($assignment->received_at)->not->toBeNull()
        ->and($assignment->received_warehouse_id)->toBe($warehouse->id)
        ->and($driver->status)->toBe('available');

    $receipt = WarehouseReceipt::query()
        ->where('pickup_assignment_id', $assignment->id)
        ->where('warehouse_id', $warehouse->id)
        ->firstOrFail();

    expect($receipt->status)->toBe(WarehouseReceipt::STATUS_FINALIZED)
        ->and(WarehouseReceiptItem::query()->where('warehouse_receipt_id', $receipt->id)->count())->toBe(2)
        ->and(WarehouseReceiptItemLabel::query()
            ->whereHas('receiptItem', fn ($query) => $query->where('warehouse_receipt_id', $receipt->id))
            ->count())->toBe(2);
});
