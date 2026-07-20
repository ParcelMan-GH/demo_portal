<?php

namespace App\Providers;

use App\Events\DeliveryRunStopStatusChanged;
use App\Events\DriverAssignedToPickup;
use App\Events\DriverAssignedToTransport;
use App\Events\DriverAssignedToDelivery;
use App\Events\DriverUnassignedFromPickup;
use App\Events\DriverUnassignedFromTransport;
use App\Events\DriverUnassignedFromDelivery;
use App\Events\PickupAssignmentStatusChanged;
use App\Events\ShipmentCollected;
use App\Events\ShipmentReadyForCollection;
use App\Events\ShipmentStatusChanged;
use App\Events\TransportManifestStatusChanged;
use App\Events\VendorRegistered;
use App\Events\WalkinShipmentReceived;
use App\Listeners\SendAdminDeliveryNotification;
use App\Listeners\SendAdminPickupNotification;
use App\Listeners\SendAdminShipmentNotification;
use App\Listeners\SendAdminTransportNotification;
use App\Listeners\SendAdminVendorRegisteredNotification;
use App\Listeners\SendDriverAssignmentNotification;
use App\Listeners\SendDriverTransportNotification;
use App\Listeners\SendDriverDeliveryNotification;
use App\Listeners\SendDriverUnassignedNotification;
use App\Listeners\SendCollectionNotifications;
use App\Listeners\SendCustomerEmailTemplateNotification;
use App\Listeners\SendVendorShipmentNotification;
use App\Listeners\SendWalkinShipmentNotifications;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Shipment events
        ShipmentStatusChanged::class => [
            SendVendorShipmentNotification::class,
            SendAdminShipmentNotification::class,
            SendCustomerEmailTemplateNotification::class,
        ],

        // Pickup events
        DriverAssignedToPickup::class => [
            SendDriverAssignmentNotification::class,
            SendCustomerEmailTemplateNotification::class,
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

        DriverAssignedToDelivery::class => [
            SendDriverDeliveryNotification::class,
        ],
        DriverUnassignedFromDelivery::class => [
            SendDriverUnassignedNotification::class,
        ],

        // Delivery run events
        DeliveryRunStopStatusChanged::class => [
            SendAdminDeliveryNotification::class,
            SendCustomerEmailTemplateNotification::class,
        ],

        // Walk-in shipment
        WalkinShipmentReceived::class => [
            SendWalkinShipmentNotifications::class,
        ],

        // Collection (self-pickup)
        ShipmentReadyForCollection::class => [
            SendCollectionNotifications::class,
            SendCustomerEmailTemplateNotification::class,
        ],
        ShipmentCollected::class => [
            SendCollectionNotifications::class,
        ],

        // Vendor registration
        VendorRegistered::class => [
            SendAdminVendorRegisteredNotification::class,
            SendCustomerEmailTemplateNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
