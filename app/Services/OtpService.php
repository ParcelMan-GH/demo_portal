<?php

namespace App\Services;

use App\Models\OtpCode;

class OtpService
{
    protected SmsService $smsService;

    protected AppReviewAccessService $appReviewAccessService;

    public function __construct(
        SmsService $smsService,
        AppReviewAccessService $appReviewAccessService
    ) {
        $this->smsService = $smsService;
        $this->appReviewAccessService = $appReviewAccessService;
    }

    /**
     * Generate and send OTP to phone number.
     * Returns the OTP code (for development/testing) or null on failure.
     */
    public function generate(string $phone, string $purpose): ?string
    {
        // Invalidate any existing OTPs for this phone and purpose
        OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $reviewCode = $this->appReviewAccessService->fixedOtpFor($phone, $purpose);

        // Review access still uses a normal expiring OTP record. Only the
        // configured review phone skips the external SMS provider.
        $code = $reviewCode
            ?? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP with 5-minute expiry
        $otp = OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        // Send SMS
        $sent = $reviewCode !== null;
        if (! $sent) {
            $message = "Your Parcelman code is {$code}";
            $sent = $this->smsService->send($phone, $message);
        }

        if (! $sent) {
            $otp->delete();

            return null;
        }

        return $code;
    }

    /**
     * Verify OTP code.
     * Returns true if valid, false otherwise.
     */
    public function verify(string $phone, string $code, string $purpose): bool
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('code', $code)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->first();

        if (! $otp) {
            return false;
        }

        if ($otp->isExpired()) {
            return false;
        }

        // Mark as verified
        $otp->markVerified();

        return true;
    }

    /**
     * Check if there's a pending (unverified, not expired) OTP for this phone.
     */
    public function hasPendingOtp(string $phone, string $purpose): bool
    {
        return OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Get seconds until user can request a new OTP.
     * Returns 0 if they can resend now, otherwise returns seconds to wait.
     */
    public function getSecondsUntilCanResend(string $phone, string $purpose): int
    {
        $otp = OtpCode::where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest('created_at')
            ->first();

        if (! $otp) {
            return 0;
        }

        // Allow resend after 60 seconds from creation
        $canResendAt = $otp->created_at->addSeconds(60);

        if (now()->gte($canResendAt)) {
            return 0;
        }

        return (int) now()->diffInSeconds($canResendAt);
    }

    /**
     * Cleanup expired OTPs.
     * Should be run as a scheduled task.
     */
    public function cleanup(): int
    {
        return OtpCode::where('expires_at', '<', now()->subHour())->delete();
    }
}
