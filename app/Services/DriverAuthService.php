<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverAuthService
{
    protected DriverActivityLogService $activityLogService;

    public function __construct(DriverActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Login with email and password.
     */
    public function login(string $email, string $password, Request $request): array
    {
        $driver = Driver::where('email', $email)->first();

        if (!$driver || !Hash::check($password, $driver->password)) {
            // Log failed attempt
            $this->activityLogService->log(
                $driver?->id,
                'driver_login_failed',
                'Invalid email or password',
                $request
            );

            return [
                'success' => false,
                'message' => 'Invalid email or password.',
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

        // Create token
        $token = $driver->createToken('driver-app')->plainTextToken;

        // Log activity
        $this->activityLogService->log(
            $driver->id,
            'driver_login',
            'Driver logged in successfully',
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
            'Driver logged out',
            $request
        );

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
        ];
    }

    /**
     * Format driver for API response.
     */
    public function formatDriver(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'email' => $driver->email,
            'phone' => $driver->phone,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_number' => $driver->vehicle_number,
            'license_number' => $driver->license_number,
            'base_location' => $driver->base_location,
            'status' => $driver->status,
            'is_active' => $driver->is_active,
            'task_capabilities' => $driver->getCapabilities(),
        ];
    }
}
