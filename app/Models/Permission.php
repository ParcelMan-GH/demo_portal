<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'module',
        'action',
        'description',
        'sort_order',
    ];

    /**
     * Get the roles that have this permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')->withTimestamps();
    }

    /**
     * Scope a query to only include permissions for a specific module.
     */
    public function scopeModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Get all permissions grouped by module.
     */
    public static function getGroupedByModule(): Collection
    {
        return static::orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy('module');
    }

    /**
     * Get a human-readable label for the permission.
     */
    public function label(): string
    {
        return ucfirst(str_replace('.', ' - ', $this->name));
    }
}
