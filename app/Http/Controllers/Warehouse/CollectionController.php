<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\FulfillmentType;
use App\Http\Controllers\Controller;
use App\Models\Shipment;
use App\Models\ShipmentCollection;
use App\Models\ShipmentItem;
use App\Services\ShipmentCollectionService;
use App\Services\StorageService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
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

        $baseQuery = ShipmentCollection::where('warehouse_id', $warehouse->id);

        $query = (clone $baseQuery)
            ->with([
                'shipment.vendor',
                'shipment.items.images',
                'shipment.items.warehouseReceiptItems.photos',
                'shipment.pickupAssignment.photos',
                'handedOverBy',
            ]);

        $status = $request->get('status', ShipmentCollection::STATUS_READY);
        if (in_array($status, [ShipmentCollection::STATUS_READY, ShipmentCollection::STATUS_COLLECTED], true)) {
            $query->where('status', $status);
        } else {
            $query->where('status', ShipmentCollection::STATUS_READY);
            $status = ShipmentCollection::STATUS_READY;
        }

        if ($search = $request->get('search')) {
            $query->whereHas('shipment', function ($q) use ($search) {
                $q->where('shipment_number', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                  ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                  ->orWhereHas('vendor', fn ($vq) => $vq->where('name', 'like', "%{$search}%"));
                });
        }

        $dateColumn = $status === ShipmentCollection::STATUS_COLLECTED ? 'collected_at' : 'ready_at';
        if ($from = $request->get('date_from')) {
            $query->where($dateColumn, '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to = $request->get('date_to')) {
            $query->where($dateColumn, '<=', Carbon::parse($to)->endOfDay());
        }

        if ($min = $request->get('items_min')) {
            $query->whereHas('shipment', fn ($q) => $q->has('items', '>=', (int) $min));
        }
        if ($max = $request->get('items_max')) {
            $query->whereHas('shipment', fn ($q) => $q->has('items', '<=', (int) $max));
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $page    = max((int) $request->get('page', 1), 1);
        $total   = $query->count();
        $items   = $query->latest($dateColumn)->skip(($page - 1) * $perPage)->take($perPage)->get();

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
                    'ready_at'           => $c->ready_at?->format('d M Y, h:i A'),
                    'collected_at'       => $c->collected_at?->format('d M Y, h:i A'),
                    'collected_by_name'  => $c->collected_by_name,
                    'collected_by_phone' => $c->collected_by_phone,
                    'handed_over_by'     => $c->handedOverBy?->name,
                    'packages'           => $shipment?->items?->map(fn (ShipmentItem $item) => $this->packagePayload($item))?->values() ?? collect(),
                ];
            }),
            'meta' => [
                'total'        => $total,
                'ready_count'  => (clone $baseQuery)->where('status', ShipmentCollection::STATUS_READY)->count(),
                'collected_count' => (clone $baseQuery)->where('status', ShipmentCollection::STATUS_COLLECTED)->count(),
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => max((int) ceil($total / $perPage), 1),
                'from'         => $total ? (($page - 1) * $perPage) + 1 : 0,
                'to'           => min($page * $perPage, $total),
            ],
        ]);
    }

    private function packagePayload(ShipmentItem $item): array
    {
        $vendorPhotos = $item->images
            ?->map(fn ($image) => $image->getSignedUrl() + ['source' => 'Vendor'])
            ->values() ?? collect();

        $pickupPhotos = $item->shipment?->pickupAssignment?->photos
            ?->filter(fn ($photo) => !$photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $item->id)
            ->map(fn ($photo) => $this->photoPayload($photo, 'Pickup photo', 'Pickup'))
            ->values() ?? collect();

        $receiptPhotos = $item->warehouseReceiptItems
            ?->flatMap(fn ($receiptItem) => $receiptItem->photos ?? collect())
            ->map(fn ($photo) => $this->photoPayload($photo, 'Receipt photo', 'Receipt'))
            ->values() ?? collect();

        $primaryPhotos = $vendorPhotos->isNotEmpty()
            ? $vendorPhotos
            : ($pickupPhotos->isNotEmpty() ? $pickupPhotos : $receiptPhotos);

        return [
            'id' => $item->id,
            'description' => $item->description,
            'tracking_code' => $item->tracking_code,
            'quantity' => (int) $item->quantity,
            'photos' => [
                'primary' => $primaryPhotos->values(),
                'primary_source' => $vendorPhotos->isNotEmpty() ? 'Vendor' : ($pickupPhotos->isNotEmpty() ? 'Pickup' : ($receiptPhotos->isNotEmpty() ? 'Receipt' : 'No photos')),
                'vendor' => $vendorPhotos,
                'pickup' => $pickupPhotos,
                'receipt' => $receiptPhotos,
                'total' => $vendorPhotos->count() + $pickupPhotos->count() + $receiptPhotos->count(),
            ],
        ];
    }

    private function photoPayload($photo, string $fallbackName, string $source): array
    {
        return [
            'id' => $photo->id,
            'url' => app(StorageService::class)->getUrl($photo->path),
            'original_name' => $photo->original_name ?: $fallbackName,
            'source' => $source,
        ];
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
