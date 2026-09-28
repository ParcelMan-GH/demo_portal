<?php

namespace App\Services;

use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
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
        /*
         * The fields a driver may change about themselves.
         *
         * Drivers live in their own `drivers` table (App\Models\Driver), not in
         * `users` — so this list has to match the `drivers` schema, which is:
         * name, email, phone, vehicle_type, vehicle_number, license_number,
         * base_location, photo_path, status, task_capabilities.
         *
         * `email` is the one addition: the profile screen has always collected
         * it and login accepts an email, but the field was never writable, so an
         * email edit looked saved and then reverted.
         *
         * make_model and max_capacity are here now that
         * 2026_09_29_000001 adds them to `drivers` — the vehicle screen collects
         * both and previously had nowhere to put them.
         *
         * There is no separate `license_plate` column, by design: `vehicle_number`
         * already holds the vehicle's registration (the seeded values are
         * Ghanaian plates, e.g. "GR-1234-20"). A second plate column would be a
         * duplicate of the same fact. The screen's "License Plate Number" field
         * writes to `vehicle_number`.
         *
         * The list stays filtered against the schema, so a name that is wrong for
         * a future table cannot become a 500.
         */
        $writable = ['name', 'phone', 'email', 'vehicle_type', 'vehicle_number', 'make_model', 'license_number', 'max_capacity', 'base_location', 'emergency_contact'];
        $allowedFields = array_values(array_filter(
            $writable,
            fn (string $field) => Schema::hasColumn($driver->getTable(), $field)
        ));

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
