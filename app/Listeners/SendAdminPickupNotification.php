<?php

namespace App\Listeners;

use App\Events\PickupAssignmentStatusChanged;
use App\Services\PushNotificationService;

class SendAdminPickupNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(PickupAssignmentStatusChanged $event): void
    {
        $assignment = $event->assignment;
        $shipmentNumber = $assignment->shipment?->shipment_number ?? 'Unknown';
        $driverName = $assignment->driver?->name ?? 'Rider';

        [$title, $body] = match ($event->newStatus) {
            'arrived' => [
                'Rider Arrived at Pickup',
                "{$driverName} has arrived at the pickup location for shipment {$shipmentNumber}.",
            ],
            'completed' => [
                'Pickup Completed',
                "{$driverName} has completed the pickup of shipment {$shipmentNumber}.",
            ],
            default => [null, null],
        };

        if (!$title) {
            return;
        }

        $this->pushService->sendToAllAdmins(
            title: $title,
            body: $body,
            data: [
                'assignment_id'   => (string) $assignment->id,
                'shipment_number' => $shipmentNumber,
                'status'          => $event->newStatus,
                'url'             => '/admin/shipments/' . ($assignment->shipment_id ?? ''),
            ],
            type: 'pickup_status'
        );
    }
}
