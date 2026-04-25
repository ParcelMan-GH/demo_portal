<?php

use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Services\ActivityLogService;
use App\Services\ShipmentService;
use App\Services\StorageService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

function vendorCourierHandoffProofPhotoBuildSchema(): void
{
    foreach ([
        'delivery_run_items',
        'delivery_run_stops',
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
        $table->string('pickup_contact_name')->nullable();
        $table->string('pickup_contact_phone')->nullable();
        $table->string('pickup_town')->nullable();
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->text('sender_notes')->nullable();
        $table->timestamp('submitted_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->text('cancellation_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id');
        $table->string('description')->nullable();
        $table->unsignedInteger('quantity')->default(1);
        $table->string('delivery_method')->nullable();
        $table->string('status')->default('pending');
        $table->string('tracking_code')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_stops', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->string('status')->default('pending');
        $table->string('delivery_method')->default('direct');
        $table->string('proof_photo_path')->nullable();
        $table->unsignedInteger('proof_photo_size')->nullable();
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
        $table->unsignedBigInteger('delivery_run_stop_id');
        $table->unsignedBigInteger('shipment_item_id');
        $table->unsignedInteger('expected_quantity')->default(1);
        $table->unsignedInteger('delivered_quantity')->default(0);
        $table->string('status')->default('pending');
        $table->timestamps();
    });
}

function vendorCourierHandoffProofPhotoInvokeTransformShipment(ShipmentService $service, Shipment $shipment): array
{
    $method = new ReflectionMethod(ShipmentService::class, 'transformShipment');
    $method->setAccessible(true);

    return $method->invoke($service, $shipment, [
        'include_pickup_details' => false,
        'include_legacy_delivery_aliases' => false,
    ]);
}

beforeEach(function () {
    vendorCourierHandoffProofPhotoBuildSchema();
});

test('vendor shipment payload includes handoff proof photo for shipment and item', function () {
    $shipment = Shipment::create([
        'vendor_id' => 1,
        'shipment_number' => 'PCM-2026-01015',
        'status' => ShipmentStatus::HANDED_TO_COURIER->value,
        'source' => ShipmentSource::VENDOR_APP->value,
        'destination_mode' => ShipmentDestinationMode::SINGLE->value,
        'pickup_contact_name' => 'Tony Mensa',
        'pickup_contact_phone' => '+233542796510',
        'pickup_town' => 'Madina',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Laptop',
        'quantity' => 2,
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
        'status' => ItemStatus::HANDED_TO_COURIER->value,
        'tracking_code' => 'TRKXITYJK1R',
    ]);

    $stop = DeliveryRunStop::create([
        'status' => DeliveryRunStop::STATUS_HANDED_OFF,
        'delivery_method' => DeliveryRunStop::METHOD_BUS_HANDOFF,
        'proof_photo_path' => 'delivery/handoff-proof.jpg',
        'handoff_courier_name' => 'Accra station',
        'handoff_courier_phone' => '+233201112223',
        'handoff_vehicle_number' => 'GR-1234-26',
        'bus_station_name' => 'Accra station',
        'handoff_at' => now(),
    ]);

    DeliveryRunItem::create([
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => $item->id,
        'status' => DeliveryRunItem::STATUS_HANDED_OFF,
    ]);

    $shipment->setRelation('items', new Collection([
        tap($item, fn (ShipmentItem $record) => $record->setRelation('images', new Collection())),
    ]));
    $shipment->setRelation('charges', new Collection());
    $shipment->setRelation('invoice', null);
    $shipment->setRelation('invoices', new Collection());
    $shipment->setRelation('pickupAssignment', null);

    $activityLogService = Mockery::mock(ActivityLogService::class);
    $activityLogService->shouldIgnoreMissing();

    $storageService = Mockery::mock(StorageService::class);
    $storageService
        ->shouldReceive('getUrl')
        ->twice()
        ->with('delivery/handoff-proof.jpg')
        ->andReturn('https://example.test/delivery/handoff-proof.jpg');

    $service = new ShipmentService($activityLogService, $storageService);

    $payload = vendorCourierHandoffProofPhotoInvokeTransformShipment($service, $shipment);

    expect($payload['courier_handoff'])->not->toBeNull()
        ->and($payload['courier_handoff']['proof_photo_url'])->toBe('https://example.test/delivery/handoff-proof.jpg')
        ->and($payload['items'])->toHaveCount(1)
        ->and($payload['items'][0]['handoff'])->not->toBeNull()
        ->and($payload['items'][0]['handoff']['proof_photo_url'])->toBe('https://example.test/delivery/handoff-proof.jpg');
});

test('vendor item handoff remains visible after the package is delivered', function () {
    $shipment = Shipment::create([
        'vendor_id' => 1,
        'shipment_number' => 'PCM-2026-01016',
        'status' => ShipmentStatus::DELIVERED->value,
        'source' => ShipmentSource::VENDOR_APP->value,
        'destination_mode' => ShipmentDestinationMode::SINGLE->value,
        'pickup_contact_name' => 'Tony Mensa',
        'pickup_contact_phone' => '+233542796510',
        'pickup_town' => 'Madina',
    ]);

    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'description' => 'Laptop',
        'quantity' => 1,
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF,
        'status' => ItemStatus::DELIVERED->value,
        'tracking_code' => 'TRKDELIVERED1',
    ]);

    $stop = DeliveryRunStop::create([
        'status' => DeliveryRunStop::STATUS_DELIVERED,
        'delivery_method' => DeliveryRunStop::METHOD_BUS_HANDOFF,
        'proof_photo_path' => 'delivery/handoff-proof-delivered.jpg',
        'handoff_courier_name' => 'Kaneshie station',
        'handoff_courier_phone' => '+233201112224',
        'handoff_vehicle_number' => 'GR-5555-26',
        'bus_station_name' => 'Kaneshie station',
        'handoff_at' => now(),
    ]);

    DeliveryRunItem::create([
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => $item->id,
        'status' => DeliveryRunItem::STATUS_HANDED_OFF,
    ]);

    $shipment->setRelation('items', new Collection([
        tap($item, fn (ShipmentItem $record) => $record->setRelation('images', new Collection())),
    ]));
    $shipment->setRelation('charges', new Collection());
    $shipment->setRelation('invoice', null);
    $shipment->setRelation('invoices', new Collection());
    $shipment->setRelation('pickupAssignment', null);

    $activityLogService = Mockery::mock(ActivityLogService::class);
    $activityLogService->shouldIgnoreMissing();

    $storageService = Mockery::mock(StorageService::class);
    $storageService
        ->shouldReceive('getUrl')
        ->twice()
        ->with('delivery/handoff-proof-delivered.jpg')
        ->andReturn('https://example.test/delivery/handoff-proof-delivered.jpg');

    $service = new ShipmentService($activityLogService, $storageService);

    $payload = vendorCourierHandoffProofPhotoInvokeTransformShipment($service, $shipment);

    expect($payload['courier_handoff'])->not->toBeNull()
        ->and($payload['courier_handoff']['proof_photo_url'])->toBe('https://example.test/delivery/handoff-proof-delivered.jpg')
        ->and($payload['items'])->toHaveCount(1)
        ->and($payload['items'][0]['handoff'])->not->toBeNull()
        ->and($payload['items'][0]['handoff']['bus_station'])->toBe('Kaneshie station')
        ->and($payload['items'][0]['handoff']['proof_photo_url'])->toBe('https://example.test/delivery/handoff-proof-delivered.jpg');
});
