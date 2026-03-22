<?php

namespace App\Listeners;

use App\Events\ShipmentCollected;
use App\Events\ShipmentReadyForCollection;
use App\Services\PushNotificationService;
use App\Services\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCollectionNotifications implements ShouldQueue
{
    public function __construct(
        private PushNotificationService $pushService,
        private SmsService $smsService,
    ) {}

    public function handle(ShipmentReadyForCollection|ShipmentCollected $event): void
    {
        $shipment = $event->shipment;
        $collection = $event->collection;
        $vendor = $shipment->vendor;
        $warehouse = $collection->warehouse;
        $shipmentNumber = $shipment->shipment_number;

        if ($event instanceof ShipmentReadyForCollection) {
            $this->handleReadyForCollection($shipment, $warehouse, $vendor, $shipmentNumber);
        } else {
            $this->handleCollected($shipment, $collection, $vendor, $shipmentNumber);
        }
    }

    private function handleReadyForCollection($shipment, $warehouse, $vendor, string $shipmentNumber): void
    {
        // SMS to recipient
        $recipientPhone = $shipment->delivery_recipient_phone;
        if (!$recipientPhone && $shipment->items->isNotEmpty()) {
            $recipientPhone = $shipment->items->first()->delivery_recipient_phone;
        }

        if ($recipientPhone) {
            $warehouseAddress = $warehouse->address ?: $warehouse->name;
            $this->smsService->send(
                $recipientPhone,
                "Your package ({$shipmentNumber}) is ready for collection at {$warehouse->name}, {$warehouseAddress}. Please bring a valid ID."
            );
        }

        // Push to vendor
        if ($vendor?->fcm_token) {
            $this->pushService->sendToVendor(
                vendor: $vendor,
                title: 'Ready for Collection',
                body: "Shipment {$shipmentNumber} is ready for collection at {$warehouse->name}.",
                data: [
                    'shipment_id'     => (string) $shipment->id,
                    'shipment_number' => $shipmentNumber,
                    'warehouse'       => $warehouse->name,
                ],
                type: 'shipment_ready_for_collection'
            );
        }
    }

    private function handleCollected($shipment, $collection, $vendor, string $shipmentNumber): void
    {
        // SMS to recipient
        $recipientPhone = $collection->collected_by_phone;
        if ($recipientPhone) {
            $this->smsService->send(
                $recipientPhone,
                "Your package ({$shipmentNumber}) has been collected successfully. Thank you for using Parcelman Express."
            );
        }

        // Push to vendor
        if ($vendor?->fcm_token) {
            $this->pushService->sendToVendor(
                vendor: $vendor,
                title: 'Shipment Collected',
                body: "Shipment {$shipmentNumber} has been collected by {$collection->collected_by_name}.",
                data: [
                    'shipment_id'     => (string) $shipment->id,
                    'shipment_number' => $shipmentNumber,
                    'collected_by'    => $collection->collected_by_name,
                ],
                type: 'shipment_collected'
            );
        }
    }
}
