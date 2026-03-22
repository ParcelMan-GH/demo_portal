<?php

namespace App\Listeners;

use App\Events\WalkinShipmentReceived;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWalkinShipmentNotifications implements ShouldQueue
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(WalkinShipmentReceived $event): void
    {
        $shipment = $event->shipment;
        $warehouse = $event->warehouse;
        $actor = $event->receivedBy->name;
        $vendor = $shipment->vendor;
        $shipmentNumber = $shipment->shipment_number;
        $vendorName = $vendor?->name ?? 'Unknown vendor';

        // Notify super admins
        $this->pushService->sendToSuperAdmins(
            title: 'Walk-in Shipment Received',
            body: "{$actor} received walk-in shipment {$shipmentNumber} from {$vendorName} at {$warehouse->name}.",
            data: [
                'shipment_id'     => (string) $shipment->id,
                'shipment_number' => $shipmentNumber,
                'warehouse'       => $warehouse->name,
                'vendor'          => $vendorName,
                'actor'           => $actor,
                'url'             => '/admin/shipments/' . $shipment->id,
            ],
            type: 'walkin_shipment_received'
        );

        // Notify vendor (if they have FCM token)
        if ($vendor?->fcm_token) {
            $this->pushService->sendToVendor(
                vendor: $vendor,
                title: 'Shipment Received',
                body: "Your shipment {$shipmentNumber} has been received at {$warehouse->name}.",
                data: [
                    'shipment_id'     => (string) $shipment->id,
                    'shipment_number' => $shipmentNumber,
                ],
                type: 'walkin_shipment_received'
            );
        }
    }
}
