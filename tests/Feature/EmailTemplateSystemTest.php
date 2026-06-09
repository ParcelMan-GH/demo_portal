<?php

use App\Enums\FulfillmentType;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Events\DriverAssignedToPickup;
use App\Events\ShipmentStatusChanged;
use App\Events\VendorRegistered;
use App\Listeners\SendCustomerEmailTemplateNotification;
use App\Models\Driver;
use App\Models\EmailTemplate;
use App\Models\NotificationLog;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Services\EmailTemplateService;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    emailTemplateBuildSchema();
    $this->seed(EmailTemplateSeeder::class);
});

it('seeds system templates with expected default enabled states', function () {
    expect(EmailTemplate::query()->count())->toBe(10)
        ->and((bool) EmailTemplate::query()->where('key', EmailTemplate::VENDOR_WELCOME)->value('is_enabled'))->toBeTrue()
        ->and((bool) EmailTemplate::query()->where('key', EmailTemplate::SHIPMENT_SUBMITTED)->value('is_enabled'))->toBeFalse()
        ->and(EmailTemplate::query()->where('key', 'shipment_received')->exists())->toBeFalse();
});

it('sends and logs an enabled template', function () {
    Mail::shouldReceive('html')->once()->andReturn(null);
    $vendor = emailTemplateVendor();

    $sent = app(EmailTemplateService::class)->send(EmailTemplate::VENDOR_WELCOME, $vendor, [
        'vendor_name' => 'Akua',
        'login_url' => 'https://parcelman.test/vendor/login',
    ]);

    expect($sent)->toBeTrue()
        ->and(NotificationLog::query()->where('type', EmailTemplate::VENDOR_WELCOME)->where('status', 'sent')->count())->toBe(1);
});

it('skips disabled and missing templates without sending or logging', function () {
    Mail::shouldReceive('html')->never();
    $vendor = emailTemplateVendor();

    $disabled = app(EmailTemplateService::class)->send(EmailTemplate::SHIPMENT_SUBMITTED, $vendor, [
        'vendor_name' => 'Akua',
        'shipment_number' => 'PCM-2026-00001',
    ]);
    $missing = app(EmailTemplateService::class)->send('missing_template', $vendor);

    expect($disabled)->toBeFalse()
        ->and($missing)->toBeFalse()
        ->and(NotificationLog::query()->count())->toBe(0);
});

it('renders variables in subjects and bodies', function () {
    $template = EmailTemplate::query()->where('key', EmailTemplate::SHIPMENT_SUBMITTED)->firstOrFail();
    $preview = app(EmailTemplateService::class)->preview($template, [
        'vendor_name' => 'Akua',
        'shipment_number' => 'PCM-2026-00999',
    ]);

    expect($preview['subject'])->toContain('PCM-2026-00999')
        ->and($preview['body_text'])->toContain('Akua')
        ->and($preview['body_html'])->toContain('PCM-2026-00999');
});

it('vendor registration sends welcome only when enabled', function () {
    Mail::shouldReceive('html')->once()->andReturn(null);
    $vendor = emailTemplateVendor();

    app(SendCustomerEmailTemplateNotification::class)->handle(new VendorRegistered($vendor));

    expect(NotificationLog::query()->where('type', EmailTemplate::VENDOR_WELCOME)->count())->toBe(1);
});

it('shipment submitted sends vendor email and not an admin email', function () {
    Mail::shouldReceive('html')->once()->andReturn(null);
    EmailTemplate::query()->where('key', EmailTemplate::SHIPMENT_SUBMITTED)->update(['is_enabled' => true]);
    $shipment = emailTemplateShipment();

    app(SendCustomerEmailTemplateNotification::class)->handle(new ShipmentStatusChanged(
        $shipment,
        ShipmentStatus::DRAFT->value,
        ShipmentStatus::SUBMITTED->value
    ));

    expect(NotificationLog::query()->where('type', EmailTemplate::SHIPMENT_SUBMITTED)->where('notifiable_type', Vendor::class)->count())->toBe(1)
        ->and(NotificationLog::query()->where('notifiable_type', User::class)->count())->toBe(0);
});

