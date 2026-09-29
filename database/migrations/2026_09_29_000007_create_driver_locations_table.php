<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each rider was, and when.
 *
 * The admin dashboard has always been able to draw riders, but it only ever had
 * stop coordinates to draw them at — and every delivery_run_stop in the database
 * has NULL latitude/longitude, so in practice it drew nobody and showed "No
 * active deliveries" while a transporter was mid-trip.
 *
 * This is the real thing: one row per position report from a phone. History
 * rather than a single updated column, because "track every rider at every point
 * in time" needs the trail, and because a rider's position at the moment an
 * admin looks is only the last of many.
 *
 * The trip columns are set when the position was reported while the rider was on
 * a transport manifest or a delivery run, so a position can be read in context
 * ("driver 5, on PM-BATCH-FME7DB, heading for Kumasi Center") rather than as a
 * pair of bare numbers.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('driver_locations')) {
            return;
        }

        Schema::create('driver_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transport_manifest_id')->nullable()->constrained('transport_manifests')->nullOnDelete();
            $table->foreignId('delivery_run_id')->nullable()->constrained('delivery_runs')->nullOnDelete();

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->decimal('speed', 8, 2)->nullable();

            // When the phone took the fix, which is not when it reached us: a
            // ping that sat in a dead spot arrives late, and ordering by
            // arrival would put the trail out of order.
            $table->timestamp('recorded_at');
            $table->timestamps();

            // The read that matters: the newest fix for the riders on live trips.
            // (Declaring an index on `recorded_at` twice — once here and once on
            // the column above — creates the same index name twice and fails the
            // whole table.)
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_locations');
    }
};
