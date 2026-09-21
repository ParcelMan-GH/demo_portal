<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\OutgoingBatch;
use App\Models\Region;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The hub app's API: checking a consignment into a hub, holding it as inventory,
 * putting it on an intercity bus, and releasing it onwards.
 *
 * A hub is a `warehouses` row and every call is scoped to the hub of the signed
 * in user (`EnsureHubAgent` guarantees one is assigned).
 */
class HubController extends Controller
{
    /**
     * Statuses that mean "this parcel is physically sitting in the hub".
     *
     * `at_warehouse` is included because the warehouse receiving flow already
     * uses it for exactly that state, before this API existed.
     *
     * @var array<int, ItemStatus>
     */
    private const AT_HUB_STATUSES = [ItemStatus::ARRIVED_AT_HUB, ItemStatus::AT_WAREHOUSE];

    /**
     * Destination names, remembered for the life of the request so listing a
     * page of parcels does not re-query the same handful of places.
     *
     * @var array<int, string|null>
     */
    private array $regionNames = [];

    /** @var array<int, string|null> */
    private array $districtNames = [];

    /**
     * Who the hub is signed in as, and where.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $hub = $user->warehouse;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                ],
                'hub' => $this->serializeHub($hub),
            ],
        ]);
    }

    /**
     * Check a consignment into the hub.
     *
     * Accepts a batch number (all of its parcels), a single barcode/tracking
     * code, or explicit package ids. A parcel that does not belong to the batch
     * it was scanned against is rejected rather than quietly accepted.
     */
    public function intake(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_number' => ['nullable', 'string', 'max:60'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'tracking_code' => ['nullable', 'string', 'max:120'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer'],
            'shelf_location' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $request->user();
        $hub = $user->warehouse;

        $batch = null;

        if (! empty($validated['batch_number'])) {
            $batch = OutgoingBatch::query()
                ->where('batch_number', $validated['batch_number'])
                ->first();

            if (! $batch) {
                return $this->failed("No batch found for {$validated['batch_number']}.", 404);
            }
        }

        $code = $validated['barcode'] ?? $validated['tracking_code'] ?? null;

        if ($code) {
            $item = $this->findItemByCode($code);

            if (! $item) {
                return $this->failed("No package found for {$code}.", 404);
            }

            if ($batch && (int) $item->outgoing_batch_id !== (int) $batch->id) {
                return $this->failed(
                    "Package {$item->tracking_code} does not belong to batch {$batch->batch_number}.",
                    422
                );
            }

            if (! $batch && $item->outgoing_batch_id) {
                $batch = OutgoingBatch::query()->find($item->outgoing_batch_id);
            }

            $items = collect([$item]);
        } elseif (! empty($validated['package_ids'])) {
            $ids = array_values(array_unique($validated['package_ids']));
            $items = ShipmentItem::query()->whereIn('id', $ids)->get();

            if ($items->count() !== count($ids)) {
                return $this->failed('Some of those packages could not be found.', 404);
            }

            if ($batch) {
                foreach ($items as $item) {
                    if ((int) $item->outgoing_batch_id !== (int) $batch->id) {
                        return $this->failed(
                            "Package {$item->tracking_code} does not belong to batch {$batch->batch_number}.",
                            422
                        );
                    }
                }
            }
        } elseif ($batch) {
            $items = $batch->shipmentItems()->get();
        } else {
            return $this->failed('Provide a batch number, a barcode, or package ids to check in.', 422);
        }

        if ($items->isEmpty()) {
            return $this->failed('There was nothing to check in.', 422);
        }

        $received = [];
        $alreadyAtHub = [];

        foreach ($items as $item) {
            // Re-scanning a parcel is normal at a busy desk: report it, don't
            // move it or stamp it twice.
            if ($item->hub_id === $hub->id && $item->arrived_at_hub_at) {
                $alreadyAtHub[] = $this->serializePackage($item, $hub);

                continue;
            }

            $item->update([
                'hub_id' => $hub->id,
                'arrived_at_hub_at' => $item->arrived_at_hub_at ?? now(),
                'status' => ItemStatus::ARRIVED_AT_HUB->value,
                'shelf_location' => $validated['shelf_location'] ?? $item->shelf_location,
                'pickup_code' => $item->pickup_code ?: $this->generatePickupCode(),
            ]);

            $this->logTracking(
                $item,
                ItemStatus::ARRIVED_AT_HUB,
                $hub,
                $batch
                    ? "Checked in at {$hub->name} for batch {$batch->batch_number}"
                    : "Checked in at {$hub->name}",
                ['source' => 'hub_intake', 'batch_number' => $batch?->batch_number],
                (int) $user->id
            );

            $received[] = $this->serializePackage($item->fresh(), $hub);
        }

