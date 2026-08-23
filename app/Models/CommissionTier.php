<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CommissionTier extends Model
{
    protected $guarded = [];

    protected $casts = [
        'min_collection' => 'decimal:2',
        'max_collection' => 'decimal:2',
        'payout_amount'  => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    /**
     * Scope to only get active commission tiers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Find the correct commission tier for a specific collected amount.
     */
    public static function findTierForAmount(float $amount): ?self
    {
        return self::active()
            ->where('min_collection', '<=', $amount)
            ->where(function (Builder $query) use ($amount) {
                $query->whereNull('max_collection')
                      ->orWhere('max_collection', '>=', $amount);
            })
            ->orderByDesc('min_collection')
            ->first();
    }
}