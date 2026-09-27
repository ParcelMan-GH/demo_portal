<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One parcel handed by a bus handoff agent to an external bus driver.
 *
 * @property int $id
 * @property int $shipment_item_id
 * @property int|null $hub_id
 * @property int|null $outgoing_batch_id
 * @property int|null $handed_off_by
 * @property string $driver_name
 * @property string|null $driver_phone
 * @property string|null $driver_id_number
 * @property string|null $vehicle_plate
 * @property string|null $vehicle_description
 * @property string|null $bus_company
 * @property string|null $destination
 * @property \Illuminate\Support\Carbon|null $departure_at
 * @property string $proof_photo_path
 * @property int|null $proof_photo_size
 * @property \Illuminate\Support\Carbon|null $proof_photo_taken_at
 * @property string|null $public_token_hash
 * @property \Illuminate\Support\Carbon|null $public_token_expires_at
 * @property \Illuminate\Support\Carbon|null $sms_sent_at
 * @property \Illuminate\Support\Carbon|null $sms_failed_at
 * @property string|null $sms_error
 * @property string|null $notes
 */
class HubBusHandoff extends Model
{
    use HasFactory;

    protected $table = 'hub_bus_handoffs';

    protected $fillable = [
        'shipment_item_id',
        'hub_id',
        'outgoing_batch_id',
        'handed_off_by',
        'driver_name',
        'driver_phone',
        'driver_id_number',
        'vehicle_plate',
        'vehicle_description',
        'bus_company',
        'destination',
        'departure_at',
        'proof_photo_path',
        'proof_photo_size',
        'proof_photo_taken_at',
        'public_token_hash',
        'public_token_expires_at',
        'sms_sent_at',
        'sms_failed_at',
        'sms_error',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'departure_at' => 'datetime',
            'proof_photo_taken_at' => 'datetime',
            'public_token_expires_at' => 'datetime',
            'sms_sent_at' => 'datetime',
            'sms_failed_at' => 'datetime',
            'proof_photo_size' => 'integer',
        ];
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    /**
     * The hub (a `warehouses` row) the parcel left from.
     */
    public function hub(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'hub_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OutgoingBatch::class, 'outgoing_batch_id');
    }

    /**
     * The bus handoff agent who recorded the handover.
     */
    public function handedOffBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_off_by');
    }

    /**
     * Has the customer's link passed its expiry?
     */
    public function publicLinkExpired(): bool
    {
        return $this->public_token_expires_at === null
            || $this->public_token_expires_at->isPast();
    }
}
