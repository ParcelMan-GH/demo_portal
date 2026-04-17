<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            if (!Schema::hasColumn('delivery_run_stops', 'delivery_method')) {
                $table->string('delivery_method', 20)->default('direct')->after('status');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'handoff_courier_name')) {
                $table->string('handoff_courier_name', 255)->nullable()->after('delivery_notes');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'handoff_courier_phone')) {
                $table->string('handoff_courier_phone', 20)->nullable()->after('handoff_courier_name');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'handoff_vehicle_number')) {
                $table->string('handoff_vehicle_number', 50)->nullable()->after('handoff_courier_phone');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'handoff_at')) {
                $table->timestamp('handoff_at')->nullable()->after('handoff_vehicle_number');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'confirmed_by_admin_id')) {
                $table->unsignedBigInteger('confirmed_by_admin_id')->nullable()->after('handoff_at');
                $table->foreign('confirmed_by_admin_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('delivery_run_stops', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmed_by_admin_id');
            }
            if (!Schema::hasColumn('delivery_run_stops', 'confirmation_notes')) {
                $table->text('confirmation_notes')->nullable()->after('confirmed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('delivery_run_stops', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by_admin_id']);
            $table->dropColumn([
                'delivery_method', 'handoff_courier_name', 'handoff_courier_phone',
                'handoff_vehicle_number', 'handoff_at',
                'confirmed_by_admin_id', 'confirmed_at', 'confirmation_notes',
            ]);
        });
    }
};
