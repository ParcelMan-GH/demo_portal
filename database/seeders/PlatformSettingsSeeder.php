<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Arkesel SMS Settings.
        //
        // SECURITY: a live Arkesel API key used to be hardcoded here
        // ('RmtnU3NFeHBJT2ZMWk9oZkp4Ymg'). This repository is public, so that
        // key was exposed to anyone and must be treated as compromised: rotate
        // it at Arkesel and supply the replacement via the environment. The
        // value now comes from ARKESEL_API_KEY, and an empty result fails
        // loudly with "Arkesel API key or Sender ID not configured" rather
        // than silently calling the provider with a dead credential.
        PlatformSetting::setValue(
            'arkesel_api_key',
            (string) env('ARKESEL_API_KEY', ''),
            encrypt: true,
            description: 'Arkesel SMS API Key'
        );

        PlatformSetting::setValue(
            'arkesel_sender_id',
            (string) env('ARKESEL_SENDER_ID', 'SHAXI'),
            encrypt: false,
            description: 'Arkesel SMS Sender ID'
        );
    }
}
