<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\StorageService;

class TransportLoadingException extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';

    public const REASON_LABEL_DAMAGED = 'label_damaged';
    public const REASON_LABEL_MISSING = 'label_missing';
    public const REASON_CAMERA_CANNOT_READ = 'camera_cannot_read';
    public const REASON_ITEM_PRESENT_NO_LABEL = 'item_present_no_label';
    public const REASON_OTHER = 'other';

    protected $fillable = [
        'transport_manifest_id',
        'transport_container_id',
        'transport_manifest_item_id',
        'driver_id',
        'reason',
        'note',
        'proof_photo_path',
        'status',
        'auto_accepted',
        'reviewed_by_user_id',
        'reviewed_at',
        'admin_note',
    ];

    protected $casts = [
        'auto_accepted' => 'boolean',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = [
        'proof_photo_url',
    ];

    public function manifest(): BelongsTo
    {
        return $this->belongsTo(TransportManifest::class, 'transport_manifest_id');
    }

    public function container(): BelongsTo
    {
        return $this->belongsTo(TransportContainer::class, 'transport_container_id');
    }

    public function manifestItem(): BelongsTo
    {
        return $this->belongsTo(TransportManifestItem::class, 'transport_manifest_item_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function getProofPhotoUrlAttribute(): ?string
    {
        return $this->proof_photo_path ? app(StorageService::class)->getUrl($this->proof_photo_path) : null;
    }
}
