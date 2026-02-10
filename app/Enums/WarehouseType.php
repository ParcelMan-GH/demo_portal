<?php

namespace App\Enums;

enum WarehouseType: string
{
    case ORIGIN = 'origin';
    case DESTINATION = 'destination';
    case BOTH = 'both';

    public function label(): string
    {
        return match ($this) {
            self::ORIGIN => 'Origin',
            self::DESTINATION => 'Destination',
            self::BOTH => 'Both',
        };
    }

    public static function toArray(): array
    {
        return array_map(
            fn(self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
