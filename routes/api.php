<?php

use App\Http\Controllers\Api\V1\Auth\DriverAuthController;
use App\Http\Controllers\Api\V1\Auth\VendorAuthController;
use App\Http\Controllers\Api\V1\DriverAssignmentController;
use App\Http\Controllers\Api\V1\DriverProfileController;
use App\Http\Controllers\Api\V1\VendorInvoiceController;
use App\Http\Controllers\Api\V1\VendorLocationController;
use App\Http\Controllers\Api\V1\VendorProfileController;
use App\Http\Controllers\Api\V1\VendorShipmentController;
use App\Http\Controllers\Api\V1\VendorShipmentItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// API v1 - Vendor Authentication
Route::prefix('v1/auth/vendor')->group(function () {
    Route::post('send-otp', [VendorAuthController::class, 'sendOtp']);
    Route::post('verify-phone', [VendorAuthController::class, 'verifyPhone']);
    Route::post('register', [VendorAuthController::class, 'register']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [VendorAuthController::class, 'logout']);
    });
});

// API v1 - Vendor Profile (Authenticated)
Route::prefix('v1/vendor')->middleware('auth:sanctum')->group(function () {
    Route::get('profile', [VendorProfileController::class, 'show']);
    Route::put('profile', [VendorProfileController::class, 'update']);

    // Location endpoints
    Route::get('regions', [VendorLocationController::class, 'regions']);
    Route::get('regions/{region}/districts', [VendorLocationController::class, 'districts']);

    // Shipment endpoints
    Route::get('shipments', [VendorShipmentController::class, 'index']);
    Route::post('shipments', [VendorShipmentController::class, 'store']);
    Route::get('shipments/{shipment}', [VendorShipmentController::class, 'show']);
    Route::put('shipments/{shipment}', [VendorShipmentController::class, 'update']);
    Route::delete('shipments/{shipment}', [VendorShipmentController::class, 'destroy']);
    Route::post('shipments/{shipment}/submit', [VendorShipmentController::class, 'submit']);

    // Shipment Item endpoints
    Route::post('shipments/{shipment}/items', [VendorShipmentItemController::class, 'store']);
    Route::put('shipments/{shipment}/items/{item}', [VendorShipmentItemController::class, 'update']);
    Route::delete('shipments/{shipment}/items/{item}', [VendorShipmentItemController::class, 'destroy']);
    Route::post('shipments/{shipment}/items/{item}/images', [VendorShipmentItemController::class, 'uploadImage']);
    Route::delete('shipments/{shipment}/items/{item}/images/{image}', [VendorShipmentItemController::class, 'deleteImage']);

    // Invoice endpoints
    Route::get('invoices', [VendorInvoiceController::class, 'index']);
    Route::get('invoices/view', [VendorInvoiceController::class, 'showByFilter']);
    Route::get('invoices/{invoice}', [VendorInvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/accept', [VendorInvoiceController::class, 'accept']);
    Route::post('invoices/{invoice}/reject', [VendorInvoiceController::class, 'reject']);
    Route::get('invoices/{invoice}/pdf', [VendorInvoiceController::class, 'downloadPdf']);
});

// API v1 - Driver Authentication
Route::prefix('v1/driver')->group(function () {
    Route::post('login', [DriverAuthController::class, 'login']);

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [DriverAuthController::class, 'logout']);
        Route::get('profile', [DriverProfileController::class, 'show']);
        Route::put('profile', [DriverProfileController::class, 'update']);
        Route::put('change-password', [DriverProfileController::class, 'changePassword']);

        // Pickup endpoints
        Route::get('pickups', [DriverAssignmentController::class, 'index']);
        Route::get('pickups/{assignment}', [DriverAssignmentController::class, 'show']);
        Route::post('pickups/{assignment}/en-route', [DriverAssignmentController::class, 'startEnRoute']);
        Route::post('pickups/{assignment}/arrive', [DriverAssignmentController::class, 'arrive']);
        Route::post('pickups/{assignment}/items/{item}/confirm', [DriverAssignmentController::class, 'confirmPickupItem']);
        Route::post('pickups/{assignment}/confirm-pickup', [DriverAssignmentController::class, 'confirmPickup']);
    });
});
