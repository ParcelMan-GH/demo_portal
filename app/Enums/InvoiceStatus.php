<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT => 'Sent',
            self::ACCEPTED => 'Accepted',
            self::REJECTED => 'Rejected',
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

    public function isActive(): bool
    {
        return in_array($this, self::activeStatuses(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function activeStatuses(): array
    {
        return [
            self::PENDING,
            self::SENT,
            self::ACCEPTED,
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function activeValues(): array
    {
        return array_map(
            fn(self $status) => $status->value,
            self::activeStatuses()
        );
    }
}
