<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Models\RiderPackageLocationChange;
use App\Models\RiderPackageTransfer;
use App\Models\ShipmentItem;
use App\Models\WarehouseReceipt;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RiderPackageAuditController extends Controller
{
    public function locationChangesIndex(Request $request): View
    {
        $this->authorizePermission('warehouse.items.scan');

        return view('warehouse.rider-package-audits.location-changes');
    }

    public function locationChangesData(Request $request, StorageService $storage): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = app(WarehousePortalService::class)->resolveWarehouse(Auth::guard('admin')->user());
        $search = trim((string) $request->get('search', ''));

        $changes = RiderPackageLocationChange::query()
            ->with([
                'driver:id,name,phone',
                'shipmentItem.shipment:id,shipment_number',
                'shipmentItem.warehouseReceiptItems' => fn ($query) => $query
                    ->select('id', 'shipment_item_id', 'warehouse_receipt_id', 'received_at')
                    ->whereHas('receipt', fn ($receipt) => $receipt
                        ->where('warehouse_id', $warehouse->id)
                        ->where('status', WarehouseReceipt::STATUS_FINALIZED))
                    ->latest('received_at'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('shipmentItem', fn ($item) => $item
                        ->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('delivery_town', 'like', "%{$search}%"))
                        ->orWhereHas('driver', fn ($driver) => $driver
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->latest('changed_at')
            ->limit(200)
            ->get()
            ->map(fn (RiderPackageLocationChange $change) => [
                'id' => $change->id,
                'tracking_code' => $change->shipmentItem?->tracking_code,
                'description' => $change->shipmentItem?->description,
                'shipment_number' => $change->shipmentItem?->shipment?->shipment_number,
                'package_url' => $this->packageUrl($change->shipmentItem),
                'driver' => $change->driver ? [
                    'name' => $change->driver->name,
                    'phone' => $change->driver->phone,
                ] : null,
                'old_location' => $this->locationText($change->old_location),
                'new_location' => $this->locationText($change->new_location),
                'proof_photo_url' => $change->proof_photo_path ? $storage->getUrl($change->proof_photo_path) : null,
                'changed_at' => $change->changed_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $changes,
        ]);
    }

    public function transfersIndex(Request $request): View
    {
        $this->authorizePermission('warehouse.items.scan');

        return view('warehouse.rider-package-audits.transfers');
    }

    public function transfersData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = app(WarehousePortalService::class)->resolveWarehouse(Auth::guard('admin')->user());
        $search = trim((string) $request->get('search', ''));
        $status = trim((string) $request->get('status', ''));

        $transfers = RiderPackageTransfer::query()
            ->with([
                'shipmentItem.shipment:id,shipment_number',
                'shipmentItem.warehouseReceiptItems' => fn ($query) => $query
                    ->select('id', 'shipment_item_id', 'warehouse_receipt_id', 'received_at')
                    ->whereHas('receipt', fn ($receipt) => $receipt
                        ->where('warehouse_id', $warehouse->id)
                        ->where('status', WarehouseReceipt::STATUS_FINALIZED))
                    ->latest('received_at'),
                'fromDriver:id,name,phone',
                'toDriver:id,name,phone',
            ])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('shipmentItem', fn ($item) => $item
                        ->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%"))
                        ->orWhereHas('fromDriver', fn ($driver) => $driver
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('toDriver', fn ($driver) => $driver
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->latest('requested_at')
            ->limit(200)
            ->get()
            ->map(fn (RiderPackageTransfer $transfer) => [
                'id' => $transfer->id,
                'tracking_code' => $transfer->shipmentItem?->tracking_code,
                'description' => $transfer->shipmentItem?->description,
                'shipment_number' => $transfer->shipmentItem?->shipment?->shipment_number,
                'package_url' => $this->packageUrl($transfer->shipmentItem),
                'from_driver' => $transfer->fromDriver ? [
                    'name' => $transfer->fromDriver->name,
                    'phone' => $transfer->fromDriver->phone,
                ] : null,
                'to_driver' => $transfer->toDriver ? [
                    'name' => $transfer->toDriver->name,
                    'phone' => $transfer->toDriver->phone,
                ] : null,
                'status' => $transfer->status,
                'requested_at' => $transfer->requested_at?->toIso8601String(),
                'responded_at' => $transfer->responded_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    private function locationText(?array $location): string
    {
        if (! $location) {
            return '-';
        }

        return collect([
            $location['town'] ?? null,
            $location['district'] ?? null,
            $location['region'] ?? null,
        ])->filter()->join(', ') ?: '-';
    }

    private function packageUrl(?ShipmentItem $shipmentItem): ?string
    {
        $receiptItem = $shipmentItem?->warehouseReceiptItems?->first();

        return $receiptItem
            ? route('warehouse.packages.show', $receiptItem)
            : null;
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth('admin')->user();
        abort_unless($user && $user->hasPermission($permission), 403);
    }
}
