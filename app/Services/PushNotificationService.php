<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    protected const FCM_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    protected const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    // ---------------------------------------------------------------
    // Public send helpers
    // ---------------------------------------------------------------

    public function sendToVendor(Vendor $vendor, string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        if (!$vendor->fcm_token) {
            return false;
        }
        return $this->send($vendor->fcm_token, $title, $body, $data, 'App\Models\Vendor', $vendor->id, $type);
    }

    public function sendToDriver(Driver $driver, string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        if (!$driver->fcm_token) {
            return false;
        }
        return $this->send($driver->fcm_token, $title, $body, $data, 'App\Models\Driver', $driver->id, $type);
    }

    public function sendToAdmin(User $user, string $title, string $body, array $data = [], string $type = 'general'): bool
    {
        if (!$user->fcm_token) {
            return false;
        }
        return $this->send($user->fcm_token, $title, $body, $data, 'App\Models\User', $user->id, $type);
    }

    /**
     * Send to all users (portal admins) that have an FCM token.
     */
    public function sendToAllAdmins(string $title, string $body, array $data = [], string $type = 'general'): int
    {
        $users = User::whereNotNull('fcm_token')->get();
        $count = 0;

        foreach ($users as $user) {
            if ($this->sendToAdmin($user, $title, $body, $data, $type)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Send to users assigned to warehouse roles.
     */
    public function sendToWarehouseAdmins(string $title, string $body, array $data = [], string $type = 'general'): int
    {
        $users = User::whereNotNull('fcm_token')
            ->whereHas('roles', function ($q) {
                $q->where('is_warehouse_role', true);
            })
            ->get()
            ->unique('id');

        $count = 0;

        foreach ($users as $user) {
            if ($this->sendToAdmin($user, $title, $body, $data, $type)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Send to HQ warehouse users only.
     */
    public function sendToSuperAdmins(string $title, string $body, array $data = [], string $type = 'general'): int
    {
        $users = User::whereNotNull('fcm_token')
            ->whereHas('warehouse', fn ($q) => $q
                ->where('is_hq', true)
                ->where('can_administer_system', true))
            ->get();

        $count = 0;

        foreach ($users as $user) {
            if ($this->sendToAdmin($user, $title, $body, $data, $type)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Send to all users (warehouse managers/staff) assigned to a specific warehouse.
     */
    public function sendToWarehouseManagers(Warehouse $warehouse, string $title, string $body, array $data = [], string $type = 'general'): int
    {
        $users = $warehouse->users()
            ->whereNotNull('fcm_token')
            ->whereHas('roles', fn($q) => $q->where('is_warehouse_role', true))
            ->get();

        $count = 0;

        foreach ($users as $user) {
            if ($this->sendToAdmin($user, $title, $body, $data, $type)) {
                $count++;
            }
        }

        return $count;
    }

    // ---------------------------------------------------------------
    // Core send logic (FCM v1 API)
    // ---------------------------------------------------------------

    protected function send(
        string $token,
        string $title,
        string $body,
        array $data,
        string $notifiableType,
        int $notifiableId,
        string $type
    ): bool {
        if (!$this->isPushEnabled()) {
            $this->log($notifiableType, $notifiableId, $type, $title, $body, $data, 'failed', 'Push notifications disabled.');
            return false;
        }

        $projectId = $this->getProjectId();
        if (!$projectId) {
            Log::warning('PushNotificationService: Firebase project ID not configured.');
            $this->log($notifiableType, $notifiableId, $type, $title, $body, $data, 'failed', 'Firebase credentials not configured.');
            return false;
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::warning('PushNotificationService: Could not obtain FCM access token.');
            $this->log($notifiableType, $notifiableId, $type, $title, $body, $data, 'failed', 'Failed to obtain FCM access token.');
            return false;
        }

        // All data values must be strings for FCM
        $stringData = array_merge(
            array_map('strval', $data),
            ['type' => $type]
        );

        $payload = [
            'message' => [
                'token'        => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data'         => $stringData,
                'webpush'      => [
                    'notification' => [
                        'icon'  => '/favicon.jpg',
                        'badge' => '/favicon.jpg',
                        'click_action' => '/',
                    ],
                ],
            ],
        ];

        $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

        try {
            $response = Http::withToken($accessToken)
                ->post($url, $payload);

            $success = $response->successful();

            $this->log(
                $notifiableType, $notifiableId, $type, $title, $body, $data,
                $success ? 'sent' : 'failed',
                $success ? null : ($response->json('error.message') ?? 'Unknown FCM v1 error')
            );

            if (!$success) {
                Log::warning('PushNotificationService: FCM v1 send failed.', [
                    'status'   => $response->status(),
                    'response' => $response->body(),
                ]);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('PushNotificationService: Exception during FCM v1 send.', ['error' => $e->getMessage()]);
            $this->log($notifiableType, $notifiableId, $type, $title, $body, $data, 'failed', $e->getMessage());
            return false;
        }
    }

    // ---------------------------------------------------------------
    // Credential & token helpers
    // ---------------------------------------------------------------

    protected function isPushEnabled(): bool
    {
        return (bool) PlatformSetting::getValue('push_notifications_enabled', false);
    }

    protected function getCredentials(): ?array
    {
        $path = storage_path('app/firebase/firestore.json');
        if (!file_exists($path)) {
            return null;
        }

        $json = json_decode(file_get_contents($path), true);

        if (!$json || !isset($json['project_id'], $json['private_key'], $json['client_email'])) {
            return null;
        }

        return $json;
    }

    protected function getProjectId(): ?string
    {
        $credentials = $this->getCredentials();
        return $credentials['project_id'] ?? null;
    }

    /**
     * Get a valid OAuth2 access token for FCM, cached for 55 minutes.
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('fcm_access_token', 55 * 60, function () {
            return $this->fetchAccessToken();
        });
    }

    /**
     * Exchange a signed JWT for a Google OAuth2 access token.
     */
    protected function fetchAccessToken(): ?string
    {
        $credentials = $this->getCredentials();
        if (!$credentials) {
            return null;
        }

        $jwt = $this->buildJwt($credentials);
        if (!$jwt) {
            return null;
        }

        try {
            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]);

            if (!$response->successful()) {
                Log::error('PushNotificationService: OAuth2 token exchange failed.', ['body' => $response->body()]);
                return null;
            }

            return $response->json('access_token');
        } catch (\Throwable $e) {
            Log::error('PushNotificationService: Exception during token exchange.', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Build an RS256-signed JWT for the Google OAuth2 token exchange.
     */
    protected function buildJwt(array $credentials): ?string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss'   => $credentials['client_email'],
            'sub'   => $credentials['client_email'],
            'aud'   => self::TOKEN_URL,
            'scope' => self::FCM_SCOPE,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $data = "{$header}.{$payload}";

        $privateKey = openssl_pkey_get_private($credentials['private_key']);
        if (!$privateKey) {
            Log::error('PushNotificationService: Failed to load private key from service account.');
            return null;
        }

        $signature = '';
        if (!openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            Log::error('PushNotificationService: Failed to sign JWT.');
            return null;
        }

        return "{$data}." . $this->base64UrlEncode($signature);
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // ---------------------------------------------------------------
    // Logging
    // ---------------------------------------------------------------

    protected function log(
        string $notifiableType,
        int $notifiableId,
        string $type,
        string $title,
        string $body,
        array $data,
        string $status,
        ?string $error = null
    ): void {
        try {
            NotificationLog::create([
                'notifiable_type' => $notifiableType,
                'notifiable_id'   => $notifiableId,
                'type'            => $type,
                'channel'         => 'push',
                'title'           => $title,
                'body'            => $body,
                'data'            => $data ?: null,
                'status'          => $status,
                'error'           => $error,
            ]);
        } catch (\Throwable $e) {
            Log::error('PushNotificationService: Failed to write notification log.', ['error' => $e->getMessage()]);
        }
    }
}
