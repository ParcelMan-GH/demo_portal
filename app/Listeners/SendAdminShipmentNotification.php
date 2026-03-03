<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Services\PushNotificationService;

class SendAdminShipmentNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(ShipmentStatusChanged $event): void
    {
        $shipment = $event->shipment;

        [$title, $body] = match ($event->newStatus) {
            'submitted' => [
                'New Shipment Submitted',
                "Vendor {$shipment->vendor?->name} submitted shipment {$shipment->shipment_number}.",
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
                'shipment_id'     => (string) $shipment->id,
                'shipment_number' => $shipment->shipment_number,
                'status'          => $event->newStatus,
                'url'             => '/admin/shipments/' . $shipment->id,
            ],
            type: 'shipment_submitted'
        );
    }
}
