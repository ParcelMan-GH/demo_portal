<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\Shipment;
use App\Models\Vendor;
use App\Services\BackOfficeAccess;
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

        // ── Active Riders (REAL DATA MAPPING) ─────────────────────────────
        // Fetch runs actively on the road, eager load the driver and shipments to calculate stats
        $activeRuns = DeliveryRun::with(['assignedDriver', 'items'])
                                 ->where('status', 'out_for_delivery')
                                 ->get();

        $activeRiders = $activeRuns->map(function($run) {
            $driverName = $run->assignedDriver ? $run->assignedDriver->name : 'Unknown Rider';
            
            // Calculate real stats from the run's shipments
            $totalAssigned = $run->items? $run->items->count() : 0;
            $totalDelivered = $run->items ? $run->items->where('status', 'delivered')->count() : 0;
            $remaining = $totalAssigned - $totalDelivered;
            $progress = $totalAssigned > 0 ? round(($totalDelivered / $totalAssigned) * 100) : 0;

            // Generate coordinates around Accra (Replace with real GPS columns later)
            $lat = 5.6037 + (mt_rand(-50, 50) / 1000);
            $lng = -0.1870 + (mt_rand(-50, 50) / 1000);

            return [
                'id' => $run->id,
                'name' => explode(' ', $driverName)[0], // Get first name like "Vincent"
                'full_name' => $driverName,
                'avatar' => "https://ui-avatars.com/api/?name=".urlencode($driverName)."&background=random", // Fallback avatar
                'lat' => $lat,
                'lng' => $lng,
                'assigned' => $totalAssigned,
                'delivered' => $totalDelivered,
                'remaining' => $remaining,
                'progress' => $progress,
                'current_location' => 'In Transit', 
                'next_stop' => 'Pending GPS...'
            ];
        })->values()->toArray();

        // Fallback fake data if no runs are currently active (so the map doesn't look empty during dev)
        if (empty($activeRiders)) {
            $activeRiders = [
                ['id' => 1, 'name' => 'Vincent', 'full_name' => 'Vincent O.', 'avatar' => 'https://ui-avatars.com/api/?name=Vincent&background=1e293b&color=fff', 'lat' => 5.6400, 'lng' => -0.1600, 'assigned' => 18, 'delivered' => 11, 'remaining' => 7, 'progress' => 61, 'current_location' => 'East Legon', 'next_stop' => 'Osu, Accra. 3.2 km away'],
                ['id' => 2, 'name' => 'Kwame', 'full_name' => 'Kwame Mensah', 'avatar' => 'https://ui-avatars.com/api/?name=Kwame&background=1e293b&color=fff', 'lat' => 5.6037, 'lng' => -0.1870, 'assigned' => 24, 'delivered' => 12, 'remaining' => 12, 'progress' => 50, 'current_location' => 'Airport Res', 'next_stop' => 'Cantonments. 1.5 km away'],
                ['id' => 3, 'name' => 'Isaac', 'full_name' => 'Isaac A.', 'avatar' => 'https://ui-avatars.com/api/?name=Isaac&background=1e293b&color=fff', 'lat' => 5.6550, 'lng' => -0.1450, 'assigned' => 10, 'delivered' => 9, 'remaining' => 1, 'progress' => 90, 'current_location' => 'Madina', 'next_stop' => 'Adenta. 4.0 km away'],
            ];
        }

        return view('admin.dashboard.index', compact(
            'admin', 'totalShipmentsMonth', 'shipmentsChange', 'outForDelivery',
            'activeDeliveriesChange', 'totalVendors', 'vendorsChange', 'onTimeDeliveryRate',
            'onTimeChange', 'recentShipments', 'activeRiders'
        ));
    }
}