<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Voice notes.
 *
 * `attachment_type` is stored rather than inferred at read time. The renderers
 * need to know whether a bubble is a picture or a player *before* they fetch the
 * file, and inferring it from the URL means every client reimplements the same
 * extension sniffing — and disagrees about `.m4a` on some Android browsers.
 *
 * `duration_seconds` is likewise stored. Only the recording client knows how long
 * a clip is, and the alternative is asking a browser to load the whole file just
 * to learn its length before it can draw a progress bar.
 *
 * Existing rows are backfilled so nothing renders as an unknown type.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            // 'text' | 'image' | 'audio'. A string, not an enum, for the same
            // reason as `sender_type`: a future 'video' should not need a schema
            // change to exist.
            $table->string('attachment_type', 16)->default('text')->after('message');
            $table->unsignedInteger('duration_seconds')->nullable()->after('attachment_type');

            // The conversation reads oldest-first per thread and filters nothing
            // on type, so no new index is warranted here.
        });

        // Backfill: anything already carrying a file was an image, everything
        // else is text. Without this, existing attachment bubbles would report
        // 'text' and render no player and no picture.
        \Illuminate\Support\Facades\DB::table('chat_messages')
            ->whereNull('attachment_url')
            ->update(['attachment_type' => 'text']);

        \Illuminate\Support\Facades\DB::table('chat_messages')
            ->whereNotNull('attachment_url')
            ->update(['attachment_type' => 'image']);
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_type', 'duration_seconds']);
        });
    }
};
