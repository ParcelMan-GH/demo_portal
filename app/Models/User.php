<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'last_permission_cache_at',
        'created_by_user_id',
        'warehouse_id',
        'fcm_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'last_permission_cache_at' => 'datetime',
            'warehouse_id' => 'integer',
        ];
    }

    // =====================
    // RELATIONSHIPS
    // =====================

    /**
     * Get the roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('assigned_at', 'assigned_by');
    }

    /**
     * Get the user who created this user.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Get the warehouse this user belongs to.
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get the users created by this user.
     */
    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_user_id');
    }

    /**
     * Audit logs created by this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class);
    }

    // =====================
    // PERMISSION METHODS
    // =====================

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Super admin bypasses all checks
        if ($this->hasRole('super_admin')) {
            return true;
        }

        $permissionNames = Cache::remember(
            "user.{$this->id}.permission_names",
            now()->addHours(24),
            fn() => $this->getAllPermissions()
                ->pluck('name')
                ->filter()
                ->unique()
                ->values()
                ->all()
        );

        return in_array($permission, $permissionNames, true);
    }

    /**
     * Check if the user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get all permissions from all assigned roles.
     */
    public function getAllPermissions(): Collection
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id');
    }

    /**
     * Clear the user's permission cache.
     */
    public function flushPermissionCache(): void
    {
        Cache::forget("user.{$this->id}.permissions");
        Cache::forget("user.{$this->id}.permission_names");
        $this->update(['last_permission_cache_at' => now()]);
    }

    // =====================
    // ROLE METHODS
    // =====================

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(Role|string $role): void
    {
        $role = is_string($role)
            ? Role::where('slug', $role)->firstOrFail()
            : $role;

        // Enforce one-role-per-user by replacing any existing role assignment.
        $this->roles()->sync([
            $role->id => [
                'assigned_at' => now(),
                'assigned_by' => auth('admin')->id()
            ]
        ]);

        $this->flushPermissionCache();
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(Role|string $role): void
    {
        $role = is_string($role)
            ? Role::where('slug', $role)->firstOrFail()
            : $role;

        $this->roles()->detach($role->id);
        $this->flushPermissionCache();
    }

    /**
     * Sync roles for the user.
     */
    public function syncRoles(array $roleIds): void
    {
        $firstRoleId = collect($roleIds)
            ->filter(fn($id) => !is_null($id) && $id !== '')
            ->map(fn($id) => (int) $id)
            ->first();

        if (!$firstRoleId) {
            $this->roles()->sync([]);
            $this->flushPermissionCache();
            return;
        }

        $this->roles()->sync([
            $firstRoleId => [
                'assigned_at' => now(),
                'assigned_by' => auth('admin')->id()
            ]
        ]);
        $this->flushPermissionCache();
    }

    // =====================
    // HELPER METHODS
    // =====================

    /**
     * Check if user is super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user can create other users.
     */
    public function canCreateUsers(): bool
    {
        return $this->hasPermission('users.create');
    }

    /**
     * Check if user can manage another user.
     */
    public function canManage(User $user): bool
    {
        // Super admin can manage anyone
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Can manage users they created
        return $user->created_by_user_id === $this->id;
    }
}
