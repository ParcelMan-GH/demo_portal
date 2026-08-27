<?php

use App\Http\Controllers\Admin\AdminContactQueueController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDeliveryRunController;
use App\Http\Controllers\Admin\AdminInAppNotificationController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminMarketingController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AdminShipmentChargesController;
use App\Http\Controllers\Admin\AdminSortBatchController;
use App\Http\Controllers\Admin\AdminTransportManifestController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BackOfficeContextController;
use App\Http\Controllers\Admin\CollectionCenterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Http\Controllers\Admin\PickupAssignmentController;
use App\Http\Controllers\Admin\RecipientPaymentController;
use App\Http\Controllers\Admin\RiderTeamController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ShipmentPaymentController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\VendorPayoutController;
use App\Http\Controllers\Admin\WarehouseCapabilityController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\BusHandoffPublicController;
use App\Http\Controllers\Warehouse\CollectionController as WarehouseCollectionController;
use App\Http\Controllers\Warehouse\ContactQueueController as WarehouseContactQueueController;
use App\Http\Controllers\Warehouse\DashboardController as WarehouseDashboardController;
use App\Http\Controllers\Warehouse\DeliveryRunController as WarehouseDeliveryRunController;
use App\Http\Controllers\Warehouse\PackageController as WarehousePackageController;
use App\Http\Controllers\Warehouse\ReceiptController as WarehouseReceiptController;
use App\Http\Controllers\Warehouse\RiderPackageAuditController as WarehouseRiderPackageAuditController;
use App\Http\Controllers\Warehouse\ShipmentPaymentController as WarehouseShipmentPaymentController;
use App\Http\Controllers\Warehouse\SortingController as WarehouseSortingController;
use App\Http\Controllers\Warehouse\TransportManifestController as WarehouseTransportManifestController;
use App\Http\Controllers\Warehouse\UserController as WarehouseUserController;
use App\Http\Controllers\Warehouse\WalkinController as WarehouseWalkinController;
use App\Http\Controllers\Warehouse\WarehouseShipmentChargesController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AgentAllocationController;
use App\Http\Controllers\Warehouse\MobileCameraController;
use App\Http\Controllers\Admin\CommissionLedgerController;

use App\Http\Controllers\Agent\AgentDashboardController;

// Call Agent Dashboard & Actions
Route::prefix('agent')->name('agent.')->group(function () {
    Route::get('/dashboard', [AgentDashboardController::class, 'index'])->name('dashboard');
    Route::post('/tasks/{task}/approve', [AgentDashboardController::class, 'approvePayment'])->name('tasks.approve');
});

// Commission Ledger & Overrides
Route::get('/agents/ledger', [CommissionLedgerController::class, 'index'])->name('admin.agents.ledger');
Route::post('/agents/ledger/{quota}/override', [CommissionLedgerController::class, 'override'])->name('admin.agents.ledger.override');

// Smart Allocation Panel
Route::get('/agents/allocation', [AgentAllocationController::class, 'index'])->name('admin.agents.allocation');
Route::post('/agents/allocation/assign', [AgentAllocationController::class, 'assignTasks'])->name('admin.agents.allocation.assign');

// Mobile Handoff Routes (No Auth Required)
Route::get('/mobile-camera/{sessionId}', [MobileCameraController::class, 'show'])->name('mobile-camera.show');
Route::post('/mobile-camera/{sessionId}/upload', [MobileCameraController::class, 'upload'])->name('mobile-camera.upload');

Route::get('/', function () {
    return view('web.landing');
})->name('web.landing');

Route::get('privacy-policy', fn () => view('web.legal.privacy'))->name('web.privacy');
Route::get('terms-of-service', fn () => view('web.legal.terms'))->name('web.terms');

Route::get('h/{token}', [BusHandoffPublicController::class, 'show'])->name('bus-handoff.public.show');
Route::post('h/{token}/confirm', [BusHandoffPublicController::class, 'confirm'])->name('bus-handoff.public.confirm');
Route::post('h/{token}/issue', [BusHandoffPublicController::class, 'issue'])->name('bus-handoff.public.issue');

if (app()->environment(['local', 'testing'])) {
    Route::get('dev/bus-handoff-confirmation-preview', function () {
        $reasons = \App\Models\DeliveryFailureReason::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'label'])
            ->map(fn ($reason) => ['id' => $reason->id, 'label' => $reason->label])
            ->values()
            ->all();

        return view('public.bus-handoff-confirmation', [
            'token' => 'PREVIEWTOKEN',
            'handoff' => [
                'package' => [
                    'tracking_code' => 'TRKPREVIEW001',
                    'description' => 'Foodstuff bag',
                ],
                'handoff' => [
                    'bus_station' => 'Neoplan Station',
                    'handed_off_at' => now()->subHours(3)->toIso8601String(),
                ],
            ],
            'reasons' => $reasons ?: [
                ['id' => 1, 'label' => 'Recipient says not received'],
                ['id' => 2, 'label' => 'Recipient unreachable'],
                ['id' => 3, 'label' => 'Courier delay'],
                ['id' => 4, 'label' => 'Package damaged'],
            ],
        ]);
    })->name('dev.bus-handoff-confirmation-preview');
}

Route::prefix('vendor')->name('web.vendor.')->group(function () {
    Route::get('login', function () {
        return view('web.vendor.login');
    })->name('login');

    Route::get('home', function () {
        return view('web.vendor.home');
    })->name('home');

    Route::get('profile', function () {
        return view('web.vendor.profile');
    })->name('profile');

    Route::prefix('shipments')->name('shipments.')->group(function () {
        Route::get('/', function () {
            return view('web.vendor.shipments.index');
        })->name('index');

        Route::get('create', function () {
            return view('web.vendor.shipments.create');
        })->name('create');

        Route::get('{shipment}/edit', function ($shipment) {
            return view('web.vendor.shipments.edit', ['shipmentId' => $shipment]);
        })->name('edit');

        Route::get('{shipment}', function ($shipment) {
            return view('web.vendor.shipments.show', ['shipmentId' => $shipment]);
        })->name('show');
    });

});

