<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CREDENTIAL_KEYS = [
        'storage.s3.access_key',
        'storage.s3.secret_key',
    ];

    /**
     * Encrypt legacy plaintext Storj/S3 credential rows without changing values.
     */
    public function up(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        foreach (self::CREDENTIAL_KEYS as $key) {
            $setting = DB::table('platform_settings')->where('key', $key)->first();

            if (! $setting || (bool) $setting->is_encrypted) {
                continue;
            }

            DB::table('platform_settings')
                ->where('key', $key)
                ->update([
                    'value' => filled($setting->value) ? Crypt::encryptString($setting->value) : $setting->value,
                    'is_encrypted' => true,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('platform_settings')) {
            return;
        }

        foreach (self::CREDENTIAL_KEYS as $key) {
            $setting = DB::table('platform_settings')->where('key', $key)->first();

            if (! $setting || ! (bool) $setting->is_encrypted) {
                continue;
            }

            try {
                $value = filled($setting->value) ? Crypt::decryptString($setting->value) : $setting->value;
            } catch (Throwable) {
                continue;
            }

            DB::table('platform_settings')
                ->where('key', $key)
                ->update([
                    'value' => $value,
                    'is_encrypted' => false,
                    'updated_at' => now(),
                ]);
        }
    }
};
