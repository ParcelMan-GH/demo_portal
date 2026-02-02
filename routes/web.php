<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VendorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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
        Route::post('settings/test-email', [SettingsController::class, 'testEmail'])->name('settings.test-email');
        Route::post('settings/test-sms', [SettingsController::class, 'testSms'])->name('settings.test-sms');
        Route::post('settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    });
});
