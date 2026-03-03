<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\NotificationLog;
use App\Models\Vendor;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMarketingController extends Controller
{
    public function __construct(private PushNotificationService $pushService) {}

    public function index(): View
    {
        // Recent campaigns (notification_logs with type='broadcast')
        $recentCampaigns = NotificationLog::where('type', 'broadcast')
            ->latest()
            ->take(10)
            ->get(['id', 'title', 'body', 'channel', 'status', 'notifiable_type', 'created_at']);

        $vendorCount = Vendor::where('is_active', true)->whereNotNull('fcm_token')->count();
        $driverCount = Driver::where('is_active', true)->whereNotNull('fcm_token')->count();
        $totalBroadcasts = NotificationLog::where('type', 'broadcast')->count();

        return view('admin.marketing.index', compact('recentCampaigns', 'vendorCount', 'driverCount', 'totalBroadcasts'));
    }

    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title'    => ['required', 'string', 'max:200'],
            'body'     => ['required', 'string', 'max:1000'],
            'audience' => ['required', 'in:all_vendors,all_drivers,all'],
        ]);

        $sent = 0;
        $failed = 0;

        if (in_array($validated['audience'], ['all_vendors', 'all'])) {
            Vendor::where('is_active', true)->whereNotNull('fcm_token')->each(function (Vendor $vendor) use ($validated, &$sent, &$failed) {
                $success = $this->pushService->sendToVendor(
                    vendor: $vendor,
                    title: $validated['title'],
                    body: $validated['body'],
                    data: ['audience' => 'vendor'],
                    type: 'broadcast'
                );
                $success ? $sent++ : $failed++;
            });
        }

        if (in_array($validated['audience'], ['all_drivers', 'all'])) {
            Driver::where('is_active', true)->whereNotNull('fcm_token')->each(function (Driver $driver) use ($validated, &$sent, &$failed) {
                $success = $this->pushService->sendToDriver(
                    driver: $driver,
                    title: $validated['title'],
                    body: $validated['body'],
                    data: ['audience' => 'driver'],
                    type: 'broadcast'
                );
                $success ? $sent++ : $failed++;
            });
        }

        return response()->json([
            'success' => true,
            'message' => "Broadcast sent. {$sent} delivered, {$failed} failed.",
            'data'    => ['sent' => $sent, 'failed' => $failed],
        ]);
    }
}
