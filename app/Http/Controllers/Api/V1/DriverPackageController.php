<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
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

        // Get all labels where the latest custody event is 'claimed' by this driver
        $claimedLabelIds = LabelCustodyEvent::query()
            ->where('driver_id', $driver->id)
            ->where('event_type', LabelCustodyEvent::TYPE_CLAIMED)
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('label_custody_events')
                    ->groupBy('warehouse_receipt_item_label_id');
            })
            ->pluck('warehouse_receipt_item_label_id');

        $labels = WarehouseReceiptItemLabel::whereIn('id', $claimedLabelIds)
            ->with([
                'receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_recipient_phone,delivery_town',
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            ])
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Your packages retrieved.',
            'data' => [
                'packages' => $labels->map(fn ($label) => $this->transformLabel($label))->values(),
                'total' => $labels->count(),
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
}
