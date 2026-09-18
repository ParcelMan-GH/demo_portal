<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\TransportLoadingException;
use App\Models\OutgoingBatch;
use App\Models\ShipmentItem;
use App\Models\TransportManifestItem;
use App\Models\Warehouse;
use App\Models\Driver;
use App\Models\TransportManifest;
use App\Services\DriverTransportService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DriverTransportController extends Controller
{

    /** Driver resolved for the authenticated account (cached per request). */
    private ?Driver $resolvedActingDriver = null;

    /**
     * The app signs in the staff User that holds the rider/transporter role, while
     * dispatches, custody and deliveries are recorded against a Driver record.
     */
    private function actingDriver(Request $request): Driver
    {
        if ($this->resolvedActingDriver) {
            return $this->resolvedActingDriver;
        }

        $user = $request->user();

        if ($user instanceof Driver) {
            return $this->resolvedActingDriver = $user;
        }

        $phone = trim((string) ($user?->phone ?? ''));
        $email = trim((string) ($user?->email ?? ''));
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $tail = strlen($digits) >= 9 ? substr($digits, -9) : '';
        $normalisePhone = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '')";

        $driver = null;

        if ($phone !== '' || $email !== '') {
            $driver = Driver::query()
                ->where(function ($query) use ($phone, $email, $digits, $tail, $normalisePhone) {
                    if ($phone !== '') {
                        $query->where('phone', $phone);
                    }
                    if ($digits !== '') {
                        $query->orWhereRaw("{$normalisePhone} = ?", [$digits]);
                    }
                    if ($tail !== '') {
                        $query->orWhereRaw("RIGHT({$normalisePhone}, 9) = ?", [$tail]);
                    }
                    if ($email !== '') {
                        $query->orWhere('email', $email);
                    }
                })
                ->orderByDesc('is_active')
                ->first();
        }

        if (! $driver && $phone !== '') {
            $driver = $this->provisionDriverProfile($user, $phone);
        }

        if (! $driver) {
            abort(403, 'No rider profile is linked to this account yet. Please contact your warehouse supervisor.');
        }

        return $this->resolvedActingDriver = $driver;
    }

    private function provisionDriverProfile(?object $user, string $phone): ?Driver
    {
        $fallbackEmail = 'rider-'.(preg_replace('/\D+/', '', $phone) ?: 'unknown').'@parcelmanexpress.local';

        foreach (array_values(array_unique(array_filter([$user?->email, $fallbackEmail]))) as $email) {
            try {
                return Driver::create([
                    'name' => (string) ($user?->name ?: 'Rider'),
                    'email' => $email,
                    'phone' => $phone,
                    'password' => bcrypt(bin2hex(random_bytes(16))),
                    'vehicle_type' => 'motorcycle',
                    'status' => 'available',
                    'is_active' => true,
                    'task_capabilities' => ['pickup', 'delivery'],
                ]);
            } catch (\Throwable $e) {
                Log::warning('Could not auto-provision rider profile', ['error' => $e->getMessage()]);
            }
        }

        return null;
    }

    /**
     * Build the transport manifest a scanned outgoing-batch code refers to.
     */
    private function bridgeOutgoingBatch(string $code, Driver $driver): ?TransportManifest
    {
        try {
            $existing = TransportManifest::query()->where('manifest_number', $code)->first();

            if ($existing) {
                return $existing;
            }

            $batch = OutgoingBatch::query()->where('batch_number', $code)->first();

            if (! $batch) {
                return null;
            }

            $items = ShipmentItem::query()
                ->where('outgoing_batch_id', $batch->id)
                ->with('warehouseReceiptItems.receipt')
                ->get();

            $originId = $items
                ->map(fn ($item) => $item->warehouseReceiptItems->first()?->receipt?->warehouse_id)
                ->filter()
                ->first()
                ?? ($driver->warehouse_id ?? null);

            $destinationQuery = Warehouse::query()->where('is_active', true);

            if (Schema::hasColumn('warehouses', 'region_id') && $batch->delivery_region_id) {
                $destinationQuery->where('region_id', $batch->delivery_region_id);
            }

            $destinationId = (clone $destinationQuery)->orderBy('id')->value('id') ?? $originId;

            if (! $originId || ! $destinationId) {
                Log::warning('Bridge skipped: no warehouse could be resolved', ['batch' => $code]);

                return null;
            }

            $now = now();

            $attributes = array_intersect_key(array_filter([
                'manifest_number' => $batch->batch_number,
                'origin_warehouse_id' => $originId,
                'destination_warehouse_id' => $destinationId,
                'assigned_driver_id' => $driver->id,
                'assigned_at' => $now,
                'status' => 'in_transit',
                'dispatched_at' => $now,
                'notes' => 'Created from outgoing batch '.$batch->batch_number.' during a transporter scan.',
            ], fn ($value) => $value !== null), array_flip(Schema::getColumnListing('transport_manifests')));

            $manifest = TransportManifest::query()->create($attributes);

            $itemColumns = array_flip(Schema::getColumnListing('transport_manifest_items'));

            foreach ($items as $item) {
                $quantity = max(1, (int) $item->quantity);

                $itemAttributes = array_intersect_key(array_filter([
                    'transport_manifest_id' => $manifest->id,
                    'shipment_item_id' => $item->id,
                    'expected_quantity' => $quantity,
                    'loaded_quantity' => $quantity,
                    'line_status' => 'loaded',
                    'loaded_at' => $now,
                ], fn ($value) => $value !== null), $itemColumns);

                TransportManifestItem::query()->create($itemAttributes);
            }

            Log::info('Bridged outgoing batch during a transporter scan', [
                'batch' => $batch->batch_number,
                'manifest_id' => $manifest->id,
                'driver_id' => $driver->id,
                'items' => $items->count(),
            ]);

            return $manifest->fresh();
        } catch (\Throwable $e) {
            Log::error('Bridge failed during transporter scan: '.$e->getMessage(), ['code' => $code]);

            return null;
        }
    }

    public function __construct(
        private DriverTransportService $driverTransportService,
        private WarehouseTransportService $transportService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $driver = $this->actingDriver($request);
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

            // A transporter scanning a batch the portal dispatched before the bridge
            // existed: build the manifest now and hand it to this transporter.
            if ($search !== '' && $manifests->isEmpty()) {
                $bridged = $this->bridgeOutgoingBatch($search, $driver);

                if ($bridged) {
                    $manifests = collect([$bridged->load(['originWarehouse', 'destinationWarehouse'])]);
                }
            }

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