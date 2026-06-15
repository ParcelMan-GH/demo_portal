<?php

use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentStatus;
use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\AdminNotification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\ShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(LogAdminAuditActivity::class);
});

function ainAdminWithPermissions(array $permissionNames, array $overrides = []): User
{
    $warehouse = Warehouse::create([
        'name' => 'Admin Notification HQ',
        'code' => 'HQ-AIN-'.uniqid(),
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $admin = User::factory()->create(array_merge([
        'name' => 'Notification Admin',
        'email' => 'notification-admin-'.uniqid().'@example.test',
        'is_active' => true,
        'warehouse_id' => $warehouse->id,
        'fcm_token' => null,
    ], $overrides));

    $role = Role::create([
        'name' => 'Notification Role '.$admin->id,
        'slug' => 'notification-role-'.$admin->id,
        'description' => 'Notification test role',
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

function ainCreateDraftShipment(): Shipment
{
    $vendor = Vendor::create([
        'name' => 'Order Vendor',
        'business_name' => 'Order Vendor Logistics',
        'phone' => '+233240000099',
        'email' => 'order-vendor@example.test',
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-AIN-'.strtoupper(substr(uniqid(), -6)),
        'status' => ShipmentStatus::DRAFT,
        'destination_mode' => ShipmentDestinationMode::PER_ITEM,
        'pickup_contact_name' => 'Order Vendor',
        'pickup_contact_phone' => '+233240000099',
        'pickup_town' => 'Lapaz',
        'vendor_declared_quantity' => 1,
    ]);

    ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => null,
        'quantity' => 1,
        'status' => 'pending',
    ]);

    return $shipment->fresh(['vendor', 'items']);
}

it('creates in-app admin notifications for shipment viewers when a vendor order is submitted', function (): void {
    $recipient = ainAdminWithPermissions(['shipments.view']);
    $excluded = ainAdminWithPermissions(['vendors.view']);
    $inactive = ainAdminWithPermissions(['shipments.view'], ['is_active' => false]);
    $shipment = ainCreateDraftShipment();

    $result = app(ShipmentService::class)->submit($shipment, Request::create('/api/v1/vendor/shipments/'.$shipment->id.'/submit', 'POST'));

    expect($result['success'])->toBeTrue();

    $notification = AdminNotification::query()->where('user_id', $recipient->id)->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe('shipment_submitted')
        ->and($notification->title)->toBe('New Shipment Submitted')
        ->and($notification->body)->toContain($shipment->shipment_number)
        ->and($notification->url)->toBe(route('admin.orders.show', $shipment, false))
        ->and($notification->data['shipment_id'])->toBe($shipment->id)
        ->and($notification->read_at)->toBeNull();

    expect(AdminNotification::query()->where('user_id', $excluded->id)->exists())->toBeFalse()
        ->and(AdminNotification::query()->where('user_id', $inactive->id)->exists())->toBeFalse();
});

it('returns only the current admins inbox notifications and unread count', function (): void {
    $admin = ainAdminWithPermissions(['shipments.view']);
    $otherAdmin = ainAdminWithPermissions(['shipments.view']);

    AdminNotification::create([
        'user_id' => $admin->id,
        'type' => 'shipment_submitted',
        'title' => 'New Shipment Submitted',
        'body' => 'Vendor submitted PCM-AIN-111111.',
        'url' => '/admin/orders/1',
        'data' => ['shipment_id' => 1],
    ]);
    AdminNotification::create([
        'user_id' => $admin->id,
        'type' => 'shipment_submitted',
        'title' => 'Read Shipment',
        'body' => 'Already read.',
        'url' => '/admin/orders/2',
        'data' => ['shipment_id' => 2],
        'read_at' => now(),
    ]);
    AdminNotification::create([
        'user_id' => $otherAdmin->id,
        'type' => 'shipment_submitted',
        'title' => 'Other Admin Shipment',
        'body' => 'Should not leak.',
        'url' => '/admin/orders/3',
        'data' => ['shipment_id' => 3],
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.in-app-notifications.index'))
        ->assertOk()
        ->assertJsonPath('data.unread_count', 1)
        ->assertJsonCount(2, 'data.notifications')
        ->assertJsonMissing(['title' => 'Other Admin Shipment']);
});

it('marks only the current admins notification rows as read', function (): void {
    $admin = ainAdminWithPermissions(['shipments.view']);
    $otherAdmin = ainAdminWithPermissions(['shipments.view']);

    $ownNotification = AdminNotification::create([
        'user_id' => $admin->id,
        'type' => 'shipment_submitted',
        'title' => 'Own Shipment',
        'body' => 'Own body.',
        'url' => '/admin/orders/1',
        'data' => ['shipment_id' => 1],
    ]);
    $otherNotification = AdminNotification::create([
        'user_id' => $otherAdmin->id,
        'type' => 'shipment_submitted',
        'title' => 'Other Shipment',
        'body' => 'Other body.',
        'url' => '/admin/orders/2',
        'data' => ['shipment_id' => 2],
    ]);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.in-app-notifications.mark-read', $ownNotification))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($ownNotification->fresh()->read_at)->not->toBeNull()
        ->and($otherNotification->fresh()->read_at)->toBeNull();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.in-app-notifications.mark-read', $otherNotification))
        ->assertNotFound();

    expect($otherNotification->fresh()->read_at)->toBeNull();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.in-app-notifications.read-all'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($otherNotification->fresh()->read_at)->toBeNull();
});
