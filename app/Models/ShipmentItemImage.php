<?php

namespace App\Models;

use App\Services\StorageService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentItemImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_item_id',
        'path',
        'original_name',
        'size',
        'sort_order',
        'recipient_phone',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Delete from storage when model is deleted
        static::deleting(function ($image) {
            app(StorageService::class)->delete($image->path);
        });
    }

    /**
     * Get the shipment item this image belongs to.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ShipmentItem::class, 'shipment_item_id');
    }

    /**
     * Get the signed URL for this image.
     */
    public function getSignedUrl(): array
    {
        $storageService = app(StorageService::class);
        $url = $storageService->getUrl($this->path);
        $expiresAt = null;

        // If using S3, include expiry time
        if ($storageService->getDriver() === 's3') {
            $expiry = (int) PlatformSetting::getValue('storage.s3.signed_url_expiry', 60);
            $expiresAt = now()->addMinutes($expiry)->toIso8601String();
        }

        return [
            'id' => $this->id,
            'url' => $url,
            'original_name' => $this->original_name,
            'size' => $this->size,
            'size_human' => $this->formatSize((int) $this->size),
            'recipient_phone' => $this->recipient_phone,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Convert bytes to a human-readable size string.
     */
    private function formatSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, 2).' '.$units[$power];
    }
}
