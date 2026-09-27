<?php

use App\Http\Controllers\Admin\ShipmentController;
use App\Models\District;
use App\Models\Region;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\ChargesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * A delivery fee used to live in two places: the walk-in intake wrote it onto
 * the package, while every screen (receiving workspace, API, mobile app) reads
 * it from the charges ledger. These tests pin the behaviour that makes one
 * captured fee visible everywhere.
 */
function dfuWarehouse(): Warehouse
{
    return Warehouse::query()->create([
        'name' => 'Fee Test Hub',
        'code' => 'WH-'.Str::upper(Str::random(6)),
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);
}

function dfuAdmin(Warehouse $warehouse): User
{
    return User::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);
}

function dfuDestination(): array
{
    $suffix = Str::upper(Str::random(4));

    $region = Region::query()->create([
        'name' => 'Fee Region '.$suffix,
        'code' => 'R'.$suffix,
        'is_active' => true,
    ]);

    $district = District::query()->create([
        'region_id' => $region->id,
        'name' => 'Fee District '.$suffix,
        'code' => 'D'.$suffix,
        'is_active' => true,
    ]);

    return [$region, $district];
}

/**
 * A walk-in payload in the shape the warehouse form posts.
 */
function dfuWalkinPayload(Warehouse $warehouse, array $itemOverrides = []): array
{
    [$region, $district] = dfuDestination();

    $vendor = Vendor::query()->create([
        'name' => 'Fee Test Vendor',
        'business_name' => 'Fee Test Shop',
        'phone' => '+2332'.random_int(10000000, 99999999),
        'is_active' => true,
    ]);

    return [
        'vendor_id' => $vendor->id,
        'warehouse_id' => $warehouse->id,
        'fulfillment_type' => 'warehouse',
        'delivery_preference' => 'deliver',
        'destination_mode' => 'per_item',
        'items' => [
            array_merge([
                'description' => 'Counter package',
                'quantity' => 1,
                'delivery_method' => 'direct',
                'delivery' => [
                    'recipient_name' => 'Ama Recipient',
                    'recipient_phone' => '0241234567',
                    'region_id' => $region->id,
                    'district_id' => $district->id,
                    'town' => 'Osu',
                    'instructions' => 'Call on arrival',
                ],
            ], $itemOverrides),
        ],
    ];
}

/**
 * A package with the fee agreed at intake and no ledger line yet.
 */
function dfuItemWithIntakeFee(string $fee = '30.00'): ShipmentItem
{
    $vendor = Vendor::query()->create([
        'name' => 'Ledger Vendor',
        'business_name' => 'Ledger Shop',
        'phone' => '+2332'.random_int(10000000, 99999999),
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'SHP-'.Str::upper(Str::random(10)),
        'status' => 'draft',
        'source' => 'vendor_app',
        'destination_mode' => 'per_item',
        'fulfillment_type' => 'warehouse',
        'delivery_preference' => 'deliver',
    ]);

    return ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Ledger parcel',
        'quantity' => 1,
        'delivery_fee' => $fee,
        'status' => 'at_warehouse',
        'tracking_code' => 'TRK-'.Str::upper(Str::random(8)),
    ]);
}

test('walk-in intake writes the delivery fee into the charges ledger', function () {
    $warehouse = dfuWarehouse();

    $this->actingAs(dfuAdmin($warehouse), 'admin')
        ->postJson(route('warehouse.walkin.store'), dfuWalkinPayload($warehouse, [
            'delivery_fee' => '45.00',
        ]))
        ->assertOk()
        ->assertJsonPath('success', true);

    $item = ShipmentItem::query()->sole();
    expect((float) $item->delivery_fee)->toBe(45.0);

    // The ledger line is what the order view, the API and the app read.
    $charge = ShipmentCharge::query()
        ->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)
        ->sole();

    expect((float) $charge->amount)->toBe(45.0)
        ->and($charge->shipment_item_id)->toBe($item->id)
        ->and($charge->shipment_id)->toBe($item->shipment_id)
        ->and($charge->payer_type)->toBe(ShipmentCharge::PAYER_RECIPIENT)
        ->and($charge->due_stage)->toBe(ShipmentCharge::STAGE_AT_DELIVERY)
        ->and($charge->direction)->toBe(ShipmentCharge::DIRECTION_REVENUE)
        ->and($charge->status)->toBe(ShipmentCharge::STATUS_PENDING);
});

