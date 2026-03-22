<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_run_stops', 'delivery_notes')) {
                $table->text('delivery_notes')->nullable()->after('proof_photo_size');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            $table->dropColumn('delivery_notes');
        });
    }
};
