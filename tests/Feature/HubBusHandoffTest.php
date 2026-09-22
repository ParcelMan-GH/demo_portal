<?php

use App\Enums\ItemStatus;
use App\Http\Middleware\LogAdminAuditActivity;
use App\Models\District;
use App\Models\HubBusHandoff;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\SmsService;
use App\Services\StorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Reads or resets the SMS the mocked provider was handed.
 *
 * @param  mixed  $set  Pass a value to record it; omit to read.
 */
function bhhSentSms(mixed $set = '__read__'): ?array
{
    static $sms = null;

    if ($set !== '__read__') {
        $sms = $set;
    }

    return $sms;
}

beforeEach(function () {
    $this->withoutMiddleware(LogAdminAuditActivity::class);

    bhhSentSms(null);

    // No real bucket, no real SMS: the tests care about what was written down
    // and what the customer was told, not about S3 or Arkesel.
    $this->mock(StorageService::class, function ($mock) {
        $mock->shouldReceive('upload')->andReturn([
            'path' => 'hubs/1/bus-handoffs/1/handover.jpg',
            'original_name' => 'handover.jpg',
            'size' => 2048,
        ]);
        $mock->shouldReceive('getUrl')->andReturn('https://cdn.example.test/handover.jpg');
    });

    $this->mock(SmsService::class, function ($mock) {
        $mock->shouldReceive('send')->andReturnUsing(function ($phone, $message) {
            bhhSentSms(['phone' => $phone, 'message' => $message]);

            return true;
        });
    });
});

function bhhHub(string $name = 'Accra Central Hub'): Warehouse
{
    return Warehouse::query()->create([
        'name' => $name,
        'code' => 'WH-'.Str::upper(Str::random(6)),
        'is_active' => true,
        'is_hq' => false,
        'can_administer_system' => false,
    ]);
}

function bhhUser(Warehouse $hub, string $roleSlug = 'external_bus_handoff_agent'): User
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
        'name' => 'Bus Handoff Agent',
        'phone' => '0244'.random_int(100000, 999999),
        'password' => Hash::make('secret-pass'),
        'warehouse_id' => $hub->id,
        'is_active' => true,
    ]);

    $user->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    return $user->fresh();
}

function bhhParcel(Warehouse $hub, array $overrides = []): ShipmentItem
{
    $vendor = Vendor::query()->create([
        'name' => 'Bus Handoff Vendor',
        'business_name' => 'Bus Handoff Shop',
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
        'name' => 'Bus Region '.$suffix,
        'code' => 'R'.$suffix,
        'is_active' => true,
    ]);

    $district = District::query()->create([
        'region_id' => $region->id,
        'name' => 'Bus District '.$suffix,
        'code' => 'D'.$suffix,
        'is_active' => true,
    ]);

    return ShipmentItem::create(array_merge([
        'shipment_id' => $shipment->id,
        'description' => 'Bag of rice',
        'quantity' => 1,
        'status' => ItemStatus::ARRIVED_AT_HUB->value,
        'tracking_code' => 'TRK-'.Str::upper(Str::random(8)),
        'delivery_recipient_name' => 'Ama Recipient',
        'delivery_recipient_phone' => '0241234567',
        'delivery_region_id' => $region->id,
        'delivery_district_id' => $district->id,
        'delivery_town' => 'Kumasi',
        'hub_id' => $hub->id,
    ], $overrides));
}

function bhhAdmin(Warehouse $warehouse, array $permissionNames = ['shipments.view']): User
{
    $admin = User::factory()->create([
        'name' => 'Bus Handoff Admin',
        'email' => 'bus-admin-'.Str::lower(Str::random(8)).'@example.test',
        'is_active' => true,
        'warehouse_id' => $warehouse->id,
    ]);

    $role = Role::create([
        'name' => 'Bus Handoff Role '.$admin->id,
        'slug' => 'bus-handoff-role-'.$admin->id,
        'description' => 'Bus handoff test role',
        'is_system_role' => false,
        'is_warehouse_role' => false,
        'is_assignable_by_warehouse_manager' => false,
        'is_active' => true,
    ]);

    foreach ($permissionNames as $permissionName) {
        [$module, $action] = array_pad(explode('.', $permissionName, 2), 2, 'view');

        $permission = Permission::firstOrCreate(
            ['name' => $permissionName],
            ['module' => $module, 'action' => $action, 'description' => $permissionName],
        );

        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $admin->roles()->attach($role->id, [
        'assigned_at' => now(),
        'assigned_by' => $admin->id,
    ]);

    return $admin->fresh();
}

/**
 * Post a handover the way the app does: multipart, so the photo rides along.
 */
function bhhSubmit(object $test, array $payload, bool $withPhoto = true, array $headers = ['Accept' => 'application/json'])
{
    if ($withPhoto && ! isset($payload['proof_photo'])) {
        $payload['proof_photo'] = UploadedFile::fake()->create('handover.jpg', 250, 'image/jpeg');
    }

    return $test->post('/api/v1/hub/handoffs', $payload, $headers);
}

function bhhValidPayload(ShipmentItem $item, array $overrides = []): array
{
    return array_merge([
        'code' => $item->tracking_code,
        'driver_name' => 'Kwame Bus Driver',
        'driver_phone' => '0201112233',
        'vehicle_plate' => 'GT 1234-22',
        'vehicle_description' => 'White Toyota Hiace',
        'bus_company' => 'VIP Transport',
    ], $overrides);
}

// ------------------------------------------------------- the agent's scan

test('a bus handoff agent scans a parcel and gets it back with its destination', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/handoffs/lookup?code='.$item->tracking_code)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.package.tracking_code', $item->tracking_code)
        ->assertJsonPath('data.package.recipient_phone', '0241234567')
        ->assertJsonPath('data.package.destination', District::find($item->delivery_district_id)->name
            .', '.Region::find($item->delivery_region_id)->name);
});

