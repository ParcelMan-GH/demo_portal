<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One credited hub-agent commission — a flat amount for one parcel, in one
 * direction (checked in, or released).
 *
 * This is the hub-side analogue of `VendorEarning`: a per-parcel row of earned
 * money that a later payout settles. See the create migration for why it is a
 * table of its own rather than a `role` column on `agent_daily_quotas`.
 */
class HubAgentCommission extends Model
{
    /** An item checked in at the hub (arrival scan). GHC 0.50 by default. */
    public const DIRECTION_INBOUND = 'inbound';

    /** An item leaving the hub (counter handover or doorstep dispatch). GHC 1.00. */
    public const DIRECTION_OUTBOUND = 'outbound';

    /** Earned and awaiting a payout. The only status in use today. */
    public const STATUS_APPROVED = 'approved';

    /** Settled by a payout. Reserved for when a hub payout flow is wired. */
    public const STATUS_PAID = 'paid';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'rate' => 'decimal:2',
        'credited_at' => 'datetime',
    ];

    /** The hub agent this credit is owed to. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The hub the work happened at, when it is still on file. */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'hub_id');
    }

    /** The parcel the credit was earned on. */
    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class, 'shipment_item_id');
    }
}
