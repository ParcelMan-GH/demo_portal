<?php

namespace App\Http\Controllers\Warehouse;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Models\ShipmentItem;
use App\Models\PaymentWallet;
use App\Models\OtpCode;
use App\Models\RecipientPaymentSession;
use App\Models\RecipientPaymentSessionEntry;
use App\Models\RecipientPaymentTask;
use App\Models\SortBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceiptItem;
use App\Models\WarehouseReceiptItemPhoto;
use App\Services\StorageService;
use App\Services\WalkinShipmentService;
use App\Services\Warehouse\BarcodeService;
use App\Services\Warehouse\RecipientPaymentService;
use App\Services\Warehouse\WarehousePackageLedgerService;
use App\Services\Warehouse\WarehousePortalService;
use App\Services\Warehouse\WarehouseReceivingService;
use App\Services\Warehouse\WarehouseSortingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehousePackageLedgerService $ledgerService,
        private WarehouseSortingService $sortingService,
        private WarehouseReceivingService $receivingService,
        private RecipientPaymentService $recipientPaymentService,
        private WalkinShipmentService $walkinShipmentService,
        private StorageService $storageService,
        private BarcodeService $barcodeService,
    ) {
    }

    public function legacyIndex(): RedirectResponse
    {
        return redirect()->route('warehouse.packages.index');
    }

    public function index(): View
    {
        $this->authorizePermission('warehouse.items.scan');

        return $this->packageIndexView(
            endpoint: route('warehouse.packages.data'),
            title: 'Warehouse Packages',
            subtitle: 'Every package that has passed through',
            exportFileName: 'warehouse-packages',
        );
    }

    public function busStationIndex(): View
    {
        $this->authorizePermission('warehouse.items.scan');

        return $this->packageIndexView(
            endpoint: route('warehouse.bus-station-packages.data'),
            title: 'Bus Station Packages',
            subtitle: 'Packages marked to be sent through a bus station at',
            exportFileName: 'bus-station-packages',
            forcedFilters: ['delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF],
        );
    }

    private function packageIndexView(
        string $endpoint,
        string $title,
        string $subtitle,
        string $exportFileName,
        array $forcedFilters = [],
    ): View {
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $openBatches = SortBatch::query()
            ->where('origin_warehouse_id', $warehouse->id)
            ->where('status', SortBatch::STATUS_OPEN)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'batch_number', 'dispatch_mode', 'destination_warehouse_id']);
        $warehouseUsers = User::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $transferWarehouses = Warehouse::query()
            ->where('is_active', true)
            ->whereKeyNot($warehouse->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Warehouse $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ])
            ->values();

        return view('warehouse.items.received', [
            'warehouse' => $warehouse,
            'pageTitle' => $title,
            'pageSubtitle' => $subtitle,
            'dataEndpoint' => $endpoint,
            'exportFileName' => $exportFileName,
            'forcedFilters' => $forcedFilters,
            'openBatches' => $openBatches,
            'warehouseUsers' => $warehouseUsers,
            'transferWarehouses' => $transferWarehouses,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');

        return $this->packageDataResponse($request);
    }

    public function busStationData(Request $request): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');

        $request->merge(['delivery_method' => ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF]);

        return $this->packageDataResponse($request);
    }

    private function packageDataResponse(Request $request): JsonResponse
    {
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $query = $this->ledgerService->applyFilters($this->ledgerService->query($warehouse), $request);
        $summary = $this->ledgerService->summary(clone $query);

        $sortBy = $request->input('sort', 'received_at');
        $direction = $request->input('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        if (in_array($sortBy, ['received_at', 'received_quantity', 'damaged_quantity', 'created_at'], true)) {
            $query->orderBy($sortBy, $direction);
        } else {
            $query->latest('received_at');
        }

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $rows = $query->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $rows->map(fn (WarehouseReceiptItem $item) => $this->ledgerService->map($item))->values(),
            'summary' => $summary,
            'meta' => [
                'current_page' => $page,
                'from' => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to' => min($page * $perPage, $total),
                'total' => $total,
                'last_page' => max((int) ceil($total / $perPage), 1),
            ],
        ]);
    }

    public function show(WarehouseReceiptItem $warehouseReceiptItem): View
    {
        $this->authorizePermission('warehouse.items.scan');

        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $receiptItem = $this->ledgerService->query($warehouse)
            ->whereKey($warehouseReceiptItem->id)
            ->firstOrFail();

        $detail = $this->packageDetailPayload($receiptItem, $warehouse);

        $openPaymentSessions = RecipientPaymentSession::query()
            ->where('user_id', $user?->id)
            ->where('warehouse_id', $warehouse->id)
            ->where('status', RecipientPaymentSession::STATUS_OPEN)
            ->whereDate('started_at', today())
            ->get(['id', 'payment_wallet_id', 'started_at'])
            ->keyBy('payment_wallet_id');

        $wallets = PaymentWallet::query()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('warehouse_id')->orWhere('warehouse_id', $warehouse->id))
            ->when(
                !$user?->hasPermission('warehouse.recipient_payments.manage_wallets'),
                fn ($query) => $query->whereHas('assignedUsers', fn ($assigned) => $assigned->whereKey($user?->id))
            )
            ->orderBy('name')
            ->get(['id', 'name', 'provider', 'phone_number', 'account_owner'])
            ->map(function (PaymentWallet $wallet) use ($openPaymentSessions) {
                $session = $openPaymentSessions->get($wallet->id);

                return [
                    'id' => $wallet->id,
                    'name' => $wallet->name,
                    'provider' => $wallet->provider,
                    'phone_number' => $wallet->phone_number,
                    'account_owner' => $wallet->account_owner,
                    'has_open_session' => (bool) $session,
                    'open_session_id' => $session?->id,
                    'session_started_at' => $this->humanDate($session?->started_at),
                ];
            })
            ->values();

        $openBatches = SortBatch::query()
            ->where('origin_warehouse_id', $warehouse->id)
            ->where('status', SortBatch::STATUS_OPEN)
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'batch_number', 'dispatch_mode', 'destination_warehouse_id']);
        $warehouseUsers = User::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $transferWarehouses = Warehouse::query()
            ->where('is_active', true)
            ->whereKeyNot($warehouse->id)
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(fn (Warehouse $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'code' => $item->code,
            ])
            ->values();

        $config = [
            'package' => $detail,
            'update_url' => route('warehouse.packages.update', ['warehouseReceiptItem' => $receiptItem]),
            'print_label_url' => route('warehouse.packages.print-label', ['warehouseReceiptItem' => $receiptItem]),
            'delivery_fee_url' => route('warehouse.packages.delivery-fee', ['warehouseReceiptItem' => $receiptItem]),
            'mark_paid_url' => route('warehouse.packages.mark-paid', ['warehouseReceiptItem' => $receiptItem]),
            'location_search_url' => route('warehouse.locations.search'),
            'payment_sessions_url' => route('warehouse.recipient-payments.sessions'),
            'back_url' => route('warehouse.packages.index'),
            'current_user_id' => $user?->id,
            'transfer_warehouses' => $transferWarehouses,
            'open_batches' => $openBatches->map(fn ($batch) => [
                'id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'dispatch_mode' => $batch->dispatch_mode,
            ])->values(),
            'warehouse_users' => $warehouseUsers->map(fn ($warehouseUser) => [
                'id' => $warehouseUser->id,
                'name' => $warehouseUser->name,
            ])->values(),
            'wallets' => $wallets,
            'permissions' => [
                'can_edit_package' => (bool) $user?->hasPermission('warehouse.items.scan'),
                'can_process_payments' => (bool) $user?->hasPermission('warehouse.recipient_payments.process'),
                'can_assign_payments' => (bool) $user?->hasPermission('warehouse.recipient_payments.assign'),
                'can_override_payments' => (bool) $user?->hasPermission('warehouse.recipient_payments.override'),
                'can_manage_wallets' => (bool) $user?->hasPermission('warehouse.recipient_payments.manage_wallets'),
            ],
        ];

        return view('warehouse.items.received-show', [
            'warehouse' => $warehouse,
            'receiptItem' => $receiptItem,
            'package' => $detail,
            'config' => $config,
        ]);
    }

    public function update(Request $request, WarehouseReceiptItem $warehouseReceiptItem): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseReceiptItem($warehouseReceiptItem, $warehouse->id);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:500'],
            'quantity' => ['required', 'integer', 'min:1'],
            'delivery_recipient_name' => ['nullable', 'string', 'max:255'],
            'delivery_recipient_phone' => ['nullable', 'string', 'max:20'],
            'delivery_region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'delivery_town' => ['nullable', 'string', 'max:255'],
            'delivery_landmark' => ['nullable', 'string', 'max:255'],
            'delivery_instructions' => ['nullable', 'string', 'max:1000'],
            'delivery_method' => ['nullable', 'in:direct,bus_handoff'],
            'forward_to_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['file', 'image', 'max:12288'],
            'remove_photo_ids' => ['nullable', 'array'],
            'remove_photo_ids.*' => ['integer', 'exists:warehouse_receipt_item_photos,id'],
        ]);

        $relations = [
            'sortBatchItems.sortBatch',
            'shipmentItem.images',
            'receipt.pickupAssignment.photos',
            'photos',
        ];
        if (Schema::hasTable('delivery_run_items') && Schema::hasTable('delivery_run_stops')) {
            $relations[] = 'shipmentItem.deliveryRunItems.stop';
        }
        if (Schema::hasTable('transport_manifest_items') && Schema::hasTable('transport_manifests')) {
            $relations[] = 'shipmentItem.transportManifestItems.manifest';
        }
        $warehouseReceiptItem->load($relations);

        $item = $warehouseReceiptItem->shipmentItem ?: $warehouseReceiptItem->shipmentItem()->firstOrFail();
        $currentSortBatchItem = $warehouseReceiptItem->sortBatchItems
            ->whereNull('removed_at')
            ->sortByDesc('id')
            ->first();
        $currentBatch = $currentSortBatchItem?->sortBatch;
        $deliveryRunItem = $item->relationLoaded('deliveryRunItems')
            ? $item->deliveryRunItems->sortByDesc('id')->first()
            : null;
        $deliveryStop = $deliveryRunItem?->stop;
        $manifest = $item->relationLoaded('transportManifestItems')
            ? $item->transportManifestItems->sortByDesc('id')->first()?->manifest
            : null;
        $newDeliveryMethod = $validated['delivery_method'] ?? ShipmentItem::DELIVERY_METHOD_DIRECT;
        $deliveryMethodChanged = $newDeliveryMethod !== ($item->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT);
        $busHandoffLocked = $deliveryStop?->status === \App\Models\DeliveryRunStop::STATUS_HANDED_OFF || filled($deliveryStop?->handoff_at) || $deliveryStop?->status === \App\Models\DeliveryRunStop::STATUS_DELIVERED;

        if ($deliveryMethodChanged && $busHandoffLocked) {
            return response()->json([
                'success' => false,
                'message' => 'Bus station handoff cannot be changed after handoff or delivery.',
            ], 422);
        }

        $currentForwardWarehouseId = $currentBatch?->dispatch_mode === SortBatch::DISPATCH_TRANSFER
            ? (int) $currentBatch->destination_warehouse_id
            : null;
        $requestedForwardWarehouseId = !empty($validated['forward_to_warehouse_id'])
            ? (int) $validated['forward_to_warehouse_id']
            : null;
        if ($requestedForwardWarehouseId === (int) $warehouse->id) {
            $requestedForwardWarehouseId = null;
        }
        $forwardChanged = $requestedForwardWarehouseId !== $currentForwardWarehouseId;
        $forwardLocked = $manifest || $deliveryRunItem || ($currentBatch && $currentBatch->status !== SortBatch::STATUS_OPEN);

        if ($forwardChanged && $forwardLocked) {
            return response()->json([
                'success' => false,
                'message' => 'Forwarding cannot be changed after the package is sealed, manifested, or assigned to delivery.',
            ], 422);
        }

        $removePhotoIds = collect($validated['remove_photo_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
        $receiptPhotos = $warehouseReceiptItem->photos;
        $removablePhotoIds = $receiptPhotos->pluck('id')->map(fn ($id) => (int) $id);
        $invalidRemoval = $removePhotoIds->diff($removablePhotoIds)->isNotEmpty();

        if ($invalidRemoval) {
            return response()->json([
                'success' => false,
                'message' => 'Only receipt photos attached to this package can be removed here.',
            ], 422);
        }

        $fallbackPhotoCount = (int) ($item->images?->count() ?? 0)
            + (int) ($warehouseReceiptItem->receipt?->pickupAssignment?->photos
                ?->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $item->id)
                ->count() ?? 0);
        $remainingReceiptPhotoCount = $receiptPhotos
            ->reject(fn ($photo) => $removePhotoIds->contains((int) $photo->id))
            ->count() + count($request->file('photos', []));

        if ($fallbackPhotoCount === 0 && $remainingReceiptPhotoCount < 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one package photo is required. Upload a replacement before removing the last receipt photo.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($item, $validated, $newDeliveryMethod, $warehouseReceiptItem, $request, $warehouse, $currentBatch, $requestedForwardWarehouseId, $forwardChanged, $removePhotoIds) {
                $item->update([
                    'description' => $validated['description'],
                    'quantity' => (int) $validated['quantity'],
                    'delivery_recipient_name' => $validated['delivery_recipient_name'] ?? null,
                    'delivery_recipient_phone' => !empty($validated['delivery_recipient_phone']) ? PhoneHelper::format($validated['delivery_recipient_phone']) : null,
                    'delivery_region_id' => $validated['delivery_region_id'] ?? null,
                    'delivery_district_id' => $validated['delivery_district_id'] ?? null,
                    'delivery_town' => $validated['delivery_town'] ?? null,
                    'delivery_landmark' => $validated['delivery_landmark'] ?? null,
                    'delivery_instructions' => $validated['delivery_instructions'] ?? null,
                    'delivery_method' => $newDeliveryMethod,
                ]);

                foreach ($request->file('photos', []) as $photo) {
                    if (!$photo) {
                        continue;
                    }

                    $stored = $this->storageService->upload($photo, 'warehouse-receipts/' . $warehouseReceiptItem->warehouse_receipt_id);
                    WarehouseReceiptItemPhoto::create([
                        'warehouse_receipt_item_id' => $warehouseReceiptItem->id,
                        'path' => $stored['path'],
                        'original_name' => $stored['original_name'],
                        'size' => $stored['size'],
                        'photo_type' => 'proof',
                        'created_by_user_id' => Auth::guard('admin')->id(),
                    ]);
                }

                if ($removePhotoIds->isNotEmpty()) {
                    $photosToDelete = WarehouseReceiptItemPhoto::query()
                        ->where('warehouse_receipt_item_id', $warehouseReceiptItem->id)
                        ->whereIn('id', $removePhotoIds->all())
                        ->get();

                    foreach ($photosToDelete as $photo) {
                        $this->storageService->delete($photo->path);
                        if (Storage::disk('public')->exists($photo->path)) {
                            Storage::disk('public')->delete($photo->path);
                        }
                        $photo->delete();
                    }
                }

                if ($forwardChanged) {
                    $user = Auth::guard('admin')->user();
                    if ($currentBatch) {
                        $result = $this->sortingService->removeItem($currentBatch, $item, $warehouse, $user);
                        if (!($result['success'] ?? false)) {
                            throw new \RuntimeException($result['message'] ?? 'Unable to remove package from current batch.');
                        }
                    }

                    if ($requestedForwardWarehouseId) {
                        $result = $this->sortingService->autoRouteReceiptItemsToDestinationBatches([
                            [
                                'warehouse_receipt_item_id' => $warehouseReceiptItem->id,
                                'destination_warehouse_id' => $requestedForwardWarehouseId,
                            ],
                        ], $warehouse, $user);

                        if (!($result['success'] ?? false)) {
                            throw new \RuntimeException($result['message'] ?? 'Unable to forward package to selected warehouse.');
                        }
                    }
                }
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $fresh = $this->ledgerService->query($warehouse)->whereKey($warehouseReceiptItem->id)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Package details updated.',
            'data' => $this->ledgerService->map($fresh),
        ]);
    }

    public function moveSortBatch(Request $request, WarehouseReceiptItem $warehouseReceiptItem): JsonResponse
    {
        $this->authorizePermission('warehouse.sorting.manage');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseReceiptItem($warehouseReceiptItem, $warehouse->id);

        $validated = $request->validate([
            'sort_batch_id' => ['required', 'integer', 'exists:sort_batches,id'],
        ]);

        $targetBatch = SortBatch::query()->findOrFail((int) $validated['sort_batch_id']);
        $relations = ['sortBatchItems.sortBatch'];
        if (\Illuminate\Support\Facades\Schema::hasTable('transport_manifest_items') && \Illuminate\Support\Facades\Schema::hasTable('transport_manifests')) {
            $relations[] = 'shipmentItem.transportManifestItems.manifest';
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('delivery_run_items') && \Illuminate\Support\Facades\Schema::hasTable('delivery_runs')) {
            $relations[] = 'shipmentItem.deliveryRunItems.run';
        }
        $warehouseReceiptItem->load($relations);

        $currentSortBatchItem = $warehouseReceiptItem->sortBatchItems
            ->whereNull('removed_at')
            ->sortByDesc('id')
            ->first();
        $currentBatch = $currentSortBatchItem?->sortBatch;

        if (!$this->ledgerService->canMoveSortBatch($warehouseReceiptItem->shipmentItem, $currentBatch)) {
            return response()->json([
                'success' => false,
                'message' => $this->ledgerService->sortLockReason($warehouseReceiptItem->shipmentItem, $currentBatch) ?? 'Package cannot be moved.',
            ], 422);
        }

        if ($currentBatch && (int) $currentBatch->id === (int) $targetBatch->id) {
            return response()->json(['success' => true, 'message' => 'Package is already in that sort batch.']);
        }

        try {
            DB::transaction(function () use ($currentBatch, $targetBatch, $warehouseReceiptItem, $warehouse) {
                $user = Auth::guard('admin')->user();
                if ($currentBatch) {
                    $result = $this->sortingService->removeItem($currentBatch, $warehouseReceiptItem->shipmentItem, $warehouse, $user);
                    if (!($result['success'] ?? false)) {
                        throw new \RuntimeException($result['message'] ?? 'Unable to remove package from current batch.');
                    }
                }

                $result = $this->sortingService->addItems($targetBatch, $warehouse, $user, [$warehouseReceiptItem->id]);
                if (!($result['success'] ?? false)) {
                    throw new \RuntimeException($result['message'] ?? 'Unable to add package to target batch.');
                }
            });
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $fresh = $this->ledgerService->query($warehouse)->whereKey($warehouseReceiptItem->id)->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Sort batch updated.',
            'data' => $this->ledgerService->map($fresh),
        ]);
    }

    public function printLabel(Request $request, WarehouseReceiptItem $warehouseReceiptItem): JsonResponse
    {
        $this->authorizePermission('warehouse.items.scan');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseReceiptItem($warehouseReceiptItem, $warehouse->id);

        $validated = $request->validate([
            'label_count' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);
        $labelCount = (int) ($validated['label_count'] ?? 1);

        $warehouseReceiptItem->load(['receipt.pickupAssignment', 'shipmentItem']);
        $assignment = $warehouseReceiptItem->receipt?->pickupAssignment;
        if (!$warehouseReceiptItem->shipmentItem) {
            return response()->json([
                'success' => false,
                'message' => 'Package cannot be printed because the package record is missing.',
            ], 422);
        }

        if (!$assignment) {
            $result = $this->walkinShipmentService->printWalkinItemLabel(
                shipmentItem: $warehouseReceiptItem->shipmentItem,
                warehouse: $warehouse,
                user: Auth::guard('admin')->user(),
                labelCount: $labelCount
            );

            return response()->json($result, $result['success'] ? 200 : 422);
        }

        $result = $this->receivingService->generateLabels(
            assignment: $assignment,
            shipmentItem: $warehouseReceiptItem->shipmentItem,
            warehouse: $warehouse,
            user: Auth::guard('admin')->user(),
            labelCount: $labelCount,
            labelType: $labelCount === 1 ? 'sealed' : 'unit'
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function setDeliveryFee(Request $request, WarehouseReceiptItem $warehouseReceiptItem): JsonResponse
    {
        $this->authorizePermission('warehouse.recipient_payments.process');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseReceiptItem($warehouseReceiptItem, $warehouse->id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $task = $this->paymentTaskForPackage($warehouseReceiptItem, $warehouse);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient payment task could not be created for this package. Check recipient phone and delivery setup.',
            ], 422);
        }

        $user = Auth::guard('admin')->user();
        $claim = $this->claimPaymentTaskForUser($task, $user);
        if (!($claim['success'] ?? false)) {
            return response()->json($claim, $claim['conflict'] ?? false ? 403 : 422);
        }

        $result = $this->recipientPaymentService->setFee($claim['task'], (float) $validated['amount'], $user, $validated['notes'] ?? null);
        if (!($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        return response()->json($this->packageActionResponse($warehouseReceiptItem, $warehouse, $result['message'] ?? 'Delivery fee saved.'));
    }

    public function markDeliveryPaid(Request $request, WarehouseReceiptItem $warehouseReceiptItem): JsonResponse
    {
        $this->authorizePermission('warehouse.recipient_payments.process');
        $warehouse = $this->portalService->resolveWarehouse(Auth::guard('admin')->user());
        $this->ensureWarehouseReceiptItem($warehouseReceiptItem, $warehouse->id);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_wallet_id' => ['required', 'integer', 'exists:payment_wallets,id'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'outcome' => ['required', 'string', 'in:answered,no_answer,busy,wrong_number,callback,payment_promised'],
            'payment_receipt' => ['nullable', 'image', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $task = $this->paymentTaskForPackage($warehouseReceiptItem, $warehouse);
        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient payment task could not be created for this package. Check recipient phone and delivery setup.',
            ], 422);
        }

        $wallet = PaymentWallet::query()
            ->where(fn ($query) => $query->whereNull('warehouse_id')->orWhere('warehouse_id', $warehouse->id))
            ->findOrFail((int) $validated['payment_wallet_id']);
        $user = Auth::guard('admin')->user();
        $claim = $this->claimPaymentTaskForUser($task, $user);
        if (!($claim['success'] ?? false)) {
            return response()->json($claim, $claim['conflict'] ?? false ? 403 : 422);
        }

        $feeResult = $this->recipientPaymentService->setFee($claim['task'], (float) $validated['amount'], $user, $validated['notes'] ?? null);
        if (!($feeResult['success'] ?? false)) {
            return response()->json($feeResult, 422);
        }
        $task = RecipientPaymentTask::query()->findOrFail($claim['task']->id);
        $result = $this->recipientPaymentService->markPaid(
            $task,
            $wallet,
            $user,
            $validated['payment_reference'] ?? null,
            $validated['notes'] ?? null,
            !$user?->hasPermission('warehouse.recipient_payments.manage_wallets')
        );

        if (!($result['success'] ?? false)) {
            return response()->json($result, 422);
        }

        $receiptPath = $request->file('payment_receipt')?->store('recipient-payment-receipts', 'public');
        if ($receiptPath) {
            $entry = RecipientPaymentSessionEntry::query()
                ->where('recipient_payment_task_id', $task->id)
                ->latest('id')
                ->first();
            $entry?->update(['receipt_path' => $receiptPath]);
        }
        $this->recipientPaymentService->logCall($task, $user, $validated['outcome'], $validated['notes'] ?? null);

        return response()->json($this->packageActionResponse($warehouseReceiptItem, $warehouse, $result['message'] ?? 'Recipient payment marked paid.'));
    }

    private function packageActionResponse(WarehouseReceiptItem $warehouseReceiptItem, Warehouse $warehouse, string $message): array
    {
        $fresh = $this->ledgerService->query($warehouse)
            ->whereKey($warehouseReceiptItem->id)
            ->firstOrFail();

        return [
            'success' => true,
            'message' => $message,
            'data' => $this->packageDetailPayload($fresh, $warehouse),
        ];
    }

    private function paymentTaskForPackage(WarehouseReceiptItem $warehouseReceiptItem, Warehouse $warehouse): ?RecipientPaymentTask
    {
        $warehouseReceiptItem->loadMissing([
            'receipt',
            'shipmentItem.shipment',
            'sortBatchItems.sortBatch',
        ]);

        $sortBatchItem = $warehouseReceiptItem->sortBatchItems
            ->whereNull('removed_at')
            ->sortByDesc('id')
            ->first();

        if ($sortBatchItem) {
            return $this->recipientPaymentService->ensureTaskForSortBatchItem($sortBatchItem);
        }

        return $this->recipientPaymentService->ensureLocalDeliveryTaskForReceiptItem($warehouseReceiptItem, $warehouse);
    }

    private function claimPaymentTaskForUser(RecipientPaymentTask $task, User $user): array
    {
        $task->loadMissing('assignedTo:id,name');

        if ($task->assigned_to_user_id && (int) $task->assigned_to_user_id !== (int) $user->id) {
            $canTakeOver = $user->hasPermission('warehouse.recipient_payments.assign')
                || $user->hasPermission('warehouse.recipient_payments.override')
                || $user->hasPermission('warehouse.recipient_payments.manage_wallets');

            if (!$canTakeOver) {
                return [
                    'success' => false,
                    'conflict' => true,
                    'message' => 'This payment task is assigned to ' . ($task->assignedTo?->name ?: 'another user') . '. Ask a supervisor to reassign it before processing.',
                ];
            }

            $task->forceFill([
                'assigned_to_user_id' => $user->id,
                'assigned_at' => now(),
                'status' => in_array($task->status, [RecipientPaymentTask::STATUS_PENDING, RecipientPaymentTask::STATUS_FAILED, RecipientPaymentTask::STATUS_DISPUTED], true)
                    ? RecipientPaymentTask::STATUS_ASSIGNED
                    : $task->status,
            ])->save();

            return [
                'success' => true,
                'message' => 'Payment task reassigned to you.',
                'task' => $task->fresh(['assignedTo']),
            ];
        }

        return $this->recipientPaymentService->claimTaskForUser($task, $user);
    }

    private function packageDetailPayload(WarehouseReceiptItem $receiptItem, Warehouse $warehouse): array
    {
        $row = $this->ledgerService->map($receiptItem);
        $shipmentItem = $receiptItem->shipmentItem;
        $shipment = $shipmentItem?->shipment;
        $sortBatchItems = $receiptItem->sortBatchItems->sortBy('id')->values();
        $manifestItems = $shipmentItem && $shipmentItem->relationLoaded('transportManifestItems')
            ? $shipmentItem->transportManifestItems->sortBy('id')->values()
            : collect();
        $deliveryRunItems = $shipmentItem && $shipmentItem->relationLoaded('deliveryRunItems')
            ? $shipmentItem->deliveryRunItems->sortBy('id')->values()
            : collect();
        $latestTask = $this->latestPaymentTask($receiptItem);
        $collection = $shipment?->collection;

        $sortHistory = $sortBatchItems->map(function ($item) {
            $batch = $item->sortBatch;
            return [
                'id' => $item->id,
                'batch_id' => $batch?->id,
                'number' => $batch?->batch_number,
                'status' => $this->statusLabel($batch?->status),
                'dispatch_mode' => $this->statusLabel($batch?->dispatch_mode),
                'origin' => $batch?->originWarehouse?->name,
                'destination' => $batch?->destinationWarehouse?->name,
                'added_at' => $this->humanDate($item->added_at ?? $item->created_at),
                'added_by' => $item->addedBy?->name,
                'removed_at' => $this->humanDate($item->removed_at),
                'sealed_at' => $this->humanDate($batch?->sealed_at),
                'sealed_by' => $batch?->sealedBy?->name,
                'url' => $batch ? route('warehouse.sorting.show', $batch) : null,
            ];
        })->values();

        $manifestHistory = $manifestItems->map(function ($item) {
            $manifest = $item->manifest;
            return [
                'id' => $item->id,
                'manifest_id' => $manifest?->id,
                'number' => $manifest?->manifest_number,
                'status' => $this->statusLabel($manifest?->status),
                'line_status' => $this->statusLabel($item->line_status),
                'origin' => $manifest?->originWarehouse?->name,
                'destination' => $manifest?->destinationWarehouse?->name,
                'driver' => $manifest?->assignedDriver?->name,
                'driver_phone' => $manifest?->assignedDriver?->phone,
                'created_by' => $manifest?->createdBy?->name,
                'received_by' => $manifest?->receivedBy?->name,
                'loaded_at' => $this->humanDate($item->loaded_at),
                'received_at' => $this->humanDate($item->received_at ?? $manifest?->received_at),
                'url' => $manifest ? route('warehouse.manifests.transport.show', $manifest) : null,
            ];
        })->values();

        $deliveryHistory = $deliveryRunItems->map(function ($item) {
            $run = $item->run;
            $stop = $item->stop;
            $handoffConfirmation = $item->busHandoffConfirmation;
            $proofPhotoUrl = $stop?->proof_photo_path ? $this->storageService->getUrl($stop->proof_photo_path) : null;
            $canViewOtp = (bool) Auth::guard('admin')->user()?->hasPermission('warehouse.delivery.code.reset');
            $otpCode = $canViewOtp ? $this->getDeliveryOtpCode($stop) : null;

            return [
                'id' => $item->id,
                'run_id' => $run?->id,
                'number' => $run?->run_number,
                'status' => $this->statusLabel($item->status ?: $run?->status),
                'run_status' => $this->statusLabel($run?->status),
                'driver' => $run?->assignedDriver?->name,
                'driver_phone' => $run?->assignedDriver?->phone,
                'created_by' => $run?->createdBy?->name,
                'confirmed_by' => $stop?->confirmedBy?->name,
                'assigned_at' => $this->humanDate($run?->assigned_at),
                'dispatched_at' => $this->humanDate($run?->dispatched_at),
                'completed_at' => $this->humanDate($run?->completed_at),
                'stop_status' => $this->statusLabel($stop?->status),
                'stop_recipient' => $stop?->recipient_name,
                'stop_phone' => $stop?->recipient_phone,
                'stop_location' => collect([$stop?->town, $stop?->district?->name, $stop?->region?->name])->filter()->join(', '),
                'stop_landmark' => $stop?->landmark,
                'gh_post_address' => $stop?->gh_post_address,
                'arrived_at' => $this->humanDate($stop?->arrived_at),
                'delivered_at' => $this->humanDate($item->delivered_at ?? $stop?->delivered_at),
                'confirmed_at' => $this->humanDate($stop?->confirmed_at),
                'confirmation_notes' => $stop?->confirmation_notes,
                'delivery_notes' => $stop?->delivery_notes ?? $item->notes,
                'proof_photo_url' => $proofPhotoUrl,
                'delivery_coordinates' => collect([$stop?->delivery_latitude, $stop?->delivery_longitude])->filter(fn ($value) => filled($value))->join(', '),
                'failure_reason' => $this->statusLabel($stop?->failure_reason),
                'failure_notes' => $stop?->failure_notes,
                'verification' => [
                    'code' => $otpCode,
                    'sent_at' => $this->humanDate($stop?->verification_code_sent_at),
                    'expires_at' => $this->humanDate($stop?->verification_code_expires_at),
                    'attempts' => (int) ($stop?->verification_attempts ?? 0),
                    'max_attempts' => (int) ($stop?->max_attempts ?? 0),
                    'can_view_code' => $canViewOtp,
                    'attempt_logs' => $stop && $stop->relationLoaded('verificationAttempts')
                        ? $stop->verificationAttempts->sortByDesc('attempted_at')->map(fn ($attempt) => [
                            'code' => $attempt->entered_code_masked,
                            'success' => (bool) $attempt->is_success,
                            'driver' => $attempt->driver?->name,
                            'attempted_at' => $this->humanDate($attempt->attempted_at),
                        ])->values()
                        : [],
                ],
                'delivery_method' => $this->statusLabel($stop?->delivery_method),
                'bus_handoff' => $stop && $stop->delivery_method === \App\Models\DeliveryRunStop::METHOD_BUS_HANDOFF ? [
                    'bus_station' => $stop->bus_station_name,
                    'courier_name' => $stop->handoff_courier_name,
                    'courier_phone' => $stop->handoff_courier_phone,
                    'vehicle_number' => $stop->handoff_vehicle_number,
                    'handoff_at' => $this->humanDate($stop->handoff_at),
                    'proof_photo_url' => $proofPhotoUrl,
                    'confirmation_status' => $this->statusLabel($handoffConfirmation?->status),
                    'confirmation_source' => $this->statusLabel($handoffConfirmation?->source),
                    'confirmation_target' => $handoffConfirmation?->target_type
                        ? collect([$this->statusLabel($handoffConfirmation->target_type), $handoffConfirmation->target_name, $handoffConfirmation->target_phone])->filter()->join(' / ')
                        : null,
                    'handoff_owner' => $handoffConfirmation?->handoffDriver
                        ? collect([$handoffConfirmation->handoffDriver->name, $handoffConfirmation->handoffDriver->phone])->filter()->join(' / ')
                        : null,
                    'confirmed_by' => $handoffConfirmation?->confirmedByDriver?->name ?: $handoffConfirmation?->confirmedByAdmin?->name,
                    'confirmed_at' => $this->humanDate($handoffConfirmation?->confirmed_at),
                    'reason' => $handoffConfirmation?->reason_label ?: $handoffConfirmation?->reason?->label,
                    'issue_notes' => $handoffConfirmation?->issue_notes,
                    'confirmation_notes' => $handoffConfirmation?->confirmation_notes,
                ] : null,
                'url' => $run ? route('warehouse.deliveries.runs.show', $run) : null,
            ];
        })->values();

        $timeline = collect([
            [
                'label' => 'Shipment created',
                'at' => $this->humanDate($shipment?->created_at),
                'actor' => $row['vendor_name'] ?? null,
                'detail' => $shipment?->shipment_number,
                'tone' => 'slate',
            ],
            [
                'label' => 'Received at warehouse',
                'at' => $row['received_at'],
                'actor' => $row['received_by'] ?? $row['pickup_driver'] ?? null,
                'detail' => $warehouse->name,
                'tone' => 'emerald',
            ],
        ])
            ->merge($sortHistory->map(fn ($entry) => [
                'label' => 'Added to sort batch',
                'at' => $entry['added_at'],
                'actor' => $entry['added_by'],
                'detail' => $entry['number'],
                'tone' => 'violet',
            ]))
            ->merge($manifestHistory->map(fn ($entry) => [
                'label' => 'Transport manifest',
                'at' => $entry['loaded_at'] ?: $entry['received_at'],
                'actor' => $entry['driver'] ?: $entry['created_by'],
                'detail' => $entry['number'] ? $entry['number'] . ' · ' . $entry['status'] : null,
                'tone' => 'blue',
            ]))
            ->merge($deliveryHistory->map(fn ($entry) => [
                'label' => $entry['bus_handoff'] ? 'Bus/courier handoff' : 'Delivery run',
                'at' => $entry['bus_handoff']['handoff_at'] ?? $entry['delivered_at'] ?? $entry['dispatched_at'],
                'actor' => $entry['confirmed_by'] ?: $entry['driver'],
                'detail' => $entry['bus_handoff']['bus_station'] ?? $entry['number'],
                'tone' => $entry['bus_handoff'] ? 'amber' : 'orange',
            ]))
            ->when($collection, fn ($timeline) => $timeline->push([
                'label' => $collection->isCollected() ? 'Collected at warehouse' : 'Ready for collection',
                'at' => $this->humanDate($collection->collected_at ?: $collection->ready_at),
                'actor' => $collection->handedOverBy?->name,
                'detail' => $collection->isCollected()
                    ? collect([$collection->collected_by_name, $collection->warehouse?->name])->filter()->join(' / ')
                    : $collection->warehouse?->name,
                'tone' => $collection->isCollected() ? 'emerald' : 'amber',
            ]))
            ->filter(fn ($entry) => !empty($entry['at']) || !empty($entry['detail']))
            ->values();
        $lastSort = $sortHistory->last();
        $lastManifest = $manifestHistory->last();
        $lastDelivery = $deliveryHistory->last();

        return array_merge($row, [
            'vendor' => [
                'name' => $shipment?->vendor?->business_name ?: $shipment?->vendor?->name,
                'business_name' => $shipment?->vendor?->business_name,
                'phone' => $shipment?->vendor?->phone,
            ],
            'shipment' => [
                'number' => $shipment?->shipment_number,
                'status' => $this->statusLabel($shipment?->status?->value ?? $shipment?->getRawOriginal('status')),
                'source' => $this->statusLabel($shipment?->source?->value ?? $shipment?->getRawOriginal('source')),
                'destination_mode' => $this->statusLabel($shipment?->destination_mode?->value ?? $shipment?->getRawOriginal('destination_mode')),
                'fulfillment_type' => $this->statusLabel($shipment?->fulfillment_type?->value ?? $shipment?->getRawOriginal('fulfillment_type')),
                'created_at' => $this->humanDate($shipment?->created_at),
                'submitted_at' => $this->humanDate($shipment?->submitted_at),
                'submitted_by' => $shipment?->createdByUser?->name ?: ($shipment?->vendor?->business_name ?: $shipment?->vendor?->name),
            ],
            'warehouse_receipt' => [
                'source' => $row['receipt_source'] ?? null,
                'received_at' => $row['received_at'] ?? null,
                'received_by' => $row['received_by'] ?? null,
                'finalized_at' => $this->humanDate($receiptItem->receipt?->finalized_at),
                'pickup_driver' => $receiptItem->receipt?->pickupAssignment?->driver?->name,
                'pickup_driver_phone' => $receiptItem->receipt?->pickupAssignment?->driver?->phone,
            ],
            'collection' => $collection ? [
                'id' => $collection->id,
                'status' => $collection->status,
                'status_label' => $this->statusLabel($collection->status),
                'is_collected' => $collection->isCollected(),
                'warehouse' => $collection->warehouse?->name,
                'warehouse_code' => $collection->warehouse?->code,
                'ready_at' => $this->humanDate($collection->ready_at),
                'collected_at' => $this->humanDate($collection->collected_at),
                'collected_by_name' => $collection->collected_by_name,
                'collected_by_phone' => $collection->collected_by_phone,
                'collected_by_id_type' => $this->statusLabel($collection->collected_by_id_type),
                'collected_by_id_number' => $collection->collected_by_id_number,
                'handed_over_by' => $collection->handedOverBy?->name,
                'notes' => $collection->notes,
            ] : null,
            'payment_task_id' => $latestTask?->id,
            'payment_task' => [
                'id' => $latestTask?->id,
                'status' => $latestTask?->status,
                'assigned_to_user_id' => $latestTask?->assigned_to_user_id,
                'assigned_to' => $latestTask?->assignedTo?->name,
                'assigned_at' => $this->humanDate($latestTask?->assigned_at),
            ],
            'histories' => [
                'sort_batches' => $sortHistory,
                'manifests' => $manifestHistory,
                'deliveries' => $deliveryHistory,
                'timeline' => $timeline,
            ],
            'audit' => [
                'received_by' => $row['received_by'],
                'sort_last_by' => $lastSort['added_by'] ?? null,
                'manifest_last_by' => $lastManifest['received_by'] ?? $lastManifest['created_by'] ?? null,
                'delivery_last_by' => $lastDelivery['confirmed_by'] ?? $lastDelivery['driver'] ?? null,
                'payment_last_by' => $row['payment']['paid_by'] ?? null,
            ],
        ]);
    }

    private function latestPaymentTask(WarehouseReceiptItem $receiptItem): ?RecipientPaymentTask
    {
        $sortBatchTask = $receiptItem->sortBatchItems
            ->whereNull('removed_at')
            ->sortByDesc('id')
            ->first()?->recipientPaymentTask;
        if ($sortBatchTask) {
            return $sortBatchTask;
        }

        return $receiptItem->shipmentItem && $receiptItem->shipmentItem->relationLoaded('recipientPaymentTasks')
            ? $receiptItem->shipmentItem->recipientPaymentTasks->sortByDesc('id')->first()
            : null;
    }

    private function getDeliveryOtpCode(?\App\Models\DeliveryRunStop $stop): ?string
    {
        if (!$stop || $stop->delivery_method === \App\Models\DeliveryRunStop::METHOD_BUS_HANDOFF || $stop->status === \App\Models\DeliveryRunStop::STATUS_DELIVERED || !$stop->verification_code_sent_at) {
            return null;
        }

        return OtpCode::query()
            ->where('phone', (string) $stop->recipient_phone)
            ->where('purpose', 'delivery_verification')
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->value('code');
    }

    private function humanDate($date): ?string
    {
        return $date ? $date->format('M j, Y g:i A') : null;
    }

    private function statusLabel(?string $status): ?string
    {
        return $status === null || $status === ''
            ? null
            : ucwords(str_replace('_', ' ', $status));
    }

    private function ensureWarehouseReceiptItem(WarehouseReceiptItem $item, int $warehouseId): void
    {
        $item->loadMissing('receipt:id,warehouse_id,status');
        if (!$item->receipt || (int) $item->receipt->warehouse_id !== $warehouseId) {
            abort(403);
        }
    }

    private function authorizePermission(string $permission): void
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }
}
