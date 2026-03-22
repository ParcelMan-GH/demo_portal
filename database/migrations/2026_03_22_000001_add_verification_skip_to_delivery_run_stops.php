<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_run_stops', 'verification_skipped')) {
                $table->boolean('verification_skipped')->default(false)->after('max_attempts');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'verification_skip_reason')) {
                $table->string('verification_skip_reason')->nullable()->after('verification_skipped');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'verification_skipped_at')) {
                $table->timestamp('verification_skipped_at')->nullable()->after('verification_skip_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            $table->dropColumn(['verification_skipped', 'verification_skip_reason', 'verification_skipped_at']);
        });
    }
};
