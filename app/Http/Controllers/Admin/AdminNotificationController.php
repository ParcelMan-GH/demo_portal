<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationController extends Controller
{
    public function index(): View
    {
        $types = [
            'shipment_status', 'invoice_sent', 'invoice_accepted', 'invoice_rejected',
            'driver_assigned', 'payment_recorded', 'general',
        ];
        $channels = ['push', 'email', 'sms'];
        $statuses = ['pending', 'sent', 'failed'];

        return view('admin.notifications.index', compact('types', 'channels', 'statuses'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = NotificationLog::query()->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%")
                  ->orWhere('notifiable_type', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($channel = $request->input('channel')) {
            $query->where('channel', $channel);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $total = (clone $query)->count();
        $perPage = 20;
        $page = max(1, (int) $request->input('page', 1));
        $skip = ($page - 1) * $perPage;

        $logs = $query->skip($skip)->take($perPage)->get();

        return response()->json([
            'data' => $logs->map(fn(NotificationLog $log) => [
                'id'              => $log->id,
                'notifiable_type' => class_basename($log->notifiable_type),
                'notifiable_id'   => $log->notifiable_id,
                'type'            => $log->type,
                'channel'         => $log->channel,
                'title'           => $log->title,
                'body'            => $log->body,
                'status'          => $log->status,
                'error'           => $log->error,
                'read_at'         => $log->read_at?->format('Y-m-d H:i'),
                'created_at'      => $log->created_at->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max((int) ceil($total / $perPage), 1),
                'from'         => $skip + 1,
                'to'           => min($skip + $perPage, $total),
            ],
        ]);
    }

    public function markRead(NotificationLog $notification): JsonResponse
    {
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }
}
