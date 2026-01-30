<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DriverProfileService
{
    protected DriverActivityLogService $activityLogService;
    protected DriverAuthService $driverAuthService;

    public function __construct(DriverActivityLogService $activityLogService, DriverAuthService $driverAuthService)
    {
        $this->activityLogService = $activityLogService;
        $this->driverAuthService = $driverAuthService;
    }

    /**
     * Get driver profile.
     */
    public function getProfile(Driver $driver): array
    {
        return [
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => $this->driverAuthService->formatDriver($driver),
            ],
        ];
    }

    /**
     * Update driver profile.
     */
    public function updateProfile(Driver $driver, array $data, Request $request): array
    {
        // Update allowed fields
        $allowedFields = ['name', 'phone', 'vehicle_type', 'vehicle_number', 'license_number', 'base_location'];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $driver->$field = $data[$field];
            }
        }

        $driver->save();

        // Log activity
        $this->activityLogService->log(
            $driver->id,
            'driver_profile_updated',
            'Profile updated successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => $this->driverAuthService->formatDriver($driver),
            ],
        ];
    }

    /**
     * Change driver password.
     */
    public function changePassword(Driver $driver, string $currentPassword, string $newPassword, Request $request): array
    {
        // Verify current password
        if (!Hash::check($currentPassword, $driver->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        // Update password
        $driver->password = Hash::make($newPassword);
        $driver->save();

        // Revoke all existing tokens
        $driver->tokens()->delete();

        // Create new token
        $token = $driver->createToken('driver-app')->plainTextToken;

        // Log activity
        $this->activityLogService->log(
            $driver->id,
            'driver_password_changed',
            'Password changed successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'Password changed successfully.',
            'data' => [
                'user' => $this->driverAuthService->formatDriver($driver),
                'token' => $token,
            ],
        ];
    }
}
