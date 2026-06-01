<?php

use App\Http\Controllers\Admin\AdminContactQueueController;
use App\Models\PackageContactTask;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SmsService;
use App\Services\Warehouse\PackageContactService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

function buildAdminContactQueueAssignmentSchema(): void
{
    foreach ([
        'package_contact_tasks',
        'bus_handoff_confirmations',
        'delivery_run_items',
        'delivery_run_stops',
        'delivery_runs',
        'drivers',
        'shipment_collections',
        'shipment_items',
        'shipments',
        'warehouses',
        'users',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique()->nullable();
        $table->string('password')->nullable();
        $table->boolean('is_active')->default(true);
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->timestamps();
    });

    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->string('shipment_number')->unique();
        $table->string('destination_mode')->default('single');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipment_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id');
        $table->string('tracking_code')->nullable();
        $table->string('description')->nullable();
        $table->unsignedInteger('quantity')->default(1);
        $table->string('status')->default('pending');
        $table->timestamps();
    });

    Schema::create('shipment_collections', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->string('status')->nullable();
        $table->timestamp('ready_at')->nullable();
        $table->timestamp('collected_at')->nullable();
        $table->timestamps();
    });

    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('phone')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_runs', function (Blueprint $table) {
        $table->id();
        $table->string('run_number')->nullable();
        $table->unsignedBigInteger('assigned_driver_id')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_stops', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->string('status')->nullable();
        $table->string('delivery_method')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->unsignedBigInteger('confirmed_by_admin_id')->nullable();
        $table->string('bus_station_name')->nullable();
        $table->timestamps();
    });

    Schema::create('delivery_run_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_id')->nullable();
        $table->unsignedBigInteger('delivery_run_stop_id')->nullable();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->string('status')->nullable();
        $table->timestamp('delivered_at')->nullable();
        $table->timestamps();
    });

    Schema::create('bus_handoff_confirmations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('delivery_run_item_id')->nullable();
        $table->string('status')->nullable();
        $table->string('source')->nullable();
        $table->string('target_type')->nullable();
        $table->string('target_name')->nullable();
        $table->string('target_phone')->nullable();
        $table->timestamp('confirmed_at')->nullable();
        $table->unsignedBigInteger('confirmed_by_driver_id')->nullable();
        $table->unsignedBigInteger('confirmed_by_admin_id')->nullable();
        $table->timestamp('public_confirmed_at')->nullable();
        $table->timestamps();
    });

    Schema::create('package_contact_tasks', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_item_id')->nullable();
        $table->unsignedBigInteger('shipment_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->unsignedBigInteger('assigned_to_user_id')->nullable();
        $table->timestamp('assigned_at')->nullable();
        $table->string('status')->default('pending');
        $table->string('recipient_name')->nullable();
        $table->string('recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->string('outcome')->nullable();
        $table->timestamp('callback_at')->nullable();
        $table->text('notes')->nullable();
        $table->unsignedInteger('attempts_count')->default(0);
        $table->timestamp('resolved_at')->nullable();
        $table->unsignedBigInteger('resolved_by_user_id')->nullable();
        $table->string('confirmation_code')->nullable();
        $table->timestamp('confirmation_code_sent_at')->nullable();
        $table->timestamp('confirmation_code_expires_at')->nullable();
        $table->timestamp('confirmation_code_verified_at')->nullable();
        $table->unsignedInteger('confirmation_attempts')->default(0);
        $table->timestamps();
    });
}

function makeAdminContactQueueController(): AdminContactQueueController
{
    $contactService = new PackageContactService(
        Mockery::mock(SmsService::class)->shouldIgnoreMissing()
    );

    return new class($contactService) extends AdminContactQueueController {
        protected function authorizePermission(string $permission): void
        {
            // Permission enforcement is covered elsewhere; this test focuses
            // on payload shape and assignment behavior.
        }
    };
}

beforeEach(function () {
    buildAdminContactQueueAssignmentSchema();
});

test('admin contact queue data includes assignee and display aliases used by the page', function () {
    $warehouse = Warehouse::create(['name' => 'Accra Main Office']);
    $worker = User::create(['name' => 'Akosua Agent']);
    $shipment = Shipment::create(['shipment_number' => 'PCM-2026-03001']);
    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'tracking_code' => 'TRKCONTACT001',
        'description' => 'Laptop',
    ]);

    PackageContactTask::create([
        'shipment_item_id' => $item->id,
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'assigned_to_user_id' => $worker->id,
        'assigned_at' => now(),
        'status' => PackageContactTask::STATUS_ASSIGNED,
        'recipient_name' => 'Majid',
        'recipient_phone' => '+233542796510',
        'delivery_town' => 'Madina',
        'attempts_count' => 2,
    ]);

    $response = makeAdminContactQueueController()->data(Request::create('/admin/contacts/data', 'GET'));
    $payload = $response->getData(true);

    expect($payload['data'])->toHaveCount(1)
        ->and($payload['data'][0]['tracking_number'])->toBe('TRKCONTACT001')
        ->and($payload['data'][0]['recipient_town'])->toBe('Madina')
        ->and($payload['data'][0]['assigned_to_name'])->toBe('Akosua Agent')
        ->and($payload['data'][0]['assigned_to_id'])->toBe($worker->id)
        ->and($payload['data'][0]['warehouse_name'])->toBe('Accra Main Office');
});

test('admin contact queue assign accepts worker_id and returns updated task payload', function () {
    $warehouse = Warehouse::create(['name' => 'Accra Main Office']);
    $worker = User::create(['name' => 'Kojo Worker']);
    $shipment = Shipment::create(['shipment_number' => 'PCM-2026-03002']);
    $item = ShipmentItem::create([
        'shipment_id' => $shipment->id,
        'tracking_code' => 'TRKCONTACT002',
        'description' => 'Phone',
    ]);

    $task = PackageContactTask::create([
        'shipment_item_id' => $item->id,
        'shipment_id' => $shipment->id,
        'warehouse_id' => $warehouse->id,
        'status' => PackageContactTask::STATUS_PENDING,
        'recipient_name' => 'John',
        'recipient_phone' => '+233205531644',
        'delivery_town' => 'Adenta',
    ]);

    $request = Request::create("/admin/contacts/{$task->id}/assign", 'POST', [
        'worker_id' => $worker->id,
    ]);

    $response = makeAdminContactQueueController()->assign($request, $task);
    $payload = $response->getData(true);

    $task->refresh();

    expect($payload['success'])->toBeTrue()
        ->and($payload['task']['assigned_to_name'])->toBe('Kojo Worker')
        ->and($payload['task']['assigned_to_id'])->toBe($worker->id)
        ->and($payload['task']['status'])->toBe(PackageContactTask::STATUS_ASSIGNED)
        ->and($task->assigned_to_user_id)->toBe($worker->id)
        ->and($task->status)->toBe(PackageContactTask::STATUS_ASSIGNED)
        ->and($task->assigned_at)->not->toBeNull();
});