test('the scan is refused for a parcel held at another hub', function () {
    $hub = bhhHub();
    $otherHub = bhhHub('Kumasi Hub');
    $agent = bhhUser($hub);
    $item = bhhParcel($otherHub);

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/handoffs/lookup?code='.$item->tracking_code)
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

test('the scan is refused for a parcel that is not yet in the hub', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub, ['status' => ItemStatus::IN_TRANSIT->value]);

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/handoffs/lookup?code='.$item->tracking_code)
        ->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('the scan is refused for an unknown barcode', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/handoffs/lookup?code=NOT-A-REAL-CODE')
        ->assertStatus(404)
        ->assertJsonPath('success', false);
});

// ----------------------------------------------------------- the handover

test('submitting a handover records it, dispatches the parcel and texts the customer', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    bhhSubmit($this, bhhValidPayload($item))
        ->assertCreated()
        ->assertJsonPath('success', true);

    $handoff = HubBusHandoff::query()->firstOrFail();

    expect($handoff->shipment_item_id)->toBe($item->id)
        ->and($handoff->hub_id)->toBe($hub->id)
        ->and($handoff->handed_off_by)->toBe($agent->id)
        ->and($handoff->driver_name)->toBe('Kwame Bus Driver')
        ->and($handoff->vehicle_plate)->toBe('GT 1234-22')
        ->and($handoff->bus_company)->toBe('VIP Transport')
        ->and($handoff->proof_photo_path)->toBe('hubs/1/bus-handoffs/1/handover.jpg')
        ->and($handoff->destination)->toContain('Bus District')
        ->and($handoff->sms_sent_at)->not->toBeNull();

    expect($item->fresh()->status)->toBe(ItemStatus::DISPATCHED_TO_BUS)
        ->and($item->fresh()->dispatched_to_bus_at)->not->toBeNull();

    expect(ShipmentItemTracking::query()
        ->where('shipment_item_id', $item->id)
        ->where('status', ItemStatus::DISPATCHED_TO_BUS->value)
        ->exists())->toBeTrue();

    $sms = bhhSentSms();

    expect($sms)->not->toBeNull()
        ->and($sms['message'])->toContain($item->tracking_code)
        ->and($sms['message'])->toContain('/handover/')
        ->and($sms['phone'])->toContain('241234567');
});

test('a handover without a photo is refused', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/hub/handoffs', bhhValidPayload($item))
        ->assertStatus(422)
        ->assertJsonValidationErrors('proof_photo');

    expect(HubBusHandoff::count())->toBe(0)
        ->and($item->fresh()->status)->toBe(ItemStatus::ARRIVED_AT_HUB);
});

test('a handover without a driver name is refused', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    bhhSubmit($this, bhhValidPayload($item, ['driver_name' => '']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('driver_name');
});

test('the same parcel cannot be handed over twice', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    bhhSubmit($this, bhhValidPayload($item))->assertCreated();

    bhhSubmit($this, bhhValidPayload($item, ['driver_name' => 'Second Driver']))
        ->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(HubBusHandoff::count())->toBe(1);
});

test('a failed text is recorded against the handover instead of losing it', function () {
    $this->mock(SmsService::class, function ($mock) {
        $mock->shouldReceive('send')->andReturn(false);
    });

    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    bhhSubmit($this, bhhValidPayload($item))->assertCreated();

    $handoff = HubBusHandoff::query()->firstOrFail();

    expect($handoff->sms_sent_at)->toBeNull()
        ->and($handoff->sms_failed_at)->not->toBeNull()
        ->and($handoff->sms_error)->not->toBeNull();

    // The handover still happened, even though the customer was not told.
    expect($item->fresh()->status)->toBe(ItemStatus::DISPATCHED_TO_BUS);
});

// ------------------------------------------------------ the customer's link

