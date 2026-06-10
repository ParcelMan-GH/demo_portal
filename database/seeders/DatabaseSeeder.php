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
            WarehouseSeeder::class,
            SuperAdminSeeder::class,
            DriversSeeder::class,
            ShipmentSettingsSeeder::class,
            PickupVehicleTypeSeeder::class,
            DeliveryFailureReasonSeeder::class,
            DeliveryDelayReasonSeeder::class,
            DeliveryDelaySettingsSeeder::class,
            EmailTemplateSeeder::class,
        ]);

        // IncomingTransportManifestSeeder is fixture/demo data and is intentionally
        // not part of the default production seed path.
    }
}
