<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The last position a rider reported, on the row the API already has.
 *
 * `TransporterLocationController` has been writing these three columns since it
 * was written, and none of them existed — so every position report died on a
 * mass-assignment error before it stored anything. The columns are created here
 * rather than the writes being deleted, because a latest-position pointer on the
 * user is the cheap read: the dashboard shows one dot per rider, not a trail, and
 * `driver_locations` is the history behind it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_latitude')) {
                $table->decimal('current_latitude', 10, 7)->nullable()->after('warehouse_id');
            }

            if (! Schema::hasColumn('users', 'current_longitude')) {
                $table->decimal('current_longitude', 10, 7)->nullable()->after('current_latitude');
            }

            if (! Schema::hasColumn('users', 'last_location_at')) {
                $table->timestamp('last_location_at')->nullable()->after('current_longitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['current_latitude', 'current_longitude', 'last_location_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
