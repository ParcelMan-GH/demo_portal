<?php

namespace Database\Seeders;

use App\Models\PickupVehicleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PickupVehicleTypeSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => 'Motorbike', 'capacity_hint' => 'Small parcels and light pickup runs.'],
            ['name' => 'Aboboyaa', 'capacity_hint' => 'Bulky local pickup loads.'],
            ['name' => 'Van', 'capacity_hint' => 'Medium package volumes.'],
            ['name' => 'Truck', 'capacity_hint' => 'Large or heavy pickup loads.'],
        ];

        foreach ($defaults as $index => $type) {
            PickupVehicleType::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($type['name'])],
                [
                    'name' => $type['name'],
                    'icon' => 'car',
                    'capacity_hint' => $type['capacity_hint'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'deleted_at' => null,
                ],
            );
        }
    }
}
