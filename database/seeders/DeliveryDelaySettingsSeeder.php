<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class DeliveryDelaySettingsSeeder extends Seeder
{
    public function run(): void
    {
        PlatformSetting::setValue(
            'delivery_eta_grace_minutes',
            '30',
            encrypt: false,
            description: 'Minutes after package ETA before marking it overdue.'
        );

        PlatformSetting::setValue(
            'delivery_no_eta_threshold_hours',
            '4',
            encrypt: false,
            description: 'Hours after delivery dispatch before a package without ETA is flagged.'
        );
    }
}
