<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentCollection;
use App\Services\ShipmentCollectionService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CollectionController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private ShipmentCollectionService $collectionService,
    ) {}

    public function index(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $readyCount = ShipmentCollection::where('warehouse_id', $warehouse->id)
            ->where('status', ShipmentCollection::STATUS_READY)
            ->count();

        $collectedCount = ShipmentCollection::where('warehouse_id', $warehouse->id)
            ->where('status', ShipmentCollection::STATUS_COLLECTED)
            ->count();

        return view('warehouse.collections.index', compact('warehouse', 'readyCount', 'collectedCount'));
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $query = ShipmentCollection::where('warehouse_id', $warehouse->id)
            ->with(['shipment.vendor', 'shipment.items', 'handedOverBy']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            $query->where('status', ShipmentCollection::STATUS_READY);
        }

        if ($search = $request->get('search')) {
            $query->whereHas('shipment', function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%"));
            });
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page    = max((int) $request->get('page', 1), 1);
        $total   = $query->count();
        $items   = $query->latest('ready_at')->skip(($page - 1) * $perPage)->take($perPage)->get();

        return response()->json([
            'data' => $items->map(function (ShipmentCollection $c) {
                $shipment = $c->shipment;
                return [
                    'id'                 => $c->id,
                    'shipment_id'        => $shipment?->id,
                    'shipment_number'    => $shipment?->shipment_number,
                    'vendor_name'        => $shipment?->vendor?->name,
                    'recipient_name'     => $shipment?->delivery_recipient_name ?: $shipment?->items?->first()?->delivery_recipient_name,
                    'recipient_phone'    => $shipment?->delivery_recipient_phone ?: $shipment?->items?->first()?->delivery_recipient_phone,
                    'items_count'        => $shipment?->items?->count() ?? 0,
                    'status'             => $c->status,
                    'ready_at'           => $c->ready_at?->format('d M Y, H:i'),
                    'collected_at'       => $c->collected_at?->format('d M Y, H:i'),
                    'collected_by_name'  => $c->collected_by_name,
                    'handed_over_by'     => $c->handedOverBy?->name,
                ];
            }),
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max((int) ceil($total / $perPage), 1),
                'from'         => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    public function handover(Request $request, Shipment $shipment): JsonResponse
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);

        $collection = $shipment->collection;
        if (!$collection || $collection->warehouse_id !== $warehouse->id) {
            return response()->json(['success' => false, 'message' => 'Collection not found at this warehouse.'], 404);
        }

        if ($collection->isCollected()) {
            return response()->json(['success' => false, 'message' => 'This shipment has already been collected.'], 400);
        }

        $validated = $request->validate([
            'collected_by_name'      => 'required|string|max:255',
            'collected_by_phone'     => 'required|string|max:20',
            'collected_by_id_type'   => 'nullable|string|max:50',
            'collected_by_id_number' => 'nullable|string|max:100',
            'notes'                  => 'nullable|string|max:1000',
        ]);

        $result = $this->collectionService->recordHandover($shipment, $user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Shipment handed over successfully.',
        ]);
    }
}
