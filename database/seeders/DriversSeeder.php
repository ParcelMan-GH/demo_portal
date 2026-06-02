<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriversSeeder extends Seeder
{
    public function run(): void
    {
        Driver::updateOrCreate(
            ['phone' => '+233244111111'],
            [
                'name' => 'John Driver',
                'email' => 'driver@example.com',
                'password' => Hash::make('password123'),
                'vehicle_type' => 'motorcycle',
                'vehicle_number' => 'GR-1234-20',
                'license_number' => 'DL123456',
                'base_location' => 'Accra Central',
                'status' => 'offline',
                'is_active' => true,
                'task_capabilities' => ['pickup', 'delivery'],
            ],
        );
    }
}
