<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\RiderTeam;
use App\Models\RiderTeamHandover;
use App\Models\RiderTeamHandoverItem;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\RiderTeamHandoverService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverRiderTeamHandoverController extends Controller
{
    public function __construct(private readonly RiderTeamHandoverService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $teamIds = $driver->riderTeamMemberships()
            ->where('is_active', true)
            ->whereNull('removed_at')
            ->pluck('rider_team_id');

        $handovers = RiderTeamHandover::query()
            ->with(['team:id,name', 'receiver:id,name,phone'])
            ->whereIn('rider_team_id', $teamIds)
            ->latest('id')
            ->limit((int) min(max((int) $request->input('limit', 50), 1), 100))
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'handovers' => $handovers->map(fn ($handover) => $this->handoverSummary($handover))->values(),
            ],
        ]);
    }

    public function show(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $driver = $request->user();
        $handover->load(['team', 'receiver:id,name,phone', 'warehouse:id,name,code']);
        abort_unless($this->service->driverBelongsToTeam($driver, $handover->team), 403);

        $isReceiver = (int) ($handover->receiver_driver_id ?: $handover->leader_driver_id) === (int) $driver->id;
        $isTeamLeader = $this->service->driverCanManageTeam($driver, $handover->team);
        $canSeeFullHandover = $isReceiver || $isTeamLeader;

        $itemsQuery = $handover->items()
            ->with([
                'allocatedTo:id,name,phone',
                'label.receiptItem.shipmentItem:id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_landmark,delivery_instructions',
                'label.receiptItem.shipmentItem.images:id,shipment_item_id,path,original_name,size,sort_order,recipient_phone',
                'label.receiptItem.receipt:id,pickup_assignment_id',
                'label.receiptItem.receipt.pickupAssignment:id',
                'label.receiptItem.receipt.pickupAssignment.photos:id,pickup_assignment_id,shipment_item_id,path,original_name,size,type',
                'label.receiptItem.photos:id,warehouse_receipt_item_id,path,original_name,size,photo_type',
            ])
            ->orderBy('id');

        if (! $canSeeFullHandover) {
            $itemsQuery->where('allocated_to_driver_id', $driver->id);
        }

        $items = $itemsQuery->get()->map(fn ($item) => $this->handoverItem($item, $canSeeFullHandover))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'handover' => array_merge($this->handoverSummary($handover), [
                    'can_manage' => $isTeamLeader,
                    'can_receive' => $isReceiver,
                    'can_distribute' => $isReceiver || $isTeamLeader,
                    'items' => $items,
                ]),
            ],
        ]);
    }

    public function scanReceive(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $receiver = $request->user();
        $validated = $request->validate(['barcode' => ['required', 'string', 'max:100']]);

        $item = $this->service->receiveByReceiver($handover->loadMissing('team'), $receiver, $validated['barcode']);

        return response()->json([
            'success' => true,
            'message' => 'Package received into rider team handover.',
            'data' => ['item' => $this->handoverItem($item, true)],
        ]);
    }

    public function scanReceiveForTeam(Request $request, RiderTeam $team): JsonResponse
    {
        $receiver = $request->user();
        $validated = $request->validate([
            'barcode' => ['required', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->service->receiveTeamCustody(
            $receiver,
            $team->loadMissing('warehouse'),
            $validated['barcode'],
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null,
        );

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Package received into rider team custody.',
            'action' => $result['status'] ?? 'team_received',
            'data' => [
                'handover' => $this->handoverSummary($result['handover']->loadMissing(['team', 'receiver'])),
                'item' => $this->handoverItem($result['item'], true),
            ],
        ]);
    }

    public function allocate(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $receiver = $request->user();
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'barcodes' => ['required', 'array', 'min:1'],
            'barcodes.*' => ['required', 'string', 'max:100'],
        ]);

        $member = Driver::findOrFail($validated['driver_id']);
        $result = $this->service->allocateLabels($handover->loadMissing('team'), $receiver, $member, $validated['barcodes']);

        return response()->json([
            'success' => true,
            'message' => $result['allocated'] . ' package(s) allocated.',
            'data' => $result,
        ]);
    }

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

        if (! $label) {
            return response()->json(['success' => false, 'message' => 'Label not found.'], 404);
        }

        $result = $this->service->claimFromScan(
            $driver,
            $label,
            $validated['latitude'] ?? null,
            $validated['longitude'] ?? null,
            $validated['notes'] ?? null,
        );

        if (($result['status'] ?? null) === 'conflict') {
            return response()->json(['success' => false, 'message' => $result['message']], 409);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'] ?? 'Package claimed.',
            'action' => $result['status'] ?? 'claimed',
        ]);
    }

    private function handoverSummary(RiderTeamHandover $handover): array
    {
        return [
            'id' => $handover->id,
            'handover_number' => $handover->handover_number,
            'status' => $handover->status,
            'team' => $handover->team ? [
                'id' => $handover->team->id,
                'name' => $handover->team->name,
            ] : null,
            'receiver' => $handover->receiver ? [
                'id' => $handover->receiver->id,
                'name' => $handover->receiver->name,
                'phone' => $handover->receiver->phone,
            ] : null,
            'counts' => [
                'assigned' => $handover->assigned_count,
                'received' => $handover->received_count,
                'distributed' => $handover->distributed_count,
                'claimed' => $handover->claimed_count,
                'delivered' => $handover->delivered_count,
                'failed' => $handover->failed_count,
                'with_receiver' => max($handover->received_count - $handover->distributed_count, 0),
            ],
            'created_at' => $handover->created_at?->toIso8601String(),
            'assigned_at' => $handover->assigned_at?->toIso8601String(),
            'received_at' => $handover->received_at?->toIso8601String(),
        ];
    }

    private function handoverItem(RiderTeamHandoverItem $item, bool $includePackageDetails): array
    {
        $item->loadMissing([
            'allocatedTo:id,name,phone',
            'label.receiptItem.shipmentItem:id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town,delivery_landmark,delivery_instructions',
            'label.receiptItem.shipmentItem.images:id,shipment_item_id,path,original_name,size,sort_order,recipient_phone',
            'label.receiptItem.receipt:id,pickup_assignment_id',
            'label.receiptItem.receipt.pickupAssignment:id',
            'label.receiptItem.receipt.pickupAssignment.photos:id,pickup_assignment_id,shipment_item_id,path,original_name,size,type',
            'label.receiptItem.photos:id,warehouse_receipt_item_id,path,original_name,size,photo_type',
        ]);

        $label = $item->label;
        $receiptItem = $label?->receiptItem;
        $shipmentItem = $receiptItem?->shipmentItem;

        return [
            'id' => $item->id,
            'barcode' => $label?->barcode_value,
            'status' => $item->status,
            'allocated_to' => $item->allocatedTo ? [
                'id' => $item->allocatedTo->id,
                'name' => $item->allocatedTo->name,
                'phone' => $item->allocatedTo->phone,
            ] : null,
            'assigned_at' => $item->assigned_at?->toIso8601String(),
            'receiver_received_at' => $item->leader_received_at?->toIso8601String(),
            'allocated_at' => $item->allocated_at?->toIso8601String(),
            'member_claimed_at' => $item->member_claimed_at?->toIso8601String(),
            'package' => $includePackageDetails ? [
                'tracking_code' => $shipmentItem?->tracking_code,
                'description' => $shipmentItem?->description,
                'recipient_name' => $shipmentItem?->delivery_recipient_name,
                'recipient_phone' => $shipmentItem?->delivery_recipient_phone,
                'delivery_town' => $shipmentItem?->delivery_town,
                'delivery_landmark' => $shipmentItem?->delivery_landmark,
                'delivery_instructions' => $shipmentItem?->delivery_instructions,
                'images' => $this->packagePhotos($receiptItem, $shipmentItem),
            ] : null,
        ];
    }

    private function packagePhotos($receiptItem, $shipmentItem): array
    {
        $vendorPhotos = $shipmentItem?->images
            ?->map(fn ($photo) => $photo->getSignedUrl() + ['source' => 'Vendor'])
            ->values() ?? collect();

        $driverPhotos = $receiptItem?->receipt?->pickupAssignment?->photos
            ?->filter(fn ($photo) => ! $photo->shipment_item_id || (int) $photo->shipment_item_id === (int) $shipmentItem?->id)
            ->map(fn ($photo) => $this->formatPhoto($photo, 'Driver'))
            ->values() ?? collect();

        $receiptPhotos = $receiptItem?->photos
            ?->map(fn ($photo) => $this->formatPhoto($photo, 'Receipt'))
            ->values() ?? collect();

        return ($vendorPhotos->isNotEmpty()
            ? $vendorPhotos
            : ($driverPhotos->isNotEmpty() ? $driverPhotos : $receiptPhotos))
            ->values()
            ->all();
    }

    private function formatPhoto($photo, string $source): array
    {
        $storage = app(StorageService::class);
        $url = $storage->getUrl($photo->path);

        return [
            'id' => $photo->id,
            'url' => $url,
            'original_name' => $photo->original_name,
            'source' => $source,
            'size' => $photo->size,
        ];
    }
}
