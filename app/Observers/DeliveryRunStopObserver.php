<?php

namespace App\Observers;

use App\Events\DeliveryRunStopStatusChanged;
use App\Models\DeliveryRunStop;

class DeliveryRunStopObserver
{
    public function updated(DeliveryRunStop $stop): void
    {
        if ($stop->wasChanged('status')) {
            $oldStatus = $stop->getOriginal('status');
            $newStatus = $stop->status;

            $oldValue = $oldStatus instanceof \BackedEnum ? $oldStatus->value : (string) $oldStatus;
            $newValue = $newStatus instanceof \BackedEnum ? $newStatus->value : (string) $newStatus;

            if (in_array($newValue, [DeliveryRunStop::STATUS_DELIVERED, DeliveryRunStop::STATUS_FAILED], true)) {
                event(new DeliveryRunStopStatusChanged($stop, $oldValue, $newValue));
            }
        }
    }
}
