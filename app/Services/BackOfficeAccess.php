<?php

namespace App\Services;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseCapability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class BackOfficeAccess
{
    /**
     * Modules that local warehouse operations can use without an explicit HQ
     * capability grant. User role permissions still apply.
     */
    private const LOCAL_MODULES = [
        'dashboard',
        'warehouse',
    ];

    public function warehouseFor(User $user): ?Warehouse
    {
        if ((int) ($user->warehouse_id ?? 0) <= 0) {
            return null;
        }

        return $user->relationLoaded('warehouse')
            ? $user->warehouse
            : $user->warehouse()->first();
    }

    public function isHq(User $user): bool
    {
        $warehouse = $this->warehouseFor($user);

        return (bool) ($warehouse?->is_hq && $warehouse?->can_administer_system);
    }

    public function canUseModule(User $user, string $module): bool
    {
        $module = $this->normalizeModule($module);

        if ($this->isHq($user)) {
            return true;
        }

        if (in_array($module, self::LOCAL_MODULES, true)) {
            return true;
        }

        $warehouse = $this->warehouseFor($user);

        if (!$warehouse) {
            return false;
        }

        return $warehouse->capabilities()
            ->where('module', $module)
            ->exists();
    }

    public function canUsePermission(User $user, string $permission): bool
    {
        if (!$user->hasPermission($permission)) {
            return false;
        }

        return $this->canUseModule($user, $this->moduleFromPermission($permission));
    }

    /**
     * Return the warehouse IDs this user can operate against for a module.
     *
     * @return array<int>
     */
    public function warehouseIdsFor(User $user, string $module): array
    {
        $warehouse = $this->warehouseFor($user);

        if (!$warehouse) {
            return [];
        }

        if ($this->isHq($user)) {
            return Warehouse::query()->where('is_active', true)->pluck('id')->all();
        }

        $module = $this->normalizeModule($module);

        if (in_array($module, self::LOCAL_MODULES, true)) {
            return [$warehouse->id];
        }

        $capability = $warehouse->capabilities()
            ->where('module', $module)
            ->first();

        if (!$capability) {
            return [];
        }

        if ($capability->scope === WarehouseCapability::SCOPE_GLOBAL) {
            return Warehouse::query()->where('is_active', true)->pluck('id')->all();
        }

        if ($capability->scope === WarehouseCapability::SCOPE_SELECTED) {
            return collect($capability->allowed_warehouse_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [$warehouse->id];
    }

    public function warehousesFor(User $user, string $module): Collection
    {
        $ids = $this->warehouseIdsFor($user, $module);

        if ($ids === []) {
            return collect();
        }

        return Warehouse::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->orderByDesc('is_hq')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'is_hq', 'can_administer_system']);
    }

    public function selectedWarehouse(User $user, string $module): ?Warehouse
    {
        $selectedId = (int) Session::get('backoffice.selected_warehouse_id');

        if ($selectedId <= 0) {
            return null;
        }

        return $this->warehousesFor($user, $module)
            ->firstWhere('id', $selectedId);
    }

    public function setSelectedWarehouse(User $user, ?int $warehouseId, string $module): void
    {
        if ($warehouseId === null) {
            Session::forget('backoffice.selected_warehouse_id');

            return;
        }

        $allowed = $this->warehousesFor($user, $module)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        abort_unless(in_array($warehouseId, $allowed, true), 403);

        Session::put('backoffice.selected_warehouse_id', $warehouseId);
    }

    /**
     * Warehouse IDs after applying the user's temporary UI scope selection.
     *
     * @return array<int>
     */
    public function scopedWarehouseIdsFor(User $user, string $module): array
    {
        $selected = $this->selectedWarehouse($user, $module);

        if ($selected) {
            return [$selected->id];
        }

        return $this->warehouseIdsFor($user, $module);
    }

    public function applyWarehouseScope(Builder $query, User $user, string $module, string $column = 'warehouse_id'): Builder
    {
        $ids = $this->scopedWarehouseIdsFor($user, $module);

        abort_if($ids === [], 403);

        return $query->whereIn($column, $ids);
    }

    public function assertCanUseWarehouse(User $user, int $warehouseId, string $module): void
    {
        abort_unless(in_array($warehouseId, $this->warehouseIdsFor($user, $module), true), 403);
    }

    public function moduleFromPermission(string $permission): string
    {
        return $this->normalizeModule(str($permission)->before('.')->toString());
    }

    public function moduleFromRequestPath(string $path): string
    {
        $segments = collect(explode('/', trim($path, '/')))
            ->filter()
            ->values();

        $segment = $segments->get(1, 'dashboard');

        if ($segment === 'operations') {
            $segment = $segments->get(2, 'warehouse');
        }

        return $this->normalizeModule((string) $segment);
    }

    public function visibleCapabilities(User $user): Collection
    {
        if ($this->isHq($user)) {
            return collect([
                ['module' => '*', 'scope' => WarehouseCapability::SCOPE_GLOBAL],
            ]);
        }

        return $this->warehouseFor($user)?->capabilities()->get() ?? collect();
    }

    private function normalizeModule(string $module): string
    {
        return str($module)
            ->replace('-', '_')
            ->snake()
            ->toString();
    }
}
