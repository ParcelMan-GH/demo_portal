<?php

use App\Models\User;
use App\Services\AdminAuditLogService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    if (!Schema::hasTable('platform_settings')) {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('description')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });
    }

    if (!Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable()->unique();
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
    }
});

test('landing page contains vendor and driver login options', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Vendor Portal');
    $response->assertSee('Driver Login');
});

test('web auth pages are accessible', function () {
    $this->get('/vendor/login')->assertOk();
    $this->get('/vendor/home')->assertOk();
    $this->get('/vendor/profile')->assertOk();
    $this->get('/vendor/shipments')->assertOk();
    $this->get('/vendor/shipments/create')->assertOk();
    $this->get('/vendor/shipments/1')->assertOk();
    $this->get('/vendor/shipments/1/edit')->assertOk();
    $this->get('/driver/login')->assertOk();
    $this->get('/driver/home')->assertOk();
    $this->get('/driver/profile')->assertOk();
    $this->get('/driver/pickups')->assertOk();
    $this->get('/driver/pickups/1')->assertOk();
});

test('admin login route remains accessible', function () {
    $this->get('/admin/login')->assertOk();
});

test('staff can log in with phone number and password', function () {
    $this->mock(AdminAuditLogService::class, function ($mock) {
        $mock->shouldReceive('logAuthEvent')->zeroOrMoreTimes();
    });

    $user = User::factory()->create([
        'phone' => '+233241234567',
        'password' => Hash::make('secret-password'),
        'is_active' => true,
    ]);

    $this->post('/admin/login', [
        'email' => '0241234567',
        'password' => 'secret-password',
    ])->assertRedirect(route('warehouse.dashboard'));

    $this->assertAuthenticatedAs($user, 'admin');
});
