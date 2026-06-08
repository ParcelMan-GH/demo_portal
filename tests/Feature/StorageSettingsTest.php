<?php

use App\Models\Permission;
use App\Models\PlatformSetting;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StorageService;
use Database\Seeders\ShipmentSettingsSeeder;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutMiddleware(\App\Http\Middleware\LogAdminAuditActivity::class);

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
});

function makeStorageSettingsAdmin(array $permissions = [], bool $isHq = true): User
{
    Cache::flush();

    $warehouse = Warehouse::query()->create([
        'name' => $isHq ? 'HQ' : 'Branch',
        'code' => 'HQ-STORAGE-'.uniqid(),
        'is_active' => true,
        'is_hq' => $isHq,
        'can_administer_system' => $isHq,
    ]);

    $role = Role::query()->create([
        'name' => 'Storage Settings Role '.uniqid(),
        'slug' => 'storage-settings-role-'.uniqid(),
        'is_system_role' => true,
        'is_active' => true,
    ]);

    foreach ($permissions as $permission) {
        $permissionModel = Permission::query()->firstOrCreate(
            ['name' => $permission],
            [
                'module' => str($permission)->before('.')->toString(),
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

test('storage service uses database driver setting instead of filesystem default config', function () {
    Storage::fake('public');

    config(['filesystems.default' => 's3']);
    PlatformSetting::setValue('storage.driver', 'local');

    $upload = app(StorageService::class)->upload(
        UploadedFile::fake()->image('parcel.jpg', 120, 120),
        'storage-source-test',
    );

    Storage::disk('public')->assertExists($upload['path']);
});

test('storj credentials are retrieved from encrypted platform settings', function () {
    PlatformSetting::setValue('storage.s3.access_key', 'test-access-key', encrypt: true);
    PlatformSetting::setValue('storage.s3.secret_key', 'test-secret-key', encrypt: true);

    expect(PlatformSetting::query()->where('key', 'storage.s3.access_key')->value('value'))
        ->not->toBe('test-access-key')
        ->and(PlatformSetting::query()->where('key', 'storage.s3.secret_key')->value('value'))
        ->not->toBe('test-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.access_key'))
        ->toBe('test-access-key')
        ->and(PlatformSetting::getValue('storage.s3.secret_key'))
        ->toBe('test-secret-key');
});

test('storj uploads fail before writing when database configuration is incomplete', function () {
    PlatformSetting::setValue('storage.driver', 's3');
    PlatformSetting::setValue('storage.s3.access_key', '', encrypt: true);
    PlatformSetting::setValue('storage.s3.secret_key', '', encrypt: true);
    PlatformSetting::setValue('storage.s3.bucket', '');
    PlatformSetting::setValue('storage.s3.endpoint', '');
    PlatformSetting::setValue('storage.s3.region', 'us-east-1');

    app(StorageService::class)->upload(
        UploadedFile::fake()->image('parcel.jpg', 120, 120),
        'storj-incomplete-test',
    );
})->throws(RuntimeException::class, 'S3/Storj storage is selected');

test('storage uploads throw when the active disk write fails', function () {
    PlatformSetting::setValue('storage.driver', 'local');

    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('put')->once()->andReturnFalse();

    Storage::shouldReceive('disk')
        ->with('public')
        ->once()
        ->andReturn($disk);

    app(StorageService::class)->upload(
        UploadedFile::fake()->image('parcel.jpg', 120, 120),
        'write-failure-test',
    );
})->throws(RuntimeException::class, 'Unable to write file to configured storage disk.');

test('storage service reports redacted local connection status', function () {
    PlatformSetting::setValue('storage.driver', 'local');

    $status = app(StorageService::class)->connectionStatus();

    expect($status)
        ->toMatchArray([
            'driver' => 'local',
            'configured' => true,
            'reachable' => true,
        ])
        ->and($status)->toHaveKeys([
            'message',
            'local_path',
            'public_linked',
        ])
        ->and($status)->not->toHaveKey('access_key')
        ->and($status)->not->toHaveKey('secret_key');
});

test('storj connection status falls back to demo prefix when database prefix is blank', function () {
    PlatformSetting::setValue('storage.driver', 's3');
    PlatformSetting::setValue('storage.s3.access_key', '', encrypt: true);
    PlatformSetting::setValue('storage.s3.secret_key', '', encrypt: true);
    PlatformSetting::setValue('storage.s3.bucket', '');
    PlatformSetting::setValue('storage.s3.endpoint', '');
    PlatformSetting::setValue('storage.s3.region', 'us-east-1');
    PlatformSetting::setValue('storage.s3.env', '');

    $status = app(StorageService::class)->connectionStatus();

    expect($status['prefix'])->toBe('demo');
});

test('storage settings page does not render saved storj secrets', function () {
    PlatformSetting::setValue('storage.driver', 'local');
    PlatformSetting::setValue('storage.s3.access_key', 'visible-access-key-should-not-render', encrypt: true);
    PlatformSetting::setValue('storage.s3.secret_key', 'visible-secret-key-should-not-render', encrypt: true);

    $admin = makeStorageSettingsAdmin(['settings.view', 'settings.edit']);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.settings.index', ['tab' => 'storage']))
        ->assertOk()
        ->assertDontSee('visible-access-key-should-not-render')
        ->assertDontSee('visible-secret-key-should-not-render');
});

test('storage settings page only shows save action to admins who can edit settings', function () {
    $viewer = makeStorageSettingsAdmin(['settings.view']);

    $this->actingAs($viewer, 'admin')
        ->get(route('admin.settings.index', ['tab' => 'storage']))
        ->assertOk()
        ->assertDontSee('Save Changes');

    $editor = makeStorageSettingsAdmin(['settings.view', 'settings.edit']);

    $this->actingAs($editor, 'admin')
        ->get(route('admin.settings.index', ['tab' => 'storage']))
        ->assertOk()
        ->assertSee('Save Changes');
});

test('authorized storage settings save encrypts storj keys and preserves redacted blanks', function () {
    $admin = makeStorageSettingsAdmin(['settings.edit']);

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.save'), [
            'tab' => 'storage',
            'settings' => [
                'storage.driver' => ['value' => 's3'],
                'storage.s3.access_key' => ['value' => 'saved-access-key'],
                'storage.s3.secret_key' => ['value' => 'saved-secret-key'],
                'storage.s3.bucket' => ['value' => 'saved-bucket'],
                'storage.s3.endpoint' => ['value' => 'https://gateway.storjshare.io'],
                'storage.s3.region' => ['value' => 'us-east-1'],
                'storage.s3.env' => ['value' => 'prod'],
                'storage.s3.signed_url_expiry' => ['value' => '45'],
            ],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(PlatformSetting::query()->where('key', 'storage.s3.access_key')->value('value'))
        ->not->toBe('saved-access-key')
        ->and(PlatformSetting::query()->where('key', 'storage.s3.secret_key')->value('value'))
        ->not->toBe('saved-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.access_key'))->toBe('saved-access-key')
        ->and(PlatformSetting::getValue('storage.s3.secret_key'))->toBe('saved-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.bucket'))->toBe('saved-bucket');

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.save'), [
            'tab' => 'storage',
            'settings' => [
                'storage.s3.access_key' => ['value' => ''],
                'storage.s3.secret_key' => ['value' => ''],
                'storage.s3.bucket' => ['value' => 'updated-bucket'],
            ],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(PlatformSetting::getValue('storage.s3.access_key'))->toBe('saved-access-key')
        ->and(PlatformSetting::getValue('storage.s3.secret_key'))->toBe('saved-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.bucket'))->toBe('updated-bucket');
});

