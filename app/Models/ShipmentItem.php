<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Parcels this hub is holding that have to go on a bus to another hub.
     *
     * This is the dashboard's "Ready for Bus" tile. Three things must hold:
     *
     *  1. the parcel is here — by either arrival route, see `scopeAtHub()`;
     *  2. it is still awaiting outbound movement — its status is one that says
     *     "sitting at a hub", and it has not already been handed to a bus. Statuses
     *     like `dispatched_to_bus` are not in that set, so a parcel that has gone
     *     cannot be counted. That is the bug this replaced: the tile used to count
     *     `dispatched_to_bus` itself, meaning the opposite of its own label and
     *     reading zero in normal operation;
     *  3. it is *assigned to a different hub*, by either kind of batch. A parcel
     *     may be allocated to a sort batch (`sort_batches`) or attached to an
     *     outgoing batch (`outgoing_batches`); both now carry a
     *     `destination_warehouse_id`, and either one naming a hub other than this
     *     one makes the parcel bus work. That column uses NULL to mean "no
     *     inter-hub transfer recorded", which is why an unassigned parcel is
     *     excluded rather than guessed at.
     *
     * Comparing hubs rather than regions is the point: two hubs can share a region
     * (Accra Main and Tema Warehouse are both region 1), so a region test both
     * missed genuine inter-hub transfers and counted parcels that were never going
     * anywhere else.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<ShipmentItem>  $query
     * @return \Illuminate\Database\Eloquent\Builder<ShipmentItem>
     */
    public function scopeWaitingBus($query, Warehouse $hub)
    {
        return $query->atHub($hub->id)
            ->whereIn('status', array_map(fn (ItemStatus $status) => $status->value, self::AT_HUB_STATUSES))
            ->whereDoesntHave('busHandoffs')
            ->where(function ($target) use ($hub) {
                // A sort batch the parcel was allocated to...
                $target->whereHas('sortBatches', function ($batch) use ($hub) {
                    $batch->whereNotNull('sort_batches.destination_warehouse_id')
                        ->where('sort_batches.destination_warehouse_id', '!=', $hub->id);
                })
                    // ...or an outgoing batch it was collected into.
                    ->orWhereHas('outgoingBatch', function ($batch) use ($hub) {
                        $batch->whereNotNull('outgoing_batches.destination_warehouse_id')
                            ->where('outgoing_batches.destination_warehouse_id', '!=', $hub->id);
                    });
            });
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

    /**
     * Call logs an agent recorded against this parcel.
     *
     * Two rules hang off this relation rather than off the parcel's own columns:
     * a parcel that already has one is hidden from the agent's Call Queue, and a
     * second call cannot be logged for it. Both are questions about the logs, so
     * `whereDoesntHave('agentCallLogs')` answers the first in a single query
     * instead of a per-row lookup.
     */
    public function agentCallLogs(): HasMany
    {
        return $this->hasMany(AgentCallLog::class, 'shipment_item_id');
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
     * The sort batches this parcel has been allocated to.
     *
     * Carries the destination hub: `sort_batches.destination_warehouse_id` is
     * where a parcel is going when it is going to another hub, and NULL when it
     * is staying local. Rows with `removed_at` set are excluded — the parcel was
     * taken back out of that batch, so it is no longer assigned to that hub.
     */
    public function sortBatches(): BelongsToMany
    {
        return $this->belongsToMany(SortBatch::class, 'sort_batch_items')
            ->withPivot(['quantity_allocated', 'added_at', 'removed_at'])
            ->wherePivotNull('removed_at');
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

    /**
     * A short, readable name for where this parcel will be *received* — the
     * counterpart to the warehouse named in the "received at" part of a message.
     *
     * The address can arrive in one of three shapes — a region/district chosen
     * from dropdowns, a dropped map pin, or a Ghana Post GPS code — and any shape
     * can be missing while a free-text town or landmark sits beside it.
     * `delivery_location_type` says which shape was used, so this reads the
     * matching fields and degrades to whatever text actually exists. It never
     * returns an empty string or the word "null": callers append it to customer
     * messages, where a blank is worse than a vaguer label. Kept deliberately
     * short — it is read down a phone line and shown on one line.
     *
     * Reusable on purpose: the SMS listener is the first caller, but any screen
     * that needs to say where a parcel is going can use the same string.
     */
    public function deliveryLocationLabel(): string
    {
        $town = trim((string) $this->delivery_town);
        $landmark = trim((string) $this->delivery_landmark);
        $ghPost = trim((string) $this->delivery_gh_post_address);
        $region = trim((string) $this->deliveryRegion?->name);
        $district = trim((string) $this->deliveryDistrict?->name);

        $label = match ($this->delivery_location_type) {
            // Region/district chosen from the dropdowns: town is the most
            // specific thing the customer recognises, then district, then region.
            'dropdown' => $this->joinLocationParts([$town, $district, $region]),

            // A dropped pin. Its coordinates are not something a customer reads,
            // so a town or landmark beats them; the numbers are the last resort
            // for a pin-only address.
            'coordinates' => $this->joinLocationParts([$town, $landmark]) ?: $this->coordinateLabel(),

            // Ghana Post GPS code — the postcode locals actually use.
            'gh_post' => $this->joinLocationParts([$ghPost, $town]),

            // Nothing matched the named shapes, so fall back across every text
            // field before giving up on a generic phrase.
            default => $this->joinLocationParts([$town, $landmark, $ghPost, $district, $region]),
        };

        return $label !== '' ? $label : 'the delivery address on file';
    }

    /**
     * Join the non-empty parts of an address into one label, dropping blanks and
     * repeats so "Accra, Accra" or a dangling separator never reaches a customer.
     *
     * @param  array<int, string|null>  $parts
     */
    protected function joinLocationParts(array $parts): string
    {
        $unique = [];

        foreach ($parts as $part) {
            $part = trim((string) $part);

            if ($part === '') {
                continue;
            }

            // Case-insensitive de-duplication: regions and districts are often
            // named after their town ("Kumasi, Kumasi Metropolitan", "Accra").
            $key = mb_strtolower($part);

            if (isset($unique[$key])) {
                continue;
            }

            $unique[$key] = $part;
        }

        return implode(', ', array_values($unique));
    }

    /**
     * A last-resort label for a pin-only address: the numbers a rider can follow.
     */
    protected function coordinateLabel(): string
    {
        if (! filled($this->delivery_latitude) || ! filled($this->delivery_longitude)) {
            return '';
        }

        return 'GPS '.number_format((float) $this->delivery_latitude, 5).', '.number_format((float) $this->delivery_longitude, 5);
    }
}