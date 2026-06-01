<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryDelayReason;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Services\DeliveryDelayService;
use App\Services\DriverDeliveryService;
use App\Services\Warehouse\WarehouseDeliveryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverDeliveryController extends Controller
{
    public function __construct(
        private DriverDeliveryService $driverDeliveryService,
        private WarehouseDeliveryService $warehouseDeliveryService,
        private DeliveryDelayService $deliveryDelayService,
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
        $skipVerification = filter_var($request->input('skip_verification', false), FILTER_VALIDATE_BOOLEAN);

        $validated = $request->validate([
            'verification_code' => [$skipVerification ? 'nullable' : 'required', 'digits:4'],
            'skip_verification' => ['nullable', 'in:true,false,1,0'],
            'skip_reason' => [$skipVerification ? 'required' : 'nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'proof_photo' => ['required', 'file', 'image', 'max:12288'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.shipment_item_id' => ['required', 'integer', 'exists:shipment_items,id'],
            'items.*.delivered_quantity' => ['required', 'integer', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->warehouseDeliveryService->driverConfirmStop(
            run: $run,
            stop: $stop,
            driver: $driver,
            verificationCode: $validated['verification_code'] ?? null,
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            proofPhoto: $request->file('proof_photo'),
            linePayloads: $validated['items'],
            ipAddress: (string) $request->ip(),
            skipVerification: $skipVerification,
            skipReason: $validated['skip_reason'] ?? null,
            deliveryNotes: $validated['delivery_notes'] ?? null
        );

        return $this->deliveryActionResponse($driver, $run, $result, 400);
    }

    public function confirmStopPackages(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $driver = $request->user();
        $skipVerification = filter_var($request->input('skip_verification', false), FILTER_VALIDATE_BOOLEAN);

        $validated = $request->validate([
            'verification_code' => [$skipVerification ? 'nullable' : 'required', 'digits:4'],
            'skip_verification' => ['nullable', 'in:true,false,1,0'],
            'skip_reason' => [$skipVerification ? 'required' : 'nullable', 'string', 'max:500'],
            'packages_delivered' => ['required', 'integer', 'min:0'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'proof_photo' => ['required', 'file', 'image', 'max:12288'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            // Optional: the delivery fee the driver collected from the recipient
            // on arrival (for stops where the fee wasn't set in advance, or
            // renegotiated in the field). Recorded as a paid recipient→parcelman
            // delivery_fee charge at at_delivery stage.
            'in_field_delivery_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $result = $this->warehouseDeliveryService->driverConfirmStopByPackage(
            run: $run,
            stop: $stop,
            driver: $driver,
            verificationCode: $validated['verification_code'] ?? null,
            packagesDelivered: (int) $validated['packages_delivered'],
            latitude: isset($validated['latitude']) ? (float) $validated['latitude'] : null,
            longitude: isset($validated['longitude']) ? (float) $validated['longitude'] : null,
            proofPhoto: $request->file('proof_photo'),
            ipAddress: (string) $request->ip(),
            skipVerification: $skipVerification,
            skipReason: $validated['skip_reason'] ?? null,
            deliveryNotes: $validated['delivery_notes'] ?? null,
            inFieldDeliveryFee: isset($validated['in_field_delivery_fee']) ? (float) $validated['in_field_delivery_fee'] : null,
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

    public function updateItemEta(Request $request, DeliveryRunItem $deliveryRunItem): JsonResponse
    {
        $driver = $request->user();

        if (!$this->deliveryDelayService->canDriverUpdate($deliveryRunItem, $driver)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only update ETA for active packages assigned to you.',
            ], 403);
        }

        $validated = $request->validate([
            'expected_delivery_at' => ['required', 'date', 'after:now'],
            'reason_id' => ['nullable', 'integer', 'exists:delivery_delay_reasons,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $reason = null;
        if (!empty($validated['reason_id'])) {
            $reason = DeliveryDelayReason::query()
                ->where('is_active', true)
                ->find($validated['reason_id']);

            if (!$reason) {
                return response()->json([
                    'success' => false,
                    'message' => 'Select an active delay reason.',
                ], 422);
            }
        }

        $item = $this->deliveryDelayService->setEta(
            item: $deliveryRunItem,
            expectedDeliveryAt: Carbon::parse($validated['expected_delivery_at']),
            reason: $reason,
            notes: $validated['notes'] ?? null,
            source: \App\Models\DeliveryDelayEvent::SOURCE_RIDER_ETA,
            driver: $driver,
        );

        return response()->json([
            'success' => true,
            'message' => 'Delivery ETA updated.',
            'data' => [
                'item' => [
                    'id' => $item->id,
                    'eta' => $this->deliveryDelayService->snapshot($item),
                ],
            ],
        ]);
    }

    public function confirmHandoff(Request $request, DeliveryRun $run, DeliveryRunStop $stop): JsonResponse
    {
        $driver = $request->user();

        $validated = $request->validate([
            'courier_name' => ['nullable', 'string', 'max:255'],
            'courier_phone' => ['nullable', 'string', 'max:20'],
            'vehicle_number' => ['nullable', 'string', 'max:50'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'proof_photo' => ['required', 'file', 'image', 'max:12288'],
            // Station name is still stored as text so riders can choose a saved
            // station or type a one-off station when it is not listed.
            'bus_station_name' => ['nullable', 'string', 'max:255'],
            // Optional: how much was paid to the bus station courier to take these packages.
            // Recorded as a parcelman→station expense charge line per shipment handed off.
            'station_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
        ]);

        $result = $this->warehouseDeliveryService->driverConfirmHandoff(
            driver: $driver,
            run: $run,
            stop: $stop,
            data: $validated,
            request: $request,
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
