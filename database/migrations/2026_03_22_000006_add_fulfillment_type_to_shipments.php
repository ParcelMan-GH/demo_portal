<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'fulfillment_type')) {
                $table->string('fulfillment_type')->default('warehouse')->after('source');
                $table->index('fulfillment_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'fulfillment_type')) {
                $table->dropIndex(['fulfillment_type']);
                $table->dropColumn('fulfillment_type');
            }
        });
    }
};
