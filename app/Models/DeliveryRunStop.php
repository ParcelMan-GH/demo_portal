<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryRunStop extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ARRIVED = 'arrived';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_HANDED_OFF = 'handed_off';

    public const METHOD_DIRECT = 'direct';
    public const METHOD_BUS_HANDOFF = 'bus_handoff';

    protected $fillable = [
        'delivery_run_id',
        'recipient_name',
        'recipient_phone',
        'region_id',
        'district_id',
        'town',
        'latitude',
        'longitude',
        'gh_post_address',
        'landmark',
        'status',
        'verification_code_hash',
        'verification_code_expires_at',
        'verification_code_sent_at',
        'verification_attempts',
        'max_attempts',
        'arrived_at',
        'delivered_at',
        'delivery_latitude',
        'delivery_longitude',
        'proof_photo_path',
        'proof_photo_size',
        'failure_reason',
        'failure_notes',
        'delivery_method',
        'handoff_courier_name',
        'handoff_courier_phone',
        'handoff_vehicle_number',
        'handoff_at',
        'confirmed_by_admin_id',
        'confirmed_at',
        'confirmation_notes',
    ];

    protected $casts = [
        'verification_code_expires_at' => 'datetime',
        'verification_code_sent_at' => 'datetime',
        'arrived_at' => 'datetime',
        'delivered_at' => 'datetime',
        'verification_attempts' => 'integer',
        'max_attempts' => 'integer',
        'proof_photo_size' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'delivery_latitude' => 'decimal:8',
        'delivery_longitude' => 'decimal:8',
        'handoff_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(DeliveryRun::class, 'delivery_run_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryRunItem::class, 'delivery_run_stop_id');
    }

    public function verificationAttempts(): HasMany
    {
        return $this->hasMany(DeliveryVerificationAttempt::class, 'delivery_run_stop_id');
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by_admin_id');
    }

    public function isBusHandoff(): bool
    {
        return $this->delivery_method === self::METHOD_BUS_HANDOFF;
    }

    public function isHandedOff(): bool
    {
        return $this->status === self::STATUS_HANDED_OFF;
    }
}

