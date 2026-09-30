<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reclassify voice notes that were stored as images.
 *
 * PHP sniffs a WebM *audio* blob as `video/webm`, so the original
 * `str_starts_with($mime, 'audio/')` test failed for every recording made with
 * the portal's browser recorder and they were written as `image`. The thread then
 * rendered them through the picture branch: a broken <img> whose alt text showed
 * as the literal word "attachment" in the bubble.
 *
 * The controller now classifies on three signals; this corrects the rows written
 * before it did.
 *
 * Signals used here, in order of confidence:
 *   - an audio file extension on the stored URL
 *   - a recorded `duration_seconds`, which only a recorder ever sets
 *
 * Deliberately does NOT touch rows already marked `audio`, and does not invent a
 * type for rows with no attachment at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        $audioExtensions = ['m4a', 'aac', 'mp3', 'mpga', 'ogg', 'oga', 'wav', 'caf', 'webm'];

        $rows = DB::table('chat_messages')
            ->whereNotNull('attachment_url')
            ->where(function ($query) {
                $query->where('attachment_type', '!=', 'audio')
                    ->orWhereNull('attachment_type');
            })
            ->get(['id', 'attachment_url', 'duration_seconds']);

        $fixed = 0;

        foreach ($rows as $row) {
            $path = parse_url((string) $row->attachment_url, PHP_URL_PATH) ?: '';
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            $looksAudio = in_array($ext, $audioExtensions, true)
                || $row->duration_seconds !== null;

            if (! $looksAudio) {
                continue;
            }

            DB::table('chat_messages')
                ->where('id', $row->id)
                ->update(['attachment_type' => 'audio']);

            $fixed++;
        }

        if ($fixed > 0) {
            // Named so an operator reading the deploy log can tell what changed
            // and how much.
            logger()->info("Reclassified {$fixed} chat message(s) from image to audio.");
        }
    }

    /**
     * Not reversed: the previous value was wrong, and restoring it would put the
     * broken bubbles back on screen.
     */
    public function down(): void
    {
    }
};
