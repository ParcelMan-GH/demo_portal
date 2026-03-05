<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\ShipmentDestinationMode;
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
        'destination_mode',
        'current_invoice_id',
        'pickup_contact_name',
        'pickup_contact_phone',
        'pickup_region_id',
        'pickup_district_id',
        'pickup_town',
        'pickup_latitude',
        'pickup_longitude',
        'pickup_gh_post_address',
        'pickup_landmark',
        'pickup_instructions',
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
        'destination_mode' => ShipmentDestinationMode::class,
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
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

            if (empty($shipment->destination_mode)) {
                $shipment->destination_mode = ShipmentDestinationMode::SINGLE;
            }

            self::formatPhoneFields($shipment);
        });

        static::updating(function ($shipment) {
            self::formatPhoneFields($shipment);
        });
    }

    private static function formatPhoneFields(self $shipment): void
    {
        if (!empty($shipment->pickup_contact_phone)) {
            $shipment->pickup_contact_phone = PhoneHelper::format($shipment->pickup_contact_phone);
        }

        if (!empty($shipment->delivery_recipient_phone)) {
            $shipment->delivery_recipient_phone = PhoneHelper::format($shipment->delivery_recipient_phone);
        }
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
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    /**
     * Get the region for this shipment.
     */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'delivery_region_id');
    }

    /**
     * Get the district for this shipment.
     */
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'delivery_district_id');
    }

    public function pickupRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'pickup_region_id');
    }

    public function pickupDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'pickup_district_id');
    }

    public function deliveryRegion(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'delivery_region_id');
    }

    public function deliveryDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'delivery_district_id');
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
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'current_invoice_id');
    }

    /**
     * Get the current active invoice for this shipment.
     */
    public function currentInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'current_invoice_id');
    }

    /**
     * Get invoice history for this shipment.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('id');
    }

    /**
     * Get all recorded payments for this shipment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\ShipmentPayment::class)->latest('payment_date');
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
     * Phase 3: Invoice can be created when submitted (old flow) OR at_warehouse (new flow).
     */
    public function canBeInvoiced(): bool
    {
        return in_array($this->status, [ShipmentStatus::SUBMITTED, ShipmentStatus::AT_WAREHOUSE])
            && !$this->invoices()
                ->whereIn('status', InvoiceStatus::activeValues())
                ->exists();
    }

    /**
     * Check if shipment can have a driver assigned.
     * Phase 3: Assignment allowed immediately on SUBMITTED (no invoice required first).
     */
    public function canBeAssigned(): bool
    {
        return in_array($this->status, [ShipmentStatus::SUBMITTED, ShipmentStatus::INVOICE_ACCEPTED]);
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

    public function isSingleDestination(): bool
    {
        return $this->destination_mode === ShipmentDestinationMode::SINGLE;
    }

    public function isPerItemDestination(): bool
    {
        return $this->destination_mode === ShipmentDestinationMode::PER_ITEM;
    }

    public function getRecipientNameAttribute(): ?string
    {
        if ($this->isSingleDestination()) {
            return $this->delivery_recipient_name;
        }

        $names = $this->relationLoaded('items')
            ? $this->items->pluck('delivery_recipient_name')->filter()->unique()->values()
            : $this->items()->whereNotNull('delivery_recipient_name')->distinct()->pluck('delivery_recipient_name');

        if ($names->isEmpty()) {
            return null;
        }

        if ($names->count() === 1) {
            return (string) $names->first();
        }

        return 'Multiple recipients';
    }

    public function getRecipientPhoneAttribute(): ?string
    {
        if ($this->isSingleDestination()) {
            return $this->delivery_recipient_phone;
        }

        $phones = $this->relationLoaded('items')
            ? $this->items->pluck('delivery_recipient_phone')->filter()->unique()->values()
            : $this->items()->whereNotNull('delivery_recipient_phone')->distinct()->pluck('delivery_recipient_phone');

        if ($phones->isEmpty()) {
            return null;
        }

        if ($phones->count() === 1) {
            return (string) $phones->first();
        }

        return 'Multiple numbers';
    }

    public function getTownAttribute(): ?string
    {
        return $this->delivery_town;
    }

    public function getLandmarkAttribute(): ?string
    {
        return $this->delivery_landmark;
    }

    public function getRegionIdAttribute(): ?int
    {
        return $this->delivery_region_id;
    }

    public function getDistrictIdAttribute(): ?int
    {
        return $this->delivery_district_id;
    }

    public function getLatitudeAttribute(): ?string
    {
        return $this->delivery_latitude;
    }

    public function getLongitudeAttribute(): ?string
    {
        return $this->delivery_longitude;
    }

    public function getGhPostAddressAttribute(): ?string
    {
        return $this->delivery_gh_post_address;
    }

    /**
     * Get the pickup location type.
     */
    public function getPickupLocationTypeAttribute(): string
    {
        if ($this->pickup_region_id && $this->pickup_district_id) {
            return 'dropdown';
        }

        if ($this->pickup_latitude && $this->pickup_longitude) {
            return 'coordinates';
        }

        if ($this->pickup_gh_post_address) {
            return 'gh_post';
        }

        return 'unknown';
    }

    /**
     * Get formatted pickup location array.
     */
    public function getFormattedPickupLocationAttribute(): array
    {
        return [
            'type' => $this->pickup_location_type,
            'region' => $this->pickupRegion?->name,
            'region_id' => $this->pickup_region_id,
            'district' => $this->pickupDistrict?->name,
            'district_id' => $this->pickup_district_id,
            'town' => $this->pickup_town,
            'latitude' => $this->pickup_latitude,
            'longitude' => $this->pickup_longitude,
            'gh_post_address' => $this->pickup_gh_post_address,
            'landmark' => $this->pickup_landmark,
        ];
    }

    /**
     * Get the location type.
     */
    public function getLocationTypeAttribute(): string
    {
        if ($this->isPerItemDestination()) {
            return 'multiple';
        }

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

    /**
     * Get formatted location array.
     */
    public function getFormattedLocationAttribute(): array
    {
        if ($this->isPerItemDestination()) {
            return [
                'type' => 'multiple',
                'region' => null,
                'region_id' => null,
                'district' => null,
                'district_id' => null,
                'town' => null,
                'latitude' => null,
                'longitude' => null,
                'gh_post_address' => null,
                'landmark' => null,
            ];
        }

        return [
            'type' => $this->location_type,
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
