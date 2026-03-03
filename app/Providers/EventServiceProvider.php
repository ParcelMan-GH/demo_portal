<?php

namespace App\Providers;

use App\Events\DeliveryRunStopStatusChanged;
use App\Events\DriverAssignedToPickup;
use App\Events\DriverAssignedToTransport;
use App\Events\DriverUnassignedFromPickup;
use App\Events\DriverUnassignedFromTransport;
use App\Events\InvoiceAcceptedByVendor;
use App\Events\InvoiceRejectedByVendor;
use App\Events\InvoiceSent;
use App\Events\PickupAssignmentStatusChanged;
use App\Events\ShipmentStatusChanged;
use App\Events\TransportManifestStatusChanged;
use App\Events\VendorRegistered;
use App\Listeners\SendAdminDeliveryNotification;
use App\Listeners\SendAdminInvoiceResponseNotification;
use App\Listeners\SendAdminPickupNotification;
use App\Listeners\SendAdminShipmentNotification;
use App\Listeners\SendAdminTransportNotification;
use App\Listeners\SendAdminVendorRegisteredNotification;
use App\Listeners\SendDriverAssignmentNotification;
use App\Listeners\SendDriverTransportNotification;
use App\Listeners\SendDriverUnassignedNotification;
use App\Listeners\SendVendorInvoiceNotification;
use App\Listeners\SendVendorShipmentNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Shipment events
        ShipmentStatusChanged::class => [
            SendVendorShipmentNotification::class,
            SendAdminShipmentNotification::class,
        ],

        // Pickup events
        DriverAssignedToPickup::class => [
            SendDriverAssignmentNotification::class,
        ],
        PickupAssignmentStatusChanged::class => [
            SendAdminPickupNotification::class,
        ],
        DriverUnassignedFromPickup::class => [
            SendDriverUnassignedNotification::class,
        ],

        // Transport manifest events
        TransportManifestStatusChanged::class => [
            SendAdminTransportNotification::class,
        ],
        DriverAssignedToTransport::class => [
            SendDriverTransportNotification::class,
        ],
        DriverUnassignedFromTransport::class => [
            SendDriverUnassignedNotification::class,
        ],

        // Delivery run events
        DeliveryRunStopStatusChanged::class => [
            SendAdminDeliveryNotification::class,
        ],

        // Invoice events
        InvoiceSent::class => [
            SendVendorInvoiceNotification::class,
        ],
        InvoiceAcceptedByVendor::class => [
            SendAdminInvoiceResponseNotification::class,
        ],
        InvoiceRejectedByVendor::class => [
            SendAdminInvoiceResponseNotification::class,
        ],

        // Vendor registration
        VendorRegistered::class => [
            SendAdminVendorRegisteredNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
