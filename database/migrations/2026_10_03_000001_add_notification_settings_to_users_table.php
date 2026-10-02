<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The alert toggles behind the agent app's notification screen.
 *
 * `drivers` gained `notification_settings` in 2026_09_29_000001, but `users` —
 * where the call agents live — never did, so `/v1/agent/notifications/settings`
 * had nowhere to persist. Mirrors the driver column rather than inventing a
 * second shape: both hold the same three toggles as JSON.
 *
 * Guarded with `hasColumn`, like the driver migration, so re-running on an
 * environment that already has it is a no-op rather than an error.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'notification_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('notification_settings')->nullable()->after('fcm_token');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'notification_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('notification_settings');
            });
        }
    }
};
