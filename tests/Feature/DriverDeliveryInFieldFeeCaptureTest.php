<?php

use App\Enums\ItemStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Services\ChargesService;
use App\Services\SmsService;
use App\Services\StorageService;
use App\Services\VendorCommissionService;
use App\Services\Warehouse\DeliveryVerificationService;
use App\Services\Warehouse\WarehouseDeliveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;

function buildDriverDeliveryInFieldFeeCaptureSchema(): void
{
    foreach ([
        'users',
        'shipment_charges',
        'shipment_item_tracking',
        'delivery_run_items',
        'delivery_run_stops',
        'delivery_runs',
        'shipment_items',
        'shipments',
        'drivers',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('fcm_token')->nullable();
        $table->timestamps();
    });

    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->unique();
        $table->string('password');
        $table->string('status')->default('busy');
        $table->boolean('is_active')->default(true);
        $table->json('task_capabilities')->nullable();
        $table->string('remember_token')->nullable();
        $table->timestamps();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('vendor_id')->nullable();
        $table->string('shipment_number')->unique();
        $table->string('status')->default('submitted');
        $table->string('source')->default('vendor_app');
        $table->string('destination_mode')->default('single');
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
        $table->string('delivery_method')->nullable();
        $table->string('status')->default('pending');
        $table->string('tracking_code')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_runs', function (Blueprint $table) {
        $table->id();
        $table->string('run_number')->nullable();
        $table->unsignedBigInteger('assigned_driver_id')->nullable();
        $table->string('status')->default('draft');
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_stops', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->string('recipient_name')->nullable();
        $table->string('recipient_phone')->nullable();
        $table->string('town')->nullable();
        $table->string('gh_post_address')->nullable();
        $table->string('landmark')->nullable();
        $table->string('status')->default('pending');
        $table->unsignedInteger('total_packages')->default(1);
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

    Schema::create('shipment_item_tracking', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_item_id');
        $table->string('status');
        $table->string('location')->nullable();
        $table->text('notes')->nullable();
        $table->json('meta')->nullable();
        $table->string('created_by')->nullable();
        $table->timestamp('created_at')->nullable();
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

beforeEach(function () {
    buildDriverDeliveryInFieldFeeCaptureSchema();
});

test('driver direct delivery settles existing pending delivery fee instead of creating a duplicate charge', function () {
    $driver = Driver::create([
        'name' => 'Field Driver',
        'email' => 'driver@example.test',
        'phone' => '+233201234567',
        'password' => bcrypt('secret'),
        'status' => 'busy',
    ]);

    $shipment = Shipment::create([
        'shipment_number' => 'PCM-2026-02017',
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
        'delivery_method' => ShipmentItem::DELIVERY_METHOD_DIRECT,
        'status' => ItemStatus::OUT_FOR_DELIVERY->value,
        'tracking_code' => 'TRKFIELDDELIVERY1',
    ]);

    $run = DeliveryRun::create([
        'run_number' => 'DRN-2026-00053',
        'assigned_driver_id' => $driver->id,
        'status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY,
    ]);

    $stop = DeliveryRunStop::create([
        'delivery_run_id' => $run->id,
        'recipient_name' => 'Ama Mensah',
        'recipient_phone' => '+233241234567',
        'town' => 'Osu',
        'status' => DeliveryRunStop::STATUS_ARRIVED,
        'delivery_method' => DeliveryRunStop::METHOD_DIRECT,
        'total_packages' => 1,
    ]);

    DeliveryRunItem::create([
        'delivery_run_id' => $run->id,
        'delivery_run_stop_id' => $stop->id,
        'shipment_item_id' => $item->id,
        'expected_quantity' => 1,
        'delivered_quantity' => 0,
        'status' => DeliveryRunItem::STATUS_PENDING,
    ]);

    $existingCharge = ShipmentCharge::create([
        'shipment_id' => $shipment->id,
        'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
        'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
        'direction' => ShipmentCharge::DIRECTION_REVENUE,
        'due_stage' => ShipmentCharge::STAGE_AT_DELIVERY,
        'amount' => 20.00,
        'currency' => 'GHS',
        'status' => ShipmentCharge::STATUS_PENDING,
        'notes' => 'Recipient pays on delivery',
    ]);

    $verificationService = Mockery::mock(DeliveryVerificationService::class);
    $storageService = Mockery::mock(StorageService::class);
    $storageService
        ->shouldReceive('upload')
        ->once()
        ->andReturn(['path' => 'deliveries/runs/53/stops/1/proof.jpg', 'size' => 1024]);

    $smsService = Mockery::mock(SmsService::class);
    $commissionService = Mockery::mock(VendorCommissionService::class);
    $commissionService
        ->shouldReceive('createEarningsForStop')
        ->once();

    $service = new WarehouseDeliveryService(
        $verificationService,
        $storageService,
        $smsService,
        $commissionService,
        new ChargesService(),
    );

    $result = $service->driverConfirmStopByPackage(
        run: $run,
        stop: $stop,
        driver: $driver,
        verificationCode: null,
        packagesDelivered: 1,
        latitude: 5.6037,
        longitude: -0.1870,
        proofPhoto: UploadedFile::fake()->image('proof.jpg'),
        ipAddress: '127.0.0.1',
        skipVerification: true,
        skipReason: 'Recipient requested direct handover',
        deliveryNotes: 'Collected fee and completed delivery',
        inFieldDeliveryFee: 20.00,
    );

    expect($result['success'])->toBeTrue()
        ->and(ShipmentCharge::count())->toBe(1);

    $existingCharge->refresh();

    expect($existingCharge->status)->toBe(ShipmentCharge::STATUS_PAID)
        ->and($existingCharge->payment_method)->toBe('cash')
        ->and((int) $existingCharge->delivery_run_stop_id)->toBe($stop->id)
        ->and((int) $existingCharge->recorded_by_driver_id)->toBe($driver->id);
});
