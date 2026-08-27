<?php

use App\Http\Controllers\Api\V1\Auth\DriverAuthController;
use App\Http\Controllers\Api\V1\Auth\VendorAuthController;
use App\Http\Controllers\Api\V1\AgentParcelController;
use App\Http\Controllers\Api\V1\DriverAssignmentController;
use App\Http\Controllers\Api\V1\DriverBusHandoffController;
use App\Http\Controllers\Api\V1\DriverBusStationController;
use App\Http\Controllers\Api\V1\DriverDeliveryController;
use App\Http\Controllers\Api\V1\DriverProfileController;
use App\Http\Controllers\Api\V1\DriverRiderTeamController;
use App\Http\Controllers\Api\V1\DriverRiderTeamHandoverController;
use App\Http\Controllers\Api\V1\DriverTransportController;
use App\Http\Controllers\Api\V1\VendorLocationController;
use App\Http\Controllers\Api\V1\VendorProfileController;
use App\Http\Controllers\Api\V1\VendorEarningsController;
use App\Http\Controllers\Api\V1\VendorNotificationController;
use App\Http\Controllers\Api\V1\VendorPickupVehicleTypeController;
use App\Http\Controllers\Api\V1\VendorShipmentController;
use App\Http\Controllers\Api\V1\VendorShipmentItemController;
use App\Http\Controllers\Api\V1\DriverNotificationController;
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
    Route::middleware(['auth:sanctum', 'vendor.active'])->group(function () {
        Route::post('logout', [VendorAuthController::class, 'logout']);
    });
});

// API v1 - Agent Operations
Route::prefix('v1/agent')->middleware(['auth:sanctum'])->group(function () {
    // Scan & Claim Endpoints
    Route::post('/parcels/scan-claim', [AgentParcelController::class, 'scanClaim']);
    Route::post('/parcels/claim', [AgentParcelController::class, 'scanClaim']);
    Route::post('/scan-claim', [AgentParcelController::class, 'scanClaim']);
    Route::post('/claim', [AgentParcelController::class, 'scanClaim']);

    // Agent Queue
    Route::get('/parcels/queue', [AgentParcelController::class, 'getQueue']);
    Route::get('/queue', [AgentParcelController::class, 'getQueue']);

    // Dashboard & Overview Stats
    Route::get('/overview', [AgentParcelController::class, 'overview']);

    // Call Verification Logging
    Route::post('/calls/log', [AgentParcelController::class, 'logCall']);
    Route::post('/parcels/call-log', [AgentParcelController::class, 'logCall']);
    Route::get('/calls/history', [AgentParcelController::class, 'callHistory']);

    // Agent Notifications
    Route::get('/notifications', [AgentParcelController::class, 'notifications']);
    Route::post('/notifications/read-all', [AgentParcelController::class, 'markAllNotificationsRead']);
    Route::post('/notifications/settings', [AgentParcelController::class, 'updateNotificationSettings']);

    // Agent Earnings
    Route::get('/earnings', [AgentParcelController::class, 'earnings']);
});

