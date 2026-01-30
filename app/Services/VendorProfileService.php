<?php

namespace App\Services;

use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorProfileService
{
    protected ActivityLogService $activityLogService;
    protected OtpService $otpService;

    public function __construct(ActivityLogService $activityLogService, OtpService $otpService)
    {
        $this->activityLogService = $activityLogService;
        $this->otpService = $otpService;
    }

    /**
     * Get vendor profile.
     */
    public function getProfile(Vendor $vendor): array
    {
        return [
            'success' => true,
            'message' => 'Profile retrieved successfully.',
            'data' => [
                'user' => $this->formatVendor($vendor),
            ],
        ];
    }

    /**
     * Update vendor profile.
     */
    public function updateProfile(Vendor $vendor, array $data, Request $request): array
    {
        $vendor->name = $data['name'];
        $vendor->business_name = $data['business_name'] ?? null;
        $vendor->email = $data['email'] ?? null;
        $vendor->save();

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'profile_updated',
            'Profile updated successfully',
            $request,
            ['fields' => array_keys($data)]
        );

        return [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => [
                'user' => $this->formatVendor($vendor),
            ],
        ];
    }

    /**
     * Request OTP to change PIN.
     */
    public function requestPinChange(Vendor $vendor, Request $request): array
    {
        // Send OTP
        $this->otpService->generate($vendor->phone, 'pin_change');

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'pin_change_requested',
            'Requested OTP for PIN change',
            $request
        );

        return [
            'success' => true,
            'message' => 'OTP sent to your phone.',
            'data' => ['expires_in' => 300],
        ];
    }

    /**
     * Change vendor PIN with OTP verification.
     */
    public function changePin(Vendor $vendor, string $otp, string $newPin, Request $request): array
    {
        // Verify OTP
        if (!$this->otpService->verify($vendor->phone, $otp, 'pin_change')) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Update PIN
        $vendor->setPin($newPin);
        $vendor->save();

        // Revoke all existing tokens
        $vendor->tokens()->delete();

        // Create new token
        $token = $vendor->createToken('vendor-app')->plainTextToken;

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'pin_changed',
            'PIN changed successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'PIN changed successfully.',
            'data' => [
                'user' => $this->formatVendor($vendor),
                'token' => $token,
            ],
        ];
    }

    /**
     * Format vendor for API response.
     */
    protected function formatVendor(Vendor $vendor): array
    {
        return [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'business_name' => $vendor->business_name,
            'phone' => $vendor->phone,
            'email' => $vendor->email,
            'is_phone_verified' => $vendor->is_phone_verified,
            'is_active' => $vendor->is_active,
        ];
    }
}
