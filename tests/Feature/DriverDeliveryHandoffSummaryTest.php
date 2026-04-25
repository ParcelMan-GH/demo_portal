<?php

use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Services\DriverDeliveryService;
use App\Services\StorageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

function driverDeliveryHandoffSummaryBuildSchema(): void
{
    foreach ([
        'shipment_charges',
        'delivery_run_items',
        'delivery_run_stops',
        'delivery_runs',
        'shipment_items',
        'shipments',
        'platform_settings',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('platform_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->string('description')->nullable();
        $table->boolean('is_encrypted')->default(false);
        $table->timestamps();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('vendor_id')->nullable();
        $table->string('shipment_number')->unique();
        $table->string('status')->default('draft');
        $table->string('source')->default('vendor_app');
        $table->string('fulfillment_type')->nullable();
        $table->string('destination_mode')->default('single');
        $table->string('delivery_preference')->nullable();
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id');
        $table->string('description')->nullable();
        $table->unsignedInteger('quantity')->default(1);
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->string('delivery_method')->nullable();
        $table->string('status')->default('pending');
        $table->string('tracking_code')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_runs', function (Blueprint $table) {
        $table->id();
        $table->string('run_number')->nullable();
        $table->unsignedBigInteger('sort_batch_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->unsignedBigInteger('assigned_driver_id')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('assigned_at')->nullable();
        $table->timestamp('dispatched_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_stops', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->string('recipient_name')->nullable();
        $table->string('recipient_phone')->nullable();
        $table->unsignedBigInteger('region_id')->nullable();
        $table->unsignedBigInteger('district_id')->nullable();
        $table->string('town')->nullable();
        $table->decimal('latitude', 10, 8)->nullable();
        $table->decimal('longitude', 11, 8)->nullable();
        $table->string('gh_post_address')->nullable();
        $table->string('landmark')->nullable();
        $table->string('status')->default('pending');
        $table->unsignedInteger('total_packages')->default(1);
        $table->timestamp('verification_code_expires_at')->nullable();
        $table->timestamp('verification_code_sent_at')->nullable();
        $table->unsignedInteger('verification_attempts')->default(0);
        $table->unsignedInteger('max_attempts')->default(3);
        $table->boolean('verification_skipped')->default(false);
        $table->string('verification_skip_reason')->nullable();
        $table->timestamp('verification_skipped_at')->nullable();
        $table->timestamp('arrived_at')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->decimal('delivery_latitude', 10, 8)->nullable();
        $table->decimal('delivery_longitude', 11, 8)->nullable();
        $table->string('proof_photo_path')->nullable();
        $table->unsignedInteger('proof_photo_size')->nullable();
        $table->string('failure_reason')->nullable();
        $table->text('failure_notes')->nullable();
        $table->text('delivery_notes')->nullable();
        $table->string('delivery_method')->default('direct');
        $table->string('handoff_courier_name')->nullable();
        $table->string('handoff_courier_phone')->nullable();
        $table->string('handoff_vehicle_number')->nullable();
        $table->string('bus_station_name')->nullable();
        $table->timestamp('handoff_at')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->unsignedBigInteger('shipment_item_id');
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('delivered_quantity')->default(0);
        $table->string('status')->default('pending');
        $table->text('notes')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
    });

    Schema::create('shipment_charges', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id')->nullable();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->string('charge_type');
        $table->string('payer_type');
        $table->string('direction');
        $table->string('due_stage');
        $table->decimal('amount', 12, 2)->default(0);
        $table->string('currency')->default('GHS');
        $table->string('status')->default('draft');
        $table->timestamp('paid_at')->nullable();
        $table->string('payment_method')->nullable();
        $table->string('payment_reference')->nullable();
        $table->unsignedBigInteger('recorded_by_admin_id')->nullable();
        $table->unsignedBigInteger('recorded_by_driver_id')->nullable();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->unsignedBigInteger('pickup_assignment_id')->nullable();
        $table->text('notes')->nullable();
        $table->text('waive_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
}

function driverDeliveryHandoffSummaryInvokeTransformRun(DriverDeliveryService $service, DeliveryRun $run): array
{
    $method = new ReflectionMethod(DriverDeliveryService::class, 'transformRun');
    $method->setAccessible(true);

    return $method->invoke($service, $run);
}

beforeEach(function () {
    driverDeliveryHandoffSummaryBuildSchema();
});

test('driver delivery stop handoff summary exposes station fee and proof photo', function () {
    $shipment = Shipment::create([
        'vendor_id' => 1,
        'shipment_number' => 'PCM-2026-02015',
        'status' => ShipmentStatus::HANDED_TO_COURIER->value,
        'source' => ShipmentSource::VENDOR_APP->value,
        'destination_mode' => ShipmentDestinationMode::SINGLE->value,
        'delivery_town' => 'Madina',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Laptop',
        'quantity' => 2,
        'delivery_town' => 'Madina',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
        'status' => ItemStatus::HANDED_TO_COURIER->value,
        'tracking_code' => 'TRKDRIVERSTOP1',
    ]);

    $run = DeliveryRun::create([
        'run_number' => 'DRN-2026-00051',
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
    ]);

    $stop = DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Bus Station Handoff',
        'status' => DeliveryRunStop::STATUS_HANDED_OFF,
        'delivery_method' => DeliveryRunStop::METHOD_BUS_HANDOFF,
        'total_packages' => 2,
        'town' => 'Madina',
        'proof_photo_path' => 'deliveries/runs/51/stops/7/handoff-proof.jpg',
        'handoff_courier_phone' => '+233201112223',
        'handoff_vehicle_number' => 'GR-1234-26',
        'bus_station_name' => 'Accra station',
        'handoff_at' => now(),
    ]);

    $runItem = DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 2,
        'delivered_quantity' => 2,
        'status' => DeliveryRunItem::STATUS_HANDED_OFF,
    ]);

    ShipmentCharge::create([
        'shipment_id' => $shipment->id,
        'shipment_item_id' => $item->id,
        'charge_type' => ShipmentCharge::TYPE_STATION_FEE,
        'payer_type' => ShipmentCharge::PAYER_PARCELMAN,
        'direction' => ShipmentCharge::DIRECTION_EXPENSE,
        'due_stage' => ShipmentCharge::STAGE_AT_HANDOFF,
        'amount' => 18.50,
        'currency' => 'GHS',
        'status' => ShipmentCharge::STATUS_PAID,
        'delivery_run_stop_id' => $stop->id,
        'payment_method' => 'cash',
        'notes' => 'Driver paid bus courier at handoff',
    ]);

    $shipment->setRelation('vendor', null);
    $item->setRelation('shipment', $shipment);
    $runItem->setRelation('shipmentItem', $item);
    $stop->setRelation('region', null);
    $stop->setRelation('district', null);
    $run->setRelation('warehouse', null);
    $run->setRelation('stops', new Collection([$stop]));
    $run->setRelation('items', new Collection([$runItem]));

    $storageService = Mockery::mock(StorageService::class);
    $storageService
        ->shouldReceive('getUrl')
        ->once()
        ->with('deliveries/runs/51/stops/7/handoff-proof.jpg')
        ->andReturn('https://example.test/deliveries/runs/51/stops/7/handoff-proof.jpg');

    $service = new DriverDeliveryService($storageService);

    $payload = driverDeliveryHandoffSummaryInvokeTransformRun($service, $run);

    expect($payload['stops'])->toHaveCount(1)
        ->and($payload['stops'][0]['handoff'])->not->toBeNull()
        ->and($payload['stops'][0]['handoff']['bus_station'])->toBe('Accra station')
        ->and($payload['stops'][0]['handoff']['proof_photo_url'])->toBe('https://example.test/deliveries/runs/51/stops/7/handoff-proof.jpg')
        ->and($payload['stops'][0]['handoff']['amount_paid'])->toBe(18.5)
        ->and($payload['stops'][0]['handoff']['currency'])->toBe('GHS');
});

test('driver delivery stop exposes outstanding delivery fee summary for direct delivery', function () {
    $shipment = Shipment::create([
        'vendor_id' => 1,
        'shipment_number' => 'PCM-2026-02016',
        'status' => ShipmentStatus::OUT_FOR_DELIVERY->value,
        'source' => ShipmentSource::VENDOR_APP->value,
        'destination_mode' => ShipmentDestinationMode::SINGLE->value,
        'delivery_recipient_name' => 'Ama Mensah',
        'delivery_recipient_phone' => '+233241234567',
        'delivery_town' => 'Osu',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Shoes',
        'quantity' => 1,
        'delivery_recipient_name' => 'Ama Mensah',
        'delivery_recipient_phone' => '+233241234567',
        'delivery_town' => 'Osu',
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => ItemStatus::OUT_FOR_DELIVERY->value,
        'tracking_code' => 'TRKDRIVERSTOP2',
    ]);

    $run = DeliveryRun::create([
        'run_number' => 'DRN-2026-00052',
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
    ]);

    $stop = DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Ama Mensah',
        'recipient_phone' => '+233241234567',
        'status' => DeliveryRunStop::STATUS_ARRIVED,
        'delivery_method' => DeliveryRunStop::METHOD_DIRECT,
        'total_packages' => 1,
        'town' => 'Osu',
    ]);

    $runItem = DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 1,
        'delivered_quantity' => 0,
        'status' => DeliveryRunItem::STATUS_PENDING,
    ]);

    ShipmentCharge::create([
        'shipment_id' => $shipment->id,
        'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
        'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
        'direction' => ShipmentCharge::DIRECTION_REVENUE,
        'due_stage' => ShipmentCharge::STAGE_AT_DELIVERY,
        'amount' => 15.00,
        'currency' => 'GHS',
        'status' => ShipmentCharge::STATUS_PENDING,
        'notes' => 'Recipient pays on delivery',
    ]);

    $shipment->setRelation('vendor', null);
    $item->setRelation('shipment', $shipment);
    $runItem->setRelation('shipmentItem', $item);
    $stop->setRelation('region', null);
    $stop->setRelation('district', null);
    $run->setRelation('warehouse', null);
    $run->setRelation('stops', new Collection([$stop]));
    $run->setRelation('items', new Collection([$runItem]));

    $storageService = Mockery::mock(StorageService::class);
    $service = new DriverDeliveryService($storageService);

    $payload = driverDeliveryHandoffSummaryInvokeTransformRun($service, $run);

    expect($payload['stops'])->toHaveCount(1)
        ->and($payload['stops'][0]['handoff'])->toBeNull()
        ->and($payload['stops'][0]['delivery_fee'])->not->toBeNull()
        ->and($payload['stops'][0]['delivery_fee']['status'])->toBe('collect')
        ->and($payload['stops'][0]['delivery_fee']['total_amount'])->toBe(15.0)
        ->and($payload['stops'][0]['delivery_fee']['outstanding_amount'])->toBe(15.0)
        ->and($payload['stops'][0]['delivery_fee']['paid_amount'])->toBe(0.0)
        ->and($payload['stops'][0]['delivery_fee']['can_capture_amount'])->toBeTrue()
        ->and($payload['stops'][0]['delivery_fee']['notes'])->toBe('Recipient pays on delivery');
});
