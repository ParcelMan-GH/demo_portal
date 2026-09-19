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
        Schema::table('outgoing_batches', function (Blueprint $table) {
            // What kind of destination this batch serves. A 'commerce' batch may
            // only carry commerce packages — enforced in OutgoingBatchPackageService
            // so both the UI and the API apply the same rule.
            $table->string('destination_type')->nullable()->after('delivery_district_id');
            $table->index('destination_type');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            // Marks an item as a commerce package, which is what makes it eligible
            // for a commerce batch.
            $table->boolean('is_commerce')->default(false)->after('delivery_preference');
            $table->index('is_commerce');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outgoing_batches', function (Blueprint $table) {
            $table->dropIndex(['destination_type']);
            $table->dropColumn('destination_type');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropIndex(['is_commerce']);
            $table->dropColumn('is_commerce');
        });
    }
};
