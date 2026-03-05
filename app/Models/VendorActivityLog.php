<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'action',
        'description',
        'ip_address',
        'device_type',
        'device_name',
        'os_version',
        'app_version',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * The vendor this log belongs to.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class)->withTrashed();
    }
}
