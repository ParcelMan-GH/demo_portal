<?php

namespace App\Enums;

enum ShipmentDestinationMode: string
{
    case SINGLE = 'single';
    case PER_ITEM = 'per_item';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single Destination',
            self::PER_ITEM => 'Per Item Destination',
        };
    }

    public static function toArray(): array
    {
        return array_map(
            fn(self $mode) => [
                'value' => $mode->value,
                'label' => $mode->label(),
            ],
            self::cases()
        );
    }
}

