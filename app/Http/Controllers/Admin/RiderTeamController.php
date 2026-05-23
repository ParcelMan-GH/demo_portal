<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\RiderTeam;
use App\Models\RiderTeamHandover;
use App\Models\RiderTeamHandoverItem;
use App\Models\RiderTeamMembership;
use App\Services\BackOfficeAccess;
use App\Services\RiderTeamHandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RiderTeamController extends Controller
{
    public function __construct(private readonly RiderTeamHandoverService $service)
    {
    }

    public function index(): View
    {
        $this->authorizeRiderTeamView();
        $user = Auth::guard('admin')->user();
        $warehouses = app(BackOfficeAccess::class)->warehousesFor($user, 'warehouse');

        return view('admin.rider-teams.index', [
            'warehouses' => $warehouses->values(),
            'drivers' => Driver::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function data(): JsonResponse
    {
        $this->authorizeRiderTeamView();
        $warehouseIds = $this->scopedWarehouseIds();

        $teams = RiderTeam::query()
            ->with(['warehouse:id,name,code'])
            ->whereIn('warehouse_id', $warehouseIds)
            ->withCount([
                'activeMemberships as members_count',
                'leaders as leaders_count',
                'handovers as handovers_count',
            ])
            ->latest('id')
            ->get()
            ->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'is_active' => $team->is_active,
                'warehouse' => $team->warehouse ? [
                    'id' => $team->warehouse->id,
                    'name' => $team->warehouse->name,
                    'code' => $team->warehouse->code,
                ] : null,
                'members_count' => $team->members_count,
                'leaders_count' => $team->leaders_count,
                'handovers_count' => $team->handovers_count,
            ]);

        return response()->json(['success' => true, 'data' => ['teams' => $teams]]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        app(BackOfficeAccess::class)->assertCanUseWarehouse($user, (int) $validated['warehouse_id'], 'warehouse');

        $team = RiderTeam::create([
            'name' => $validated['name'],
            'warehouse_id' => $validated['warehouse_id'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json(['success' => true, 'message' => 'Rider team created.', 'data' => ['team' => $team]]);
    }

    public function update(Request $request, RiderTeam $team): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $this->assertTeamAccess($team);
        $user = Auth::guard('admin')->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        app(BackOfficeAccess::class)->assertCanUseWarehouse($user, (int) $validated['warehouse_id'], 'warehouse');

        $team->update([
            'name' => $validated['name'],
            'warehouse_id' => $validated['warehouse_id'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        return response()->json(['success' => true, 'message' => 'Rider team updated.', 'data' => ['team' => $team->fresh()]]);
    }

    public function members(RiderTeam $team): JsonResponse
    {
        $this->authorizeRiderTeamView();
        $this->assertTeamAccess($team);

        $members = $team->activeMemberships()
            ->with('driver:id,name,phone,vehicle_type,vehicle_number,is_active')
            ->orderByRaw("role = 'leader' desc")
            ->orderBy('id')
            ->get()
            ->groupBy('driver_id')
            ->map(function ($memberships) {
                $leader = $memberships->firstWhere('role', RiderTeamMembership::ROLE_LEADER);
                $membership = $leader ?: $memberships->first();

                return [
                    'id' => $membership->id,
                    'role' => $leader ? RiderTeamMembership::ROLE_LEADER : RiderTeamMembership::ROLE_MEMBER,
                    'is_leader' => (bool) $leader,
                    'joined_at' => $membership->joined_at?->format('M d, Y h:i A'),
                    'driver' => [
                        'id' => $membership->driver?->id,
                        'name' => $membership->driver?->name,
                        'phone' => $membership->driver?->phone,
                        'vehicle' => trim(($membership->driver?->vehicle_type ?? '') . ' ' . ($membership->driver?->vehicle_number ?? '')),
                        'is_active' => $membership->driver?->is_active,
                    ],
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => ['members' => $members]]);
    }

    public function lookupRider(Request $request, RiderTeam $team): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $this->assertTeamAccess($team);

        $validated = $request->validate(['phone' => ['required', 'string', 'max:30']]);
        $driver = $this->service->lookupRider($validated['phone']);

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Rider not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rider' => [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                    'vehicle_type' => $driver->vehicle_type,
                    'vehicle_number' => $driver->vehicle_number,
                ],
                'already_member' => $this->service->driverBelongsToTeam($driver, $team),
            ],
        ]);
    }

    public function addMember(Request $request, RiderTeam $team): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $this->assertTeamAccess($team);

        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'in:leader,member'],
        ]);

        $driver = ! empty($validated['driver_id'])
            ? Driver::where('is_active', true)->find($validated['driver_id'])
            : $this->service->lookupRider((string) ($validated['phone'] ?? ''));

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Rider not found.'], 404);
        }

        $membership = $this->service->addMembership($team, $driver, $validated['role'] ?? RiderTeamMembership::ROLE_MEMBER, 'user', Auth::guard('admin')->id());

        return response()->json(['success' => true, 'message' => 'Rider added to team.', 'data' => ['membership' => $membership]]);
    }

    public function removeMember(RiderTeam $team, Driver $driver): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $this->assertTeamAccess($team);

        $role = request('role');
        if (! in_array($role, [RiderTeamMembership::ROLE_LEADER, RiderTeamMembership::ROLE_MEMBER], true)) {
            $role = null;
        }

        $this->service->removeMembership($team, $driver, $role);

        return response()->json(['success' => true, 'message' => 'Rider removed from team.']);
    }

    public function handoversData(Request $request): JsonResponse
    {
        $this->authorizeRiderTeamView();
        $warehouseIds = $this->scopedWarehouseIds();

        $handovers = RiderTeamHandover::query()
            ->with(['team:id,name', 'leader:id,name,phone', 'warehouse:id,name,code'])
            ->whereIn('warehouse_id', $warehouseIds)
            ->when($request->filled('team_id'), fn ($query) => $query->where('rider_team_id', $request->integer('team_id')))
            ->latest('id')
            ->limit(100)
            ->get()
            ->map(fn ($handover) => $this->serializeHandover($handover));

        return response()->json(['success' => true, 'data' => ['handovers' => $handovers]]);
    }

    public function storeHandover(Request $request): JsonResponse
    {
        $this->authorizeRiderTeamManage();

        $validated = $request->validate([
            'rider_team_id' => ['required', 'exists:rider_teams,id'],
            'leader_driver_id' => ['required', 'exists:drivers,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'barcodes' => ['nullable', 'array'],
            'barcodes.*' => ['string', 'max:100'],
            'barcode_text' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $team = RiderTeam::findOrFail($validated['rider_team_id']);
        $this->assertTeamAccess($team);
        $leader = Driver::findOrFail($validated['leader_driver_id']);
        $warehouse = $team->warehouse;

        $barcodes = collect($validated['barcodes'] ?? [])
            ->merge(preg_split('/[\s,]+/', (string) ($validated['barcode_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($barcode) => trim((string) $barcode))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $handover = DB::transaction(function () use ($team, $leader, $warehouse, $validated, $barcodes) {
            $handover = $this->service->createHandover($team, $leader, $warehouse, Auth::guard('admin')->user(), null, $validated['notes'] ?? null);
            if ($barcodes) {
                $this->service->assignLabels($handover, $barcodes, $validated['notes'] ?? null);
            }

            return $handover->fresh(['team', 'leader', 'warehouse']);
        });

        return response()->json(['success' => true, 'message' => 'Rider team handover created.', 'data' => ['handover' => $this->serializeHandover($handover)]]);
    }

    public function showHandover(RiderTeamHandover $handover): JsonResponse
    {
        $this->authorizeRiderTeamView();
        $this->assertHandoverAccess($handover);

        $handover->load([
            'team:id,name',
            'leader:id,name,phone',
            'warehouse:id,name,code',
            'items.allocatedTo:id,name,phone',
            'items.label.receiptItem.shipmentItem:id,description,tracking_code,delivery_recipient_name,delivery_recipient_phone,delivery_town',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'handover' => array_merge($this->serializeHandover($handover), [
                    'items' => $handover->items->map(fn ($item) => $this->serializeHandoverItem($item))->values(),
                ]),
            ],
        ]);
    }

    public function assignLabels(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $this->assertHandoverAccess($handover);

        $validated = $request->validate([
            'barcodes' => ['nullable', 'array'],
            'barcodes.*' => ['string', 'max:100'],
            'barcode_text' => ['nullable', 'string', 'max:20000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $barcodes = collect($validated['barcodes'] ?? [])
            ->merge(preg_split('/[\s,]+/', (string) ($validated['barcode_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $result = $this->service->assignLabels($handover, $barcodes, $validated['notes'] ?? null);

        return response()->json(['success' => true, 'message' => $result['assigned'] . ' label(s) assigned.', 'data' => $result]);
    }

    public function recallLabels(Request $request, RiderTeamHandover $handover): JsonResponse
    {
        $this->authorizeRiderTeamManage();
        $this->assertHandoverAccess($handover);

        $validated = $request->validate([
            'barcodes' => ['required', 'array', 'min:1'],
            'barcodes.*' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($handover, $validated) {
            $items = $handover->items()
                ->whereHas('label', fn ($query) => $query->whereIn('barcode_value', $validated['barcodes']))
                ->whereNotIn('status', [
                    RiderTeamHandoverItem::STATUS_IN_DELIVERY,
                    RiderTeamHandoverItem::STATUS_DELIVERED,
                ])
                ->with('label')
                ->get();

            foreach ($items as $item) {
                $item->update(['status' => RiderTeamHandoverItem::STATUS_RECALLED, 'returned_at' => now(), 'notes' => $validated['notes'] ?? null]);
                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $item->warehouse_receipt_item_label_id,
                    'event_type' => LabelCustodyEvent::TYPE_RECALLED,
                    'scanned_by_user_id' => Auth::guard('admin')->id(),
                    'notes' => $validated['notes'] ?? 'Recalled by admin.',
                ]);
            }

            $this->service->refreshHandoverCounts($handover);
        });

        return response()->json(['success' => true, 'message' => 'Selected labels recalled.']);
    }

    public function printHandover(RiderTeamHandover $handover): View
    {
        $this->authorizeRiderTeamView();
        $this->assertHandoverAccess($handover);

        $handover->load([
            'team:id,name',
            'leader:id,name,phone',
            'warehouse:id,name,code',
            'items.label',
        ]);

        return view('admin.rider-teams.print-handover', compact('handover'));
    }

    protected function authorizePermission(string $permission): void
    {
        if (! Auth::guard('admin')->user()?->hasPermission($permission)) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function authorizeRiderTeamView(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless(
            $user && (
                $this->canUsePermission($user, 'drivers.view')
                || $this->canUsePermission($user, 'warehouse.delivery.assign')
                || $this->canUsePermission($user, 'warehouse.manifest.manage')
            ),
            403,
            'Unauthorized action.'
        );
    }

    private function authorizeRiderTeamManage(): void
    {
        $user = Auth::guard('admin')->user();

        abort_unless(
            $user && (
                $this->canUsePermission($user, 'drivers.edit')
                || $this->canUsePermission($user, 'warehouse.delivery.assign')
                || $this->canUsePermission($user, 'warehouse.manifest.manage')
            ),
            403,
            'Unauthorized action.'
        );
    }

    private function canUsePermission($user, string $permission): bool
    {
        return app(BackOfficeAccess::class)->canUsePermission($user, $permission);
    }

    /**
     * @return array<int>
     */
    private function scopedWarehouseIds(): array
    {
        return app(BackOfficeAccess::class)->scopedWarehouseIdsFor(Auth::guard('admin')->user(), 'warehouse');
    }

    private function assertTeamAccess(RiderTeam $team): void
    {
        abort_unless(in_array((int) $team->warehouse_id, $this->scopedWarehouseIds(), true), 403);
    }

    private function assertHandoverAccess(RiderTeamHandover $handover): void
    {
        abort_unless(in_array((int) $handover->warehouse_id, $this->scopedWarehouseIds(), true), 403);
    }

    private function serializeHandover(RiderTeamHandover $handover): array
    {
        return [
            'id' => $handover->id,
            'handover_number' => $handover->handover_number,
            'status' => $handover->status,
            'team' => $handover->team ? ['id' => $handover->team->id, 'name' => $handover->team->name] : null,
            'leader' => $handover->leader ? ['id' => $handover->leader->id, 'name' => $handover->leader->name, 'phone' => $handover->leader->phone] : null,
            'warehouse' => $handover->warehouse ? ['id' => $handover->warehouse->id, 'name' => $handover->warehouse->name, 'code' => $handover->warehouse->code] : null,
            'counts' => [
                'assigned' => $handover->assigned_count,
                'received' => $handover->received_count,
                'distributed' => $handover->distributed_count,
                'claimed' => $handover->claimed_count,
                'delivered' => $handover->delivered_count,
                'failed' => $handover->failed_count,
                'still_with_leader' => max($handover->received_count - $handover->distributed_count, 0),
            ],
            'created_at' => $handover->created_at?->format('M d, Y h:i A'),
            'print_url' => route('admin.rider-teams.handovers.print', $handover),
        ];
    }

    private function serializeHandoverItem(RiderTeamHandoverItem $item): array
    {
        $shipmentItem = $item->label?->receiptItem?->shipmentItem;

        return [
            'id' => $item->id,
            'barcode' => $item->label?->barcode_value,
            'status' => $item->status,
            'allocated_to' => $item->allocatedTo ? ['id' => $item->allocatedTo->id, 'name' => $item->allocatedTo->name, 'phone' => $item->allocatedTo->phone] : null,
            'package' => [
                'tracking_code' => $shipmentItem?->tracking_code,
                'description' => $shipmentItem?->description,
                'recipient_name' => $shipmentItem?->delivery_recipient_name,
                'delivery_town' => $shipmentItem?->delivery_town,
            ],
        ];
    }
}
