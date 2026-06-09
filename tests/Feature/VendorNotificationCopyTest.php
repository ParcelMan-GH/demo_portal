<?php

use App\Http\Controllers\Api\V1\VendorNotificationController;
use App\Models\NotificationLog;
use App\Models\Vendor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('notification_logs');
    Schema::dropIfExists('vendors');

    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('business_name')->nullable();
        $table->string('phone')->unique();
        $table->string('email')->nullable()->unique();
        $table->boolean('is_active')->default(true);
        $table->string('fcm_token', 512)->nullable();
        $table->decimal('commission_rate_override', 8, 2)->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('notification_logs', function (Blueprint $table) {
        $table->id();
        $table->string('notifiable_type');
        $table->unsignedBigInteger('notifiable_id');
        $table->string('type');
        $table->string('channel');
        $table->string('title');
        $table->text('body');
        $table->json('data')->nullable();
        $table->string('status')->default('pending');
        $table->text('error')->nullable();
        $table->timestamp('read_at')->nullable();
        $table->timestamps();
    });
});

it('keeps vendor-facing notification source copy on parcel wording', function () {
    $combined = collect([
        app_path('Listeners/SendVendorShipmentNotification.php'),
        app_path('Listeners/SendCollectionNotifications.php'),
        app_path('Listeners/SendWalkinShipmentNotifications.php'),
        app_path('Http/Controllers/Admin/ShipmentController.php'),
    ])->map(fn (string $path) => file_get_contents($path))->implode("\n");

    expect($combined)->toContain('Parcel Picked Up')
        ->and($combined)->toContain('Your parcel has been collected by the rider.')
        ->and($combined)->toContain('Parcel Collected')
        ->and($combined)->toContain('Parcel Received')
        ->and($combined)->toContain('Parcel request rejected')
        ->and($combined)->not->toContain("title: 'Shipment Collected'")
        ->and($combined)->not->toContain("title: 'Shipment Received'")
        ->and($combined)->not->toContain("\$title = 'Shipment request rejected';")
        ->and($combined)->not->toContain('Your shipment')
        ->and($combined)->not->toContain('pick up your shipment');
});

it('hides failed vendor push logs from the default notification list and unread count', function () {
    $vendor = Vendor::query()->create([
        'name' => 'Akua Mensah',
        'business_name' => 'Akua Stores',
        'phone' => '+233240000000',
        'email' => 'akua@example.com',
        'is_active' => true,
    ]);

    NotificationLog::query()->create([
        'notifiable_type' => Vendor::class,
        'notifiable_id' => $vendor->id,
        'type' => 'shipment_status',
        'channel' => 'push',
        'title' => 'Parcel Picked Up',
        'body' => 'Your parcel has been collected by the rider.',
        'data' => ['shipment_id' => '1'],
        'status' => 'sent',
    ]);

    NotificationLog::query()->create([
        'notifiable_type' => Vendor::class,
        'notifiable_id' => $vendor->id,
        'type' => 'shipment_rejected',
        'channel' => 'push',
        'title' => 'Parcel request rejected',
        'body' => 'Your parcel request PCM-2026-00009 was rejected: Invalid details',
        'data' => ['shipment_id' => '2'],
        'status' => 'logged',
    ]);

    NotificationLog::query()->create([
        'notifiable_type' => Vendor::class,
        'notifiable_id' => $vendor->id,
        'type' => 'shipment_status',
        'channel' => 'push',
        'title' => 'Failed push row',
        'body' => 'This failed send should not appear.',
        'data' => ['shipment_id' => '3'],
        'status' => 'failed',
    ]);

    $request = Request::create('/api/v1/vendor/notifications', 'GET');
    $request->setUserResolver(fn () => $vendor);

    $payload = app(VendorNotificationController::class)->index($request)->getData(true);
    $titles = collect($payload['data']['notifications'])->pluck('title');

    expect($payload['data']['pagination']['total'])->toBe(2)
        ->and($payload['data']['unread_count'])->toBe(2)
        ->and($titles)->toContain('Parcel Picked Up')
        ->and($titles)->toContain('Parcel request rejected')
        ->and($titles)->not->toContain('Failed push row');
});
