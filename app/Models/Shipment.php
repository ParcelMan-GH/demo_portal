<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\PickupAssignmentStatus;
use App\Enums\PickupCoverageStatus;
use App\Enums\ShipmentDestinationMode;
use App\Enums\ShipmentSource;
use App\Enums\ShipmentStatus;
use App\Helpers\CodeResolver;
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
        'source',
        'fulfillment_type',
        'created_by_user_id',
        'destination_mode',
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
        'delivery_preference',
        'sender_notes',
        'vendor_declared_quantity',
        'submitted_at',
        'cancelled_at',
        'cancellation_reason',
        'rejected_at',
        'rejected_by_admin_id',
        'rejection_reason',
        'rejected_from_status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => \App\Casts\TolerantBackedEnumCast::class.':'.ShipmentStatus::class.','.ShipmentStatus::DRAFT->value,
        'source' => ShipmentSource::class,
        'fulfillment_type' => FulfillmentType::class,
        'destination_mode' => ShipmentDestinationMode::class,
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'vendor_declared_quantity' => 'integer',
        'submitted_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rejected_at' => 'datetime',
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
     * Generate a unique shipment number, e.g. `PM-2026-00040`.
     *
     * The prefix is issued as `PM-`. The sequence is deliberately continued
     * across the prefixes this series has used rather than restarted: with 47
     * rows numbered up to `PCM-2026-00039`, restarting would hand out
     * `PM-2026-00001` and leave two live parcels sharing the number 00001. The
     * next number after `PCM-2026-00039` is `PM-2026-00040`.
     */
    public static function generateShipmentNumber(): string
    {
        $prefix = rtrim(
            (string) PlatformSetting::getValue('shipment.number_prefix', CodeResolver::PREFIX),
            '-'
        );
        $length = (int) PlatformSetting::getValue('shipment.number_length', 5);
        $year = date('Y');

        // Every spelling this series may have been written under, so the numeric
        // suffix found is the highest in the series and not just this prefix's.
        $prefixes = array_values(array_unique(array_merge([$prefix], CodeResolver::LEGACY_PREFIXES)));

        // Highest *numeric* suffix used this year. Legacy/manual numbers such as
        // "PCM-2026-D104" have no numeric suffix and used to be cast to 0, which
        // made the next number "PCM-2026-00001" and collide with an existing one.
        $numbers = static::withTrashed()
            ->where(function ($query) use ($prefixes, $year) {
                foreach ($prefixes as $candidate) {
                    $query->orWhere('shipment_number', 'like', "{$candidate}-{$year}-%");
                }
            })
            ->pluck('shipment_number');

        $nextNumber = 1;

        foreach ($numbers as $number) {
            $suffix = substr((string) $number, strrpos((string) $number, '-') + 1);

            if (ctype_digit($suffix)) {
                $nextNumber = max($nextNumber, ((int) $suffix) + 1);
            }
        }

        // Never hand out a number that is already taken, under any spelling.
        do {
            $candidate = sprintf("%s-%s-%0{$length}d", $prefix, $year, $nextNumber);
            $taken = static::withTrashed()
                ->whereIn('shipment_number', [
                    $candidate,
                    ...array_map(fn ($legacy) => "{$legacy}-{$year}-".sprintf("%0{$length}d", $nextNumber), CodeResolver::LEGACY_PREFIXES),
                ])
                ->exists();
            $nextNumber++;
        } while ($taken);

        return $candidate;
    }

    /**
     * Get the vendor that owns this shipment.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isWalkin(): bool
    {
        return $this->source?->isWalkin() ?? false;
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
     * Get all recorded payments for this shipment.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\ShipmentPayment::class)->latest('payment_date');
    }

    /**
     * All charge lines (pickup/delivery/station/handling/other) on this shipment.
     */
    public function charges(): HasMany
    {
        return $this->hasMany(ShipmentCharge::class)->latest('id');
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

    public function pickupVehicleRequests(): HasMany
    {
        return $this->hasMany(ShipmentPickupVehicleRequest::class);
    }

    /**
     * How many pickup slots this shipment asked for — the sum of the quantities
     * on its per-vehicle requests. Zero means no vehicle type was ever requested,
     * which is true for most historical rows.
     *
     * Reads the loaded relation when it is there so a list transform does not
     * fire one query per shipment; otherwise it queries, the way
     * transformPickupVehicleRequests already falls back.
     */
    public function pickupRequiredSlotCount(): int
    {
        $requests = $this->relationLoaded('pickupVehicleRequests')
            ? $this->pickupVehicleRequests
            : $this->pickupVehicleRequests()->get();

        return (int) $requests->sum('quantity');
    }

    /**
     * How many pickup slots are currently filled: every assignment that has not
     * been cancelled. A cancelled assignment releases its slot back.
     */
    public function pickupAssignedSlotCount(): int
    {
        $assignments = $this->relationLoaded('pickupAssignments')
            ? $this->pickupAssignments
            : $this->pickupAssignments()->get();

        return $assignments
            ->filter(fn (PickupAssignment $assignment) => $assignment->status !== PickupAssignmentStatus::CANCELLED)
            ->count();
    }

    /**
     * How well the requested slots are covered.
     *
     * Derived on every read rather than stored: a stored copy of "3 of 3 filled"
     * would drift the moment an assignment is cancelled or added, and the whole
     * point of the number is to summarise those rows.
     *
     * The required === 0 && assigned > 0 case resolves to FULLY_ASSIGNED on
     * purpose. Shipments that never requested a vehicle (all the historical
     * data) are still allowed exactly one rider through the legacy flow, and
     * calling that PARTIALLY_ASSIGNED would make every one of them look somehow
     * incomplete. With no request to measure against, a live rider is complete.
     */
    public function pickupCoverageStatus(): PickupCoverageStatus
    {
        $required = $this->pickupRequiredSlotCount();
        $assigned = $this->pickupAssignedSlotCount();

        if ($assigned === 0) {
            return PickupCoverageStatus::UNASSIGNED;
        }

        if ($required === 0) {
            return PickupCoverageStatus::FULLY_ASSIGNED;
        }

        return $assigned >= $required
            ? PickupCoverageStatus::FULLY_ASSIGNED
            : PickupCoverageStatus::PARTIALLY_ASSIGNED;
    }

    /**
     * Per requested vehicle TYPE, how many slots were asked for and how many are
     * already covered, in the order the requests were created. Lets the admin UI
     * render "2x Motorbike, 1x Aboboyaa" and see how many of each remain.
     *
     * Assignments are matched by pickup_vehicle_type_id. Historical unslotted
     * assignments carry no type, so they count towards the shipment total in
     * pickupAssignedSlotCount() but not against any one type here.
     */
    public function pickupSlotBreakdown(): array
    {
        $requests = $this->relationLoaded('pickupVehicleRequests')
            ? $this->pickupVehicleRequests->sortBy('id')->values()
            : $this->pickupVehicleRequests()->orderBy('id')->get();

        if ($requests->isEmpty()) {
            return [];
        }

        // Avoid an N+1 on vehicleType when the relation came in without it.
        if (! $requests->first()->relationLoaded('vehicleType')) {
            $requests->load('vehicleType');
        }

        $assignments = $this->relationLoaded('pickupAssignments')
            ? $this->pickupAssignments
            : $this->pickupAssignments()->get();

        $assignedByType = $assignments
            ->filter(fn (PickupAssignment $assignment) => $assignment->status !== PickupAssignmentStatus::CANCELLED
                && ! is_null($assignment->pickup_vehicle_type_id))
            ->countBy('pickup_vehicle_type_id');

        $breakdown = [];

        foreach ($requests as $request) {
            $typeId = $request->pickup_vehicle_type_id;
            // Group repeats of the same type; fall back to the snapshot name when
            // the type row has been deleted (the FK is nullOnDelete).
            $key = $typeId
                ? "type:{$typeId}"
                : 'snapshot:'.($request->vehicle_name_snapshot ?? 'unknown');

            if (! isset($breakdown[$key])) {
                $breakdown[$key] = [
                    'type' => $request->vehicleType?->slug,
                    'vehicle_type_id' => $typeId,
                    'name' => $request->vehicleType?->name ?? $request->vehicle_name_snapshot,
                    'required' => 0,
                    'assigned' => 0,
                ];
            }

            $breakdown[$key]['required'] += (int) $request->quantity;

            if ($typeId) {
                $breakdown[$key]['assigned'] = (int) ($assignedByType[$typeId] ?? 0);
            }
        }

        return array_values($breakdown);
    }

    public function collection(): HasOne
    {
        return $this->hasOne(ShipmentCollection::class);
    }

    /**
     * Check if shipment can have a driver assigned.
     */
    public function canBeAssigned(): bool
{
    return in_array($this->status, [
        ShipmentStatus::SUBMITTED,
        ShipmentStatus::PROCESSING,
        ShipmentStatus::PICKUP_ASSIGNED, // <-- Added this
    ]);
}

    /**
     * Check if shipment can be edited.
     */
    public function canBeEdited(): bool
    {
        if ($this->status === ShipmentStatus::DRAFT) {
            return true;
        }

        // Submitted + unprocessed (no admin processing yet)
        if ($this->status === ShipmentStatus::SUBMITTED) {
            $hasAssignment = $this->pickupAssignment()->exists();
            $isProcessed = $this->items()
                ->whereNotNull('description')
                ->where('description', '!=', '')
                ->exists();

            return !$hasAssignment && !$isProcessed;
        }

        return false;
    }

    /**
     * Check if shipment can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // Draft can always be deleted
        if ($this->status === ShipmentStatus::DRAFT) {
            return true;
        }

        // Submitted can be deleted if no processing has started
        if ($this->status === ShipmentStatus::SUBMITTED) {
            $hasAssignment = $this->pickupAssignment()->exists();

            return !$hasAssignment;
        }

        return false;
    }

    public function canBeRejected(): bool
    {
        if (! in_array($this->status, [ShipmentStatus::SUBMITTED, ShipmentStatus::PROCESSING], true)) {
            return false;
        }

        return ! $this->pickupAssignment()->exists();
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
