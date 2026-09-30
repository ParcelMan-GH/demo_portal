<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor <-> support chat.
 *
 * Two tables, one thread per vendor.
 *
 * The unread counters are denormalised onto the thread on purpose. Both screens
 * render a badge next to every row in a list, and deriving those with a
 * correlated subquery means N counts per page load — the exact shape that gets
 * slow on the admin inbox, which is the one screen that must stay instant. The
 * counters are maintained in the same transaction as the message insert, so they
 * cannot drift from the messages they describe.
 *
 * `last_message_at` is separate from `updated_at` because it is the sort key for
 * the admin inbox. `updated_at` moves for any touch — a read receipt, a status
 * change — and sorting on it would silently reshuffle the inbox when someone
 * merely opened a thread.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedInteger('unread_admin_count')->default(0);
            $table->unsignedInteger('unread_vendor_count')->default(0);
            $table->timestamps();

            // One thread per vendor today. Enforced here rather than in code so a
            // race between two app instances opening the thread simultaneously
            // cannot create a second one — the chat screen "fetches or
            // initialises", which is exactly the pattern that races.
            $table->unique('vendor_id');

            // The admin inbox sorts by last_message_at and filters on unread.
            $table->index(['last_message_at']);
            $table->index(['unread_admin_count']);
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thread_id')->constrained('chat_threads')->cascadeOnDelete();
            // Deliberately a string, not an enum: 'vendor' | 'admin'. Both the
            // mobile app and the portal write here, and a future 'system' or
            // 'bot' sender should not need a schema change to say so.
            $table->string('sender_type', 20);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message')->nullable();
            $table->string('attachment_url')->nullable();
            $table->timestamp('read_at')->nullable();
            // Only `created_at`. A chat message is immutable once sent — there is
            // no edit path and there should not be one, since the vendor may have
            // already read it.
            $table->timestamp('created_at')->nullable();

            // Every read is "the thread's messages, oldest first".
            $table->index(['thread_id', 'id']);
            // Marking read walks unread messages for one side of one thread.
            $table->index(['thread_id', 'sender_type', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_threads');
    }
};
