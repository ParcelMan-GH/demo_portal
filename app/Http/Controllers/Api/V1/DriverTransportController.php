<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransportLoadingException;
use App\Models\TransportManifest;
use App\Services\DriverTransportService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DriverTransportController extends Controller
{
    public function __construct(
        private DriverTransportService $driverTransportService,
        private WarehouseTransportService $transportService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $driver = $request->user();
            $search = trim($request->query('search', ''));

            // Dynamically detect column name (driver_id vs transporter_id)
            $driverCol = Schema::hasColumn('transport_manifests', 'driver_id') ? 'driver_id' : 'transporter_id';

            // Query dispatches assigned to this driver OR unassigned in the pool
            $query = TransportManifest::query()
                ->with(['originWarehouse', 'destinationWarehouse'])
                ->where(function ($q) use ($driver, $driverCol) {
                    $q->where($driverCol, $driver->id)
                      ->orWhereNull($driverCol)
                      ->orWhere($driverCol, 0)
                      ->orWhere($driverCol, '');
                });

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $isFirst = true;

                    if (is_numeric($search)) {
                        $q->where('id', (int) $search);
                        $isFirst = false;
                    }

                    foreach (['batch_number', 'manifest_number', 'code'] as $col) {
                        if (Schema::hasColumn('transport_manifests', $col)) {
                            if ($isFirst) {
                                $q->where($col, 'like', "%{$search}%");
                                $isFirst = false;
                            } else {
                                $q->orWhere($col, 'like', "%{$search}%");
                            }
                        }
                    }

                    if ($isFirst) {
                        $q->whereHas('containers', fn ($cq) => $cq->where('code', 'like', "%{$search}%"));
                    } else {
                        $q->orWhereHas('containers', fn ($cq) => $cq->where('code', 'like', "%{$search}%"));
                    }

                    $q->orWhereHas('items', fn ($iq) => $iq->where('tracking_code', 'like', "%{$search}%"));
                });
            }

            $manifests = $query->latest()->get();

            // Calculate transporter metrics
            $drivesMade = TransportManifest::where($driverCol, $driver->id)
                ->whereIn('status', ['in_transit', 'completed', 'arrived'])
                ->count();
            $totalBatches = TransportManifest::where($driverCol, $driver->id)->count();
            $exceptions = 0;

            if (class_exists(TransportLoadingException::class)) {
                $exceptions = TransportLoadingException::whereHas('manifest', fn ($q) => $q->where($driverCol, $driver->id))->count();
            }

            $recent = $manifests->map(function ($m) use ($driverCol, $driver) {
                $code = $m->batch_number ?? $m->manifest_number ?? $m->code ?? "TRN-{$m->id}";
                $destinationName = $m->destinationWarehouse?->name 
                    ?? (isset($m->delivery_region_id) ? "Region #{$m->delivery_region_id} / District #{$m->delivery_district_id}" : 'Destination Hub');

                return [
                    'id' => (string) $m->id,
                    'manifest_code' => $code,
                    'origin' => $m->originWarehouse?->name ?? 'Origin Hub',
                    'destination' => $destinationName,
                    'package_count' => $m->items_count ?? ($m->relationLoaded('items') ? $m->items->count() : 0),
                    'status' => str_replace('_', ' ', ucfirst($m->status ?? 'pending')),
                    'status_raw' => $m->status ?? 'pending',
                    'transporter_id' => $m->{$driverCol} ?? $driver->id,
                ];
            });

            return response()->json([
                'success' => true,
                'metrics' => [
                    'drives_made' => $drivesMade,
                    'total_batches' => $totalBatches,
                    'exceptions' => $exceptions,
                ],
                'data' => $recent,
                'recent_activities' => $recent,
            ]);
        } catch (\Throwable $e) {
            Log::error('DriverTransportController Index Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
                'recent_activities' => [],
            ], 500);
        }
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
        $driverCol = Schema::hasColumn('transport_manifests', 'driver_id') ? 'driver_id' : 'transporter_id';

        if (!$manifest->{$driverCol}) {
            $manifest->update([
                $driverCol => $driver->id,
            ]);
        }

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