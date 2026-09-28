<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The driver's emergency contact.
 *
 * The edit-profile screen has always collected it and posted it, and there was
 * nowhere to put it: `drivers` had no such column and the service's writable
 * list did not mention it, so the value was dropped without complaint while the
 * screen said "Saved". The screen now reports a dropped field honestly, which
 * means this field would otherwise fail loudly and permanently — better to give
 * it the column it was always meant to have.
 *
 * A plain nullable string, matching how the form captures it: a free-text line
 * that may hold a name, a number, or both.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('drivers', 'emergency_contact')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->string('emergency_contact', 255)->nullable()->after('base_location');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('drivers', 'emergency_contact')) {
            Schema::table('drivers', function (Blueprint $table) {
                $table->dropColumn('emergency_contact');
            });
        }
    }
};
