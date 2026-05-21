<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pickup_vehicle_types')) {
            return;
        }

        $types = DB::table('pickup_vehicle_types')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($types as $index => $type) {
            DB::table('pickup_vehicle_types')
                ->where('id', $type->id)
                ->update(['sort_order' => $index + 1]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pickup_vehicle_types')) {
            return;
        }

        $types = DB::table('pickup_vehicle_types')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id']);

        foreach ($types as $index => $type) {
            DB::table('pickup_vehicle_types')
                ->where('id', $type->id)
                ->update(['sort_order' => ($index + 1) * 10]);
        }
    }
};
