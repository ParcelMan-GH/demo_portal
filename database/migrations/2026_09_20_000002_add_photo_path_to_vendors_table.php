<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            // Drivers and users already had this; vendors were the only profile
            // type that could not hold a photo at all.
            if (! Schema::hasColumn('vendors', 'photo_path')) {
                $table->string('photo_path')->nullable()->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            if (Schema::hasColumn('vendors', 'photo_path')) {
                $table->dropColumn('photo_path');
            }
        });
    }
};
