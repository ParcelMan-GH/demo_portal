<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $settings = [
            ['key' => 'delivery_eta_grace_minutes', 'value' => '30'],
            ['key' => 'delivery_no_eta_threshold_hours', 'value' => '4'],
        ];

        foreach ($settings as $setting) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $setting['key']],
                ['value' => $setting['value'], 'updated_at' => $now, 'created_at' => $now]
            );
        }
    }

    public function down(): void
    {
        DB::table('platform_settings')->whereIn('key', [
            'delivery_eta_grace_minutes',
            'delivery_no_eta_threshold_hours',
        ])->delete();
    }
};
