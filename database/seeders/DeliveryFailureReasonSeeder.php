<?php

namespace Database\Seeders;

use App\Models\DeliveryFailureReason;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DeliveryFailureReasonSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['label' => 'Recipient unreachable', 'type' => DeliveryFailureReason::TYPE_NOT_RECEIVED],
            ['label' => 'Recipient says not received', 'type' => DeliveryFailureReason::TYPE_NOT_RECEIVED],
            ['label' => 'Wrong contact', 'type' => DeliveryFailureReason::TYPE_FAILED],
            ['label' => 'Courier delay', 'type' => DeliveryFailureReason::TYPE_ISSUE],
            ['label' => 'Package damaged', 'type' => DeliveryFailureReason::TYPE_ISSUE],
            ['label' => 'Other', 'type' => DeliveryFailureReason::TYPE_OTHER],
        ];

        foreach ($defaults as $index => $reason) {
            DeliveryFailureReason::withTrashed()->updateOrCreate(
                ['slug' => Str::slug($reason['label'])],
                [
                    'label' => $reason['label'],
                    'type' => $reason['type'],
                    'sort_order' => $index + 1,
                    'is_active' => true,
                    'deleted_at' => null,
                ],
            );
        }
    }
}
