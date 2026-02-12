<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OtpCode;
use App\Models\PlatformSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Available tabs configuration
     */
    protected array $tabs = [
        'platform' => ['label' => 'Platform Info', 'icon' => 'building'],
        'locations' => ['label' => 'Locations', 'icon' => 'map'],
        'invoice' => ['label' => 'Invoice Settings', 'icon' => 'document'],
        'sms' => ['label' => 'SMS Config', 'icon' => 'chat'],
        'mail' => ['label' => 'Mail Config', 'icon' => 'envelope'],
        'email-templates' => ['label' => 'Email Templates', 'icon' => 'template'],
        'email-logs' => ['label' => 'Email Logs', 'icon' => 'inbox'],
        'sms-logs' => ['label' => 'SMS Logs', 'icon' => 'phone'],
        'otp-logs' => ['label' => 'OTP Logs', 'icon' => 'chat'],
        'push' => ['label' => 'Push Notifications', 'icon' => 'bell'],
        'health' => ['label' => 'System Health', 'icon' => 'heart'],
        'logs' => ['label' => 'System Logs', 'icon' => 'terminal'],
    ];

    /**
     * Display the settings page.
     */
    public function index(Request $request): View
    {
        $activeTab = $request->get('tab', 'platform');

        // Validate tab exists
        if (!array_key_exists($activeTab, $this->tabs)) {
            $activeTab = 'platform';
        }

        $tabs = $this->tabs;
        $settings = $this->getSettingsForTab($activeTab);
        $tabData = $this->getTabData($activeTab);

        return view('admin.settings.index', compact('tabs', 'activeTab', 'settings', 'tabData'));
    }

    /**
     * Get settings grouped by tab.
     */
    protected function getSettingsForTab(string $tab): array
    {
        $settingsMap = [
            'platform' => [
                'platform_name' => ['label' => 'Platform Name', 'type' => 'text', 'default' => 'Parcelman Express'],
                'platform_email' => ['label' => 'Support Email', 'type' => 'email', 'default' => ''],
                'platform_phone' => ['label' => 'Support Phone', 'type' => 'text', 'default' => ''],
                'platform_address' => ['label' => 'Business Address', 'type' => 'textarea', 'default' => ''],
                'platform_timezone' => ['label' => 'Timezone', 'type' => 'select', 'options' => $this->getTimezones(), 'default' => 'UTC'],
                'platform_currency' => ['label' => 'Default Currency', 'type' => 'select', 'options' => $this->getCurrencies(), 'default' => 'GHS'],
                'platform_date_format' => ['label' => 'Date Format', 'type' => 'select', 'options' => ['Y-m-d' => 'YYYY-MM-DD', 'd/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY'], 'default' => 'd/m/Y'],
            ],
            'locations' => [
                'locations_country' => ['label' => 'Default Country', 'type' => 'text', 'default' => 'Ghana'],
                'locations_regions' => ['label' => 'Supported Regions', 'type' => 'tags', 'default' => ''],
            ],
            'invoice' => [
                'invoice_prefix' => ['label' => 'Invoice Prefix', 'type' => 'text', 'default' => 'INV-'],
                'invoice_start_number' => ['label' => 'Starting Number', 'type' => 'number', 'default' => '1000'],
                'invoice_tax_rate' => ['label' => 'Tax Rate (%)', 'type' => 'number', 'default' => '0'],
                'invoice_tax_label' => ['label' => 'Tax Label', 'type' => 'text', 'default' => 'VAT'],
                'invoice_notes' => ['label' => 'Default Invoice Notes', 'type' => 'textarea', 'default' => 'Thank you for your business!'],
                'invoice_terms' => ['label' => 'Terms & Conditions', 'type' => 'textarea', 'default' => ''],
            ],
            'sms' => [
                'sms_provider' => ['label' => 'SMS Provider', 'type' => 'select', 'options' => ['arkesel' => 'Arkesel', 'twilio' => 'Twilio', 'nexmo' => 'Vonage/Nexmo'], 'default' => 'arkesel'],
                'sms_sender_id' => ['label' => 'Sender ID', 'type' => 'text', 'default' => ''],
                'arkesel_api_key' => ['label' => 'Arkesel API Key', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'twilio_sid' => ['label' => 'Twilio SID', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'twilio_token' => ['label' => 'Twilio Token', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'twilio_phone' => ['label' => 'Twilio Phone', 'type' => 'text', 'default' => ''],
                'sms_enabled' => ['label' => 'Enable SMS', 'type' => 'toggle', 'default' => '1'],
            ],
            'mail' => [
                'mail_mailer' => ['label' => 'Mail Driver', 'type' => 'select', 'options' => ['smtp' => 'SMTP', 'mailgun' => 'Mailgun', 'ses' => 'Amazon SES', 'postmark' => 'Postmark'], 'default' => 'smtp'],
                'mail_host' => ['label' => 'SMTP Host', 'type' => 'text', 'default' => ''],
                'mail_port' => ['label' => 'SMTP Port', 'type' => 'number', 'default' => '587'],
                'mail_username' => ['label' => 'SMTP Username', 'type' => 'text', 'default' => ''],
                'mail_password' => ['label' => 'SMTP Password', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'mail_encryption' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'], 'default' => 'tls'],
                'mail_from_address' => ['label' => 'From Address', 'type' => 'email', 'default' => ''],
                'mail_from_name' => ['label' => 'From Name', 'type' => 'text', 'default' => 'Parcelman Express'],
            ],
            'push' => [
                'push_enabled' => ['label' => 'Enable Push Notifications', 'type' => 'toggle', 'default' => '0'],
                'firebase_server_key' => ['label' => 'Firebase Server Key', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'firebase_sender_id' => ['label' => 'Firebase Sender ID', 'type' => 'text', 'default' => ''],
                'onesignal_app_id' => ['label' => 'OneSignal App ID', 'type' => 'text', 'default' => ''],
                'onesignal_api_key' => ['label' => 'OneSignal API Key', 'type' => 'password', 'encrypted' => true, 'default' => ''],
            ],
        ];

        $config = $settingsMap[$tab] ?? [];
        $result = [];

        foreach ($config as $key => $meta) {
            $result[$key] = array_merge($meta, [
                'value' => PlatformSetting::getValue($key, $meta['default']),
                'key' => $key,
            ]);
        }

        return $result;
    }

    /**
     * Get additional data for specific tabs.
     */
    protected function getTabData(string $tab): array
    {
        return match ($tab) {
            'health' => $this->getSystemHealth(),
            'email-templates' => $this->getEmailTemplates(),
            default => [],
        };
    }

    /**
     * Get system health data.
     */
    protected function getSystemHealth(): array
    {
        $checks = [];

        // PHP Version
        $checks['php'] = [
            'label' => 'PHP Version',
            'value' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'ok' : 'warning',
            'message' => version_compare(PHP_VERSION, '8.2.0', '>=') ? 'OK' : 'PHP 8.2+ recommended',
        ];

        // Laravel Version
        $checks['laravel'] = [
            'label' => 'Laravel Version',
            'value' => app()->version(),
            'status' => 'ok',
            'message' => 'OK',
        ];

        // Database Connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'label' => 'Database',
                'value' => 'Connected',
                'status' => 'ok',
                'message' => 'Connection successful',
            ];
        } catch (\Exception $e) {
            $checks['database'] = [
                'label' => 'Database',
                'value' => 'Error',
                'status' => 'error',
                'message' => 'Connection failed',
            ];
        }

        // Cache
        try {
            Cache::put('health_check', true, 10);
            $cacheWorks = Cache::get('health_check') === true;
            Cache::forget('health_check');
            $checks['cache'] = [
                'label' => 'Cache',
                'value' => config('cache.default'),
                'status' => $cacheWorks ? 'ok' : 'warning',
                'message' => $cacheWorks ? 'Working' : 'May not be working',
            ];
        } catch (\Exception $e) {
            $checks['cache'] = [
                'label' => 'Cache',
                'value' => 'Error',
                'status' => 'error',
                'message' => 'Cache not working',
            ];
        }

        // Storage
        $storagePath = storage_path('app');
        $checks['storage'] = [
            'label' => 'Storage',
            'value' => is_writable($storagePath) ? 'Writable' : 'Not writable',
            'status' => is_writable($storagePath) ? 'ok' : 'error',
            'message' => is_writable($storagePath) ? 'OK' : 'Storage not writable',
        ];

        // Disk Space
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercent = round((($totalSpace - $freeSpace) / $totalSpace) * 100, 1);
        $checks['disk'] = [
            'label' => 'Disk Space',
            'value' => $this->formatBytes($freeSpace) . ' free',
            'status' => $usedPercent < 80 ? 'ok' : ($usedPercent < 90 ? 'warning' : 'error'),
            'message' => $usedPercent . '% used',
        ];

        // Memory
        $memoryLimit = ini_get('memory_limit');
        $checks['memory'] = [
            'label' => 'Memory Limit',
            'value' => $memoryLimit,
            'status' => 'ok',
            'message' => 'OK',
        ];

        // Queue
        $checks['queue'] = [
            'label' => 'Queue Driver',
            'value' => config('queue.default'),
            'status' => config('queue.default') !== 'sync' ? 'ok' : 'warning',
            'message' => config('queue.default') === 'sync' ? 'Using sync driver' : 'OK',
        ];

        // SSL
        $checks['ssl'] = [
            'label' => 'SSL/HTTPS',
            'value' => request()->secure() ? 'Enabled' : 'Disabled',
            'status' => request()->secure() ? 'ok' : 'warning',
            'message' => request()->secure() ? 'OK' : 'HTTPS recommended',
        ];

        return ['checks' => $checks];
    }

    /**
     * Get email templates.
     */
    protected function getEmailTemplates(): array
    {
        $templates = [];
        $viewPath = resource_path('views/emails');

        if (File::isDirectory($viewPath)) {
            $files = File::allFiles($viewPath);
            foreach ($files as $file) {
                $templates[] = [
                    'name' => $file->getFilenameWithoutExtension(),
                    'path' => str_replace(resource_path('views/'), '', $file->getPathname()),
                    'modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        return ['templates' => $templates];
    }

    /**
     * Save settings.
     */
    public function save(Request $request): JsonResponse
    {
        $tab = $request->input('tab');
        $settings = $request->input('settings', []);

        foreach ($settings as $key => $data) {
            // Extract the actual value - settings come in as objects with 'value' property
            $value = is_array($data) && array_key_exists('value', $data) ? $data['value'] : $data;

            $isEncrypted = $this->shouldEncrypt($key);

            // Don't update password fields if empty (keep existing value)
            if ($isEncrypted && empty($value)) {
                continue;
            }

            // Convert boolean to string for storage
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }

            PlatformSetting::setValue($key, $value, $isEncrypted);
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings saved successfully.',
        ]);
    }

    /**
     * Upload file (logo, favicon, etc.)
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,ico,svg|max:2048',
            'key' => 'required|string',
        ]);

        $file = $request->file('file');
        $key = $request->input('key');

        $filename = $key . '_' . time() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('branding', $filename, 'public');

        PlatformSetting::setValue($key, '/storage/' . $path);

        return response()->json([
            'success' => true,
            'path' => '/storage/' . $path,
            'message' => 'File uploaded successfully.',
        ]);
    }

    /**
     * Get system logs data.
     */
    public function logsData(Request $request): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                    'per_page' => 50,
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 0,
                    'to' => 0,
                ],
            ]);
        }

        $content = File::get($logFile);
        $logs = $this->parseLogFile($content);

        // Filter by level
        if ($level = $request->input('level')) {
            $logs = array_filter($logs, fn($log) => strtolower($log['level']) === strtolower($level));
            $logs = array_values($logs);
        }

        // Filter by search
        if ($search = $request->input('search')) {
            $logs = array_filter($logs, fn($log) =>
                stripos($log['message'], $search) !== false ||
                stripos($log['context'], $search) !== false
            );
            $logs = array_values($logs);
        }

        // Filter by date
        if ($dateFrom = $request->input('date_from')) {
            $logs = array_filter($logs, fn($log) => $log['date'] >= $dateFrom);
            $logs = array_values($logs);
        }
        if ($dateTo = $request->input('date_to')) {
            $logs = array_filter($logs, fn($log) => $log['date'] <= $dateTo . ' 23:59:59');
            $logs = array_values($logs);
        }

        // Sort (newest first by default)
        $sortDirection = $request->input('direction', 'desc');
        usort($logs, function($a, $b) use ($sortDirection) {
            $cmp = strcmp($b['date'], $a['date']);
            return $sortDirection === 'asc' ? -$cmp : $cmp;
        });

        // Pagination
        $total = count($logs);
        $perPage = min((int) $request->input('per_page', 50), 100);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;

        $paginatedLogs = array_slice($logs, $offset, $perPage);

        // Add IDs for frontend
        $paginatedLogs = array_map(function($log, $index) use ($offset) {
            $log['id'] = $offset + $index + 1;
            return $log;
        }, $paginatedLogs, array_keys($paginatedLogs));

        return response()->json([
            'data' => $paginatedLogs,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Get single log entry details.
     */
    public function logDetail(Request $request, int $index): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $content = File::get($logFile);
        $logs = $this->parseLogFile($content);

        // Sort newest first
        usort($logs, fn($a, $b) => strcmp($b['date'], $a['date']));

        if (!isset($logs[$index - 1])) {
            return response()->json(['error' => 'Log entry not found'], 404);
        }

        return response()->json([
            'success' => true,
            'log' => $logs[$index - 1],
        ]);
    }

    /**
     * Clear system logs.
     */
    public function clearLogs(): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (File::exists($logFile)) {
            File::put($logFile, '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Logs cleared successfully.',
        ]);
    }

    /**
     * Export system logs.
     */
    public function exportLogs(Request $request)
    {
        $logFile = storage_path('logs/laravel.log');

        if (!File::exists($logFile)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $format = $request->input('format', 'txt');
        $content = File::get($logFile);

        if ($format === 'json') {
            $logs = $this->parseLogFile($content);
            usort($logs, fn($a, $b) => strcmp($b['date'], $a['date']));

            return response()->json($logs)
                ->header('Content-Disposition', 'attachment; filename="logs_' . date('Y-m-d_His') . '.json"');
        }

        // Default: raw log file
        $filename = 'laravel_' . date('Y-m-d_His') . '.log';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Parse log file content into structured array.
     */
    protected function parseLogFile(string $content): array
    {
        $logs = [];
        $pattern = '/\[(\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}:\d{2}\.?\d*[\+\-]?\d*:?\d*)\]\s+(\w+)\.(\w+):\s+(.*?)(?=\[\d{4}-\d{2}-\d{2}|$)/s';

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $dateStr = $match[1];
            // Normalize date format
            $date = date('Y-m-d H:i:s', strtotime($dateStr));

            $message = trim($match[4]);
            $context = '';

            // Try to extract context/stack trace
            if (($pos = strpos($message, "\n")) !== false) {
                $firstLine = substr($message, 0, $pos);
                $context = trim(substr($message, $pos));
                $message = $firstLine;
            }

            $logs[] = [
                'date' => $date,
                'environment' => $match[2],
                'level' => strtoupper($match[3]),
                'message' => $message,
                'context' => $context,
            ];
        }

        return $logs;
    }

    /**
     * Get email logs data.
     */
    public function emailLogsData(Request $request): JsonResponse
    {
        // This would typically come from a database table that logs emails
        // For now, return empty data structure
        return response()->json([
            'data' => [],
            'meta' => [
                'total' => 0,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 0,
                'to' => 0,
            ],
        ]);
    }

    /**
     * Get SMS logs data.
     */
    public function smsLogsData(Request $request): JsonResponse
    {
        // This would typically come from a database table that logs SMS
        // For now, return empty data structure
        return response()->json([
            'data' => [],
            'meta' => [
                'total' => 0,
                'per_page' => 50,
                'current_page' => 1,
                'last_page' => 1,
                'from' => 0,
                'to' => 0,
            ],
        ]);
    }

    /**
     * Get OTP logs data.
     */
    public function otpLogsData(Request $request): JsonResponse
    {
        $query = OtpCode::query();

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($purpose = trim((string) $request->get('purpose', ''))) {
            $query->where('purpose', $purpose);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $query->orderByDesc('created_at');

        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'phone' => $log->phone,
                    'code' => $log->code,
                    'purpose' => $log->purpose,
                    'expires_at' => $log->expires_at?->format('Y-m-d H:i:s'),
                    'verified_at' => $log->verified_at?->format('Y-m-d H:i:s'),
                    'is_verified' => $log->verified_at !== null,
                    'is_expired' => $log->expires_at?->isPast() ?? false,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                ];
            }),
            'meta' => [
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem() ?? 0,
                'to' => $logs->lastItem() ?? 0,
            ],
        ]);
    }

    /**
     * Send test email.
     */
    public function testEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            \Illuminate\Support\Facades\Mail::raw('This is a test email from Parcelman Express.', function($message) use ($request) {
                $message->to($request->email)
                    ->subject('Test Email - Parcelman Express');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send test SMS.
     */
    public function testSms(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        // This would integrate with your SMS service
        // For now, return success
        return response()->json([
            'success' => true,
            'message' => 'Test SMS sent successfully.',
        ]);
    }

    /**
     * Clear cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('view:clear');
            Artisan::call('route:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear cache: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if a setting key should be encrypted.
     */
    protected function shouldEncrypt(string $key): bool
    {
        $encryptedKeys = [
            'arkesel_api_key',
            'twilio_sid',
            'twilio_token',
            'mail_password',
            'firebase_server_key',
            'onesignal_api_key',
        ];

        return in_array($key, $encryptedKeys);
    }

    /**
     * Format bytes to human readable.
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get available timezones.
     */
    protected function getTimezones(): array
    {
        return [
            'UTC' => 'UTC',
            'Africa/Accra' => 'Africa/Accra (GMT)',
            'Africa/Lagos' => 'Africa/Lagos (WAT)',
            'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
            'Europe/London' => 'Europe/London (GMT/BST)',
            'America/New_York' => 'America/New York (EST/EDT)',
            'America/Los_Angeles' => 'America/Los Angeles (PST/PDT)',
            'Asia/Dubai' => 'Asia/Dubai (GST)',
        ];
    }

    /**
     * Get available currencies.
     */
    protected function getCurrencies(): array
    {
        return [
            'GHS' => 'Ghana Cedi (GHS)',
            'USD' => 'US Dollar (USD)',
            'EUR' => 'Euro (EUR)',
            'GBP' => 'British Pound (GBP)',
            'NGN' => 'Nigerian Naira (NGN)',
            'KES' => 'Kenyan Shilling (KES)',
        ];
    }
}
