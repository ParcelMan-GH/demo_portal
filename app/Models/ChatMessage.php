<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single chat message.
 *
 * Immutable once sent — there is no update path, and the migration carries no
 * `updated_at` for the same reason. A vendor may already have read a message, so
 * silently editing it would make both sides' history disagree.
 */
class ChatMessage extends Model
{
    /** The table only records when a message was sent. */
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'thread_id');
    }

    public function isFromVendor(): bool
    {
        return $this->sender_type === ChatThread::SENDER_VENDOR;
    }

    /**
     * A message needs *something* in it.
     *
     * A row with neither text nor an attachment renders as an empty bubble that
     * neither side can interpret, so both send paths validate against this
     * rather than each keeping its own idea of "blank" — the app sends an empty
     * string when a user attaches a photo with no caption.
     */
    public function hasContent(): bool
    {
        return filled($this->message) || filled($this->attachment_url);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
