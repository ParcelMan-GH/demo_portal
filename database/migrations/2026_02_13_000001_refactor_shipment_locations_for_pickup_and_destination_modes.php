<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('destination_mode')->default('single')->after('status');

            $table->string('pickup_contact_name')->nullable()->after('destination_mode');
            $table->string('pickup_contact_phone')->nullable()->after('pickup_contact_name');
            $table->foreignId('pickup_region_id')->nullable()->after('pickup_contact_phone')->constrained('regions')->nullOnDelete();
            $table->foreignId('pickup_district_id')->nullable()->after('pickup_region_id')->constrained('districts')->nullOnDelete();
            $table->string('pickup_town')->nullable()->after('pickup_district_id');
            $table->decimal('pickup_latitude', 10, 8)->nullable()->after('pickup_town');
            $table->decimal('pickup_longitude', 11, 8)->nullable()->after('pickup_latitude');
            $table->string('pickup_gh_post_address')->nullable()->after('pickup_longitude');
            $table->string('pickup_landmark')->nullable()->after('pickup_gh_post_address');
            $table->text('pickup_instructions')->nullable()->after('pickup_landmark');

            $table->string('delivery_recipient_name')->nullable()->after('pickup_instructions');
            $table->string('delivery_recipient_phone')->nullable()->after('delivery_recipient_name');
            $table->foreignId('delivery_region_id')->nullable()->after('delivery_recipient_phone')->constrained('regions')->nullOnDelete();
            $table->foreignId('delivery_district_id')->nullable()->after('delivery_region_id')->constrained('districts')->nullOnDelete();
            $table->string('delivery_town')->nullable()->after('delivery_district_id');
            $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_town');
            $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
            $table->string('delivery_gh_post_address')->nullable()->after('delivery_longitude');
            $table->string('delivery_landmark')->nullable()->after('delivery_gh_post_address');

            $table->index('destination_mode');
            $table->index('pickup_region_id');
            $table->index('pickup_district_id');
            $table->index('delivery_region_id');
            $table->index('delivery_district_id');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->string('delivery_recipient_name')->nullable()->after('quantity');
            $table->string('delivery_recipient_phone')->nullable()->after('delivery_recipient_name');
            $table->foreignId('delivery_region_id')->nullable()->after('delivery_recipient_phone')->constrained('regions')->nullOnDelete();
            $table->foreignId('delivery_district_id')->nullable()->after('delivery_region_id')->constrained('districts')->nullOnDelete();
            $table->string('delivery_town')->nullable()->after('delivery_district_id');
            $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_town');
            $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
            $table->string('delivery_gh_post_address')->nullable()->after('delivery_longitude');
            $table->string('delivery_landmark')->nullable()->after('delivery_gh_post_address');
            $table->text('delivery_instructions')->nullable()->after('delivery_landmark');

            $table->index('delivery_region_id');
            $table->index('delivery_district_id');
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['region_id']);
            $table->dropForeign(['district_id']);
            $table->dropColumn([
                'recipient_name',
                'recipient_phone',
                'region_id',
                'district_id',
                'town',
                'latitude',
                'longitude',
                'gh_post_address',
                'landmark',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('status');
            $table->string('recipient_phone')->nullable()->after('recipient_name');
            $table->foreignId('region_id')->nullable()->after('recipient_phone')->constrained('regions')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->after('region_id')->constrained('districts')->nullOnDelete();
            $table->string('town')->nullable()->after('district_id');
            $table->decimal('latitude', 10, 8)->nullable()->after('town');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->string('gh_post_address')->nullable()->after('longitude');
            $table->string('landmark')->nullable()->after('gh_post_address');
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropForeign(['delivery_region_id']);
            $table->dropForeign(['delivery_district_id']);
            $table->dropColumn([
                'delivery_recipient_name',
                'delivery_recipient_phone',
                'delivery_region_id',
                'delivery_district_id',
                'delivery_town',
                'delivery_latitude',
                'delivery_longitude',
                'delivery_gh_post_address',
                'delivery_landmark',
                'delivery_instructions',
            ]);
        });

        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['pickup_region_id']);
            $table->dropForeign(['pickup_district_id']);
            $table->dropForeign(['delivery_region_id']);
            $table->dropForeign(['delivery_district_id']);
            $table->dropColumn([
                'destination_mode',
                'pickup_contact_name',
                'pickup_contact_phone',
                'pickup_region_id',
                'pickup_district_id',
                'pickup_town',
                'pickup_latitude',
                'pickup_longitude',
                'pickup_gh_post_address',
                'pickup_landmark',
                'pickup_instructions',
                'delivery_recipient_name',
                'delivery_recipient_phone',
                'delivery_region_id',
                'delivery_district_id',
                'delivery_town',
                'delivery_latitude',
                'delivery_longitude',
                'delivery_gh_post_address',
                'delivery_landmark',
            ]);
        });
    }
};
