<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\UpdateProfileRequest;
use App\Services\VendorProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorProfileController extends Controller
{
    protected VendorProfileService $profileService;

    public function __construct(VendorProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get vendor profile.
     * GET /api/v1/vendor/profile
     */
    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $result = $this->profileService->getProfile($vendor);

        return response()->json($result);
    }

    /**
     * Update vendor profile.
     * PUT /api/v1/vendor/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $vendor = $request->user();

        $result = $this->profileService->updateProfile(
            $vendor,
            $request->validated(),
            $request
        );

        return response()->json($result);
    }
}
