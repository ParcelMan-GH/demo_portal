<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\RiderTeamHandover;
use App\Models\RiderTeamHandoverItem;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\RiderTeamHandoverService;
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
            ->with(['team:id,name,zone', 'leader:id,name,phone'])
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
        $handover->load(['team', 'leader:id,name,phone', 'warehouse:id,name,code']);
        abort_unless($this->service->driverBelongsToTeam($driver, $handover->team), 403);

        $isLeader = (int) $handover->leader_driver_id === (int) $driver->id
            && $this->service->driverCanManageTeam($driver, $handover->team);

        $itemsQuery = $handover->items()
            ->with([
                'allocatedTo:id,name,phone',
                'label.receiptItem.shipmentItem:id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            ])
            ->orderBy('id');

        if (! $isLeader) {
            $itemsQuery->where('allocated_to_driver_id', $driver->id);
        }

        $items = $itemsQuery->get()->map(fn ($item) => $this->handoverItem($item, $isLeader))->values();

        return response()->json([
            'success' => true,
            'data' => [
                'handover' => array_merge($this->handoverSummary($handover), [
                    'can_manage' => $isLeader,
                    'items' => $items,
                ]),
            ],
        ]);
    }

    public function scanReceive(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $leader = $request->user();
        $validated = $request->validate(['barcode' => ['required', 'string', 'max:100']]);

        $item = $this->service->receiveByLeader($handover->loadMissing('team'), $leader, $validated['barcode']);

        return response()->json([
            'success' => true,
            'message' => 'Package received into rider team handover.',
            'data' => ['item' => $this->handoverItem($item, true)],
        ]);
    }

    public function allocate(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $leader = $request->user();
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:drivers,id'],
            'barcodes' => ['required', 'array', 'min:1'],
            'barcodes.*' => ['required', 'string', 'max:100'],
        ]);

        $member = Driver::findOrFail($validated['driver_id']);
        $result = $this->service->allocateLabels($handover->loadMissing('team'), $leader, $member, $validated['barcodes']);

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
                'zone' => $handover->team->zone,
            ] : null,
            'leader' => $handover->leader ? [
                'id' => $handover->leader->id,
                'name' => $handover->leader->name,
                'phone' => $handover->leader->phone,
            ] : null,
            'counts' => [
                'assigned' => $handover->assigned_count,
                'received' => $handover->received_count,
                'distributed' => $handover->distributed_count,
                'claimed' => $handover->claimed_count,
                'delivered' => $handover->delivered_count,
                'failed' => $handover->failed_count,
                'still_with_leader' => max($handover->received_count - $handover->distributed_count, 0),
            ],
            'created_at' => $handover->created_at?->toIso8601String(),
            'assigned_at' => $handover->assigned_at?->toIso8601String(),
            'received_at' => $handover->received_at?->toIso8601String(),
        ];
    }

    private function handoverItem(RiderTeamHandoverItem $item, bool $includePackageDetails): array
    {
        $label = $item->label;
        $shipmentItem = $label?->receiptItem?->shipmentItem;

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
            'leader_received_at' => $item->leader_received_at?->toIso8601String(),
            'allocated_at' => $item->allocated_at?->toIso8601String(),
            'member_claimed_at' => $item->member_claimed_at?->toIso8601String(),
            'package' => $includePackageDetails ? [
                'tracking_code' => $shipmentItem?->tracking_code,
                'description' => $shipmentItem?->description,
                'recipient_name' => $shipmentItem?->delivery_recipient_name,
                'recipient_phone' => $shipmentItem?->delivery_recipient_phone,
                'delivery_town' => $shipmentItem?->delivery_town,
            ] : null,
        ];
    }
}
