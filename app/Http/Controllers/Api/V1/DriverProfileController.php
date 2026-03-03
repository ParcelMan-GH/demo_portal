<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Driver\ChangePasswordRequest;
use App\Http\Requests\Api\Driver\UpdateProfileRequest;
use App\Services\DriverProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverProfileController extends Controller
{
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
        $driver = $request->user();

        $result = $this->profileService->getProfile($driver);

        return response()->json($result);
    }

    /**
     * Update driver profile.
     * PUT /api/v1/driver/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $driver = $request->user();

        $result = $this->profileService->updateProfile(
            $driver,
            $request->validated(),
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
        $driver = $request->user();

        $result = $this->profileService->changePassword(
            $driver,
            $request->current_password,
            $request->new_password,
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
        $driver = $request->user();
        $driver->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['success' => true, 'message' => 'FCM token updated.']);
    }
}
