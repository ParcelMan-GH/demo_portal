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
            // Referenced by the agent commission ledger: the payout an agent earns
            // is resolved from these bands, so without them every payout computes
            // to zero. It was written but never registered here — which is why the
            // production table was empty and agent earnings read as GH₵ 0.00.
            // The same rows are also applied by
            // 2026_09_30_000004_seed_commission_tiers for the deploy path.
            CommissionTierSeeder::class,
        ]);

        // IncomingTransportManifestSeeder is fixture/demo data and is intentionally
        // not part of the default production seed path.
    }
}
