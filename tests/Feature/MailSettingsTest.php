<?php

use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use App\Http\Controllers\Admin\AdminMarketingController;
use App\Services\EmailTemplateService;
use App\Services\MailSettingsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\LogAdminAuditActivity::class);

    Schema::dropIfExists('notification_logs');
    Schema::dropIfExists('email_templates');
    Schema::dropIfExists('vendors');
    Schema::dropIfExists('admin_audit_logs');
    Schema::dropIfExists('user_roles');
    Schema::dropIfExists('role_permissions');
    Schema::dropIfExists('warehouse_capabilities');
    Schema::dropIfExists('permissions');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('users');
    Schema::dropIfExists('warehouses');
    Schema::dropIfExists('platform_settings');

    Schema::create('platform_settings', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->text('value')->nullable();
        $table->string('description')->nullable();
        $table->boolean('is_encrypted')->default(false);
        $table->timestamps();
    });

    Schema::create('warehouses', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('code')->unique();
        $table->boolean('is_active')->default(true);
        $table->boolean('is_hq')->default(false);
        $table->boolean('can_administer_system')->default(false);
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable()->unique();
        $table->string('phone', 20)->nullable()->unique();
        $table->string('photo_path')->nullable();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->boolean('is_active')->default(true);
        $table->timestamp('last_login_at')->nullable();
        $table->timestamp('last_permission_cache_at')->nullable();
        $table->foreignId('created_by_user_id')->nullable();
        $table->foreignId('warehouse_id')->nullable();
        $table->rememberToken();
        $table->timestamps();
    });

    Schema::create('roles', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->boolean('is_system_role')->default(false);
        $table->boolean('is_warehouse_role')->default(true);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('permissions', function (Blueprint $table) {
        $table->id();
        $table->string('module');
        $table->string('action');
        $table->string('name')->unique();
        $table->text('description')->nullable();
        $table->integer('sort_order')->default(0);
        $table->timestamps();
    });

    Schema::create('role_permissions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('role_id');
        $table->foreignId('permission_id');
        $table->timestamps();
    });

    Schema::create('user_roles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('role_id');
        $table->timestamp('assigned_at')->nullable();
        $table->foreignId('assigned_by')->nullable();
    });

    Schema::create('warehouse_capabilities', function (Blueprint $table) {
        $table->id();
        $table->foreignId('warehouse_id');
        $table->string('module');
        $table->string('scope')->default('own');
        $table->json('allowed_warehouse_ids')->nullable();
        $table->foreignId('granted_by_user_id')->nullable();
        $table->timestamps();
    });

    Schema::create('admin_audit_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('warehouse_id')->nullable();
        $table->string('scope', 20)->nullable();
        $table->string('action_type', 40);
        $table->string('action', 180);
        $table->string('description', 255)->nullable();
        $table->string('method', 12)->nullable();
        $table->string('route_name', 180)->nullable();
        $table->string('url', 2048)->nullable();
        $table->unsignedSmallInteger('status_code')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        $table->unsignedInteger('duration_ms')->nullable();
        $table->json('metadata')->nullable();
        $table->timestamp('created_at')->useCurrent();
    });

    Schema::create('email_templates', function (Blueprint $table) {
        $table->id();
        $table->string('key')->unique();
        $table->string('name');
        $table->string('category')->default('general');
        $table->string('recipient_type')->default('vendor');
        $table->string('subject');
        $table->text('body_html')->nullable();
        $table->text('body_text')->nullable();
        $table->json('variables')->nullable();
        $table->boolean('is_enabled')->default(true);
        $table->boolean('is_system')->default(false);
        $table->timestamps();
    });

    Schema::create('notification_logs', function (Blueprint $table) {
        $table->id();
        $table->nullableMorphs('notifiable');
        $table->string('type');
        $table->string('channel')->nullable();
        $table->string('title')->nullable();
        $table->text('body')->nullable();
        $table->json('data')->nullable();
        $table->string('status')->default('pending');
        $table->text('error')->nullable();
        $table->timestamps();
    });

    Schema::create('vendors', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('business_name')->nullable();
        $table->string('email')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
});