        return response()->json([
            'success' => true,
            'message' => $this->intakeMessage($received, $alreadyAtHub, $batch),
            'data' => [
                'hub' => $this->serializeHub($hub),
                'batch_number' => $batch?->batch_number,
                'received_count' => count($received),
                'already_at_hub_count' => count($alreadyAtHub),
                'packages' => $received,
                'already_at_hub' => $alreadyAtHub,
            ],
        ]);
    }

    /**
     * What is sitting in this hub.
     *
     * Defaults to parcels currently held, filterable by status, searchable by
     * tracking code / recipient, and narrowable by destination.
     */
    public function inventory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:120'],
            'destination' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $hub = $request->user()->warehouse;
        $perPage = (int) ($validated['per_page'] ?? 30);

        $query = ShipmentItem::query()->where('hub_id', $hub->id);

        $statuses = $this->statusesFromFilter($validated['status'] ?? null);

        if ($statuses === null) {
            return $this->failed('Unknown status filter: '.$validated['status'], 422);
        }

        $query->whereIn('status', $statuses);

        if (! empty($validated['search'])) {
            $term = '%'.$validated['search'].'%';

            $query->where(function ($inner) use ($term) {
                $inner->where('tracking_code', 'like', $term)
                    ->orWhere('delivery_recipient_name', 'like', $term)
                    ->orWhere('delivery_recipient_phone', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if (! empty($validated['destination'])) {
            $term = '%'.$validated['destination'].'%';

            $query->where(function ($inner) use ($term) {
                $inner->where('delivery_town', 'like', $term)
                    ->orWhereIn('delivery_region_id', Region::query()->where('name', 'like', $term)->pluck('id'))
                    ->orWhereIn('delivery_district_id', District::query()->where('name', 'like', $term)->pluck('id'));
            });
        }

        $packages = $query
            ->orderByDesc('arrived_at_hub_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'hub' => $this->serializeHub($hub),
                'packages' => $this->serializePackages($packages->getCollection(), $hub),
                'counts' => $this->hubCounts($hub),
                'pagination' => [
                    'current_page' => $packages->currentPage(),
                    'per_page' => $packages->perPage(),
                    'total' => $packages->total(),
                    'has_more' => $packages->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Batches this hub can put on a bus.
     */
    public function batches(Request $request): JsonResponse
    {
        $hub = $request->user()->warehouse;

        $batches = OutgoingBatch::query()
            ->whereHas('shipmentItems', fn ($query) => $query->where('hub_id', $hub->id))
            ->whereNotIn('status', OutgoingBatch::CLOSED_STATUSES)
            ->withCount(['shipmentItems' => fn ($query) => $query->where('hub_id', $hub->id)])
            ->orderBy('batch_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'hub' => $this->serializeHub($hub),
                'batches' => $batches->map(fn (OutgoingBatch $batch) => [
                    'batch_number' => $batch->batch_number,
                    'status' => $batch->status,
                    'package_count' => $batch->shipment_items_count,
                    'transport_driver_id' => $batch->transport_driver_id,
                    'destination' => $this->destinationLabel($batch->delivery_region_id, $batch->delivery_district_id),
                ])->values(),
            ],
        ]);
    }

    /**
     * The manifest for one batch, so the desk can review what is on the
     * transporter before confirming the intake.
     */
    public function showBatch(Request $request, string $batchNumber): JsonResponse
    {
        $hub = $request->user()->warehouse;

        $batch = OutgoingBatch::query()
            ->where('batch_number', $batchNumber)
            ->first();

        if (! $batch) {
            return $this->failed("No batch found for {$batchNumber}.", 404);
        }

        $items = $batch->shipmentItems()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'hub' => $this->serializeHub($hub),
                'batch' => [
                    'batch_number' => $batch->batch_number,
                    'status' => $batch->status,
                    'destination' => $this->destinationLabel($batch->delivery_region_id, $batch->delivery_district_id),
                    'transport_driver_id' => $batch->transport_driver_id,
                    'total_parcels' => $items->count(),
                    'already_at_this_hub' => $items->where('hub_id', $hub->id)->count(),
                ],
                'packages' => $this->serializePackages($items, $hub),
            ],
        ]);
    }

    /**
     * Hand a batch (or loose parcels) to an intercity bus.
     */
    public function handoff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'batch_number' => ['nullable', 'string', 'max:60'],
            'package_ids' => ['nullable', 'array'],
            'package_ids.*' => ['integer'],
            'transport_driver_id' => ['nullable', 'integer'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'bus_company' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $hub = $user->warehouse;

        $batch = null;

        if (! empty($validated['batch_number'])) {
            $batch = OutgoingBatch::query()
                ->where('batch_number', $validated['batch_number'])
                ->first();

            if (! $batch) {
                return $this->failed("No batch found for {$validated['batch_number']}.", 404);
            }

            if (in_array($batch->status, [OutgoingBatch::STATUS_RECEIVED], true)) {
                return $this->failed("Batch {$batch->batch_number} has already been received downstream.", 422);
            }

            $items = $batch->shipmentItems()->where('hub_id', $hub->id)->get();
        } elseif (! empty($validated['package_ids'])) {
            $ids = array_values(array_unique($validated['package_ids']));
            $items = ShipmentItem::query()
                ->whereIn('id', $ids)
                ->where('hub_id', $hub->id)
                ->get();

            if ($items->count() !== count($ids)) {
                return $this->failed('Some of those packages are not in this hub.', 403);
            }
        } else {
            return $this->failed('Provide a batch number or package ids to hand off.', 422);
        }

        if ($items->isEmpty()) {
            return $this->failed('There is nothing in this hub to dispatch for that selection.', 422);
        }

        $meta = array_filter([
            'source' => 'hub_handoff',
            'batch_number' => $batch?->batch_number,
            'driver_name' => $validated['driver_name'] ?? null,
            'driver_phone' => $validated['driver_phone'] ?? null,
            'vehicle_plate' => $validated['vehicle_plate'] ?? null,
            'bus_company' => $validated['bus_company'] ?? null,
            'departure_time' => $validated['departure_time'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $dispatched = [];

        foreach ($items as $item) {
            $item->update([
                'status' => ItemStatus::DISPATCHED_TO_BUS->value,
                'dispatched_to_bus_at' => now(),
            ]);

            $this->logTracking(
                $item,
                ItemStatus::DISPATCHED_TO_BUS,
                $hub,
                'Dispatched from '.$hub->name.' on an intercity bus',
                $meta + ['notes' => $validated['notes'] ?? null],
                (int) $user->id
            );

            $dispatched[] = $this->serializePackage($item->fresh(), $hub);
        }

        if ($batch) {
            $batch->update(array_filter([
                'status' => OutgoingBatch::STATUS_DISPATCHED,
                'transport_driver_id' => $validated['transport_driver_id'] ?? null,
            ], fn ($value) => $value !== null));
        }

        return response()->json([
            'success' => true,
            'message' => count($dispatched).' '.str('package')->plural(count($dispatched))
                .' dispatched'.($batch ? " for batch {$batch->batch_number}" : '').'.',
            'data' => [
                'hub' => $this->serializeHub($hub),
                'batch_number' => $batch?->batch_number,
                'batch_status' => $batch?->fresh()->status,
                'dispatched_count' => count($dispatched),
                'packages' => $dispatched,
            ],
        ]);
    }

    /**
     * Release a parcel from the hub to a rider, or straight to the recipient.
     */
    public function release(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => ['nullable'],
            'barcode' => ['nullable', 'string', 'max:120'],
            'tracking_code' => ['nullable', 'string', 'max:120'],
            'release_to' => ['required', 'string', 'in:driver,recipient'],
            'confirmation_code' => ['nullable', 'string', 'max:12'],
            'driver_id' => ['nullable', 'integer'],
            'driver_name' => ['nullable', 'string', 'max:120'],
            'recipient_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $hub = $user->warehouse;

        $code = $validated['barcode']
            ?? $validated['tracking_code']
            ?? $validated['package_id']
            ?? null;

        if (! $code) {
            return $this->failed('Provide the package to release.', 422);
        }

        $item = $this->findItemByCode($code);

        if (! $item) {
            return $this->failed("No package found for {$code}.", 404);
        }

        if ($item->hub_id !== $hub->id) {
            return $this->failed("Package {$item->tracking_code} is not held at {$hub->name}.", 403);
        }

        if ($item->released_at) {
            return $this->failed("Package {$item->tracking_code} was already released.", 422);
        }

        $toRecipient = $validated['release_to'] === 'recipient';

        // The pickup code is the recipient's proof of collection, so it is only
        // demanded when the recipient themselves is collecting. A rider handover
        // is verified by who is taking the parcel, not by that code.
        if ($toRecipient && $item->pickup_code) {
            if (empty($validated['confirmation_code'])) {
                return $this->failed('This package needs its pickup code to be released.', 422);
            }

            if (! hash_equals($item->pickup_code, (string) $validated['confirmation_code'])) {
                return $this->failed('That pickup code does not match this package.', 422);
            }
        }

        $item->update([
            'status' => $toRecipient
                ? ItemStatus::DELIVERED->value
                : ItemStatus::OUT_FOR_DELIVERY->value,
            'released_at' => now(),
        ]);

        $this->logTracking(
            $item,
            $toRecipient ? ItemStatus::DELIVERED : ItemStatus::OUT_FOR_DELIVERY,
            $hub,
            $toRecipient
                ? 'Collected by the recipient at '.$hub->name
                : 'Released to a rider for last-mile delivery',
            array_filter([
                'source' => 'hub_release',
                'release_to' => $validated['release_to'],
                'driver_id' => $validated['driver_id'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'recipient_name' => $validated['recipient_name'] ?? null,
                'pickup_code_verified' => $item->pickup_code ? true : null,
                'notes' => $validated['notes'] ?? null,
            ], fn ($value) => $value !== null),
            (int) $user->id
        );

        return response()->json([
            'success' => true,
            'message' => $toRecipient
                ? 'Package handed to the recipient.'
                : 'Package released to the rider.',
            'data' => [
                'hub' => $this->serializeHub($hub),
                'package' => $this->serializePackage($item->fresh(), $hub),
                'counts' => $this->hubCounts($hub),
            ],
        ]);
    }

    /**
     * Rack a parcel on a shelf.
     */
    public function shelve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => ['required'],
            'shelf_location' => ['required', 'string', 'max:60'],
        ]);

        $hub = $request->user()->warehouse;
        $item = $this->findItemByCode($validated['package_id']);

        if (! $item) {
            return $this->failed('No package found for '.$validated['package_id'].'.', 404);
        }

        if ($item->hub_id !== $hub->id) {
            return $this->failed("Package {$item->tracking_code} is not held at {$hub->name}.", 403);
        }

        $item->update(['shelf_location' => $validated['shelf_location']]);

        return response()->json([
            'success' => true,
            'message' => "Package shelved at {$validated['shelf_location']}.",
            'data' => ['package' => $this->serializePackage($item->fresh(), $hub)],
        ]);
    }

    /**
     * Recent hub activity, for the notifications screen.
     */
    public function activity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $hub = $request->user()->warehouse;
        $limit = (int) ($validated['limit'] ?? 20);

        $itemIds = ShipmentItem::query()->where('hub_id', $hub->id)->pluck('id');

        $rows = ShipmentItemTracking::query()
            ->whereIn('shipment_item_id', $itemIds)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $codes = ShipmentItem::query()
            ->whereIn('id', $rows->pluck('shipment_item_id')->unique())
            ->pluck('tracking_code', 'id');

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $rows->map(fn (ShipmentItemTracking $row) => [
                    'id' => (string) $row->id,
                    'package_id' => (string) $row->shipment_item_id,
                    'tracking_code' => $codes[$row->shipment_item_id] ?? null,
                    'status' => $row->status,
                    'title' => ItemStatus::tryFrom((string) $row->status)?->label() ?? (string) $row->status,
                    'body' => $row->notes,
                    'location' => $row->location,
                    'created_at' => $row->created_at?->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    /**
     * Turn the mobile app's status filter into status values.
     *
     * @return array<int, string>|null null when the filter is not recognised
     */
    private function statusesFromFilter(?string $filter): ?array
    {
        if ($filter === null || $filter === '' || $filter === 'at_hub' || $filter === 'in_hub') {
            return array_map(fn (ItemStatus $status) => $status->value, self::AT_HUB_STATUSES);
        }

        if ($filter === 'all') {
            return array_map(fn (ItemStatus $status) => $status->value, ItemStatus::cases());
        }

        $status = ItemStatus::tryFrom($filter);

        return $status ? [$status->value] : null;
    }

    private function findItemByCode($code): ?ShipmentItem
    {
        if ($code === null || $code === '') {
            return null;
        }

        if (is_numeric($code)) {
            $byId = ShipmentItem::query()->whereKey((int) $code)->first();

            if ($byId) {
                return $byId;
            }
        }

        return ShipmentItem::query()->where('tracking_code', (string) $code)->first();
    }

    private function generatePickupCode(): string
    {
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $candidate = (string) random_int(1000, 9999);

            if (! ShipmentItem::query()->where('pickup_code', $candidate)->exists()) {
                return $candidate;
            }
        }

        return (string) random_int(100000, 999999);
    }

    private function logTracking(
        ShipmentItem $item,
        ItemStatus $status,
        Warehouse $hub,
        string $notes,
        array $meta,
        int $userId
    ): void {
        ShipmentItemTracking::create([
            'shipment_item_id' => $item->id,
            'status' => $status->value,
            'location' => $hub->name,
            'notes' => $notes,
            'meta' => array_filter($meta, fn ($value) => $value !== null),
            'created_by' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeHub(?Warehouse $hub): array
    {
        return [
            'id' => $hub?->id,
            'name' => $hub?->name,
            'code' => $hub?->code,
            'contact_phone' => $hub?->contact_phone,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function hubCounts(Warehouse $hub): array
    {
        $base = ShipmentItem::query()->where('hub_id', $hub->id);

        return [
            'at_hub' => (clone $base)->whereIn('status', array_map(
                fn (ItemStatus $status) => $status->value,
                self::AT_HUB_STATUSES
            ))->count(),
            'dispatched_to_bus' => (clone $base)->where('status', ItemStatus::DISPATCHED_TO_BUS->value)->count(),
            'released_today' => (clone $base)->whereNotNull('released_at')
                ->whereDate('released_at', today())
                ->count(),
            'received_today' => (clone $base)->whereNotNull('arrived_at_hub_at')
                ->whereDate('arrived_at_hub_at', today())
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, ShipmentItem>  $items
     * @return array<int, array<string, mixed>>
     */
    private function serializePackages(Collection $items, Warehouse $hub): array
    {
        return $items->map(fn (ShipmentItem $item) => $this->serializePackage($item, $hub))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function serializePackage(ShipmentItem $item, Warehouse $hub): array
    {
        $status = $item->status instanceof ItemStatus
            ? $item->status
            : ItemStatus::tryFrom((string) $item->status);

        return [
            'id' => (string) $item->id,
            'tracking_code' => $item->tracking_code,
            'description' => $item->description,
            'status' => $status?->value ?? (string) $item->status,
            'status_label' => $status?->label(),
            'recipient_name' => $item->delivery_recipient_name,
            'recipient_phone' => $item->delivery_recipient_phone,
            'pickup_code' => $item->pickup_code,
            'shelf_location' => $item->shelf_location,
            'arrived_at_hub_at' => $item->arrived_at_hub_at?->toIso8601String(),
            'dispatched_to_bus_at' => $item->dispatched_to_bus_at?->toIso8601String(),
            'released_at' => $item->released_at?->toIso8601String(),
            'hub' => $this->serializeHub($hub),
            'destination' => [
                'region' => $this->regionName($item->delivery_region_id),
                'district' => $this->districtName($item->delivery_district_id),
                'town' => $item->delivery_town,
            ],
        ];
    }

    private function regionName(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        return $this->regionNames[$id] ??= Region::query()->whereKey($id)->value('name');
    }

    private function districtName(?int $id): ?string
    {
        if (! $id) {
            return null;
        }

        return $this->districtNames[$id] ??= District::query()->whereKey($id)->value('name');
    }

    private function destinationLabel(?int $regionId, ?int $districtId): ?string
    {
        $parts = [$this->districtName($districtId), $this->regionName($regionId)];

        return collect($parts)->filter()->implode(', ') ?: null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $received
     * @param  array<int, array<string, mixed>>  $alreadyAtHub
     */
    private function intakeMessage(array $received, array $alreadyAtHub, ?OutgoingBatch $batch): string
    {
        $parts = [];

        if ($received) {
            $parts[] = count($received).' '.str('package')->plural(count($received)).' checked in';
        }

        if ($alreadyAtHub) {
            $parts[] = count($alreadyAtHub).' already at this hub';
        }

        if (! $parts) {
            $parts[] = 'Nothing to check in';
        }

        return implode(', ', $parts).($batch ? " for batch {$batch->batch_number}" : '').'.';
    }

    private function failed(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