test('shipment settings seeder does not overwrite existing database-managed storj settings', function () {
    PlatformSetting::setValue('storage.driver', 's3');
    PlatformSetting::setValue('storage.s3.access_key', 'existing-access-key', encrypt: true);
    PlatformSetting::setValue('storage.s3.secret_key', 'existing-secret-key', encrypt: true);
    PlatformSetting::setValue('storage.s3.bucket', 'existing-bucket');
    PlatformSetting::setValue('storage.s3.endpoint', 'https://gateway.storjshare.io');
    PlatformSetting::setValue('storage.s3.region', 'us-east-1');
    PlatformSetting::setValue('storage.s3.env', 'prod');

    (new ShipmentSettingsSeeder)->run();

    expect(PlatformSetting::getValue('storage.driver'))->toBe('s3')
        ->and(PlatformSetting::getValue('storage.s3.access_key'))->toBe('existing-access-key')
        ->and(PlatformSetting::getValue('storage.s3.secret_key'))->toBe('existing-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.bucket'))->toBe('existing-bucket')
        ->and(PlatformSetting::getValue('storage.s3.endpoint'))->toBe('https://gateway.storjshare.io')
        ->and(PlatformSetting::getValue('storage.s3.env'))->toBe('prod');
});

test('shipment settings seeder encrypts existing plaintext storj credentials without changing values', function () {
    PlatformSetting::query()->create([
        'key' => 'storage.s3.access_key',
        'value' => 'legacy-plaintext-access-key',
        'description' => 'Legacy access key',
        'is_encrypted' => false,
    ]);
    PlatformSetting::query()->create([
        'key' => 'storage.s3.secret_key',
        'value' => 'legacy-plaintext-secret-key',
        'description' => 'Legacy secret key',
        'is_encrypted' => false,
    ]);

    (new ShipmentSettingsSeeder)->run();

    $access = PlatformSetting::query()->where('key', 'storage.s3.access_key')->first();
    $secret = PlatformSetting::query()->where('key', 'storage.s3.secret_key')->first();

    expect($access->is_encrypted)->toBeTrue()
        ->and($access->value)->not->toBe('legacy-plaintext-access-key')
        ->and($secret->is_encrypted)->toBeTrue()
        ->and($secret->value)->not->toBe('legacy-plaintext-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.access_key'))->toBe('legacy-plaintext-access-key')
        ->and(PlatformSetting::getValue('storage.s3.secret_key'))->toBe('legacy-plaintext-secret-key');
});