test('the customer link opens the handover photo', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);

    bhhSubmit($this, bhhValidPayload($item))->assertCreated();

    preg_match('~/handover/([A-Z0-9]+)~', bhhSentSms()['message'], $matches);
    $token = $matches[1] ?? null;

    expect($token)->not->toBeNull();

    $this->get('/handover/'.$token)
        ->assertOk()
        ->assertSee($item->tracking_code)
        ->assertSee('https://cdn.example.test/handover.jpg');

    // The driver's phone number is not shown to the customer in full.
    $this->get('/handover/'.$token)->assertDontSee('0201112233');
});

test('an unknown link shows the unavailable state rather than an error', function () {
    $this->get('/handover/NOTAREALTOKEN')
        ->assertOk()
        ->assertSee('Link unavailable');
});

test('an expired link shows the unavailable state', function () {
    $hub = bhhHub();
    $item = bhhParcel($hub);

    $handoff = HubBusHandoff::query()->create([
        'shipment_item_id' => $item->id,
        'hub_id' => $hub->id,
        'driver_name' => 'Kwame Bus Driver',
        'proof_photo_path' => 'hubs/1/bus-handoffs/1/handover.jpg',
        'public_token_hash' => hash('sha256', 'EXPIREDTOKEN'),
        'public_token_expires_at' => now()->subDay(),
    ]);

    $this->get('/handover/EXPIREDTOKEN')
        ->assertOk()
        ->assertSee('Link unavailable');

    expect($handoff->publicLinkExpired())->toBeTrue();
});

// ------------------------------------------------------------- the agent's list

test('a bus handoff agent sees only the handovers they made', function () {
    $hub = bhhHub();
    $firstAgent = bhhUser($hub);
    $secondAgent = bhhUser($hub);

    Sanctum::actingAs($firstAgent);
    bhhSubmit($this, bhhValidPayload(bhhParcel($hub)))->assertCreated();

    Sanctum::actingAs($secondAgent);
    bhhSubmit($this, bhhValidPayload(bhhParcel($hub)))->assertCreated();

    Sanctum::actingAs($firstAgent);

    $response = $this->getJson('/api/v1/hub/handoffs')->assertOk();

    expect($response->json('data.handoffs'))->toHaveCount(1)
        ->and($response->json('data.pagination.total'))->toBe(1)
        ->and($response->json('data.summary.today'))->toBe(1);
});

test('a hub agent sees every handover their hub made, not just their own', function () {
    $hub = bhhHub();
    $busAgent = bhhUser($hub);
    $hubAgent = bhhUser($hub, 'external_hub_agent');

    Sanctum::actingAs($busAgent);
    bhhSubmit($this, bhhValidPayload(bhhParcel($hub)))->assertCreated();

    Sanctum::actingAs($hubAgent);

    $response = $this->getJson('/api/v1/hub/handoffs')->assertOk();

    expect($response->json('data.handoffs'))->toHaveCount(1);
});

test('a handover from another hub is not readable', function () {
    $hub = bhhHub();
    $otherHub = bhhHub('Kumasi Hub');
    $agent = bhhUser($hub);
    $otherAgent = bhhUser($otherHub);

    Sanctum::actingAs($otherAgent);
    bhhSubmit($this, bhhValidPayload(bhhParcel($otherHub)))->assertCreated();

    $handoff = HubBusHandoff::query()->firstOrFail();

    Sanctum::actingAs($agent);

    $this->getJson('/api/v1/hub/handoffs/'.$handoff->id)
        ->assertStatus(403)
        ->assertJsonPath('success', false);
});

// ------------------------------------------------------------------- admin

test('the admin can see a handover and its photo', function () {
    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);
    bhhSubmit($this, bhhValidPayload($item))->assertCreated();

    $admin = bhhAdmin($hub);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.hub-handoffs.index'))
        ->assertOk()
        ->assertSee($item->tracking_code)
        ->assertSee('Kwame Bus Driver');

    $handoff = HubBusHandoff::query()->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.hub-handoffs.show', $handoff))
        ->assertOk()
        ->assertSee('Handover photo')
        ->assertSee('https://cdn.example.test/handover.jpg')
        ->assertSee('Kwame Bus Driver')
        ->assertSee('VIP Transport');
});

test('the admin list can be filtered to handovers where the customer was not texted', function () {
    $this->mock(SmsService::class, function ($mock) {
        $mock->shouldReceive('send')->andReturn(false);
    });

    $hub = bhhHub();
    $agent = bhhUser($hub);
    $item = bhhParcel($hub);

    Sanctum::actingAs($agent);
    bhhSubmit($this, bhhValidPayload($item))->assertCreated();

    $admin = bhhAdmin($hub);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.hub-handoffs.index', ['sms' => 'failed']))
        ->assertOk()
        ->assertSee($item->tracking_code);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.hub-handoffs.index', ['sms' => 'sent']))
        ->assertOk()
        ->assertDontSee($item->tracking_code);
});
