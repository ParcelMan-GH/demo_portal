<?php

use App\Enums\ItemStatus;
use App\Models\District;
use App\Models\OutgoingBatch;
use App\Models\Region;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function hubWarehouse(string $name = 'Accra Central Hub'): Warehouse
{
    return Warehouse::query()->create([
        'name' => $name,
        'code' => 'WH-'.Str::upper(Str::random(6)),
        'is_active' => true,
        'is_hq' => false,
        'can_administer_system' => false,
    ]);
}

/**
 * A hub-side user with the given role slug, working out of $hub.
 */
function hubUser(Warehouse $hub, string $roleSlug = 'external_hub_agent', string $password = 'secret-pass'): User
{
    $role = Role::query()->firstOrCreate(
        ['slug' => $roleSlug],
        [
            'name' => Str::headline($roleSlug),
            'is_system_role' => true,
            'is_warehouse_role' => true,
            'is_assignable_by_warehouse_manager' => false,
            'is_active' => true,
        ],
    );

    $user = User::factory()->create([
        'name' => 'Hub Agent',
        'phone' => '0244'.random_int(100000, 999999),
        'password' => Hash::make($password),
        'warehouse_id' => $hub->id,
        'is_active' => true,
    ]);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    return $user->fresh();
}

/**
 * A parcel sitting at a hub, optionally inside a batch.
 */
