<?php

namespace App\Services\Warehouse;

use App\Models\PickupAssignment;
use App\Models\Role;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
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
            'received_items' => (int) $this->receivedItemsQuery($warehouse)->sum('received_quantity'),
            'warehouse_users' => $this->warehouseUsersQuery($warehouse)->count(),
        ];
    }

    public function getAssignableWarehouseRoles(): Collection
    {
        return Role::query()
            ->active()
            ->warehouseRoles()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_assignable_by_warehouse_manager']);
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
            ->whereNotNull('picked_up_at')
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
        return WarehouseReceiptItem::query()
            ->with([
                'receipt:id,pickup_assignment_id,warehouse_id,status,finalized_at',
                'receipt.pickupAssignment:id,driver_id,received_warehouse_id,received_at',
                'receipt.pickupAssignment.driver:id,name,phone',
                'shipmentItem:id,shipment_id,description,quantity',
                'shipmentItem.shipment:id,shipment_number',
            ])
            ->whereHas('receipt', function (Builder $query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('status', WarehouseReceipt::STATUS_FINALIZED);
            });
    }

    public function receiptForAssignment(PickupAssignment $assignment, Warehouse $warehouse): ?WarehouseReceipt
    {
        return WarehouseReceipt::query()
            ->with([
                'items.shipmentItem.images',
                'items.photos',
            ])
            ->where('pickup_assignment_id', $assignment->id)
            ->where('warehouse_id', $warehouse->id)
            ->first();
    }

    public function destinationWarehouses(): Collection
    {
        return Warehouse::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);
    }

    public function sortingBatchesQuery(Warehouse $warehouse): Builder
    {
        return SortBatch::query()
            ->with([
                'destinationWarehouse:id,name,code',
                'activeItems:id,sort_batch_id,shipment_item_id,warehouse_receipt_item_id,quantity_allocated,added_at',
                'activeItems.shipmentItem:id,description,tracking_code',
            ])
            ->where('origin_warehouse_id', $warehouse->id);
    }

    public function warehouseReceiptItemsQuery(Warehouse $warehouse): Builder
    {
        return WarehouseReceiptItem::query()
            ->with([
                'receipt:id,warehouse_id,status,pickup_assignment_id',
                'shipmentItem:id,shipment_id,description,quantity,tracking_code,status,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark',
                'shipmentItem.shipment:id,shipment_number,destination_mode,delivery_recipient_name,delivery_recipient_phone,delivery_region_id,delivery_district_id,delivery_town,delivery_landmark',
                'shipmentItem.shipment.deliveryRegion:id,name',
                'shipmentItem.shipment.deliveryDistrict:id,name',
                'shipmentItem.deliveryRegion:id,name',
                'shipmentItem.deliveryDistrict:id,name',
                'sortBatchItems.sortBatch:id,status',
            ])
            ->whereHas('receipt', function (Builder $query) use ($warehouse) {
                $query->where('warehouse_id', $warehouse->id)
                    ->where('status', WarehouseReceipt::STATUS_FINALIZED);
            });
    }
}
