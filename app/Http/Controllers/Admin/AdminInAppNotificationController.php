<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminInAppNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('admin');
        $limit = max(1, min((int) $request->integer('limit', 10), 20));

        $notifications = AdminNotification::query()
            ->where('user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get();

        $unreadCount = AdminNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => [
                'unread_count' => $unreadCount,
                'notifications' => $notifications->map(fn (AdminNotification $notification) => $this->serialize($notification))->values(),
            ],
        ]);
    }

    public function markRead(Request $request, AdminNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user('admin')->id, 404);

        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AdminNotification::query()
            ->where('user_id', $request->user('admin')->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    private function serialize(AdminNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'url' => $notification->url,
            'data' => $notification->data ?? [],
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at?->toISOString(),
            'created_at_label' => $notification->created_at?->diffForHumans(),
        ];
    }
}
