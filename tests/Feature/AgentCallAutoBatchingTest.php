<?php

use App\Enums\ItemStatus;
use App\Models\AgentCallLog;
use App\Models\District;
use App\Models\OutgoingBatch;
use App\Models\OutgoingBatchAssignmentEvent;
use App\Models\Region;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function acbRegion(string $code): Region
{
    return Region::create([
        'name' => 'Region '.$code,
        'code' => $code,
        'is_active' => true,
    ]);
}

function acbDistrict(Region $region, string $code): District
{
    return District::create([
        'region_id' => $region->id,
        'name' => 'District '.$code,
        'code' => $code,
        'is_active' => true,
    ]);
}

function acbAgent(): User
{
    return User::factory()->create(['is_active' => true]);
}

/**
 * A claimed parcel, optionally given a destination.
 */
function acbParcel(?Region $region = null, ?District $district = null, array $overrides = []): ShipmentItem
{
    $vendor = Vendor::create([
        'name' => 'Auto Batch Vendor',
        'business_name' => 'Auto Batch Logistics',
        'phone' => '+2332'.random_int(10000000, 99999999),
        'email' => 'autobatch-'.Str::lower(Str::random(8)).'@example.test',
        'is_active' => true,
    ]);

    $shipment = Shipment::create([
        'vendor_id' => $vendor->id,
        'shipment_number' => 'SHP-'.Str::upper(Str::random(10)),
        'status' => 'draft',
        'source' => 'vendor_app',
        'destination_mode' => 'single',
        'fulfillment_type' => 'warehouse',
        'delivery_preference' => 'deliver',
    ]);

    return ShipmentItem::create(array_merge([
        'shipment_id' => $shipment->id,
        'description' => 'Parcel '.Str::upper(Str::random(4)),
        'quantity' => 1,
        'status' => ItemStatus::PICKED_UP->value,
        'tracking_code' => 'TRK-'.Str::upper(Str::random(8)),
        'delivery_region_id' => $region?->id,
        'delivery_district_id' => $district?->id,
        'delivery_town' => 'Lapaz',
    ], $overrides));
}

it('creates an outgoing batch for the destination when payment is confirmed', function () {
    $region = acbRegion('GAR');
    $district = acbDistrict($region, 'AMA');
    $agent = acbAgent();
    $parcel = acbParcel($region, $district);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/agent/calls/log', [
        'parcel_id' => $parcel->id,
        'outcome' => 'confirmed',
        'amount_paid' => '150.00',
        'notes' => 'Recipient paid on the phone',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.batching.result', 'batch_created');

    $parcel->refresh();

    expect($parcel->outgoing_batch_id)->not->toBeNull();
    expect($parcel->status)->toBe(ItemStatus::READY_FOR_HUB_TRANSFER);
    expect($parcel->agent_id)->toBe($agent->id);

    $batch = OutgoingBatch::findOrFail($parcel->outgoing_batch_id);
    expect($batch->delivery_region_id)->toBe($region->id);
    expect($batch->delivery_district_id)->toBe($district->id);
    expect($batch->status)->toBe(OutgoingBatch::STATUS_OPEN);

    expect(AgentCallLog::count())->toBe(1);
    expect(AgentCallLog::first()->outcome)->toBe(AgentCallLog::OUTCOME_CONFIRMED);

    $event = OutgoingBatchAssignmentEvent::firstOrFail();
    expect($event->event_type)->toBe(OutgoingBatchAssignmentEvent::EVENT_BATCH_CREATED);
    expect($event->source)->toBe(OutgoingBatchAssignmentEvent::SOURCE_AGENT_CALL);
    expect($event->actor_user_id)->toBe($agent->id);
});

it('adds a second parcel for the same destination to the batch already open', function () {
    $region = acbRegion('ASH');
    $district = acbDistrict($region, 'KUM');
    $agent = acbAgent();

    $first = acbParcel($region, $district);
    $second = acbParcel($region, $district);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/agent/calls/log', ['parcel_id' => $first->id, 'outcome' => 'confirmed'])->assertOk();
    $this->postJson('/api/v1/agent/calls/log', ['parcel_id' => $second->id, 'outcome' => 'confirmed'])
        ->assertOk()
        ->assertJsonPath('data.batching.result', 'batch_attached');

    expect(OutgoingBatch::count())->toBe(1);
    expect($second->fresh()->outgoing_batch_id)->toBe($first->fresh()->outgoing_batch_id);

    expect(OutgoingBatchAssignmentEvent::where('event_type', OutgoingBatchAssignmentEvent::EVENT_BATCH_CREATED)->count())->toBe(1);
    expect(OutgoingBatchAssignmentEvent::where('event_type', OutgoingBatchAssignmentEvent::EVENT_BATCH_ATTACHED)->count())->toBe(1);
});

