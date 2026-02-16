<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'address',
        'region_id',
        'district_id',
        'latitude',
        'longitude',
        'contact_phone',
        'contact_email',
        'capacity',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'capacity' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($warehouse) {
            if (empty($warehouse->code)) {
                $warehouse->code = static::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $lastWarehouse = static::withTrashed()
            ->where('code', 'like', 'WH-%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastWarehouse) {
            $lastNumber = (int) str_replace('WH-', '', $lastWarehouse->code);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('WH-%03d', $nextNumber);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class);
    }
}
