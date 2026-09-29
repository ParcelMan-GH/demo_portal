<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\DriverLocation;
use App\Models\Shipment;
use App\Models\TransportManifest;
use App\Models\Vendor;
use App\Services\Admin\ActivityFeedService;
use App\Services\BackOfficeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(BackOfficeAccess $access, ActivityFeedService $activityFeed): View|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        $monthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        $calcChange = function ($current, $previous) {
            if ($previous == 0) return $current > 0 ? 100 : 0;
            return round((($current - $previous) / $previous) * 100, 2);
        };

        // ── 1. Total Deliveries (Shipments) ───────────────────────────────
        $totalShipmentsMonth = Shipment::where('created_at', '>=', $monthStart)->count();
        $lastMonthShipments = Shipment::whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $shipmentsChange = $calcChange($totalShipmentsMonth, $lastMonthShipments);

        // ── 2. Active Deliveries ──────────────────────────────────────────
        $outForDelivery = DeliveryRun::where('status', 'out_for_delivery')->count();
        $yesterdayActiveDeliveries = DeliveryRun::whereDate('created_at', $yesterday)->count(); 
        $activeDeliveriesChange = $calcChange($outForDelivery, $yesterdayActiveDeliveries);

        // ── 3. Total Vendors ──────────────────────────────────────────────
        $totalVendors = Vendor::count();
        $lastMonthVendors = Vendor::where('created_at', '<=', $lastMonthEnd)->count();
        $vendorsChange = $calcChange($totalVendors, $lastMonthVendors);

        // ── 4. On Time Delivery (SLA Calculation) ─────────────────────────
        $deliveredThisMonth = Shipment::where('status', 'delivered')->where('updated_at', '>=', $monthStart)->count();
        
        $onTimeThisMonth = Shipment::where('status', 'delivered')
            ->where('updated_at', '>=', $monthStart)
            ->where(function ($query) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $query->whereRaw('(julianday(updated_at) - julianday(created_at)) <= 2');
                } else {
                    $query->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 48');
                }
            })
            ->count();
        
        $onTimeDeliveryRate = $deliveredThisMonth > 0 ? round(($onTimeThisMonth / $deliveredThisMonth) * 100, 1) : 100;

        /*
         * Was a literal -10.6.
         *
         * It printed the same "▼ -10.6%" every day regardless of what the
         * operation did, which is the one number on the dashboard an admin would
         * reasonably make a decision on. It is the change against last month's
         * rate now, and null — rendered as "—" — when last month delivered
         * nothing, because there is no honest comparison to make.
         */
        $lastMonthDelivered = Shipment::where('status', 'delivered')
            ->whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $lastMonthOnTime = $lastMonthDelivered === 0 ? null : Shipment::where('status', 'delivered')
            ->whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])
            ->where(function ($query) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $query->whereRaw('(julianday(updated_at) - julianday(created_at)) <= 2');
                } else {
                    $query->whereRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) <= 48');
                }
            })
            ->count();

        $onTimeChange = $lastMonthOnTime === null
            ? null
            : round($onTimeDeliveryRate - round(($lastMonthOnTime / $lastMonthDelivered) * 100, 1), 1);

        // ── Recent Activity Feed ──────────────────────────────────────────
        $activityFeedItems = $activityFeed->recent(12);

        // ── Active Riders (REAL POSITION MAPPING) ─────────────────────────
        // Positions come from real position reports (driver_locations), falling
        // back to delivery_run_stops where a run has no live fix. No fabricated
        // data — a rider with no position is listed as such rather than omitted,
        // because "rider is out but we have no fix" is information too.
        $activeRiders = $this->buildActiveRiders();

        return view('admin.dashboard.index', compact(
            'admin', 'totalShipmentsMonth', 'shipmentsChange', 'outForDelivery',
            'activeDeliveriesChange', 'totalVendors', 'vendorsChange', 'onTimeDeliveryRate',
            'onTimeChange', 'activityFeedItems', 'activeRiders'
        ));
    }

    /**
     * JSON feed of active riders with their real positions, used by the
     * dashboard map's polling loop (see resources/views/admin/dashboard/index.blade.php).
     */
    public function liveRiders(): JsonResponse
    {
        return response()->json($this->buildActiveRiders());
    }

    /**
     * Every rider who is out right now, in one list.
     *
     * Two kinds of rider share the road and the dashboard used to know about only
     * one of them:
     *
     *   · a delivery run (a rider working stops in a city), and
     *   · a transporter carrying a batch between hubs.
     *
     * Only the first was listed, and only from `delivery_run_stops` coordinates —
     * and every one of those is NULL, so in practice the map showed nobody at all
     * while a transporter was mid-trip. Positions now come from the phones
     * themselves; stop coordinates remain as a fallback for runs whose rider has
     * no live fix.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildActiveRiders(): array
    {
        $deliveryRiders = $this->deliveryRunRiders();
        $transporters = $this->transporterRiders();

        $driverIds = collect($deliveryRiders)
            ->merge($transporters)
            ->pluck('driver_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $fixes = DriverLocation::latestForDrivers($driverIds);

        return collect($deliveryRiders)
            ->merge($transporters)
            ->map(function (array $rider) use ($fixes) {
                $fix = $rider['driver_id'] ? $fixes->get($rider['driver_id']) : null;

                // A real fix beats a planned stop; a stop beats nothing.
                if ($fix) {
                    $rider['lat'] = (float) $fix->latitude;
                    $rider['lng'] = (float) $fix->longitude;
                    $rider['position_source'] = 'gps';
                    $rider['position_age_minutes'] = $fix->recorded_at
                        ? (int) $fix->recorded_at->diffInMinutes(now())
                        : null;
                    $rider['position_is_live'] = $fix->isLive();
                    $rider['updated_at'] = $fix->recorded_at?->toIso8601String();
                }

                $rider['has_position'] = $rider['lat'] !== null && $rider['lng'] !== null;
                $rider['progress'] = $rider['has_position']
                    ? $this->progressPercent($rider)
                    : ($rider['progress'] ?? 0);

                return $rider;
            })
            ->sortByDesc(fn ($rider) => $rider['has_position'])
            ->values()
            ->all();
    }

    /**
     * Riders working a delivery run today.
     *
     * Position may be missing: the stops carry no coordinates in this deployment,
     * so most of these riders are listed as position-unknown until their phone
     * reports in.
     */
    private function deliveryRunRiders(): array
    {
        $runs = DeliveryRun::with(['assignedDriver', 'items', 'stops'])
            ->where('status', 'out_for_delivery')
            ->get();

        return $runs->map(function ($run) {
            $driver = $run->assignedDriver;
            $driverName = $driver?->name ?: 'Unassigned rider';

            $stops = $run->stops->sortBy('id')->values();
            $lastDone = $stops->filter(fn ($stop) => $stop->arrived_at || $stop->delivered_at)->last();
            $next = $stops->first(fn ($stop) => $stop->status === 'pending');

            $lat = $lastDone?->delivery_latitude ?? $lastDone?->latitude ?? $next?->latitude;
            $lng = $lastDone?->delivery_longitude ?? $lastDone?->longitude ?? $next?->longitude;

            $totalAssigned = $run->items ? $run->items->count() : 0;
            $totalDelivered = $run->items ? $run->items->where('status', 'delivered')->count() : 0;
            $remaining = max($totalAssigned - $totalDelivered, 0);

            return [
                'id' => 'run-'.$run->id,
                'kind' => 'delivery',
                'driver_id' => $run->assigned_driver_id,
                'name' => explode(' ', $driverName)[0],
                'full_name' => $driverName,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($driverName).'&background=random',
                'lat' => $lat !== null && (float) $lat != 0.0 ? (float) $lat : null,
                'lng' => $lng !== null && (float) $lng != 0.0 ? (float) $lng : null,
                'position_source' => ($lat && $lng) ? 'stop' : null,
                'assigned' => $totalAssigned,
                'delivered' => $totalDelivered,
                'remaining' => $remaining,
                'progress' => $totalAssigned > 0 ? round(($totalDelivered / $totalAssigned) * 100) : 0,
                'current_location' => $lastDone?->town ?: 'Assigned — en route to first stop',
                'next_stop' => $next ? ($next->town.' — pending') : 'All stops complete',
                'reference' => $run->run_number,
                'destination' => null,
                'route' => null,
                'updated_at' => ($lastDone?->delivered_at ?? $lastDone?->arrived_at)?->toIso8601String(),
            ];
        })->all();
    }

    /**
     * Transporters carrying a batch between hubs right now.
     *
     * These were invisible to the dashboard: a batch in transit is not a delivery
     * run, so nothing about it appeared in Live Tracking at all. Their progress is
     * measured along the leg they are actually travelling — from the origin hub
     * towards the destination hub, using the latest position report.
     */
    private function transporterRiders(): array
    {
        $manifests = TransportManifest::with([
            'assignedDriver:id,name',
            'originWarehouse:id,name,latitude,longitude',
            'destinationWarehouse:id,name,latitude,longitude',
        ])
            ->whereIn('status', [
                TransportManifest::STATUS_ASSIGNED,
                TransportManifest::STATUS_LOADING,
                TransportManifest::STATUS_IN_TRANSIT,
            ])
            ->whereNotNull('assigned_driver_id')
            ->get();

        return $manifests->map(function ($manifest) {
            $driver = $manifest->assignedDriver;
            $driverName = $driver?->name ?: 'Unassigned transporter';
            $destination = $manifest->destinationWarehouse;
            $origin = $manifest->originWarehouse;

            $packages = (int) $manifest->items()->count();

            return [
                'id' => 'manifest-'.$manifest->id,
                'kind' => 'transport',
                'driver_id' => $manifest->assigned_driver_id,
                'name' => explode(' ', $driverName)[0],
                'full_name' => $driverName,
                'avatar' => 'https://ui-avatars.com/api/?name='.urlencode($driverName).'&background=random',
                'lat' => null,
                'lng' => null,
                'position_source' => null,
                'assigned' => $packages,
                'delivered' => 0,
                'remaining' => $packages,
                'progress' => 0,
                'current_location' => $origin
                    ? 'Left '.$origin->name.' — '.$this->manifestStatusLabel($manifest->status)
                    : $this->manifestStatusLabel($manifest->status),
                'next_stop' => $destination ? $destination->name.' — destination hub' : 'Destination hub not set',
                'reference' => $manifest->manifest_number,
                'destination' => $destination?->name,
                'route' => ($origin && $destination) ? [
                    'origin' => [
                        'lat' => (float) $origin->latitude,
                        'lng' => (float) $origin->longitude,
                        'name' => $origin->name,
                    ],
                    'destination' => [
                        'lat' => (float) $destination->latitude,
                        'lng' => (float) $destination->longitude,
                        'name' => $destination->name,
                    ],
                ] : null,
                'updated_at' => $manifest->dispatched_at?->toIso8601String(),
            ];
        })->all();
    }

    /**
     * How far along the leg a transporter is, as a percentage.
     *
     * Measured from the origin hub to the destination hub through the rider's
     * current position. Null where the route or the rider cannot be placed — the
     * caller falls back to whatever the rider already had.
     */
    private function progressPercent(array $rider): ?int
    {
        $route = $rider['route'] ?? null;

        if (! $route || $rider['lat'] === null) {
            return $rider['progress'] ?? null;
        }

        $origin = $route['origin'];
        $destination = $route['destination'];

        $total = $this->metresBetween($origin['lat'], $origin['lng'], $destination['lat'], $destination['lng']);

        if ($total <= 0) {
            return null;
        }

        $travelled = $this->metresBetween($origin['lat'], $origin['lng'], (float) $rider['lat'], (float) $rider['lng']);

        return (int) max(0, min(100, round(($travelled / $total) * 100)));
    }

    private function metresBetween(float $fromLat, float $fromLng, float $toLat, float $toLng): float
    {
        $earthRadius = 6371000.0;

        $dLat = deg2rad($toLat - $fromLat);
        $dLng = deg2rad($toLng - $fromLng);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($fromLat)) * cos(deg2rad($toLat)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadius * asin(min(1.0, sqrt($a)));
    }

    private function manifestStatusLabel(?string $status): string
    {
        return match ($status) {
            TransportManifest::STATUS_ASSIGNED => 'assigned, not yet loaded',
            TransportManifest::STATUS_LOADING => 'loading at the hub',
            TransportManifest::STATUS_IN_TRANSIT => 'on the road',
            default => (string) $status,
        };
    }
}
