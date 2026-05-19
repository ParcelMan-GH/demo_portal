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
        $greaterAccra = Region::where('name', 'Greater Accra')->first();
        $ashanti = Region::where('name', 'Ashanti')->first();

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
