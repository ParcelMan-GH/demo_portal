<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouses', 'is_hq')) {
                $table->boolean('is_hq')->default(false)->after('is_active');
            }

            if (!Schema::hasColumn('warehouses', 'can_administer_system')) {
                $table->boolean('can_administer_system')->default(false)->after('is_hq');
            }
        });

        $hq = Warehouse::query()
            ->where('is_hq', true)
            ->first()
            ?? Warehouse::query()->where('code', 'WH-001')->first()
            ?? Warehouse::query()->orderBy('id')->first();

        if ($hq) {
            $hq->forceFill([
                'is_hq' => true,
                'can_administer_system' => true,
            ])->save();

            User::query()
                ->whereNull('warehouse_id')
                ->whereHas('roles', fn ($query) => $query->whereIn('slug', ['administrator', 'super_admin']))
                ->update(['warehouse_id' => $hq->id]);
        }
    }

    public function down(): void
    {
        Schema::table('warehouses', function (Blueprint $table) {
            if (Schema::hasColumn('warehouses', 'can_administer_system')) {
                $table->dropColumn('can_administer_system');
            }

            if (Schema::hasColumn('warehouses', 'is_hq')) {
                $table->dropColumn('is_hq');
            }
        });
    }
};
