<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\Warehouse;
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

        $label = WarehouseReceiptItemLabel::where('barcode_value', $validated['barcode'])->first();

        if (!$label) {
            return response()->json([
                'success' => false,
                'message' => 'Label not found. Check the barcode and try again.',
            ], 404);
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

        // Get all labels where the latest custody event is 'claimed' by this driver
        $eventsQuery = LabelCustodyEvent::query()
            ->where('driver_id', $driver->id)
            ->where('event_type', LabelCustodyEvent::TYPE_CLAIMED)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('label_custody_events')
                    ->groupBy('warehouse_receipt_item_label_id');
            });

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
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            ])
            ->get();

        // Check which items are already in active delivery runs
        $activeRunItemIds = \App\Models\DeliveryRunItem::query()
            ->whereHas('deliveryRun', fn ($q) => $q->whereNotIn('status', ['completed', 'cancelled']))
            ->pluck('shipment_item_id');

        // Check if driver has an active run
        $activeRun = \App\Models\DeliveryRun::query()
            ->where('assigned_driver_id', $driver->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->latest()
            ->first();

        $packages = $labels->map(function ($label) use ($claimedAtMap, $activeRunItemIds) {
            $data = $this->transformLabel($label);
            $event = $claimedAtMap->get($label->id);
            $data['claimed_at'] = $event?->created_at?->toIso8601String();
            $itemId = $label->receiptItem?->shipment_item_id;
            $data['in_delivery_run'] = $itemId ? $activeRunItemIds->contains($itemId) : false;
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
                'active_delivery_run' => $activeRun ? [
                    'id' => $activeRun->id,
                    'run_number' => $activeRun->run_number,
                    'status' => $activeRun->status,
                ] : null,
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
                ->whereHas('deliveryRun', function ($q) use ($driver) {
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
            'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
        ]);

        $item = $label->receiptItem?->shipmentItem;
        $shipment = $item?->shipment;

        // Use per-item delivery if available, otherwise shipment-level
        $recipientName = $item?->delivery_recipient_name ?: $shipment?->delivery_recipient_name;
        $recipientPhone = $item?->delivery_recipient_phone ?: $shipment?->delivery_recipient_phone;
        $deliveryTown = $item?->delivery_town ?: $shipment?->delivery_town;

        return [
            'barcode' => $label->barcode_value,
            'label_index' => $label->label_index,
            'labels_total' => $label->labels_total,
            'label_type' => $label->label_type,
            'shipment_number' => $shipment?->shipment_number,
            'description' => $item?->description,
            'tracking_code' => $item?->tracking_code,
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
        $result = $deliveryService->createRunFromClaims($driver, $warehouse);

        $statusCode = $result['success'] ? 200 : 422;
        return response()->json($result, $statusCode);
    }
}
