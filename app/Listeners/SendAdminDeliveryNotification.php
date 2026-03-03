<?php

namespace App\Listeners;

use App\Events\DeliveryRunStopStatusChanged;
use App\Services\PushNotificationService;

class SendAdminDeliveryNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(DeliveryRunStopStatusChanged $event): void
    {
        $stop = $event->stop;
        $recipientName = $stop->recipient_name ?? 'Recipient';
        $runNumber = $stop->run?->run_number ?? 'Unknown';

        [$title, $body] = match ($event->newStatus) {
            'delivered' => [
                'Stop Delivered',
                "Delivery stop for {$recipientName} on run {$runNumber} was successfully delivered.",
            ],
            'failed' => [
                'Delivery Stop Failed',
                "Delivery stop for {$recipientName} on run {$runNumber} has failed. Reason: " . ($stop->failure_reason ?? 'Not specified'),
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
                'stop_id'        => (string) $stop->id,
                'run_id'         => (string) $stop->delivery_run_id,
                'status'         => $event->newStatus,
                'url'            => '/admin/delivery-runs/' . $stop->delivery_run_id,
            ],
            type: 'delivery_stop_status'
        );
    }
}
