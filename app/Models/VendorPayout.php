<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayout extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_CONFIRMED = 'confirmed';

    protected $fillable = [
        'vendor_id',
        'amount',
        'status',
        'payment_method',
        'payment_reference',
        'payment_phone',
        'notes',
        'processed_by_admin_id',
        'sent_at',
        'confirmed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'processed_by_admin_id');
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(VendorEarning::class, 'payout_id');
    }
}
