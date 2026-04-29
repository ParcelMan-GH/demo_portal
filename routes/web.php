<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminDeliveryRunController;
use App\Http\Controllers\Admin\CollectionCenterController;
use App\Http\Controllers\Admin\AdminInvoiceListController;
use App\Http\Controllers\Admin\AdminLocationController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminSearchController;
use App\Http\Controllers\Admin\AdminSortBatchController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminTransportManifestController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PickupAssignmentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\ShipmentPaymentController;
use App\Http\Controllers\Admin\AdminMarketingController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\AdminContactQueueController;
use App\Http\Controllers\Admin\AdminShipmentChargesController;
use App\Http\Controllers\Warehouse\WarehouseShipmentChargesController;
use App\Http\Controllers\Admin\VendorPayoutController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\Warehouse\DashboardController as WarehouseDashboardController;
use App\Http\Controllers\Warehouse\DeliveryRunController as WarehouseDeliveryRunController;
use App\Http\Controllers\Warehouse\InvoiceController as WarehouseInvoiceController;
use App\Http\Controllers\Warehouse\ReceiptController as WarehouseReceiptController;
use App\Http\Controllers\Warehouse\ShipmentPaymentController as WarehouseShipmentPaymentController;
use App\Http\Controllers\Warehouse\SortingController as WarehouseSortingController;
use App\Http\Controllers\Warehouse\TransportManifestController as WarehouseTransportManifestController;
use App\Http\Controllers\Warehouse\UserController as WarehouseUserController;
use App\Http\Controllers\Warehouse\CollectionController as WarehouseCollectionController;
use App\Http\Controllers\Warehouse\ContactQueueController as WarehouseContactQueueController;
use App\Http\Controllers\Warehouse\WalkinController as WarehouseWalkinController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('web.landing');
})->name('web.landing');

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

    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', function () {
            return view('web.vendor.invoices.index');
        })->name('index');

        Route::get('{invoice}', function ($invoice) {
            return view('web.vendor.invoices.show', ['invoiceId' => $invoice]);
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

// API Tester (no auth required)
Route::get('/api-tester', function () {
    return view('api-tester');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    // Guest routes (login)
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('login', [AuthController::class, 'login']);
    });

    // Shared authenticated route(s)
    Route::middleware(['auth:admin', 'admin.audit'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });

    // Authenticated system-admin routes
    Route::middleware(['auth:admin', 'admin.audit', 'system.user'])->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Admin Self-Profile
        Route::get('profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
        Route::put('profile', [AdminProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/change-password', [AdminProfileController::class, 'changePassword'])->name('profile.change-password');

        // Global Search
        Route::get('search', [AdminSearchController::class, 'search'])->name('search');

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
        Route::get('vendors/{vendor}/activity-logs', [VendorController::class, 'activityLogs'])->name('vendors.activity-logs')->withTrashed();
        Route::get('vendors/{vendor}/otp-logs', [VendorController::class, 'otpLogs'])->name('vendors.otp-logs')->withTrashed();

        // Vendor Payouts Management
        Route::get('vendor-payouts', [VendorPayoutController::class, 'index'])->name('vendor-payouts.index');
        Route::get('vendor-payouts-data', [VendorPayoutController::class, 'data'])->name('vendor-payouts.data');
        Route::get('vendors/{vendor}/payouts-data', [VendorPayoutController::class, 'vendorPayouts'])->name('vendors.payouts-data');
        Route::post('vendors/{vendor}/payouts', [VendorPayoutController::class, 'store'])->name('vendors.payouts.store');
        Route::patch('vendor-payouts/{payout}/mark-sent', [VendorPayoutController::class, 'markSent'])->name('vendor-payouts.mark-sent');
        Route::patch('vendor-payouts/{payout}/confirm', [VendorPayoutController::class, 'confirm'])->name('vendor-payouts.confirm');

        // Shipment Management
        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('shipments-data', [ShipmentController::class, 'data'])->name('shipments.data');
        Route::get('shipments/create', [ShipmentController::class, 'create'])->name('shipments.create');
        Route::post('shipments', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::get('shipments/vendor-lookup', [ShipmentController::class, 'vendorLookup'])->name('shipments.vendor-lookup');
        Route::post('shipments/vendor-create', [ShipmentController::class, 'vendorCreate'])->name('shipments.vendor-create');
        Route::get('locations/search', [ShipmentController::class, 'locationSearch'])->name('locations.search');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'showPage'])->name('shipments.show');
        Route::get('shipments/{shipment}/edit', [ShipmentController::class, 'editPage'])->name('shipments.edit');
        Route::post('shipments/{shipment}/duplicate', [ShipmentController::class, 'duplicate'])->name('shipments.duplicate');
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

        // Invoice Management (on shipments)
        Route::post('shipments/{shipment}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/admin-accept', [InvoiceController::class, 'adminAccept'])->name('invoices.admin-accept');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
        Route::get('invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/payments-data', [InvoiceController::class, 'paymentsData'])->name('invoices.payments.data');
        Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'recordPayment'])->name('invoices.payments.store');

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

        // Driver Management
        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('drivers-data', [DriverController::class, 'data'])->name('drivers.data');
        Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('drivers/{driver}', [DriverController::class, 'showPage'])->name('drivers.show');
        Route::get('drivers/{driver}/json', [DriverController::class, 'show'])->name('drivers.show.json');
        Route::get('drivers/{driver}/packages-data', [DriverController::class, 'packagesData'])->name('drivers.packages-data');
        Route::put('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
        Route::patch('drivers/{driver}/toggle-active', [DriverController::class, 'toggleActive'])->name('drivers.toggle-active');
        Route::get('drivers-export', [DriverController::class, 'export'])->name('drivers.export');
        Route::get('drivers/{driver}/assignments', [DriverController::class, 'assignments'])->name('drivers.assignments');
        Route::get('drivers/{driver}/activity-logs', [DriverController::class, 'activityLogs'])->name('drivers.activity-logs');
        Route::get('drivers/{driver}/transport-manifests', [DriverController::class, 'transportManifests'])->name('drivers.transport-manifests');
        Route::get('drivers/{driver}/delivery-runs', [DriverController::class, 'deliveryRuns'])->name('drivers.delivery-runs');

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
        Route::post('delivery-runs/{run}/stops/{stop}/items/{item}/confirm-handoff', [AdminDeliveryRunController::class, 'confirmHandoffItem'])->name('delivery-runs.stops.items.confirm-handoff');
        Route::get('delivery-runs/{run}', [AdminDeliveryRunController::class, 'show'])->name('delivery-runs.show');

        // Transport Manifests (admin read + action capabilities)
        Route::get('transport-manifests', [AdminTransportManifestController::class, 'index'])->name('transport-manifests.index');
        Route::get('transport-manifests-data', [AdminTransportManifestController::class, 'data'])->name('transport-manifests.data');
        Route::get('transport-manifests-export', [AdminTransportManifestController::class, 'export'])->name('transport-manifests.export');
        Route::get('transport-manifests/{manifest}', [AdminTransportManifestController::class, 'show'])->name('transport-manifests.show');
        Route::post('transport-manifests/{manifest}/assign-driver', [AdminTransportManifestController::class, 'assignDriver'])->name('transport-manifests.assign-driver');
        Route::post('transport-manifests/{manifest}/unassign-driver', [AdminTransportManifestController::class, 'unassignDriver'])->name('transport-manifests.unassign-driver');
        Route::post('transport-manifests/{manifest}/dispatch', [AdminTransportManifestController::class, 'dispatch'])->name('transport-manifests.dispatch');

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
        Route::post('sort-batches/{batch}/create-delivery-run', [AdminSortBatchController::class, 'createDeliveryRun'])->name('sort-batches.create-delivery-run');

        // Collection Center (self-pickup)
        Route::get('collection-center', [CollectionCenterController::class, 'index'])->name('collection-center.index');
        Route::get('collection-center-data', [CollectionCenterController::class, 'data'])->name('collection-center.data');
        Route::post('collection-center/{shipment}/handover', [CollectionCenterController::class, 'handover'])->name('collection-center.handover');

        // Invoice List (all invoices across all shipments)
        Route::get('invoices', [AdminInvoiceListController::class, 'index'])->name('invoices.index');
        Route::get('invoices-data', [AdminInvoiceListController::class, 'data'])->name('invoices.data');
        Route::get('invoices-export', [AdminInvoiceListController::class, 'export'])->name('invoices.export');

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
Route::prefix('warehouse')
    ->name('warehouse.')
    ->middleware(['auth:admin', 'admin.audit', 'warehouse.user'])
    ->group(function () {
        Route::get('/', [WarehouseDashboardController::class, 'index'])->name('dashboard');

        // Walk-in Vendor Shipments
        Route::get('walkin', [WarehouseWalkinController::class, 'create'])->name('walkin.create');
        Route::post('walkin', [WarehouseWalkinController::class, 'store'])->name('walkin.store');
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
        Route::get('users/{user}/audit-logs-data', [WarehouseUserController::class, 'auditLogsData'])->name('users.audit-logs-data');
        Route::post('users', [WarehouseUserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [WarehouseUserController::class, 'update'])->name('users.update');
        Route::patch('users/{user}/toggle-active', [WarehouseUserController::class, 'toggleActive'])->name('users.toggle-active');

        // Receipts / Pickups / Items
        Route::get('receipts/pending', [WarehouseReceiptController::class, 'pendingIndex'])->name('receipts.pending.index');
        Route::get('receipts/pending-data', [WarehouseReceiptController::class, 'pendingData'])->name('receipts.pending.data');
        Route::get('receipts/pending/{pickupAssignment}', [WarehouseReceiptController::class, 'pendingShow'])->name('receipts.pending.show');
        Route::post('receipts/pending/{pickupAssignment}/items/{shipmentItem}', [WarehouseReceiptController::class, 'savePendingItem'])->name('receipts.pending.items.save');
        Route::post('receipts/pending/{pickupAssignment}/items/{shipmentItem}/print-label', [WarehouseReceiptController::class, 'printPendingItemLabel'])->name('receipts.pending.items.print-label');
        Route::post('receipts/pending/{pickupAssignment}/finalize', [WarehouseReceiptController::class, 'finalizePendingReceipt'])->name('receipts.pending.finalize');
        Route::get('pickups/received', [WarehouseReceiptController::class, 'receivedPickupsIndex'])->name('pickups.received.index');
        Route::get('pickups/received-data', [WarehouseReceiptController::class, 'receivedPickupsData'])->name('pickups.received.data');
        Route::get('pickups/received/{pickupAssignment}', [WarehouseReceiptController::class, 'receivedPickupShow'])->name('pickups.received.show');
        Route::get('items/received', [WarehouseReceiptController::class, 'receivedItemsIndex'])->name('items.received.index');
        Route::get('items/received-data', [WarehouseReceiptController::class, 'receivedItemsData'])->name('items.received.data');
        Route::get('items/received/{warehouseReceiptItem}', [WarehouseReceiptController::class, 'receivedItemShow'])->name('items.received.show');

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
        Route::post('sorting/batches', [WarehouseSortingController::class, 'storeBatch'])->name('sorting.batches.store');
        Route::post('sorting/batches/{sortBatch}/items', [WarehouseSortingController::class, 'addItems'])->name('sorting.batches.items.store');
        Route::delete('sorting/batches/{sortBatch}/items/{shipmentItem}', [WarehouseSortingController::class, 'removeItem'])->name('sorting.batches.items.destroy');
        Route::post('sorting/batches/{sortBatch}/seal', [WarehouseSortingController::class, 'seal'])->name('sorting.batches.seal');
        Route::post('sorting/batches/{sortBatch}/reopen', [WarehouseSortingController::class, 'reopen'])->name('sorting.batches.reopen');

        // Transport Manifests (Outbound + Incoming)
        Route::get('manifests/transport', [WarehouseTransportManifestController::class, 'outboundIndex'])->name('manifests.transport.index');
        Route::get('manifests/transport-data', [WarehouseTransportManifestController::class, 'outboundData'])->name('manifests.transport.data');
        Route::post('manifests/transport', [WarehouseTransportManifestController::class, 'create'])->name('manifests.transport.store');
        Route::post('manifests/transport/{manifest}/assign-driver', [WarehouseTransportManifestController::class, 'assignDriver'])->name('manifests.transport.assign-driver');
        Route::post('manifests/transport/{manifest}/dispatch', [WarehouseTransportManifestController::class, 'dispatch'])->name('manifests.transport.dispatch');
        Route::post('manifests/transport/{manifest}/unassign-driver', [WarehouseTransportManifestController::class, 'unassignDriver'])->name('manifests.transport.unassign-driver');
        Route::get('manifests/transport/{manifest}', [WarehouseTransportManifestController::class, 'outboundShow'])->name('manifests.transport.show');

        Route::get('manifests/incoming', [WarehouseTransportManifestController::class, 'incomingIndex'])->name('manifests.incoming.index');
        Route::get('manifests/incoming-data', [WarehouseTransportManifestController::class, 'incomingData'])->name('manifests.incoming.data');
        Route::get('manifests/incoming/{manifest}', [WarehouseTransportManifestController::class, 'incomingShow'])->name('manifests.incoming.show');
        Route::post('manifests/incoming/{manifest}/items/{shipmentItem}/scan-receive', [WarehouseTransportManifestController::class, 'scanIncomingItem'])->name('manifests.incoming.items.scan');
        Route::post('manifests/incoming/{manifest}/finalize-receipt', [WarehouseTransportManifestController::class, 'finalizeIncoming'])->name('manifests.incoming.finalize');

        // Delivery Runs
        Route::get('deliveries/runs', [WarehouseDeliveryRunController::class, 'index'])->name('deliveries.runs.index');
        Route::get('deliveries/runs-data', [WarehouseDeliveryRunController::class, 'data'])->name('deliveries.runs.data');
        Route::get('deliveries/runs/eligible-items', [WarehouseDeliveryRunController::class, 'eligibleItems'])->name('deliveries.runs.eligible-items');
        Route::post('deliveries/runs', [WarehouseDeliveryRunController::class, 'store'])->name('deliveries.runs.store');
        Route::post('deliveries/runs/from-items', [WarehouseDeliveryRunController::class, 'storeFromItems'])->name('deliveries.runs.store-from-items');
        Route::post('deliveries/runs/{run}/assign-driver', [WarehouseDeliveryRunController::class, 'assignDriver'])->name('deliveries.runs.assign-driver');
        Route::post('deliveries/runs/{run}/dispatch', [WarehouseDeliveryRunController::class, 'dispatch'])->name('deliveries.runs.dispatch');
        Route::post('deliveries/runs/{run}/stops/{stop}/resend-code', [WarehouseDeliveryRunController::class, 'resendCode'])->name('deliveries.runs.stops.resend-code');
        Route::patch('deliveries/runs/{run}/stops/{stop}/packages', [WarehouseDeliveryRunController::class, 'updateStopPackages'])->name('deliveries.runs.stops.update-packages');
        Route::patch('deliveries/runs/{run}/stops/{stop}/delivery-method', [WarehouseDeliveryRunController::class, 'updateStopDeliveryMethod'])->name('deliveries.runs.stops.update-delivery-method');
        Route::post('deliveries/runs/{run}/stops/{stop}/confirm-handoff', [WarehouseDeliveryRunController::class, 'adminConfirmHandoff'])->name('deliveries.runs.stops.confirm-handoff');
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
        Route::get('contacts/{task}/attempts', [WarehouseContactQueueController::class, 'attempts'])->name('contacts.attempts');
        Route::get('contacts/worker-stats', [WarehouseContactQueueController::class, 'workerStats'])->name('contacts.worker-stats');

        // Invoice Management
        Route::post('receipts/pending/{pickupAssignment}/invoices', [WarehouseInvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [WarehouseInvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [WarehouseInvoiceController::class, 'update'])->name('invoices.update');
        Route::post('invoices/{invoice}/send', [WarehouseInvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/cancel', [WarehouseInvoiceController::class, 'cancel'])->name('invoices.cancel');
        Route::post('invoices/{invoice}/admin-accept', [WarehouseInvoiceController::class, 'adminAccept'])->name('invoices.admin-accept');
        Route::get('invoices/{invoice}/download', [WarehouseInvoiceController::class, 'download'])->name('invoices.download');
        Route::get('invoices/{invoice}/print', [WarehouseInvoiceController::class, 'print'])->name('invoices.print');
        Route::get('invoices/{invoice}/payments-data', [WarehouseInvoiceController::class, 'paymentsData'])->name('invoices.payments.data');
        Route::post('invoices/{invoice}/payments', [WarehouseInvoiceController::class, 'recordPayment'])->name('invoices.payments.store');

        // Shipment Payments
        Route::get('receipts/pending/{pickupAssignment}/payments-data', [WarehouseShipmentPaymentController::class, 'data'])->name('receipts.payments.data');
        Route::post('receipts/pending/{pickupAssignment}/payments', [WarehouseShipmentPaymentController::class, 'store'])->name('receipts.payments.store');
        Route::delete('payments/{payment}', [WarehouseShipmentPaymentController::class, 'destroy'])->name('payments.destroy');
        Route::get('payments/{payment}/download', [WarehouseShipmentPaymentController::class, 'download'])->name('payments.download');
        Route::get('payments/{payment}/print', [WarehouseShipmentPaymentController::class, 'print'])->name('payments.print');
    });

/*
|--------------------------------------------------------------------------
| Developer Docs
|--------------------------------------------------------------------------
*/
Route::get('/docs/driver-flow', function () {
    return view('docs.driver-flow-guide');
})->name('docs.driver-flow');
