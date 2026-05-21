<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->default('car');
            $table->string('capacity_hint')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        Schema::create('shipment_pickup_vehicle_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pickup_vehicle_type_id')->nullable()->constrained('pickup_vehicle_types')->nullOnDelete();
            $table->string('vehicle_name_snapshot');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->index('shipment_id');
            $table->index('pickup_vehicle_type_id');
        });

        $defaults = [
            ['name' => 'Motorbike', 'icon' => 'bicycle', 'capacity_hint' => 'Small parcels and light pickup runs.'],
            ['name' => 'Aboboyaa', 'icon' => 'trail-sign', 'capacity_hint' => 'Bulky local pickup loads.'],
            ['name' => 'Van', 'icon' => 'car', 'capacity_hint' => 'Medium package volumes.'],
            ['name' => 'Truck', 'icon' => 'bus', 'capacity_hint' => 'Large or heavy pickup loads.'],
        ];

        foreach ($defaults as $index => $row) {
            DB::table('pickup_vehicle_types')->insert([
                'name' => $row['name'],
                'slug' => Str::slug($row['name']),
                'icon' => $row['icon'],
                'capacity_hint' => $row['capacity_hint'],
                'sort_order' => $index + 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_pickup_vehicle_requests');
        Schema::dropIfExists('pickup_vehicle_types');
    }
};
