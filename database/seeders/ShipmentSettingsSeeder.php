<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class ShipmentSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Storage Settings
        $this->setDefaultValue(
            'storage.driver',
            'local',
            encrypt: false,
            description: 'Storage driver: local or s3'
        );

        $this->setEncryptedDefaultValue(
            'storage.s3.access_key',
            '',
            description: 'S3/Storj access key'
        );

        $this->setEncryptedDefaultValue(
            'storage.s3.secret_key',
            '',
            description: 'S3/Storj secret key'
        );

        $this->setDefaultValue(
            'storage.s3.bucket',
            '',
            encrypt: false,
            description: 'S3/Storj bucket name'
        );

        $this->setDefaultValue(
            'storage.s3.endpoint',
            '',
            encrypt: false,
            description: 'S3/Storj endpoint URL'
        );

        $this->setDefaultValue(
            'storage.s3.region',
            'us-east-1',
            encrypt: false,
            description: 'S3/Storj signing region'
        );

        $this->setDefaultValue(
            'storage.s3.env',
            'demo',
            encrypt: false,
            description: 'Environment folder (demo/prod)'
        );

        $this->setDefaultValue(
            'storage.s3.signed_url_expiry',
            '60',
            encrypt: false,
            description: 'Signed URL expiry in minutes'
        );

        // Shipment Settings
        PlatformSetting::setValue(
            'shipment.max_images_per_item',
            '5',
            encrypt: false,
            description: 'Maximum images allowed per shipment item'
        );

        PlatformSetting::setValue(
            'shipment.number_prefix',
            'PCM',
            encrypt: false,
            description: 'Shipment number prefix'
        );

        PlatformSetting::setValue(
            'shipment.number_length',
            '5',
            encrypt: false,
            description: 'Shipment number padding length'
        );

        PlatformSetting::setValue(
            'shipment.tracking_prefix',
            'TRK',
            encrypt: false,
            description: 'Item tracking code prefix'
        );
    }

    private function setDefaultValue(string $key, mixed $value, bool $encrypt = false, ?string $description = null): void
    {
        if (PlatformSetting::query()->where('key', $key)->exists()) {
            return;
        }

        PlatformSetting::setValue($key, $value, $encrypt, $description);
    }

    private function setEncryptedDefaultValue(string $key, mixed $value, ?string $description = null): void
    {
        $setting = PlatformSetting::query()->where('key', $key)->first();

        if (! $setting) {
            PlatformSetting::setValue($key, $value, encrypt: true, description: $description);

            return;
        }

        if (! $setting->is_encrypted) {
            PlatformSetting::setValue($key, $setting->value ?? '', encrypt: true, description: $setting->description ?: $description);
        }
    }
}
