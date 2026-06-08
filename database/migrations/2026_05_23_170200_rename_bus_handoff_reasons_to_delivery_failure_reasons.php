<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bus_handoff_reasons') && !Schema::hasTable('delivery_failure_reasons')) {
            Schema::rename('bus_handoff_reasons', 'delivery_failure_reasons');
        }

        Schema::table('bus_handoff_confirmations', function (Blueprint $table) {
            if (!Schema::hasColumn('bus_handoff_confirmations', 'reason_label')) {
                $table->string('reason_label', 120)->nullable()->after('reason_id');
            }
            if (!Schema::hasColumn('bus_handoff_confirmations', 'reason_type')) {
                $table->string('reason_type', 40)->nullable()->after('reason_label');
            }
        });

        if (Schema::hasTable('delivery_failure_reasons')) {
            if (DB::getDriverName() === 'sqlite') {
                DB::table('bus_handoff_confirmations')
                    ->whereNotNull('reason_id')
                    ->whereNull('reason_label')
                    ->update([
                        'reason_label' => DB::raw('(SELECT label FROM delivery_failure_reasons WHERE delivery_failure_reasons.id = bus_handoff_confirmations.reason_id LIMIT 1)'),
                        'reason_type' => DB::raw('(SELECT type FROM delivery_failure_reasons WHERE delivery_failure_reasons.id = bus_handoff_confirmations.reason_id LIMIT 1)'),
                    ]);

                return;
            }

            DB::table('bus_handoff_confirmations')
                ->leftJoin('delivery_failure_reasons', 'bus_handoff_confirmations.reason_id', '=', 'delivery_failure_reasons.id')
                ->whereNotNull('bus_handoff_confirmations.reason_id')
                ->whereNull('bus_handoff_confirmations.reason_label')
                ->update([
                    'bus_handoff_confirmations.reason_label' => DB::raw('delivery_failure_reasons.label'),
                    'bus_handoff_confirmations.reason_type' => DB::raw('delivery_failure_reasons.type'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('bus_handoff_confirmations', function (Blueprint $table) {
            if (Schema::hasColumn('bus_handoff_confirmations', 'reason_type')) {
                $table->dropColumn('reason_type');
            }
            if (Schema::hasColumn('bus_handoff_confirmations', 'reason_label')) {
                $table->dropColumn('reason_label');
            }
        });

        if (Schema::hasTable('delivery_failure_reasons') && !Schema::hasTable('bus_handoff_reasons')) {
            Schema::rename('delivery_failure_reasons', 'bus_handoff_reasons');
        }
    }
};
