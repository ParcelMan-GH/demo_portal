<?php

namespace App\Listeners;

use App\Events\DriverAssignedToDelivery;
use App\Services\PushNotificationService;

class SendDriverDeliveryNotification
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(DriverAssignedToDelivery $event): void
    {
        $run = $event->run->loadMissing('warehouse');
        $runNumber = $run->run_number ?: "Run #{$run->id}";
        $warehouse = $run->warehouse?->name;

        $this->pushService->sendToDriver(
            driver: $event->driver,
            title: 'New Delivery Assignment',
            body: $warehouse
                ? "You have been assigned delivery run {$runNumber} from {$warehouse}."
                : "You have been assigned delivery run {$runNumber}.",
            data: [
                'delivery_run_id' => (string) $run->id,
                'run_number' => $runNumber,
            ],
            type: 'delivery_assigned',
        );
    }
}
