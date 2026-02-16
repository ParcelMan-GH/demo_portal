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
        if (Schema::hasColumn('warehouses', 'type')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('warehouses', 'type')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->string('type')->default('both')->after('code');
            });
        }
    }
};
