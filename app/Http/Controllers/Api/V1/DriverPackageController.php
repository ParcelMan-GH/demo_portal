<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\RiderPackageTransfer;
use App\Models\RiderTeamHandoverItem;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Services\DriverPackageOperationsService;
use App\Services\RiderTeamHandoverService;
use App\Services\Warehouse\WarehouseDeliveryService;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverPackageController extends Controller
{
    /**
     * Scan and claim a package label.
     * POST /api/v1/driver/scan-claim
     */
    public function scanClaim(Request $request): JsonResponse
    {
        $driver = $request->user();

        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $label = WarehouseReceiptItemLabel::with(['receiptItem.receipt', 'latestCustody', 'riderTeamHandoverItem.handover.team'])
            ->where('barcode_value', $validated['barcode'])
            ->first();

        if (!$label) {
            return response()->json([
                'success' => false,
                'message' => 'Label not found. Check the barcode and try again.',
            ], 404);
        }

        $result = app(RiderTeamHandoverService::class)->claimFromScan(
            $driver,
            $label,
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null,
            $validated['notes'] ?? null,
        );

        if (($result['status'] ?? null) === 'conflict') {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Package claimed successfully.',
            'action' => $result['status'] ?? 'claimed',
            'data' => ['label' => $this->transformLabel($label->fresh())],
        ]);
    }

    /**
     * Get all packages currently held by the driver.
     * GET /api/v1/driver/my-packages
     */
    public function myPackages(Request $request): JsonResponse
    {
        $driver = $request->user();

        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        // Get all labels where this driver's claim is the latest event
        // Step 1: Get all label IDs this driver has ever claimed
        $driverClaimedLabelIds = LabelCustodyEvent::query()
            ->where('driver_id', $driver->id)
            ->where('event_type', LabelCustodyEvent::TYPE_CLAIMED)
            ->distinct()
            ->pluck('warehouse_receipt_item_label_id');

        // Step 2: For each of those labels, check if the latest event is still a claim by this driver
        $activeLabelIds = [];
        foreach ($driverClaimedLabelIds as $labelId) {
            $latest = LabelCustodyEvent::where('warehouse_receipt_item_label_id', $labelId)
                ->latest('id')
                ->first();
            if ($latest && $latest->event_type === LabelCustodyEvent::TYPE_CLAIMED && $latest->driver_id === $driver->id) {
                $activeLabelIds[] = $latest->id;
            }
        }

        $eventsQuery = LabelCustodyEvent::query()
            ->whereIn('id', $activeLabelIds);

        if (!empty($validated['from_date'])) {
            $eventsQuery->whereDate('created_at', '>=', $validated['from_date']);
        }
        if (!empty($validated['to_date'])) {
            $eventsQuery->whereDate('created_at', '<=', $validated['to_date']);
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $offset = (int) ($validated['offset'] ?? 0);

        $claimedEvents = $eventsQuery->get();

        $allocatedItemsQuery = RiderTeamHandoverItem::query()
            ->with(['handover.team:id,name', 'handover.receiver:id,name,phone', 'allocatedTo:id,name,phone'])
            ->where('allocated_to_driver_id', $driver->id)
            ->where('status', RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER);

        if (!empty($validated['from_date'])) {
            $allocatedItemsQuery->whereDate('allocated_at', '>=', $validated['from_date']);
        }
        if (!empty($validated['to_date'])) {
            $allocatedItemsQuery->whereDate('allocated_at', '<=', $validated['to_date']);
        }

        $allocatedItems = $allocatedItemsQuery->get();

        $claimRows = $claimedEvents->map(fn (LabelCustodyEvent $event) => [
            'label_id' => (int) $event->warehouse_receipt_item_label_id,
            'claimed_event' => $event,
            'handover_item' => null,
            'sort_at' => $event->created_at,
        ]);

        $allocatedRows = $allocatedItems
            ->reject(fn (RiderTeamHandoverItem $item) => $claimedEvents->contains('warehouse_receipt_item_label_id', $item->warehouse_receipt_item_label_id))
            ->map(fn (RiderTeamHandoverItem $item) => [
                'label_id' => (int) $item->warehouse_receipt_item_label_id,
                'claimed_event' => null,
                'handover_item' => $item,
                'sort_at' => $item->allocated_at ?: $item->updated_at,
            ]);

        $rows = $claimRows
            ->concat($allocatedRows)
            ->sortByDesc(fn (array $row) => $row['sort_at']?->timestamp ?? 0)
            ->values();

        $total = $rows->count();
        $pagedRows = $rows->slice($offset, $limit)->values();
        $contextByLabelId = $pagedRows->keyBy('label_id');
        $claimedLabelIds = $pagedRows->pluck('label_id');

        $labels = WarehouseReceiptItemLabel::whereIn('id', $claimedLabelIds)
            ->with([
                'receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_recipient_phone,delivery_town',
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_method',
                'riderTeamHandoverItem.handover.team:id,name',
                'riderTeamHandoverItem.handover.receiver:id,name,phone',
            ])
            ->get();

        // Check which labels cannot be started again: labels already covered
        // by active run quantity, plus delivered quantity from completed runs
        // whose custody event is still claimed.
        $shipmentItemIds = $labels
            ->map(fn ($label) => $label->receiptItem?->shipment_item_id)
            ->filter()
            ->unique()
            ->values();

        $activeQuantitiesByItemId = \App\Models\DeliveryRunItem::query()
            ->whereIn('shipment_item_id', $shipmentItemIds)
            ->whereHas('run', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->selectRaw('shipment_item_id, COALESCE(SUM(expected_quantity), 0) as active_quantity')
            ->groupBy('shipment_item_id')
            ->pluck('active_quantity', 'shipment_item_id');

        $deliveredQuantitiesByItemId = \App\Models\DeliveryRunItem::query()
            ->whereIn('shipment_item_id', $shipmentItemIds)
            ->where('status', \App\Models\DeliveryRunItem::STATUS_DELIVERED)
            ->selectRaw('shipment_item_id, COALESCE(SUM(expected_quantity), 0) as delivered_quantity')
            ->groupBy('shipment_item_id')
            ->pluck('delivered_quantity', 'shipment_item_id');

        $unavailableLabelIds = collect();
        $labels
            ->groupBy(fn ($label) => (int) $label->receiptItem?->shipment_item_id)
            ->each(function ($itemLabels, int $itemId) use ($activeQuantitiesByItemId, $deliveredQuantitiesByItemId, $unavailableLabelIds) {
                if ($itemId <= 0) {
                    return;
                }

                $unavailableQuantity = max(
                    (int) ($activeQuantitiesByItemId->get($itemId) ?? 0),
                    (int) ($deliveredQuantitiesByItemId->get($itemId) ?? 0)
                );

                if ($unavailableQuantity <= 0) {
                    return;
                }

                $itemLabels
                    ->sortBy(fn ($label) => str_pad((string) ($label->label_index ?? 0), 10, '0', STR_PAD_LEFT)
                        . '|'
                        . str_pad((string) $label->id, 10, '0', STR_PAD_LEFT))
                    ->take($unavailableQuantity)
                    ->pluck('id')
                    ->each(fn ($labelId) => $unavailableLabelIds->push($labelId));
            });


        $labelsById = $labels->keyBy('id');

        $pendingTransfersByItemId = RiderPackageTransfer::query()
            ->with(['toDriver:id,name,phone', 'fromDriver:id,name,phone'])
            ->whereIn('shipment_item_id', $shipmentItemIds)
            ->where('status', RiderPackageTransfer::STATUS_PENDING)
            ->latest('requested_at')
            ->get()
            ->keyBy('shipment_item_id');

        $packages = $pagedRows->map(function (array $row) use ($driver, $labelsById, $unavailableLabelIds, $pendingTransfersByItemId) {
            $label = $labelsById->get($row['label_id']);
            if (! $label) {
                return null;
            }

            /** @var LabelCustodyEvent|null $event */
            $event = $row['claimed_event'];
            /** @var RiderTeamHandoverItem|null $handoverItem */
            $handoverItem = $row['handover_item'] ?: $label->riderTeamHandoverItem;
            $data = $this->transformLabel($label, $event, $handoverItem);
            $data['claimed_at'] = $event?->created_at?->toIso8601String();
            $data['in_delivery_run'] = $unavailableLabelIds->contains($label->id);
            $shipmentItemId = (int) ($label->receiptItem?->shipment_item_id ?? 0);
            $pendingTransfer = $shipmentItemId > 0 ? $pendingTransfersByItemId->get($shipmentItemId) : null;
            $teamAllocatedToDriver = $handoverItem
                && (int) $handoverItem->allocated_to_driver_id === (int) $driver->id
                && $handoverItem->status === RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER;
            $driverHasCustody = (bool) $event || $teamAllocatedToDriver;
            $data['pending_transfer'] = $pendingTransfer ? $this->transformTransfer($pendingTransfer) : null;
            $data['can_change_location'] = $driverHasCustody && ! $data['in_delivery_run'] && ! $pendingTransfer;
            $data['can_transfer'] = $driverHasCustody && ! $data['in_delivery_run'] && ! $pendingTransfer;
            $data['can_start_delivery'] = $driverHasCustody && ! $data['in_delivery_run'] && ! $pendingTransfer;
            return $data;
        })->filter()->values();

        return response()->json([
            'success' => true,
            'message' => 'Your packages retrieved.',
            'data' => [
                'packages' => $packages,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ],
        ]);
    }

    public function changePackageLocation(
        Request $request,
        string $trackingCode,
        DriverPackageOperationsService $operations
    ): JsonResponse {
        $driver = $request->user();

        $validated = $request->validate([
            'delivery_town' => ['required', 'string', 'max:255'],
            'delivery_region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'delivery_district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'proof_photo' => ['required', 'image', 'max:12288'],
        ]);

        $change = $operations->changeLocation($driver, $trackingCode, $validated, $request->file('proof_photo'));

        return response()->json([
            'success' => true,
            'message' => 'Delivery location updated.',
            'data' => [
                'change' => $this->transformLocationChange($change->fresh(['driver', 'shipmentItem'])),
            ],
        ]);
    }

    public function requestTransfer(
        Request $request,
        string $trackingCode,
        DriverPackageOperationsService $operations
    ): JsonResponse {
        $driver = $request->user();

        $validated = $request->validate([
            'receiver_phone' => ['required', 'string', 'max:30'],
        ]);

        $transfer = $operations->requestTransfer($driver, $trackingCode, $validated['receiver_phone']);

        return response()->json([
            'success' => true,
            'message' => 'Transfer request sent.',
            'data' => [
                'transfer' => $this->transformTransfer($transfer),
            ],
        ]);
    }

    public function incomingTransfers(Request $request): JsonResponse
    {
        $driver = $request->user();

        $transfers = RiderPackageTransfer::query()
            ->with([
                'shipmentItem.shipment:id,shipment_number',
                'fromDriver:id,name,phone',
                'toDriver:id,name,phone',
            ])
            ->where('to_driver_id', $driver->id)
            ->where('status', RiderPackageTransfer::STATUS_PENDING)
            ->latest('requested_at')
            ->get()
            ->map(fn (RiderPackageTransfer $transfer) => $this->transformTransfer($transfer))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Incoming transfers retrieved.',
            'data' => [
                'transfers' => $transfers,
            ],
        ]);
    }

    public function outgoingTransfers(Request $request): JsonResponse
    {
        $driver = $request->user();

        $transfers = RiderPackageTransfer::query()
            ->with([
                'shipmentItem.shipment:id,shipment_number',
                'fromDriver:id,name,phone',
                'toDriver:id,name,phone',
            ])
            ->where('from_driver_id', $driver->id)
            ->latest('requested_at')
            ->limit(100)
            ->get()
            ->map(fn (RiderPackageTransfer $transfer) => $this->transformTransfer($transfer))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Outgoing transfers retrieved.',
            'data' => [
                'transfers' => $transfers,
            ],
        ]);
    }

    public function acceptTransfer(
        Request $request,
        RiderPackageTransfer $transfer,
        DriverPackageOperationsService $operations
    ): JsonResponse {
        $accepted = $operations->acceptTransfer($request->user(), $transfer);

        return response()->json([
            'success' => true,
            'message' => 'Transfer accepted.',
            'data' => [
                'transfer' => $this->transformTransfer($accepted),
            ],
        ]);
    }

    public function rejectTransfer(
        Request $request,
        RiderPackageTransfer $transfer,
        DriverPackageOperationsService $operations
    ): JsonResponse {
        $rejected = $operations->rejectTransfer($request->user(), $transfer);

        return response()->json([
            'success' => true,
            'message' => 'Transfer rejected.',
            'data' => [
                'transfer' => $this->transformTransfer($rejected),
            ],
        ]);
    }

    public function cancelTransfer(
        Request $request,
        RiderPackageTransfer $transfer,
        DriverPackageOperationsService $operations
    ): JsonResponse {
        $cancelled = $operations->cancelTransfer($request->user(), $transfer);

        return response()->json([
            'success' => true,
            'message' => 'Transfer request cancelled.',
            'data' => [
                'transfer' => $this->transformTransfer($cancelled),
            ],
        ]);
    }

    public function recallTransfer(
        Request $request,
        RiderPackageTransfer $transfer,
        DriverPackageOperationsService $operations
    ): JsonResponse {
        $recalled = $operations->recallTransfer($request->user(), $transfer);

        return response()->json([
            'success' => true,
            'message' => 'Transfer recalled.',
            'data' => [
                'transfer' => $this->transformTransfer($recalled),
            ],
        ]);
    }

    /**
     * Release a package (put back / unclaim).
     * POST /api/v1/driver/release-package
     */
    public function releasePackage(Request $request): JsonResponse
    {
        $driver = $request->user();

        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $label = WarehouseReceiptItemLabel::where('barcode_value', $validated['barcode'])->first();

        if (!$label) {
            return response()->json(['success' => false, 'message' => 'Label not found.'], 404);
        }

        $latestEvent = $label->custodyEvents()->latest()->first();

        if (!$latestEvent || $latestEvent->event_type !== LabelCustodyEvent::TYPE_CLAIMED || $latestEvent->driver_id !== $driver->id) {
            return response()->json(['success' => false, 'message' => 'You do not currently hold this package.'], 400);
        }

        // Block release if package is in an active delivery run
        $shipmentItemId = $label->receiptItem?->shipment_item_id;
        if ($shipmentItemId) {
            $inActiveRun = \App\Models\DeliveryRunItem::query()
                ->where('shipment_item_id', $shipmentItemId)
                ->whereHas('run', function ($q) use ($driver) {
                    $q->where('assigned_driver_id', $driver->id)
                      ->whereNotIn('status', ['completed', 'cancelled']);
                })
                ->exists();

            if ($inActiveRun) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot release — this package is in an active delivery run. Use "Failed Delivery" at the stop if you cannot deliver.',
                ], 400);
            }
        }

        LabelCustodyEvent::create([
            'warehouse_receipt_item_label_id' => $label->id,
            'event_type' => LabelCustodyEvent::TYPE_RELEASED,
            'driver_id' => $driver->id,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package released.',
        ]);
    }

    /**
     * Get custody history for a specific label.
     * GET /api/v1/driver/package-history/{barcode}
     */
    public function packageHistory(Request $request, string $barcode): JsonResponse
    {
        $label = WarehouseReceiptItemLabel::where('barcode_value', $barcode)->first();

        if (!$label) {
            return response()->json(['success' => false, 'message' => 'Label not found.'], 404);
        }

        $events = $label->custodyEvents()
            ->with('driver:id,name,phone')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($e) => [
                'event_type' => $e->event_type,
                'driver_name' => $e->driver?->name,
                'driver_phone' => $e->driver?->phone,
                'notes' => $e->notes,
                'created_at' => $e->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'barcode' => $label->barcode_value,
                'label' => $this->transformLabel($label),
                'history' => $events,
            ],
        ]);
    }

    private function transformLabel(
        WarehouseReceiptItemLabel $label,
        ?LabelCustodyEvent $claimedEvent = null,
        ?RiderTeamHandoverItem $handoverItem = null
    ): array
    {
        $label->loadMissing([
            'receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_method',
            'riderTeamHandoverItem.handover.team:id,name',
            'riderTeamHandoverItem.handover.receiver:id,name,phone',
        ]);

        $item = $label->receiptItem?->shipmentItem;
        $shipment = $item?->shipment;
        $handoverItem = $handoverItem ?: $label->riderTeamHandoverItem;
        $handover = $handoverItem?->handover;
        $isTeamPackage = (bool) $handoverItem;
        $isClaimed = (bool) $claimedEvent;
        $deliveryMethod = $item?->delivery_method ?? ShipmentItem::DELIVERY_METHOD_DIRECT;

        // Use per-item delivery if available, otherwise shipment-level
        $recipientName = $item?->delivery_recipient_name ?: $shipment?->delivery_recipient_name;
        $recipientPhone = $item?->delivery_recipient_phone ?: $shipment?->delivery_recipient_phone;
        $deliveryTown = $item?->delivery_town ?: $shipment?->delivery_town;
        $routeLabel = $deliveryMethod === ShipmentItem::DELIVERY_METHOD_BUS_HANDOFF
            ? 'Bus Station'
            : ($recipientName ?: 'Unknown');

        return [
            'barcode' => $label->barcode_value,
            'label_index' => $label->label_index,
            'labels_total' => $label->labels_total,
            'label_type' => $label->label_type,
            'shipment_number' => $shipment?->shipment_number,
            'description' => $item?->description,
            'tracking_code' => $item?->tracking_code,
            'delivery_method' => $deliveryMethod,
            'route_label' => $routeLabel,
            'recipient_name' => $recipientName,
            'recipient_phone' => $recipientPhone,
            'delivery_town' => $deliveryTown,
            'custody_status' => $isClaimed ? 'claimed' : ($handoverItem?->status ?: 'available'),
            'claim_source' => $isTeamPackage ? ($isClaimed ? 'rider_team_claimed' : 'rider_team_assigned') : 'self_scan',
            'is_claimed' => $isClaimed,
            'team' => $handover?->team ? [
                'id' => $handover->team->id,
                'name' => $handover->team->name,
            ] : null,
            'handover' => $handover ? [
                'id' => $handover->id,
                'handover_number' => $handover->handover_number,
            ] : null,
            'assigned_by' => $handover?->receiver ? [
                'id' => $handover->receiver->id,
                'name' => $handover->receiver->name,
                'phone' => $handover->receiver->phone,
            ] : null,
            'allocated_at' => $handoverItem?->allocated_at?->toIso8601String(),
            'member_claimed_at' => $handoverItem?->member_claimed_at?->toIso8601String(),
        ];
    }

    private function transformTransfer(RiderPackageTransfer $transfer): array
    {
        $transfer->loadMissing([
            'shipmentItem.shipment:id,shipment_number',
            'fromDriver:id,name,phone',
            'toDriver:id,name,phone',
        ]);

        $item = $transfer->shipmentItem;

        return [
            'id' => $transfer->id,
            'status' => $transfer->status,
            'tracking_code' => $item?->tracking_code,
            'description' => $item?->description,
            'shipment_number' => $item?->shipment?->shipment_number,
            'recipient_name' => $item?->delivery_recipient_name ?: $item?->shipment?->delivery_recipient_name,
            'delivery_town' => $item?->delivery_town ?: $item?->shipment?->delivery_town,
            'from_driver' => $transfer->fromDriver ? [
                'id' => $transfer->fromDriver->id,
                'name' => $transfer->fromDriver->name,
                'phone' => $transfer->fromDriver->phone,
            ] : null,
            'to_driver' => $transfer->toDriver ? [
                'id' => $transfer->toDriver->id,
                'name' => $transfer->toDriver->name,
                'phone' => $transfer->toDriver->phone,
            ] : null,
            'requested_at' => $transfer->requested_at?->toIso8601String(),
            'responded_at' => $transfer->responded_at?->toIso8601String(),
        ];
    }

    private function transformLocationChange($change): array
    {
        return [
            'id' => $change->id,
            'tracking_code' => $change->shipmentItem?->tracking_code,
            'old_location' => $change->old_location,
            'new_location' => $change->new_location,
            'changed_at' => $change->changed_at?->toIso8601String(),
            'driver' => $change->driver ? [
                'id' => $change->driver->id,
                'name' => $change->driver->name,
                'phone' => $change->driver->phone,
            ] : null,
        ];
    }

    /**
     * Start deliveries — auto-create a delivery run from claimed packages.
     * POST /api/v1/driver/start-deliveries
     */
    public function startDeliveries(Request $request): JsonResponse
    {
        $driver = $request->user();

        $validated = $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'barcodes' => ['nullable', 'array'],
            'barcodes.*' => ['string', 'max:100'],
        ]);

        // Resolve warehouse — try from the driver's last pickup assignment, or from request
        $warehouseId = $validated['warehouse_id'] ?? null;
        if (!$warehouseId) {
            // Find the warehouse from one of the claimed labels
            $claimedLabel = LabelCustodyEvent::query()
                ->where('driver_id', $driver->id)
                ->where('event_type', LabelCustodyEvent::TYPE_CLAIMED)
                ->whereIn('id', function ($q) {
                    $q->selectRaw('MAX(id)')->from('label_custody_events')->groupBy('warehouse_receipt_item_label_id');
                })
                ->with('label.receiptItem.receipt')
                ->latest()
                ->first();

            $warehouseId = $claimedLabel?->label?->receiptItem?->receipt?->warehouse_id;
        }

        if (!$warehouseId) {
            return response()->json(['success' => false, 'message' => 'Could not determine warehouse. Please try again.'], 422);
        }

        $warehouse = Warehouse::findOrFail($warehouseId);

        $selectedItemIds = collect($validated['barcodes'] ?? [])
            ->filter()
            ->whenNotEmpty(function ($barcodes) {
                return WarehouseReceiptItemLabel::query()
                    ->whereIn('barcode_value', $barcodes)
                    ->with('receiptItem:id,shipment_item_id')
                    ->get()
                    ->map(fn ($label) => $label->receiptItem?->shipment_item_id)
                    ->filter()
                    ->unique()
                    ->values();
            });

        if ($selectedItemIds->isNotEmpty() && app(DriverPackageOperationsService::class)->hasPendingTransferFromDriver($driver->id, $selectedItemIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected packages have a pending rider transfer.',
            ], 422);
        }

        $deliveryService = app(WarehouseDeliveryService::class);
        $result = $deliveryService->createRunFromClaims(
            $driver,
            $warehouse,
            null,
            $validated['barcodes'] ?? null
        );

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }
}
