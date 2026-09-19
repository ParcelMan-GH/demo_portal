<?php

namespace App\Enums;

enum BatchDestinationType: string
{
    case COMMERCE = 'commerce';
    case RESIDENTIAL = 'residential';

    public function label(): string
    {
        return match ($this) {
            self::COMMERCE => 'Commerce',
            self::RESIDENTIAL => 'Residential',
        };
    }

    /**
     * A commerce batch only accepts commerce packages.
     */
    public function acceptsOnlyCommerce(): bool
    {
        return $this === self::COMMERCE;
    }

    /**
     * Resolve a stored/raw value to a case, tolerating null and unknown strings.
     */
    public static function fromValue(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return null;
        }

        return self::tryFrom(strtolower(trim($value)));
    }

    public static function toArray(): array
    {
        return array_map(
            fn (self $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ],
            self::cases()
        );
    }
}