function hubParcel(array $overrides = []): ShipmentItem
{
    // Created per parcel on purpose: RefreshDatabase wipes the database between
    // tests, so nothing may be cached across calls.
    $vendor = Vendor::query()->create([
        'name' => 'Hub Test Vendor',
        'business_name' => 'Hub Test Shop',
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

    $suffix = Str::upper(Str::random(4));

    $region = Region::query()->create([
        'name' => 'Hub Region '.$suffix,
        'code' => 'R'.$suffix,
        'is_active' => true,
    ]);

    $district = District::query()->create([
        'region_id' => $region->id,
        'name' => 'Hub District '.$suffix,
        'code' => 'D'.$suffix,
        'is_active' => true,
    ]);

    return ShipmentItem::create(array_merge([
        'shipment_id' => $shipment->id,
        'description' => 'Parcel for hub',
        'quantity' => 1,
        'status' => ItemStatus::READY_FOR_HUB_TRANSFER->value,
        'tracking_code' => 'TRK-'.Str::upper(Str::random(8)),
        'delivery_recipient_name' => 'Ama Recipient',
        'delivery_recipient_phone' => '0241234567',
        'delivery_region_id' => $region->id,
        'delivery_district_id' => $district->id,
        'delivery_town' => 'Kumasi',
    ], $overrides));
}

function hubBatchWithItems(Warehouse $hub, int $count = 2): array
{
    $first = hubParcel();

    $batch = OutgoingBatch::query()->create([
        'batch_number' => 'BATCH-'.strtoupper(Str::random(6)),
        'delivery_region_id' => $first->delivery_region_id,
        'delivery_district_id' => $first->delivery_district_id,
        'status' => OutgoingBatch::STATUS_OPEN,
    ]);

    $first->update(['outgoing_batch_id' => $batch->id]);

    $items = [$first];

    for ($i = 1; $i < $count; $i++) {
        $item = hubParcel([
            'delivery_region_id' => $first->delivery_region_id,
            'delivery_district_id' => $first->delivery_district_id,
            'outgoing_batch_id' => $batch->id,
        ]);

        $items[] = $item;
    }

    return [$batch, $items];
}

// ---------------------------------------------------------------- login

test('a hub agent signs in and receives a token for their hub', function () {
    $hub = hubWarehouse('Kumasi Hub');
    $agent = hubUser($hub, 'external_hub_agent', 'secret-pass');

    $response = $this->postJson('/api/v1/hub/login', [
        'phone' => $agent->phone,
        'password' => 'secret-pass',
        'role' => 'hub_agent',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('user.role', 'hub_agent')
        ->assertJsonPath('user.hub_id', $hub->id)
        ->assertJsonPath('user.hub_name', 'Kumasi Hub');

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('the bus handoff role signs in through the same endpoint', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub, 'external_bus_handoff_agent');

    $this->postJson('/api/v1/hub/login', [
        'phone' => $agent->phone,
        'password' => 'secret-pass',
        'role' => 'bus_handoff',
    ])
        ->assertOk()
        ->assertJsonPath('user.role', 'bus_handoff')
        ->assertJsonPath('user.role_slug', 'external_bus_handoff_agent');
});

test('a user whose assigned role does not match the portal is refused', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub, 'external_hub_agent');

    $this->postJson('/api/v1/hub/login', [
        'phone' => $agent->phone,
        'password' => 'secret-pass',
        'role' => 'bus_handoff',
    ])->assertStatus(403);
});

test('a user with no hub assigned cannot sign in', function () {
    $user = User::factory()->create([
        'phone' => '0244999888',
        'password' => Hash::make('secret-pass'),
        'warehouse_id' => null,
        'is_active' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'External Hub Agent',
        'slug' => 'external_hub_agent',
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    $user->roles()->attach($role->id, ['assigned_at' => now(), 'assigned_by' => $user->id]);

    $this->postJson('/api/v1/hub/login', [
        'phone' => '0244999888',
        'password' => 'secret-pass',
        'role' => 'hub_agent',
    ])->assertStatus(403);
});

test('a wrong password is rejected', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);

    $this->postJson('/api/v1/hub/login', [
        'phone' => $agent->phone,
        'password' => 'not-the-password',
        'role' => 'hub_agent',
    ])->assertStatus(422);
});

// ------------------------------------------------------- access control

test('the hub endpoints require a token', function () {
    $this->getJson('/api/v1/hub/inventory')->assertStatus(401);
});

test('a signed in user without a hub role is refused', function () {
    $hub = hubWarehouse();
    $outsider = User::factory()->create(['warehouse_id' => $hub->id, 'is_active' => true]);

    Sanctum::actingAs($outsider);

    $this->getJson('/api/v1/hub/inventory')->assertStatus(403);
});

// --------------------------------------------------------------- intake

test('checking in a batch marks every parcel as arrived at the hub', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);

    $response = $this->postJson('/api/v1/hub/batches/intake', [
        'batch_number' => $batch->batch_number,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.received_count', 2)
        ->assertJsonPath('data.already_at_hub_count', 0);

    foreach ($items as $item) {
        $item->refresh();

        expect($item->status)->toBe(ItemStatus::ARRIVED_AT_HUB)
            ->and($item->hub_id)->toBe($hub->id)
            ->and($item->arrived_at_hub_at)->not->toBeNull()
            ->and($item->pickup_code)->not->toBeNull();
    }

    // Audited, with the hub recorded as the location.
    expect(ShipmentItemTracking::query()->where('status', ItemStatus::ARRIVED_AT_HUB->value)->count())->toBe(2);
    expect(ShipmentItemTracking::query()->first()->location)->toBe($hub->name);
});

test('a single barcode can be checked in against its batch', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', [
        'batch_number' => $batch->batch_number,
        'barcode' => $items[0]->tracking_code,
    ])
        ->assertOk()
        ->assertJsonPath('data.received_count', 1);

    expect($items[0]->fresh()->status)->toBe(ItemStatus::ARRIVED_AT_HUB)
        ->and($items[1]->fresh()->status)->toBe(ItemStatus::READY_FOR_HUB_TRANSFER);
});

test('a parcel that does not belong to the scanned batch is rejected', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch] = hubBatchWithItems($hub, 1);
    $stray = hubParcel();

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', [
        'batch_number' => $batch->batch_number,
        'barcode' => $stray->tracking_code,
    ])->assertStatus(422);

    expect($stray->fresh()->status)->toBe(ItemStatus::READY_FOR_HUB_TRANSFER);
    expect($stray->fresh()->hub_id)->toBeNull();
});

