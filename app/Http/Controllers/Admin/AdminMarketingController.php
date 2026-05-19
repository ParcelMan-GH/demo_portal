<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\Vendor;
use App\Services\PushNotificationService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminMarketingController extends Controller
{
    public function __construct(
        private PushNotificationService $pushService,
        private SmsService $smsService,
    ) {}

    public function index(): View
    {
        // Counts per channel
        $vendorCountPush  = Vendor::where('is_active', true)->whereNotNull('fcm_token')->count();
        $driverCountPush  = Driver::where('is_active', true)->whereNotNull('fcm_token')->count();
        $vendorCountSms   = Vendor::where('is_active', true)->whereNotNull('phone')->where('phone', '!=', '')->count();
        $driverCountSms   = Driver::where('is_active', true)->whereNotNull('phone')->where('phone', '!=', '')->count();
        $vendorCountEmail = Vendor::where('is_active', true)->whereNotNull('email')->where('email', '!=', '')->count();
        $driverCountEmail = Driver::where('is_active', true)->whereNotNull('email')->where('email', '!=', '')->count();

        $totalBroadcasts = NotificationLog::where('type', 'broadcast')->count();
        $broadcastsByChannel = NotificationLog::where('type', 'broadcast')
            ->selectRaw('channel, count(*) as count')
            ->groupBy('channel')
            ->pluck('count', 'channel')
            ->toArray();

        $emailTemplates = $this->getEmailTemplatesList();

        return view('admin.marketing.index', compact(
            'vendorCountPush', 'driverCountPush',
            'vendorCountSms', 'driverCountSms',
            'vendorCountEmail', 'driverCountEmail',
            'totalBroadcasts', 'broadcastsByChannel',
            'emailTemplates',
        ));
    }

    public function data(Request $request): JsonResponse
    {
        $query = NotificationLog::query()
            ->where('type', 'broadcast')
            ->with('notifiable')
            ->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhere('error', 'like', "%{$search}%")
                  ->orWhereHasMorph('notifiable', [Vendor::class, Driver::class], function ($recipientQuery) use ($search) {
                      $recipientQuery->where('name', 'like', "%{$search}%")
                          ->orWhere('phone', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");

                      if ($recipientQuery->getModel() instanceof Vendor) {
                          $recipientQuery->orWhere('business_name', 'like', "%{$search}%");
                      }
                  });
            });
        }

        if ($channel = $request->input('channel')) {
            $query->where('channel', $channel);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($audience = $request->input('audience')) {
            $query->where('data->audience', $audience);
        }

        if ($template = $request->input('template')) {
            $query->where('data->template', $template);
        }

        if ($request->filled('has_error')) {
            $request->boolean('has_error')
                ? $query->whereNotNull('error')->where('error', '!=', '')
                : $query->where(function ($errorQuery) {
                    $errorQuery->whereNull('error')->orWhere('error', '');
                });
        }

        if ($dateFrom = $request->date('date_from')) {
            $query->where('created_at', '>=', $dateFrom->startOfDay());
        }

        if ($dateTo = $request->date('date_to')) {
            $query->where('created_at', '<=', $dateTo->endOfDay());
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $page    = max((int) $request->input('page', 1), 1);
        $total   = $query->count();
        $logs    = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $logs->map(fn ($log) => [
                'id'              => $log->id,
                'title'           => $log->title,
                'body'            => $log->body,
                'channel'         => $log->channel,
                'status'          => $log->status,
                'error'           => $log->error,
                'notifiable_type' => class_basename($log->notifiable_type ?? ''),
                'notifiable_id'   => $log->notifiable_id,
                'recipient_name'   => $this->recipientName($log->notifiable),
                'recipient_phone'  => $log->notifiable?->phone,
                'recipient_email'  => $log->notifiable?->email,
                'audience'         => $log->data['audience'] ?? null,
                'template'         => $log->data['template'] ?? null,
                'created_at'      => $log->created_at?->toISOString(),
            ]),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max((int) ceil($total / $perPage), 1),
                'from'         => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    private function recipientName(mixed $recipient): ?string
    {
        if (!$recipient) {
            return null;
        }

        return $recipient->business_name
            ?? $recipient->name
            ?? null;
    }

    public function emailTemplates(): JsonResponse
    {
        return response()->json(['templates' => $this->getEmailTemplatesList()]);
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel'        => ['required', 'in:push,sms,email'],
            'audience'       => ['required', 'in:all_vendors,all_drivers,all'],
            'title'          => ['nullable', 'string', 'max:200'],
            'body'           => ['required', 'string', 'max:5000'],
            'subject'        => ['nullable', 'string', 'max:200'],
            'email_template' => ['nullable', 'string'],
        ]);

        $channel  = $validated['channel'];
        $audience = $validated['audience'];

        // Validate channel-specific required fields
        if ($channel === 'push' && empty($validated['title'])) {
            return response()->json(['success' => false, 'message' => 'Title is required for push notifications.'], 422);
        }
        if ($channel === 'email' && empty($validated['subject'])) {
            return response()->json(['success' => false, 'message' => 'Subject is required for emails.'], 422);
        }

        $sent   = 0;
        $failed = 0;

        $recipients = $this->getRecipients($channel, $audience);

        foreach ($recipients as $recipient) {
            $success = match ($channel) {
                'push'  => $this->sendPush($recipient, $validated),
                'sms'   => $this->sendSms($recipient, $validated),
                'email' => $this->sendEmail($recipient, $validated),
            };
            $success ? $sent++ : $failed++;
        }

        $channelLabel = strtoupper($channel);

        return response()->json([
            'success' => true,
            'message' => "{$channelLabel} broadcast sent. {$sent} delivered, {$failed} failed.",
            'data'    => ['sent' => $sent, 'failed' => $failed],
        ]);
    }

    // ─── Private helpers ─────────────────────────────────────────

    private function getRecipients(string $channel, string $audience): \Illuminate\Support\Collection
    {
        $field = match ($channel) {
            'push'  => 'fcm_token',
            'sms'   => 'phone',
            'email' => 'email',
        };

        $vendors  = collect();
        $drivers  = collect();

        if (in_array($audience, ['all_vendors', 'all'])) {
            $vendors = Vendor::where('is_active', true)
                ->whereNotNull($field)->where($field, '!=', '')
                ->get();
        }

        if (in_array($audience, ['all_drivers', 'all'])) {
            $drivers = Driver::where('is_active', true)
                ->whereNotNull($field)->where($field, '!=', '')
                ->get();
        }

        return $vendors->merge($drivers);
    }

    private function sendPush($recipient, array $data): bool
    {
        if ($recipient instanceof Vendor) {
            return $this->pushService->sendToVendor(
                vendor: $recipient,
                title: $data['title'],
                body: $data['body'],
                data: ['audience' => 'vendor'],
                type: 'broadcast',
            );
        }

        return $this->pushService->sendToDriver(
            driver: $recipient,
            title: $data['title'],
            body: $data['body'],
            data: ['audience' => 'driver'],
            type: 'broadcast',
        );
    }

    private function sendSms($recipient, array $data): bool
    {
        $success = false;
        $error   = null;

        try {
            $success = $this->smsService->send($recipient->phone, $data['body']);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        NotificationLog::create([
            'notifiable_type' => get_class($recipient),
            'notifiable_id'   => $recipient->id,
            'type'            => 'broadcast',
            'channel'         => 'sms',
            'title'           => 'SMS Broadcast',
            'body'            => $data['body'],
            'data'            => ['audience' => $data['audience']],
            'status'          => $success ? 'sent' : 'failed',
            'error'           => $error,
        ]);

        return $success;
    }

    private function sendEmail($recipient, array $data): bool
    {
        $success = false;
        $error   = null;
        $subject = $data['subject'] ?? $data['title'] ?? 'Broadcast';
        $template = $data['email_template'] ?? 'custom';

        try {
            $toEmail = $recipient->email;
            $toName  = $recipient->name ?? $recipient->business_name ?? '';

            if ($template !== 'custom' && view()->exists("emails.{$template}")) {
                $html = view("emails.{$template}", [
                    'subject'       => $subject,
                    'body'          => $data['body'],
                    'recipientName' => $toName,
                ])->render();

                Mail::html($html, function ($message) use ($toEmail, $toName, $subject) {
                    $message->to($toEmail, $toName)->subject($subject);
                });
            } else {
                Mail::raw($data['body'], function ($message) use ($toEmail, $toName, $subject) {
                    $message->to($toEmail, $toName)->subject($subject);
                });
            }

            $success = true;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        NotificationLog::create([
            'notifiable_type' => get_class($recipient),
            'notifiable_id'   => $recipient->id,
            'type'            => 'broadcast',
            'channel'         => 'email',
            'title'           => $subject,
            'body'            => $data['body'],
            'data'            => ['audience' => $data['audience'], 'template' => $template],
            'status'          => $success ? 'sent' : 'failed',
            'error'           => $error,
        ]);

        return $success;
    }

    private function getEmailTemplatesList(): array
    {
        $dir = resource_path('views/emails');

        if (!File::isDirectory($dir)) {
            return [];
        }

        return collect(File::allFiles($dir))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->map(fn ($file) => [
                'name' => str_replace('.blade.php', '', $file->getFilename()),
                'path' => $file->getRelativePathname(),
            ])
            ->values()
            ->toArray();
    }
}
