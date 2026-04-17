<?php

namespace App\Enums;

enum ItemStatus: string
{
    case PENDING = 'pending';
    case PICKED_UP = 'picked_up';
    case AT_WAREHOUSE = 'at_warehouse';
    case SORTED = 'sorted';
    case IN_TRANSIT = 'in_transit';
    case AT_DESTINATION = 'at_destination';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case HANDED_TO_COURIER = 'handed_to_courier';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';

    /**
     * Get human-readable label for status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PICKED_UP => 'Picked Up',
            self::AT_WAREHOUSE => 'At Warehouse',
            self::SORTED => 'Sorted',
            self::IN_TRANSIT => 'In Transit',
            self::AT_DESTINATION => 'At Destination',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::HANDED_TO_COURIER => 'Handed to Courier',
            self::DELIVERED => 'Delivered',
            self::RETURNED => 'Returned',
        };
    }

    /**
     * Get all statuses as array for dropdowns.
     */
    public static function toArray(): array
    {
        return array_map(
            fn(self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
