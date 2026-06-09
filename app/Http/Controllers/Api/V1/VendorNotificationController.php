<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorNotificationController extends Controller
{
    /**
     * List notifications for the authenticated vendor.
     */
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $validated = $request->validate([
            'status'     => ['nullable', 'string', Rule::in(['sent', 'failed', 'logged'])],
            'type'       => ['nullable', 'string', 'max:100'],
            'is_read'    => ['nullable', 'in:true,false,1,0'],
            'from_date'  => ['nullable', 'date'],
            'to_date'    => ['nullable', 'date', 'after_or_equal:from_date'],
            'limit'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset'     => ['nullable', 'integer', 'min:0'],
            'sort_by'    => ['nullable', 'string', Rule::in(['id', 'type', 'status', 'created_at', 'read_at'])],
            'sort_order' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
        ]);

        $query = NotificationLog::where('notifiable_type', 'App\Models\Vendor')
            ->where('notifiable_id', $vendor->id);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        } else {
            $query->whereIn('status', ['sent', 'logged']);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['from_date'])) {
            $query->whereDate('created_at', '>=', $validated['from_date']);
        }

        if (!empty($validated['to_date'])) {
            $query->whereDate('created_at', '<=', $validated['to_date']);
        }

        $unreadQuery = clone $query;

        if (array_key_exists('is_read', $validated) && !is_null($validated['is_read'])) {
            if (filter_var($validated['is_read'], FILTER_VALIDATE_BOOLEAN)) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $limit     = (int) ($validated['limit'] ?? 20);
        $offset    = (int) ($validated['offset'] ?? 0);
        $sortBy    = $validated['sort_by'] ?? 'created_at';
        $sortOrder = $validated['sort_order'] ?? 'desc';

        $total = (clone $query)->count();
        $unreadCount = $unreadQuery->whereNull('read_at')->count();

        $notifications = $query
            ->orderBy($sortBy, $sortOrder)
            ->offset($offset)
            ->limit($limit)
            ->get();

        $rows = $notifications->map(fn(NotificationLog $log) => [
            'id'         => $log->id,
            'type'       => $log->type,
            'channel'    => $log->channel,
            'title'      => $log->title,
            'body'       => $log->body,
            'data'       => $log->data,
            'status'     => $log->status,
            'is_read'    => $log->isRead(),
            'read_at'    => $log->read_at,
            'created_at' => $log->created_at,
        ])->toArray();

        $count    = count($rows);
        $hasMore  = ($offset + $count) < $total;
        $nextOffset = $hasMore ? ($offset + $limit) : null;

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully.',
            'data'    => [
                'notifications' => $rows,
                'unread_count'  => $unreadCount,
                'pagination'    => [
                    'offset'       => $offset,
                    'limit'        => $limit,
                    'total'        => $total,
                    'has_more'     => $hasMore,
                    'next_offset'  => $nextOffset,
                    'current_page' => (int) floor($offset / $limit) + 1,
                    'last_page'    => max((int) ceil($total / $limit), 1),
                    'per_page'     => $limit,
                ],
            ],
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, NotificationLog $notification): JsonResponse
    {
        $vendor = $request->user();

        if ($notification->notifiable_type !== 'App\Models\Vendor' || $notification->notifiable_id !== $vendor->id) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found.',
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read.',
            'data'    => [
                'notification' => [
                    'id'         => $notification->id,
                    'type'       => $notification->type,
                    'channel'    => $notification->channel,
                    'title'      => $notification->title,
                    'body'       => $notification->body,
                    'data'       => $notification->data,
                    'status'     => $notification->status,
                    'is_read'    => true,
                    'read_at'    => $notification->read_at,
                    'created_at' => $notification->created_at,
                ],
            ],
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $updated = NotificationLog::where('notifiable_type', 'App\Models\Vendor')
            ->where('notifiable_id', $vendor->id)
            ->whereIn('status', ['sent', 'logged'])
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} notification(s) marked as read.",
            'data'    => [
                'updated_count' => $updated,
            ],
        ]);
    }
}
