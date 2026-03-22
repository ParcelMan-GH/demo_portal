<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_run_stops', 'total_packages')) {
                $table->unsignedInteger('total_packages')->default(1)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            $table->dropColumn('total_packages');
        });
    }
};
