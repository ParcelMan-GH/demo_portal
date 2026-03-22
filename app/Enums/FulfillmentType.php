<?php

namespace App\Enums;

enum FulfillmentType: string
{
    case WAREHOUSE = 'warehouse';
    case SELF_PICKUP = 'self_pickup';
    case DIRECT = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::WAREHOUSE => 'Warehouse Delivery',
            self::SELF_PICKUP => 'Self Pickup',
            self::DIRECT => 'Direct Delivery',
        };
    }

    public function isSelfPickup(): bool
    {
        return $this === self::SELF_PICKUP;
    }

    public function isDirect(): bool
    {
        return $this === self::DIRECT;
    }

    public function isWarehouse(): bool
    {
        return $this === self::WAREHOUSE;
    }
}
