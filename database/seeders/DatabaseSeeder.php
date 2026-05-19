<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlatformSettingsSeeder::class,
            GhanaLocationsSeeder::class,
            GhanaTownsSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            SuperAdminSeeder::class,
            WarehouseSeeder::class,
            DriversSeeder::class,
            ShipmentSettingsSeeder::class,
            EmailTemplateSeeder::class,
        ]);
    }
}
