<?php

namespace App\Enums;

enum PickupAssignmentStatus: string
{
    case ASSIGNED = 'assigned';
    case EN_ROUTE = 'en_route';
    case ARRIVED = 'arrived';
    case PICKING_UP = 'picking_up';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ASSIGNED => 'Assigned',
            self::EN_ROUTE => 'En Route',
            self::ARRIVED => 'Arrived',
            self::PICKING_UP => 'Picking Up',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

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
