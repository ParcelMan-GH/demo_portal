<?php

namespace App\Enums;

enum AdminRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case WAREHOUSE_MANAGER = 'warehouse_manager';
    case WAREHOUSE_STAFF = 'warehouse_staff';

    /**
     * Get the human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::WAREHOUSE_MANAGER => 'Warehouse Manager',
            self::WAREHOUSE_STAFF => 'Warehouse Staff',
        };
    }

    /**
     * Get the badge color class for the role.
     */
    public function badgeColor(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'bg-purple-100 text-purple-800',
            self::WAREHOUSE_MANAGER => 'bg-blue-100 text-blue-800',
            self::WAREHOUSE_STAFF => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Check if this role can create users.
     */
    public function canCreateUsers(): bool
    {
        return in_array($this, [self::SUPER_ADMIN, self::WAREHOUSE_MANAGER]);
    }

    /**
     * Get all roles as options for select dropdowns.
     */
    public static function options(): array
    {
        return array_map(fn($role) => [
            'value' => $role->value,
            'label' => $role->label(),
        ], self::cases());
    }
}