// API v1 - Vendor Profile (Authenticated)
Route::prefix('v1/vendor')->middleware(['auth:sanctum', 'vendor.active'])->group(function () {
    Route::get('profile', [VendorProfileController::class, 'show']);
    Route::put('profile', [VendorProfileController::class, 'update']);
    Route::get('payout-account', [VendorProfileController::class, 'payoutAccount']);
    Route::put('payout-account', [VendorProfileController::class, 'updatePayoutAccount']);
    Route::post('fcm-token', [VendorProfileController::class, 'updateFcmToken']);
    Route::delete('account', [VendorProfileController::class, 'deleteAccount']);

    // Location endpoints
    Route::get('regions', [VendorLocationController::class, 'regions']);
    Route::get('regions/{region}/districts', [VendorLocationController::class, 'districts']);
    Route::get('locations/search', [VendorLocationController::class, 'searchLocations']);
    Route::get('pickup-vehicle-types', [VendorPickupVehicleTypeController::class, 'index']);

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

    // Notification endpoints
    Route::get('notifications', [VendorNotificationController::class, 'index']);
    Route::post('notifications/read-all', [VendorNotificationController::class, 'markAllAsRead']);
    Route::post('notifications/{notification}/read', [VendorNotificationController::class, 'markAsRead']);

    // Earnings & Payouts endpoints
    Route::get('earnings/summary', [VendorEarningsController::class, 'summary']);
    Route::get('earnings', [VendorEarningsController::class, 'earnings']);
    Route::get('payouts', [VendorEarningsController::class, 'payouts']);
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
        Route::post('fcm-token', [DriverProfileController::class, 'updateFcmToken']);
        Route::get('bus-stations', [DriverBusStationController::class, 'index']);
        Route::get('locations/search', [VendorLocationController::class, 'searchLocations']);
        Route::get('delivery-failure-reasons', [DriverBusHandoffController::class, 'reasons']);
        Route::get('bus-handoff-reasons', [DriverBusHandoffController::class, 'reasons']);
        Route::get('bus-handoffs', [DriverBusHandoffController::class, 'index']);
        Route::get('bus-handoffs/{deliveryRunItem}', [DriverBusHandoffController::class, 'show']);
        Route::post('bus-handoffs/{deliveryRunItem}/send-confirmation', [DriverBusHandoffController::class, 'sendConfirmation']);
        Route::post('bus-handoffs/{deliveryRunItem}/confirm-code', [DriverBusHandoffController::class, 'confirmCode']);
        Route::post('bus-handoffs/{deliveryRunItem}/report-issue', [DriverBusHandoffController::class, 'reportIssue']);

        // Pickup endpoints
        Route::get('pickups', [DriverAssignmentController::class, 'index']);
        Route::get('pickups/{assignment}', [DriverAssignmentController::class, 'show']);
        Route::post('pickups/{assignment}/en-route', [DriverAssignmentController::class, 'startEnRoute']);
        Route::post('pickups/{assignment}/arrive', [DriverAssignmentController::class, 'arrive']);
        Route::post('pickups/{assignment}/items/{item}/confirm', [DriverAssignmentController::class, 'confirmPickupItem']);
        Route::post('pickups/{assignment}/confirm-pickup', [DriverAssignmentController::class, 'confirmPickup']);

        // Transport endpoints
        Route::get('transports', [DriverTransportController::class, 'index']);
        Route::get('transports/{manifest}', [DriverTransportController::class, 'show']);
        Route::post('transports/{manifest}/start-loading', [DriverTransportController::class, 'startLoading']);
        Route::post('transports/{manifest}/scan-load', [DriverTransportController::class, 'scanLoad']);
        Route::post('transports/{manifest}/scan-issue', [DriverTransportController::class, 'scanIssue']);
        Route::post('transports/{manifest}/depart', [DriverTransportController::class, 'depart']);
        Route::post('transports/{manifest}/arrive', [DriverTransportController::class, 'arrive']);

        // Delivery endpoints
        Route::get('deliveries', [DriverDeliveryController::class, 'index']);
        Route::get('deliveries/{run}', [DriverDeliveryController::class, 'show']);
        Route::post('deliveries/{run}/stops/{stop}/arrive', [DriverDeliveryController::class, 'arriveStop']);
        Route::post('deliveries/{run}/stops/{stop}/confirm', [DriverDeliveryController::class, 'confirmStop']);
        Route::post('deliveries/{run}/stops/{stop}/confirm-packages', [DriverDeliveryController::class, 'confirmStopPackages']);
        Route::post('deliveries/{run}/stops/{stop}/confirm-handoff', [DriverDeliveryController::class, 'confirmHandoff']);
        Route::post('deliveries/{run}/stops/{stop}/fail', [DriverDeliveryController::class, 'failStop']);
        Route::patch('delivery-items/{deliveryRunItem}/eta', [DriverDeliveryController::class, 'updateItemEta']);

        // Notification endpoints
        Route::get('notifications', [DriverNotificationController::class, 'index']);
        Route::post('notifications/read-all', [DriverNotificationController::class, 'markAllAsRead']);
        Route::post('notifications/{notification}/read', [DriverNotificationController::class, 'markAsRead']);

        // Package custody (scan & claim)
        Route::post('scan-claim', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'scanClaim']);
        Route::get('my-packages', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'myPackages']);
        Route::post('release-package', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'releasePackage']);
        Route::post('start-deliveries', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'startDeliveries']);
        Route::get('package-history/{barcode}', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'packageHistory']);
        Route::post('packages/{trackingCode}/location-change', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'changePackageLocation']);
        Route::post('packages/{trackingCode}/transfer', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'requestTransfer']);
        Route::get('package-transfers/incoming', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'incomingTransfers']);
        Route::get('package-transfers/outgoing', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'outgoingTransfers']);
        Route::post('package-transfers/{transfer}/accept', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'acceptTransfer']);
        Route::post('package-transfers/{transfer}/reject', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'rejectTransfer']);
        Route::post('package-transfers/{transfer}/cancel', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'cancelTransfer']);
        Route::post('package-transfers/{transfer}/recall', [\App\Http\Controllers\Api\V1\DriverPackageController::class, 'recallTransfer']);

        // Rider team custody
        Route::get('rider-teams', [DriverRiderTeamController::class, 'index']);
        Route::get('rider-teams/{team}', [DriverRiderTeamController::class, 'show']);
        Route::post('rider-teams/{team}/members/lookup', [DriverRiderTeamController::class, 'lookupMember']);
        Route::post('rider-teams/{team}/members', [DriverRiderTeamController::class, 'addMember']);
        Route::delete('rider-teams/{team}/members/{driver}', [DriverRiderTeamController::class, 'removeMember']);
        Route::get('rider-team-handovers', [DriverRiderTeamHandoverController::class, 'index']);
        Route::get('rider-team-handovers/{handover}', [DriverRiderTeamHandoverController::class, 'show']);
        Route::post('rider-teams/{team}/scan-receive', [DriverRiderTeamHandoverController::class, 'scanReceiveForTeam']);
        Route::post('rider-team-handovers/{handover}/scan-receive', [DriverRiderTeamHandoverController::class, 'scanReceive']);
        Route::post('rider-team-handovers/{handover}/allocate', [DriverRiderTeamHandoverController::class, 'allocate']);
        Route::post('rider-team-handovers/scan-claim', [DriverRiderTeamHandoverController::class, 'scanClaim']);
    });
});