<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The missing half of the driver's vehicle record, plus notification settings.
 *
 * The vehicle screen has always collected Make & Model, License Plate and
 * Maximum Carrying Capacity. Of those, only the plate had a column
 * (`vehicle_number`), so the other two were accepted by the form and then
 * silently discarded — there was nowhere for them to go. `drivers` is its own
 * table, separate from `users`, and it never had them.
 *
 * `max_capacity` is a string, not an integer: the field is free text and the
 * value a driver enters is a unit-bearing label like "3,500 kg", which an
 * integer column would either reject or truncate to 3.
 *
 * `notification_settings` holds the alert toggles. There was no column and no
 * endpoint, so the switches on the notifications screen could only ever show
 * their defaults.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'make_model')) {
                $table->string('make_model')->nullable()->after('vehicle_type');
            }

            if (! Schema::hasColumn('drivers', 'max_capacity')) {
                $table->string('max_capacity', 50)->nullable()->after('license_number');
            }

            if (! Schema::hasColumn('drivers', 'notification_settings')) {
                $table->json('notification_settings')->nullable()->after('task_capabilities');
            }
        });
    }

    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            foreach (['make_model', 'max_capacity', 'notification_settings'] as $column) {
                if (Schema::hasColumn('drivers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
