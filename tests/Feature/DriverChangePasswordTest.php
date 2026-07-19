<?php

use App\Models\Driver;
use App\Models\DriverActivityLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function createPasswordChangeDriver(array $overrides = []): Driver
{
    return Driver::create(array_merge([
        'name' => 'Password Test Rider',
        'email' => 'password-rider@example.com',
        'phone' => '+233200000099',
        'password' => Hash::make('CurrentPass123!'),
        'vehicle_type' => 'motorcycle',
        'status' => 'available',
        'is_active' => true,
        'task_capabilities' => ['pickup', 'delivery'],
    ], $overrides));
}

test('rider can change password with canonical fields and keeps the current token', function () {
    $driver = createPasswordChangeDriver();
    $currentToken = $driver->createToken('current-device');
    $otherToken = $driver->createToken('other-device');
    $otherPlainTextToken = $otherToken->plainTextToken;

    $response = $this->withToken($currentToken->plainTextToken)
        ->putJson('/api/v1/driver/change-password', [
            'current_password' => 'CurrentPass123!',
            'new_password' => 'UpdatedPass456!',
            'confirm_password' => 'UpdatedPass456!',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Password changed successfully.')
        ->assertJsonPath('data.user.id', $driver->id)
        ->assertJsonMissingPath('data.token')
        ->assertJsonMissing(['exception']);

    expect(Hash::check('UpdatedPass456!', $driver->fresh()->password))->toBeTrue()
        ->and(Hash::check('CurrentPass123!', $driver->fresh()->password))->toBeFalse();

    $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->id]);
    $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
    $this->assertDatabaseHas('driver_activity_logs', [
        'driver_id' => $driver->id,
        'action' => 'driver_password_changed',
    ]);

    $activityLog = DriverActivityLog::where('driver_id', $driver->id)
        ->where('action', 'driver_password_changed')
        ->firstOrFail();
    $loggedValues = json_encode($activityLog->getAttributes());
    expect($loggedValues)
        ->not->toContain('CurrentPass123!')
        ->not->toContain('UpdatedPass456!');

    $this->withToken($currentToken->plainTextToken)
        ->getJson('/api/v1/driver/profile')
        ->assertOk();

    Auth::forgetGuards();

    $this->withToken($otherPlainTextToken)
        ->getJson('/api/v1/driver/profile')
        ->assertUnauthorized();

    $this->postJson('/api/v1/driver/login', [
        'identifier' => $driver->email,
        'password' => 'CurrentPass123!',
    ])->assertUnauthorized();

    $this->postJson('/api/v1/driver/login', [
        'identifier' => $driver->email,
        'password' => 'UpdatedPass456!',
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.id', $driver->id)
        ->assertJsonStructure(['data' => ['token']]);
});

test('build 9 password field aliases remain supported', function () {
    $driver = createPasswordChangeDriver();
    $token = $driver->createToken('build-9-device');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/driver/change-password', [
            'current_password' => 'CurrentPass123!',
            'password' => 'LegacyPass456!',
            'password_confirmation' => 'LegacyPass456!',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonMissingPath('data.token');

    expect(Hash::check('LegacyPass456!', $driver->fresh()->password))->toBeTrue();

    $this->withToken($token->plainTextToken)
        ->getJson('/api/v1/driver/profile')
        ->assertOk();
});

test('canonical password fields take precedence over build 9 aliases', function () {
    $driver = createPasswordChangeDriver();
    $token = $driver->createToken('canonical-device');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/driver/change-password', [
            'current_password' => 'CurrentPass123!',
            'new_password' => 'CanonicalPass456!',
            'confirm_password' => 'CanonicalPass456!',
            'password' => 'LegacyPass456!',
            'password_confirmation' => 'LegacyPass456!',
        ])
        ->assertOk();

    expect(Hash::check('CanonicalPass456!', $driver->fresh()->password))->toBeTrue()
        ->and(Hash::check('LegacyPass456!', $driver->fresh()->password))->toBeFalse();
});

test('wrong current password does not change password or revoke tokens', function () {
    $driver = createPasswordChangeDriver();
    $token = $driver->createToken('current-device');
    $otherToken = $driver->createToken('other-device');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/driver/change-password', [
            'current_password' => 'WrongPass123!',
            'new_password' => 'UpdatedPass456!',
            'confirm_password' => 'UpdatedPass456!',
        ])
        ->assertUnprocessable()
        ->assertExactJson([
            'success' => false,
            'message' => 'Current password is incorrect.',
        ]);

    expect(Hash::check('CurrentPass123!', $driver->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
    $this->assertDatabaseHas('personal_access_tokens', ['id' => $otherToken->accessToken->id]);
});

test('password validation returns clean json errors', function (array $payload, string $message) {
    $driver = createPasswordChangeDriver();
    $token = $driver->createToken('validation-device');

    $this->withToken($token->plainTextToken)
        ->putJson('/api/v1/driver/change-password', $payload)
        ->assertUnprocessable()
        ->assertExactJson([
            'success' => false,
            'message' => $message,
        ]);

    expect(Hash::check('CurrentPass123!', $driver->fresh()->password))->toBeTrue();
})->with([
    'missing current password' => [[
        'new_password' => 'UpdatedPass456!',
        'confirm_password' => 'UpdatedPass456!',
    ], 'Current password is required.'],
    'missing new password' => [
        ['current_password' => 'CurrentPass123!'],
        'New password is required.',
    ],
    'short new password' => [[
        'current_password' => 'CurrentPass123!',
        'new_password' => 'short7',
        'confirm_password' => 'short7',
    ], 'New password must be at least 8 characters.'],
    'mismatched confirmation' => [[
        'current_password' => 'CurrentPass123!',
        'new_password' => 'UpdatedPass456!',
        'confirm_password' => 'DifferentPass456!',
    ], 'Password confirmation does not match.'],
    'short build 9 password' => [[
        'current_password' => 'CurrentPass123!',
        'password' => 'short7',
        'password_confirmation' => 'short7',
    ], 'New password must be at least 8 characters.'],
]);
