<?php

namespace App\Enums;

/**
 * How well a shipment's requested pickup vehicle slots are covered by its
 * assignments.
 *
 * This is a different axis from PickupAssignmentStatus, which tracks one
 * rider's own lifecycle (assigned → en_route → … → completed). A shipment can
 * be PARTIALLY_ASSIGNED while every one of its riders is happily ASSIGNED.
 * Coverage is derived from the assignments; it is never stored.
 */
enum PickupCoverageStatus: string
{
    case UNASSIGNED = 'unassigned';
    case PARTIALLY_ASSIGNED = 'partially_assigned';
    case FULLY_ASSIGNED = 'fully_assigned';

    public function label(): string
    {
        return match ($this) {
            self::UNASSIGNED => 'Unassigned',
            self::PARTIALLY_ASSIGNED => 'Partially Assigned',
            self::FULLY_ASSIGNED => 'Fully Assigned',
        };
    }

    public static function toArray(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases()
        );
    }
}
