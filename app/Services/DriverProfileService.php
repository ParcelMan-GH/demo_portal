<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverProfileService
{
    protected DriverActivityLogService $activityLogService;

    protected DriverAuthService $driverAuthService;

    protected ProfilePhotoService $photoService;

    public function __construct(
        DriverActivityLogService $activityLogService,
        DriverAuthService $driverAuthService,
        ProfilePhotoService $photoService,
    ) {
        $this->activityLogService = $activityLogService;
        $this->driverAuthService = $driverAuthService;
        $this->photoService = $photoService;
    }

    /**
     * Replace the driver's profile photo.
     *
     * A dedicated endpoint rather than part of updateProfile: PHP only parses
     * multipart bodies on POST, so a PUT carrying a file would arrive empty.
     */
    public function updatePhoto(Driver $driver, UploadedFile $file, Request $request): array
    {
        $this->photoService->assertSupported($driver->getTable());

        $driver->photo_path = $this->photoService->replace(
            $file,
            ProfilePhotoService::FOLDER_DRIVER,
            $driver->photo_path,
        );
        $driver->save();

        $this->activityLogService->log(
            $driver->id,
            'driver_profile_photo_updated',
            'Profile photo updated',
            $request
        );

        return [
            'success' => true,
            'message' => 'Profile photo updated successfully.',
            'data' => [
                'user' => $this->driverAuthService->formatDriver($driver),
            ],
        ];
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
        if (! Hash::check($currentPassword, $driver->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect.',
            ];
        }

        $currentTokenId = $driver->currentAccessToken()?->getKey();

        DB::transaction(function () use ($driver, $newPassword, $request, $currentTokenId): void {
            $driver->password = Hash::make($newPassword);
            $driver->save();

            $otherTokens = $driver->tokens();
            if ($currentTokenId !== null) {
                $otherTokens->whereKeyNot($currentTokenId);
            }
            $otherTokens->delete();

            $this->activityLogService->log(
                $driver->id,
                'driver_password_changed',
                'Password changed successfully',
                $request
            );
        });

        return [
            'success' => true,
            'message' => 'Password changed successfully.',
            'data' => [
                'user' => $this->driverAuthService->formatDriver($driver->fresh()),
            ],
        ];
    }
}
