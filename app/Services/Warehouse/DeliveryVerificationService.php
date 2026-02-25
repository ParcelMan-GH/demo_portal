<?php

namespace App\Services\Warehouse;

use App\Models\DeliveryRunStop;
use App\Models\DeliveryVerificationAttempt;
use App\Models\Driver;
use App\Models\OtpCode;
use App\Services\SmsService;
use Illuminate\Support\Facades\Hash;

class DeliveryVerificationService
{
    public function __construct(
        private SmsService $smsService
    ) {
    }

    public function issueCode(DeliveryRunStop $stop, string $runNumber): array
    {
        $plainCode = $this->generateCode();
        $sentAt = now();

        $stop->update([
            'verification_code_hash' => Hash::make($plainCode),
            'verification_code_expires_at' => $sentAt->copy()->addDay(),
            'verification_code_sent_at' => $sentAt,
            'verification_attempts' => 0,
        ]);

        $message = sprintf(
            'Parcelman delivery code for run %s: %s. Share this code with the driver to confirm delivery. Expires in 24 hours.',
            $runNumber,
            $plainCode
        );

        $sent = $this->smsService->send((string) $stop->recipient_phone, $message);

        OtpCode::create([
            'phone' => (string) $stop->recipient_phone,
            'code' => $plainCode,
            'purpose' => 'delivery_verification',
            'expires_at' => $sentAt->copy()->addDay(),
            'verified_at' => null,
            'created_at' => $sentAt,
        ]);

        return [
            'success' => true,
            'message' => $sent ? 'Verification code sent successfully.' : 'Verification code generated, but SMS sending failed.',
            'data' => [
                'sent' => $sent,
                'expires_at' => $stop->fresh()->verification_code_expires_at?->toIso8601String(),
                'remaining_attempts' => max(((int) $stop->max_attempts) - ((int) $stop->verification_attempts), 0),
            ],
        ];
    }

    public function verifyCode(
        DeliveryRunStop $stop,
        string $code,
        ?Driver $driver = null,
        ?string $ipAddress = null
    ): array {
        if ($stop->status === DeliveryRunStop::STATUS_DELIVERED) {
            return [
                'success' => false,
                'message' => 'This stop is already delivered.',
            ];
        }

        if (blank($stop->verification_code_hash) || blank($stop->verification_code_expires_at)) {
            return [
                'success' => false,
                'message' => 'Verification code is not available for this stop.',
            ];
        }

        if ((int) $stop->verification_attempts >= (int) $stop->max_attempts) {
            return [
                'success' => false,
                'message' => 'Verification code attempts exceeded. Ask warehouse manager to regenerate.',
                'locked' => true,
            ];
        }

        if (now()->greaterThan($stop->verification_code_expires_at)) {
            return [
                'success' => false,
                'message' => 'Verification code has expired. Ask warehouse manager to regenerate.',
            ];
        }

        $isValid = Hash::check($code, (string) $stop->verification_code_hash);

        DeliveryVerificationAttempt::query()->create([
            'delivery_run_stop_id' => $stop->id,
            'entered_code_masked' => $this->maskCode($code),
            'is_success' => $isValid,
            'driver_id' => $driver?->id,
            'attempted_at' => now(),
            'ip_address' => $ipAddress,
        ]);

        if (!$isValid) {
            $stop->increment('verification_attempts');
            $stop->refresh();

            $remainingAttempts = max((int) $stop->max_attempts - (int) $stop->verification_attempts, 0);

            return [
                'success' => false,
                'message' => $remainingAttempts > 0
                    ? "Invalid verification code. {$remainingAttempts} attempt(s) remaining."
                    : 'Verification code attempts exceeded. Ask warehouse manager to regenerate.',
                'remaining_attempts' => $remainingAttempts,
                'locked' => $remainingAttempts === 0,
            ];
        }

        OtpCode::where('phone', (string) $stop->recipient_phone)
            ->where('purpose', 'delivery_verification')
            ->where('code', $code)
            ->whereNull('verified_at')
            ->latest('created_at')
            ->first()
            ?->markVerified();

        return [
            'success' => true,
            'message' => 'Verification code accepted.',
            'remaining_attempts' => max((int) $stop->max_attempts - (int) $stop->verification_attempts, 0),
        ];
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function maskCode(string $code): string
    {
        $code = preg_replace('/\D/', '', $code) ?: '';
        if (strlen($code) <= 2) {
            return str_repeat('*', strlen($code));
        }

        return str_repeat('*', strlen($code) - 2) . substr($code, -2);
    }
}

