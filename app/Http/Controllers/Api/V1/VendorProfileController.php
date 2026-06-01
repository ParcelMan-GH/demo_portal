<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Vendor\UpdateProfileRequest;
use App\Helpers\PhoneHelper;
use App\Services\VendorProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

    public function payoutAccount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Payout account retrieved successfully.',
            'data' => [
                'payout_account' => $this->profileService->formatPayoutAccount($request->user()),
            ],
        ]);
    }

    public function updatePayoutAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payout_momo_network' => ['required', 'string', Rule::in(['mtn', 'telecel', 'airteltigo'])],
            'payout_account_name' => ['required', 'string', 'max:255'],
            'payout_account_number' => ['required', 'string', 'max:20', function ($attribute, $value, $fail) {
                $local = PhoneHelper::toLocal((string) $value);

                if (!$local || !preg_match('/^0(?:2\d|5\d)\d{7}$/', $local)) {
                    $fail('Enter a valid 10-digit Ghana phone number');
                }
            }],
        ]);

        $result = $this->profileService->updatePayoutAccount(
            $request->user(),
            $validated,
            $request
        );

        return response()->json($result);
    }

    /**
     * Delete vendor account.
     * DELETE /api/v1/vendor/account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $vendor = $request->user();

        // Revoke all API tokens
        $vendor->tokens()->delete();

        // Mangle phone to free the unique constraint for re-registration
        $vendor->update([
            'phone'     => $vendor->phone . '_deleted_' . time(),
            'fcm_token' => null,
            'is_active' => false,
        ]);

        // Soft-delete the vendor
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Your account has been deleted successfully.',
        ]);
    }

    /**
     * Update vendor FCM device token.
     * POST /api/v1/vendor/fcm-token
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => ['required', 'string', 'max:512']]);
        $vendor = $request->user();
        $vendor->update(['fcm_token' => $request->fcm_token]);
        return response()->json(['success' => true, 'message' => 'FCM token updated.']);
    }
}
