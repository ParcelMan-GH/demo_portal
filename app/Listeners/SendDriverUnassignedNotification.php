<?php

namespace App\Listeners;

use App\Enums\PickupAssignmentStatus;
use App\Events\DriverUnassignedFromPickup;
use App\Events\DriverUnassignedFromTransport;
use App\Events\DriverUnassignedFromDelivery;
use App\Services\PushNotificationService;

class SendDriverUnassignedNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(DriverUnassignedFromPickup|DriverUnassignedFromTransport|DriverUnassignedFromDelivery $event): void
    {
        $driver = $event->driver;

        if ($event instanceof DriverUnassignedFromPickup) {
            $assignment = $event->assignment;
            $shipmentNumber = $assignment->shipment?->shipment_number ?? 'Unknown';
            $isCancelled = $assignment->status === PickupAssignmentStatus::CANCELLED;
            $title = $isCancelled ? 'Pickup Assignment Cancelled' : 'Pickup Assignment Removed';
            $body = "You have been unassigned from pickup for parcel {$shipmentNumber}.";
            if ($event->reason) {
                $body .= " Reason: {$event->reason}";
            }
            $data = [
                'pickup_id'       => (string) $assignment->id,
                'assignment_id'   => (string) $assignment->id,
                'shipment_number' => $shipmentNumber,
            ];

            $this->pushService->sendToDriver(
                driver: $driver,
                title: $title,
                body: $body,
                data: $data,
                type: 'pickup_unassigned'
            );

            // A reassignment is followed by a new assignment event; it must not look cancelled.
            $assignment->loadMissing('targetWarehouse');
            if ($isCancelled && $assignment->targetWarehouse) {
                $this->pushService->sendToWarehouseManagers(
                    warehouse: $assignment->targetWarehouse,
                    title: 'Pickup Assignment Cancelled',
                    body: "Pickup for shipment {$shipmentNumber} has been cancelled.",
                    data: $data,
                    type: 'pickup_cancelled'
                );
            }
        } elseif ($event instanceof DriverUnassignedFromTransport) {
            $manifest = $event->manifest;
            $manifestNumber = $manifest->manifest_number ?? "Manifest #{$manifest->id}";
            $title = 'Transport Assignment Removed';
            $body = "You have been unassigned from transport manifest {$manifestNumber}.";
            if ($event->reason) {
                $body .= " Reason: {$event->reason}";
            }
            $data = [
                'transport_id'    => (string) $manifest->id,
                'manifest_id'     => (string) $manifest->id,
                'manifest_number' => $manifestNumber,
            ];

            $this->pushService->sendToDriver(
                driver: $driver,
                title: $title,
                body: $body,
                data: $data,
                type: 'transport_unassigned'
            );
        } else {
            $run = $event->run;
            $runNumber = $run->run_number ?: "Run #{$run->id}";
            $body = "You have been unassigned from delivery run {$runNumber}.";
            if ($event->reason) {
                $body .= " Reason: {$event->reason}";
            }

            $this->pushService->sendToDriver(
                driver: $driver,
                title: 'Delivery Assignment Removed',
                body: $body,
                data: [
                    'delivery_run_id' => (string) $run->id,
                    'run_number' => $runNumber,
                ],
                type: 'delivery_unassigned'
            );
        }
    }
}
