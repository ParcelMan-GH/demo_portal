<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use RuntimeException;

class AppReviewAccessService
{
    /**
     * Return a fixed OTP only for the explicitly configured review vendor.
     */
    public function fixedOtpFor(string $phone, string $purpose): ?string
    {
        if ($purpose !== 'login') {
            return null;
        }

        try {
            $credentials = $this->credentials();
        } catch (RuntimeException) {
            return null;
        }

        return PhoneHelper::format($phone) === $credentials['vendor_phone']
            ? $credentials['vendor_otp']
            : null;
    }

    /**
     * Resolve and validate review credentials without exposing them externally.
     *
     * @return array{vendor_phone: string, vendor_otp: string, rider_phone: string, rider_password: string}
     */
    public function credentials(): array
    {
        if (! config('app_review.enabled', false)) {
            throw new RuntimeException('App Review access is disabled.');
        }

        $vendorPhone = PhoneHelper::format((string) config('app_review.vendor_phone'));
        $vendorOtp = trim((string) config('app_review.vendor_otp'));
        $riderPhone = PhoneHelper::format((string) config('app_review.rider_phone'));
        $riderPassword = (string) config('app_review.rider_password');

        if (! $vendorPhone || ! $riderPhone) {
            throw new RuntimeException('App Review phone numbers must be valid Ghana phone numbers.');
        }

        if ($vendorPhone === $riderPhone) {
            throw new RuntimeException('App Review vendor and rider phone numbers must be different.');
        }

        if (! preg_match('/^\d{6}$/', $vendorOtp)) {
            throw new RuntimeException('App Review vendor OTP must contain exactly six digits.');
        }

        if (strlen($riderPassword) < 12) {
            throw new RuntimeException('App Review rider password must contain at least 12 characters.');
        }

        return [
            'vendor_phone' => $vendorPhone,
            'vendor_otp' => $vendorOtp,
            'rider_phone' => $riderPhone,
            'rider_password' => $riderPassword,
        ];
    }
}
