<?php

use App\Models\District;
use App\Models\Region;
use App\Models\ShipmentCharge;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\WalkinShipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeWalkinChargeAdmin(?Warehouse $warehouse = null): User
{
    $warehouse ??= Warehouse::query()->create([
        'name' => 'Accra Main Hub',
        'code' => 'WH-WALKIN-001',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    return User::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);
}

function makeWalkinChargeVendor(): Vendor
{
    return Vendor::query()->create([
        'name' => 'Counter Vendor',
        'business_name' => 'Counter Vendor Shop',
        'phone' => '+233241234567',
        'is_active' => true,
    ]);
}

function validWalkinChargePayload(array $overrides = []): array
{
    $vendor = makeWalkinChargeVendor();

    return array_replace_recursive([
        'vendor_id' => $vendor->id,
        'fulfillment_type' => 'warehouse',
        'delivery_preference' => 'deliver',
        'destination_mode' => 'per_item',
        'items' => [
            [
                'description' => 'Counter package',
                'quantity' => 1,
                'delivery_method' => 'direct',
                'delivery' => [
                    'recipient_name' => 'Ama Recipient',
                    'recipient_phone' => '0241234567',
                    'town' => 'Osu',
                    'instructions' => 'Call on arrival',
                ],
            ],
        ],
    ], $overrides);
}

function validAdminWalkinChargePayload(Warehouse $warehouse, array $overrides = []): array
{
    $region = Region::query()->create([
        'name' => 'Greater Accra',
        'code' => 'GAR',
        'is_active' => true,
    ]);
    $district = District::query()->create([
        'region_id' => $region->id,
        'name' => 'Accra Metro',
        'code' => 'AMA',
        'is_active' => true,
    ]);

    return array_replace_recursive(validWalkinChargePayload([
        'warehouse_id' => $warehouse->id,
        'items' => [
            [
                'description' => 'Counter package',
                'quantity' => 1,
                'delivery' => [
                    'recipient_name' => 'Ama Recipient',
                    'recipient_phone' => '0241234567',
                    'region_id' => $region->id,
                    'district_id' => $district->id,
                    'town' => 'Osu',
                    'instructions' => 'Call on arrival',
                ],
            ],
        ],
    ]), $overrides);
}

test('blank walk-in charge amount creates no shipment charge', function () {
    $admin = makeWalkinChargeAdmin();

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('warehouse.walkin.store'), validWalkinChargePayload([
            'pickup_fee_amount' => '',
        ]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('charge', null);

    expect(ShipmentCharge::query()->count())->toBe(0);
});

test('zero walk-in charge amount creates no shipment charge', function () {
    $admin = makeWalkinChargeAdmin();

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('warehouse.walkin.store'), validWalkinChargePayload([
            'pickup_fee_amount' => 0,
        ]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('charge', null);

    expect(ShipmentCharge::query()->count())->toBe(0);
});

test('positive walk-in charge amount creates pending vendor pickup fee', function () {
    $admin = makeWalkinChargeAdmin();

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('warehouse.walkin.store'), validWalkinChargePayload([
            'pickup_fee_amount' => '42.75',
        ]));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('charge.status', ShipmentCharge::STATUS_PENDING);

    $charge = ShipmentCharge::query()->sole();

    expect((float) $charge->amount)->toBe(42.75)
        ->and($charge->charge_type)->toBe(ShipmentCharge::TYPE_PICKUP_FEE)
        ->and($charge->payer_type)->toBe(ShipmentCharge::PAYER_VENDOR)
        ->and($charge->direction)->toBe(ShipmentCharge::DIRECTION_REVENUE)
        ->and($charge->due_stage)->toBe(ShipmentCharge::STAGE_AT_PICKUP)
        ->and($charge->status)->toBe(ShipmentCharge::STATUS_PENDING)
        ->and($charge->currency)->toBe('GHS')
        ->and($charge->recorded_by_admin_id)->toBe($admin->id)
        ->and($charge->notes)->toBe('Walk-in counter charge recorded at shipment creation.');
});

test('invalid walk-in charge amount is rejected', function (mixed $amount) {
    $admin = makeWalkinChargeAdmin();

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('warehouse.walkin.store'), validWalkinChargePayload([
            'pickup_fee_amount' => $amount,
        ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('pickup_fee_amount');

    expect(ShipmentCharge::query()->count())->toBe(0);
})->with([-1, 'not-a-number']);

test('walk-in forms handle non-json submit responses before parsing json', function (string $viewPath, string $blockEnd) {
    $source = file_get_contents(resource_path($viewPath));
    $submitShipmentSource = Str::between($source, 'async submitShipment()', $blockEnd);

    expect($submitShipmentSource)
        ->toContain('window.ParcelmanWalkinResponse.parse(res')
        ->toContain('Server error while creating shipment. Please try again.')
        ->not->toContain('const json = await res.json();')
        ->not->toContain('Failed to create shipment.');
})->with([
    'warehouse walk-in form' => ['views/warehouse/walkin/create.blade.php', 'prepareCreatedPackage(pkg)'],
    'admin shipment form' => ['views/admin/shipments/create.blade.php', "    };\n}"],
]);

test('walk-in ajax actions use the shared safe response helper', function (string $viewPath) {
    $source = file_get_contents(resource_path($viewPath));

    expect($source)
        ->toContain("@include('shared.walkin-response-helpers')")
        ->toContain('window.ParcelmanWalkinResponse.parse')
        ->not->toContain('const json = await res.json();');
})->with([
    'warehouse walk-in form' => ['views/warehouse/walkin/create.blade.php'],
    'admin shipment form' => ['views/admin/shipments/create.blade.php'],
]);

test('walk-in store returns json validation errors for ajax requests', function () {
    $admin = makeWalkinChargeAdmin();

    $response = $this
        ->actingAs($admin, 'admin')
        ->post(route('warehouse.walkin.store'), validWalkinChargePayload([
            'pickup_fee_amount' => -1,
        ]), [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

    $response->assertUnprocessable()
        ->assertHeader('content-type', 'application/json')
        ->assertJsonValidationErrors('pickup_fee_amount');
});

test('warehouse walk-in store returns json server error when shipment creation fails', function () {
    $admin = makeWalkinChargeAdmin();

    $this->mock(WalkinShipmentService::class, function ($mock) {
        $mock->shouldReceive('createWalkinShipment')
            ->once()
            ->andThrow(new RuntimeException('Simulated walk-in failure'));
    });

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('warehouse.walkin.store'), validWalkinChargePayload([
            'pickup_fee_amount' => '45.00',
        ]));

    $response->assertStatus(500)
        ->assertHeader('content-type', 'application/json')
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Server error while creating shipment.');
});

test('admin walk-in store returns json server error when shipment creation fails', function () {
    $warehouse = Warehouse::query()->create([
        'name' => 'HQ Walk-in',
        'code' => 'WH-WALKIN-ADMIN',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
    $admin = makeWalkinChargeAdmin($warehouse);

    $this->mock(WalkinShipmentService::class, function ($mock) {
        $mock->shouldReceive('createWalkinShipment')
            ->once()
            ->andThrow(new RuntimeException('Simulated admin walk-in failure'));
    });

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('admin.shipments.store'), validAdminWalkinChargePayload($warehouse, [
            'pickup_fee_amount' => '45.00',
        ]));

    $response->assertStatus(500)
        ->assertHeader('content-type', 'application/json')
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Server error while creating shipment.');
});

test('admin walk-in store endpoint accepts positive optional pickup fee amount', function () {
    $warehouse = Warehouse::query()->create([
        'name' => 'HQ Walk-in',
        'code' => 'WH-WALKIN-ADMIN',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
    $admin = makeWalkinChargeAdmin($warehouse);

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('admin.shipments.store'), validAdminWalkinChargePayload($warehouse, [
            'pickup_fee_amount' => '18.25',
        ]));

    $response->assertOk()
        ->assertJsonPath('success', true);

    $charge = ShipmentCharge::query()->sole();

    expect((float) $charge->amount)->toBe(18.25)
        ->and($charge->charge_type)->toBe(ShipmentCharge::TYPE_PICKUP_FEE)
        ->and($charge->payer_type)->toBe(ShipmentCharge::PAYER_VENDOR)
        ->and($charge->due_stage)->toBe(ShipmentCharge::STAGE_AT_PICKUP)
        ->and($charge->status)->toBe(ShipmentCharge::STATUS_PENDING)
        ->and($charge->recorded_by_admin_id)->toBe($admin->id);
});

test('admin walk-in store endpoint rejects invalid optional pickup fee amount', function () {
    $warehouse = Warehouse::query()->create([
        'name' => 'HQ Walk-in',
        'code' => 'WH-WALKIN-ADMIN',
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
    $admin = makeWalkinChargeAdmin($warehouse);

    $response = $this
        ->actingAs($admin, 'admin')
        ->postJson(route('admin.shipments.store'), validAdminWalkinChargePayload($warehouse, [
            'pickup_fee_amount' => -1,
        ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('pickup_fee_amount');

    expect(ShipmentCharge::query()->count())->toBe(0);
});
