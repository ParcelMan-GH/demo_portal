<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case PROCESSING = 'processing';
    case PICKUP_ASSIGNED = 'pickup_assigned';
    case PICKED_UP = 'picked_up';
    case AT_WAREHOUSE = 'at_warehouse';
    case SORTED = 'sorted';
    case IN_TRANSIT = 'in_transit';
    case AT_DESTINATION = 'at_destination';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case HANDED_TO_COURIER = 'handed_to_courier';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';

    /**
     * Get human-readable label for status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::PROCESSING => 'Processing',
            self::PICKUP_ASSIGNED => 'Pickup Assigned',
            self::PICKED_UP => 'Picked Up',
            self::AT_WAREHOUSE => 'At Warehouse',
            self::SORTED => 'Sorted',
            self::IN_TRANSIT => 'In Transit',
            self::AT_DESTINATION => 'At Destination',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::HANDED_TO_COURIER => 'Handed to Courier',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Check if shipment can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if shipment can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if shipment can be submitted.
     */
    public function canBeSubmitted(): bool
    {
        return $this === self::DRAFT;
    }

    /**
     * Check if shipment can be cancelled.
     * Phase 3: Allow cancel through PICKUP_ASSIGNED (before driver picks up).
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SUBMITTED,
            self::PROCESSING,
            self::PICKUP_ASSIGNED,
        ]);
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
