<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class VendorAuthService
{
    protected OtpService $otpService;
    protected ActivityLogService $activityLogService;

    public function __construct(OtpService $otpService, ActivityLogService $activityLogService)
    {
        $this->otpService = $otpService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Register a new vendor.
     * Returns ['success' => bool, 'message' => string, 'data' => array]
     */
    public function register(array $data, Request $request): array
    {
        // Format phone to international format
        $phone = PhoneHelper::format($data['phone']);
        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Invalid Ghana phone number.',
            ];
        }

        // Check if phone already exists
        if (Vendor::where('phone', $phone)->exists()) {
            return [
                'success' => false,
                'message' => 'This phone is already registered.',
            ];
        }

        // Create vendor (unverified)
        $vendor = Vendor::create([
            'name' => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'pin_hash' => Hash::make($data['pin']),
            'is_phone_verified' => false,
            'is_active' => true,
        ]);

        // Send OTP
        $this->otpService->generate($phone, 'registration');

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'register',
            'Vendor registered, awaiting phone verification',
            $request,
            ['phone' => $phone]
        );

        return [
            'success' => true,
            'message' => 'OTP sent to verify your phone.',
            'data' => ['expires_in' => 300],
        ];
    }

    /**
     * Verify phone with OTP.
     */
    public function verifyPhone(string $phone, string $otp, Request $request): array
    {
        // Format phone to international format
        $phone = PhoneHelper::format($phone);
        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        // Find vendor by phone
        $vendor = Vendor::where('phone', $phone)->first();

        if (!$vendor) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        if ($vendor->is_phone_verified) {
            return [
                'success' => false,
                'message' => 'Phone is already verified.',
            ];
        }

        // Verify OTP
        if (!$this->otpService->verify($phone, $otp, 'registration')) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Mark as verified
        $vendor->is_phone_verified = true;
        $vendor->save();

        // Create token
        $token = $vendor->createToken('vendor-app')->plainTextToken;

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'verify_phone',
            'Phone verified successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'Phone verified successfully.',
            'data' => [
                'user' => $this->formatVendor($vendor),
                'token' => $token,
            ],
        ];
    }

    /**
     * Login with phone and PIN.
     */
    public function login(string $phone, string $pin, Request $request): array
    {
        // Format phone to international format
        $phone = PhoneHelper::format($phone);
        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Invalid phone or PIN.',
            ];
        }

        $vendor = Vendor::where('phone', $phone)->first();

        if (!$vendor || !$vendor->verifyPin($pin)) {
            // Log failed attempt (without vendor_id if vendor not found)
            $this->activityLogService->log(
                $vendor?->id,
                'login_failed',
                'Invalid phone or PIN',
                $request,
                ['phone' => $phone]
            );

            return [
                'success' => false,
                'message' => 'Invalid phone or PIN.',
            ];
        }

        if (!$vendor->is_phone_verified) {
            return [
                'success' => false,
                'message' => 'Please verify your phone first.',
            ];
        }

        if (!$vendor->is_active) {
            return [
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ];
        }

        // Create token
        $token = $vendor->createToken('vendor-app')->plainTextToken;

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'login',
            'Vendor logged in successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $this->formatVendor($vendor),
                'token' => $token,
            ],
        ];
    }

    /**
     * Request forgot PIN OTP.
     */
    public function forgotPin(string $phone, Request $request): array
    {
        // Format phone to international format
        $formattedPhone = PhoneHelper::format($phone);

        // Always return same message to prevent enumeration
        $response = [
            'success' => true,
            'message' => 'If this phone is registered, an OTP has been sent.',
            'data' => ['expires_in' => 300],
        ];

        if (!$formattedPhone) {
            return $response;
        }

        $vendor = Vendor::where('phone', $formattedPhone)
            ->where('is_phone_verified', true)
            ->first();

        if ($vendor) {
            // Send OTP
            $this->otpService->generate($formattedPhone, 'pin_reset');

            // Log activity
            $this->activityLogService->log(
                $vendor->id,
                'forgot_pin',
                'Requested PIN reset',
                $request
            );
        }

        return $response;
    }

    /**
     * Reset PIN with OTP.
     */
    public function resetPin(string $phone, string $otp, string $newPin, Request $request): array
    {
        // Format phone to international format
        $phone = PhoneHelper::format($phone);
        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        $vendor = Vendor::where('phone', $phone)->first();

        if (!$vendor) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        // Verify OTP
        if (!$this->otpService->verify($phone, $otp, 'pin_reset')) {
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
            'reset_pin',
            'PIN reset successfully',
            $request
        );

        return [
            'success' => true,
            'message' => 'PIN reset successful.',
            'data' => [
                'user' => $this->formatVendor($vendor),
                'token' => $token,
            ],
        ];
    }

    /**
     * Logout vendor.
     */
    public function logout(Vendor $vendor, Request $request): array
    {
        // Revoke current token
        $vendor->currentAccessToken()->delete();

        // Log activity
        $this->activityLogService->log(
            $vendor->id,
            'logout',
            'Vendor logged out',
            $request
        );

        return [
            'success' => true,
            'message' => 'Logged out successfully.',
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
