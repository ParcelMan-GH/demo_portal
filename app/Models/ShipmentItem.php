<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
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
        'delivery_fee',
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
        'delivery_preference',
        'is_commerce',
        'delivery_method',
        'status',
        'tracking_code',
        'outgoing_batch_id',
        'agent_id',
        'claimed_at',

        // Hub leg: which hub holds the parcel, when it got there, when it left
        // on a bus, and when it was handed over.
        'hub_id',
        'arrived_at_hub_at',
        'dispatched_to_bus_at',
        'released_at',
        'shelf_location',
        'pickup_code',
    ];

    public const DELIVERY_METHOD_DIRECT = 'direct';
    public const DELIVERY_METHOD_BUS_HANDOFF = 'bus_handoff';
    public const DELIVERY_METHODS = [
        self::DELIVERY_METHOD_DIRECT,
        self::DELIVERY_METHOD_BUS_HANDOFF,
    ];

    /**
     * Statuses that mean a parcel is physically sitting at a hub.
     *
     * This is the single list behind both questions the hub app asks — "what is
     * in my hub" (inventory, dashboard counts) and "what may I put on a bus"
     * (bus handoff). They were separate lists once, which is how a parcel could
     * be handoff-able but invisible in inventory.
     *
     * `pending` is in here, but it cannot pass the location test on its own — a
     * parcel with no custody record is never "at" a hub, so it stays out of
     * inventory and off the buses. See `scopeAtHub()`.
     *
     * @var array<int, ItemStatus>
     */
    public const AT_HUB_STATUSES = [
        ItemStatus::AT_WAREHOUSE,
        ItemStatus::ARRIVED_AT_HUB,
        ItemStatus::SORTED,
        ItemStatus::READY_FOR_HUB_TRANSFER,
        ItemStatus::PENDING,
    ];

    /**
     * Restrict a query to parcels physically sitting at the given hub.
     *
     * There are two ways a parcel gets to a hub, and only one of them writes a
     * column on this table:
     *
     *  1. Hub intake — a transporter's batch is scanned in and `hub_id` is set.
     *  2. Over the counter — a walk-in is booked at the office, which produces a
     *     finalized warehouse receipt naming the warehouse and never touches
     *     `hub_id` at all.
     *
     * Reading only `hub_id` means every walk-in parcel is invisible and
     * undispatchable, which is exactly what was happening. Both are read here.
     *
     * `hub_id` wins wherever it is set, because it is the more recent fact: a
     * parcel taken in at Accra Main and *later* received at Kumasi keeps its
     * Accra receipt, and must not still count as being at Accra.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShipmentItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShipmentItem>
     */
    public function scopeAtHub($query, int $hubId)
    {
        return $query->where(function ($inner) use ($hubId) {
            $inner->where($this->qualifyColumn('hub_id'), $hubId)
                ->orWhere(function ($counter) use ($hubId) {
                    $counter->whereNull($this->qualifyColumn('hub_id'))
                        ->whereHas('warehouseReceiptItems.receipt', function ($receipt) use ($hubId) {
                            $receipt->where('warehouse_receipts.warehouse_id', $hubId)
                                ->where('warehouse_receipts.status', WarehouseReceipt::STATUS_FINALIZED);
                        });
                });
        });
    }

    /**
     * The complement of `scopeAtHub()`: parcels that are not at this hub.
     *
     * Written out rather than negated in SQL because `hub_id != ?` is NULL-unsafe
     * — a walk-in parcel has no `hub_id`, and `NULL != 1` is not true, so a naive
     * negation would silently drop the very rows this exists to catch.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShipmentItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShipmentItem>
     */
    public function scopeNotAtHub($query, int $hubId)
    {
        return $query->where(function ($inner) use ($hubId) {
            $inner->where(function ($elsewhere) use ($hubId) {
                $elsewhere->whereNotNull($this->qualifyColumn('hub_id'))
                    ->where($this->qualifyColumn('hub_id'), '!=', $hubId);
            })->orWhere(function ($unaccounted) use ($hubId) {
                $unaccounted->whereNull($this->qualifyColumn('hub_id'))
                    ->whereDoesntHave('warehouseReceiptItems.receipt', function ($receipt) use ($hubId) {
                        $receipt->where('warehouse_receipts.warehouse_id', $hubId)
                            ->where('warehouse_receipts.status', WarehouseReceipt::STATUS_FINALIZED);
                    });
            });
        });
    }

    /**
     * Parcels this hub is holding that still have to go on a bus.
     *
     * This is the dashboard's "Ready for Bus" tile. Three things must hold:
     *
     *  1. the parcel is here — by either arrival route, see `scopeAtHub()`;
     *  2. it is still awaiting outbound movement, meaning its status is one that
     *     says "sitting at a hub" and it has not already been handed over. That
     *     second half is the bug this replaces: the tile used to count
     *     `dispatched_to_bus`, i.e. parcels that had already gone, so it read
     *     zero in normal operation and meant the opposite of its own label;
     *  3. its destination is not one this hub can serve itself. A parcel we can
     *     prove is destined inside the hub's own region is local delivery work,
     *     not a bus consignment.
     *
     * A parcel with no recorded destination is counted on purpose. It cannot be
     * shown to be local, and the hub is still holding it and still has to move
     * it somewhere — excluding it would under-report the hub's workload, which is
     * the failure this tile is already recovering from.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShipmentItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShipmentItem>
     */
    public function scopeAwaitingBus($query, Warehouse $hub)
    {
        $query->atHub($hub->id)
            ->whereIn('status', array_map(fn (ItemStatus $status) => $status->value, self::AT_HUB_STATUSES))
            ->whereDoesntHave('busHandoffs');

        // Only meaningful when the hub itself carries a region to compare with.
        // Without one the region test would compare against NULL and exclude
        // everything, which is the failure mode being fixed.
        if (filled($hub->region_id)) {
            $query->where(function ($destination) use ($hub) {
                $destination->whereNull('delivery_region_id')
                    ->orWhere('delivery_region_id', '!=', $hub->region_id);
            });
        }

        return $query;
    }

    /**
     * Is this parcel physically at the given hub?
     *
     * Delegates to the scope rather than repeating the rule, so a single parcel
     * and a list of parcels can never disagree about where it is. That matters:
     * inventory listing and bus handoff eligibility ask the same question, and
     * they must not answer it differently.
     */
    public function isAtHub(int $hubId): bool
    {
        return static::query()->whereKey($this->getKey())->atHub($hubId)->exists();
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'integer',
        'is_commerce' => 'boolean',
        'delivery_fee' => 'decimal:2',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'fulfillment_type' => FulfillmentType::class,
        'status' => \App\Casts\TolerantBackedEnumCast::class.':'.ItemStatus::class.','.ItemStatus::PENDING->value,
        'claimed_at' => 'datetime',
        'arrived_at_hub_at' => 'datetime',
        'dispatched_to_bus_at' => 'datetime',
        'released_at' => 'datetime',
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

        static::creating(function ($item) {
            if (empty($item->tracking_code) && static::statusNeedsTrackingCode($item->status)) {
                $item->tracking_code = static::generateTrackingCode();
            }
        });

        // Generate tracking code when item enters package movement.
        static::updating(function ($item) {
            if ($item->isDirty('status') &&
                static::statusNeedsTrackingCode($item->status) &&
                empty($item->tracking_code)) {
                $item->tracking_code = static::generateTrackingCode();
            }
        });
    }

    protected static function statusNeedsTrackingCode(mixed $status): bool
    {
        $value = $status instanceof ItemStatus ? $status->value : $status;

        return filled($value) && $value !== ItemStatus::PENDING->value;
    }

    /**
     * Generate a unique parcel tracking code, e.g. `PM-KQ7XW2MNP`.
     *
     * The body stays random rather than sequential: these codes are printed on
     * labels and read aloud down a phone line, and a sequential code would leak
     * how much volume the business does. Only the prefix is standardised.
     */
    public static function generateTrackingCode(): string
    {
        $prefix = rtrim(
            (string) PlatformSetting::getValue('shipment.tracking_prefix', CodeResolver::PREFIX),
            '-'
        );

        do {
            $code = $prefix.'-'.strtoupper(Str::random(8));
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

    public function outgoingBatch(): BelongsTo
    {
        return $this->belongsTo(OutgoingBatch::class);
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

    public function busHandoffConfirmations(): HasMany
    {
        return $this->hasMany(BusHandoffConfirmation::class);
    }

    /**
     * Bus handovers recorded for this parcel.
     *
     * A parcel that has one has already left on a bus, so it is no longer work
     * the hub has waiting.
     */
    public function busHandoffs(): HasMany
    {
        return $this->hasMany(HubBusHandoff::class);
    }

    public function riderLocationChanges(): HasMany
    {
        return $this->hasMany(RiderPackageLocationChange::class);
    }

    public function riderPackageTransfers(): HasMany
    {
        return $this->hasMany(RiderPackageTransfer::class);
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ShipmentCharge::class);
    }

    public function recipientPaymentTasks(): HasMany
    {
        return $this->hasMany(RecipientPaymentTask::class);
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