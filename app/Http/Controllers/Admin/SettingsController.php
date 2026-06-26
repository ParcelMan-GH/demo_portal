<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\BusStation;
use App\Models\DeliveryDelayReason;
use App\Models\DeliveryFailureReason;
use App\Models\EmailTemplate;
use App\Models\OtpCode;
use App\Models\PickupVehicleType;
use App\Models\PlatformSetting;
use App\Models\SmsLog;
use App\Services\BackOfficeAccess;
use App\Services\EmailTemplateService;
use App\Services\MailSettingsService;
use App\Services\SmsService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class SettingsController extends Controller
{
    /**
     * Available tabs configuration
     */
    protected array $tabs = [
        'platform' => ['label' => 'Platform Info', 'icon' => 'building'],
        'sms' => ['label' => 'SMS Config', 'icon' => 'chat'],
        'mail' => ['label' => 'Mail Config', 'icon' => 'envelope'],
        'storage' => ['label' => 'Storage Config', 'icon' => 'storage'],
        'email-templates' => ['label' => 'Email Templates', 'icon' => 'template'],
        'email-logs' => ['label' => 'Email Logs', 'icon' => 'inbox'],
        'sms-logs' => ['label' => 'SMS Logs', 'icon' => 'phone'],
        'otp-logs' => ['label' => 'OTP Logs', 'icon' => 'chat'],
        'admin-audit-logs' => ['label' => 'Admin Audit Logs', 'icon' => 'shield'],
        'notification-logs' => ['label' => 'Notification Logs', 'icon' => 'bell'],
        'delivery' => ['label' => 'Delivery Settings', 'icon' => 'truck'],
        'pickup-vehicles' => ['label' => 'Pickup Vehicles', 'icon' => 'truck'],
        'bus-stations' => ['label' => 'Bus Stations', 'icon' => 'map'],
        'delivery-failure-reasons' => ['label' => 'Delivery Failure Reasons', 'icon' => 'chat'],
        'delivery-delay-reasons' => ['label' => 'Delivery Delay Reasons', 'icon' => 'clock'],
        'pricing' => ['label' => 'Vendor Commissions', 'icon' => 'cash'],
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

        if ($activeTab === 'bus-handoff-reasons') {
            $activeTab = 'delivery-failure-reasons';
        }

        // Validate tab exists
        if (! array_key_exists($activeTab, $this->tabs)) {
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
            'sms' => [
                'sms_provider' => ['label' => 'SMS Provider', 'type' => 'select', 'options' => ['arkesel' => 'Arkesel', 'twilio' => 'Twilio'], 'default' => 'arkesel'],
                'sms_sender_id' => ['label' => 'Sender ID', 'type' => 'text', 'default' => ''],
                'arkesel_api_key' => ['label' => 'Arkesel API Key', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'twilio_sid' => ['label' => 'Twilio SID', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'twilio_token' => ['label' => 'Twilio Token', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'twilio_phone' => ['label' => 'Twilio Phone', 'type' => 'text', 'default' => ''],
                'sms_enabled' => ['label' => 'Enable SMS', 'type' => 'toggle', 'default' => '1'],
            ],
            'mail' => [
                'mail_mailer' => ['label' => 'Mail Driver', 'type' => 'select', 'options' => ['smtp' => 'SMTP', 'mailgun' => 'Mailgun'], 'default' => 'smtp'],
                'mail_host' => ['label' => 'SMTP Host', 'type' => 'text', 'default' => ''],
                'mail_port' => ['label' => 'SMTP Port', 'type' => 'number', 'default' => '587'],
                'mail_username' => ['label' => 'SMTP Username', 'type' => 'text', 'default' => ''],
                'mail_password' => ['label' => 'SMTP Password', 'type' => 'password', 'encrypted' => true, 'default' => ''],
                'mail_encryption' => ['label' => 'Encryption', 'type' => 'select', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', '' => 'None'], 'default' => 'tls'],
                'mail_from_address' => ['label' => 'From Address', 'type' => 'email', 'default' => ''],
                'mail_from_name' => ['label' => 'From Name', 'type' => 'text', 'default' => 'Parcelman Express'],
            ],
            'delivery' => [
                'delivery.allow_skip_verification' => ['label' => 'Allow Riders to Skip OTP Verification', 'type' => 'toggle', 'default' => '0', 'help' => 'When enabled, riders can skip OTP verification during delivery with a reason. The delivery will be flagged for review.'],
                'delivery.show_otp_to_vendor' => ['label' => 'Show OTP Code to Vendor', 'type' => 'toggle', 'default' => '0', 'help' => 'When enabled, vendors can see the delivery OTP code in their shipment details. This allows recipients to call the vendor for the code if SMS fails.'],
                'delivery_eta_grace_minutes' => ['label' => 'ETA Grace Period (minutes)', 'type' => 'number', 'default' => '30', 'help' => 'How long after a package ETA before it is flagged as overdue.'],
                'delivery_no_eta_threshold_hours' => ['label' => 'No ETA Threshold (hours)', 'type' => 'number', 'default' => '4', 'help' => 'How long after dispatch before active packages without ETA are flagged for follow-up.'],
                'contact_queue.auto_queue_on_transport_receive' => ['label' => 'Auto-Queue on Transport Manifest Receive', 'type' => 'toggle', 'default' => '0', 'help' => 'When enabled, items from incoming transport manifests are automatically added to the contact queue when received at the warehouse.'],
                'transport.scan_issue_auto_accept' => ['label' => 'Auto-Accept Transport Scan Issues', 'type' => 'toggle', 'default' => '0', 'help' => 'When enabled, rider scan issue reports with proof photos immediately mark the selected load group or package as loaded. When disabled, admins must review them first.'],
            ],
            'pricing' => [
                'vendor_commission.enabled' => ['label' => 'Enable Vendor Commission', 'type' => 'toggle', 'default' => '0', 'help' => 'When enabled, vendors earn a commission for each package delivered to their recipients. Can be overridden per vendor on their profile.'],
                'vendor_commission.rate_per_package' => ['label' => 'Commission Rate Per Package (GHS)', 'type' => 'number', 'default' => '2.00', 'help' => 'Amount in Ghana Cedis earned by the vendor per delivered package.'],
                'vendor_commission.min_payout' => ['label' => 'Minimum Vendor Payout (GHS)', 'type' => 'number', 'default' => '20.00', 'help' => 'Vendors must accumulate at least this amount before a payout can be processed.'],
            ],
            'push' => [
                'push_notifications_enabled' => ['label' => 'Enable Push Notifications', 'type' => 'toggle', 'default' => '0'],
                'firebase_web_api_key' => ['label' => 'Web API Key', 'type' => 'text', 'default' => ''],
                'firebase_auth_domain' => ['label' => 'Auth Domain', 'type' => 'text', 'default' => ''],
                'firebase_messaging_sender_id' => ['label' => 'Messaging Sender ID', 'type' => 'text', 'default' => ''],
                'firebase_app_id' => ['label' => 'Web App ID', 'type' => 'text', 'default' => ''],
                'firebase_vapid_key' => ['label' => 'VAPID Key', 'type' => 'text', 'default' => ''],
            ],
            'storage' => [
                'storage.driver' => ['label' => 'Storage Driver', 'type' => 'select', 'options' => ['local' => 'Local Public Storage', 's3' => 'S3 / Storj Bucket'], 'default' => 'local', 'help' => 'Choose where uploaded package photos and proof files are stored. Local uses storage/app/public and the /storage link.'],
                'storage.s3.access_key' => ['label' => 'Access Key', 'type' => 'password', 'encrypted' => true, 'default' => '', 'help' => 'Required for S3-compatible signing. A blank key causes malformed signed URLs.'],
                'storage.s3.secret_key' => ['label' => 'Secret Key', 'type' => 'password', 'encrypted' => true, 'default' => '', 'help' => 'Required for uploading and signing private file links.'],
                'storage.s3.bucket' => ['label' => 'Bucket', 'type' => 'text', 'default' => '', 'help' => 'Example: shaxi'],
                'storage.s3.endpoint' => ['label' => 'Endpoint URL', 'type' => 'text', 'default' => '', 'help' => 'Example: https://gateway.storjshare.io'],
                'storage.s3.region' => ['label' => 'Region', 'type' => 'text', 'default' => 'us-east-1', 'help' => 'Storj gateway signing commonly uses us-east-1.'],
                'storage.s3.env' => ['label' => 'Folder Prefix', 'type' => 'text', 'default' => 'demo', 'help' => 'Files are stored under this prefix, for example demo or prod.'],
                'storage.s3.signed_url_expiry' => ['label' => 'S3 Signed URL Expiry (minutes)', 'type' => 'number', 'default' => '60', 'help' => 'How long generated private image links remain valid.'],
            ],
        ];

        $config = $settingsMap[$tab] ?? [];
        $result = [];

        foreach ($config as $key => $meta) {
            $value = PlatformSetting::getValue($key, $meta['default']);
            $isEncrypted = (bool) ($meta['encrypted'] ?? false);

            $result[$key] = array_merge($meta, [
                'value' => $isEncrypted ? '' : $value,
                'key' => $key,
                'configured' => $isEncrypted && filled($value),
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
            'notification-logs' => $this->getNotificationLogsMeta(),
            'pickup-vehicles' => $this->getPickupVehiclesMeta(),
            'bus-stations' => $this->getBusStationsMeta(),
            'delivery-failure-reasons' => $this->getDeliveryFailureReasonsMeta(),
            'delivery-delay-reasons' => $this->getDeliveryDelayReasonsMeta(),
            'storage' => $this->getStorageMeta(),
            default => [],
        };
    }

    protected function getStorageMeta(): array
    {
        $storage = app(StorageService::class);
        $missing = $storage->missingS3ConfigFields();
        $connectionStatus = $storage->connectionStatus();

        return [
            'driver' => PlatformSetting::getValue('storage.driver', 'local'),
            's3_configured' => empty($missing),
            'missing_s3_fields' => $missing,
            'local_path' => storage_path('app/public'),
            'public_linked' => is_link(public_path('storage')) || file_exists(public_path('storage')),
            'connection_status' => $connectionStatus,
        ];
    }

    protected function getPickupVehiclesMeta(): array
    {
        return [
            'vehicleTypes' => PickupVehicleType::query()
                ->latest('id')
                ->get()
                ->map(fn (PickupVehicleType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                    'capacity_hint' => $type->capacity_hint,
                    'sort_order' => $type->sort_order,
                    'is_active' => $type->is_active,
                ])
                ->values(),
        ];
    }

    protected function getBusStationsMeta(): array
    {
        return [
            'stations' => BusStation::query()
                ->latest('id')
                ->get()
                ->map(fn (BusStation $station) => $this->busStationPayload($station))
                ->values(),
        ];
    }

    protected function getDeliveryFailureReasonsMeta(): array
    {
        return [
            'reasons' => DeliveryFailureReason::query()
                ->latest('id')
                ->get()
                ->map(fn (DeliveryFailureReason $reason) => $this->deliveryFailureReasonPayload($reason))
                ->values(),
            'types' => DeliveryFailureReason::types(),
        ];
    }

    protected function getDeliveryDelayReasonsMeta(): array
    {
        return [
            'reasons' => DeliveryDelayReason::query()
                ->latest('id')
                ->get()
                ->map(fn (DeliveryDelayReason $reason) => $this->deliveryDelayReasonPayload($reason))
                ->values(),
        ];
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
            'value' => $this->formatBytes($freeSpace).' free',
            'status' => $usedPercent < 80 ? 'ok' : ($usedPercent < 90 ? 'warning' : 'error'),
            'message' => $usedPercent.'% used',
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
    protected function getNotificationLogsMeta(): array
    {
        return [
            'types' => [
                'shipment_status',
                'driver_assigned', 'payment_recorded', 'general',
            ],
            'channels' => ['push', 'email', 'sms'],
            'statuses' => ['pending', 'sent', 'failed'],
        ];
    }

    protected function getEmailTemplates(): array
    {
        $templates = EmailTemplate::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn (EmailTemplate $template) => $this->serializeEmailTemplate($template))
            ->values();

        return [
            'templates' => $templates,
            'categories' => $templates->pluck('category')->unique()->values(),
            'recipientTypes' => $templates->pluck('recipient_type')->unique()->values(),
        ];
    }

    public function updateEmailTemplate(Request $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        if (blank($validated['body_html'] ?? null) && blank($validated['body_text'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Add either HTML content or a plain text fallback.',
            ], 422);
        }

        $emailTemplate->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Email template saved.',
            'template' => $this->serializeEmailTemplate($emailTemplate->fresh()),
        ]);
    }

    public function storeEmailTemplate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/', 'unique:email_templates,key'],
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:80'],
            'recipient_type' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'body_text' => ['nullable', 'string'],
            'variables' => ['nullable', 'array'],
            'variables.*' => ['string', 'max:80', 'regex:/^[a-zA-Z0-9_]+$/'],
            'is_enabled' => ['required', 'boolean'],
        ]);

        if (blank($validated['body_html'] ?? null) && blank($validated['body_text'] ?? null)) {
            return response()->json([
                'success' => false,
                'message' => 'Add either HTML content or a plain text fallback.',
            ], 422);
        }

        $template = EmailTemplate::create([
            ...$validated,
            'variables' => array_values(array_unique($validated['variables'] ?? [])),
            'is_system' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email template created.',
            'template' => $this->serializeEmailTemplate($template),
        ], 201);
    }

    public function toggleEmailTemplate(EmailTemplate $emailTemplate): JsonResponse
    {
        $emailTemplate->update(['is_enabled' => ! $emailTemplate->is_enabled]);

        return response()->json([
            'success' => true,
            'message' => $emailTemplate->is_enabled ? 'Template enabled.' : 'Template disabled.',
            'template' => $this->serializeEmailTemplate($emailTemplate->fresh()),
        ]);
    }

    public function previewEmailTemplate(EmailTemplate $emailTemplate, EmailTemplateService $service): JsonResponse
    {
        return response()->json([
            'success' => true,
            'preview' => $service->preview($emailTemplate),
        ]);
    }

    private function serializeEmailTemplate(EmailTemplate $template): array
    {
        return [
            'id' => $template->id,
            'key' => $template->key,
            'name' => $template->name,
            'category' => $template->category,
            'recipient_type' => $template->recipient_type,
            'recipient_label' => str($template->recipient_type)->replace('_', ' / ')->title()->toString(),
            'subject' => $template->subject,
            'body_html' => $template->body_html,
            'body_text' => $template->body_text,
            'variables' => $template->variables ?: [],
            'is_enabled' => $template->is_enabled,
            'is_system' => $template->is_system,
            'updated_at' => $template->updated_at?->format('d M Y, h:i A'),
        ];
    }

    /**
     * Save settings.
     */
    public function save(Request $request): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $tab = $request->input('tab');
        $settings = $request->input('settings', []);

        if ($tab === 'mail') {
            $this->validateMailSettings($request);
        }

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

    private function validateMailSettings(Request $request): void
    {
        $request->validate([
            'settings.mail_mailer.value' => ['nullable', 'string', Rule::in(['smtp', 'mailgun'])],
            'settings.mail_host.value' => ['nullable', 'string', 'max:255', 'regex:/^[A-Za-z0-9.-]+$/'],
            'settings.mail_port.value' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'settings.mail_username.value' => ['nullable', 'string', 'max:255'],
            'settings.mail_password.value' => ['nullable', 'string', 'max:2048'],
            'settings.mail_encryption.value' => ['nullable', 'string', Rule::in(['tls', 'ssl', ''])],
            'settings.mail_from_address.value' => ['nullable', 'email', 'max:255'],
            'settings.mail_from_name.value' => ['nullable', 'string', 'max:255'],
        ], [
            'settings.mail_host.value.regex' => 'Enter a valid SMTP host name without a protocol or spaces.',
        ]);
    }

    public function storePickupVehicle(Request $request): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', 'unique:pickup_vehicle_types,name'],
            'capacity_hint' => ['nullable', 'string', 'max:180'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $type = PickupVehicleType::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'icon' => 'car',
            'capacity_hint' => $validated['capacity_hint'] ?? null,
            'sort_order' => $validated['sort_order'] ?? ((PickupVehicleType::max('sort_order') ?? 0) + 1),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup vehicle type created.',
            'vehicle_type' => $this->pickupVehiclePayload($type),
        ], 201);
    }

    public function updatePickupVehicle(Request $request, PickupVehicleType $pickupVehicleType): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('pickup_vehicle_types', 'name')->ignore($pickupVehicleType->id)],
            'capacity_hint' => ['nullable', 'string', 'max:180'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $pickupVehicleType->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'capacity_hint' => $validated['capacity_hint'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $pickupVehicleType->sort_order,
            'is_active' => $validated['is_active'] ?? $pickupVehicleType->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup vehicle type updated.',
            'vehicle_type' => $this->pickupVehiclePayload($pickupVehicleType->fresh()),
        ]);
    }

    public function togglePickupVehicle(PickupVehicleType $pickupVehicleType): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $pickupVehicleType->update(['is_active' => ! $pickupVehicleType->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Pickup vehicle type status updated.',
            'vehicle_type' => $this->pickupVehiclePayload($pickupVehicleType->fresh()),
        ]);
    }

    public function deletePickupVehicle(PickupVehicleType $pickupVehicleType): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $pickupVehicleType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pickup vehicle type deleted.',
        ]);
    }

    private function pickupVehiclePayload(PickupVehicleType $type): array
    {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'slug' => $type->slug,
            'capacity_hint' => $type->capacity_hint,
            'sort_order' => $type->sort_order,
            'is_active' => $type->is_active,
        ];
    }

    public function storeBusStation(Request $request): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:bus_stations,name'],
            'location_hint' => ['nullable', 'string', 'max:180'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $station = BusStation::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'location_hint' => $validated['location_hint'] ?? null,
            'sort_order' => $validated['sort_order'] ?? ((BusStation::max('sort_order') ?? 0) + 1),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bus station created.',
            'bus_station' => $this->busStationPayload($station),
        ], 201);
    }

    public function updateBusStation(Request $request, BusStation $busStation): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('bus_stations', 'name')->ignore($busStation->id)],
            'location_hint' => ['nullable', 'string', 'max:180'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $busStation->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'location_hint' => $validated['location_hint'] ?? null,
            'sort_order' => $validated['sort_order'] ?? $busStation->sort_order,
            'is_active' => $validated['is_active'] ?? $busStation->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bus station updated.',
            'bus_station' => $this->busStationPayload($busStation->fresh()),
        ]);
    }

    public function toggleBusStation(BusStation $busStation): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $busStation->update(['is_active' => ! $busStation->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Bus station status updated.',
            'bus_station' => $this->busStationPayload($busStation->fresh()),
        ]);
    }

    public function deleteBusStation(BusStation $busStation): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $busStation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bus station deleted.',
        ]);
    }

    private function busStationPayload(BusStation $station): array
    {
        return [
            'id' => $station->id,
            'name' => $station->name,
            'slug' => $station->slug,
            'location_hint' => $station->location_hint,
            'sort_order' => $station->sort_order,
            'is_active' => $station->is_active,
        ];
    }

    public function storeDeliveryFailureReason(Request $request): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120', 'unique:delivery_failure_reasons,label'],
            'type' => ['required', Rule::in(DeliveryFailureReason::types())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $reason = DeliveryFailureReason::create([
            'label' => $validated['label'],
            'slug' => Str::slug($validated['label']),
            'type' => $validated['type'],
            'sort_order' => $validated['sort_order'] ?? ((DeliveryFailureReason::max('sort_order') ?? 0) + 1),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery failure reason created.',
            'reason' => $this->deliveryFailureReasonPayload($reason),
        ], 201);
    }

    public function updateDeliveryFailureReason(Request $request, DeliveryFailureReason $deliveryFailureReason): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120', Rule::unique('delivery_failure_reasons', 'label')->ignore($deliveryFailureReason->id)],
            'type' => ['required', Rule::in(DeliveryFailureReason::types())],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $deliveryFailureReason->update([
            'label' => $validated['label'],
            'slug' => Str::slug($validated['label']),
            'type' => $validated['type'],
            'sort_order' => $validated['sort_order'] ?? $deliveryFailureReason->sort_order,
            'is_active' => $validated['is_active'] ?? $deliveryFailureReason->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery failure reason updated.',
            'reason' => $this->deliveryFailureReasonPayload($deliveryFailureReason->fresh()),
        ]);
    }

    public function toggleDeliveryFailureReason(DeliveryFailureReason $deliveryFailureReason): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $deliveryFailureReason->update(['is_active' => ! $deliveryFailureReason->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery failure reason status updated.',
            'reason' => $this->deliveryFailureReasonPayload($deliveryFailureReason->fresh()),
        ]);
    }

    public function deleteDeliveryFailureReason(DeliveryFailureReason $deliveryFailureReason): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $deliveryFailureReason->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery failure reason deleted.',
        ]);
    }

    private function deliveryFailureReasonPayload(DeliveryFailureReason $reason): array
    {
        return [
            'id' => $reason->id,
            'label' => $reason->label,
            'slug' => $reason->slug,
            'type' => $reason->type,
            'type_label' => Str::of($reason->type)->replace('_', ' ')->title()->toString(),
            'sort_order' => $reason->sort_order,
            'is_active' => $reason->is_active,
        ];
    }

    public function storeDeliveryDelayReason(Request $request): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120', 'unique:delivery_delay_reasons,label'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $reason = DeliveryDelayReason::create([
            'label' => $validated['label'],
            'slug' => Str::slug($validated['label']),
            'sort_order' => $validated['sort_order'] ?? ((DeliveryDelayReason::max('sort_order') ?? 0) + 1),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery delay reason created.',
            'reason' => $this->deliveryDelayReasonPayload($reason),
        ], 201);
    }

    public function updateDeliveryDelayReason(Request $request, DeliveryDelayReason $deliveryDelayReason): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:120', Rule::unique('delivery_delay_reasons', 'label')->ignore($deliveryDelayReason->id)],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $deliveryDelayReason->update([
            'label' => $validated['label'],
            'slug' => Str::slug($validated['label']),
            'sort_order' => $validated['sort_order'] ?? $deliveryDelayReason->sort_order,
            'is_active' => $validated['is_active'] ?? $deliveryDelayReason->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery delay reason updated.',
            'reason' => $this->deliveryDelayReasonPayload($deliveryDelayReason->fresh()),
        ]);
    }

    public function toggleDeliveryDelayReason(DeliveryDelayReason $deliveryDelayReason): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $deliveryDelayReason->update(['is_active' => ! $deliveryDelayReason->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery delay reason status updated.',
            'reason' => $this->deliveryDelayReasonPayload($deliveryDelayReason->fresh()),
        ]);
    }

    public function deleteDeliveryDelayReason(DeliveryDelayReason $deliveryDelayReason): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $deliveryDelayReason->delete();

        return response()->json([
            'success' => true,
            'message' => 'Delivery delay reason deleted.',
        ]);
    }

    private function deliveryDelayReasonPayload(DeliveryDelayReason $reason): array
    {
        return [
            'id' => $reason->id,
            'label' => $reason->label,
            'slug' => $reason->slug,
            'sort_order' => $reason->sort_order,
            'is_active' => $reason->is_active,
        ];
    }

    private function authorizeSettingsEdit(): void
    {
        $user = auth('admin')->user();

        abort_unless($user && app(BackOfficeAccess::class)->canUsePermission($user, 'settings.edit'), 403);
    }

    /**
     * Upload file (logo, favicon, etc.)
     */
    public function uploadFile(Request $request): JsonResponse
    {
        $this->authorizeSettingsEdit();

        $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,ico,svg|max:2048',
            'key' => 'required|string',
        ]);

        $file = $request->file('file');
        $key = $request->input('key');

        $storedFile = app(StorageService::class)->upload($file, 'branding');
        $path = $storedFile['path'];

        PlatformSetting::setValue($key, $path);

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => app(StorageService::class)->getUrl($path),
            'message' => 'File uploaded successfully.',
        ]);
    }

    /**
     * Send a test push notification to the currently logged-in admin.
     */
    public function testPushNotification(Request $request): JsonResponse
    {
        $user = \Illuminate\Support\Facades\Auth::guard('admin')->user();

        if (! $user->fcm_token) {
            return response()->json([
                'success' => false,
                'needs_token' => true,
                'message' => 'No FCM token found for your account.',
            ]);
        }

        try {
            $sent = app(\App\Services\PushNotificationService::class)->sendToAdmin(
                $user,
                'Test Notification',
                'Push notifications are working correctly on Parcelman!',
                ['test' => 'true'],
                'test'
            );

            return response()->json([
                'success' => $sent,
                'message' => $sent
                    ? 'Test notification sent! You should see it in your browser now.'
                    : 'Send failed. Check your Firebase service account credentials.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Upload Firebase service account credentials JSON file.
     */
    public function uploadFirebaseCredentials(Request $request): JsonResponse
    {
        $request->validate([
            'credentials' => 'required|file|mimes:json|max:64',
        ]);

        $file = $request->file('credentials');
        $content = file_get_contents($file->getRealPath());
        $json = json_decode($content, true);

        if (! $json || ! isset($json['project_id'], $json['private_key'], $json['client_email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid service account JSON. Must contain project_id, private_key, and client_email.',
            ], 422);
        }

        $dir = storage_path('app/firebase');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($dir.'/firestore.json', $content);

        PlatformSetting::setValue('firebase_project_id', $json['project_id']);
        PlatformSetting::setValue('firebase_credentials_uploaded_at', now()->toIso8601String());

        // Clear cached access token so the new credentials take effect immediately
        \Illuminate\Support\Facades\Cache::forget('fcm_access_token');

        return response()->json([
            'success' => true,
            'message' => 'Firebase credentials uploaded successfully. Project: '.$json['project_id'],
        ]);
    }

    /**
     * Get system logs data.
     */
    public function logsData(Request $request): JsonResponse
    {
        $logFile = storage_path('logs/laravel.log');

        if (! File::exists($logFile)) {
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
            $logs = array_filter($logs, fn ($log) => strtolower($log['level']) === strtolower($level));
            $logs = array_values($logs);
        }

        // Filter by search
        if ($search = $request->input('search')) {
            $logs = array_filter($logs, fn ($log) => stripos($log['message'], $search) !== false ||
                stripos($log['context'], $search) !== false
            );
            $logs = array_values($logs);
        }

        // Filter by date
        if ($dateFrom = $request->input('date_from')) {
            $logs = array_filter($logs, fn ($log) => $log['date'] >= $dateFrom);
            $logs = array_values($logs);
        }
        if ($dateTo = $request->input('date_to')) {
            $logs = array_filter($logs, fn ($log) => $log['date'] <= $dateTo.' 23:59:59');
            $logs = array_values($logs);
        }

        // Sort (newest first by default)
        $sortDirection = $request->input('direction', 'desc');
        usort($logs, function ($a, $b) use ($sortDirection) {
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
        $paginatedLogs = array_map(function ($log, $index) use ($offset) {
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

        if (! File::exists($logFile)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $content = File::get($logFile);
        $logs = $this->parseLogFile($content);

        // Sort newest first
        usort($logs, fn ($a, $b) => strcmp($b['date'], $a['date']));

        if (! isset($logs[$index - 1])) {
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

        if (! File::exists($logFile)) {
            return response()->json(['error' => 'Log file not found'], 404);
        }

        $format = $request->input('format', 'txt');
        $content = File::get($logFile);

        if ($format === 'json') {
            $logs = $this->parseLogFile($content);
            usort($logs, fn ($a, $b) => strcmp($b['date'], $a['date']));

            return response()->json($logs)
                ->header('Content-Disposition', 'attachment; filename="logs_'.date('Y-m-d_His').'.json"');
        }

        // Default: raw log file
        $filename = 'laravel_'.date('Y-m-d_His').'.log';

        return response($content)
            ->header('Content-Type', 'text/plain')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
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
        $query = SmsLog::query();

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%")
                    ->orWhere('sender', 'like', "%{$search}%");
            });
        }

        if ($status = trim((string) $request->get('status', ''))) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('sent_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('sent_at', '<=', $dateTo);
        }

        $query->orderByDesc('sent_at')->orderByDesc('id');

        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => $logs->map(function (SmsLog $log) {
                return [
                    'id' => $log->id,
                    'recipient' => $log->recipient,
                    'message' => Str::limit($log->message, 180),
                    'status' => $log->status,
                    'provider' => $log->provider,
                    'sender' => $log->sender,
                    'status_code' => $log->status_code,
                    'error' => Str::limit($log->error ?: $this->smsLogResponseSummary($log), 220),
                    'sent_at' => $log->sent_at?->format('Y-m-d H:i:s') ?? $log->created_at?->format('Y-m-d H:i:s'),
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

    private function smsLogResponseSummary(SmsLog $log): ?string
    {
        if (! $log->response) {
            return null;
        }

        $response = $log->response;

        foreach (['message', 'error', 'detail', 'description', 'status'] as $key) {
            if (isset($response[$key]) && is_scalar($response[$key])) {
                return (string) $response[$key];
            }
        }

        return json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null;
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
     * Get admin and warehouse user audit logs data.
     */
    public function adminAuditLogsData(Request $request): JsonResponse
    {
        $query = $this->buildAdminAuditLogsQuery($request);
        $perPage = min(max((int) $request->get('per_page', 25), 1), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'data' => $logs->map(function (AdminAuditLog $log) {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                    'scope' => $log->scope ?: 'system',
                    'action_type' => $log->action_type,
                    'action' => $log->action,
                    'description' => $log->description,
                    'method' => $log->method,
                    'route_name' => $log->route_name,
                    'url' => $log->url,
                    'status_code' => $log->status_code,
                    'duration_ms' => $log->duration_ms,
                    'ip_address' => $log->ip_address,
                    'actor' => [
                        'id' => $log->user?->id,
                        'name' => $log->user?->name ?? 'Unknown',
                        'email' => $log->user?->email,
                        'role' => $log->user?->roles?->first()?->name,
                        'warehouse_id' => $log->warehouse_id,
                        'warehouse_name' => $log->warehouse?->name,
                    ],
                    'metadata' => $log->metadata ?? [],
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
     * Export admin audit logs in JSON/CSV format.
     */
    public function adminAuditLogsExport(Request $request)
    {
        $format = strtolower((string) $request->get('format', 'json'));
        $rows = $this->buildAdminAuditLogsQuery($request)
            ->limit(5000)
            ->get()
            ->map(function (AdminAuditLog $log) {
                return [
                    'id' => $log->id,
                    'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                    'scope' => $log->scope,
                    'action_type' => $log->action_type,
                    'action' => $log->action,
                    'description' => $log->description,
                    'method' => $log->method,
                    'route_name' => $log->route_name,
                    'status_code' => $log->status_code,
                    'duration_ms' => $log->duration_ms,
                    'ip_address' => $log->ip_address,
                    'actor_name' => $log->user?->name,
                    'actor_email' => $log->user?->email,
                    'actor_role' => $log->user?->roles?->first()?->name,
                    'warehouse' => $log->warehouse?->name,
                    'url' => $log->url,
                    'metadata' => json_encode($log->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                ];
            })
            ->values()
            ->toArray();

        $filenameBase = 'admin_audit_logs_'.date('Y-m-d_His');

        if ($format === 'csv') {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="'.$filenameBase.'.csv"',
            ];

            $callback = function () use ($rows): void {
                $output = fopen('php://output', 'w');
                if (! $output) {
                    return;
                }

                if (! empty($rows)) {
                    fputcsv($output, array_keys($rows[0]));
                }

                foreach ($rows as $row) {
                    fputcsv($output, $row);
                }

                fclose($output);
            };

            return response()->stream($callback, 200, $headers);
        }

        return response()->json($rows)
            ->header('Content-Disposition', 'attachment; filename="'.$filenameBase.'.json"');
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
            app(MailSettingsService::class)->apply();

            \Illuminate\Support\Facades\Mail::raw('This is a test email from Parcelman Express.', function ($message) use ($request) {
                $message->to($request->email)
                    ->subject('Test Email - Parcelman Express');
            });

            return response()->json([
                'success' => true,
                'message' => 'Test email sent successfully.',
            ]);
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'message' => MailSettingsService::TEST_FAILURE_MESSAGE,
            ], 500);
        }
    }

    /**
     * Send test SMS.
     */
    public function testSms(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => 'required|string',
        ]);

        try {
            $sent = app(SmsService::class)->send(
                $validated['phone'],
                'Parcelman test SMS. Your SMS settings are working.'
            );

            return response()->json([
                'success' => $sent,
                'message' => $sent
                    ? 'Test SMS sent successfully.'
                    : 'Test SMS failed. Check SMS settings and the application log for the provider response.',
            ], $sent ? 200 : 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test SMS: '.$e->getMessage(),
            ], 500);
        }
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
                'message' => 'Failed to clear cache: '.$e->getMessage(),
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
            'storage.s3.access_key',
            'storage.s3.secret_key',
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

        return round($bytes, 2).' '.$units[$i];
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

    /**
     * Build admin audit logs query with filters.
     */
    protected function buildAdminAuditLogsQuery(Request $request)
    {
        $query = AdminAuditLog::query()->with([
            'user:id,name,email,warehouse_id',
            'user.roles:id,name',
            'warehouse:id,name',
        ]);

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('route_name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($scope = trim((string) $request->get('scope', ''))) {
            $query->where('scope', $scope);
        }

        if ($actionType = trim((string) $request->get('action_type', ''))) {
            $query->where('action_type', $actionType);
        }

        if ($method = trim((string) $request->get('method', ''))) {
            $query->where('method', strtoupper($method));
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($statusCode = $request->get('status_code')) {
            $query->where('status_code', (int) $statusCode);
        }

        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sortBy = (string) $request->get('sort_by', 'created_at');
        $sortOrder = strtolower((string) $request->get('sort_order', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'action_type', 'method', 'status_code', 'duration_ms'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        return $query->orderBy($sortBy, $sortOrder);
    }
}
