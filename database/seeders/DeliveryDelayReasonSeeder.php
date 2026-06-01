<?php

namespace Database\Seeders;

use App\Models\DeliveryDelayReason;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeliveryDelayReasonSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'Traffic delay',
            'Recipient requested later delivery',
            'Weather delay',
            'Vehicle issue',
            'Route delay',
            'Other',
        ];

        foreach ($defaults as $index => $label) {
            DeliveryDelayReason::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($label)],
                [
                    'label' => $label,
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'deleted_at' => null,
                ],
            );
        }
    }
}