test('re-scanning a parcel already at the hub does not move it twice', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 1);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $firstStamp = $items[0]->fresh()->arrived_at_hub_at;

    $this->postJson('/api/v1/hub/batches/intake', [
        'batch_number' => $batch->batch_number,
        'barcode' => $items[0]->tracking_code,
    ])
        ->assertOk()
        ->assertJsonPath('data.received_count', 0)
        ->assertJsonPath('data.already_at_hub_count', 1);

    expect($items[0]->fresh()->arrived_at_hub_at->equalTo($firstStamp))->toBeTrue();
    expect(ShipmentItemTracking::query()->count())->toBe(1);
});

test('a shelf location can be recorded at check in', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 1);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', [
        'batch_number' => $batch->batch_number,
        'shelf_location' => 'Shelf C3',
    ])->assertOk();

    expect($items[0]->fresh()->shelf_location)->toBe('Shelf C3');
});

// ------------------------------------------------------------ inventory

test('inventory lists what the hub is holding', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $response = $this->getJson('/api/v1/hub/inventory');

    $response->assertOk()
        ->assertJsonPath('data.counts.at_hub', 2)
        ->assertJsonCount(2, 'data.packages');

    $package = collect($response->json('data.packages'))->firstWhere('id', (string) $items[0]->id);

    expect($package)->not->toBeNull()
        ->and($package['tracking_code'])->toBe($items[0]->tracking_code)
        ->and($package['status'])->toBe(ItemStatus::ARRIVED_AT_HUB->value)
        ->and($package['status_label'])->toBe('Arrived at Hub')
        ->and($package['destination']['town'])->toBe('Kumasi');
});

test('inventory can be searched and filtered', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $this->getJson('/api/v1/hub/inventory?search='.$items[1]->tracking_code)
        ->assertOk()
        ->assertJsonCount(1, 'data.packages');

    $this->getJson('/api/v1/hub/inventory?status=dispatched_to_bus')
        ->assertOk()
        ->assertJsonCount(0, 'data.packages');

    $this->getJson('/api/v1/hub/inventory?status=not_a_status')->assertStatus(422);
});

test('inventory never shows another hub parcels', function () {
    $mine = hubWarehouse('My Hub');
    $theirs = hubWarehouse('Other Hub');
    $agent = hubUser($mine);
    [$batch] = hubBatchWithItems($theirs, 1);

    // Somebody else's hub checked this batch in.
    $otherAgent = hubUser($theirs);
    Sanctum::actingAs($otherAgent);
    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/inventory')
        ->assertOk()
        ->assertJsonCount(0, 'data.packages')
        ->assertJsonPath('data.counts.at_hub', 0);
});

// -------------------------------------------------------------- handoff

test('handing a batch to a bus dispatches its parcels', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $this->postJson('/api/v1/hub/batches/handoff', [
        'batch_number' => $batch->batch_number,
        'driver_name' => 'Kofi Mensah',
        'driver_phone' => '0244555666',
        'vehicle_plate' => 'GR-1234-22',
        'bus_company' => 'VIP Jeoun',
    ])
        ->assertOk()
        ->assertJsonPath('data.dispatched_count', 2)
        ->assertJsonPath('data.batch_status', OutgoingBatch::STATUS_DISPATCHED);

    foreach ($items as $item) {
        expect($item->fresh()->status)->toBe(ItemStatus::DISPATCHED_TO_BUS)
            ->and($item->fresh()->dispatched_to_bus_at)->not->toBeNull();
    }

    expect($batch->fresh()->status)->toBe(OutgoingBatch::STATUS_DISPATCHED);
});

