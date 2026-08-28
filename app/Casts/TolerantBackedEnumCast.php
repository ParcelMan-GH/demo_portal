<?php

namespace App\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Backed-enum cast that never throws on dirty data.
 *
 * Historical records may contain an empty string or an otherwise invalid
 * backing value (e.g. "" written before statuses were enforced). A plain
 * enum cast would raise ValueError on every attribute read, taking down
 * whole pages. This cast falls back to a safe default case instead so
 * views, endpoints and services keep working.
 */
class TolerantBackedEnumCast implements CastsAttributes
{
    public function __construct(
        protected readonly string $enumClass,
        protected readonly ?string $fallbackCase = null,
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?BackedEnum
    {
        if ($value === null || $value === '') {
            return $this->fallback();
        }

        return $this->enumClass::tryFrom((string) $value) ?? $this->fallback();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value === null || $value === '') {
            return $this->fallback()->value;
        }

        return $this->enumClass::tryFrom((string) $value)?->value ?? $this->fallback()->value;
    }

    protected function fallback(): BackedEnum
    {
        if ($this->fallbackCase) {
            return $this->enumClass::from($this->fallbackCase);
        }

        return $this->enumClass::cases()[0];
    }
}
