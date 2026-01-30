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
        PlatformSetting::setValue(
            'storage.driver',
            's3',
            encrypt: false,
            description: 'Storage driver: local or s3'
        );

        PlatformSetting::setValue(
            'storage.s3.access_key',
            'jxewql44cxak3xiwep3dwopqv6ra',
            encrypt: true,
            description: 'S3/Storj access key'
        );

        PlatformSetting::setValue(
            'storage.s3.secret_key',
            'jy3nptxgm6jqoy2wchydfqyon2ljxnq6e2xkmfby5pmdjbdfx3dbs',
            encrypt: true,
            description: 'S3/Storj secret key'
        );

        PlatformSetting::setValue(
            'storage.s3.bucket',
            'shaxi',
            encrypt: false,
            description: 'S3/Storj bucket name'
        );

        PlatformSetting::setValue(
            'storage.s3.endpoint',
            'https://gateway.storjshare.io',
            encrypt: false,
            description: 'S3/Storj endpoint URL'
        );

        PlatformSetting::setValue(
            'storage.s3.env',
            'demo',
            encrypt: false,
            description: 'Environment folder (demo/prod)'
        );

        PlatformSetting::setValue(
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
}
