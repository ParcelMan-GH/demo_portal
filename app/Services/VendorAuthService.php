<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\OtpCode;
use App\Models\Vendor;
use Illuminate\Http\Request;

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
     * Send OTP to phone number.
     * Works for both existing vendors (login) and new users (registration).
     */
    public function sendOtp(string $phone, Request $request): array
    {
        $phone = PhoneHelper::format($phone);

        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        // Check rate limit
        $waitSeconds = $this->otpService->getSecondsUntilCanResend($phone, 'login');
        if ($waitSeconds > 0) {
            return [
                'success' => false,
                'message' => 'Please wait before requesting another OTP.',
                'data' => ['retry_after' => $waitSeconds],
            ];
        }

        // Always send OTP regardless of whether vendor exists, but report provider failures.
        $code = $this->otpService->generate($phone, 'login');

        if (!$code) {
            return [
                'success' => false,
                'message' => 'Unable to send OTP right now. Please try again shortly.',
            ];
        }

        // Log activity if vendor exists
        $vendor = Vendor::where('phone', $phone)->first();
        if ($vendor) {
            $this->activityLogService->log(
                $vendor->id,
                'login_otp_requested',
                'Login OTP requested',
                $request,
                ['phone' => $phone]
            );
        }

        return [
            'success' => true,
            'message' => 'OTP sent successfully.',
            'data' => ['expires_in' => 300],
        ];
    }

    /**
     * Verify OTP and check if vendor exists.
     * Returns user_exists: true with user+token, or user_exists: false.
     */
    public function verifyPhone(string $phone, string $otp, Request $request): array
    {
        $phone = PhoneHelper::format($phone);
        if (!$phone) {
            return [
                'success' => false,
                'message' => 'Invalid phone number.',
            ];
        }

        // Verify OTP
        if (!$this->otpService->verify($phone, $otp, 'login')) {
            return [
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ];
        }

        // Check if vendor exists and is active
        $vendor = Vendor::where('phone', $phone)
            ->where('is_active', true)
            ->first();

        if ($vendor) {
            // Save FCM token if provided at login time (web portal sends it during verify-phone)
            if (!empty($request->input('fcm_token'))) {
                $vendor->update(['fcm_token' => $request->input('fcm_token')]);
            }

            $token = $vendor->createToken('vendor-app')->plainTextToken;

            $this->activityLogService->log(
                $vendor->id,
                'login',
                'Vendor logged in via OTP',
                $request
            );

            return [
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user_exists' => true,
                    'user' => $this->formatVendor($vendor),
                    'token' => $token,
                ],
            ];
        }

        // No vendor found - phone verified but no account
        return [
            'success' => true,
            'message' => 'Phone verified. Please complete registration.',
            'data' => [
                'user_exists' => false,
            ],
        ];
    }

    /**
     * Register a new vendor.
     * Requires a recently verified OTP (within 10 minutes).
     */
    public function register(array $data, Request $request): array
    {
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

        // Check for recently verified OTP (10 minute window)
        $recentVerified = OtpCode::where('phone', $phone)
            ->where('purpose', 'login')
            ->whereNotNull('verified_at')
            ->where('verified_at', '>=', now()->subMinutes(10))
            ->exists();

        if (!$recentVerified) {
            return [
                'success' => false,
                'message' => 'Phone verification expired. Please verify your phone again.',
            ];
        }

        // Create vendor (phone already verified via OTP)
        $vendor = Vendor::create([
            'name' => $data['name'],
            'business_name' => $data['business_name'] ?? null,
            'phone' => $phone,
            'email' => $data['email'] ?? null,
            'is_active' => true,
        ]);

        // Save FCM token if provided at registration time
        if (!empty($data['fcm_token'])) {
            $vendor->update(['fcm_token' => $data['fcm_token']]);
        }

        $token = $vendor->createToken('vendor-app')->plainTextToken;

        $this->activityLogService->log(
            $vendor->id,
            'register',
            'Vendor registered',
            $request,
            ['phone' => $phone]
        );

        event(new \App\Events\VendorRegistered($vendor));

        return [
            'success' => true,
            'message' => 'Registration successful.',
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
        $vendor->currentAccessToken()->delete();

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
        ];
    }
}
