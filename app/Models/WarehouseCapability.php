<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseCapability extends Model
{
    public const SCOPE_OWN = 'own';
    public const SCOPE_SELECTED = 'selected';
    public const SCOPE_GLOBAL = 'global';

    protected $fillable = [
        'warehouse_id',
        'module',
        'scope',
        'allowed_warehouse_ids',
        'granted_by_user_id',
    ];

    protected $casts = [
        'allowed_warehouse_ids' => 'array',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by_user_id');
    }
}
