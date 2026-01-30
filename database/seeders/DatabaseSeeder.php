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
        // Seed platform settings
        $this->call(PlatformSettingsSeeder::class);

        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            PlatformSettingsSeeder::class,
            GhanaLocationsSeeder::class,
            RoleSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
            DriversSeeder::class,
            ShipmentSettingsSeeder::class
        ]);
    }
}