function makeMailSettingsAdmin(): User
{
    Cache::flush();

    $warehouse = Warehouse::query()->create([
        'name' => 'HQ',
        'code' => 'HQ-MAIL-'.uniqid(),
        'is_active' => true,
        'is_hq' => true,
        'can_administer_system' => true,
    ]);

    $role = Role::query()->create([
        'name' => 'Mail Settings Role '.uniqid(),
        'slug' => 'mail-settings-role-'.uniqid(),
        'is_system_role' => true,
        'is_warehouse_role' => true,
        'is_active' => true,
    ]);

    foreach (['settings.view', 'settings.edit'] as $permission) {
        $permissionModel = Permission::query()->firstOrCreate(
            ['name' => $permission],
            [
                'module' => 'settings',
                'action' => str($permission)->after('.')->toString(),
                'description' => $permission,
            ],
        );

        $role->permissions()->attach($permissionModel);
    }

    $user = User::factory()->create([
        'warehouse_id' => $warehouse->id,
        'is_active' => true,
    ]);

    $user->roles()->attach($role, ['assigned_at' => now()]);

    return $user;
}

function seedMailSettings(array $overrides = []): void
{
    $settings = array_merge([
        'mail_mailer' => 'smtp',
        'mail_host' => 'smtp.example.test',
        'mail_port' => '587',
        'mail_username' => 'support@example.test',
        'mail_password' => 'secret-password',
        'mail_encryption' => 'tls',
        'mail_from_address' => 'support@example.test',
        'mail_from_name' => 'ParcelMan Test',
    ], $overrides);

    foreach ($settings as $key => $value) {
        PlatformSetting::setValue($key, $value, encrypt: $key === 'mail_password');
    }
}

test('runtime mail config uses saved platform mail settings over environment config', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'parcelmanexpress',
        'mail.mailers.smtp.port' => 2525,
        'mail.mailers.smtp.username' => 'old@example.test',
        'mail.mailers.smtp.password' => 'old-password',
        'mail.mailers.smtp.scheme' => null,
        'mail.from.address' => 'old@example.test',
        'mail.from.name' => 'Old Name',
    ]);
    seedMailSettings();

    app(MailSettingsService::class)->apply();

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.example.test')
        ->and(config('mail.mailers.smtp.port'))->toBe(587)
        ->and(config('mail.mailers.smtp.username'))->toBe('support@example.test')
        ->and(config('mail.mailers.smtp.password'))->toBe('secret-password')
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.encryption'))->toBe('tls')
        ->and(config('mail.from.address'))->toBe('support@example.test')
        ->and(config('mail.from.name'))->toBe('ParcelMan Test');
});

test('runtime mail config supports local smtp without auth or auto tls', function () {
    config([
        'mail.mailers.smtp.host' => 'parcelmanexpress.com',
        'mail.mailers.smtp.port' => 587,
        'mail.mailers.smtp.username' => 'support@example.test',
        'mail.mailers.smtp.password' => 'secret-password',
        'mail.mailers.smtp.encryption' => 'tls',
        'mail.mailers.smtp.auto_tls' => true,
    ]);
    seedMailSettings([
        'mail_host' => '127.0.0.1',
        'mail_port' => '25',
        'mail_username' => '',
        'mail_password' => '',
        'mail_encryption' => '',
    ]);

    app(MailSettingsService::class)->apply();

    expect(config('mail.mailers.smtp.host'))->toBe('127.0.0.1')
        ->and(config('mail.mailers.smtp.port'))->toBe(25)
        ->and(config('mail.mailers.smtp.username'))->toBeNull()
        ->and(config('mail.mailers.smtp.password'))->toBeNull()
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.encryption'))->toBeNull()
        ->and(config('mail.mailers.smtp.auto_tls'))->toBeFalse();
});

