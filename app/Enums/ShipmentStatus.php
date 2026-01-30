<?php

namespace App\Enums;

enum ShipmentStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case INVOICE_SENT = 'invoice_sent';
    case INVOICE_ACCEPTED = 'invoice_accepted';
    case PICKUP_ASSIGNED = 'pickup_assigned';
    case PICKED_UP = 'picked_up';
    case AT_WAREHOUSE = 'at_warehouse';
    case SORTED = 'sorted';
    case IN_TRANSIT = 'in_transit';
    case AT_DESTINATION = 'at_destination';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    /**
     * Get human-readable label for status.
     */
    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::INVOICE_SENT => 'Invoice Sent',
            self::INVOICE_ACCEPTED => 'Invoice Accepted',
            self::PICKUP_ASSIGNED => 'Pickup Assigned',
            self::PICKED_UP => 'Picked Up',
            self::AT_WAREHOUSE => 'At Warehouse',
            self::SORTED => 'Sorted',
            self::IN_TRANSIT => 'In Transit',
            self::AT_DESTINATION => 'At Destination',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
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
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SUBMITTED,
            self::INVOICE_SENT,
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