test('a batch can be listed for handoff with its parcel count', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);
    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $response = $this->getJson('/api/v1/hub/batches')->assertOk();

    $listed = collect($response->json('data.batches'))->firstWhere('batch_number', $batch->batch_number);

    expect($listed)->not->toBeNull()
        ->and($listed['package_count'])->toBe(2);
});

// -------------------------------------------------------------- release

test('releasing to the recipient needs the pickup code', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 1);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    // Missing code.
    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $items[0]->tracking_code,
        'release_to' => 'recipient',
    ])->assertStatus(422);

    // Wrong code.
    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $items[0]->tracking_code,
        'release_to' => 'recipient',
        'confirmation_code' => '0000',
    ])->assertStatus(422);

    // Right code.
    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $items[0]->tracking_code,
        'release_to' => 'recipient',
        'confirmation_code' => $items[0]->fresh()->pickup_code,
    ])->assertOk();

    expect($items[0]->fresh()->status)->toBe(ItemStatus::DELIVERED)
        ->and($items[0]->fresh()->released_at)->not->toBeNull();
});

test('releasing to a rider sends the parcel out for delivery', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 1);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $items[0]->tracking_code,
        'release_to' => 'driver',
        'driver_name' => 'Rider Kwame',
    ])->assertOk();

    expect($items[0]->fresh()->status)->toBe(ItemStatus::OUT_FOR_DELIVERY);
});

test('a hub cannot release a parcel it is not holding', function () {
    $hub = hubWarehouse('My Hub');
    $agent = hubUser($hub);
    $stray = hubParcel();

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $stray->tracking_code,
        'release_to' => 'recipient',
    ])->assertStatus(403);
});

test('a released parcel cannot be released again', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch, $items] = hubBatchWithItems($hub, 1);

    Sanctum::actingAs($agent);
    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $code = $items[0]->fresh()->pickup_code;

    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $items[0]->tracking_code,
        'release_to' => 'recipient',
        'confirmation_code' => $code,
    ])->assertOk();

    $this->postJson('/api/v1/hub/packages/release', [
        'barcode' => $items[0]->tracking_code,
        'release_to' => 'recipient',
        'confirmation_code' => $code,
    ])->assertStatus(422);
});

// ------------------------------------------------------------- activity

test('hub activity reports what happened at the hub', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch] = hubBatchWithItems($hub, 1);

    Sanctum::actingAs($agent);
    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $response = $this->getJson('/api/v1/hub/activity')->assertOk();

    expect($response->json('data.activities'))->toHaveCount(1);
    expect($response->json('data.activities.0.title'))->toBe('Arrived at Hub');
    expect($response->json('data.activities.0.location'))->toBe($hub->name);
});

test('me returns the signed in hub', function () {
    $hub = hubWarehouse('Tema Hub');
    $agent = hubUser($hub);

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/me')
        ->assertOk()
        ->assertJsonPath('data.hub.id', $hub->id)
        ->assertJsonPath('data.hub.name', 'Tema Hub');
});

test('a batch manifest can be reviewed before check in', function () {
    $hub = hubWarehouse();
    $agent = hubUser($hub);
    [$batch] = hubBatchWithItems($hub, 2);

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/batches/'.$batch->batch_number)
        ->assertOk()
        ->assertJsonPath('data.batch.batch_number', $batch->batch_number)
        ->assertJsonPath('data.batch.total_parcels', 2)
        ->assertJsonPath('data.batch.already_at_this_hub', 0)
        ->assertJsonCount(2, 'data.packages');

    // After check in the same manifest reports them as already held here.
    $this->postJson('/api/v1/hub/batches/intake', ['batch_number' => $batch->batch_number])->assertOk();

    $this->getJson('/api/v1/hub/batches/'.$batch->batch_number)
        ->assertOk()
        ->assertJsonPath('data.batch.already_at_this_hub', 2);
});

test('an unknown batch cannot be reviewed', function () {
    $agent = hubUser(hubWarehouse());

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/batches/BATCH-DOES-NOT-EXIST')->assertStatus(404);
});
