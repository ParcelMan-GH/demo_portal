<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionTier;

class CommissionTierSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [
            ['min' => 2100.00, 'max' => 2199.99, 'payout' => 10.00],
            ['min' => 2200.00, 'max' => 2299.99, 'payout' => 20.00],
            ['min' => 2300.00, 'max' => 2399.99, 'payout' => 30.00],
            ['min' => 2400.00, 'max' => 2499.99, 'payout' => 40.00],
            ['min' => 2500.00, 'max' => 2599.99, 'payout' => 50.00],
            ['min' => 2600.00, 'max' => 2699.99, 'payout' => 60.00],
            ['min' => 2700.00, 'max' => 2799.99, 'payout' => 70.00],
            ['min' => 2800.00, 'max' => 2899.99, 'payout' => 80.00],
            ['min' => 2900.00, 'max' => 2999.99, 'payout' => 90.00],
            ['min' => 3000.00, 'max' => 3000.99, 'payout' => 100.00],
            ['min' => 3001.00, 'max' => 3500.99, 'payout' => 120.00],
            ['min' => 3501.00, 'max' => 4000.99, 'payout' => 150.00],
            // Anything above 4000 won't have a max, so they stay at the top tier
            ['min' => 4001.00, 'max' => null,    'payout' => 200.00], 
        ];

        foreach ($tiers as $tier) {
            CommissionTier::updateOrCreate(
                ['min_collection' => $tier['min']],
                [
                    'max_collection' => $tier['max'],
                    'payout_amount'  => $tier['payout'],
                    'is_active'      => true,
                ]
            );
        }
    }
}