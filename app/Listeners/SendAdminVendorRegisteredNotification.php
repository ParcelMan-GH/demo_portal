<?php

namespace App\Listeners;

use App\Events\VendorRegistered;
use App\Services\PushNotificationService;

class SendAdminVendorRegisteredNotification 
{
    public function __construct(private PushNotificationService $pushService) {}

    public function handle(VendorRegistered $event): void
    {
        $vendor = $event->vendor;

        $this->pushService->sendToAllAdmins(
            title: 'New Vendor Registered',
            body: "{$vendor->name} has registered as a new vendor.",
            data: [
                'vendor_id'   => (string) $vendor->id,
                'vendor_name' => $vendor->name,
                'url'         => '/admin/vendors',
            ],
            type: 'vendor_registered'
        );
    }
}
