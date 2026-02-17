<?php

namespace App\Services\Warehouse;

use App\Models\PickupAssignment;
use App\Models\PickupItemConfirmation;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WarehousePortalService
{
    public function resolveWarehouse(User $user): Warehouse
    {
        return Warehouse::query()
            ->whereKey($user->warehouse_id)
            ->where('is_active', true)
            ->firstOrFail();
    }

    public function getDashboardStats(Warehouse $warehouse): array
    {
        return [
            'pending_receipts' => $this->pendingReceiptsQuery($warehouse)->count(),
            'received_pickups' => $this->receivedPickupsQuery($warehouse)->count(),
            'received_items' => (int) $this->receivedItemsQuery($warehouse)->sum('confirmed_quantity'),
            'warehouse_users' => $this->warehouseUsersQuery($warehouse)->count(),
        ];
    }

    public function getAssignableWarehouseRoles(): Collection
    {
        return Role::query()
            ->active()
            ->warehouseRoles()
            ->where('is_assignable_by_warehouse_manager', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    public function warehouseUsersQuery(Warehouse $warehouse): Builder
    {
        return User::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['roles:id,name,slug,is_warehouse_role', 'creator:id,name', 'warehouse:id,name']);
    }

    public function pendingReceiptsQuery(Warehouse $warehouse): Builder
    {
        return PickupAssignment::query()
            ->with([
                'shipment:id,shipment_number,submitted_at',
                'driver:id,name,phone',
                'targetWarehouse:id,name,code',
            ])
            ->where('target_warehouse_id', $warehouse->id)
            ->whereNull('received_at')
            ->where('status', '!=', 'cancelled');
    }

    public function receivedPickupsQuery(Warehouse $warehouse): Builder
    {
        return PickupAssignment::query()
            ->with([
                'shipment:id,shipment_number,submitted_at',
                'driver:id,name,phone',
                'receivedWarehouse:id,name,code',
            ])
            ->where('received_warehouse_id', $warehouse->id)
            ->whereNotNull('received_at');
    }

    public function receivedItemsQuery(Warehouse $warehouse): Builder
    {
        return PickupItemConfirmation::query()
            ->with([
                'shipmentItem:id,shipment_id,description,quantity',
                'shipmentItem.shipment:id,shipment_number',
                'pickupAssignment:id,driver_id,received_warehouse_id,received_at',
                'pickupAssignment.driver:id,name,phone',
            ])
            ->whereHas('pickupAssignment', function (Builder $query) use ($warehouse) {
                $query->where('received_warehouse_id', $warehouse->id)
                    ->whereNotNull('received_at');
            });
    }
}

