<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LabelCustodyEvent;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PackageTrackingController extends Controller
{
    public function index()
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission('shipments.view')) {
            abort(403);
        }

        // Stats
        $totalLabels = WarehouseReceiptItemLabel::count();

        $claimedIds = LabelCustodyEvent::query()
            ->where('event_type', 'claimed')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('label_custody_events')->groupBy('warehouse_receipt_item_label_id');
            })
            ->pluck('warehouse_receipt_item_label_id');

        $deliveredIds = LabelCustodyEvent::query()
            ->where('event_type', 'delivered')
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('label_custody_events')->groupBy('warehouse_receipt_item_label_id');
            })
            ->pluck('warehouse_receipt_item_label_id');

        return view('admin.package-tracking.index', [
            'stats' => [
                'total' => $totalLabels,
                'claimed' => $claimedIds->count(),
                'delivered' => $deliveredIds->count(),
                'unclaimed' => $totalLabels - $claimedIds->count() - $deliveredIds->count(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        if (!$user || !$user->hasPermission('shipments.view')) {
            abort(403);
        }

        $query = WarehouseReceiptItemLabel::query()
            ->with([
                'receiptItem.shipmentItem.shipment:id,shipment_number,delivery_recipient_name,delivery_town',
                'receiptItem.shipmentItem:id,shipment_id,description,tracking_code,delivery_recipient_name,delivery_town',
            ]);

        // Search
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('barcode_value', 'like', "%{$search}%")
                  ->orWhereHas('receiptItem.shipmentItem', fn ($sq) => $sq
                      ->where('tracking_code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                  )
                  ->orWhereHas('receiptItem.shipmentItem.shipment', fn ($sq) => $sq
                      ->where('shipment_number', 'like', "%{$search}%")
                  );
            });
        }

        // Filter by status
        $status = $request->input('status');
        if ($status === 'claimed') {
            $claimedIds = $this->getLatestEventLabelIds('claimed');
            $query->whereIn('id', $claimedIds);
        } elseif ($status === 'unclaimed') {
            $hasEventIds = LabelCustodyEvent::query()->distinct()->pluck('warehouse_receipt_item_label_id');
            $claimedIds = $this->getLatestEventLabelIds('claimed');
            $deliveredIds = $this->getLatestEventLabelIds('delivered');
            $query->whereNotIn('id', $claimedIds->merge($deliveredIds));
        } elseif ($status === 'delivered') {
            $deliveredIds = $this->getLatestEventLabelIds('delivered');
            $query->whereIn('id', $deliveredIds);
        }

        // Filter by driver
        if ($driverId = $request->input('driver_id')) {
            $driverLabelIds = $this->getLatestEventLabelIds('claimed', (int) $driverId);
            $query->whereIn('id', $driverLabelIds);
        }

        $total = $query->count();
        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $page = max((int) $request->input('page', 1), 1);
        $offset = ($page - 1) * $perPage;

        $labels = $query->latest('id')->skip($offset)->take($perPage)->get();

        // Batch load latest custody events
        $labelIds = $labels->pluck('id');
        $latestEvents = LabelCustodyEvent::query()
            ->with('driver:id,name,phone')
            ->whereIn('id', function ($q) use ($labelIds) {
                $q->selectRaw('MAX(id)')
                  ->from('label_custody_events')
                  ->whereIn('warehouse_receipt_item_label_id', $labelIds)
                  ->groupBy('warehouse_receipt_item_label_id');
            })
            ->get()
            ->keyBy('warehouse_receipt_item_label_id');

        $rows = $labels->map(function ($label) use ($latestEvents) {
            $item = $label->receiptItem?->shipmentItem;
            $shipment = $item?->shipment;
            $event = $latestEvents->get($label->id);

            $isClaimed = $event && $event->event_type === 'claimed';
            $isDelivered = $event && $event->event_type === 'delivered';

            return [
                'id' => $label->id,
                'barcode' => $label->barcode_value,
                'label_index' => $label->label_index,
                'labels_total' => $label->labels_total,
                'shipment_number' => $shipment?->shipment_number,
                'description' => $item?->description,
                'tracking_code' => $item?->tracking_code,
                'recipient_name' => $item?->delivery_recipient_name ?: $shipment?->delivery_recipient_name,
                'delivery_town' => $item?->delivery_town ?: $shipment?->delivery_town,
                'status' => $isDelivered ? 'delivered' : ($isClaimed ? 'claimed' : 'unclaimed'),
                'driver' => $isClaimed ? [
                    'id' => $event->driver_id,
                    'name' => $event->driver?->name,
                    'phone' => $event->driver?->phone,
                ] : null,
                'last_event_at' => $event?->created_at?->format('M d, H:i'),
                'created_at' => $label->created_at?->format('M d, Y H:i'),
            ];
        });

        return response()->json([
            'data' => $rows,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    private function getLatestEventLabelIds(string $eventType, ?int $driverId = null)
    {
        $query = LabelCustodyEvent::query()
            ->where('event_type', $eventType)
            ->whereIn('id', function ($q) {
                $q->selectRaw('MAX(id)')->from('label_custody_events')->groupBy('warehouse_receipt_item_label_id');
            });

        if ($driverId) {
            $query->where('driver_id', $driverId);
        }

        return $query->pluck('warehouse_receipt_item_label_id');
    }
}
