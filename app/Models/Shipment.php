<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use App\Helpers\PhoneHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shipment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'vendor_id',
        'shipment_number',
        'status',
        'recipient_name',
        'recipient_phone',
        'region_id',
        'district_id',
        'town',
        'latitude',
        'longitude',
        'gh_post_address',
        'delivery_instructions',
        'landmark',
        'submitted_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ShipmentStatus::class,
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->shipment_number)) {
                $shipment->shipment_number = static::generateShipmentNumber();
            }

            // Format phone number
            if (!empty($shipment->recipient_phone)) {
                $shipment->recipient_phone = PhoneHelper::format($shipment->recipient_phone);
            }
        });

        static::updating(function ($shipment) {
            // Format phone number on update
            if ($shipment->isDirty('recipient_phone') && !empty($shipment->recipient_phone)) {
                $shipment->recipient_phone = PhoneHelper::format($shipment->recipient_phone);
            }
        });
    }

    /**
     * Generate a unique shipment number.
     */
    public static function generateShipmentNumber(): string
    {
        $prefix = PlatformSetting::getValue('shipment.number_prefix', 'PCM');
        $length = (int) PlatformSetting::getValue('shipment.number_length', 5);
        $year = date('Y');

        // Get the last shipment number for this year
        $lastShipment = static::withTrashed()
            ->where('shipment_number', 'like', "{$prefix}-{$year}-%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastShipment) {
            // Extract the number part and increment
            $parts = explode('-', $lastShipment->shipment_number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf("%s-%s-%0{$length}d", $prefix, $year, $nextNumber);
    }

    /**
     * Get the vendor that owns this shipment.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get the region for this shipment.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * Get the district for this shipment.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Get the items in this shipment.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    /**
     * Get the invoice for this shipment.
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Get the latest pickup assignment for this shipment.
     */
    public function pickupAssignment(): HasOne
    {
        return $this->hasOne(PickupAssignment::class)->latestOfMany();
    }

    /**
     * Get all pickup assignments for this shipment.
     */
    public function pickupAssignments(): HasMany
    {
        return $this->hasMany(PickupAssignment::class);
    }

    /**
     * Check if shipment can be invoiced.
     */
    public function canBeInvoiced(): bool
    {
        return $this->status === ShipmentStatus::SUBMITTED;
    }

    /**
     * Check if shipment can have a driver assigned.
     */
    public function canBeAssigned(): bool
    {
        return $this->status === ShipmentStatus::INVOICE_ACCEPTED;
    }

    /**
     * Check if shipment can be edited.
     */
    public function canBeEdited(): bool
    {
        return $this->status->canBeEdited();
    }

    /**
     * Check if shipment can be deleted.
     */
    public function canBeDeleted(): bool
    {
        return $this->status->canBeDeleted();
    }

    /**
     * Check if shipment can be submitted.
     */
    public function canBeSubmitted(): bool
    {
        return $this->status->canBeSubmitted() && $this->items()->count() > 0;
    }

    /**
     * Check if shipment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    /**
     * Get the location type.
     */
    public function getLocationTypeAttribute(): string
    {
        if ($this->region_id && $this->district_id) {
            return 'dropdown';
        } elseif ($this->latitude && $this->longitude) {
            return 'coordinates';
        } elseif ($this->gh_post_address) {
            return 'gh_post';
        }

        return 'unknown';
    }

    /**
     * Get formatted location array.
     */
    public function getFormattedLocationAttribute(): array
    {
        return [
            'type' => $this->location_type,
            'region' => $this->region?->name,
            'region_id' => $this->region_id,
            'district' => $this->district?->name,
            'district_id' => $this->district_id,
            'town' => $this->town,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'gh_post_address' => $this->gh_post_address,
            'landmark' => $this->landmark,
        ];
    }
}