it('does not double-assign when the same confirmation is logged twice', function () {
    $region = acbRegion('WR');
    $district = acbDistrict($region, 'TAK');
    $agent = acbAgent();
    $parcel = acbParcel($region, $district);

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/agent/calls/log', ['parcel_id' => $parcel->id, 'outcome' => 'confirmed'])->assertOk();

    $batchId = $parcel->fresh()->outgoing_batch_id;
    expect($batchId)->not->toBeNull();

    $this->postJson('/api/v1/agent/calls/log', ['parcel_id' => $parcel->id, 'outcome' => 'confirmed'])
        ->assertOk()
        ->assertJsonPath('data.batching.result', 'already_batched');

    expect(OutgoingBatch::count())->toBe(1);
    expect($parcel->fresh()->outgoing_batch_id)->toBe($batchId);

    // The call itself is logged both times; the assignment only happens once.
    expect(AgentCallLog::count())->toBe(2);
    expect(OutgoingBatchAssignmentEvent::count())->toBe(1);
});

it('logs the call but skips batching when the parcel has no destination', function () {
    $agent = acbAgent();
    $parcel = acbParcel();

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/agent/calls/log', ['parcel_id' => $parcel->id, 'outcome' => 'confirmed'])
        ->assertOk()
        ->assertJsonPath('data.batching.result', 'missing_destination');

    expect($parcel->fresh()->outgoing_batch_id)->toBeNull();
    expect(OutgoingBatch::count())->toBe(0);
    expect(AgentCallLog::count())->toBe(1);
});

it('accepts the CONFIRMED_PAYMENT spelling of the outcome', function () {
    $region = acbRegion('NR');
    $district = acbDistrict($region, 'TAM');
    $parcel = acbParcel($region, $district);

    Sanctum::actingAs(acbAgent());

    $this->postJson('/api/v1/agent/calls/log', [
        'parcel_id' => $parcel->id,
        'outcome' => 'CONFIRMED_PAYMENT',
    ])->assertOk();

    expect(AgentCallLog::firstOrFail()->outcome)->toBe(AgentCallLog::OUTCOME_CONFIRMED);
    expect($parcel->fresh()->outgoing_batch_id)->not->toBeNull();
});

it('records the outcome without batching when the call is not a confirmation', function () {
    $region = acbRegion('VR');
    $district = acbDistrict($region, 'HO');
    $parcel = acbParcel($region, $district);

    Sanctum::actingAs(acbAgent());

    $this->postJson('/api/v1/agent/calls/log', [
        'parcel_id' => $parcel->id,
        'outcome' => 'rescheduled',
    ])
        ->assertOk()
        ->assertJsonPath('data.batching', null);

    expect(AgentCallLog::firstOrFail()->outcome)->toBe(AgentCallLog::OUTCOME_RESCHEDULED);
    expect($parcel->fresh()->outgoing_batch_id)->toBeNull();
    expect(OutgoingBatch::count())->toBe(0);
});

it('rejects an outcome it does not understand', function () {
    $parcel = acbParcel();

    Sanctum::actingAs(acbAgent());

    $this->postJson('/api/v1/agent/calls/log', [
        'parcel_id' => $parcel->id,
        'outcome' => 'went_fishing',
    ])->assertStatus(422);

    expect(AgentCallLog::count())->toBe(0);
});

it('refuses to log a call against another agent parcel', function () {
    $parcel = acbParcel(null, null, ['agent_id' => acbAgent()->id]);

    Sanctum::actingAs(acbAgent());

    $this->postJson('/api/v1/agent/calls/log', [
        'parcel_id' => $parcel->id,
        'outcome' => 'confirmed',
    ])->assertStatus(403);

    expect(AgentCallLog::count())->toBe(0);
});

it('records the agent on the parcel when it is claimed', function () {
    $agent = acbAgent();
    $parcel = acbParcel();

    Sanctum::actingAs($agent);

    $this->postJson('/api/v1/agent/parcels/scan-claim', [
        'tracking_code' => $parcel->tracking_code,
    ])->assertOk();

    $parcel->refresh();

    expect($parcel->agent_id)->toBe($agent->id);
    expect($parcel->claimed_at)->not->toBeNull();
    expect($parcel->status)->toBe(ItemStatus::PICKED_UP);
});
