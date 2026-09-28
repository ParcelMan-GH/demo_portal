<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When this batch's box label was printed.
 *
 * A batch travels as one box with one label on it. Whether that label has been
 * printed is what says the box was packed and sealed at the warehouse, and it is
 * what departure should wait for.
 *
 * Departure used to be gated on every parcel carrying its own label row, which
 * is a different question and a worse one: a single parcel that was never
 * receipted — no receipt item, so nothing to derive a barcode from — made the
 * whole box undepartable, and no amount of printing could clear it. The driver
 * was left holding a sealed, labelled box the app refused to release.
 *
 * Printing the box label is a discrete event, so it is recorded as one. The
 * per-parcel labels are still created and still reported on the sheet, where a
 * shortfall belongs: with the warehouse, on the paper, before the box is sealed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transport_manifests', 'labels_printed_at')) {
            Schema::table('transport_manifests', function (Blueprint $table) {
                $table->timestamp('labels_printed_at')->nullable()->after('assigned_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transport_manifests', 'labels_printed_at')) {
            Schema::table('transport_manifests', function (Blueprint $table) {
                $table->dropColumn('labels_printed_at');
            });
        }
    }
};