test('storj credential migration encrypts existing plaintext platform settings', function () {
    PlatformSetting::query()->create([
        'key' => 'storage.s3.access_key',
        'value' => 'migration-plaintext-access-key',
        'description' => 'Legacy access key',
        'is_encrypted' => false,
    ]);
    PlatformSetting::query()->create([
        'key' => 'storage.s3.secret_key',
        'value' => 'migration-plaintext-secret-key',
        'description' => 'Legacy secret key',
        'is_encrypted' => false,
    ]);

    $migration = include database_path('migrations/2026_06_08_000002_encrypt_storage_s3_credentials.php');
    $migration->up();

    $access = PlatformSetting::query()->where('key', 'storage.s3.access_key')->first();
    $secret = PlatformSetting::query()->where('key', 'storage.s3.secret_key')->first();

    expect($access->is_encrypted)->toBeTrue()
        ->and($access->value)->not->toBe('migration-plaintext-access-key')
        ->and($secret->is_encrypted)->toBeTrue()
        ->and($secret->value)->not->toBe('migration-plaintext-secret-key')
        ->and(PlatformSetting::getValue('storage.s3.access_key'))->toBe('migration-plaintext-access-key')
        ->and(PlatformSetting::getValue('storage.s3.secret_key'))->toBe('migration-plaintext-secret-key');
});

test('admins without settings edit permission cannot save storage settings', function () {
    $admin = makeStorageSettingsAdmin(['settings.view']);

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.save'), [
            'tab' => 'storage',
            'settings' => [
                'storage.driver' => ['value' => 's3'],
            ],
        ])
        ->assertForbidden();
});

test('branch admins with settings edit role cannot save global storage settings', function () {
    $admin = makeStorageSettingsAdmin(['settings.edit'], isHq: false);

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(route('admin.settings.save'), [
            'tab' => 'storage',
            'settings' => [
                'storage.driver' => ['value' => 's3'],
            ],
        ])
        ->assertForbidden();
});
