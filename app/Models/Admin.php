<?php

namespace App\Models;

use App\Enums\AdminRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'created_by_admin_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'role' => AdminRole::class,
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the admin who created this admin.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by_admin_id');
    }

    /**
     * Get all admins created by this admin.
     */
    public function createdAdmins(): HasMany
    {
        return $this->hasMany(Admin::class, 'created_by_admin_id');
    }

    /**
     * Check if admin is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === AdminRole::SUPER_ADMIN;
    }

    /**
     * Check if admin is a warehouse manager.
     */
    public function isWarehouseManager(): bool
    {
        return $this->role === AdminRole::WAREHOUSE_MANAGER;
    }

    /**
     * Check if admin is warehouse staff.
     */
    public function isWarehouseStaff(): bool
    {
        return $this->role === AdminRole::WAREHOUSE_STAFF;
    }

    /**
     * Check if admin can create users.
     */
    public function canCreateUsers(): bool
    {
        return $this->role->canCreateUsers();
    }

    /**
     * Get the roles this admin can assign to new users.
     */
    public function getAssignableRoles(): array
    {
        if ($this->isSuperAdmin()) {
            return AdminRole::cases();
        }

        if ($this->isWarehouseManager()) {
            return [AdminRole::WAREHOUSE_STAFF];
        }

        return [];
    }

    /**
     * Check if this admin can manage the given admin.
     */
    public function canManage(Admin $admin): bool
    {
        // Super admin can manage anyone
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Managers can only manage admins they created
        return $admin->created_by_admin_id === $this->id;
    }
}
