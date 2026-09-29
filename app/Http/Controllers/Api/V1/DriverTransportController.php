<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\TransportLoadingException;
use App\Models\OutgoingBatch;
use App\Models\ShipmentItem;
use App\Models\TransportManifestItem;
use App\Models\Warehouse;
use App\Models\Driver;
use App\Http\Controllers\Api\V1\Concerns\ResolvesActingDriver;
use App\Models\TransportManifest;
use App\Services\DriverTransportService;
use App\Services\Warehouse\WarehouseTransportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class DriverTransportController extends Controller
{
    use ResolvesActingDriver;


    /** Driver resolved for the authenticated account (cached per request). */
    private ?Driver $resolvedActingDriver = null;

    /**
     * The app signs in the staff User that holds the rider/transporter role, while
     * dispatches, custody and deliveries are recorded against a Driver record.
     */
    /**
     * Build the transport manifest a scanned outgoing-batch code refers to.
     */
    private function bridgeOutgoingBatch(string $code, Driver $driver): ?TransportManifest
    {
        try {
            $existing = TransportManifest::query()->where('manifest_number', $code)->first();

            if ($existing) {
                /*
                 * The batch was closed and dispatched without naming a
                 * transporter, so it reached the app unassigned. Scanning it is
                 * the claim: the driver holding the scanner is the driver taking
                 * the load. A manifest already assigned to somebody else is left
                 * exactly as it is.
                 */
                $this->transportService->claimManifest($existing, $driver);

                return $existing->fresh();
            }

            $batch = CodeResolver::resolveOutgoingBatch($code);

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
                // The real link back to the batch. Without it the only connection
                // is the manifest number matching the batch number, which is a
                // string coincidence rather than a relationship — and it is what
                // the label-print step had to fall back on.
                'sort_batch_id' => $batch->id,
                'origin_warehouse_id' => $originId,
                'destination_warehouse_id' => $destinationId,
                'assigned_driver_id' => $driver->id,
                'assigned_at' => $now,
                /*
                 * Handed over as `assigned`, not `in_transit`.
                 *
                 * The scan screen's next step is POST .../start-loading, and
                 * driverStartLoading only accepts `assigned` or `loading` — so a
                 * bridged manifest created straight into `in_transit` came back
                 * "Manifest is not ready for loading." The driver could see the
                 * batch but not load it, which is the flow this is meant to
                 * unblock. The lifecycle now runs normally:
                 * assigned -> loading -> in_transit -> arrived.
                 */
                'status' => TransportManifest::STATUS_ASSIGNED,
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

            // The manifest's driver column.
            //
            // This previously guessed `driver_id`, then fell back to
            // `transporter_id` — and neither exists on transport_manifests. The
            // real column is `assigned_driver_id`, so the fallback was always
            // taken and EVERY call to this endpoint died with
            // "Unknown column 'transporter_id'" (HTTP 500). The probe stays, with
            // the real column first, so it keeps working if the schema moves.
            $driverCol = collect(['assigned_driver_id', 'driver_id', 'transporter_id'])
                ->first(fn ($col) => Schema::hasColumn('transport_manifests', $col))
                ?? 'assigned_driver_id';

            // The active view deliberately includes unclaimed work, so a driver
            // can see a batch waiting to be claimed. History must not: it is the
            // record of what this driver actually did.
            $isHistory = $request->query('filter') === 'history';

            $query = TransportManifest::query()
                /*
                 * The coordinate columns matter as much as the names here: the
                 * app draws the route and computes the distance from them, and
                 * without them it substituted hardcoded Accra and Kumasi
                 * constants — so every batch was reported as a 250 km intercity
                 * run, including the ones whose origin and destination are the
                 * same warehouse.
                 */
                ->with([
                    'originWarehouse:id,name,code,address,latitude,longitude,region_id,district_id',
                    'originWarehouse.region:id,name',
                    'destinationWarehouse:id,name,code,address,latitude,longitude,region_id,district_id',
                    'destinationWarehouse.region:id,name',
                    'destinationWarehouse.district:id,name',
                ])
                /*
                 * Counted, not loaded. The payload reads `items_count`, and this
                 * query never produced it — so `package_count` fell through to
                 * the `relationLoaded('items')` check, which was also false, and
                 * every batch reported "0 packages" however many parcels it held.
                 * withCount is also the cheaper of the two: it does not hydrate
                 * the rows just to count them.
                 */
                ->withCount(['items', 'containers']);

            if ($isHistory) {
                $query->where($driverCol, $driver->id)
                      ->whereIn('status', TransportManifest::STATUSES_HISTORY);
            } else {
                $query->where(function ($q) use ($driver, $driverCol) {
                    $q->where($driverCol, $driver->id)
                      ->orWhereNull($driverCol)
                      ->orWhere($driverCol, 0)
                      ->orWhere($driverCol, '');
                });
            }

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

                    /*
                     * Both of these used columns that do not exist, and an
                     * unguarded column reference is a hard SQL error rather than
                     * a no-op:
                     *
                     *   transport_containers.code          -> the column is container_code
                     *   transport_manifest_items.tracking_code -> no such column at all;
                     *       the tracking code lives on the related shipment item.
                     *
                     * So EVERY scan died with "Unknown column 'code' in 'WHERE'"
                     * (HTTP 500), which the app reports as "Batch Not Found".
                     * Every code was affected, not just the reported one.
                     */
                    $containerColumn = collect(['container_code', 'code'])
                        ->first(fn ($col) => Schema::hasColumn('transport_containers', $col));

                    if ($containerColumn) {
                        $containerMatch = fn ($cq) => $cq->where($containerColumn, 'like', "%{$search}%");

                        if ($isFirst) {
                            $q->whereHas('containers', $containerMatch);
                            $isFirst = false;
                        } else {
                            $q->orWhereHas('containers', $containerMatch);
                        }
                    }

                    // The tracking code belongs to the shipment item, which this
                    // line references — not to the manifest line itself.
                    if (Schema::hasColumn('shipment_items', 'tracking_code')) {
                        $q->orWhereHas(
                            'items.shipmentItem',
                            fn ($iq) => $iq->where('tracking_code', 'like', CodeResolver::likeTerm($search))
                        );
                    }
                });
            }

            $manifests = $query->latest()->get();

            // A transporter scanning a batch the portal dispatched before the bridge
            // existed: build the manifest now and hand it to this transporter.
            if ($search !== '' && $manifests->isEmpty()) {
                $bridged = $this->bridgeOutgoingBatch($search, $driver);

                if ($bridged) {
                    $manifests = collect([
                        $bridged
                            ->load([
                                'originWarehouse:id,name,code,address,latitude,longitude,region_id,district_id',
                                'originWarehouse.region:id,name',
                                'destinationWarehouse:id,name,code,address,latitude,longitude,region_id,district_id',
                                'destinationWarehouse.region:id,name',
                                'destinationWarehouse.district:id,name',
                            ])
                            ->loadCount(['items', 'containers']),
                    ]);
                }
            }

            // Calculate transporter metrics
            // 'completed' is not one of this model's statuses, so it matched
            // nothing; 'received' is the real end of the journey.
            $drivesMade = TransportManifest::where($driverCol, $driver->id)
                ->whereIn('status', TransportManifest::STATUSES_HISTORY)
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
                    'origin' => $m->originWarehouse?->name ?? 'Origin not recorded',
                    'destination' => $destinationName,
                    /*
                     * Coordinates, so the client never has to invent them.
                     *
                     * Sent both as warehouse objects (with the address, for a
                     * future detail view) and flattened as *_lat / *_lng, which
                     * is what the existing screens already look for. They are
                     * null when a warehouse has no coordinate on file — the
                     * client must handle that by showing no route rather than a
                     * guess.
                     */
                    'origin_warehouse' => $m->originWarehouse ? [
                        'id' => $m->originWarehouse->id,
                        'name' => $m->originWarehouse->name,
                        'code' => $m->originWarehouse->code,
                        'address' => $m->originWarehouse->address,
                        'latitude' => $m->originWarehouse->latitude !== null ? (float) $m->originWarehouse->latitude : null,
                        'longitude' => $m->originWarehouse->longitude !== null ? (float) $m->originWarehouse->longitude : null,
                    ] : null,
                    'origin_lat' => $m->originWarehouse?->latitude !== null ? (float) $m->originWarehouse->latitude : null,
                    'origin_lng' => $m->originWarehouse?->longitude !== null ? (float) $m->originWarehouse->longitude : null,
                    'destination_warehouse' => $m->destinationWarehouse ? [
                        'id' => $m->destinationWarehouse->id,
                        'name' => $m->destinationWarehouse->name,
                        'code' => $m->destinationWarehouse->code,
                        'address' => $m->destinationWarehouse->address,
                        'latitude' => $m->destinationWarehouse->latitude !== null ? (float) $m->destinationWarehouse->latitude : null,
                        'longitude' => $m->destinationWarehouse->longitude !== null ? (float) $m->destinationWarehouse->longitude : null,
                        'region_id' => $m->destinationWarehouse->region_id,
                        'region' => $m->destinationWarehouse->region?->name,
                        'district_id' => $m->destinationWarehouse->district_id,
                        'district' => $m->destinationWarehouse->district?->name,
                    ] : null,
                    'destination_lat' => $m->destinationWarehouse?->latitude !== null ? (float) $m->destinationWarehouse->latitude : null,
                    'destination_lng' => $m->destinationWarehouse?->longitude !== null ? (float) $m->destinationWarehouse->longitude : null,
                    /*
                     * The destination region, flat, so one trip can be held to one
                     * region without the client guessing. A driver must not be able
                     * to add a batch landing in another region to a trip already
                     * carrying one — the rule is enforced server-side, and these
                     * are the names the app needs to say which two regions clashed.
                     */
                    'destination_region_id' => $m->destinationWarehouse?->region_id,
                    'destination_region' => $m->destinationWarehouse?->region?->name,
                    'destination_district_id' => $m->destinationWarehouse?->district_id,
                    'destination_district' => $m->destinationWarehouse?->district?->name,
                    'package_count' => (int) ($m->items_count ?? ($m->relationLoaded('items') ? $m->items->count() : 0)),
                    // The raw counts, so the client is not parsing a number out
                    // of a display field to decide whether anything is loaded.
                    'items_count' => (int) ($m->items_count ?? 0),
                    'containers_count' => (int) ($m->containers_count ?? 0),
                    'status' => str_replace('_', ' ', ucfirst($m->status ?? 'pending')),
                    'status_raw' => $m->status ?? 'pending',
                    // Was `$m->{$driverCol} ?? $driver->id`, which reported the
                    // CALLER's id for an unassigned manifest — making unclaimed
                    // pool work indistinguishable from a driver's own batch. Both
                    // values are now reported truthfully so the client can tell
                    // them apart.
                    'assigned_driver_id' => $m->{$driverCol},
                    'is_assigned_to_me' => (int) $m->{$driverCol} === (int) $driver->id,
                    'transporter_id' => $m->{$driverCol},
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
        $result = $this->driverTransportService->show($this->actingDriver($request), $manifest);
        $status = $result['status'] ?? 200;
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function startLoading(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $this->actingDriver($request);
        $result = $this->transportService->driverStartLoading($manifest, $driver);

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    public function scanLoad(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $this->actingDriver($request);
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
        $driver = $this->actingDriver($request);
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
        $driver = $this->actingDriver($request);

        /*
         * Claiming is not done here any more; WarehouseTransportService::
         * claimManifest() owns it, and driverDepart() calls it first.
         *
         * This method used to claim on its own with
         * `$manifest->update(['assigned_driver_id' => $driver->id])`. That
         * recorded the driver but left the manifest in `draft`, so the very next
         * check inside driverDepart() read `draft` and refused the departure
         * with "This batch has already left the warehouse." — on a batch that had
         * never moved. The claim is now one call, in one place, and it sets the
         * driver and the status together.
         */
        $result = $this->transportService->driverDepart($manifest, $driver);

        return $this->transportActionResponse($driver, $manifest, $result, 400);
    }

    public function arrive(Request $request, TransportManifest $manifest): JsonResponse
    {
        $driver = $this->actingDriver($request);
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