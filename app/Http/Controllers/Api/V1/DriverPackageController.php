<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
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

        $label = WarehouseReceiptItemLabel::with('receiptItem.receipt')
            ->where('barcode_value', $validated['barcode'])
            ->first();

        if (!$label) {
            return response()->json([
                'success' => false,
                'message' => 'Label not found. Check the barcode and try again.',
            ], 404);
        }

        $receipt = $label->receiptItem?->receipt;
        if (!$receipt || $receipt->status !== WarehouseReceipt::STATUS_FINALIZED) {
            return response()->json([
                'success' => false,
                'message' => 'This package is not ready for driver pickup. Warehouse receiving has not been finalized yet.',
            ], 409);
        }

        // Check if already claimed by another driver
        $latestEvent = $label->custodyEvents()->latest()->first();
        if ($latestEvent
            && $latestEvent->event_type === LabelCustodyEvent::TYPE_CLAIMED
            && $latestEvent->driver_id !== $driver->id
        ) {
            $otherDriver = Driver::find($latestEvent->driver_id);
            return response()->json([
                'success' => false,
                'message' => 'This package is already claimed by ' . ($otherDriver?->name ?? 'another driver') . '.',
            ], 409);
        }

        // Check if already claimed by this driver
        if ($latestEvent
            && $latestEvent->event_type === LabelCustodyEvent::TYPE_CLAIMED
            && $latestEvent->driver_id === $driver->id
        ) {
            return response()->json([
                'success' => true,
                'message' => 'You already have this package.',
                'data' => ['label' => $this->transformLabel($label)],
            ]);
        }

        // Create claim event
        $event = LabelCustodyEvent::create([
            'warehouse_receipt_item_label_id' => $label->id,
            'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
            'driver_id' => $driver->id,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Package claimed successfully.',
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

        $total = $eventsQuery->count();
        $limit = (int) ($validated['limit'] ?? 50);
        $offset = (int) ($validated['offset'] ?? 0);

        $claimedEvents = $eventsQuery->latest('created_at')->skip($offset)->take($limit)->get();
        $claimedLabelIds = $claimedEvents->pluck('warehouse_receipt_item_label_id');
        $claimedAtMap = $claimedEvents->keyBy('warehouse_receipt_item_label_id');

        $labels = WarehouseReceiptItemLabel::whereIn('id', $claimedLabelIds)
            ->with([
                'receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_recipient_phone,delivery_town',
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_method',
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


        $packages = $labels->map(function ($label) use ($claimedAtMap, $unavailableLabelIds) {
            $data = $this->transformLabel($label);
            $event = $claimedAtMap->get($label->id);
            $data['claimed_at'] = $event?->created_at?->toIso8601String();
            $data['in_delivery_run'] = $unavailableLabelIds->contains($label->id);
            return $data;
        })->values();

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

    private function transformLabel(WarehouseReceiptItemLabel $label): array
    {
        $label->loadMissing([
            'receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_method',
        ]);

        $item = $label->receiptItem?->shipmentItem;
        $shipment = $item?->shipment;
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
