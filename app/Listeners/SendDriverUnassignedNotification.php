<?php

namespace App\Listeners;

use App\Events\DriverUnassignedFromPickup;
use App\Events\DriverUnassignedFromTransport;
use App\Services\PushNotificationService;

class SendDriverUnassignedNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(DriverUnassignedFromPickup|DriverUnassignedFromTransport $event): void
    {
        $driver = $event->driver;

        if ($event instanceof DriverUnassignedFromPickup) {
            $assignment = $event->assignment;
            $shipmentNumber = $assignment->shipment?->shipment_number ?? 'Unknown';
            $title = 'Pickup Assignment Cancelled';
            $body = "You have been unassigned from pickup for shipment {$shipmentNumber}.";
            $data = [
                'assignment_id'   => (string) $assignment->id,
                'shipment_number' => $shipmentNumber,
            ];

            $this->pushService->sendToDriver(
                driver: $driver,
                title: $title,
                body: $body,
                data: $data,
                type: 'driver_unassigned'
            );

            // Also notify warehouse managers at the target warehouse
            $assignment->loadMissing('targetWarehouse');
            if ($assignment->targetWarehouse) {
                $this->pushService->sendToWarehouseManagers(
                    warehouse: $assignment->targetWarehouse,
                    title: 'Pickup Assignment Cancelled',
                    body: "Pickup for shipment {$shipmentNumber} has been cancelled.",
                    data: $data,
                    type: 'pickup_cancelled'
                );
            }
        } else {
            $manifest = $event->manifest;
            $manifestNumber = $manifest->manifest_number ?? "Manifest #{$manifest->id}";
            $title = 'Transport Assignment Cancelled';
            $body = "You have been unassigned from transport manifest {$manifestNumber}.";
            $data = [
                'manifest_id'     => (string) $manifest->id,
                'manifest_number' => $manifestNumber,
            ];

            $this->pushService->sendToDriver(
                driver: $driver,
                title: $title,
                body: $body,
                data: $data,
                type: 'driver_unassigned'
            );
        }
    }
}
