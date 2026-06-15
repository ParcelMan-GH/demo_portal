<?php

namespace App\Listeners;

use App\Events\ShipmentStatusChanged;
use App\Models\AdminNotification;
use App\Services\AdminInAppNotificationService;
use App\Services\PushNotificationService;

class SendAdminShipmentNotification
{
    public function __construct(
        private PushNotificationService $pushService,
        private AdminInAppNotificationService $inAppNotifications,
    ) {}

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

        if (! $title) {
            return;
        }

        $notifications = $this->inAppNotifications->notifyShipmentSubmitted($shipment, $title, $body);

        $notifications->each(function (AdminNotification $notification) use ($shipment, $event): void {
            $user = $notification->user;

            if (! $user?->fcm_token) {
                return;
            }

            $this->pushService->sendToAdmin(
                user: $user,
                title: $notification->title,
                body: $notification->body,
                data: [
                    'notification_id' => (string) $notification->id,
                    'shipment_id' => (string) $shipment->id,
                    'shipment_number' => $shipment->shipment_number,
                    'status' => $event->newStatus,
                    'url' => $notification->url,
                ],
                type: 'shipment_submitted'
            );
        });
    }
}
