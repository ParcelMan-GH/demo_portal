<?php

namespace App\Services;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;

class OtpService
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
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

        // Generate 6-digit OTP
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP with 5-minute expiry
        OtpCode::create([
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(5),
            'created_at' => now(),
        ]);

        // Send SMS
        $message = "Your Parcelman code is {$code}";
        $sent = $this->smsService->send($phone, $message);

        // Log for development (remove in production or use proper logging)
        Log::info('OTP generated', [
            'phone' => $phone,
            'purpose' => $purpose,
            'code' => $code, // Remove in production!
            'sms_sent' => $sent,
        ]);

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

        if (!$otp) {
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
     * Cleanup expired OTPs.
     * Should be run as a scheduled task.
     */
    public function cleanup(): int
    {
        return OtpCode::where('expires_at', '<', now()->subHour())->delete();
    }
}