test('walk-in intake without a fee leaves the ledger empty', function () {
    $warehouse = dfuWarehouse();

    $this->actingAs(dfuAdmin($warehouse), 'admin')
        ->postJson(route('warehouse.walkin.store'), dfuWalkinPayload($warehouse))
        ->assertOk();

    expect(ShipmentCharge::query()->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)->count())->toBe(0);
});

test('re-syncing a delivery fee updates the same ledger line instead of duplicating it', function () {
    $item = dfuItemWithIntakeFee('30.00');
    $charges = app(ChargesService::class);

    $first = $charges->syncDeliveryFeeForItem($item, 30.00);
    $second = $charges->syncDeliveryFeeForItem($item, 55.50);

    expect($second->id)->toBe($first->id)
        ->and((float) $second->amount)->toBe(55.5)
        ->and(ShipmentCharge::count())->toBe(1);
});

test('clearing the fee cancels the outstanding ledger line', function () {
    $item = dfuItemWithIntakeFee('30.00');
    $charges = app(ChargesService::class);

    $charges->syncDeliveryFeeForItem($item, 30.00);

    expect($charges->syncDeliveryFeeForItem($item, 0.0))->toBeNull();

    $charge = ShipmentCharge::query()->sole();
    expect($charge->status)->toBe(ShipmentCharge::STATUS_CANCELLED);
    expect($charges->deliveryFeeChargesForItem($item))->toBeEmpty();
});

test('a settled delivery fee is not reopened by a later collect sync', function () {
    $item = dfuItemWithIntakeFee('30.00');
    $charges = app(ChargesService::class);

    $paid = $charges->syncDeliveryFeeForItem($item, 30.00, ['mode' => 'paid'], dfuAdmin(dfuWarehouse()));
    expect($paid->status)->toBe(ShipmentCharge::STATUS_PAID);
    expect($paid->paid_at)->not->toBeNull();

    $charges->syncDeliveryFeeForItem($item, 99.00, ['mode' => 'collect']);

    $charge = ShipmentCharge::query()->sole();
    expect($charge->status)->toBe(ShipmentCharge::STATUS_PAID)
        ->and((float) $charge->amount)->toBe(30.0);
});

test('the order view reports a fee agreed at intake even without a ledger line', function () {
    $item = dfuItemWithIntakeFee('25.00');

    $controller = app(ShipmentController::class);
    $method = new ReflectionMethod($controller, 'serializeReceivingPackageDeliveryFee');

    $fee = $method->invoke($controller, $item->shipment, $item);

    expect($fee['status'])->toBe('collect')
        ->and($fee['mode'])->toBe('collect')
        ->and($fee['amount'])->toBe(25.0)
        ->and($fee['outstanding_amount'])->toBe(25.0);
});

test('the order view still reports no fee when nothing was recorded', function () {
    $item = dfuItemWithIntakeFee('0.00');

    $controller = app(ShipmentController::class);
    $method = new ReflectionMethod($controller, 'serializeReceivingPackageDeliveryFee');

    $fee = $method->invoke($controller, $item->shipment, $item);

    expect($fee['status'])->toBe('none')
        ->and($fee['amount'])->toBeNull();
});

test('the ledger line is what the order view reports when both exist', function () {
    $item = dfuItemWithIntakeFee('25.00');

    // A different figure was agreed again in the receiving workspace.
    app(ChargesService::class)->syncDeliveryFeeForItem($item, 40.00);

    $controller = app(ShipmentController::class);
    $method = new ReflectionMethod($controller, 'serializeReceivingPackageDeliveryFee');

    $fee = $method->invoke($controller, $item->shipment, $item);

    expect($fee['amount'])->toBe(40.0)
        ->and($fee['outstanding_amount'])->toBe(40.0);
});
