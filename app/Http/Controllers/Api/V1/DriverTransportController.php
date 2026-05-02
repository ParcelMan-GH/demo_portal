<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransportLoadingException;
use App\Models\TransportManifest;
use App\Services\DriverTransportService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverTransportController extends Controller
{
    public function __construct(
        private DriverTransportService $driverTransportService,
        private WarehouseTransportService $transportService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->driverTransportService->list($request->user(), $request);

        return response()->json($result);
    }

    public function show(Request $request, TransportManifest $manifest): JsonResponse
    {
        $result = $this->driverTransportService->show($request->user(), $manifest);
        $status = $result['status'] ?? 200;
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function startLoading(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $request->user();
        $result = $this->transportService->driverStartLoading($manifest, $driver);

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    public function scanLoad(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $request->user();
        $validated = $request->validate([
            'tracking_code' => ['nullable', 'string', 'max:100'],
            'container_code' => ['nullable', 'string', 'max:100'],
        ]);

        $code = $validated['container_code'] ?? $validated['tracking_code'] ?? null;
        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Container code is required.',
            ], 422);
        }

        $result = $this->transportService->driverScanLoad(
            manifest: $manifest,
            driver: $driver,
            trackingCode: $code
        );

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    public function scanIssue(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $request->user();
        $validated = $request->validate([
            'target_type' => ['required', 'string', 'in:container,item'],
            'container_id' => ['required_if:target_type,container', 'nullable', 'integer', 'exists:transport_containers,id'],
            'manifest_item_id' => ['required_if:target_type,item', 'nullable', 'integer', 'exists:transport_manifest_items,id'],
            'reason' => ['required', 'string', 'in:' . implode(',', [
                TransportLoadingException::REASON_LABEL_DAMAGED,
                TransportLoadingException::REASON_LABEL_MISSING,
                TransportLoadingException::REASON_CAMERA_CANNOT_READ,
                TransportLoadingException::REASON_ITEM_PRESENT_NO_LABEL,
                TransportLoadingException::REASON_OTHER,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
            'proof_photo' => ['required', 'image', 'max:5120'],
        ]);

        $result = $this->transportService->driverReportScanIssue(
            manifest: $manifest,
            driver: $driver,
            data: $validated,
            proofPhoto: $request->file('proof_photo')
        );

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    public function depart(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $request->user();
        $result = $this->transportService->driverDepart($manifest, $driver);

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    public function arrive(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $request->user();
        $result = $this->transportService->driverArrive($manifest, $driver);

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    private function transportActionResponse($driver, TransportManifest $manifest, array $result, int $errorCode): JsonResponse
    {
        if (($result['success'] ?? false) === true) {
            $details = $this->driverTransportService->show($driver, $manifest->fresh());
            if (($details['success'] ?? false) === true) {
                $result['data']['transport'] = $details['data']['transport'] ?? null;
            }
        }

        return response()->json($result, ($result['success'] ?? false) ? 200 : $errorCode);
    }
}
