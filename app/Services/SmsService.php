<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected const API_URL = 'https://sms.arkesel.com/api/v2/sms/send';

    /**
     * Send an SMS message via Arkesel.
     */
    public function send(string $phone, string $message): bool
    {
        if (!$this->isEnabled()) {
            Log::warning('SMS skipped because SMS notifications are disabled', [
                'phone' => $this->formatPhoneNumber($phone),
            ]);

            return false;
        }

        if ($this->getProvider() !== 'arkesel') {
            Log::error('SMS provider is not supported for runtime sends', [
                'provider' => $this->getProvider(),
            ]);

            return false;
        }

        $apiKey = $this->getApiKey();
        $senderId = $this->getSenderId();

        if (!$apiKey || !$senderId) {
            Log::error('SMS: Arkesel API key or Sender ID not configured');
            return false;
        }

        // Format phone number (ensure it has country code)
        $formattedPhone = $this->formatPhoneNumber($phone);

        try {
            $response = Http::withHeaders([
                'api-key' => $apiKey,
            ])->post(self::API_URL, [
                'sender' => $senderId,
                'message' => $message,
                'recipients' => [$formattedPhone],
            ]);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $formattedPhone,
                    'provider' => $this->getProvider(),
                    'sender' => $senderId,
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('SMS failed', [
                'phone' => $formattedPhone,
                'provider' => $this->getProvider(),
                'sender' => $senderId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('SMS exception', [
                'phone' => $formattedPhone,
                'provider' => $this->getProvider(),
                'sender' => $senderId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    protected function isEnabled(): bool
    {
        $value = PlatformSetting::getValue('sms_enabled', true);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    protected function getProvider(): string
    {
        return strtolower(trim((string) PlatformSetting::getValue('sms_provider', 'arkesel')));
    }

    /**
     * Get the Arkesel API key from platform settings.
     */
    protected function getApiKey(): ?string
    {
        return PlatformSetting::getValue('arkesel_api_key');
    }

    /**
     * Get the sender ID from platform settings.
     */
    protected function getSenderId(): ?string
    {
        $senderId = PlatformSetting::getValue('sms_sender_id');

        if (filled($senderId)) {
            return (string) $senderId;
        }

        return PlatformSetting::getValue('arkesel_sender_id', 'SHAXI');
    }

    /**
     * Format phone number for Ghana.
     * Converts 0244123456 to 233244123456
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any spaces or dashes
        $phone = preg_replace('/[\s\-]/', '', $phone);

        // If starts with 0, replace with 233
        if (str_starts_with($phone, '0')) {
            return '233' . substr($phone, 1);
        }

        // If starts with +233, remove the +
        if (str_starts_with($phone, '+233')) {
            return substr($phone, 1);
        }

        // If already starts with 233, return as is
        if (str_starts_with($phone, '233')) {
            return $phone;
        }

        // Otherwise, assume it needs 233 prefix
        return '233' . $phone;
    }
}
