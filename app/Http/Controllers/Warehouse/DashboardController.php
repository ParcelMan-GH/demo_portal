<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use App\Services\Warehouse\WarehouseDashboardService;
use App\Services\Warehouse\WarehousePortalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private WarehousePortalService $portalService,
        private WarehouseDashboardService $dashboardService,
    )
    {
    }

    public function index(): View
    {
        $user = Auth::guard('admin')->user();
        $warehouse = $this->portalService->resolveWarehouse($user);
        $stats = $this->portalService->getDashboardStats($warehouse);
        $dashboard = $this->dashboardService->data($warehouse, $user);

        return view('warehouse.dashboard.index', [
            'warehouse' => $warehouse,
            'stats' => $stats,
            'dashboard' => $dashboard,
        ]);
    }
}
