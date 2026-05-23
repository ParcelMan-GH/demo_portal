<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusHandoffConfirmation extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CODE_SENT = 'code_sent';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ISSUE_REPORTED = 'issue_reported';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ADMIN_CONFIRMED = 'admin_confirmed';

    public const SOURCE_RIDER_CODE = 'rider_code';
    public const SOURCE_PUBLIC_LINK = 'public_link';
    public const SOURCE_ADMIN = 'admin';
    public const SOURCE_VENDOR_FOLLOWUP = 'vendor_followup';

    public const TARGET_RECIPIENT = 'recipient';
    public const TARGET_VENDOR = 'vendor';

    protected $fillable = [
        'delivery_run_id',
        'delivery_run_stop_id',
        'delivery_run_item_id',
        'shipment_item_id',
        'handoff_driver_id',
        'status',
        'source',
        'target_type',
        'target_name',
        'target_phone',
        'confirmation_code_hash',
        'confirmation_code_sent_at',
        'confirmation_code_expires_at',
        'confirmation_code_verified_at',
        'confirmation_attempts',
        'public_token_hash',
        'public_token_expires_at',
        'public_link_sent_at',
        'reason_id',
        'reason_label',
        'reason_type',
        'issue_notes',
        'confirmation_notes',
        'confirmed_at',
        'confirmed_by_driver_id',
        'confirmed_by_admin_id',
        'public_confirmed_at',
        'public_reported_at',
    ];

    protected $casts = [
        'confirmation_code_sent_at' => 'datetime',
        'confirmation_code_expires_at' => 'datetime',
        'confirmation_code_verified_at' => 'datetime',
        'confirmation_attempts' => 'integer',
        'public_token_expires_at' => 'datetime',
        'public_link_sent_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'public_confirmed_at' => 'datetime',
        'public_reported_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class, 'delivery_run_id');
    }

    public function stop(): BelongsTo
    {
        return $this->belongsTo(DeliveryRunStop::class, 'delivery_run_stop_id');
    }

    public function runItem(): BelongsTo
    {
        return $this->belongsTo(DeliveryRunItem::class, 'delivery_run_item_id');
    }

    public function shipmentItem(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class);
    }

    public function handoffDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'handoff_driver_id');
    }

    public function confirmedByDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'confirmed_by_driver_id');
    }

    public function confirmedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_admin_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(DeliveryFailureReason::class, 'reason_id')->withTrashed();
    }

    public function isFinal(): bool
    {
        return in_array($this->status, [
            self::STATUS_CONFIRMED,
            self::STATUS_ADMIN_CONFIRMED,
            self::STATUS_FAILED,
        ], true);
    }
}