test('saving mail settings preserves existing encrypted password when password field is blank', function () {
    seedMailSettings(['mail_password' => 'keep-this-password']);

    $this->actingAs(makeMailSettingsAdmin(), 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.save'), [
            'tab' => 'mail',
            'settings' => [
                'mail_host' => ['value' => 'mail.parcelmanexpress.com'],
                'mail_port' => ['value' => '587'],
                'mail_password' => ['value' => ''],
            ],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(PlatformSetting::getValue('mail_host'))->toBe('mail.parcelmanexpress.com')
        ->and(PlatformSetting::getValue('mail_password'))->toBe('keep-this-password');
});

test('mail settings save validates unsafe mail values', function () {
    $this->actingAs(makeMailSettingsAdmin(), 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.save'), [
            'tab' => 'mail',
            'settings' => [
                'mail_mailer' => ['value' => 'smtp'],
                'mail_host' => ['value' => 'bad host with spaces'],
                'mail_port' => ['value' => '70000'],
                'mail_encryption' => ['value' => 'starttls'],
                'mail_from_address' => ['value' => 'not-an-email'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings.mail_host.value', 'settings.mail_port.value', 'settings.mail_encryption.value', 'settings.mail_from_address.value']);
});

test('test email applies platform mail settings and hides raw transport details on failure', function () {
    seedMailSettings([
        'mail_host' => '127.0.0.1',
        'mail_port' => '1',
    ]);

    $response = $this->actingAs(makeMailSettingsAdmin(), 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.test-email'), [
            'email' => 'ricky@example.test',
        ]);

    $response->assertStatus(500)
        ->assertJson([
            'success' => false,
            'message' => 'Failed to send test email. Please check the SMTP host, port, encryption, username, and password, then try again.',
        ]);

    expect(config('mail.mailers.smtp.host'))->toBe('127.0.0.1')
        ->and(config('mail.mailers.smtp.port'))->toBe(1);
});

test('email template sends apply platform mail settings before mail delivery', function () {
    seedMailSettings(['mail_host' => 'template-smtp.example.test']);

    EmailTemplate::query()->create([
        'key' => 'vendor_welcome',
        'name' => 'Vendor Welcome',
        'category' => 'vendor',
        'recipient_type' => 'vendor',
        'subject' => 'Welcome {{ vendor_name }}',
        'body_text' => 'Hello {{ vendor_name }}',
        'is_enabled' => true,
        'is_system' => true,
    ]);

    $vendor = Vendor::query()->create([
        'name' => 'Akua Mensah',
        'business_name' => 'Akua Stores',
        'email' => 'akua@example.test',
        'is_active' => true,
    ]);

    Mail::fake();

    app(EmailTemplateService::class)->send('vendor_welcome', $vendor, [
        'vendor_name' => 'Akua Mensah',
    ]);

    expect(config('mail.mailers.smtp.host'))->toBe('template-smtp.example.test');
});

test('marketing email sends apply platform mail settings before mail delivery', function () {
    seedMailSettings(['mail_host' => 'marketing-smtp.example.test']);

    $vendor = Vendor::query()->create([
        'name' => 'Akua Mensah',
        'business_name' => 'Akua Stores',
        'email' => 'akua@example.test',
        'is_active' => true,
    ]);

    Mail::fake();

    $controller = app(AdminMarketingController::class);
    $method = new ReflectionMethod($controller, 'sendEmail');
    $method->setAccessible(true);

    $sent = $method->invoke($controller, $vendor, [
        'audience' => 'vendors',
        'subject' => 'Hello',
        'body' => 'Hello from ParcelMan',
        'email_template' => 'custom',
    ]);

    expect($sent)->toBeTrue()
        ->and(config('mail.mailers.smtp.host'))->toBe('marketing-smtp.example.test');
});
