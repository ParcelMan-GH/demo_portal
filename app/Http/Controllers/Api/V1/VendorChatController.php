<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * The vendor's side of the support chat.
 *
 * Two endpoints, deliberately: fetch the conversation, and post to it. Reading
 * also clears the vendor's unread badge, because the app has no way to know a
 * message was actually seen otherwise — an explicit read-receipt call would be a
 * third endpoint the app could forget to make, leaving a badge that never clears.
 */
class VendorChatController extends Controller
{
    /** Messages per page. The app scrolls back, so this is a window, not a cap. */
    private const PER_PAGE = 50;

    public function show(Request $request): JsonResponse
    {
        $vendor = $request->user();
        $thread = ChatThread::forVendor($vendor->id);

        // Reading is seeing: clear the badge before returning the history.
        $thread->markReadFor(ChatThread::SENDER_VENDOR);

        $messages = $thread->messages()
            ->orderByDesc('id')
            ->limit(self::PER_PAGE)
            ->get()
            ->sortBy('id')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'thread' => [
                    'id' => $thread->id,
                    'status' => $thread->status,
                    'last_message_at' => $thread->last_message_at?->toIso8601String(),
                ],
                'messages' => $messages->map(fn (ChatMessage $m) => $this->serialize($m))->all(),
                // Read from the refreshed row, not the caller's copy, so the app
                // never shows a badge for a message it just fetched.
                'unread_count' => $thread->fresh()?->unread_vendor_count ?? 0,
                'has_more' => $thread->messages()->count() > $messages->count(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user();

        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            // 5 MB: a phone screenshot is comfortably under this, and an
            // unbounded upload on a support form is an easy way to fill a disk.
            // Audio included for voice notes. m4a/aac are what the mobile
            // recorders produce. webm and mp4 are NOT optional extras: a browser
            // MediaRecorder emits audio/webm on Chrome and audio/mp4 on Safari,
            // so omitting them would reject every voice reply an admin records
            // from the portal itself.
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,m4a,aac,mp3,ogg,wav,caf,webm,mp4,mpga', 'max:10240'],
            // Sent by the recording client, which is the only party that knows it.
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $attachmentUrl = null;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('chat-attachments', 'public');
            $attachmentUrl = Storage::disk('public')->url($path);
        }

        /*
         * A row with neither text nor a file is an empty bubble that support
         * cannot act on. The app sends an empty string when a photo is attached
         * without a caption, so "blank" has to mean both are missing — checking
         * only the string would reject every captioned-photo-less message.
         */

        /*
         * Classify the upload.
         *
         * Three signals, because no single one is reliable:
         *
         *  1. A declared audio mime.
         *  2. The extension. It is the tie-breaker that matters: PHP sniffs a
         *     WebM *audio* blob as `video/webm`, not `audio/webm`, so a browser
         *     recording from the portal failed an `audio/` prefix test and was
         *     stored as an image — then rendered as a broken picture reading
         *     "attachment" in the thread.
         *  3. A duration. Only a recorder sends one, so its presence settles the
         *     question even if both of the above are ambiguous.
         */
        $attachmentType = 'text';
        $durationSeconds = null;

        if ($attachmentUrl !== null) {
            $file = $request->file('attachment');
            $mime = (string) $file?->getMimeType();
            $ext = strtolower((string) $file?->getClientOriginalExtension());

            $audioExtensions = ['m4a', 'aac', 'mp3', 'mpga', 'ogg', 'oga', 'wav', 'caf', 'webm', 'mp4'];

            $durationSeconds = isset($validated['duration_seconds'])
                ? (int) $validated['duration_seconds']
                : null;

            $isAudio = str_starts_with($mime, 'audio/')
                || in_array($ext, $audioExtensions, true)
                || $durationSeconds !== null;

            $attachmentType = $isAudio ? 'audio' : 'image';
        }

        if (blank($validated['message'] ?? null) && $attachmentUrl === null) {
            throw ValidationException::withMessages([
                'message' => 'Type a message or attach a file.',
            ]);
        }

        $thread = ChatThread::forVendor($vendor->id);

        // A thread closed by support reopens on the vendor's next message rather
        // than trapping them: they have no other way to reach anyone.
        if ($thread->status === ChatThread::STATUS_CLOSED) {
            $thread->update(['status' => ChatThread::STATUS_OPEN]);
        }

        $message = $thread->addMessage(
            ChatThread::SENDER_VENDOR,
            (int) $vendor->id,
            $validated['message'] ?? null,
            $attachmentUrl,
            $attachmentType,
            $durationSeconds
        );

        return response()->json([
            'success' => true,
            'data' => $this->serialize($message),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'is_mine' => $message->isFromVendor(),
            'message' => $message->message,
            'attachment_type' => $message->attachment_type ?? 'text',
            'duration_seconds' => $message->duration_seconds,
            'attachment_url' => $message->attachment_url,
            'read_at' => $message->read_at?->toIso8601String(),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
