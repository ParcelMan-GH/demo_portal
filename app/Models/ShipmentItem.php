<?php

namespace App\Models;

use App\Enums\FulfillmentType;
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
        'delivery_recipient_name',
        'delivery_recipient_phone',
        'delivery_region_id',
        'delivery_district_id',
        'delivery_town',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_gh_post_address',
        'delivery_landmark',
        'delivery_instructions',
        'fulfillment_type',
        'bus_station_id',
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
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'fulfillment_type' => FulfillmentType::class,
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

    public function deliveryRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'delivery_region_id');
    }

    public function deliveryDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'delivery_district_id');
    }

    public function busStation(): BelongsTo
    {
        return $this->belongsTo(BusStation::class);
    }

    /**
     * Get the tracking history for this item.
     */
    public function tracking(): HasMany
    {
        return $this->hasMany(ShipmentItemTracking::class)->orderBy('created_at', 'desc');
    }

    public function pickupConfirmations(): HasMany
    {
        return $this->hasMany(PickupItemConfirmation::class);
    }

    public function warehouseReceiptItems(): HasMany
    {
        return $this->hasMany(WarehouseReceiptItem::class);
    }

    public function sortBatchItems(): HasMany
    {
        return $this->hasMany(SortBatchItem::class);
    }

    public function transportManifestItems(): HasMany
    {
        return $this->hasMany(TransportManifestItem::class);
    }

    public function deliveryRunItems(): HasMany
    {
        return $this->hasMany(DeliveryRunItem::class);
    }

    public function getDeliveryLocationTypeAttribute(): string
    {
        if ($this->delivery_region_id && $this->delivery_district_id) {
            return 'dropdown';
        }

        if ($this->delivery_latitude && $this->delivery_longitude) {
            return 'coordinates';
        }

        if ($this->delivery_gh_post_address) {
            return 'gh_post';
        }

        return 'unknown';
    }

    public function getFormattedDeliveryLocationAttribute(): array
    {
        return [
            'type' => $this->delivery_location_type,
            'region' => $this->deliveryRegion?->name,
            'region_id' => $this->delivery_region_id,
            'district' => $this->deliveryDistrict?->name,
            'district_id' => $this->delivery_district_id,
            'town' => $this->delivery_town,
            'latitude' => $this->delivery_latitude,
            'longitude' => $this->delivery_longitude,
            'gh_post_address' => $this->delivery_gh_post_address,
            'landmark' => $this->delivery_landmark,
        ];
    }
}
