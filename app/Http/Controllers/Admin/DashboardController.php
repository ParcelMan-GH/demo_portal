<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\Shipment;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseReceiptItemLabel;
use App\Services\BackOfficeAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(BackOfficeAccess $access): View|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();

        $canUseHqDashboard = collect([
            'shipments.view',
            'vendors.view',
            'drivers.view',
            'warehouses.view',
            'settings.view',
        ])->contains(fn (string $permission) => $access->canUsePermission($admin, $permission));

        if (!$canUseHqDashboard) {
            if ($admin->hasPermission('warehouse.recipient_payments.view')) {
                return redirect()->route('warehouse.recipient-payments.index');
            }

            return redirect()->route('warehouse.dashboard');
        }

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
        // ── Pipeline counts ───────────────────────────────────────────────
        $submitted            = Shipment::where('status', 'submitted')->count();
        $pickedUp             = Shipment::where('status', 'picked_up')->count();
        $sorted               = Shipment::where('status', 'sorted')->count();
        $inTransit            = Shipment::where('status', 'in_transit')->count();

        // ── Package Custody ───────────────────────────────────────────────
        $totalLabels          = WarehouseReceiptItemLabel::count();
        $claimedLabels        = LabelCustodyEvent::where('event_type', 'claimed')
                                    ->whereIn('id', function ($q) {
                                        $q->selectRaw('MAX(id)')
                                          ->from('label_custody_events')
                                          ->groupBy('warehouse_receipt_item_label_id');
                                    })
                                    ->count();

        // ── Driver stats ──────────────────────────────────────────────────
        $totalDrivers         = Driver::where('is_active', true)->count();
        $driversWithPackages  = LabelCustodyEvent::where('event_type', 'claimed')
                                    ->whereIn('id', function ($q) {
                                        $q->selectRaw('MAX(id)')
                                          ->from('label_custody_events')
                                          ->groupBy('warehouse_receipt_item_label_id');
                                    })
                                    ->distinct('driver_id')
                                    ->count('driver_id');

        // ── Financial Summary (this month) ────────────────────────────────
        $activeVendorsMonth   = Vendor::whereHas('shipments', function ($q) use ($monthStart) {
                                    $q->where('created_at', '>=', $monthStart);
                                })->count();
        $totalShipmentsMonth  = Shipment::where('created_at', '>=', $monthStart)->count();
        $totalVendors         = Vendor::count();

        // ── Needs Attention ───────────────────────────────────────────────
        $needsAttention       = Shipment::with('vendor:id,name,business_name')
                                    ->where('status', 'submitted')
                                    ->latest()
                                    ->limit(5)
                                    ->get(['id', 'shipment_number', 'status', 'vendor_id', 'created_at', 'sender_notes']);

        $totalNeedsAttention  = Shipment::where('status', 'submitted')->count();

        // ── Recent Activity ───────────────────────────────────────────────
        $recentShipments      = Shipment::with('vendor:id,name,business_name')
                                    ->latest()
                                    ->limit(8)
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

        // ── User stats (HQ only) ──────────────────────────────────────────
        if ($admin->isHqUser()) {
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
            'needsAttention',
            'totalNeedsAttention',
            'todayShipments',
            'pendingPickups',
            'atWarehouse',
            'outForDelivery',
            'deliveredToday',
            'submitted',
            'pickedUp',
            'sorted',
            'inTransit',
            'totalLabels',
            'claimedLabels',
            'totalDrivers',
            'driversWithPackages',
            'activeVendorsMonth',
            'totalShipmentsMonth',
            'totalVendors',
            'recentShipments',
            'activeDeliveryRuns',
            'activeManifests',
            'adminStats'
        ));
    }
}