it('pickup assigned sends to the vendor and not an admin', function () {
    Mail::shouldReceive('html')->once()->andReturn(null);
    EmailTemplate::query()->where('key', EmailTemplate::PICKUP_ASSIGNED)->update(['is_enabled' => true]);
    $shipment = emailTemplateShipment();
    $driver = Driver::query()->create([
        'name' => 'John Rider',
        'phone' => '+233244111111',
        'email' => 'rider@example.com',
        'password' => bcrypt('password'),
        'license_number' => 'DRV-001',
        'is_active' => true,
    ]);
    $warehouse = Warehouse::query()->create(['name' => 'Accra Main Hub', 'code' => 'WH001', 'is_active' => true]);
    $assignment = PickupAssignment::query()->create([
        'shipment_id' => $shipment->id,
        'driver_id' => $driver->id,
        'target_warehouse_id' => $warehouse->id,
        'status' => 'assigned',
        'assigned_at' => now(),
    ]);

    app(SendCustomerEmailTemplateNotification::class)->handle(new DriverAssignedToPickup($assignment, $driver));

    expect(NotificationLog::query()->where('type', EmailTemplate::PICKUP_ASSIGNED)->where('notifiable_type', PickupAssignment::class)->count())->toBe(1)
        ->and(NotificationLog::query()->where('notifiable_type', User::class)->count())->toBe(0);
});

it('settings email template tab loads and preview endpoint renders sample content', function () {
    $this->withoutMiddleware(\App\Http\Middleware\LogAdminAuditActivity::class);
    $this->withoutMiddleware(\App\Http\Middleware\EnsureBackOfficeUser::class);
    $admin = User::factory()->create();
    $template = EmailTemplate::query()->where('key', EmailTemplate::VENDOR_WELCOME)->firstOrFail();

    $templates = EmailTemplate::query()->orderBy('category')->orderBy('name')->get()->map(fn (EmailTemplate $template) => [
        'id' => $template->id,
        'key' => $template->key,
        'name' => $template->name,
        'category' => $template->category,
        'recipient_type' => $template->recipient_type,
        'recipient_label' => str($template->recipient_type)->replace('_', ' / ')->title()->toString(),
        'subject' => $template->subject,
        'body_html' => $template->body_html,
        'body_text' => $template->body_text,
        'variables' => $template->variables ?: [],
        'is_enabled' => $template->is_enabled,
        'is_system' => $template->is_system,
        'updated_at' => $template->updated_at?->format('d M Y, h:i A'),
    ])->values();

    $this->view('admin.settings.tabs.email-templates', [
        'tabData' => [
            'templates' => $templates,
            'categories' => $templates->pluck('category')->unique()->values(),
            'recipientTypes' => $templates->pluck('recipient_type')->unique()->values(),
        ],
    ])
        ->assertSee('Customer Email Templates')
        ->assertSee('Vendor Welcome');

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.email-templates.preview', $template))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['preview' => ['subject', 'body_html', 'body_text']]);
});

it('settings toggle and edit endpoints persist template changes', function () {
    $this->withoutMiddleware(\App\Http\Middleware\LogAdminAuditActivity::class);
    $this->withoutMiddleware(\App\Http\Middleware\EnsureBackOfficeUser::class);
    $admin = User::factory()->create();
    $template = EmailTemplate::query()->where('key', EmailTemplate::SHIPMENT_SUBMITTED)->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->patch(route('admin.settings.email-templates.toggle', $template))
        ->assertOk()
        ->assertJsonPath('template.is_enabled', true);

    $this->actingAs($admin, 'admin')
        ->put(route('admin.settings.email-templates.update', $template), [
            'subject' => 'Updated {{ shipment_number }}',
            'body_html' => '<p>Updated {{ shipment_number }}</p>',
            'body_text' => 'Updated {{ shipment_number }}',
            'is_enabled' => true,
        ])
        ->assertOk()
        ->assertJsonPath('template.subject', 'Updated {{ shipment_number }}');
});

