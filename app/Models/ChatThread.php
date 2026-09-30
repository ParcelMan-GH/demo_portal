<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * One vendor's support conversation.
 *
 * The unread counters and `last_message_at` live here, and every write to them
 * goes through this class. Two controllers (vendor app, admin portal) post
 * messages, and if each maintained the counters itself they would eventually
 * disagree — so the increments are not duplicated at the call sites.
 */
class ChatThread extends Model
{
    public const SENDER_VENDOR = 'vendor';
    public const SENDER_ADMIN  = 'admin';

    public const STATUS_OPEN   = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $guarded = [];

    protected $casts = [
        'last_message_at' => 'datetime',
        'unread_admin_count' => 'integer',
        'unread_vendor_count' => 'integer',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'thread_id')->orderBy('id');
    }

    /**
     * The vendor's thread, created on first use.
     *
     * `firstOrCreate` relies on the unique index on `vendor_id`, so two
     * simultaneous opens resolve to one row instead of racing into two. That is
     * why the constraint is in the migration rather than being a convention.
     */
    public static function forVendor(int $vendorId): self
    {
        return self::firstOrCreate(
            ['vendor_id' => $vendorId],
            ['status' => self::STATUS_OPEN]
        );
    }

    /**
     * Append a message and keep the thread's counters in step.
     *
     * Wrapped in a transaction with the insert so a failure cannot record a
     * message without incrementing the other side's unread badge — an inbox that
     * under-reports is worse than one that over-reports, because nobody goes
     * looking for a message they were never told about.
     */
    public function addMessage(string $senderType, ?int $senderId, ?string $message, ?string $attachmentUrl = null): ChatMessage
    {
        return DB::transaction(function () use ($senderType, $senderId, $message, $attachmentUrl) {
            $chat = $this->messages()->create([
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message' => $message,
                'attachment_url' => $attachmentUrl,
                'created_at' => now(),
            ]);

            $isVendor = $senderType === self::SENDER_VENDOR;

            // Touch the timestamp directly rather than using `->update()`, so the
            // single statement is the increment and cannot lose a concurrent one.
            self::whereKey($this->getKey())->update([
                'last_message_at' => now(),
                $isVendor ? 'unread_admin_count' : 'unread_vendor_count' => DB::raw(
                    ($isVendor ? 'unread_admin_count' : 'unread_vendor_count') . ' + 1'
                ),
            ]);

            $this->refresh();

            return $chat;
        });
    }

    /**
     * Clear the badge for one side.
     *
     * Returned rather than assumed so the caller can report how many were
     * cleared; it is also a no-op when there was nothing unread, which is the
     * common case on a poll loop and must not write on every tick.
     */
    public function markReadFor(string $readerType): int
    {
        $senderToClear = $readerType === self::SENDER_VENDOR
            ? self::SENDER_ADMIN
            : self::SENDER_VENDOR;

        return DB::transaction(function () use ($readerType, $senderToClear) {
            $cleared = ChatMessage::query()
                ->where('thread_id', $this->getKey())
                ->where('sender_type', $senderToClear)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $column = $readerType === self::SENDER_VENDOR
                ? 'unread_vendor_count'
                : 'unread_admin_count';

            if ($this->{$column} > 0) {
                self::whereKey($this->getKey())->update([$column => 0]);
                $this->refresh();
            }

            return $cleared;
        });
    }

    /**
     * Threads waiting on an answer, newest first — the admin inbox default.
     */
    public function scopeNeedsAttention(Builder $query): Builder
    {
        return $query->where('unread_admin_count', '>', 0);
    }
}
