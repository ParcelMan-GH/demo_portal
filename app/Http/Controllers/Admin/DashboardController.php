<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\Invoice;
use App\Models\Shipment;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $admin = Auth::guard('admin')->user();

        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();

        // ── Today's Snapshot ──────────────────────────────────────────────
        $todayShipments       = Shipment::whereDate('created_at', $today)->count();
        $pendingPickups       = Shipment::where('status', 'pickup_assigned')->count();
        $atWarehouse          = Shipment::where('status', 'at_warehouse')->count();
        $outForDelivery       = DeliveryRun::where('status', 'out_for_delivery')->count();
        $deliveredToday       = Shipment::where('status', 'delivered')
                                    ->whereDate('updated_at', $today)
                                    ->count();
        $pendingInvoices      = Invoice::where('status', 'sent')->count();

        // ── Financial Summary (this month) ────────────────────────────────
        $totalInvoicedMonth   = Invoice::where('status', 'accepted')
                                    ->where('created_at', '>=', $monthStart)
                                    ->sum('total_amount');
        $activeVendorsMonth   = Vendor::whereHas('shipments', function ($q) use ($monthStart) {
                                    $q->where('created_at', '>=', $monthStart);
                                })->count();
        $totalShipmentsMonth  = Shipment::where('created_at', '>=', $monthStart)->count();

        // ── Recent Activity ───────────────────────────────────────────────
        $recentShipments      = Shipment::with('vendor:id,name,business_name')
                                    ->latest()
                                    ->limit(10)
                                    ->get(['id', 'shipment_number', 'status', 'vendor_id', 'created_at']);

        $activeDeliveryRuns   = DeliveryRun::where('status', 'out_for_delivery')
                                    ->with('assignedDriver:id,name')
                                    ->latest()
                                    ->limit(5)
                                    ->get(['id', 'run_number', 'status', 'assigned_driver_id', 'dispatched_at']);

        $activeManifests      = TransportManifest::whereIn('status', ['in_transit', 'assigned', 'loading'])
                                    ->with(['originWarehouse:id,name', 'destinationWarehouse:id,name'])
                                    ->latest()
                                    ->limit(5)
                                    ->get(['id', 'manifest_number', 'status', 'origin_warehouse_id', 'destination_warehouse_id', 'dispatched_at']);

        // ── User stats (superadmin only) ──────────────────────────────────
        if ($admin->isSuperAdmin()) {
            $adminStats = [
                'total'    => User::whereNull('warehouse_id')->count(),
                'active'   => User::whereNull('warehouse_id')->where('is_active', true)->count(),
                'inactive' => User::whereNull('warehouse_id')->where('is_active', false)->count(),
            ];
        } else {
            $adminStats = [
                'total'    => User::where('created_by_user_id', $admin->id)->count(),
                'active'   => User::where('created_by_user_id', $admin->id)->where('is_active', true)->count(),
                'inactive' => User::where('created_by_user_id', $admin->id)->where('is_active', false)->count(),
            ];
        }

        return view('admin.dashboard.index', compact(
            'admin',
            'todayShipments',
            'pendingPickups',
            'atWarehouse',
            'outForDelivery',
            'deliveredToday',
            'pendingInvoices',
            'totalInvoicedMonth',
            'activeVendorsMonth',
            'totalShipmentsMonth',
            'recentShipments',
            'activeDeliveryRuns',
            'activeManifests',
            'adminStats'
        ));
    }
}
