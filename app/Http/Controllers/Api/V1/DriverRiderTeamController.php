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

    /** Driver resolved for the authenticated account (cached per request). */
    private ?Driver $resolvedActingDriver = null;

    /**
     * Resolve the Driver profile behind the authenticated account.
     *
     * The rider/transporter app signs in with the back-office User that holds
     * the rider or transporter role, but package custody, rider team membership
     * and transfers are all recorded against a Driver record. Map the account to
     * its Driver so scanning records the right owner instead of failing.
     */
    private function actingDriver(Request $request): Driver
    {
        if ($this->resolvedActingDriver) {
            return $this->resolvedActingDriver;
        }

        $user = $request->user();

        if ($user instanceof Driver) {
            return $this->resolvedActingDriver = $user;
        }

        $phone = trim((string) ($user?->phone ?? ''));
        $email = trim((string) ($user?->email ?? ''));
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        // Ghana numbers are stored inconsistently (+233…, 233…, 0…), so the last
        // nine digits are used as a fallback signature for comparison.
        $tail = strlen($digits) >= 9 ? substr($digits, -9) : '';
        $normalisePhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '')";

        $driver = null;

        if ($phone !== '' || $email !== '') {
            $driver = Driver::query()
                ->where(function ($query) use ($phone, $email, $digits, $tail, $normalisePhone) {
                    if ($phone !== '') {
                        $query->where('phone', $phone);
                    }
                    if ($digits !== '') {
                        $query->orWhereRaw("{$normalisePhone} = ?", [$digits]);
                    }
                    if ($tail !== '') {
                        $query->orWhereRaw("RIGHT({$normalisePhone}, 9) = ?", [$tail]);
                    }
                    if ($email !== '') {
                        $query->orWhere('email', $email);
                    }
                })
                ->orderByDesc('is_active')
                ->first();
        }

        if (! $driver) {
            abort(403, 'No rider profile is linked to this account yet. Please contact your warehouse supervisor.');
        }

        return $this->resolvedActingDriver = $driver;
    }

    public function __construct(private readonly RiderTeamHandoverService $service)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $driver = $this->actingDriver($request);

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
                'teams' => $memberships
                    ->unique('rider_team_id')
                    ->map(fn ($membership) => $this->teamSummary($membership->team, $driver))
                    ->values(),
            ],
        ]);
    }

    public function show(Request $request, RiderTeam $team): JsonResponse
    {
        $driver = $this->actingDriver($request);
        abort_unless($this->service->driverBelongsToTeam($driver, $team), 403);

        $team->load(['warehouse:id,name,code']);

        $data = $this->teamSummary($team, $driver);
        $data['members'] = $team->activeMemberships()
            ->with('driver:id,name,phone,vehicle_type,vehicle_number,is_active')
            ->orderBy('id')
            ->get()
            ->groupBy('driver_id')
            ->sortByDesc(fn ($memberships) => $memberships->max('id'))
            ->map(function ($memberships) {
                $membership = $memberships->firstWhere('role', RiderTeamMembership::ROLE_MEMBER) ?: $memberships->first();

                return [
                    'id' => $membership->id,
                    'role' => $memberships->contains('role', RiderTeamMembership::ROLE_LEADER)
                        ? RiderTeamMembership::ROLE_LEADER
                        : RiderTeamMembership::ROLE_MEMBER,
                    'joined_at' => $membership->joined_at?->toIso8601String(),
                    'driver' => $this->driverSummary($membership->driver),
                ];
            })
            ->values();

        return response()->json(['success' => true, 'data' => ['team' => $data]]);
    }

    public function lookupMember(Request $request, RiderTeam $team): JsonResponse
    {
        $leader = $this->actingDriver($request);
        abort_unless($this->service->driverCanManageTeam($leader, $team), 403);

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
        $leader = $this->actingDriver($request);
        abort_unless($this->service->driverCanManageTeam($leader, $team), 403);

        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer', 'exists:drivers,id'],
            'phone' => ['nullable', 'string', 'max:30'],
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
            RiderTeamMembership::ROLE_MEMBER,
            'driver',
            $leader->id,
        );

        return response()->json([
            'success' => true,
            'message' => 'Rider added to team.',
            'data' => [
                'membership' => [
                    'id' => $membership->id,
                    'role' => RiderTeamMembership::ROLE_MEMBER,
                    'driver' => $this->driverSummary($rider),
                ],
            ],
        ]);
    }

    public function removeMember(Request $request, RiderTeam $team, Driver $driver): JsonResponse
    {
        $leader = $this->actingDriver($request);
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
                'with_receiver' => max((int) $handovers->sum('received_count') - (int) $handovers->sum('distributed_count'), 0),
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
