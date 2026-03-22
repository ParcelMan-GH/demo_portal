<?php

namespace App\Enums;

enum ShipmentSource: string
{
    case VENDOR_APP = 'vendor_app';
    case ADMIN_WALKIN = 'admin_walkin';
    case WAREHOUSE_WALKIN = 'warehouse_walkin';

    public function label(): string
    {
        return match ($this) {
            self::VENDOR_APP => 'Vendor App',
            self::ADMIN_WALKIN => 'Walk-in (Admin)',
            self::WAREHOUSE_WALKIN => 'Walk-in (Warehouse)',
        };
    }

    public function isWalkin(): bool
    {
        return $this !== self::VENDOR_APP;
    }
}
