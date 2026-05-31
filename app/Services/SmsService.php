<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SmsService
{
    protected const API_URL = 'https://sms.arkesel.com/api/v2/sms/send';

    /**
     * Send an SMS message via Arkesel.
     */
    public function send(string $phone, string $message): bool
    {
        $formattedPhone = $this->formatPhoneNumber($phone);
        $provider = $this->getProvider();
        $senderId = $this->getSenderId();

        if (!$this->isEnabled()) {
            Log::warning('SMS skipped because SMS notifications are disabled', [
                'phone' => $formattedPhone,
            ]);
            $this->recordLog($formattedPhone, $message, 'failed', $provider, $senderId, null, null, 'SMS notifications are disabled');

            return false;
        }

        if ($provider !== 'arkesel') {
            Log::error('SMS provider is not supported for runtime sends', [
                'provider' => $provider,
            ]);
            $this->recordLog($formattedPhone, $message, 'failed', $provider, $senderId, null, null, 'SMS provider is not supported for runtime sends');

            return false;
        }

        $apiKey = $this->getApiKey();

        if (!$apiKey || !$senderId) {
            Log::error('SMS: Arkesel API key or Sender ID not configured');
            $this->recordLog($formattedPhone, $message, 'failed', $provider, $senderId, null, null, 'Arkesel API key or Sender ID not configured');
            return false;
        }

        $smsLog = $this->recordLog($formattedPhone, $message, 'pending', $provider, $senderId);

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
                    'provider' => $provider,
                    'sender' => $senderId,
                    'response' => $response->json(),
                ]);
                $this->updateLog($smsLog, 'sent', $response->status(), $response->json(), null);
                return true;
            }

            Log::error('SMS failed', [
                'phone' => $formattedPhone,
                'provider' => $provider,
                'sender' => $senderId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            $this->updateLog($smsLog, 'failed', $response->status(), $this->responsePayload($response), $response->body());
            return false;

        } catch (\Exception $e) {
            Log::error('SMS exception', [
                'phone' => $formattedPhone,
                'provider' => $provider,
                'sender' => $senderId,
                'error' => $e->getMessage(),
            ]);
            $this->updateLog($smsLog, 'failed', null, null, $e->getMessage());
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

    protected function recordLog(
        string $recipient,
        string $message,
        string $status,
        ?string $provider = null,
        ?string $sender = null,
        ?int $statusCode = null,
        ?array $response = null,
        ?string $error = null,
    ): ?SmsLog {
        try {
            if (!Schema::hasTable('sms_logs')) {
                return null;
            }

            return SmsLog::create([
                'recipient' => $recipient,
                'provider' => $provider,
                'sender' => $sender,
                'message' => $message,
                'status' => $status,
                'status_code' => $statusCode,
                'response' => $response,
                'error' => $error,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to record SMS log', [
                'recipient' => $recipient,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected function updateLog(?SmsLog $log, string $status, ?int $statusCode = null, ?array $response = null, ?string $error = null): void
    {
        if (!$log) {
            return;
        }

        try {
            $log->update([
                'status' => $status,
                'status_code' => $statusCode,
                'response' => $response,
                'error' => $error,
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Unable to update SMS log', [
                'sms_log_id' => $log->id,
                'status' => $status,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function responsePayload($response): ?array
    {
        try {
            $json = $response->json();

            return is_array($json) ? $json : ['body' => $response->body()];
        } catch (\Throwable) {
            return ['body' => $response->body()];
        }
    }
}
