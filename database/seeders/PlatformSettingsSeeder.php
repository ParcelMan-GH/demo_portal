<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Arkesel SMS Settings
        PlatformSetting::setValue(
            'arkesel_api_key',
            'RmtnU3NFeHBJT2ZMWk9oZkp4Ymg',
            encrypt: true,
            description: 'Arkesel SMS API Key'
        );

        PlatformSetting::setValue(
            'arkesel_sender_id',
            'SHAXI',
            encrypt: false,
            description: 'Arkesel SMS Sender ID'
        );
    }
}
