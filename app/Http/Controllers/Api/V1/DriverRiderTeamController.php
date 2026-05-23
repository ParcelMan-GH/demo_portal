<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\RiderTeam;
use App\Models\RiderTeamMembership;
use App\Services\RiderTeamHandoverService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverRiderTeamController extends Controller
{
    public function __construct(private readonly RiderTeamHandoverService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $driver = $request->user();

        $memberships = RiderTeamMembership::query()
            ->with(['team.warehouse:id,name,code'])
            ->where('driver_id', $driver->id)
            ->where('is_active', true)
            ->whereNull('removed_at')
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'teams' => $memberships->map(fn ($membership) => $this->teamSummary($membership->team, $driver))->values(),
            ],
        ]);
    }

    public function show(Request $request, RiderTeam $team): JsonResponse
    {
        $driver = $request->user();
        abort_unless($this->service->driverBelongsToTeam($driver, $team), 403);

        $team->load(['warehouse:id,name,code']);
        $isLeader = $this->service->driverCanManageTeam($driver, $team);

        $data = $this->teamSummary($team, $driver);
        if ($isLeader) {
            $data['members'] = $team->activeMemberships()
                ->with('driver:id,name,phone,vehicle_type,vehicle_number,is_active')
                ->orderByRaw("role = 'leader' desc")
                ->orderBy('id')
                ->get()
                ->map(fn ($membership) => [
                    'id' => $membership->id,
                    'role' => $membership->role,
                    'joined_at' => $membership->joined_at?->toIso8601String(),
                    'driver' => $this->driverSummary($membership->driver),
                ])
                ->values();
        }

        return response()->json(['success' => true, 'data' => ['team' => $data]]);
    }

    public function lookupMember(Request $request, RiderTeam $team): JsonResponse
    {
        $driver = $request->user();
        abort_unless($this->service->driverCanManageTeam($driver, $team), 403);

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $rider = $this->service->lookupRider($validated['phone']);
        if (! $rider) {
            return response()->json([
                'success' => false,
                'message' => 'Rider not found. They must be registered before they can join this team.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rider' => $this->driverSummary($rider),
                'already_member' => $this->service->driverBelongsToTeam($rider, $team),
            ],
        ]);
    }

    public function addMember(Request $request, RiderTeam $team): JsonResponse
    {
        $leader = $request->user();
        abort_unless($this->service->driverCanManageTeam($leader, $team), 403);

        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['nullable', 'in:leader,member'],
        ]);

        $rider = ! empty($validated['driver_id'])
            ? Driver::query()->where('is_active', true)->find($validated['driver_id'])
            : $this->service->lookupRider((string) ($validated['phone'] ?? ''));

        if (! $rider) {
            return response()->json(['success' => false, 'message' => 'Rider not found.'], 404);
        }

        $membership = $this->service->addMembership(
            $team,
            $rider,
            $validated['role'] ?? RiderTeamMembership::ROLE_MEMBER,
            'driver',
            $leader->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Rider added to team.',
            'data' => [
                'membership' => [
                    'id' => $membership->id,
                    'role' => $membership->role,
                    'driver' => $this->driverSummary($rider),
                ],
            ],
        ]);
    }

    public function removeMember(Request $request, RiderTeam $team, Driver $driver): JsonResponse
    {
        $leader = $request->user();
        abort_unless($this->service->driverCanManageTeam($leader, $team), 403);

        if ((int) $leader->id === (int) $driver->id) {
            return response()->json(['success' => false, 'message' => 'You cannot remove yourself from a team you lead.'], 422);
        }

        $this->service->removeMembership($team, $driver);

        return response()->json(['success' => true, 'message' => 'Rider removed from team.']);
    }

    private function teamSummary(RiderTeam $team, Driver $driver): array
    {
        $team->loadMissing('warehouse:id,name,code');
        $isLeader = $this->service->driverCanManageTeam($driver, $team);

        $handovers = $team->handovers()
            ->whereNotIn('status', ['closed', 'recalled'])
            ->get(['assigned_count', 'received_count', 'distributed_count', 'claimed_count', 'delivered_count']);

        return [
            'id' => $team->id,
            'name' => $team->name,
            'zone' => $team->zone,
            'is_active' => $team->is_active,
            'role' => $isLeader ? RiderTeamMembership::ROLE_LEADER : RiderTeamMembership::ROLE_MEMBER,
            'warehouse' => $team->warehouse ? [
                'id' => $team->warehouse->id,
                'name' => $team->warehouse->name,
                'code' => $team->warehouse->code,
            ] : null,
            'totals' => [
                'assigned' => (int) $handovers->sum('assigned_count'),
                'received' => (int) $handovers->sum('received_count'),
                'distributed' => (int) $handovers->sum('distributed_count'),
                'claimed' => (int) $handovers->sum('claimed_count'),
                'delivered' => (int) $handovers->sum('delivered_count'),
                'still_with_leader' => max((int) $handovers->sum('received_count') - (int) $handovers->sum('distributed_count'), 0),
            ],
        ];
    }

    private function driverSummary(?Driver $driver): ?array
    {
        if (! $driver) {
            return null;
        }

        return [
            'id' => $driver->id,
            'name' => $driver->name,
            'phone' => $driver->phone,
            'vehicle_type' => $driver->vehicle_type,
            'vehicle_number' => $driver->vehicle_number,
            'is_active' => $driver->is_active,
        ];
    }
}