Route::prefix('driver')->name('web.driver.')->group(function () {
    Route::get('login', function () {
        return view('web.driver.login');
    })->name('login');

    Route::get('home', function () {
        return view('web.driver.home');
    })->name('home');

    Route::get('profile', function () {
        return view('web.driver.profile');
    })->name('profile');

    Route::prefix('pickups')->name('pickups.')->group(function () {
        Route::get('/', function () {
            return view('web.driver.pickups.index');
        })->name('index');

        Route::get('{pickup}', function ($pickup) {
            return view('web.driver.pickups.show', ['pickupId' => $pickup]);
        })->name('show');
    });

    Route::prefix('transports')->name('transports.')->group(function () {
        Route::get('/', function () {
            return view('web.driver.transports.index');
        })->name('index');

        Route::get('{manifest}', function ($manifest) {
            return view('web.driver.transports.show', ['manifestId' => $manifest]);
        })->name('show');
    });

    Route::prefix('deliveries')->name('deliveries.')->group(function () {
        Route::get('/', function () {
            return view('web.driver.deliveries.index');
        })->name('index');

        Route::get('{run}', function ($run) {
            return view('web.driver.deliveries.show', ['runId' => $run]);
        })->name('show');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix(config('backoffice.prefix', 'admin'))->name('admin.')->group(function () {
    // Guest routes (login)
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    // Shared authenticated route(s)
    Route::middleware(['auth:admin', 'admin.audit'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });

    // Authenticated back-office routes
    Route::middleware(['auth:admin', 'admin.audit', 'backoffice.user'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('dashboard/live-riders', [DashboardController::class, 'liveRiders'])->name('dashboard.live-riders');
        Route::post('context/warehouse', [BackOfficeContextController::class, 'updateWarehouse'])
            ->name('context.warehouse.update');
        Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])
            ->name('impersonation.stop');

        // System Usage Manual
        Route::get('manual', fn () => view('admin.manual.index'))->name('manual');

        // Admin Self-Profile
        Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/change-password', [AdminProfileController::class, 'changePassword'])->name('profile.change-password');

        // Global Search
        Route::get('search', [AdminSearchController::class, 'search'])->name('search');
        Route::get('search/results', [AdminSearchController::class, 'results'])->name('search.results');

        // Admin in-app notifications
        Route::get('in-app-notifications', [AdminInAppNotificationController::class, 'index'])->name('in-app-notifications.index');
        Route::post('in-app-notifications/read-all', [AdminInAppNotificationController::class, 'markAllRead'])->name('in-app-notifications.read-all');
        Route::post('in-app-notifications/{notification}/read', [AdminInAppNotificationController::class, 'markRead'])->name('in-app-notifications.mark-read');

        // Role Management
        Route::get('roles/warehouse', [RoleController::class, 'warehouseIndex'])->name('roles.warehouse.index');
        Route::get('roles-data', [RoleController::class, 'data'])->name('roles.data');
        Route::get('roles-export', [RoleController::class, 'export'])->name('roles.export');
        Route::resource('roles', RoleController::class);

        // Admin Management
        Route::resource('admins', AdminController::class)->except(['destroy']);
        Route::get('admins-data', [AdminController::class, 'data'])->name('admins.data');
        Route::get('admins-export', [AdminController::class, 'export'])->name('admins.export');
        Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');
        Route::patch('admins/{admin}/toggle-active', [AdminController::class, 'toggleActive'])
            ->name('admins.toggle-active');
        Route::post('admins/{admin}/assign-roles', [AdminController::class, 'assignRoles'])
            ->name('admins.assign-roles');
        Route::get('admins/{admin}/audit-logs-data', [AdminController::class, 'auditLogsData'])
            ->name('admins.audit-logs-data');

        // Vendor Management
        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('vendors-data', [VendorController::class, 'data'])->name('vendors.data');
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('vendors/{vendor}', [VendorController::class, 'showPage'])->name('vendors.show')->withTrashed();
        Route::get('vendors/{vendor}/json', [VendorController::class, 'show'])->name('vendors.show.json')->withTrashed();
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy')->withTrashed();
        Route::patch('vendors/{vendor}/toggle-active', [VendorController::class, 'toggleActive'])
            ->name('vendors.toggle-active');
        Route::patch('vendors/{vendor}/restore', [VendorController::class, 'restore'])
            ->name('vendors.restore')->withTrashed();
        Route::get('vendors-export', [VendorController::class, 'export'])->name('vendors.export');
        Route::get('vendors/{vendor}/shipments', [VendorController::class, 'shipments'])->name('vendors.shipments')->withTrashed();
        Route::get('vendors/{vendor}/packages', [VendorController::class, 'packages'])->name('vendors.packages')->withTrashed();
        Route::get('vendors/{vendor}/activity-logs', [VendorController::class, 'activityLogs'])->name('vendors.activity-logs')->withTrashed();
        Route::get('vendors/{vendor}/otp-logs', [VendorController::class, 'otpLogs'])->name('vendors.otp-logs')->withTrashed();

        // Vendor Payouts Management
        Route::get('vendor-payouts', [VendorPayoutController::class, 'index'])->name('vendor-payouts.index');
        Route::get('vendor-payouts-data', [VendorPayoutController::class, 'data'])->name('vendor-payouts.data');
        Route::get('vendors/{vendor}/payouts-data', [VendorPayoutController::class, 'vendorPayouts'])->name('vendors.payouts-data');
        Route::post('vendors/{vendor}/payouts', [VendorPayoutController::class, 'store'])->name('vendors.payouts.store');
        Route::patch('vendor-payouts/{payout}/mark-sent', [VendorPayoutController::class, 'markSent'])->name('vendor-payouts.mark-sent');
        Route::patch('vendor-payouts/{payout}/confirm', [VendorPayoutController::class, 'confirm'])->name('vendor-payouts.confirm');

        // Order Management (HQ-facing shipment workflow)
        Route::get('operations/orders', fn () => redirect()->route('admin.orders.index'))->name('operations.orders.index');
        Route::get('operations/orders-data', [ShipmentController::class, 'data'])->name('operations.orders.data');
        Route::get('operations/orders-export', [ShipmentController::class, 'export'])->name('operations.orders.export');
        Route::get('operations/orders/{shipment}', fn (\App\Models\Shipment $shipment) => redirect()->route('admin.orders.show', $shipment))->name('operations.orders.show');
        Route::get('orders', [ShipmentController::class, 'index'])->name('orders.index');
        Route::get('orders-data', [ShipmentController::class, 'data'])->name('orders.data');
        Route::get('orders/create', [ShipmentController::class, 'create'])->name('orders.create');
        Route::post('orders', [ShipmentController::class, 'store'])->name('orders.store');
        Route::get('orders/vendor-lookup', [ShipmentController::class, 'vendorLookup'])->name('orders.vendor-lookup');
        Route::post('orders/vendor-create', [ShipmentController::class, 'vendorCreate'])->name('orders.vendor-create');
        Route::get('orders/{shipment}', [ShipmentController::class, 'showPage'])->name('orders.show');
        Route::get('orders/{shipment}/edit', [ShipmentController::class, 'editPage'])->name('orders.edit');
        Route::post('orders/{shipment}/duplicate', [ShipmentController::class, 'duplicate'])->name('orders.duplicate');
        Route::post('orders/{shipment}/reject', [ShipmentController::class, 'reject'])->name('orders.reject');
        Route::post('orders/{shipment}/reopen', [ShipmentController::class, 'reopenRejected'])->name('orders.reopen');
        Route::put('orders/{shipment}', [ShipmentController::class, 'updateShipment'])->name('orders.update');
        Route::post('orders/{shipment}/packages', [ShipmentController::class, 'addPackage'])->name('orders.packages.add');
        Route::put('orders/{shipment}/packages/{item}', [ShipmentController::class, 'updatePackage'])->name('orders.packages.update');
        Route::delete('orders/{shipment}/packages/{item}', [ShipmentController::class, 'deletePackage'])->name('orders.packages.delete');
        Route::post('orders/{shipment}/packages/{item}/split', [ShipmentController::class, 'splitPackage'])->name('orders.packages.split');
        Route::post('orders/{shipment}/auto-group-by-phone', [ShipmentController::class, 'autoGroupByPhone'])->name('orders.auto-group-by-phone');
        Route::post('orders/{shipment}/packages/{item}/photos', [ShipmentController::class, 'uploadPhotos'])->name('orders.packages.photos.upload');
        Route::post('orders/{shipment}/photos/move', [ShipmentController::class, 'movePhoto'])->name('orders.packages.photos.move');
        Route::delete('orders/photos/{image}', [ShipmentController::class, 'deletePhoto'])->name('orders.packages.photos.delete');
        Route::get('orders/{shipment}/items', [ShipmentController::class, 'items'])->name('orders.items');
        Route::get('orders/{shipment}/tracking', [ShipmentController::class, 'tracking'])->name('orders.tracking');
        Route::get('orders/{shipment}/receiving-data', [ShipmentController::class, 'receivingData'])->name('orders.receiving-data');
        Route::post('orders/{shipment}/receiving/{item}/details', [ShipmentController::class, 'saveReceivingPackageDetails'])->name('orders.receiving.details');
        Route::post('orders/{shipment}/receiving/{item}/save', [ShipmentController::class, 'receivePackage'])->name('orders.receiving.save');
        Route::post('orders/{shipment}/receiving/{item}/print-label', [ShipmentController::class, 'printPackageLabel'])->name('orders.receiving.print-label');
        Route::post('orders/{shipment}/receiving/finalize', [ShipmentController::class, 'finalizeReceiving'])->name('orders.receiving.finalize');
        Route::post('orders/{shipment}/admin-complete-pickup', [ShipmentController::class, 'adminCompletePickup'])->name('orders.admin-complete-pickup');
        Route::get('orders/{shipment}/charges', [AdminShipmentChargesController::class, 'index'])->name('orders.charges.index');
        Route::post('orders/{shipment}/charges', [AdminShipmentChargesController::class, 'store'])->name('orders.charges.store');
        Route::post('orders/{shipment}/charges/seed-pickup-fee', [AdminShipmentChargesController::class, 'seedPickupFee'])->name('orders.charges.seed-pickup-fee');
        Route::put('orders/{shipment}/charges/{charge}', [AdminShipmentChargesController::class, 'update'])->name('orders.charges.update');
        Route::patch('orders/{shipment}/charges/{charge}/mark-paid', [AdminShipmentChargesController::class, 'markPaid'])->name('orders.charges.mark-paid');
        Route::patch('orders/{shipment}/charges/{charge}/waive', [AdminShipmentChargesController::class, 'waive'])->name('orders.charges.waive');
        Route::delete('orders/{shipment}/charges/{charge}', [AdminShipmentChargesController::class, 'cancel'])->name('orders.charges.cancel');
        Route::get('orders/{shipment}/custody-data', [ShipmentController::class, 'custodyData'])->name('orders.custody-data');
        Route::post('orders/create-run-from-claims', [ShipmentController::class, 'createRunFromClaims'])->name('orders.create-run-from-claims');
        Route::post('orders/{shipment}/fulfillment-type', [ShipmentController::class, 'updateFulfillmentType'])->name('orders.update-fulfillment-type');
        Route::get('orders-export', [ShipmentController::class, 'export'])->name('orders.export');

        // Legacy shipment URLs kept as compatibility entry points.
        Route::get('operations/shipments', fn () => redirect()->route('admin.orders.index'))->name('operations.shipments.index');
        Route::get('operations/shipments-data', [ShipmentController::class, 'data'])->name('operations.shipments.data');
        Route::get('operations/shipments-export', [ShipmentController::class, 'export'])->name('operations.shipments.export');
        Route::get('operations/shipments/{shipment}', fn (\App\Models\Shipment $shipment) => redirect()->route('admin.orders.show', $shipment))->name('operations.shipments.show');
        Route::get('shipments', fn () => redirect()->route('admin.orders.index'))->name('shipments.index');
        Route::get('shipments-data', [ShipmentController::class, 'data'])->name('shipments.data');
        Route::get('shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('shipments/vendor-lookup', [ShipmentController::class, 'vendorLookup'])->name('shipments.vendor-lookup');
        Route::post('shipments/vendor-create', [ShipmentController::class, 'vendorCreate'])->name('shipments.vendor-create');
        Route::get('locations/search', [ShipmentController::class, 'locationSearch'])->name('locations.search');
        Route::get('shipments/{shipment}', fn (\App\Models\Shipment $shipment) => redirect()->route('admin.orders.show', $shipment))->name('shipments.show');
        Route::get('shipments/{shipment}/edit', fn (\App\Models\Shipment $shipment) => redirect()->route('admin.orders.edit', $shipment))->name('shipments.edit');
        Route::post('shipments/{shipment}/duplicate', [ShipmentController::class, 'duplicate'])->name('shipments.duplicate');
        Route::post('shipments/{shipment}/reject', [ShipmentController::class, 'reject'])->name('shipments.reject');
        Route::post('shipments/{shipment}/reopen', [ShipmentController::class, 'reopenRejected'])->name('shipments.reopen');
        Route::put('shipments/{shipment}', [ShipmentController::class, 'updateShipment'])->name('shipments.update');
        Route::post('shipments/{shipment}/packages', [ShipmentController::class, 'addPackage'])->name('shipments.packages.add');
        Route::put('shipments/{shipment}/packages/{item}', [ShipmentController::class, 'updatePackage'])->name('shipments.packages.update');
        Route::delete('shipments/{shipment}/packages/{item}', [ShipmentController::class, 'deletePackage'])->name('shipments.packages.delete');
        Route::post('shipments/{shipment}/packages/{item}/split', [ShipmentController::class, 'splitPackage'])->name('shipments.packages.split');
        Route::post('shipments/{shipment}/auto-group-by-phone', [ShipmentController::class, 'autoGroupByPhone'])->name('shipments.auto-group-by-phone');
        Route::post('shipments/{shipment}/packages/{item}/photos', [ShipmentController::class, 'uploadPhotos'])->name('shipments.packages.photos.upload');
        Route::post('shipments/{shipment}/photos/move', [ShipmentController::class, 'movePhoto'])->name('shipments.packages.photos.move');
        Route::delete('shipments/photos/{image}', [ShipmentController::class, 'deletePhoto'])->name('shipments.packages.photos.delete');
        Route::get('shipments/{shipment}/items', [ShipmentController::class, 'items'])->name('shipments.items');
        Route::get('shipments/{shipment}/tracking', [ShipmentController::class, 'tracking'])->name('shipments.tracking');
        Route::get('shipments/{shipment}/receiving-data', [ShipmentController::class, 'receivingData'])->name('shipments.receiving-data');
        Route::post('shipments/{shipment}/receiving/{item}/details', [ShipmentController::class, 'saveReceivingPackageDetails'])->name('shipments.receiving.details');
        Route::post('shipments/{shipment}/receiving/{item}/save', [ShipmentController::class, 'receivePackage'])->name('shipments.receiving.save');
        Route::post('shipments/{shipment}/receiving/{item}/print-label', [ShipmentController::class, 'printPackageLabel'])->name('shipments.receiving.print-label');
        Route::post('shipments/{shipment}/receiving/finalize', [ShipmentController::class, 'finalizeReceiving'])->name('shipments.receiving.finalize');
        Route::post('shipments/{shipment}/admin-complete-pickup', [ShipmentController::class, 'adminCompletePickup'])->name('shipments.admin-complete-pickup');

        // Shipment charges ledger (admin)
        Route::get('shipments/{shipment}/charges', [AdminShipmentChargesController::class, 'index'])->name('shipments.charges.index');
        Route::post('shipments/{shipment}/charges', [AdminShipmentChargesController::class, 'store'])->name('shipments.charges.store');
        Route::post('shipments/{shipment}/charges/seed-pickup-fee', [AdminShipmentChargesController::class, 'seedPickupFee'])->name('shipments.charges.seed-pickup-fee');
        Route::put('shipments/{shipment}/charges/{charge}', [AdminShipmentChargesController::class, 'update'])->name('shipments.charges.update');
        Route::patch('shipments/{shipment}/charges/{charge}/mark-paid', [AdminShipmentChargesController::class, 'markPaid'])->name('shipments.charges.mark-paid');
        Route::patch('shipments/{shipment}/charges/{charge}/waive', [AdminShipmentChargesController::class, 'waive'])->name('shipments.charges.waive');
        Route::delete('shipments/{shipment}/charges/{charge}', [AdminShipmentChargesController::class, 'cancel'])->name('shipments.charges.cancel');
        Route::get('shipments/{shipment}/custody-data', [ShipmentController::class, 'custodyData'])->name('shipments.custody-data');
        Route::post('shipments/create-run-from-claims', [ShipmentController::class, 'createRunFromClaims'])->name('shipments.create-run-from-claims');
        Route::post('shipments/{shipment}/fulfillment-type', [ShipmentController::class, 'updateFulfillmentType'])->name('shipments.update-fulfillment-type');
        Route::get('shipments-export', [ShipmentController::class, 'export'])->name('shipments.export');

        // Shipment Payments (Phase 4)
        Route::get('shipments/{shipment}/payments-data', [ShipmentPaymentController::class, 'data'])->name('shipments.payments.data');
        Route::post('shipments/{shipment}/payments', [ShipmentPaymentController::class, 'store'])->name('shipments.payments.store');
        Route::delete('payments/{payment}', [ShipmentPaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('payments/{payment}/download', [ShipmentPaymentController::class, 'download'])->name('payments.download');
        Route::get('payments/{payment}/print', [ShipmentPaymentController::class, 'print'])->name('payments.print');

        // Pickup Assignments Page
        Route::get('pickups', [PickupAssignmentController::class, 'index'])->name('pickups.index');
        Route::get('pickups-data', [PickupAssignmentController::class, 'data'])->name('pickups.data');

        // Pickup Assignment Management
        Route::get('available-drivers', [PickupAssignmentController::class, 'availableDrivers'])->name('assignments.available-drivers');
        Route::get('available-warehouses', [PickupAssignmentController::class, 'availableWarehouses'])->name('assignments.available-warehouses');
        Route::post('shipments/{shipment}/assign-driver', [PickupAssignmentController::class, 'assign'])->name('assignments.assign');
        Route::put('assignments/{pickupAssignment}/update', [PickupAssignmentController::class, 'update'])->name('assignments.update');
        Route::post('assignments/{pickupAssignment}/cancel', [PickupAssignmentController::class, 'cancel'])->name('assignments.cancel');
        Route::post('assignments/{pickupAssignment}/receive', [PickupAssignmentController::class, 'receive'])->name('assignments.receive');

        // Rider & Driver Management
        Route::get('riders-drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('riders-drivers-data', [DriverController::class, 'data'])->name('drivers.data');
        Route::post('riders-drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('riders-drivers/{driver}', [DriverController::class, 'showPage'])->name('drivers.show');
        Route::get('riders-drivers/{driver}/json', [DriverController::class, 'show'])->name('drivers.show.json');
        Route::get('riders-drivers/{driver}/packages-data', [DriverController::class, 'packagesData'])->name('drivers.packages-data');
        Route::put('riders-drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('riders-drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
        Route::patch('riders-drivers/{driver}/toggle-active', [DriverController::class, 'toggleActive'])->name('drivers.toggle-active');
        Route::get('riders-drivers-export', [DriverController::class, 'export'])->name('drivers.export');
        Route::get('riders-drivers/{driver}/assignments', [DriverController::class, 'assignments'])->name('drivers.assignments');
        Route::get('riders-drivers/{driver}/activity-logs', [DriverController::class, 'activityLogs'])->name('drivers.activity-logs');
        Route::get('riders-drivers/{driver}/transport-manifests', [DriverController::class, 'transportManifests'])->name('drivers.transport-manifests');
        Route::get('riders-drivers/{driver}/delivery-runs', [DriverController::class, 'deliveryRuns'])->name('drivers.delivery-runs');

        // Rider Teams & Handovers
        Route::get('rider-teams', [RiderTeamController::class, 'index'])->name('rider-teams.index');
        Route::get('rider-teams-data', [RiderTeamController::class, 'data'])->name('rider-teams.data');
        Route::post('rider-teams', [RiderTeamController::class, 'store'])->name('rider-teams.store');
        Route::put('rider-teams/{team}', [RiderTeamController::class, 'update'])->name('rider-teams.update');
        Route::get('rider-teams/{team}/members', [RiderTeamController::class, 'members'])->name('rider-teams.members');
        Route::post('rider-teams/{team}/members/lookup', [RiderTeamController::class, 'lookupRider'])->name('rider-teams.members.lookup');
        Route::post('rider-teams/{team}/members', [RiderTeamController::class, 'addMember'])->name('rider-teams.members.store');
        Route::post('rider-teams/{team}/members/{driver}/leader', [RiderTeamController::class, 'makeLeader'])->name('rider-teams.members.leader.store');
        Route::delete('rider-teams/{team}/members/{driver}/leader', [RiderTeamController::class, 'removeLeader'])->name('rider-teams.members.leader.destroy');
        Route::delete('rider-teams/{team}/members/{driver}', [RiderTeamController::class, 'removeMember'])->name('rider-teams.members.destroy');
        Route::get('rider-team-handovers-data', [RiderTeamController::class, 'handoversData'])->name('rider-teams.handovers.data');
        Route::post('rider-team-handovers', [RiderTeamController::class, 'storeHandover'])->name('rider-teams.handovers.store');
        Route::get('rider-team-handovers/{handover}', [RiderTeamController::class, 'showHandover'])->name('rider-teams.handovers.show');
        Route::post('rider-team-handovers/{handover}/labels', [RiderTeamController::class, 'assignLabels'])->name('rider-teams.handovers.labels.store');
        Route::post('rider-team-handovers/{handover}/labels/release', [RiderTeamController::class, 'releaseLabels'])->name('rider-teams.handovers.labels.release');
        Route::post('rider-team-handovers/{handover}/recall', [RiderTeamController::class, 'recallLabels'])->name('rider-teams.handovers.recall');
        Route::get('rider-team-handovers/{handover}/print', [RiderTeamController::class, 'printHandover'])->name('rider-teams.handovers.print');

        Route::get('drivers', [DriverController::class, 'redirectLegacyIndex']);
        Route::get('drivers-data', [DriverController::class, 'data']);
        Route::post('drivers', [DriverController::class, 'store']);
        Route::get('drivers-export', [DriverController::class, 'export']);
        Route::get('drivers/{driver}', [DriverController::class, 'redirectLegacyShow']);
        Route::get('drivers/{driver}/json', [DriverController::class, 'show']);
        Route::get('drivers/{driver}/packages-data', [DriverController::class, 'packagesData']);
        Route::put('drivers/{driver}', [DriverController::class, 'update']);
        Route::delete('drivers/{driver}', [DriverController::class, 'destroy']);
        Route::patch('drivers/{driver}/toggle-active', [DriverController::class, 'toggleActive']);
        Route::get('drivers/{driver}/assignments', [DriverController::class, 'assignments']);
        Route::get('drivers/{driver}/activity-logs', [DriverController::class, 'activityLogs']);
        Route::get('drivers/{driver}/transport-manifests', [DriverController::class, 'transportManifests']);
        Route::get('drivers/{driver}/delivery-runs', [DriverController::class, 'deliveryRuns']);

        // Warehouse Management
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('warehouses-data', [WarehouseController::class, 'data'])->name('warehouses.data');
        Route::get('warehouses-regions', [WarehouseController::class, 'regions'])->name('warehouses.regions');
        Route::get('warehouses-regions/{region}/districts', [WarehouseController::class, 'districts'])->name('warehouses.districts');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'showPage'])->name('warehouses.show');
        Route::get('warehouses/{warehouse}/json', [WarehouseController::class, 'show'])->name('warehouses.show.json');
        Route::get('warehouses/{warehouse}/users-data', [WarehouseController::class, 'usersData'])->name('warehouses.users.data');
        Route::get('warehouses/{warehouse}/users-export', [WarehouseController::class, 'usersExport'])->name('warehouses.users.export');
        Route::get('warehouses/{warehouse}/capabilities', [WarehouseCapabilityController::class, 'index'])->name('warehouses.capabilities.index');
        Route::put('warehouses/{warehouse}/capabilities', [WarehouseCapabilityController::class, 'update'])->name('warehouses.capabilities.update');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
        Route::patch('warehouses/{warehouse}/toggle-active', [WarehouseController::class, 'toggleActive'])->name('warehouses.toggle-active');
        Route::get('warehouses-export', [WarehouseController::class, 'export'])->name('warehouses.export');

        // Location Management
        Route::get('locations', [AdminLocationController::class, 'index'])->name('locations.index');
        Route::get('locations-data/regions', [AdminLocationController::class, 'regionsData'])->name('locations.regions.data');
        Route::post('locations/regions', [AdminLocationController::class, 'storeRegion'])->name('locations.regions.store');
        Route::put('locations/regions/{region}', [AdminLocationController::class, 'updateRegion'])->name('locations.regions.update');
        Route::patch('locations/regions/{region}/toggle', [AdminLocationController::class, 'toggleRegion'])->name('locations.regions.toggle');
        Route::delete('locations/regions/{region}', [AdminLocationController::class, 'destroyRegion'])->name('locations.regions.destroy');
        Route::get('locations-data/districts', [AdminLocationController::class, 'districtsData'])->name('locations.districts.data');
        Route::post('locations/districts', [AdminLocationController::class, 'storeDistrict'])->name('locations.districts.store');
        Route::put('locations/districts/{district}', [AdminLocationController::class, 'updateDistrict'])->name('locations.districts.update');
        Route::patch('locations/districts/{district}/toggle', [AdminLocationController::class, 'toggleDistrict'])->name('locations.districts.toggle');
        Route::delete('locations/districts/{district}', [AdminLocationController::class, 'destroyDistrict'])->name('locations.districts.destroy');
        Route::get('locations-data/towns', [AdminLocationController::class, 'townsData'])->name('locations.towns.data');
        Route::post('locations/towns', [AdminLocationController::class, 'storeTown'])->name('locations.towns.store');
        Route::put('locations/towns/{town}', [AdminLocationController::class, 'updateTown'])->name('locations.towns.update');
        Route::patch('locations/towns/{town}/toggle', [AdminLocationController::class, 'toggleTown'])->name('locations.towns.toggle');
        Route::delete('locations/towns/{town}', [AdminLocationController::class, 'destroyTown'])->name('locations.towns.destroy');

        // Contact Queue (admin-side)
        Route::get('contacts', [AdminContactQueueController::class, 'index'])->name('contacts.index');
        Route::get('contacts-data', [AdminContactQueueController::class, 'data'])->name('contacts.data');
        Route::post('contacts/{task}/assign', [AdminContactQueueController::class, 'assign'])->name('contacts.assign');
        Route::post('contacts/auto-assign', [AdminContactQueueController::class, 'autoAssign'])->name('contacts.auto-assign');
        Route::post('contacts/{task}/log-call', [AdminContactQueueController::class, 'logCall'])->name('contacts.log-call');
        Route::post('contacts/{task}/send-code', [AdminContactQueueController::class, 'sendCode'])->name('contacts.send-code');
        Route::post('contacts/{task}/resolve', [AdminContactQueueController::class, 'resolve'])->name('contacts.resolve');
        Route::get('contacts/{task}/attempts', [AdminContactQueueController::class, 'attempts'])->name('contacts.attempts');
        Route::get('contacts/worker-stats', [AdminContactQueueController::class, 'workerStats'])->name('contacts.worker-stats');
        Route::post('contacts/add-to-queue', [AdminContactQueueController::class, 'addToQueue'])->name('contacts.add-to-queue');

        // Recipient Payments
        Route::get('recipient-payments', [RecipientPaymentController::class, 'index'])->name('recipient-payments.index');
        Route::get('recipient-payments/reports', [RecipientPaymentController::class, 'reports'])->name('recipient-payments.reports');
        Route::get('recipient-payments/reports-data', [RecipientPaymentController::class, 'reportsData'])->name('recipient-payments.reports.data');
        Route::get('recipient-payments/reports-export', [RecipientPaymentController::class, 'reportsExport'])->name('recipient-payments.reports.export');
        Route::get('recipient-payments-data', [RecipientPaymentController::class, 'data'])->name('recipient-payments.data');
        Route::get('recipient-payments-data-export', [RecipientPaymentController::class, 'dataExport'])->name('recipient-payments.data.export');
        Route::get('recipient-payments/locations/search', [RecipientPaymentController::class, 'locationSearch'])->name('recipient-payments.locations.search');
        Route::get('recipient-payments/wallets', [RecipientPaymentController::class, 'wallets'])->name('recipient-payments.wallets');
        Route::get('recipient-payments/wallets-export', [RecipientPaymentController::class, 'walletsExport'])->name('recipient-payments.wallets.export');
        Route::post('recipient-payments/wallets', [RecipientPaymentController::class, 'storeWallet'])->name('recipient-payments.wallets.store');
        Route::post('recipient-payments/wallets/{wallet}/update', [RecipientPaymentController::class, 'updateWallet'])->name('recipient-payments.wallets.update');
        Route::post('recipient-payments/wallets/{wallet}/delete', [RecipientPaymentController::class, 'deleteWallet'])->name('recipient-payments.wallets.delete');
        Route::post('recipient-payments/wallets/{wallet}/status', [RecipientPaymentController::class, 'updateWalletStatus'])->name('recipient-payments.wallets.status');
        Route::get('recipient-payments/sessions', [RecipientPaymentController::class, 'sessions'])->name('recipient-payments.sessions');
        Route::get('recipient-payments/sessions-export', [RecipientPaymentController::class, 'sessionsExport'])->name('recipient-payments.sessions.export');
        Route::post('recipient-payments/sessions', [RecipientPaymentController::class, 'startSession'])->name('recipient-payments.sessions.store');
        Route::post('recipient-payments/sessions/{session}/close', [RecipientPaymentController::class, 'closeSession'])->name('recipient-payments.sessions.close');
        Route::post('recipient-payments/scan', [RecipientPaymentController::class, 'scan'])->name('recipient-payments.scan');
        Route::post('recipient-payments/bulk-assign', [RecipientPaymentController::class, 'bulkAssign'])->name('recipient-payments.bulk-assign');
        Route::post('recipient-payments/groups/log-call', [RecipientPaymentController::class, 'logGroupCall'])->name('recipient-payments.groups.log-call');
        Route::post('recipient-payments/groups/update-details', [RecipientPaymentController::class, 'updateGroupDetails'])->name('recipient-payments.groups.update-details');
        Route::post('recipient-payments/groups/mark-paid', [RecipientPaymentController::class, 'markGroupPaid'])->name('recipient-payments.groups.mark-paid');
        Route::post('recipient-payments/{task}/assign', [RecipientPaymentController::class, 'assign'])->name('recipient-payments.assign');
        Route::post('recipient-payments/{task}/release', [RecipientPaymentController::class, 'release'])->name('recipient-payments.release');
        Route::post('recipient-payments/{task}/log-call', [RecipientPaymentController::class, 'logCall'])->name('recipient-payments.log-call');
        Route::post('recipient-payments/{task}/fee', [RecipientPaymentController::class, 'setFee'])->name('recipient-payments.fee');
        Route::post('recipient-payments/{task}/mark-paid', [RecipientPaymentController::class, 'markPaid'])->name('recipient-payments.mark-paid');
        Route::post('recipient-payments/{task}/override', [RecipientPaymentController::class, 'override'])->name('recipient-payments.override');

        // Delivery Runs (admin read visibility)
        Route::get('delivery-runs', [AdminDeliveryRunController::class, 'index'])->name('delivery-runs.index');
        Route::get('delivery-runs-data', [AdminDeliveryRunController::class, 'data'])->name('delivery-runs.data');
        Route::get('delivery-runs-export', [AdminDeliveryRunController::class, 'export'])->name('delivery-runs.export');
        Route::post('delivery-runs', [AdminDeliveryRunController::class, 'store'])->name('delivery-runs.store');
        Route::post('delivery-runs/from-items', [AdminDeliveryRunController::class, 'storeFromItems'])->name('delivery-runs.store-from-items');
        Route::post('delivery-runs/{run}/assign-driver', [AdminDeliveryRunController::class, 'assignDriver'])->name('delivery-runs.assign-driver');
        Route::post('delivery-runs/{run}/dispatch', [AdminDeliveryRunController::class, 'dispatch'])->name('delivery-runs.dispatch');
        Route::post('delivery-runs/{run}/stops/{stop}/resend-code', [AdminDeliveryRunController::class, 'resendCode'])->name('delivery-runs.stops.resend-code');
        Route::patch('delivery-runs/{run}/stops/{stop}/delivery-method', [AdminDeliveryRunController::class, 'updateStopDeliveryMethod'])->name('delivery-runs.stops.update-delivery-method');
        Route::post('delivery-runs/{run}/stops/{stop}/confirm-handoff', [AdminDeliveryRunController::class, 'confirmHandoffStop'])->name('delivery-runs.stops.confirm-handoff');
        Route::post('delivery-runs/{run}/stops/{stop}/items/{item}/confirm-handoff', [AdminDeliveryRunController::class, 'confirmHandoffItem'])->name('delivery-runs.stops.items.confirm-handoff');
        Route::get('delivery-runs/{run}', [AdminDeliveryRunController::class, 'show'])->name('delivery-runs.show');

        // Transport Manifests (admin read + action capabilities)
        Route::get('transport-manifests', [AdminTransportManifestController::class, 'index'])->name('transport-manifests.index');
        Route::get('transport-manifests-data', [AdminTransportManifestController::class, 'data'])->name('transport-manifests.data');
        Route::get('transport-manifests-export', [AdminTransportManifestController::class, 'export'])->name('transport-manifests.export');
        Route::post('transport-manifests', [AdminTransportManifestController::class, 'store'])->name('transport-manifests.store');
        Route::get('transport-manifests/incoming', [AdminTransportManifestController::class, 'incomingIndex'])->name('transport-manifests.incoming.index');
        Route::get('transport-manifests/incoming-data', [AdminTransportManifestController::class, 'incomingData'])->name('transport-manifests.incoming.data');
        Route::get('transport-manifests/incoming/{manifest}', [AdminTransportManifestController::class, 'incomingShow'])->name('transport-manifests.incoming.show');
        Route::post('transport-manifests/incoming/{manifest}/items/{shipmentItem}/scan-receive', [AdminTransportManifestController::class, 'scanIncomingItem'])->name('transport-manifests.incoming.items.scan');
        Route::post('transport-manifests/incoming/{manifest}/finalize-receipt', [AdminTransportManifestController::class, 'finalizeIncoming'])->name('transport-manifests.incoming.finalize');
        Route::get('transport-manifests/{manifest}', [AdminTransportManifestController::class, 'show'])->name('transport-manifests.show');
        Route::delete('transport-manifests/{manifest}', [AdminTransportManifestController::class, 'destroy'])->name('transport-manifests.destroy');
        Route::post('transport-manifests/{manifest}/assign-driver', [AdminTransportManifestController::class, 'assignDriver'])->name('transport-manifests.assign-driver');
        Route::post('transport-manifests/{manifest}/unassign-driver', [AdminTransportManifestController::class, 'unassignDriver'])->name('transport-manifests.unassign-driver');
        Route::post('transport-manifests/{manifest}/dispatch', [AdminTransportManifestController::class, 'dispatch'])->name('transport-manifests.dispatch');
        Route::post('transport-manifests/{manifest}/undo-dispatch', [AdminTransportManifestController::class, 'undoDispatch'])->name('transport-manifests.undo-dispatch');
        Route::post('transport-manifests/{manifest}/print-waybill', [AdminTransportManifestController::class, 'printWaybill'])->name('transport-manifests.print-waybill');
        Route::post('transport-manifests/{manifest}/items/{item}/mark-loaded', [AdminTransportManifestController::class, 'markItemLoaded'])->name('transport-manifests.items.mark-loaded');
        Route::post('transport-manifests/{manifest}/items/{item}/mark-not-loaded', [AdminTransportManifestController::class, 'markItemNotLoaded'])->name('transport-manifests.items.mark-not-loaded');
        Route::post('transport-manifests/{manifest}/items/{item}/move-container', [AdminTransportManifestController::class, 'moveItemToContainer'])->name('transport-manifests.items.move-container');
        Route::post('transport-manifests/{manifest}/containers', [AdminTransportManifestController::class, 'createContainer'])->name('transport-manifests.containers.store');
        Route::get('transport-manifests/{manifest}/containers/{container}/items-data', [AdminTransportManifestController::class, 'containerItemsData'])->name('transport-manifests.containers.items-data');
        Route::post('transport-manifests/{manifest}/containers/{container}/notes', [AdminTransportManifestController::class, 'updateContainerNotes'])->name('transport-manifests.containers.notes');
        Route::post('transport-manifests/{manifest}/containers/{container}/mark-loaded', [AdminTransportManifestController::class, 'markContainerLoaded'])->name('transport-manifests.containers.mark-loaded');
        Route::post('transport-manifests/{manifest}/containers/{container}/mark-not-loaded', [AdminTransportManifestController::class, 'markContainerNotLoaded'])->name('transport-manifests.containers.mark-not-loaded');
        Route::post('transport-manifests/{manifest}/containers/{container}/print-label', [AdminTransportManifestController::class, 'printContainerLabel'])->name('transport-manifests.containers.print-label');
        Route::delete('transport-manifests/{manifest}/containers/{container}', [AdminTransportManifestController::class, 'deleteContainer'])->name('transport-manifests.containers.destroy');
        Route::post('transport-manifests/{manifest}/scan-issues/{exception}/approve', [AdminTransportManifestController::class, 'approveScanIssue'])->name('transport-manifests.scan-issues.approve');
        Route::post('transport-manifests/{manifest}/scan-issues/{exception}/reject', [AdminTransportManifestController::class, 'rejectScanIssue'])->name('transport-manifests.scan-issues.reject');
        Route::post('transport-manifests/{manifest}/mark-all-loaded', [AdminTransportManifestController::class, 'markAllLoaded'])->name('transport-manifests.mark-all-loaded');
        Route::post('transport-manifests/{manifest}/mark-all-not-loaded', [AdminTransportManifestController::class, 'markAllNotLoaded'])->name('transport-manifests.mark-all-not-loaded');
        Route::post('transport-manifests/{manifest}/mark-arrived', [AdminTransportManifestController::class, 'markArrived'])->name('transport-manifests.mark-arrived');
        Route::post('transport-manifests/{manifest}/undo-arrival', [AdminTransportManifestController::class, 'undoArrival'])->name('transport-manifests.undo-arrival');

        // Sort Batches (admin read visibility)
        Route::get('package-tracking', [\App\Http\Controllers\Admin\PackageTrackingController::class, 'index'])->name('package-tracking.index');
        Route::get('package-tracking-data', [\App\Http\Controllers\Admin\PackageTrackingController::class, 'data'])->name('package-tracking.data');

        Route::get('sort-batches', [AdminSortBatchController::class, 'index'])->name('sort-batches.index');
        Route::get('sort-batches/create', [AdminSortBatchController::class, 'create'])->name('sort-batches.create');
        Route::get('sort-batches-data', [AdminSortBatchController::class, 'data'])->name('sort-batches.data');
        Route::get('sort-batches-export', [AdminSortBatchController::class, 'export'])->name('sort-batches.export');
        Route::post('sort-batches', [AdminSortBatchController::class, 'store'])->name('sort-batches.store');
        Route::get('sort-batches/{batch}', [AdminSortBatchController::class, 'show'])->name('sort-batches.show');
        Route::get('sort-batches/{batch}/items-data', [AdminSortBatchController::class, 'itemsData'])->name('sort-batches.items-data');
        Route::get('sort-batches/{batch}/eligible-items', [AdminSortBatchController::class, 'eligibleItemsData'])->name('sort-batches.eligible-items');
        Route::post('sort-batches/{batch}/add-items', [AdminSortBatchController::class, 'addItems'])->name('sort-batches.add-items');
        Route::delete('sort-batches/{batch}/items/{shipmentItem}', [AdminSortBatchController::class, 'removeItem'])->name('sort-batches.remove-item');
        Route::post('sort-batches/{batch}/seal', [AdminSortBatchController::class, 'seal'])->name('sort-batches.seal');
        Route::post('sort-batches/{batch}/reopen', [AdminSortBatchController::class, 'reopen'])->name('sort-batches.reopen');
        Route::delete('sort-batches/{batch}', [AdminSortBatchController::class, 'destroy'])->name('sort-batches.destroy');
        Route::post('sort-batches/{batch}/create-transport-manifest', [AdminSortBatchController::class, 'createTransportManifest'])->name('sort-batches.create-transport-manifest');
        Route::post('sort-batches/{batch}/create-delivery-run', [AdminSortBatchController::class, 'createDeliveryRun'])->name('sort-batches.create-delivery-run');

        // Collection Center (self-pickup)
        Route::get('collection-center', [CollectionCenterController::class, 'index'])->name('collection-center.index');
        Route::get('collection-center-data', [CollectionCenterController::class, 'data'])->name('collection-center.data');
        Route::post('collection-center/{shipment}/handover', [CollectionCenterController::class, 'handover'])->name('collection-center.handover');

        // Notification Logs
        Route::get('notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
        Route::get('notifications-data', [AdminNotificationController::class, 'data'])->name('notifications.data');
        Route::post('notifications/{notification}/mark-read', [AdminNotificationController::class, 'markRead'])->name('notifications.mark-read');

        // Marketing Broadcasts
        Route::get('marketing', [AdminMarketingController::class, 'index'])->name('marketing.index');
        Route::get('marketing/data', [AdminMarketingController::class, 'data'])->name('marketing.data');
        Route::get('marketing/email-templates', [AdminMarketingController::class, 'emailTemplates'])->name('marketing.email-templates');
        Route::post('marketing/send', [AdminMarketingController::class, 'send'])->name('marketing.send');

        // Settings Management
        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('settings/save', [SettingsController::class, 'save'])->name('settings.save');
        Route::post('settings/upload', [SettingsController::class, 'uploadFile'])->name('settings.upload');
        Route::post('settings/pickup-vehicles', [SettingsController::class, 'storePickupVehicle'])->name('settings.pickup-vehicles.store');
        Route::put('settings/pickup-vehicles/{pickupVehicleType}', [SettingsController::class, 'updatePickupVehicle'])->name('settings.pickup-vehicles.update');
        Route::patch('settings/pickup-vehicles/{pickupVehicleType}/toggle', [SettingsController::class, 'togglePickupVehicle'])->name('settings.pickup-vehicles.toggle');
        Route::delete('settings/pickup-vehicles/{pickupVehicleType}', [SettingsController::class, 'deletePickupVehicle'])->name('settings.pickup-vehicles.delete');
        Route::post('settings/bus-stations', [SettingsController::class, 'storeBusStation'])->name('settings.bus-stations.store');
        Route::put('settings/bus-stations/{busStation}', [SettingsController::class, 'updateBusStation'])->name('settings.bus-stations.update');
        Route::patch('settings/bus-stations/{busStation}/toggle', [SettingsController::class, 'toggleBusStation'])->name('settings.bus-stations.toggle');
        Route::delete('settings/bus-stations/{busStation}', [SettingsController::class, 'deleteBusStation'])->name('settings.bus-stations.delete');
        Route::post('settings/delivery-failure-reasons', [SettingsController::class, 'storeDeliveryFailureReason'])->name('settings.delivery-failure-reasons.store');
        Route::put('settings/delivery-failure-reasons/{deliveryFailureReason}', [SettingsController::class, 'updateDeliveryFailureReason'])->name('settings.delivery-failure-reasons.update');
        Route::patch('settings/delivery-failure-reasons/{deliveryFailureReason}/toggle', [SettingsController::class, 'toggleDeliveryFailureReason'])->name('settings.delivery-failure-reasons.toggle');
        Route::delete('settings/delivery-failure-reasons/{deliveryFailureReason}', [SettingsController::class, 'deleteDeliveryFailureReason'])->name('settings.delivery-failure-reasons.delete');
        Route::post('settings/delivery-delay-reasons', [SettingsController::class, 'storeDeliveryDelayReason'])->name('settings.delivery-delay-reasons.store');
        Route::put('settings/delivery-delay-reasons/{deliveryDelayReason}', [SettingsController::class, 'updateDeliveryDelayReason'])->name('settings.delivery-delay-reasons.update');
        Route::patch('settings/delivery-delay-reasons/{deliveryDelayReason}/toggle', [SettingsController::class, 'toggleDeliveryDelayReason'])->name('settings.delivery-delay-reasons.toggle');
        Route::delete('settings/delivery-delay-reasons/{deliveryDelayReason}', [SettingsController::class, 'deleteDeliveryDelayReason'])->name('settings.delivery-delay-reasons.delete');
        Route::post('settings/email-templates', [SettingsController::class, 'storeEmailTemplate'])->name('settings.email-templates.store');
        Route::put('settings/email-templates/{emailTemplate}', [SettingsController::class, 'updateEmailTemplate'])->name('settings.email-templates.update');
        Route::patch('settings/email-templates/{emailTemplate}/toggle', [SettingsController::class, 'toggleEmailTemplate'])->name('settings.email-templates.toggle');
        Route::get('settings/email-templates/{emailTemplate}/preview', [SettingsController::class, 'previewEmailTemplate'])->name('settings.email-templates.preview');
        Route::get('settings/logs-data', [SettingsController::class, 'logsData'])->name('settings.logs.data');
        Route::get('settings/logs/{index}', [SettingsController::class, 'logDetail'])->name('settings.logs.detail');
        Route::delete('settings/logs', [SettingsController::class, 'clearLogs'])->name('settings.logs.clear');
        Route::get('settings/logs-export', [SettingsController::class, 'exportLogs'])->name('settings.logs.export');
        Route::get('settings/email-logs-data', [SettingsController::class, 'emailLogsData'])->name('settings.email-logs.data');
        Route::get('settings/sms-logs-data', [SettingsController::class, 'smsLogsData'])->name('settings.sms-logs.data');
        Route::get('settings/otp-logs-data', [SettingsController::class, 'otpLogsData'])->name('settings.otp-logs.data');
        Route::get('settings/admin-audit-logs-data', [SettingsController::class, 'adminAuditLogsData'])->name('settings.admin-audit-logs.data');
        Route::get('settings/admin-audit-logs-export', [SettingsController::class, 'adminAuditLogsExport'])->name('settings.admin-audit-logs.export');
        Route::post('settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
        Route::post('settings/test-sms', [SettingsController::class, 'testSms'])->name('settings.test-sms');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
        Route::post('settings/upload-firebase-credentials', [SettingsController::class, 'uploadFirebaseCredentials'])->name('settings.upload-firebase-credentials');
        Route::post('settings/test-push-notification', [SettingsController::class, 'testPushNotification'])->name('settings.test-push');

        // Admin FCM token (for web push)
        Route::post('fcm-token', function (\Illuminate\Http\Request $request) {
            $request->validate(['fcm_token' => 'required|string|max:512']);
            \Illuminate\Support\Facades\Auth::guard('admin')->user()->update(['fcm_token' => $request->fcm_token]);

            return response()->json(['success' => true]);
        })->name('fcm-token');
    });
});

/*
|--------------------------------------------------------------------------
| Warehouse Web Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix(config('backoffice.prefix', 'admin').'/operations')
    ->name('warehouse.')
    ->middleware(['auth:admin', 'admin.audit', 'backoffice.user'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'))->name('dashboard');

        // Walk-in Vendor Shipments
        Route::get('walkin', [WarehouseWalkinController::class, 'create'])->name('walkin.create');
        Route::post('walkin', [WarehouseWalkinController::class, 'store'])->name('walkin.store');
        Route::delete('walkin/{id}', [WarehouseWalkinController::class, 'destroy'])->name('walkin.destroy');
        Route::post('walkin/print-labels', [WarehouseWalkinController::class, 'printLabels'])->name('walkin.print-labels');
        Route::post('walkin/items/{shipmentItem}/print-label', [WarehouseWalkinController::class, 'printLabel'])->name('walkin.items.print-label');
        Route::get('walkin/vendor-lookup', [WarehouseWalkinController::class, 'vendorLookup'])->name('walkin.vendor-lookup');
        Route::post('walkin/vendor-create', [WarehouseWalkinController::class, 'vendorCreate'])->name('walkin.vendor-create');
        Route::get('locations/search', [WarehouseWalkinController::class, 'locationSearch'])->name('locations.search');

        // Collections (self-pickup)
        Route::get('collections', [WarehouseCollectionController::class, 'index'])->name('collections.index');
        Route::get('collections-data', [WarehouseCollectionController::class, 'data'])->name('collections.data');
        Route::post('collections/{shipment}/handover', [WarehouseCollectionController::class, 'handover'])->name('collections.handover');

        // Warehouse Users
        Route::get('users', [WarehouseUserController::class, 'index'])->name('users.index');
        Route::get('users-data', [WarehouseUserController::class, 'data'])->name('users.data');
        Route::get('users-export', [WarehouseUserController::class, 'export'])->name('users.export');
        Route::get('users/{user}', [WarehouseUserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/overview-data', [WarehouseUserController::class, 'overviewData'])->name('users.overview-data');
        Route::get('users/{user}/orders-data', [WarehouseUserController::class, 'ordersData'])->name('users.orders-data');
        Route::get('users/{user}/incoming-packages-data', [WarehouseUserController::class, 'incomingPackagesData'])->name('users.incoming-packages-data');
        Route::get('users/{user}/warehouse-packages-data', [WarehouseUserController::class, 'warehousePackagesData'])->name('users.warehouse-packages-data');
        Route::get('users/{user}/sorting-data', [WarehouseUserController::class, 'sortingData'])->name('users.sorting-data');
        Route::get('users/{user}/recipient-desk-data', [WarehouseUserController::class, 'recipientDeskData'])->name('users.recipient-desk-data');
        Route::get('users/{user}/recipient-payments-data', [WarehouseUserController::class, 'recipientPaymentsData'])->name('users.recipient-payments-data');
        Route::get('users/{user}/transport-manifests-data', [WarehouseUserController::class, 'transportManifestsData'])->name('users.transport-manifests-data');
        Route::get('users/{user}/incoming-manifests-data', [WarehouseUserController::class, 'incomingManifestsData'])->name('users.incoming-manifests-data');
        Route::get('users/{user}/delivery-runs-data', [WarehouseUserController::class, 'deliveryRunsData'])->name('users.delivery-runs-data');
        Route::get('users/{user}/pending-confirmations-data', [WarehouseUserController::class, 'pendingConfirmationsData'])->name('users.pending-confirmations-data');
        Route::get('users/{user}/team-actions-data', [WarehouseUserController::class, 'teamActionsData'])->name('users.team-actions-data');
        Route::get('users/{user}/hq-controls-data', [WarehouseUserController::class, 'hqControlsData'])->name('users.hq-controls-data');
        Route::get('users/{user}/security-log-data', [WarehouseUserController::class, 'securityLogData'])->name('users.security-log-data');
        Route::get('users/{user}/audit-logs-data', [WarehouseUserController::class, 'auditLogsData'])->name('users.audit-logs-data');
        Route::post('users', [WarehouseUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [WarehouseUserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/toggle-active', [WarehouseUserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('users/{user}/impersonate', [ImpersonationController::class, 'start'])->name('users.impersonate');

        // Receipts / Pickups / Items
        Route::get('receipts/pending', [WarehouseReceiptController::class, 'pendingIndex'])->name('receipts.pending.index');
        Route::get('receipts/pending-data', [WarehouseReceiptController::class, 'pendingData'])->name('receipts.pending.data');
        Route::get('receipts/pending/{pickupAssignment}', [WarehouseReceiptController::class, 'pendingShow'])->name('receipts.pending.show');
        Route::post('receipts/pending/{pickupAssignment}/packages', [WarehouseReceiptController::class, 'addReceiptPackage'])->name('receipts.pending.packages.add');
        Route::post('receipts/pending/{pickupAssignment}/auto-group-by-phone', [WarehouseReceiptController::class, 'autoGroupReceiptPackagesByPhone'])->name('receipts.pending.auto-group-by-phone');
        Route::post('receipts/pending/{pickupAssignment}/shared-destination', [WarehouseReceiptController::class, 'saveSharedDestination'])->name('receipts.pending.shared-destination');
        Route::post('receipts/pending/{pickupAssignment}/items/{shipmentItem}/split', [WarehouseReceiptController::class, 'splitReceiptPackage'])->name('receipts.pending.items.split');
        Route::post('receipts/pending/{pickupAssignment}/items/{shipmentItem}', [WarehouseReceiptController::class, 'savePendingItem'])->name('receipts.pending.items.save');
        Route::post('receipts/pending/{pickupAssignment}/items/{shipmentItem}/print-label', [WarehouseReceiptController::class, 'printPendingItemLabel'])->name('receipts.pending.items.print-label');
        Route::post('receipts/pending/{pickupAssignment}/finalize', [WarehouseReceiptController::class, 'finalizePendingReceipt'])->name('receipts.pending.finalize');
        Route::get('pickups/received', [WarehouseReceiptController::class, 'receivedPickupsIndex'])->name('pickups.received.index');
        Route::get('pickups/received-data', [WarehouseReceiptController::class, 'receivedPickupsData'])->name('pickups.received.data');
        Route::get('pickups/received/{pickupAssignment}', [WarehouseReceiptController::class, 'receivedPickupShow'])->name('pickups.received.show');
        Route::get('bus-station-packages', [WarehousePackageController::class, 'busStationIndex'])->name('bus-station-packages.index');
        Route::get('bus-station-packages-data', [WarehousePackageController::class, 'busStationData'])->name('bus-station-packages.data');
        Route::get('package-location-changes', [WarehouseRiderPackageAuditController::class, 'locationChangesIndex'])->name('package-location-changes.index');
        Route::get('package-location-changes-data', [WarehouseRiderPackageAuditController::class, 'locationChangesData'])->name('package-location-changes.data');
        Route::get('rider-package-transfers', [WarehouseRiderPackageAuditController::class, 'transfersIndex'])->name('rider-package-transfers.index');
        Route::get('rider-package-transfers-data', [WarehouseRiderPackageAuditController::class, 'transfersData'])->name('rider-package-transfers.data');
        Route::get('packages', [WarehousePackageController::class, 'index'])->name('packages.index');
        Route::get('packages-data', [WarehousePackageController::class, 'data'])->name('packages.data');
        Route::get('packages/{warehouseReceiptItem}', [WarehousePackageController::class, 'show'])->name('packages.show');
        Route::put('packages/{warehouseReceiptItem}', [WarehousePackageController::class, 'update'])->name('packages.update');
        Route::post('packages/{warehouseReceiptItem}/sort-batch', [WarehousePackageController::class, 'moveSortBatch'])->name('packages.sort-batch');
        Route::post('packages/{warehouseReceiptItem}/print-label', [WarehousePackageController::class, 'printLabel'])->name('packages.print-label');
        Route::post('packages/{warehouseReceiptItem}/delivery-fee', [WarehousePackageController::class, 'setDeliveryFee'])->name('packages.delivery-fee');
        Route::post('packages/{warehouseReceiptItem}/mark-paid', [WarehousePackageController::class, 'markDeliveryPaid'])->name('packages.mark-paid');
        Route::post('packages/{warehouseReceiptItem}/delay-notice', [WarehousePackageController::class, 'sendDelayNotice'])->name('packages.delay-notice');
        Route::get('items/received', [WarehousePackageController::class, 'legacyIndex'])->name('items.received.index');
        Route::get('items/received-data', [WarehousePackageController::class, 'data'])->name('items.received.data');
        Route::get('items/received/{warehouseReceiptItem}', [WarehousePackageController::class, 'show'])->name('items.received.show');

        // Shipment charges ledger (warehouse)
        Route::get('shipments/{shipment}/charges', [WarehouseShipmentChargesController::class, 'index'])->name('shipments.charges.index');
        Route::post('shipments/{shipment}/charges', [WarehouseShipmentChargesController::class, 'store'])->name('shipments.charges.store');
        Route::put('shipments/{shipment}/charges/{charge}', [WarehouseShipmentChargesController::class, 'update'])->name('shipments.charges.update');
        Route::patch('shipments/{shipment}/charges/{charge}/mark-paid', [WarehouseShipmentChargesController::class, 'markPaid'])->name('shipments.charges.mark-paid');
        Route::patch('shipments/{shipment}/charges/{charge}/waive', [WarehouseShipmentChargesController::class, 'waive'])->name('shipments.charges.waive');
        Route::delete('shipments/{shipment}/charges/{charge}', [WarehouseShipmentChargesController::class, 'cancel'])->name('shipments.charges.cancel');

        // Sorting
        Route::get('sorting', [WarehouseSortingController::class, 'index'])->name('sorting.index');
        Route::get('sorting/items-data', [WarehouseSortingController::class, 'itemsData'])->name('sorting.items.data');
        Route::get('sorting/batches-data', [WarehouseSortingController::class, 'batchesData'])->name('sorting.batches.data');
        Route::get('sorting/batches-export', [WarehouseSortingController::class, 'export'])->name('sorting.batches.export');
        Route::post('sorting/batches', [WarehouseSortingController::class, 'storeBatch'])->name('sorting.batches.store');
        Route::get('sorting/batches/{sortBatch}', [WarehouseSortingController::class, 'show'])->name('sorting.show');
        Route::get('sorting/batches/{sortBatch}/items-data', [WarehouseSortingController::class, 'batchItemsData'])->name('sorting.items-data');
        Route::get('sorting/batches/{sortBatch}/eligible-items', [WarehouseSortingController::class, 'eligibleItemsData'])->name('sorting.eligible-items');
        Route::post('sorting/batches/{sortBatch}/items', [WarehouseSortingController::class, 'addItems'])->name('sorting.batches.items.store');
        Route::delete('sorting/batches/{sortBatch}/items/{shipmentItem}', [WarehouseSortingController::class, 'removeItem'])->name('sorting.batches.items.destroy');
        Route::post('sorting/batches/{sortBatch}/seal', [WarehouseSortingController::class, 'seal'])->name('sorting.batches.seal');
        Route::post('sorting/batches/{sortBatch}/reopen', [WarehouseSortingController::class, 'reopen'])->name('sorting.batches.reopen');
        Route::delete('sorting/batches/{sortBatch}', [WarehouseSortingController::class, 'destroy'])->name('sorting.destroy');
        Route::post('sorting/batches/{sortBatch}/create-transport-manifest', [WarehouseSortingController::class, 'createTransportManifest'])->name('sorting.create-transport-manifest');
        Route::post('sorting/batches/{sortBatch}/create-delivery-run', [WarehouseSortingController::class, 'createDeliveryRun'])->name('sorting.create-delivery-run');

        // Transport Manifests (Outbound + Incoming)
        Route::get('manifests/transport', [WarehouseTransportManifestController::class, 'outboundIndex'])->name('manifests.transport.index');
        Route::get('manifests/transport-data', [WarehouseTransportManifestController::class, 'outboundData'])->name('manifests.transport.data');
        Route::post('manifests/transport', [WarehouseTransportManifestController::class, 'create'])->name('manifests.transport.store');
        Route::post('manifests/transport/{manifest}/assign-driver', [WarehouseTransportManifestController::class, 'assignDriver'])->name('manifests.transport.assign-driver');
        Route::post('manifests/transport/{manifest}/dispatch', [WarehouseTransportManifestController::class, 'dispatch'])->name('manifests.transport.dispatch');
        Route::post('manifests/transport/{manifest}/undo-dispatch', [WarehouseTransportManifestController::class, 'undoDispatch'])->name('manifests.transport.undo-dispatch');
        Route::post('manifests/transport/{manifest}/print-waybill', [WarehouseTransportManifestController::class, 'printWaybill'])->name('manifests.transport.print-waybill');
        Route::post('manifests/transport/{manifest}/unassign-driver', [WarehouseTransportManifestController::class, 'unassignDriver'])->name('manifests.transport.unassign-driver');
        Route::post('manifests/transport/{manifest}/mark-all-loaded', [WarehouseTransportManifestController::class, 'markAllLoaded'])->name('manifests.transport.mark-all-loaded');
        Route::post('manifests/transport/{manifest}/mark-all-not-loaded', [WarehouseTransportManifestController::class, 'markAllNotLoaded'])->name('manifests.transport.mark-all-not-loaded');
        Route::post('manifests/transport/{manifest}/mark-arrived', [WarehouseTransportManifestController::class, 'markArrived'])->name('manifests.transport.mark-arrived');
        Route::post('manifests/transport/{manifest}/undo-arrival', [WarehouseTransportManifestController::class, 'undoArrival'])->name('manifests.transport.undo-arrival');
        Route::post('manifests/transport/{manifest}/items/{item}/mark-loaded', [WarehouseTransportManifestController::class, 'markItemLoaded'])->name('manifests.transport.items.mark-loaded');
        Route::post('manifests/transport/{manifest}/items/{item}/mark-not-loaded', [WarehouseTransportManifestController::class, 'markItemNotLoaded'])->name('manifests.transport.items.mark-not-loaded');
        Route::post('manifests/transport/{manifest}/items/{item}/move-container', [WarehouseTransportManifestController::class, 'moveItemToContainer'])->name('manifests.transport.items.move-container');
        Route::post('manifests/transport/{manifest}/containers', [WarehouseTransportManifestController::class, 'createContainer'])->name('manifests.transport.containers.store');
        Route::get('manifests/transport/{manifest}/containers/{container}/items-data', [WarehouseTransportManifestController::class, 'containerItemsData'])->name('manifests.transport.containers.items-data');
        Route::post('manifests/transport/{manifest}/containers/{container}/attach-sort-batch', [WarehouseTransportManifestController::class, 'attachSortBatchToContainer'])->name('manifests.transport.containers.attach-sort-batch');
        Route::post('manifests/transport/{manifest}/containers/{container}/notes', [WarehouseTransportManifestController::class, 'updateContainerNotes'])->name('manifests.transport.containers.notes');
        Route::post('manifests/transport/{manifest}/containers/{container}/mark-loaded', [WarehouseTransportManifestController::class, 'markContainerLoaded'])->name('manifests.transport.containers.mark-loaded');
        Route::post('manifests/transport/{manifest}/containers/{container}/mark-not-loaded', [WarehouseTransportManifestController::class, 'markContainerNotLoaded'])->name('manifests.transport.containers.mark-not-loaded');
        Route::post('manifests/transport/{manifest}/containers/{container}/print-label', [WarehouseTransportManifestController::class, 'printContainerLabel'])->name('manifests.transport.containers.print-label');
        Route::delete('manifests/transport/{manifest}/containers/{container}', [WarehouseTransportManifestController::class, 'deleteContainer'])->name('manifests.transport.containers.destroy');
        Route::post('manifests/transport/{manifest}/scan-issues/{exception}/approve', [WarehouseTransportManifestController::class, 'approveScanIssue'])->name('manifests.transport.scan-issues.approve');
        Route::post('manifests/transport/{manifest}/scan-issues/{exception}/reject', [WarehouseTransportManifestController::class, 'rejectScanIssue'])->name('manifests.transport.scan-issues.reject');
        Route::delete('manifests/transport/{manifest}', [WarehouseTransportManifestController::class, 'destroy'])->name('manifests.transport.destroy');
        Route::get('manifests/transport/{manifest}', [WarehouseTransportManifestController::class, 'outboundShow'])->name('manifests.transport.show');

        Route::get('manifests/incoming', [WarehouseTransportManifestController::class, 'incomingIndex'])->name('manifests.incoming.index');
        Route::get('manifests/incoming-data', [WarehouseTransportManifestController::class, 'incomingData'])->name('manifests.incoming.data');
        Route::post('manifests/incoming/scan', [WarehouseTransportManifestController::class, 'scanIncomingPackage'])->name('manifests.incoming.scan');
        Route::get('manifests/incoming/{manifest}', [WarehouseTransportManifestController::class, 'incomingShow'])->name('manifests.incoming.show');
        Route::post('manifests/incoming/{manifest}/items/{shipmentItem}/scan-receive', [WarehouseTransportManifestController::class, 'scanIncomingItem'])->name('manifests.incoming.items.scan');
        Route::post('manifests/incoming/{manifest}/finalize-receipt', [WarehouseTransportManifestController::class, 'finalizeIncoming'])->name('manifests.incoming.finalize');

        // Delivery Runs
        Route::get('deliveries/runs', [WarehouseDeliveryRunController::class, 'index'])->name('deliveries.runs.index');
        Route::get('deliveries/runs-data', [WarehouseDeliveryRunController::class, 'data'])->name('deliveries.runs.data');
        Route::get('deliveries/runs/eligible-items', [WarehouseDeliveryRunController::class, 'eligibleItems'])->name('deliveries.runs.eligible-items');
        Route::post('deliveries/runs', [WarehouseDeliveryRunController::class, 'store'])->name('deliveries.runs.store');
        Route::post('deliveries/runs/from-items', [WarehouseDeliveryRunController::class, 'storeFromItems'])->name('deliveries.runs.store-from-items');
        Route::get('deliveries/runs/{run}/eligible-items', [WarehouseDeliveryRunController::class, 'eligibleItemsForRun'])->name('deliveries.runs.run-eligible-items');
        Route::post('deliveries/runs/{run}/items', [WarehouseDeliveryRunController::class, 'addItems'])->name('deliveries.runs.items.store');
        Route::post('deliveries/runs/{run}/attach-sort-batch', [WarehouseDeliveryRunController::class, 'attachSortBatch'])->name('deliveries.runs.attach-sort-batch');
        Route::post('deliveries/runs/{run}/assign-driver', [WarehouseDeliveryRunController::class, 'assignDriver'])->name('deliveries.runs.assign-driver');
        Route::post('deliveries/runs/{run}/dispatch', [WarehouseDeliveryRunController::class, 'dispatch'])->name('deliveries.runs.dispatch');
        Route::post('deliveries/runs/{run}/stops/{stop}/resend-code', [WarehouseDeliveryRunController::class, 'resendCode'])->name('deliveries.runs.stops.resend-code');
        Route::patch('deliveries/runs/{run}/stops/{stop}/packages', [WarehouseDeliveryRunController::class, 'updateStopPackages'])->name('deliveries.runs.stops.update-packages');
        Route::patch('deliveries/runs/{run}/stops/{stop}/delivery-method', [WarehouseDeliveryRunController::class, 'updateStopDeliveryMethod'])->name('deliveries.runs.stops.update-delivery-method');
        Route::post('deliveries/runs/{run}/stops/{stop}/confirm-handoff', [WarehouseDeliveryRunController::class, 'adminConfirmHandoff'])->name('deliveries.runs.stops.confirm-handoff');
        Route::post('deliveries/runs/{run}/stops/{stop}/items/{item}/confirm-handoff', [WarehouseDeliveryRunController::class, 'confirmHandoffItem'])->name('deliveries.runs.stops.items.confirm-handoff');
        Route::post('deliveries/runs/{run}/items/{item}/delay-notice', [WarehouseDeliveryRunController::class, 'sendItemDelayNotice'])->name('deliveries.runs.items.delay-notice');
        Route::get('deliveries/pending-confirmations', [WarehouseDeliveryRunController::class, 'pendingConfirmations'])->name('deliveries.pending-confirmations');
        Route::get('deliveries/pending-confirmations-data', [WarehouseDeliveryRunController::class, 'pendingConfirmationsData'])->name('deliveries.pending-confirmations-data');
        Route::get('deliveries/runs/{run}', [WarehouseDeliveryRunController::class, 'show'])->name('deliveries.runs.show');

        // Contact Queue
        Route::get('contacts', [WarehouseContactQueueController::class, 'index'])->name('contacts.index');
        Route::get('contacts-data', [WarehouseContactQueueController::class, 'data'])->name('contacts.data');
        Route::post('contacts/{task}/assign', [WarehouseContactQueueController::class, 'assign'])->name('contacts.assign');
        Route::post('contacts/bulk-assign', [WarehouseContactQueueController::class, 'bulkAssign'])->name('contacts.bulk-assign');
        Route::post('contacts/auto-assign', [WarehouseContactQueueController::class, 'autoAssign'])->name('contacts.auto-assign');
        Route::post('contacts/{task}/log-call', [WarehouseContactQueueController::class, 'logCall'])->name('contacts.log-call');
        Route::post('contacts/{task}/send-code', [WarehouseContactQueueController::class, 'sendCode'])->name('contacts.send-code');
        Route::post('contacts/{task}/resolve', [WarehouseContactQueueController::class, 'resolve'])->name('contacts.resolve');
        Route::post('contacts/{task}/handover', [WarehouseContactQueueController::class, 'handover'])->name('contacts.handover');
        Route::get('contacts/{task}/attempts', [WarehouseContactQueueController::class, 'attempts'])->name('contacts.attempts');
        Route::get('contacts/worker-stats', [WarehouseContactQueueController::class, 'workerStats'])->name('contacts.worker-stats');

        // Recipient Payments
        Route::get('recipient-payments', [RecipientPaymentController::class, 'index'])->name('recipient-payments.index');
        Route::get('recipient-payments/reports', [RecipientPaymentController::class, 'reports'])->name('recipient-payments.reports');
        Route::get('recipient-payments/reports-data', [RecipientPaymentController::class, 'reportsData'])->name('recipient-payments.reports.data');
        Route::get('recipient-payments/reports-export', [RecipientPaymentController::class, 'reportsExport'])->name('recipient-payments.reports.export');
        Route::get('recipient-payments-data', [RecipientPaymentController::class, 'data'])->name('recipient-payments.data');
        Route::get('recipient-payments-data-export', [RecipientPaymentController::class, 'dataExport'])->name('recipient-payments.data.export');
        Route::get('recipient-payments/locations/search', [RecipientPaymentController::class, 'locationSearch'])->name('recipient-payments.locations.search');
        Route::get('recipient-payments/wallets', [RecipientPaymentController::class, 'wallets'])->name('recipient-payments.wallets');
        Route::get('recipient-payments/wallets-export', [RecipientPaymentController::class, 'walletsExport'])->name('recipient-payments.wallets.export');
        Route::post('recipient-payments/wallets', [RecipientPaymentController::class, 'storeWallet'])->name('recipient-payments.wallets.store');
        Route::post('recipient-payments/wallets/{wallet}/update', [RecipientPaymentController::class, 'updateWallet'])->name('recipient-payments.wallets.update');
        Route::post('recipient-payments/wallets/{wallet}/delete', [RecipientPaymentController::class, 'deleteWallet'])->name('recipient-payments.wallets.delete');
        Route::post('recipient-payments/wallets/{wallet}/status', [RecipientPaymentController::class, 'updateWalletStatus'])->name('recipient-payments.wallets.status');
        Route::get('recipient-payments/sessions', [RecipientPaymentController::class, 'sessions'])->name('recipient-payments.sessions');
        Route::get('recipient-payments/sessions-export', [RecipientPaymentController::class, 'sessionsExport'])->name('recipient-payments.sessions.export');
        Route::post('recipient-payments/sessions', [RecipientPaymentController::class, 'startSession'])->name('recipient-payments.sessions.store');
        Route::post('recipient-payments/sessions/{session}/close', [RecipientPaymentController::class, 'closeSession'])->name('recipient-payments.sessions.close');
        Route::post('recipient-payments/scan', [RecipientPaymentController::class, 'scan'])->name('recipient-payments.scan');
        Route::post('recipient-payments/bulk-assign', [RecipientPaymentController::class, 'bulkAssign'])->name('recipient-payments.bulk-assign');
        Route::post('recipient-payments/groups/log-call', [RecipientPaymentController::class, 'logGroupCall'])->name('recipient-payments.groups.log-call');
        Route::post('recipient-payments/groups/update-details', [RecipientPaymentController::class, 'updateGroupDetails'])->name('recipient-payments.groups.update-details');
        Route::post('recipient-payments/groups/mark-paid', [RecipientPaymentController::class, 'markGroupPaid'])->name('recipient-payments.groups.mark-paid');
        Route::post('recipient-payments/{task}/assign', [RecipientPaymentController::class, 'assign'])->name('recipient-payments.assign');
        Route::post('recipient-payments/{task}/release', [RecipientPaymentController::class, 'release'])->name('recipient-payments.release');
        Route::post('recipient-payments/{task}/log-call', [RecipientPaymentController::class, 'logCall'])->name('recipient-payments.log-call');
        Route::post('recipient-payments/{task}/fee', [RecipientPaymentController::class, 'setFee'])->name('recipient-payments.fee');
        Route::post('recipient-payments/{task}/mark-paid', [RecipientPaymentController::class, 'markPaid'])->name('recipient-payments.mark-paid');
        Route::post('recipient-payments/{task}/override', [RecipientPaymentController::class, 'override'])->name('recipient-payments.override');

        // Shipment Payments
        Route::get('receipts/pending/{pickupAssignment}/payments-data', [WarehouseShipmentPaymentController::class, 'data'])->name('receipts.payments.data');
        Route::post('receipts/pending/{pickupAssignment}/payments', [WarehouseShipmentPaymentController::class, 'store'])->name('receipts.payments.store');
        Route::delete('payments/{payment}', [WarehouseShipmentPaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('payments/{payment}/download', [WarehouseShipmentPaymentController::class, 'download'])->name('payments.download');
        Route::get('payments/{payment}/print', [WarehouseShipmentPaymentController::class, 'print'])->name('payments.print');
    });

Route::any('warehouse/{path?}', function (?string $path = null) {
    $target = trim(config('backoffice.prefix', 'admin').'/operations/'.ltrim((string) $path, '/'), '/');
    $query = request()->getQueryString();

    return redirect('/'.$target.($query ? '?'.$query : ''), 308);
})->where('path', '.*')->name('warehouse.compat.redirect');

/*
|--------------------------------------------------------------------------
| Developer Docs
|--------------------------------------------------------------------------
*/
Route::get('/docs/driver-flow', function () {
    return view('docs.driver-flow-guide');
})->name('docs.driver-flow');