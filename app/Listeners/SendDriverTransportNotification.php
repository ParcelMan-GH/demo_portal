<?php

namespace App\Listeners;

use App\Events\DriverAssignedToTransport;
use App\Services\PushNotificationService;

class SendDriverTransportNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(DriverAssignedToTransport $event): void
    {
        $driver = $event->driver;
        $manifest = $event->manifest;
        $manifestNumber = $manifest->manifest_number ?? "Manifest #{$manifest->id}";
        $destination = $manifest->destinationWarehouse?->name ?? 'the destination';

        $this->pushService->sendToDriver(
            driver: $driver,
            title: 'New Transport Assignment',
            body: "You have been assigned to transport manifest {$manifestNumber} to {$destination}.",
            data: [
                'transport_id'    => (string) $manifest->id,
                'manifest_id'     => (string) $manifest->id,
                'manifest_number' => $manifestNumber,
            ],
            type: 'transport_assigned'
        );
    }
}
