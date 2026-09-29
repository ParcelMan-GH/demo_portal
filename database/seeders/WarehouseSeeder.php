<?php

namespace Database\Seeders;

use App\Models\Region;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Looked up by name on purpose: the ids are not what one would assume
        // (6 is Volta and 7 is Northern), so a name cannot be off by one.
        $greaterAccra = Region::where('name', 'Greater Accra')->first();
        $ashanti = Region::where('name', 'Ashanti')->first();
        $eastern = Region::where('name', 'Eastern')->first();
        $northern = Region::where('name', 'Northern')->first();
        $western = Region::where('name', 'Western')->first();
        $volta = Region::where('name', 'Volta')->first();

        $warehouses = [
            [
                'name' => 'Accra Main Hub',
                'code' => 'WH-001',
                'address' => 'Industrial Area, Tema Motorway Extension, Accra',
                'region_id' => $greaterAccra?->id,
                'contact_phone' => '+233241000001',
                'contact_email' => 'accra-hub@parcelman.com',
                'capacity' => 5000,
                'is_active' => true,
                'is_hq' => true,
                'can_administer_system' => true,
            ],
            [
                'name' => 'Kumasi Distribution Center',
                'code' => 'WH-002',
                'address' => 'Asokwa Industrial Area, Kumasi',
                'region_id' => $ashanti?->id,
                'contact_phone' => '+233241000002',
                'contact_email' => 'kumasi@parcelman.com',
                'capacity' => 3000,
                'is_active' => true,
                'is_hq' => false,
                'can_administer_system' => false,
            ],
            [
                'name' => 'Tema Port Warehouse',
                'code' => 'WH-003',
                'address' => 'Tema Community 1, Near Tema Port',
                'region_id' => $greaterAccra?->id,
                'contact_phone' => '+233241000003',
                'contact_email' => 'tema@parcelman.com',
                'capacity' => 2000,
                'is_active' => true,
                'is_hq' => false,
                'can_administer_system' => false,
            ],

            /*
             * Primary regional hubs. Without these, 14 of Ghana's 16 regions had
             * no warehouse, so a batch for any of them could only resolve to the
             * HQ fallback and no regional transfer lane could exist.
             *
             * Coordinates are OpenStreetMap city points (ODbL, September 2026),
             * matching the migration that backfilled the first three.
             */
            [
                'name' => 'Koforidua Hub',
                'code' => 'WH-004',
                'address' => 'Koforidua',
                'region_id' => $eastern?->id,
                'latitude' => 6.1003340,
                'longitude' => -0.2614572,
                'contact_phone' => null,
                'contact_email' => null,
                'capacity' => null,
                'is_active' => true,
                'is_hq' => false,
                'can_administer_system' => false,
            ],
            [
                'name' => 'Tamale Hub',
                'code' => 'WH-005',
                'address' => 'Tamale',
                'region_id' => $northern?->id,
                'latitude' => 9.4051992,
                'longitude' => -0.8423986,
                'contact_phone' => null,
                'contact_email' => null,
                'capacity' => null,
                'is_active' => true,
                'is_hq' => false,
                'can_administer_system' => false,
            ],
            [
                'name' => 'Takoradi Hub',
                'code' => 'WH-006',
                'address' => 'Sekondi-Takoradi',
                'region_id' => $western?->id,
                'latitude' => 4.9274560,
                'longitude' => -1.7490216,
                'contact_phone' => null,
                'contact_email' => null,
                'capacity' => null,
                'is_active' => true,
                'is_hq' => false,
                'can_administer_system' => false,
            ],
            [
                'name' => 'Ho Hub',
                'code' => 'WH-007',
                'address' => 'Ho',
                'region_id' => $volta?->id,
                'latitude' => 6.6126598,
                'longitude' => 0.4688932,
                'contact_phone' => null,
                'contact_email' => null,
                'capacity' => null,
                'is_active' => true,
                'is_hq' => false,
                'can_administer_system' => false,
            ],
        ];

        foreach ($warehouses as $data) {
            Warehouse::updateOrCreate(
                ['code' => $data['code']],
                $data
            );
        }

        $this->command->info('✓ Successfully seeded ' . count($warehouses) . ' warehouses.');
    }
}
