<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records a package being placed into an outgoing batch automatically, so the
 * decision can be audited after the fact.
 */
class OutgoingBatchAssignmentEvent extends Model
{
    /** The destination had no open batch, so one was created for it. */
    public const EVENT_BATCH_CREATED = 'batch_created';

    /** The package joined a batch that was already open for its destination. */
    public const EVENT_BATCH_ATTACHED = 'batch_attached';

    public const SOURCE_AGENT_CALL = 'agent_call';

    public const SOURCE_AGENT_DASHBOARD = 'agent_dashboard';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_item_id',
        'outgoing_batch_id',
        'actor_user_id',
        'source',
        'event_type',
        'delivery_region_id',
        'delivery_district_id',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function outgoingBatch(): BelongsTo
    {
        return $this->belongsTo(OutgoingBatch::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
