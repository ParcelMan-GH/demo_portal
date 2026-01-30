<?php

namespace App\Models;

use App\Enums\ItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ShipmentItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_id',
        'description',
        'quantity',
        'status',
        'tracking_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'status' => ItemStatus::class,
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 'pending',
        'quantity' => 1,
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate tracking code when item is picked up
        static::updating(function ($item) {
            if ($item->isDirty('status') &&
                $item->status === ItemStatus::PICKED_UP &&
                empty($item->tracking_code)) {
                $item->tracking_code = static::generateTrackingCode();
            }
        });
    }

    /**
     * Generate a unique tracking code.
     */
    public static function generateTrackingCode(): string
    {
        $prefix = PlatformSetting::getValue('shipment.tracking_prefix', 'TRK');

        do {
            $code = $prefix . strtoupper(Str::random(8));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }

    /**
     * Get the shipment this item belongs to.
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * Get the images for this item.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ShipmentItemImage::class)->orderBy('sort_order');
    }

    /**
     * Get the tracking history for this item.
     */
    public function tracking(): HasMany
    {
        return $this->hasMany(ShipmentItemTracking::class)->orderBy('created_at', 'desc');
    }
}
