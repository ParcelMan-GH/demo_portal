<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the column added an hour ago by 000005.
 *
 * It recorded when a batch's box label was printed, and departure was gated on
 * it. That was the wrong gate: printing a label is paper, it does not release a
 * batch. Releasing a batch is the admin's "Close & Dispatch", which is what puts
 * it in transport's hands.
 *
 * Nothing reads the column now, and a column that looks like a gate that no
 * longer exists is worse than no column — so it goes rather than sitting there
 * implying a rule that was removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('transport_manifests', 'labels_printed_at')) {
            Schema::table('transport_manifests', function (Blueprint $table) {
                $table->dropColumn('labels_printed_at');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('transport_manifests', 'labels_printed_at')) {
            Schema::table('transport_manifests', function (Blueprint $table) {
                $table->timestamp('labels_printed_at')->nullable()->after('assigned_at');
            });
        }
    }
};
