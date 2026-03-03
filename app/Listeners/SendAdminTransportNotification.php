<?php

namespace App\Listeners;

use App\Events\TransportManifestStatusChanged;
use App\Services\PushNotificationService;

class SendAdminTransportNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(TransportManifestStatusChanged $event): void
    {
        $manifest = $event->manifest;
        $manifestNumber = $manifest->manifest_number;
        $driverName = $manifest->assignedDriver?->name ?? 'Driver';
        $destination = $manifest->destinationWarehouse?->name ?? 'destination';

        [$title, $body] = match ($event->newStatus) {
            'loading' => [
                'Driver Loading Transport',
                "{$driverName} is loading manifest {$manifestNumber} for transport.",
            ],
            'in_transit' => [
                'Transport Departed',
                "Manifest {$manifestNumber} has departed to {$destination}.",
            ],
            'arrived' => [
                'Transport Arrived at Warehouse',
                "Manifest {$manifestNumber} has arrived at {$destination}.",
            ],
            'received' => [
                'Warehouse Received Shipment',
                "Manifest {$manifestNumber} has been received at {$destination}.",
            ],
            default => [null, null],
        };

        if (!$title) {
            return;
        }

        // Send to warehouse admins + super admins (sendToWarehouseAdmins covers both)
        $this->pushService->sendToWarehouseAdmins(
            title: $title,
            body: $body,
            data: [
                'manifest_id'     => (string) $manifest->id,
                'manifest_number' => $manifestNumber,
                'status'          => $event->newStatus,
                'url'             => '/admin/transport-manifests/' . $manifest->id,
            ],
            type: 'transport_status'
        );
    }
}
