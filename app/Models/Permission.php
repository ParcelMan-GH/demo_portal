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

    public function displayModule(): string
    {
        if ($this->module !== 'warehouse') {
            return match ($this->module) {
                'platform_settings' => 'Platform',
                'recipient_payments' => 'Recipient Payments',
                'shipments' => 'Orders',
                default => str($this->module)->replace('_', ' ')->title()->toString(),
            };
        }

        $segment = explode('.', $this->name)[1] ?? $this->action;

        return match ($segment) {
            'dashboard' => 'Dashboard',
            'users' => 'Team',
            'receiving' => 'Receiving',
            'sorting' => 'Sorting',
            'manifest', 'transport' => 'Manifests & Transport',
            'delivery' => 'Delivery',
            'items' => 'Packages',
            'charges' => 'Finance',
            'contacts' => 'Contact Queue',
            'recipient_payments' => 'Recipient Payments',
            default => str($segment)->replace('_', ' ')->title()->toString(),
        };
    }

    public function displayLabel(): string
    {
        $parts = explode('.', $this->name);

        if ($this->module === 'warehouse') {
            array_shift($parts);
        } else {
            array_shift($parts);
        }

        return collect($parts)
            ->map(fn (string $part) => str($part)->replace('_', ' ')->title()->toString())
            ->implode(' / ');
    }

    public function displayDescription(): string
    {
        return trim((string) str($this->description ?? '')
            ->replaceMatches('/\bwarehouse\s+/i', '')
            ->replaceMatches('/\s+/', ' '));
    }
}
