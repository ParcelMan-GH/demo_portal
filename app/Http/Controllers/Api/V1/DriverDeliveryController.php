<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunStop;
use App\Services\DriverDeliveryService;
use App\Services\Warehouse\WarehouseDeliveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverDeliveryController extends Controller
{
    public function __construct(
        private DriverDeliveryService $driverDeliveryService,
        private WarehouseDeliveryService $warehouseDeliveryService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->driverDeliveryService->list($request->user(), $request);

        return response()->json($result);
    }

    public function show(Request $request, DeliveryRun $run): JsonResponse
    {
        $result = $this->driverDeliveryService->show($request->user(), $run);
        $status = $result['status'] ?? 200;
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function arriveStop(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $driver = $request->user();
        $result = $this->warehouseDeliveryService->driverArriveStop($run, $stop, $driver);

        return $this->deliveryActionResponse($driver, $run, $result, 400);
    }

    public function confirmStop(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $driver = $request->user();
        $validated = $request->validate([
            'verification_code' => ['required', 'digits:6'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'proof_photo' => ['required', 'file', 'image', 'max:12288'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.shipment_item_id' => ['required', 'integer', 'exists:shipment_items,id'],
            'items.*.delivered_quantity' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->warehouseDeliveryService->driverConfirmStop(
            run: $run,
            stop: $stop,
            driver: $driver,
            verificationCode: $validated['verification_code'],
            latitude: (float) $validated['latitude'],
            longitude: (float) $validated['longitude'],
            proofPhoto: $request->file('proof_photo'),
            linePayloads: $validated['items'],
            ipAddress: (string) $request->ip()
        );

        return $this->deliveryActionResponse($driver, $run, $result, 400);
    }

    public function failStop(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $driver = $request->user();
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->warehouseDeliveryService->driverFailStop(
            run: $run,
            stop: $stop,
            driver: $driver,
            reason: $validated['reason'],
            notes: $validated['notes'] ?? null
        );

        return $this->deliveryActionResponse($driver, $run, $result, 400);
    }

    private function deliveryActionResponse($driver, DeliveryRun $run, array $result, int $errorCode): JsonResponse
    {
        if (($result['success'] ?? false) === true) {
            $details = $this->driverDeliveryService->show($driver, $run->fresh());
            if (($details['success'] ?? false) === true) {
                $result['data']['delivery'] = $details['data']['delivery'] ?? null;
            }
        }

        return response()->json($result, ($result['success'] ?? false) ? 200 : $errorCode);
    }
}

