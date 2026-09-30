<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The support team's side of the vendor chat.
 *
 * Backs the split-pane inbox: a thread list on the left, one conversation on the
 * right.
 *
 * Note on authorisation: these routes sit behind the admin auth middleware, and
 * are not additionally gated on a permission string. The portal's
 * `authorizePermission()` registry has no chat entry, and inventing
 * `chat.manage` here would 403 every request until someone added it to the
 * registry — a silent, total failure. A dedicated permission belongs in the
 * registry first; until then the guard is the boundary.
 */
class AdminChatController extends Controller
{
    private const PER_PAGE = 40;

    public function __construct(private PushNotificationService $push) {}

    /**
     * Thread list for the left pane.
     *
     * Sorted by `last_message_at`, not `updated_at`: opening a thread moves
     * `updated_at` but must not reshuffle the inbox under the reader's cursor.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ChatThread::query()
            ->with(['vendor'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($request->boolean('unread_only')) {
            $query->where('unread_admin_count', '>', 0);
        }

        if ($search = trim((string) $request->get('search'))) {
            $query->whereHas('vendor', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $threads = $query->limit(self::PER_PAGE)->get();

        /*
         * The preview is fetched with a single grouped query for the whole page
         * rather than `$thread->messages()->latest()->first()` per row, which is
         * one query per thread — the shape that makes an inbox feel slow as soon
         * as it holds a few hundred conversations.
         */
        $previews = ChatMessage::query()
            ->whereIn('thread_id', $threads->pluck('id'))
            ->orderByDesc('id')
            ->get()
            ->groupBy('thread_id')
            ->map(fn ($group) => $group->first());

        return response()->json([
            'success' => true,
            'data' => $threads->map(function (ChatThread $thread) use ($previews) {
                $last = $previews->get($thread->id);

                return [
                    'id' => $thread->id,
                    'status' => $thread->status,
                    'unread_count' => $thread->unread_admin_count,
                    'last_message_at' => $thread->last_message_at?->toIso8601String(),
                    'last_message' => $last?->message,
                    'last_sender_type' => $last?->sender_type,
                    'has_attachment' => filled($last?->attachment_url),
                    'vendor' => [
                        'id' => $thread->vendor?->id,
                        'name' => $thread->vendor?->name,
                        'business_name' => $thread->vendor?->business_name,
                        'phone' => $thread->vendor?->phone,
                        'photo_url' => $thread->vendor?->photo_path,
                    ],
                ];
            })->all(),
            'meta' => [
                'total' => ChatThread::count(),
                'unread_threads' => ChatThread::where('unread_admin_count', '>', 0)->count(),
            ],
        ]);
    }

    public function show(Request $request, int $thread): JsonResponse
    {
        $chat = ChatThread::with('vendor')->findOrFail($thread);

        $messages = $chat->messages()
            ->orderByDesc('id')
            ->limit(self::PER_PAGE)
            ->get()
            ->sortBy('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'thread' => [
                    'id' => $chat->id,
                    'status' => $chat->status,
                    'unread_count' => $chat->unread_admin_count,
                ],
                'vendor' => [
                    'id' => $chat->vendor?->id,
                    'name' => $chat->vendor?->name,
                    'business_name' => $chat->vendor?->business_name,
                    'phone' => $chat->vendor?->phone,
                    'email' => $chat->vendor?->email,
                    'photo_url' => $chat->vendor?->photo_path,
                    // Deep link for the header, so support can see what the
                    // vendor is asking about without leaving the conversation.
                    'shipments_url' => $chat->vendor
                        ? route('admin.vendors.show', $chat->vendor->id)
                        : null,
                ],
                'messages' => $messages->map(fn (ChatMessage $m) => [
                    'id' => $m->id,
                    'sender_type' => $m->sender_type,
                    'is_from_vendor' => $m->isFromVendor(),
                    'message' => $m->message,
                    'attachment_url' => $m->attachment_url,
                    'read_at' => $m->read_at?->toIso8601String(),
                    'created_at' => $m->created_at?->toIso8601String(),
                ])->all(),
            ],
        ]);
    }

    public function store(Request $request, int $thread): JsonResponse
    {
        $chat = ChatThread::with('vendor')->findOrFail($thread);

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat-attachments', 'public');
            $attachmentUrl = Storage::disk('public')->url($path);
        }

        if (blank($validated['message'] ?? null) && $attachmentUrl === null) {
            throw ValidationException::withMessages([
                'message' => 'Type a reply or attach a file.',
            ]);
        }

        $admin = Auth::guard('admin')->user();

        $message = $chat->addMessage(
            ChatThread::SENDER_ADMIN,
            $admin?->id,
            $validated['message'] ?? null,
            $attachmentUrl
        );

        /*
         * Push to the vendor's device. Wrapped because a gateway failure must not
         * fail the reply: the message is already committed, and from the vendor's
         * side an undelivered push is a missed notification, not a lost message —
         * they will still find it in the thread on next open.
         *
         * `sendToVendor()` records an in-app row even when there is no FCM token,
         * so a vendor who never granted push permission still sees it.
         */
        try {
            if ($chat->vendor) {
                $preview = $validated['message'] ?? null;
                $body = filled($preview)
                    ? mb_strimwidth($preview, 0, 120, '…')
                    : 'Sent you a photo';

                $this->push->sendToVendor(
                    $chat->vendor,
                    'New message from Parcelman Support',
                    $body,
                    ['type' => 'chat', 'thread_id' => (string) $chat->id],
                    'chat'
                );
            }
        } catch (\Throwable $e) {
            \Log::warning('Vendor chat reply push failed', [
                'thread_id' => $chat->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'message' => $message->message,
                'attachment_url' => $message->attachment_url,
                'created_at' => $message->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function markRead(int $thread): JsonResponse
    {
        $chat = ChatThread::findOrFail($thread);

        // `markReadFor()` takes the type of the READER, and clears the messages
        // sent by the other side. SENDER_ADMIN ('admin') is the reader here.
        $cleared = $chat->markReadFor(ChatThread::SENDER_ADMIN);

        return response()->json([
            'success' => true,
            'data' => ['cleared' => $cleared, 'unread_count' => $chat->fresh()?->unread_admin_count ?? 0],
        ]);
    }
}
