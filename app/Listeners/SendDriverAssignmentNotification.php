<?php

namespace App\Listeners;

use App\Events\DriverAssignedToPickup;
use App\Services\PushNotificationService;

class SendDriverAssignmentNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(DriverAssignedToPickup $event): void
    {
        $assignment = $event->assignment;
        $driver     = $event->driver;

        $assignment->loadMissing(['shipment', 'targetWarehouse']);

        $shipmentNumber = $assignment->shipment?->shipment_number ?? 'N/A';
        $warehouseName  = $assignment->targetWarehouse?->name;

        $body = $warehouseName
            ? "Pick up parcel {$shipmentNumber} and deliver it to {$warehouseName}."
            : "You have been assigned to pick up parcel {$shipmentNumber}.";

        $this->pushService->sendToDriver(
            driver: $driver,
            title: 'New Pickup Assignment',
            body: $body,
            data: [
                'pickup_id'        => (string) $assignment->id,
                'assignment_id'    => (string) $assignment->id,
                'shipment_number'  => $shipmentNumber,
            ],
            type: 'pickup_assigned'
        );

        // 2. Notify warehouse managers at the target warehouse
        if ($assignment->targetWarehouse) {
            $driverName = $driver->name ?? 'A rider';

            $this->pushService->sendToWarehouseManagers(
                warehouse: $assignment->targetWarehouse,
                title: 'Incoming Pickup',
                body: "{$driverName} is assigned to pick up shipment {$shipmentNumber} for your warehouse.",
                data: [
                    'assignment_id'   => (string) $assignment->id,
                    'shipment_number' => $shipmentNumber,
                ],
                type: 'pickup_incoming'
            );
        }
    }
}
