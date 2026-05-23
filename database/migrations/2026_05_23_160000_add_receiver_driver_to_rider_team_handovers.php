<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_team_handovers', function (Blueprint $table) {
            if (! Schema::hasColumn('rider_team_handovers', 'receiver_driver_id')) {
                $table->foreignId('receiver_driver_id')
                    ->nullable()
                    ->after('leader_driver_id')
                    ->constrained('drivers')
                    ->nullOnDelete();
                $table->index(['receiver_driver_id', 'status'], 'rth_receiver_status_index');
            }
        });

        DB::table('rider_team_handovers')
            ->whereNull('receiver_driver_id')
            ->update(['receiver_driver_id' => DB::raw('leader_driver_id')]);
    }

    public function down(): void
    {
        Schema::table('rider_team_handovers', function (Blueprint $table) {
            if (Schema::hasColumn('rider_team_handovers', 'receiver_driver_id')) {
                $table->dropForeign(['receiver_driver_id']);
                $table->dropIndex('rth_receiver_status_index');
                $table->dropColumn('receiver_driver_id');
            }
        });
    }
};
