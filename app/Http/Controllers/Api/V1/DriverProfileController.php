<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesActingDriver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\ChangePasswordRequest;
use App\Http\Requests\Api\Driver\UpdateProfileRequest;
use App\Services\DriverProfileService;
use App\Services\ProfilePhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverProfileController extends Controller
{
    use ResolvesActingDriver;

    protected DriverProfileService $profileService;

    public function __construct(DriverProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get driver profile.
     * GET /api/v1/driver/profile
     */
    public function show(Request $request): JsonResponse
    {
        $driver = $this->actingDriver($request);

        $result = $this->profileService->getProfile($driver);

        return response()->json($result);
    }

    /**
     * Update driver profile.
     * PUT /api/v1/driver/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $driver = $this->actingDriver($request);

        $result = $this->profileService->updateProfile(
            $driver,
            $request->validated(),
            $request
        );

        return response()->json($result);
    }

    /**
     * Update the driver's profile photo.
     * POST /api/v1/driver/profile/photo
     *
     * POST rather than PUT: PHP only populates uploaded files on POST, so a
     * multipart PUT body arrives empty.
     */
    public function updatePhoto(Request $request): JsonResponse
    {
        $request->validate(ProfilePhotoService::rules());

        $result = $this->profileService->updatePhoto(
            $this->actingDriver($request),
            $request->file('photo'),
            $request
        );

        return response()->json($result);
    }

    /**
     * Change driver password.
     * PUT /api/v1/driver/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $driver = $this->actingDriver($request);
        $data = $request->validated();

        $result = $this->profileService->changePassword(
            $driver,
            $data['current_password'],
            $data['new_password'],
            $request
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json($result, $statusCode);
    }

    /**
     * Update driver FCM device token.
     * POST /api/v1/driver/fcm-token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => ['required', 'string', 'max:512']]);
        $driver = $this->actingDriver($request);
        $driver->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true, 'message' => 'FCM token updated.']);
    }
}