function emailTemplateVendor(array $attributes = []): Vendor
{
    return Vendor::query()->create(array_merge([
        'name' => 'Akua Mensah',
        'business_name' => 'Akua Stores',
        'phone' => '+233240000000',
        'email' => 'akua@example.com',
        'is_active' => true,
    ], $attributes));
}

function emailTemplateBuildSchema(): void
{
    foreach ([
        'pickup_assignments',
        'shipments',
        'warehouses',
        'drivers',
        'vendors',
        'users',
        'user_roles',
        'roles',
        'permissions',
        'notification_logs',
        'email_templates',
        'platform_settings',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('email_templates', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->string('name');
        $table->string('category');
        $table->string('recipient_type');
        $table->string('subject');
        $table->longText('body_html')->nullable();
        $table->longText('body_text')->nullable();
        $table->json('variables')->nullable();
        $table->boolean('is_enabled')->default(false);
        $table->boolean('is_system')->default(true);
        $table->timestamps();
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

    Schema::create('platform_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->boolean('is_encrypted')->default(false);
        $table->timestamps();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('phone')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->timestamp('last_login_at')->nullable();
        $table->timestamp('last_permission_cache_at')->nullable();
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->unsignedBigInteger('warehouse_id')->nullable();
        $table->string('fcm_token', 512)->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_warehouse_role')->default(false);
        $table->timestamps();
    });

    Schema::create('user_roles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('role_id');
        $table->timestamp('assigned_at')->nullable();
        $table->unsignedBigInteger('assigned_by')->nullable();
    });

    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('module')->nullable();
        $table->string('action')->nullable();
        $table->string('name')->unique();
        $table->text('description')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

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

    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->string('phone')->unique();
        $table->string('password');
        $table->string('vehicle_type')->default('motorcycle');
        $table->string('vehicle_number')->nullable();
        $table->string('license_number')->nullable();
        $table->string('status')->default('offline');
        $table->boolean('is_active')->default(true);
        $table->json('task_capabilities')->nullable();
        $table->timestamps();
    });

    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->string('type')->default('both');
        $table->text('address')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('shipments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('vendor_id')->nullable();
        $table->string('shipment_number')->nullable();
        $table->string('status')->default('draft');
        $table->string('source')->default('vendor_app');
        $table->string('fulfillment_type')->default('warehouse');
        $table->unsignedBigInteger('created_by_user_id')->nullable();
        $table->string('destination_mode')->default('single');
        $table->string('delivery_recipient_name')->nullable();
        $table->string('delivery_recipient_phone')->nullable();
        $table->string('delivery_town')->nullable();
        $table->timestamp('submitted_at')->nullable();
        $table->timestamp('cancelled_at')->nullable();
        $table->text('cancellation_reason')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('pickup_assignments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('shipment_id');
        $table->unsignedBigInteger('driver_id');
        $table->unsignedBigInteger('target_warehouse_id')->nullable();
        $table->string('status')->default('assigned');
        $table->timestamp('assigned_at')->nullable();
        $table->timestamps();
    });
}

function emailTemplateShipment(array $attributes = []): Shipment
{
    $vendor = $attributes['vendor'] ?? emailTemplateVendor();

    return Shipment::query()->create(array_merge([
        'vendor_id' => $vendor->id,
        'status' => ShipmentStatus::DRAFT->value,
        'source' => ShipmentSource::VENDOR_APP->value,
        'fulfillment_type' => FulfillmentType::WAREHOUSE->value,
        'destination_mode' => ShipmentDestinationMode::SINGLE->value,
        'delivery_recipient_name' => 'Kwame Dela',
        'delivery_recipient_phone' => '+233506470984',
    ], collect($attributes)->except('vendor')->all()));
}
