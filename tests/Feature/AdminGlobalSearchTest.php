<?php

use App\Enums\ShipmentStatus;
use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\Driver;
use App\Models\Permission;
use App\Models\RecipientPaymentTask;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\ShipmentPayment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(LogAdminAuditActivity::class);
});

function agsAdminWithPermissions(array $permissionNames): User
{
    $warehouse = Warehouse::create([
        'name' => 'Admin Search HQ',
        'code' => 'HQ-ADMIN-SEARCH',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $admin = User::factory()->create([
        'name' => 'Search Admin',
        'email' => 'search-admin@example.test',
        'is_active' => true,
        'warehouse_id' => $warehouse->id,
    ]);

    $role = Role::create([
        'name' => 'Search Role',
        'slug' => 'search-role-' . $admin->id,
        'description' => 'Search test role',
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

function agsCreateVendor(array $overrides = []): Vendor
{
    return Vendor::create(array_merge([
        'name' => 'Ricky Vendor',
        'business_name' => 'Ricky Logistics',
        'phone' => '+233240000001',
        'email' => 'vendor@example.test',
        'is_active' => true,
    ], $overrides));
}

function agsCreateShipment(Vendor $vendor, array $overrides = []): Shipment
{
    return Shipment::create(array_merge([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'PCM-SEARCH-001',
        'status' => ShipmentStatus::SUBMITTED,
        'recipient_name' => 'Ama Recipient',
        'recipient_phone' => '+233240000002',
        'town' => 'Lapaz',
        'delivery_recipient_name' => 'Ama Recipient',
        'delivery_recipient_phone' => '+233240000002',
        'delivery_town' => 'Lapaz',
        'vendor_declared_quantity' => 1,
        'submitted_at' => now(),
    ], $overrides));
}

function agsCreatePackageGraph(): array
{
    $vendor = agsCreateVendor();
    $shipment = agsCreateShipment($vendor);
    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Blue laptop bag',
        'quantity' => 1,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRK-PKG-001',
        'delivery_recipient_name' => 'Package Recipient',
        'delivery_recipient_phone' => '+233240000003',
        'delivery_town' => 'Madina',
    ]);
    $warehouse = Warehouse::create([
        'name' => 'HQ Warehouse',
        'code' => 'HQ-AGS',
        'address' => 'Accra',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
    $receipt = WarehouseReceipt::create([
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'status' => WarehouseReceipt::STATUS_FINALIZED,
        'started_at' => now(),
        'finalized_at' => now(),
    ]);
    $receiptItem = WarehouseReceiptItem::create([
        'warehouse_receipt_id' => $receipt->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 1,
        'received_quantity' => 1,
        'condition_status' => 'ok',
        'barcode_value' => 'WRI-ABC-001',
    ]);
    $label = WarehouseReceiptItemLabel::create([
        'warehouse_receipt_item_id' => $receiptItem->id,
        'barcode_value' => 'PKG-ABC-001',
        'label_index' => 1,
        'labels_total' => 1,
        'label_type' => 'sealed',
    ]);

    return compact('vendor', 'shipment', 'item', 'warehouse', 'receipt', 'receiptItem', 'label');
}

it('returns an empty payload for short queries', function (): void {
    $admin = agsAdminWithPermissions(['shipments.view']);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'P']))
        ->assertOk()
        ->assertJson(['data' => []]);
});

it('searches shipments, vendors, drivers, packages, and transaction records', function (): void {
    $admin = agsAdminWithPermissions([
        'shipments.view',
        'vendors.view',
        'drivers.view',
        'charges.view',
        'recipient_payments.view',
        'vendors.manage',
    ]);

    ['vendor' => $vendor, 'shipment' => $shipment, 'item' => $item, 'warehouse' => $warehouse] = agsCreatePackageGraph();

    Driver::create([
        'name' => 'Kojo Rider',
        'email' => 'kojo-rider@example.test',
        'phone' => '+233240000004',
        'password' => 'password',
        'vehicle_type' => 'Motorbike',
        'status' => 'available',
        'is_active' => true,
    ]);
    ShipmentPayment::create([
        'shipment_id' => $shipment->id,
        'amount' => 120.50,
        'payment_method' => 'mobile_money',
        'reference_number' => 'PAY-REF-001',
        'notes' => 'Initial pickup payment',
        'recorded_by_admin_id' => $admin->id,
        'payment_date' => now(),
    ]);
    ShipmentCharge::create([
        'shipment_id' => $shipment->id,
        'shipment_item_id' => $item->id,
        'charge_type' => 'delivery_fee',
        'payer_type' => 'recipient',
        'direction' => 'revenue',
        'due_stage' => 'at_delivery',
        'amount' => 45.00,
        'currency' => 'GHS',
        'status' => 'paid',
        'payment_reference' => 'CHG-REF-001',
    ]);
    $recipientItem = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Recipient payment package',
        'quantity' => 1,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRK-REC-001',
        'delivery_recipient_name' => 'Paying Recipient',
        'delivery_recipient_phone' => '+233240000005',
        'delivery_town' => 'Tema',
    ]);
    RecipientPaymentTask::create([
        'shipment_item_id' => $recipientItem->id,
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'payment_group' => 'delivery',
        'status' => 'paid',
        'recipient_name' => 'Paying Recipient',
        'recipient_phone' => '+233240000005',
        'delivery_town' => 'Tema',
        'negotiated_amount' => 45.00,
        'currency' => 'GHS',
        'paid_at' => now(),
        'payment_reference' => 'REC-REF-001',
    ]);
    VendorPayout::create([
        'vendor_id' => $vendor->id,
        'amount' => 80.00,
        'status' => 'sent',
        'payment_method' => 'momo',
        'payment_reference' => 'PO-REF-001',
        'payment_phone' => '+233240000006',
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'PCM-SEARCH-001']))
        ->assertOk()
        ->assertJsonPath('data.shipments.0.label', 'PCM-SEARCH-001');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'Ricky Logistics']))
        ->assertOk()
        ->assertJsonPath('data.vendors.0.label', 'Ricky Logistics');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'Kojo Rider']))
        ->assertOk()
        ->assertJsonPath('data.drivers.0.label', 'Kojo Rider');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'PKG-ABC-001']))
        ->assertOk()
        ->assertJsonPath('data.packages.0.label', 'PKG-ABC-001');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'TRK-PKG-001']))
        ->assertOk()
        ->assertJsonPath('data.packages.0.sub', fn (string $sub) => str_contains($sub, 'TRK-PKG-001'));

    foreach (['PAY-REF-001', 'CHG-REF-001', 'REC-REF-001', 'PO-REF-001'] as $reference) {
        $this->actingAs($admin, 'admin')
            ->getJson(route('admin.search', ['q' => $reference]))
            ->assertOk()
            ->assertJsonPath('data.transactions.0.label', $reference);
    }

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'PO-REF-001']))
        ->assertOk()
        ->assertJsonPath('data.transactions.0.url', fn (string $url) => str_contains($url, route('admin.vendors.show', $vendor->id))
            && str_contains($url, 'tab=payouts')
            && str_contains($url, 'search=PO-REF-001'));

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.recipient-payments.data', ['search' => 'REC-REF-001']))
        ->assertOk()
        ->assertJsonPath('data.0.payment_reference', 'REC-REF-001');
});

it('does not expose result groups when the admin lacks their permissions', function (): void {
    $admin = agsAdminWithPermissions(['shipments.view']);

    agsCreatePackageGraph();
    agsCreateVendor([
        'business_name' => 'Hidden Vendor',
        'email' => 'hidden-vendor@example.test',
        'phone' => '+233240000099',
    ]);
    Driver::create([
        'name' => 'Hidden Driver',
        'email' => 'hidden-driver@example.test',
        'phone' => '+233240000007',
        'password' => 'password',
        'status' => 'available',
        'is_active' => true,
    ]);

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'Hidden']))
        ->assertOk()
        ->assertJsonMissingPath('data.vendors')
        ->assertJsonMissingPath('data.drivers')
        ->assertJsonMissingPath('data.transactions');

    $this->actingAs($admin, 'admin')
        ->getJson(route('admin.search', ['q' => 'PKG-ABC-001']))
        ->assertOk()
        ->assertJsonPath('data.packages.0.label', 'PKG-ABC-001');
});
