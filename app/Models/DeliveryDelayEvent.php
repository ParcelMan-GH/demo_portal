<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDelayEvent extends Model
{
    public const SOURCE_RIDER_ETA = 'rider_eta';
    public const SOURCE_ADMIN_DELAY_NOTICE = 'admin_delay_notice';
    public const SOURCE_ADMIN_ETA_UPDATE = 'admin_eta_update';

    protected $fillable = [
        'delivery_run_item_id',
        'delivery_run_stop_id',
        'delivery_run_id',
        'shipment_item_id',
        'delivery_delay_reason_id',
        'reason_label',
        'old_expected_delivery_at',
        'new_expected_delivery_at',
        'source',
        'actor_driver_id',
        'actor_user_id',
        'recipient_sms_sent',
        'vendor_notification_sent',
        'vendor_sms_sent',
        'message_preview',
        'notes',
    ];

    protected $casts = [
        'old_expected_delivery_at' => 'datetime',
        'new_expected_delivery_at' => 'datetime',
        'recipient_sms_sent' => 'boolean',
        'vendor_notification_sent' => 'boolean',
        'vendor_sms_sent' => 'boolean',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(DeliveryRunItem::class, 'delivery_run_item_id');
    }

    public function reason(): BelongsTo
    {
        return $this->belongsTo(DeliveryDelayReason::class, 'delivery_delay_reason_id')->withTrashed();
    }

    public function actorDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'actor_driver_id');
    }

    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
