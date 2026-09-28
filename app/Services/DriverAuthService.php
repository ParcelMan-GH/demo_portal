<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverAuthService
{
    protected DriverActivityLogService $activityLogService;

    public function __construct(
        DriverActivityLogService $activityLogService,
        private DriverWorkloadService $workloads,
    )
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Login with email or phone and password.
     */
    public function login(string $identifier, string $password, Request $request): array
    {
        $driver = $this->findDriverByIdentifier($identifier);

        if (!$driver || !Hash::check($password, $driver->password)) {
            // Log failed attempt
            $this->activityLogService->log(
                $driver?->id,
                'driver_login_failed',
                'Invalid email/phone or password',
                $request
            );

            return [
                'success' => false,
                'message' => 'Invalid email/phone or password.',
            ];
        }

        if (!$driver->is_active) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ];
        }

        // Update last login
        $driver->last_login_at = now();
        $driver->status = 'available';

        // Save FCM token if provided at login time
        if (!empty($request->input('fcm_token'))) {
            $driver->fcm_token = $request->input('fcm_token');
        }

        $driver->save();
        $driver = $this->workloads->syncStatus($driver);

        // Create token
        $token = $driver->createToken('driver-app')->plainTextToken;

        // Log activity
        $this->activityLogService->log(
            $driver->id,
            'driver_login',
            'Rider logged in successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $this->formatDriver($driver),
                'token' => $token,
            ],
        ];
    }

    /**
     * Logout driver.
     */
    public function logout(Driver $driver, Request $request): array
    {
        // Update status to offline
        $driver->status = 'offline';
        $driver->save();

        // Revoke current token
        $driver->currentAccessToken()->delete();

        // Log activity
        $this->activityLogService->log(
            $driver->id,
            'driver_logout',
            'Rider logged out',
            $request
        );

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
        ];
    }

    /**
     * Resolve a driver by email or phone. Phone input is normalized to the
     * stored +233xxxxxxxxx format before lookup.
     */
    protected function findDriverByIdentifier(string $identifier): ?Driver
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        if (str_contains($identifier, '@')) {
            return Driver::where('email', $identifier)->first();
        }

        $normalized = PhoneHelper::format($identifier);

        if ($normalized === null) {
            // Not an email and not a parseable Ghana phone — fall back to a raw
            // phone match so odd formats still have a chance to authenticate.
            return Driver::where('phone', $identifier)->first();
        }

        return Driver::where('phone', $normalized)->first();
    }

    /**
     * Format driver for API response.
     */
    /**
     * The driver as every screen receives them.
     *
     * This is the shape the app writes back from after a save, so a column that
     * is persisted but missing here reads as "the server dropped my change" on
     * the vehicle and profile screens. Keep it in step with the writable fields
     * in DriverProfileService.
     */
    public function formatDriver(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'email' => $driver->email,
            'avatar' => strtoupper(substr($driver->name, 0, 1)),
            'photo_url' => $driver->photo_path ? app(StorageService::class)->getUrl($driver->photo_path) : null,
            'phone' => $driver->phone,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_number' => $driver->vehicle_number,
            'make_model' => $driver->make_model,
            'license_number' => $driver->license_number,
            'max_capacity' => $driver->max_capacity,
            'base_location' => $driver->base_location,
            'status' => $driver->status,
            'is_active' => $driver->is_active,
            'task_capabilities' => $driver->getCapabilities(),

            // The alert toggles, so the app has them before the notifications
            // screen is ever opened.
            'notification_settings' => $driver->notificationPreferences(),
        ];
    }
}
