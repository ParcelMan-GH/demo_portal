<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\PickupAssignmentStatus;
use App\Enums\ShipmentDestinationMode;
use App\Helpers\PhoneHelper;
use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Models\Driver;
use App\Models\PickupAssignment;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemImage;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceipt;
use App\Services\StorageService;
use App\Services\Warehouse\BarcodeService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseReceivingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceiptController extends AdminShipmentController
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseReceivingService $warehouseReceivingService,
        private StorageService $storageService,
        private BarcodeService $barcodeService
    )
    {
    }

    public function pendingIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.receipts.pending', [
            'warehouse' => $warehouse,
            'statuses' => $this->pendingStatuses(),
        ]);
    }

    public function pendingShow(PickupAssignment $pickupAssignment): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if ((string) ($pickupAssignment->status?->value ?? $pickupAssignment->status) === 'cancelled') {
            abort(404);
        }

        $pickupAssignment->load([
            'shipment.vendor:id,name,business_name',
            'shipment.pickupRegion:id,name',
            'shipment.pickupDistrict:id,name',
            'shipment.deliveryRegion:id,name',
            'shipment.deliveryDistrict:id,name',
            'shipment.items.images',
            'shipment.items.deliveryRegion:id,name',
            'shipment.items.deliveryDistrict:id,name',
            'shipment.invoice',
            'shipment.invoices',
            'driver:id,name,phone',
            'targetWarehouse:id,name,code',
            'photos:id,pickup_assignment_id,shipment_item_id,path,type,created_at',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
            'warehouseReceipt.items.photos',
        ]);

        $receipt = $this->portalService->receiptForAssignment($pickupAssignment, $warehouse);

        $receiptConfig = [
            'save_item_url' => route('warehouse.receipts.pending.items.save', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'print_label_url' => route('warehouse.receipts.pending.items.print-label', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'finalize_url' => route('warehouse.receipts.pending.finalize', ['pickupAssignment' => $pickupAssignment]),
            'add_package_url' => route('warehouse.receipts.pending.packages.add', ['pickupAssignment' => $pickupAssignment]),
            'split_package_url' => route('warehouse.receipts.pending.items.split', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'auto_group_by_phone_url' => route('warehouse.receipts.pending.auto-group-by-phone', ['pickupAssignment' => $pickupAssignment]),
            'save_shared_destination_url' => route('warehouse.receipts.pending.shared-destination', ['pickupAssignment' => $pickupAssignment]),
            'townsSearchUrl' => route('admin.locations.towns.data'),
            'can_receive' => !is_null($pickupAssignment->picked_up_at),
            'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($pickupAssignment, $pickupAssignment->shipment),
            'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($pickupAssignment, $pickupAssignment->shipment),
            'shipment' => $this->serializeReceiptShipment($pickupAssignment->shipment),
            'assignment' => [
                'id' => $pickupAssignment->id,
                'status' => $pickupAssignment->status?->value ?? (string) $pickupAssignment->status,
                'status_label' => $this->statusLabel($pickupAssignment->status?->value ?? (string) $pickupAssignment->status),
                'driver' => $pickupAssignment->driver ? [
                    'id' => $pickupAssignment->driver->id,
                    'name' => $pickupAssignment->driver->name,
                    'phone' => $pickupAssignment->driver->phone,
                ] : null,
                'target_warehouse' => $pickupAssignment->targetWarehouse ? [
                    'id' => $pickupAssignment->targetWarehouse->id,
                    'name' => $pickupAssignment->targetWarehouse->name,
                    'code' => $pickupAssignment->targetWarehouse->code,
                ] : null,
            ],
            'receipt' => $receipt ? [
                'id' => $receipt->id,
                'status' => $receipt->status,
                'status_label' => $this->receiptStatusLabel($receipt->status),
                'has_discrepancies' => $receipt->items->contains(fn ($item) => $item->discrepancy_type !== 'none'),
            ] : null,
            'can_approve_discrepancy' => Auth::guard('admin')->user()->hasPermission('warehouse.receiving.approve_discrepancy'),
            'items' => $pickupAssignment->shipment?->items?->map(function (ShipmentItem $item) use ($pickupAssignment, $receipt) {
                $receiptItem = $receipt?->items?->firstWhere('shipment_item_id', $item->id);
                $driverConfirmation = $pickupAssignment->itemConfirmations->firstWhere('shipment_item_id', $item->id);
                $driverPhotos = $pickupAssignment->photos
                    ->where('shipment_item_id', $item->id)
                    ->values()
                    ->map(fn ($photo) => [
                        'id' => $photo->id,
                        'type' => $photo->type,
                        'url' => $this->storageService->getUrl($photo->path),
                        'created_at' => optional($photo->created_at)?->toIso8601String(),
                    ])->values();

                $vendorPhotos = $this->serializeVendorPackagePhotos($item);

                $vendorQuantity = (int) $item->quantity;
                $driverQuantity = $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null;
                $expectedQuantity = $driverQuantity ?? $vendorQuantity;
                $expectedSource = $driverQuantity !== null ? 'driver' : 'vendor_fallback';

                return [
                    'shipment_item_id' => $item->id,
                    'description' => $item->description,
                    'tracking_code' => $item->tracking_code,
                    'delivery_recipient_name' => $item->delivery_recipient_name ?: $item->shipment?->delivery_recipient_name,
                    'delivery_recipient_phone' => $item->delivery_recipient_phone ?: $item->shipment?->delivery_recipient_phone,
                    'delivery_region_id' => $item->delivery_region_id ?: $item->shipment?->delivery_region_id,
                    'delivery_district_id' => $item->delivery_district_id ?: $item->shipment?->delivery_district_id,
                    'delivery_region_name' => $item->deliveryRegion?->name ?: $item->shipment?->deliveryRegion?->name,
                    'delivery_district_name' => $item->deliveryDistrict?->name ?: $item->shipment?->deliveryDistrict?->name,
                    'delivery_town' => $item->delivery_town ?: $item->shipment?->delivery_town,
                    'delivery_landmark' => $item->delivery_landmark ?: $item->shipment?->delivery_landmark,
                    'delivery_instructions' => $item->delivery_instructions ?: $item->shipment?->delivery_instructions,
                    'delivery_preference' => $item->delivery_preference ?? $item->shipment?->delivery_preference ?? 'deliver',
                    'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                    'fulfillment_type' => $item->fulfillment_type?->value ?? $item->shipment?->fulfillment_type?->value ?? 'warehouse',
                    'vendor_quantity' => $vendorQuantity,
                    'vendor_declared_quantity' => (int) ($item->shipment?->vendor_declared_quantity ?? $vendorQuantity),
                    'driver_picked_quantity' => $pickupAssignment && !is_null($pickupAssignment->driver_picked_quantity) ? (int) $pickupAssignment->driver_picked_quantity : null,
                    'driver_confirmed_quantity' => $driverQuantity,
                    'driver_qty_matches_vendor' => $driverQuantity !== null
                        ? $driverQuantity === $vendorQuantity
                        : null,
                    'vendor_photos' => $vendorPhotos,
                    'driver_photos' => $driverPhotos,
                    'expected_quantity' => $expectedQuantity,
                    'expected_source' => $expectedSource,
                    'received_quantity' => (int) ($receiptItem?->received_quantity ?? 0),
                    'damaged_quantity' => (int) ($receiptItem?->damaged_quantity ?? 0),
                    'discrepancy_type' => $receiptItem?->discrepancy_type ?? 'none',
                    'condition_status' => $receiptItem?->condition_status ?? 'ok',
                    'notes' => $receiptItem?->notes,
                    'barcode_value' => $receiptItem?->barcode_value,
                    'barcode_print_count' => (int) ($receiptItem?->barcode_print_count ?? 0),
                    'photos' => $receiptItem
                        ? $this->warehouseReceivingService->serializeReceiptItem($receiptItem)['photos']
                        : [],
                    'can_split' => $this->canSplitPackageDuringReceiving($pickupAssignment, $receiptItem, $pickupAssignment->shipment),
                    'split_lock_reason' => $this->receivingSplitLockReason($pickupAssignment, $receiptItem, $pickupAssignment->shipment),
                ];
            })->values() ?? [],
        ];

        $shipment = $pickupAssignment->shipment;
        $invoice  = $shipment?->invoice;
        $invoices = $shipment?->invoices ?? collect();
        $admin    = Auth::guard('admin')->user();

        // Merge invoice/payment URLs and data into receiptConfig so one JSON attribute covers everything
        $receiptConfig['invoice_store_url']      = route('warehouse.invoices.store', ['pickupAssignment' => $pickupAssignment]);
        $receiptConfig['payments_data_url']       = route('warehouse.receipts.payments.data', ['pickupAssignment' => $pickupAssignment]);
        $receiptConfig['payments_store_url']      = route('warehouse.receipts.payments.store', ['pickupAssignment' => $pickupAssignment]);
        $receiptConfig['payment_destroy_url']     = route('warehouse.payments.destroy', ['payment' => '__ID__']);
        $receiptConfig['payment_download_url']    = route('warehouse.payments.download', ['payment' => '__ID__']);
        $receiptConfig['payment_print_url']       = route('warehouse.payments.print', ['payment' => '__ID__']);
        $receiptConfig['invoice_show_url']        = route('warehouse.invoices.show', ['invoice' => '__INVOICE__']);
        $receiptConfig['can_create_invoice']      = $admin->hasPermission('warehouse.invoices.create');
        $receiptConfig['can_edit_invoice']        = $admin->hasPermission('warehouse.invoices.edit');
        $receiptConfig['can_view_invoice']        = $admin->hasPermission('warehouse.invoices.view');
        $receiptConfig['is_hq_user']              = $admin->isHqUser();
        $receiptConfig['is_super_admin']          = $admin->isHqUser();
        $receiptConfig['invoice']                 = $invoice ? [
            'id'                => $invoice->id,
            'invoice_number'    => $invoice->invoice_number,
            'status'            => $invoice->status?->value ?? (string) $invoice->status,
            'pickup_fee'        => (float) $invoice->pickup_fee,
            'transport_fee'     => (float) $invoice->transport_fee,
            'handling_fee'      => (float) $invoice->handling_fee,
            'other_fee'         => (float) $invoice->other_fee,
            'total_amount'      => (float) $invoice->total_amount,
            'notes'             => $invoice->notes,
            'sent_at'           => $invoice->sent_at?->format('Y-m-d H:i'),
            'accepted_at'       => $invoice->accepted_at?->format('Y-m-d H:i'),
            'rejected_at'       => $invoice->rejected_at?->format('Y-m-d H:i'),
            'cancelled_at'      => $invoice->cancelled_at?->format('Y-m-d H:i'),
            'download_url'      => route('warehouse.invoices.download', ['invoice' => $invoice]),
            'print_url'         => route('warehouse.invoices.print', ['invoice' => $invoice]),
            'update_url'        => route('warehouse.invoices.update', ['invoice' => $invoice]),
            'send_url'          => route('warehouse.invoices.send', ['invoice' => $invoice]),
            'cancel_url'        => route('warehouse.invoices.cancel', ['invoice' => $invoice]),
            'admin_accept_url'  => route('warehouse.invoices.admin-accept', ['invoice' => $invoice]),
            'payments_data_url' => route('warehouse.invoices.payments.data', ['invoice' => $invoice]),
        ] : null;
        $receiptConfig['invoice_history'] = $invoices->map(fn ($inv) => [
            'id'                => $inv->id,
            'invoice_number'    => $inv->invoice_number,
            'status'            => $inv->status?->value ?? (string) $inv->status,
            'status_label'      => ucfirst($inv->status?->value ?? (string) $inv->status),
            'is_active'         => in_array($inv->status?->value ?? (string) $inv->status, ['pending', 'sent', 'accepted']),
            'total_amount'      => (float) $inv->total_amount,
            'notes'             => $inv->notes,
            'created_at'        => $inv->created_at?->format('Y-m-d H:i'),
            'sent_at'           => $inv->sent_at?->format('Y-m-d H:i'),
            'accepted_at'       => $inv->accepted_at?->format('Y-m-d H:i'),
            'cancelled_at'      => $inv->cancelled_at?->format('Y-m-d H:i'),
            'download_url'      => route('warehouse.invoices.download', ['invoice' => $inv]),
            'print_url'         => route('warehouse.invoices.print', ['invoice' => $inv]),
            'update_url'        => route('warehouse.invoices.update', ['invoice' => $inv]),
            'send_url'          => route('warehouse.invoices.send', ['invoice' => $inv]),
            'cancel_url'        => route('warehouse.invoices.cancel', ['invoice' => $inv]),
            'admin_accept_url'  => route('warehouse.invoices.admin-accept', ['invoice' => $inv]),
            'payments_data_url' => route('warehouse.invoices.payments.data', ['invoice' => $inv]),
        ])->values()->all();

        return view('warehouse.receipts.show', [
            'warehouse'     => $warehouse,
            'assignment'    => $pickupAssignment,
            'statusLabel'   => $this->statusLabel($pickupAssignment->status?->value ?? (string) $pickupAssignment->status),
            'receipt'       => $receipt,
            'receiptConfig' => $receiptConfig,
        ]);
    }

    public function pendingData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->portalService->pendingReceiptsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('shipment', fn (Builder $sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('assigned_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('assigned_at', '<=', $dateTo);
        }

        $sortBy = $request->input('sort', 'assigned_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['shipment_number', 'driver_name', 'assigned_at', 'arrived_warehouse_at', 'status', 'created_at'];
        if ($sortBy === 'shipment_number') {
            $query->orderBy(
                Shipment::select('shipment_number')
                    ->whereColumn('shipments.id', 'pickup_assignments.shipment_id')
                    ->limit(1),
                $sortDirection
            );
        } elseif ($sortBy === 'driver_name') {
            $query->orderBy(
                Driver::select('name')
                    ->whereColumn('drivers.id', 'pickup_assignments.driver_id')
                    ->limit(1),
                $sortDirection
            );
        } elseif (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('assigned_at');
        }

        return $this->paginatedResponse($query, $request, function (PickupAssignment $assignment) {
            $status = $assignment->status?->value ?? (string) $assignment->status;

            return [
                'id' => $assignment->id,
                'shipment_number' => $assignment->shipment?->shipment_number,
                'driver_name' => $assignment->driver?->name,
                'driver_phone' => $assignment->driver?->phone,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'assigned_at' => optional($assignment->assigned_at)?->format('Y-m-d H:i:s'),
                'arrived_warehouse_at' => optional($assignment->arrived_warehouse_at)?->format('Y-m-d H:i:s'),
                'view_url' => route('warehouse.receipts.pending.show', $assignment),
            ];
        });
    }

    public function savePendingItem(Request $request, PickupAssignment $pickupAssignment, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if (is_null($pickupAssignment->picked_up_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot receive items — the driver has not picked up this shipment yet.',
            ], 422);
        }

        $validated = $request->validate([
            'received_quantity' => ['required', 'integer', 'min:0'],
            'damaged_quantity' => ['nullable', 'integer', 'min:0'],
            'condition_status' => ['nullable', 'in:ok,damaged,partial'],
            'notes' => ['nullable', 'string', 'max:3000'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:12288'],
            'remove_photo_ids' => ['nullable', 'array'],
            'remove_photo_ids.*' => ['integer', 'exists:warehouse_receipt_item_photos,id'],
            // Tag the package for bus-courier handoff at the moment of
            // receiving, so the right driver picks it up downstream.
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        $shipment = $shipmentItem->shipment()->first();
        $isPerItemMode = ($shipment?->destination_mode?->value ?? (string) $shipment?->destination_mode) === ShipmentDestinationMode::PER_ITEM->value;
        $shipmentItemUpdates = [];

        if ($isPerItemMode) {
            foreach ([
                'delivery_recipient_name',
                'delivery_region_id',
                'delivery_district_id',
                'delivery_town',
                'delivery_landmark',
                'delivery_instructions',
            ] as $key) {
                if (array_key_exists($key, $validated)) {
                    $shipmentItemUpdates[$key] = $validated[$key] ?? null;
                }
            }

            if (array_key_exists('delivery_recipient_phone', $validated)) {
                $shipmentItemUpdates['delivery_recipient_phone'] = ! empty($validated['delivery_recipient_phone'])
                    ? PhoneHelper::format($validated['delivery_recipient_phone'])
                    : null;
            }
        }

        if (array_key_exists('delivery_method', $validated) && $validated['delivery_method'] !== null) {
            $shipmentItemUpdates['delivery_method'] = $validated['delivery_method'];
        }

        if (! empty($shipmentItemUpdates)) {
            $shipmentItem->update($shipmentItemUpdates);
            $shipmentItem->refresh();
        }

        $result = $this->warehouseReceivingService->upsertReceiptItem(
            assignment: $pickupAssignment,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['received_quantity'],
            damagedQuantity: (int) ($validated['damaged_quantity'] ?? 0),
            conditionStatus: $validated['condition_status'] ?? null,
            notes: $validated['notes'] ?? null,
            photos: $request->file('photos', []),
            removePhotoIds: $validated['remove_photo_ids'] ?? []
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function saveSharedDestination(Request $request, PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $shipment = $pickupAssignment->shipment()->with([
            'deliveryRegion:id,name',
            'deliveryDistrict:id,name',
            'items.images',
            'items.deliveryRegion:id,name',
            'items.deliveryDistrict:id,name',
        ])->firstOrFail();

        if (($shipment->destination_mode?->value ?? (string) $shipment->destination_mode) !== ShipmentDestinationMode::SINGLE->value) {
            return response()->json([
                'success' => false,
                'message' => 'Shared destination can only be edited in one drop-off mode.',
            ], 422);
        }

        $validated = $request->validate([
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
        ]);

        $shipmentUpdates = [];
        foreach ([
            'delivery_recipient_name',
            'delivery_region_id',
            'delivery_district_id',
            'delivery_town',
            'delivery_landmark',
            'delivery_instructions',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $shipmentUpdates[$key] = $validated[$key] ?? null;
            }
        }

        if (array_key_exists('delivery_recipient_phone', $validated)) {
            $shipmentUpdates['delivery_recipient_phone'] = ! empty($validated['delivery_recipient_phone'])
                ? PhoneHelper::format($validated['delivery_recipient_phone'])
                : null;
        }

        if (! empty($shipmentUpdates)) {
            $shipment->update($shipmentUpdates);
        }

        if (array_key_exists('delivery_method', $validated) && $validated['delivery_method']) {
            $shipment->items()->update(['delivery_method' => $validated['delivery_method']]);
        }

        $shipment = $shipment->fresh([
            'deliveryRegion:id,name',
            'deliveryDistrict:id,name',
            'items.images',
            'items.deliveryRegion:id,name',
            'items.deliveryDistrict:id,name',
        ]);

        $receipt = $this->portalService->receiptForAssignment($pickupAssignment, $warehouse);
        $pickupAssignment->loadMissing([
            'photos:id,pickup_assignment_id,shipment_item_id,path,type,created_at',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shared destination saved.',
            'data' => [
                'shipment' => $this->serializeReceiptShipment($shipment),
                'items' => $this->serializeReceiptItems($pickupAssignment, $shipment, $receipt),
            ],
        ]);
    }

    public function addReceiptPackage(Request $request, PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        [$warehouse, $shipment] = $this->scopedReceivingShipment($pickupAssignment);
        $assignment = $shipment->pickupAssignment;

        if (! $this->canAddPackageDuringReceiving($assignment, $shipment)) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingAddPackageLockReason($assignment, $shipment) ?? 'Packages can no longer be added for this receipt.',
            ], 422);
        }

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:500'],
            'quantity' => ['required', 'integer', 'min:1'],
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:12288'],
        ]);

        $item = $shipment->items()->create([
            'description' => $validated['description'],
            'quantity' => (int) $validated['quantity'],
            'status' => $this->initialStatusForAdminCreatedPackage($shipment, $assignment),
            'delivery_preference' => 'deliver',
            'fulfillment_type' => $shipment->fulfillment_type?->value ?? $shipment->getRawOriginal('fulfillment_type') ?? 'warehouse',
            'delivery_method' => $validated['delivery_method'] ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
        ]);

        $this->ensurePickupConfirmationForAdminCreatedPackage($assignment, $item);

        $result = $this->warehouseReceivingService->upsertReceiptItem(
            assignment: $assignment,
            shipmentItem: $item,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            receivedQuantity: (int) $validated['quantity'],
            damagedQuantity: 0,
            conditionStatus: 'ok',
            notes: null,
            photos: $request->file('photos', []),
            removePhotoIds: []
        );

        if (($result['success'] ?? false) !== true) {
            return response()->json($result, 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Package added.',
            'data' => $this->warehouseReceivingPayload($assignment->fresh(), $warehouse),
        ]);
    }

    public function splitReceiptPackage(Request $request, PickupAssignment $pickupAssignment, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        [$warehouse, $shipment] = $this->scopedReceivingShipment($pickupAssignment);
        $assignment = $shipment->pickupAssignment;

        if ((int) $shipmentItem->shipment_id !== (int) $shipment->id) {
            return response()->json(['success' => false, 'message' => 'Package not found.'], 404);
        }

        $validated = $request->validate([
            'photo_ids' => ['required', 'array', 'min:1'],
            'photo_ids.*' => ['required', 'integer'],
        ]);

        $receipt = $this->portalService->receiptForAssignment($assignment, $warehouse);
        $receiptItem = $receipt?->items?->firstWhere('shipment_item_id', $shipmentItem->id);

        if (! $this->canSplitPackageDuringReceiving($assignment, $receiptItem, $shipment)) {
            return response()->json([
                'success' => false,
                'message' => $this->receivingSplitLockReason($assignment, $receiptItem, $shipment) ?? 'Package can no longer be split.',
            ], 422);
        }

        $newItem = DB::transaction(function () use ($shipment, $shipmentItem, $validated, $assignment) {
            $source = ShipmentItem::query()->lockForUpdate()->findOrFail($shipmentItem->id);
            $newItem = $shipment->items()->create([
                'description' => $source->description,
                'quantity' => 1,
                'status' => $this->initialStatusForAdminCreatedPackage($shipment, $assignment),
                'delivery_preference' => $source->delivery_preference ?? 'deliver',
                'fulfillment_type' => $source->fulfillment_type?->value ?? $source->getRawOriginal('fulfillment_type'),
                'delivery_recipient_name' => $source->delivery_recipient_name,
                'delivery_recipient_phone' => $source->delivery_recipient_phone,
                'delivery_region_id' => $source->delivery_region_id,
                'delivery_district_id' => $source->delivery_district_id,
                'delivery_town' => $source->delivery_town,
                'delivery_landmark' => $source->delivery_landmark,
                'delivery_instructions' => $source->delivery_instructions,
                'delivery_method' => $source->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
            ]);

            ShipmentItemImage::query()
                ->whereIn('id', $validated['photo_ids'])
                ->where('shipment_item_id', $source->id)
                ->update(['shipment_item_id' => $newItem->id]);

            $this->ensurePickupConfirmationForAdminCreatedPackage($assignment, $newItem);

            return $newItem;
        });

        $shipment = $this->reloadReceivingShipment($shipment->fresh());
        $this->syncDestinationModeFromVendorPhotoPhones($shipment);

        return response()->json([
            'success' => true,
            'message' => 'Package split.',
            'data' => $this->warehouseReceivingPayload($assignment->fresh(), $warehouse),
        ]);
    }

    public function autoGroupReceiptPackagesByPhone(PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        [$warehouse, $shipment] = $this->scopedReceivingShipment($pickupAssignment);

        $result = $this->groupShipmentPackagesByPhoneForReceiving($shipment);
        if (($result['success'] ?? false) !== true) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Auto-group by phone is no longer available for this receipt.',
            ], $result['status'] ?? 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Packages grouped by phone.',
            'data' => $this->warehouseReceivingPayload($pickupAssignment->fresh(), $warehouse),
        ]);
    }

    public function printPendingItemLabel(PickupAssignment $pickupAssignment, ShipmentItem $shipmentItem): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $result = $this->warehouseReceivingService->printItemLabel(
            assignment: $pickupAssignment,
            shipmentItem: $shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user()
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function finalizePendingReceipt(Request $request, PickupAssignment $pickupAssignment): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if (is_null($pickupAssignment->picked_up_at)) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot finalize receipt — the driver has not picked up this shipment yet.',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
            'approval_reason' => ['nullable', 'string', 'max:3000'],
        ]);

        $result = $this->warehouseReceivingService->finalizeReceipt(
            assignment: $pickupAssignment,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            notes: $validated['notes'] ?? null,
            approvalReason: $validated['approval_reason'] ?? null
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function receivedPickupShow(PickupAssignment $pickupAssignment): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $pickupAssignment->received_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        if (is_null($pickupAssignment->received_at)) {
            abort(404);
        }

        $pickupAssignment->load([
            'shipment.vendor:id,name,business_name',
            'shipment.pickupRegion:id,name',
            'shipment.pickupDistrict:id,name',
            'shipment.deliveryRegion:id,name',
            'shipment.deliveryDistrict:id,name',
            'shipment.items.images',
            'shipment.items.deliveryRegion:id,name',
            'shipment.items.deliveryDistrict:id,name',
            'shipment.invoice',
            'shipment.invoices',
            'driver:id,name,phone',
            'targetWarehouse:id,name,code',
            'photos:id,pickup_assignment_id,shipment_item_id,path,type,created_at',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
            'warehouseReceipt.items.photos',
        ]);

        $receipt = $this->portalService->receiptForAssignment($pickupAssignment, $warehouse);

        $receiptConfig = [
            'save_item_url'   => route('warehouse.receipts.pending.items.save', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'print_label_url' => route('warehouse.receipts.pending.items.print-label', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'finalize_url'    => route('warehouse.receipts.pending.finalize', ['pickupAssignment' => $pickupAssignment]),
            'add_package_url' => route('warehouse.receipts.pending.packages.add', ['pickupAssignment' => $pickupAssignment]),
            'split_package_url' => route('warehouse.receipts.pending.items.split', ['pickupAssignment' => $pickupAssignment, 'shipmentItem' => '__ITEM__']),
            'auto_group_by_phone_url' => route('warehouse.receipts.pending.auto-group-by-phone', ['pickupAssignment' => $pickupAssignment]),
            'save_shared_destination_url' => route('warehouse.receipts.pending.shared-destination', ['pickupAssignment' => $pickupAssignment]),
            'can_receive'     => true,
            'can_auto_group'  => $this->canAutoGroupByPhoneDuringReceiving($pickupAssignment, $pickupAssignment->shipment),
            'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($pickupAssignment, $pickupAssignment->shipment),
            'shipment'        => $this->serializeReceiptShipment($pickupAssignment->shipment),
            'receipt'         => $receipt ? [
                'id'               => $receipt->id,
                'status'           => $receipt->status,
                'status_label'     => $this->receiptStatusLabel($receipt->status),
                'has_discrepancies' => $receipt->items->contains(fn ($item) => $item->discrepancy_type !== 'none'),
            ] : null,
            'can_approve_discrepancy' => Auth::guard('admin')->user()->hasPermission('warehouse.receiving.approve_discrepancy'),
            'items' => $pickupAssignment->shipment?->items?->map(function (ShipmentItem $item) use ($pickupAssignment, $receipt) {
                $receiptItem       = $receipt?->items?->firstWhere('shipment_item_id', $item->id);
                $driverConfirmation = $pickupAssignment->itemConfirmations->firstWhere('shipment_item_id', $item->id);
                $driverPhotos      = $pickupAssignment->photos
                    ->where('shipment_item_id', $item->id)
                    ->values()
                    ->map(fn ($photo) => [
                        'id'         => $photo->id,
                        'type'       => $photo->type,
                        'url'        => $this->storageService->getUrl($photo->path),
                        'created_at' => optional($photo->created_at)?->toIso8601String(),
                    ])->values();

                $vendorPhotos    = $this->serializeVendorPackagePhotos($item);
                $vendorQuantity  = (int) $item->quantity;
                $driverQuantity  = $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null;
                $expectedQuantity = $driverQuantity ?? $vendorQuantity;
                $expectedSource  = $driverQuantity !== null ? 'driver' : 'vendor_fallback';

                return [
                    'shipment_item_id'            => $item->id,
                    'description'                 => $item->description,
                    'tracking_code'               => $item->tracking_code,
                    'delivery_recipient_name'     => $item->delivery_recipient_name ?: $item->shipment?->delivery_recipient_name,
                    'delivery_recipient_phone'    => $item->delivery_recipient_phone ?: $item->shipment?->delivery_recipient_phone,
                    'delivery_town'               => $item->delivery_town ?: $item->shipment?->delivery_town,
                    'delivery_preference'         => $item->delivery_preference ?? $item->shipment?->delivery_preference ?? 'deliver',
                    'delivery_method'             => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                    'fulfillment_type'            => $item->fulfillment_type?->value ?? $item->shipment?->fulfillment_type?->value ?? 'warehouse',
                    'vendor_quantity'             => $vendorQuantity,
                    'vendor_declared_quantity'    => (int) ($item->shipment?->vendor_declared_quantity ?? $vendorQuantity),
                    'driver_picked_quantity'      => $pickupAssignment && !is_null($pickupAssignment->driver_picked_quantity) ? (int) $pickupAssignment->driver_picked_quantity : null,
                    'driver_confirmed_quantity'   => $driverQuantity,
                    'driver_qty_matches_vendor'   => $driverQuantity !== null ? $driverQuantity === $vendorQuantity : null,
                    'vendor_photos'               => $vendorPhotos,
                    'driver_photos'               => $driverPhotos,
                    'expected_quantity'           => $expectedQuantity,
                    'expected_source'             => $expectedSource,
                    'received_quantity'           => (int) ($receiptItem?->received_quantity ?? 0),
                    'damaged_quantity'            => (int) ($receiptItem?->damaged_quantity ?? 0),
                    'discrepancy_type'            => $receiptItem?->discrepancy_type ?? 'none',
                    'condition_status'            => $receiptItem?->condition_status ?? 'ok',
                    'notes'                       => $receiptItem?->notes,
                    'barcode_value'               => $receiptItem?->barcode_value,
                    'barcode_print_count'         => (int) ($receiptItem?->barcode_print_count ?? 0),
                    'photos'                      => $receiptItem
                        ? $this->warehouseReceivingService->serializeReceiptItem($receiptItem)['photos']
                        : [],
                    'can_split'                   => $this->canSplitPackageDuringReceiving($pickupAssignment, $receiptItem, $pickupAssignment->shipment),
                    'split_lock_reason'           => $this->receivingSplitLockReason($pickupAssignment, $receiptItem, $pickupAssignment->shipment),
                ];
            })->values() ?? [],
        ];

        $shipment = $pickupAssignment->shipment;
        $invoice  = $shipment?->invoice;
        $invoices = $shipment?->invoices ?? collect();
        $admin    = Auth::guard('admin')->user();

        $receiptConfig['invoice_store_url']   = route('warehouse.invoices.store', ['pickupAssignment' => $pickupAssignment]);
        $receiptConfig['payments_data_url']   = route('warehouse.receipts.payments.data', ['pickupAssignment' => $pickupAssignment]);
        $receiptConfig['payments_store_url']  = route('warehouse.receipts.payments.store', ['pickupAssignment' => $pickupAssignment]);
        $receiptConfig['payment_destroy_url'] = route('warehouse.payments.destroy', ['payment' => '__ID__']);
        $receiptConfig['payment_download_url'] = route('warehouse.payments.download', ['payment' => '__ID__']);
        $receiptConfig['payment_print_url']   = route('warehouse.payments.print', ['payment' => '__ID__']);
        $receiptConfig['invoice_show_url']    = route('warehouse.invoices.show', ['invoice' => '__INVOICE__']);
        $receiptConfig['can_create_invoice']  = $admin->hasPermission('warehouse.invoices.create');
        $receiptConfig['can_edit_invoice']    = $admin->hasPermission('warehouse.invoices.edit');
        $receiptConfig['can_view_invoice']    = $admin->hasPermission('warehouse.invoices.view');
        $receiptConfig['is_hq_user']          = $admin->isHqUser();
        $receiptConfig['is_super_admin']      = $admin->isHqUser();
        $receiptConfig['invoice']             = $invoice ? [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'status'         => $invoice->status?->value ?? (string) $invoice->status,
            'pickup_fee'     => (float) $invoice->pickup_fee,
            'transport_fee'  => (float) $invoice->transport_fee,
            'handling_fee'   => (float) $invoice->handling_fee,
            'other_fee'      => (float) $invoice->other_fee,
            'total_amount'   => (float) $invoice->total_amount,
            'notes'          => $invoice->notes,
            'sent_at'        => $invoice->sent_at?->format('Y-m-d H:i'),
            'accepted_at'    => $invoice->accepted_at?->format('Y-m-d H:i'),
            'rejected_at'    => $invoice->rejected_at?->format('Y-m-d H:i'),
            'cancelled_at'   => $invoice->cancelled_at?->format('Y-m-d H:i'),
            'download_url'   => route('warehouse.invoices.download', ['invoice' => $invoice]),
            'print_url'      => route('warehouse.invoices.print', ['invoice' => $invoice]),
            'update_url'     => route('warehouse.invoices.update', ['invoice' => $invoice]),
            'send_url'       => route('warehouse.invoices.send', ['invoice' => $invoice]),
            'cancel_url'     => route('warehouse.invoices.cancel', ['invoice' => $invoice]),
            'admin_accept_url' => route('warehouse.invoices.admin-accept', ['invoice' => $invoice]),
            'payments_data_url' => route('warehouse.invoices.payments.data', ['invoice' => $invoice]),
        ] : null;
        $receiptConfig['invoice_history'] = $invoices->map(fn ($inv) => [
            'id'               => $inv->id,
            'invoice_number'   => $inv->invoice_number,
            'status'           => $inv->status?->value ?? (string) $inv->status,
            'status_label'     => ucfirst($inv->status?->value ?? (string) $inv->status),
            'is_active'        => in_array($inv->status?->value ?? (string) $inv->status, ['pending', 'sent', 'accepted']),
            'total_amount'     => (float) $inv->total_amount,
            'notes'            => $inv->notes,
            'created_at'       => $inv->created_at?->format('Y-m-d H:i'),
            'sent_at'          => $inv->sent_at?->format('Y-m-d H:i'),
            'accepted_at'      => $inv->accepted_at?->format('Y-m-d H:i'),
            'cancelled_at'     => $inv->cancelled_at?->format('Y-m-d H:i'),
            'download_url'     => route('warehouse.invoices.download', ['invoice' => $inv]),
            'print_url'        => route('warehouse.invoices.print', ['invoice' => $inv]),
            'update_url'       => route('warehouse.invoices.update', ['invoice' => $inv]),
            'send_url'         => route('warehouse.invoices.send', ['invoice' => $inv]),
            'cancel_url'       => route('warehouse.invoices.cancel', ['invoice' => $inv]),
            'admin_accept_url' => route('warehouse.invoices.admin-accept', ['invoice' => $inv]),
            'payments_data_url' => route('warehouse.invoices.payments.data', ['invoice' => $inv]),
        ])->values()->all();

        return view('warehouse.receipts.show', [
            'warehouse'   => $warehouse,
            'assignment'  => $pickupAssignment,
            'statusLabel' => $this->statusLabel($pickupAssignment->status?->value ?? (string) $pickupAssignment->status),
            'receipt'     => $receipt,
            'receiptConfig' => $receiptConfig,
        ]);
    }

    public function receivedPickupsIndex(): View
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.pickups.received', [
            'warehouse' => $warehouse,
            'statuses' => $this->allPickupStatuses(),
        ]);
    }

    public function receivedPickupsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.receiving.manage');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->portalService->receivedPickupsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('shipment', fn (Builder $sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('received_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('received_at', '<=', $dateTo);
        }

        $sortBy = $request->input('sort', 'received_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['assigned_at', 'received_at', 'arrived_warehouse_at', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('received_at');
        }

        return $this->paginatedResponse($query, $request, function (PickupAssignment $assignment) {
            $status = $assignment->status?->value ?? (string) $assignment->status;

            return [
                'id' => $assignment->id,
                'shipment_number' => $assignment->shipment?->shipment_number,
                'driver_name' => $assignment->driver?->name,
                'driver_phone' => $assignment->driver?->phone,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'assigned_at' => optional($assignment->assigned_at)?->format('Y-m-d H:i:s'),
                'arrived_warehouse_at' => optional($assignment->arrived_warehouse_at)?->format('Y-m-d H:i:s'),
                'received_at' => optional($assignment->received_at)?->format('Y-m-d H:i:s'),
                'received_warehouse' => $assignment->receivedWarehouse?->name,
                'receive_notes' => $assignment->receive_notes,
                'view_url' => route('warehouse.pickups.received.show', $assignment),
            ];
        });
    }

    private function scopedReceivingShipment(PickupAssignment $pickupAssignment): array
    {
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        if ((int) $pickupAssignment->target_warehouse_id !== (int) $warehouse->id) {
            abort(404);
        }

        $shipment = $this->reloadReceivingShipment($pickupAssignment->shipment);
        if (! $shipment || (int) ($shipment->pickupAssignment?->id ?? 0) !== (int) $pickupAssignment->id) {
            abort(404);
        }

        return [$warehouse, $shipment];
    }

    private function warehouseReceivingPayload(PickupAssignment $pickupAssignment, Warehouse $warehouse): array
    {
        $pickupAssignment->load([
            'shipment.deliveryRegion:id,name',
            'shipment.deliveryDistrict:id,name',
            'shipment.items.images',
            'shipment.items.deliveryRegion:id,name',
            'shipment.items.deliveryDistrict:id,name',
            'photos:id,pickup_assignment_id,shipment_item_id,path,type,created_at',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
            'warehouseReceipt.items.photos',
        ]);

        $receipt = $this->portalService->receiptForAssignment($pickupAssignment, $warehouse);
        $shipment = $pickupAssignment->shipment;

        return [
            'shipment' => $this->serializeReceiptShipment($shipment),
            'items' => $this->serializeReceiptItems($pickupAssignment, $shipment, $receipt),
            'receipt' => $receipt ? [
                'id' => $receipt->id,
                'status' => $receipt->status,
                'status_label' => $this->receiptStatusLabel($receipt->status),
                'has_discrepancies' => $receipt->items->contains(fn ($item) => $item->discrepancy_type !== 'none'),
            ] : null,
            'can_auto_group' => $this->canAutoGroupByPhoneDuringReceiving($pickupAssignment, $shipment),
            'auto_group_lock_reason' => $this->receivingAutoGroupLockReason($pickupAssignment, $shipment),
        ];
    }

    private function serializeReceiptShipment(?Shipment $shipment): ?array
    {
        if (! $shipment) {
            return null;
        }

        return [
            'id' => $shipment->id,
            'destination_mode' => $shipment->destination_mode?->value ?? (string) $shipment->destination_mode,
            'pickup_town' => $shipment->pickup_town,
            'pickup_landmark' => $shipment->pickup_landmark,
            'pickup_gh_post_address' => $shipment->pickup_gh_post_address,
            'pickup_instructions' => $shipment->pickup_instructions,
            'pickup_latitude' => $shipment->pickup_latitude,
            'pickup_longitude' => $shipment->pickup_longitude,
            'pickup_region' => $shipment->pickupRegion ? ['id' => $shipment->pickupRegion->id, 'name' => $shipment->pickupRegion->name] : null,
            'pickup_district' => $shipment->pickupDistrict ? ['id' => $shipment->pickupDistrict->id, 'name' => $shipment->pickupDistrict->name] : null,
            'delivery_recipient_name' => $shipment->delivery_recipient_name,
            'delivery_recipient_phone' => $shipment->delivery_recipient_phone,
            'delivery_region_id' => $shipment->delivery_region_id,
            'delivery_district_id' => $shipment->delivery_district_id,
            'delivery_town' => $shipment->delivery_town,
            'delivery_landmark' => $shipment->delivery_landmark,
            'delivery_instructions' => $shipment->delivery_instructions,
            'delivery_region_name' => $shipment->deliveryRegion?->name,
            'delivery_district_name' => $shipment->deliveryDistrict?->name,
        ];
    }

    private function serializeReceiptItems(PickupAssignment $pickupAssignment, Shipment $shipment, ?WarehouseReceipt $receipt): array
    {
        $pickupAssignment->loadMissing([
            'photos:id,pickup_assignment_id,shipment_item_id,path,type,created_at',
            'itemConfirmations:id,pickup_assignment_id,shipment_item_id,expected_quantity,confirmed_quantity,notes,confirmed_at',
        ]);

        return $shipment->items->map(function (ShipmentItem $item) use ($pickupAssignment, $receipt, $shipment) {
            $receiptItem = $receipt?->items?->firstWhere('shipment_item_id', $item->id);
            $driverConfirmation = $pickupAssignment->itemConfirmations->firstWhere('shipment_item_id', $item->id);
            $driverPhotos = $pickupAssignment->photos
                ->where('shipment_item_id', $item->id)
                ->values()
                ->map(fn ($photo) => [
                    'id' => $photo->id,
                    'type' => $photo->type,
                    'url' => $this->storageService->getUrl($photo->path),
                    'created_at' => optional($photo->created_at)?->toIso8601String(),
                ])->values();

            $vendorPhotos = $this->serializeVendorPackagePhotos($item);

            $vendorQuantity = (int) $item->quantity;
            $driverQuantity = $driverConfirmation ? (int) $driverConfirmation->confirmed_quantity : null;
            $expectedQuantity = $driverQuantity ?? $vendorQuantity;

            return [
                'shipment_item_id' => $item->id,
                'description' => $item->description,
                'tracking_code' => $item->tracking_code,
                'delivery_recipient_name' => $item->delivery_recipient_name ?: $shipment->delivery_recipient_name,
                'delivery_recipient_phone' => $item->delivery_recipient_phone ?: $shipment->delivery_recipient_phone,
                'delivery_region_id' => $item->delivery_region_id ?: $shipment->delivery_region_id,
                'delivery_district_id' => $item->delivery_district_id ?: $shipment->delivery_district_id,
                'delivery_region_name' => $item->deliveryRegion?->name ?: $shipment->deliveryRegion?->name,
                'delivery_district_name' => $item->deliveryDistrict?->name ?: $shipment->deliveryDistrict?->name,
                'delivery_town' => $item->delivery_town ?: $shipment->delivery_town,
                'delivery_landmark' => $item->delivery_landmark ?: $shipment->delivery_landmark,
                'delivery_instructions' => $item->delivery_instructions ?: $shipment->delivery_instructions,
                'delivery_preference' => $item->delivery_preference ?? $shipment->delivery_preference ?? 'deliver',
                'delivery_method' => $item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT,
                'fulfillment_type' => $item->fulfillment_type?->value ?? $shipment->fulfillment_type?->value ?? 'warehouse',
                'vendor_quantity' => $vendorQuantity,
                'vendor_declared_quantity' => (int) ($shipment->vendor_declared_quantity ?? $vendorQuantity),
                'driver_picked_quantity' => $pickupAssignment && !is_null($pickupAssignment->driver_picked_quantity) ? (int) $pickupAssignment->driver_picked_quantity : null,
                'driver_confirmed_quantity' => $driverQuantity,
                'driver_qty_matches_vendor' => $driverQuantity !== null ? $driverQuantity === $vendorQuantity : null,
                'vendor_photos' => $vendorPhotos,
                'driver_photos' => $driverPhotos,
                'expected_quantity' => $expectedQuantity,
                'expected_source' => $driverQuantity !== null ? 'driver' : 'vendor_fallback',
                'received_quantity' => (int) ($receiptItem?->received_quantity ?? 0),
                'damaged_quantity' => (int) ($receiptItem?->damaged_quantity ?? 0),
                'discrepancy_type' => $receiptItem?->discrepancy_type ?? 'none',
                'condition_status' => $receiptItem?->condition_status ?? 'ok',
                'notes' => $receiptItem?->notes,
                'barcode_value' => $receiptItem?->barcode_value,
                'barcode_print_count' => (int) ($receiptItem?->barcode_print_count ?? 0),
                'photos' => $receiptItem
                    ? $this->warehouseReceivingService->serializeReceiptItem($receiptItem)['photos']
                    : [],
                'can_split' => $this->canSplitPackageDuringReceiving($pickupAssignment, $receiptItem, $shipment),
                'split_lock_reason' => $this->receivingSplitLockReason($pickupAssignment, $receiptItem, $shipment),
            ];
        })->values()->all();
    }

    private function serializeVendorPackagePhotos(ShipmentItem $item): array
    {
        return $item->images
            ->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->getSignedUrl()['url'] ?? null,
                'original_name' => $image->original_name,
                'recipient_phone' => $image->recipient_phone,
            ])
            ->filter(fn ($photo) => ! empty($photo['url']))
            ->values()
            ->all();
    }

    public function receivedItemsIndex(): View
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        return view('warehouse.items.received', [
            'warehouse' => $warehouse,
        ]);
    }

    public function receivedItemsData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->portalService->receivedItemsQuery($warehouse);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function (Builder $q) use ($search) {
                $q->whereHas('shipmentItem.shipment', fn (Builder $sq) => $sq->where('shipment_number', 'like', "%{$search}%"))
                    ->orWhereHas('shipmentItem', fn (Builder $iq) => $iq->where('description', 'like', "%{$search}%"))
                    ->orWhereHas('receipt.pickupAssignment.driver', fn (Builder $dq) => $dq->where('name', 'like', "%{$search}%"));
            });
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('received_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('received_at', '<=', $dateTo);
        }

        $sortBy = $request->input('sort', 'received_at');
        $sortDirection = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['received_at', 'received_quantity', 'expected_quantity', 'damaged_quantity', 'discrepancy_type', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->latest('received_at');
        }

        return $this->paginatedResponse($query, $request, function (WarehouseReceiptItem $item) {
            return [
                'id' => $item->id,
                'warehouse_receipt_id' => $item->warehouse_receipt_id,
                'pickup_assignment_id' => $item->receipt?->pickup_assignment_id,
                'shipment_number' => $item->shipmentItem?->shipment?->shipment_number,
                'item_description' => $item->shipmentItem?->description,
                'expected_quantity' => (int) $item->expected_quantity,
                'received_quantity' => (int) $item->received_quantity,
                'damaged_quantity' => (int) $item->damaged_quantity,
                'discrepancy_type' => $item->discrepancy_type,
                'driver_name' => $item->receipt?->pickupAssignment?->driver?->name,
                'received_at' => optional($item->received_at)?->format('Y-m-d H:i:s'),
                'notes' => $item->notes,
                'view_url' => route('warehouse.items.received.show', $item->id),
            ];
        });
    }

    public function receivedItemShow(WarehouseReceiptItem $warehouseReceiptItem): View
    {
        $this->authorizePermission('warehouse.items.scan');

        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());

        $warehouseReceiptItem->load(['receipt:id,pickup_assignment_id,warehouse_id,status,finalized_at']);
        if ((int) $warehouseReceiptItem->receipt->warehouse_id !== (int) $warehouse->id) {
            abort(403);
        }

        $warehouseReceiptItem->load([
            'receipt.pickupAssignment.driver:id,name,phone',
            'receipt.pickupAssignment.shipment:id,shipment_number,created_at',
            'shipmentItem.shipment:id,shipment_number,created_at',
            'shipmentItem.images',
            'photos',
            'receivedBy:id,name',
        ]);

        $barcodeSvg = $warehouseReceiptItem->barcode_value
            ? $this->barcodeService->renderCode128Svg($warehouseReceiptItem->barcode_value)
            : null;

        $warehousePhotos = $warehouseReceiptItem->photos
            ->groupBy('photo_type')
            ->map(fn ($group) => $group->map(fn ($photo) => [
                'url' => $this->storageService->getUrl($photo->path),
                'original_name' => $photo->original_name,
                'photo_type' => $photo->photo_type,
            ])->values());

        $vendorPhotos = $warehouseReceiptItem->shipmentItem->images->map(fn ($img) => [
            'url' => $this->storageService->getUrl($img->path),
            'original_name' => $img->original_name,
        ])->values();

        return view('warehouse.items.received-show', [
            'warehouse' => $warehouse,
            'receiptItem' => $warehouseReceiptItem,
            'barcodeSvg' => $barcodeSvg,
            'warehousePhotos' => $warehousePhotos,
            'vendorPhotos' => $vendorPhotos,
        ]);
    }

    private function paginatedResponse(Builder $query, Request $request, callable $mapper): JsonResponse
    {
        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $rows = $query->skip($offset)->take($perPage)->get()->map($mapper)->values();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
                'from' => $total > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    protected function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function pendingStatuses(): array
    {
        return array_values(array_map(function (PickupAssignmentStatus $status) {
            return [
                'value' => $status->value,
                'label' => $this->statusLabel($status->value),
            ];
        }, array_filter(PickupAssignmentStatus::cases(), fn (PickupAssignmentStatus $status) => $status !== PickupAssignmentStatus::CANCELLED)));
    }

    private function allPickupStatuses(): array
    {
        return array_map(fn (PickupAssignmentStatus $status) => [
            'value' => $status->value,
            'label' => $this->statusLabel($status->value),
        ], PickupAssignmentStatus::cases());
    }

    private function statusLabel(?string $status): string
    {
        return match ($status) {
            PickupAssignmentStatus::ASSIGNED->value => 'Assigned',
            PickupAssignmentStatus::EN_ROUTE->value => 'En Route',
            PickupAssignmentStatus::ARRIVED->value => 'Arrived',
            PickupAssignmentStatus::PICKING_UP->value => 'Picking Up',
            PickupAssignmentStatus::COMPLETED->value => 'Pickup Completed',
            PickupAssignmentStatus::CANCELLED->value => 'Cancelled',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }

    private function receiptStatusLabel(?string $status): string
    {
        return match ($status) {
            WarehouseReceipt::STATUS_DRAFT => 'Draft',
            WarehouseReceipt::STATUS_DISCREPANCY_OPEN => 'Discrepancy Open',
            WarehouseReceipt::STATUS_FINALIZED => 'Finalized',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : '-',
        };
    }
}
