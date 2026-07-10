<?php

use App\Models\Driver;
use App\Models\OtpCode;
use App\Models\Vendor;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app_review.enabled' => true,
        'app_review.vendor_phone' => '0200000001',
        'app_review.vendor_otp' => '654321',
        'app_review.rider_phone' => '0200000002',
        'app_review.rider_password' => 'TestOnlyReviewPassword!',
    ]);
});

test('configured review vendor receives a normal expiring fixed otp without using sms', function () {
    $vendor = Vendor::create([
        'name' => 'Apple Review Vendor',
        'business_name' => 'ParcelMan App Review Shop',
        'phone' => '+233200000001',
        'is_active' => true,
    ]);

    $this->mock(SmsService::class, function ($mock) {
        $mock->shouldNotReceive('send');
    });

    $sendResponse = $this->postJson('/api/v1/auth/vendor/send-otp', [
        'phone' => '+233200000001',
    ]);

    $sendResponse
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonMissing(['otp' => '654321'])
        ->assertJsonMissing(['code' => '654321']);

    $otp = OtpCode::query()
        ->where('phone', '+233200000001')
        ->where('purpose', 'login')
        ->firstOrFail();

    expect($otp->code)->toBe('654321')
        ->and($otp->expires_at->isFuture())->toBeTrue()
        ->and($otp->verified_at)->toBeNull();

    $this->postJson('/api/v1/auth/vendor/verify-phone', [
        'phone' => '+233200000001',
        'otp' => '654321',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user_exists', true)
        ->assertJsonPath('data.user.id', $vendor->id)
        ->assertJsonStructure(['data' => ['token']]);

    expect($otp->fresh()->verified_at)->not->toBeNull();
});

test('fixed review otp cannot authenticate any other phone', function () {
    Vendor::create([
        'name' => 'Ordinary Vendor',
        'phone' => '+233200000003',
        'is_active' => true,
    ]);

    $this->mock(SmsService::class, function ($mock) {
        $mock->shouldReceive('send')
            ->once()
            ->with('+233200000003', Mockery::type('string'))
            ->andReturnTrue();
    });

    $this->postJson('/api/v1/auth/vendor/send-otp', [
        'phone' => '+233200000003',
    ])->assertOk();

    OtpCode::query()
        ->where('phone', '+233200000003')
        ->where('purpose', 'login')
        ->update(['code' => '111111']);

    $this->postJson('/api/v1/auth/vendor/verify-phone', [
        'phone' => '+233200000003',
        'otp' => '654321',
    ])->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Invalid or expired OTP.');
});

test('invalid review configuration fails closed to the normal sms path', function () {
    config(['app_review.vendor_otp' => 'not-six-digits']);

    $this->mock(SmsService::class, function ($mock) {
        $mock->shouldReceive('send')->once()->andReturnTrue();
    });

    $this->postJson('/api/v1/auth/vendor/send-otp', [
        'phone' => '+233200000001',
    ])->assertOk();

    expect(OtpCode::query()->firstOrFail()->code)->not->toBe('not-six-digits');
});

test('provision command creates resettable review accounts and fictional sample data', function () {
    $this->artisan('app-review:provision')->assertSuccessful();

    $vendor = Vendor::where('phone', '+233200000001')->firstOrFail();
    $rider = Driver::where('phone', '+233200000002')->firstOrFail();

    expect($vendor->is_active)->toBeTrue()
        ->and($vendor->shipments()->count())->toBe(6)
        ->and($rider->is_active)->toBeTrue()
        ->and($rider->getCapabilities())->toEqualCanonicalizing(Driver::CAPABILITIES)
        ->and($rider->pickupAssignments()->count())->toBe(4);

    $this->assertDatabaseCount('notification_logs', 5);

    $this->artisan('app-review:provision')->assertSuccessful();

    expect($vendor->shipments()->count())->toBe(6)
        ->and($rider->pickupAssignments()->count())->toBe(4);
    $this->assertDatabaseCount('notification_logs', 5);

    $this->artisan('app-review:provision', ['--reset' => true])->assertSuccessful();

    expect($vendor->shipments()->count())->toBe(6)
        ->and($rider->pickupAssignments()->count())->toBe(4);
    $this->assertDatabaseCount('notification_logs', 5);

    $this->artisan('app-review:provision', ['--cleanup' => true])->assertSuccessful();

    expect($vendor->fresh()->is_active)->toBeFalse()
        ->and($vendor->shipments()->count())->toBe(0)
        ->and($rider->fresh()->is_active)->toBeFalse()
        ->and($rider->pickupAssignments()->count())->toBe(0);
    $this->assertDatabaseCount('notification_logs', 0);
});

test('provision command rejects incomplete configuration without exposing secrets', function () {
    config(['app_review.rider_password' => 'short']);

    $this->artisan('app-review:provision')
        ->expectsOutput('App Review rider password must contain at least 12 characters.')
        ->assertFailed();

    $this->assertDatabaseCount('vendors', 0);
    $this->assertDatabaseCount('drivers', 0);
});
