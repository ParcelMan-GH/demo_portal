<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\Shipment;
use App\Models\Vendor;
use App\Services\BackOfficeAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(BackOfficeAccess $access): View|RedirectResponse
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
        $onTimeChange = -10.6; 

        // ── Recent Activity Feed ──────────────────────────────────────────
        $recentShipments = Shipment::with('vendor:id,name,business_name')
                                   ->latest()
                                   ->limit(10)
                                   ->get(['id', 'shipment_number', 'status', 'vendor_id', 'created_at', 'updated_at']);

        // ── Active Riders (REAL POSITION MAPPING) ─────────────────────────
        // Positions come from real delivery_run_stops coordinates (the last stop
        // the driver reached + the next pending stop). No fabricated data —
        // riders without coordinates are not rendered, and the map shows an
        // empty state instead.
        $activeRiders = $this->buildActiveRiders();

        return view('admin.dashboard.index', compact(
            'admin', 'totalShipmentsMonth', 'shipmentsChange', 'outForDelivery',
            'activeDeliveriesChange', 'totalVendors', 'vendorsChange', 'onTimeDeliveryRate',
            'onTimeChange', 'recentShipments', 'activeRiders'
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
     * Build the list of active riders with real coordinates from
     * delivery_run_stops: the last stop the driver reached (arrived/delivered)
     * is the current position, the first pending stop is the destination.
     *
     * Riders without any coordinates are omitted — the frontend shows an
     * empty state instead of fake dots.
     */
    private function buildActiveRiders(): array
    {
        $activeRuns = DeliveryRun::with(['assignedDriver', 'items', 'stops'])
                                 ->where('status', 'out_for_delivery')
                                 ->get();

        return $activeRuns->map(function ($run) {
            $driverName = $run->assignedDriver ? $run->assignedDriver->name : 'Unknown Rider';

            // Stops in creation order
            $stops = $run->stops->sortBy('id')->values();

            // Last stop the driver actually reached, and the next pending one
            $lastDone = $stops->filter(fn ($stop) => $stop->arrived_at || $stop->delivered_at)->last();
            $next = $stops->first(fn ($stop) => $stop->status === 'pending');

            // Captured coordinates first, else the stop's target coordinates
            $lat = (float) ($lastDone?->delivery_latitude ?? $lastDone?->latitude ?? $next?->latitude ?? 0);
            $lng = (float) ($lastDone?->delivery_longitude ?? $lastDone?->longitude ?? $next?->longitude ?? 0);

            // Never render a rider without real coordinates — no fake dots
            if ($lat == 0.0 || $lng == 0.0) {
                return null;
            }

            // Real stats from the run's shipment items
            $totalAssigned = $run->items ? $run->items->count() : 0;
            $totalDelivered = $run->items ? $run->items->where('status', 'delivered')->count() : 0;
            $remaining = max($totalAssigned - $totalDelivered, 0);
            $progress = $totalAssigned > 0 ? round(($totalDelivered / $totalAssigned) * 100) : 0;

            $lastUpdated = $lastDone?->delivered_at ?? $lastDone?->arrived_at;

            return [
                'id' => $run->id,
                'name' => explode(' ', $driverName)[0], // Get first name like "Vincent"
                'full_name' => $driverName,
                'avatar' => "https://ui-avatars.com/api/?name=".urlencode($driverName)."&background=random",
                'lat' => $lat,
                'lng' => $lng,
                'assigned' => $totalAssigned,
                'delivered' => $totalDelivered,
                'remaining' => $remaining,
                'progress' => $progress,
                'current_location' => $lastDone?->town ?? 'Assigned — en route to first stop',
                'next_stop' => $next ? ($next->town.' — pending') : 'All stops complete',
                'updated_at' => $lastUpdated ? $lastUpdated->toIso8601String() : null,
            ];
        })->filter()->values()->toArray();
    }
}