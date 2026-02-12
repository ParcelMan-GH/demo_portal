<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PickupAssignmentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Admin\WarehouseController;
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

    // Authenticated routes
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Role Management
        Route::resource('roles', RoleController::class);

        // Admin Management
        Route::resource('admins', AdminController::class)->except(['destroy']);
        Route::get('admins-data', [AdminController::class, 'data'])->name('admins.data');
        Route::get('admins-export', [AdminController::class, 'export'])->name('admins.export');
        Route::patch('admins/{admin}/toggle-active', [AdminController::class, 'toggleActive'])
            ->name('admins.toggle-active');
        Route::post('admins/{admin}/assign-roles', [AdminController::class, 'assignRoles'])
            ->name('admins.assign-roles');

        // Vendor Management
        Route::get('vendors', [VendorController::class, 'index'])->name('vendors.index');
        Route::get('vendors-data', [VendorController::class, 'data'])->name('vendors.data');
        Route::post('vendors', [VendorController::class, 'store'])->name('vendors.store');
        Route::get('vendors/{vendor}', [VendorController::class, 'showPage'])->name('vendors.show');
        Route::get('vendors/{vendor}/json', [VendorController::class, 'show'])->name('vendors.show.json');
        Route::put('vendors/{vendor}', [VendorController::class, 'update'])->name('vendors.update');
        Route::delete('vendors/{vendor}', [VendorController::class, 'destroy'])->name('vendors.destroy');
        Route::patch('vendors/{vendor}/toggle-active', [VendorController::class, 'toggleActive'])
            ->name('vendors.toggle-active');
        Route::get('vendors-export', [VendorController::class, 'export'])->name('vendors.export');
        Route::get('vendors/{vendor}/shipments', [VendorController::class, 'shipments'])->name('vendors.shipments');
        Route::get('vendors/{vendor}/activity-logs', [VendorController::class, 'activityLogs'])->name('vendors.activity-logs');
        Route::get('vendors/{vendor}/otp-logs', [VendorController::class, 'otpLogs'])->name('vendors.otp-logs');

        // Shipment Management
        Route::get('shipments', [ShipmentController::class, 'index'])->name('shipments.index');
        Route::get('shipments-data', [ShipmentController::class, 'data'])->name('shipments.data');
        Route::get('shipments/{shipment}', [ShipmentController::class, 'showPage'])->name('shipments.show');
        Route::get('shipments/{shipment}/items', [ShipmentController::class, 'items'])->name('shipments.items');
        Route::get('shipments/{shipment}/tracking', [ShipmentController::class, 'tracking'])->name('shipments.tracking');
        Route::get('shipments-export', [ShipmentController::class, 'export'])->name('shipments.export');

        // Invoice Management (on shipments)
        Route::post('shipments/{shipment}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
        Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');

        // Pickup Assignment Management
        Route::get('available-drivers', [PickupAssignmentController::class, 'availableDrivers'])->name('assignments.available-drivers');
        Route::get('available-warehouses', [PickupAssignmentController::class, 'availableWarehouses'])->name('assignments.available-warehouses');
        Route::post('shipments/{shipment}/assign-driver', [PickupAssignmentController::class, 'assign'])->name('assignments.assign');
        Route::post('assignments/{pickupAssignment}/cancel', [PickupAssignmentController::class, 'cancel'])->name('assignments.cancel');
        Route::post('assignments/{pickupAssignment}/receive', [PickupAssignmentController::class, 'receive'])->name('assignments.receive');

        // Driver Management
        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('drivers-data', [DriverController::class, 'data'])->name('drivers.data');
        Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('drivers/{driver}', [DriverController::class, 'showPage'])->name('drivers.show');
        Route::get('drivers/{driver}/json', [DriverController::class, 'show'])->name('drivers.show.json');
        Route::put('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');
        Route::patch('drivers/{driver}/toggle-active', [DriverController::class, 'toggleActive'])->name('drivers.toggle-active');
        Route::get('drivers-export', [DriverController::class, 'export'])->name('drivers.export');
        Route::get('drivers/{driver}/assignments', [DriverController::class, 'assignments'])->name('drivers.assignments');
        Route::get('drivers/{driver}/activity-logs', [DriverController::class, 'activityLogs'])->name('drivers.activity-logs');

        // Warehouse Management
        Route::get('warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('warehouses-data', [WarehouseController::class, 'data'])->name('warehouses.data');
        Route::get('warehouses-regions', [WarehouseController::class, 'regions'])->name('warehouses.regions');
        Route::get('warehouses-regions/{region}/districts', [WarehouseController::class, 'districts'])->name('warehouses.districts');
        Route::post('warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'showPage'])->name('warehouses.show');
        Route::get('warehouses/{warehouse}/json', [WarehouseController::class, 'show'])->name('warehouses.show.json');
        Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])->name('warehouses.destroy');
        Route::patch('warehouses/{warehouse}/toggle-active', [WarehouseController::class, 'toggleActive'])->name('warehouses.toggle-active');
        Route::get('warehouses-export', [WarehouseController::class, 'export'])->name('warehouses.export');

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
        Route::post('settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
        Route::post('settings/test-sms', [SettingsController::class, 'testSms'])->name('settings.test-sms');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    });
});
