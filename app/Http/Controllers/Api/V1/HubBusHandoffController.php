<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\HubBusHandoff;
use App\Models\Region;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use App\Services\HubBusHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The bus handoff agent's side of the hub app.
 *
 * Their job is a single parcel at a time: scan it, photograph the handover to
 * the external bus driver, type in the driver's and vehicle's details, submit.
 * Everything here is scoped to the hub of the signed in user, which
 * `EnsureHubAgent` guarantees is assigned.
 */
class HubBusHandoffController extends Controller
{
    public function __construct(private HubBusHandoffService $service) {}

    /**
     * What the agent scanned, and whether it can be handed over.
     *
     * Called as soon as a barcode is read so the form opens for that parcel, and
     * so an ineligible parcel is refused before anyone takes a photo.
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:120'],
        ]);

        $hub = $this->hub($request);

        if (! $hub) {
            return $this->failed('No hub is assigned to this account.', 403);
        }

        $match = $this->resolveScannableParcel($validated['code'], $hub);

        if (! $match['item']) {
            return $this->failed($this->notFoundMessage($validated['code']), 404);
        }

        $item = $match['item'];

        if ($error = $this->service->eligibilityError($hub, $item)) {
            return response()->json([
                'success' => false,
                'message' => $error['message'],
                'data' => $this->lookupData($item, $hub, $match),
            ], $error['status']);
        }

        return response()->json([
            'success' => true,
            'data' => $this->lookupData($item, $hub, $match) + ['handoff' => null],
        ]);
    }

    /**
     * The parcel a scanned code refers to, looking inside a shipment if needed.
     *
     * A parcel tracking code names a parcel directly. A shipment number names a
     * *shipment*, which holds one to three parcels — so a hub agent scanning the
     * consignment note is holding the right paperwork but the wrong kind of code.
     * Following it through to its parcels is the difference between a dead-end
     * "no package found" and actually handing the parcel over.
     *
     * @return array{item: ?ShipmentItem, matched_by: ?string, shipment: ?Shipment, siblings: \Illuminate\Support\Collection<int, ShipmentItem>}
     */
    private function resolveScannableParcel(string $code, Warehouse $hub): array
    {
        $item = $this->findItemByCode($code);

        if ($item) {
            return [
                'item' => $item,
                'matched_by' => 'tracking_code',
                'shipment' => null,
                'siblings' => collect(),
            ];
        }

        $siblings = CodeResolver::resolveShipmentItemsByShipmentNumber($code);

        if ($siblings->isEmpty()) {
            return ['item' => null, 'matched_by' => null, 'shipment' => null, 'siblings' => collect()];
        }

        return [
            'item' => $this->preferredParcel($hub, $siblings),
            'matched_by' => 'shipment_number',
            'shipment' => CodeResolver::resolveShipment($code),
            'siblings' => $siblings,
        ];
    }

    /**
     * Which parcel of a shipment to hand over first.
     *
     * A shipment can hold several. Prefer one that can go on a bus right now;
     * failing that, one that at least belongs to this hub; failing that, the
     * first. And never silently — the response says how many there were.
     *
     * @param  \Illuminate\Support\Collection<int, ShipmentItem>  $items
     */
    private function preferredParcel(Warehouse $hub, $items): ?ShipmentItem
    {
        $ready = $items->first(
            fn (ShipmentItem $candidate) => $this->service->eligibilityError($hub, $candidate) === null
        );

        if ($ready) {
            return $ready;
        }

        return $items->first(fn (ShipmentItem $candidate) => (int) $candidate->hub_id === (int) $hub->id)
            ?? $items->first();
    }

    /**
     * @param  array{item: ?ShipmentItem, matched_by: ?string, shipment: ?Shipment, siblings: \Illuminate\Support\Collection<int, ShipmentItem>}  $match
     * @return array<string, mixed>
     */
    private function lookupData(ShipmentItem $item, Warehouse $hub, array $match): array
    {
        $data = ['package' => $this->parcelPayload($item, $hub)];

        if (($match['matched_by'] ?? null) !== 'shipment_number') {
            return $data;
        }

        $siblings = $match['siblings'] ?? collect();

        // So the screen can say "parcel 2 of 3" instead of pretending the
        // shipment number was the parcel all along.
        $data['matched_by'] = 'shipment_number';
        $data['shipment_number'] = $match['shipment']?->shipment_number;
        $data['shipment_parcel_count'] = $siblings->count();
        $data['shipment_parcels'] = $siblings
            ->map(function (ShipmentItem $sibling) use ($item) {
                $status = $sibling->status instanceof ItemStatus
                    ? $sibling->status
                    : ItemStatus::tryFrom((string) $sibling->status);

                return [
                    'id' => (string) $sibling->id,
                    'tracking_code' => $sibling->tracking_code,
                    'status' => $status?->value,
                    'status_label' => $status?->label(),
                    'is_this_one' => (int) $sibling->id === (int) $item->id,
                ];
            })
            ->values()
            ->all();

        return $data;
    }

    /**
     * Why a scanned code matched nothing.
     *
     * A shipment number is a different kind of code from a parcel tracking code,
     * so say which one was expected rather than leaving the agent to guess.
     */
    private function notFoundMessage(string $code): string
    {
        if (CodeResolver::family($code) === 'shipment') {
            return "No shipment found for {$code}. Check the number, or scan the parcel's own barcode.";
        }

        return "No package found for {$code}.";
    }

    /**
     * Record the handover: photo, driver, vehicle. Texts the customer.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:120'],
            'package_id' => ['nullable', 'integer'],
            'driver_name' => ['required', 'string', 'max:120'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'driver_id_number' => ['nullable', 'string', 'max:60'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'vehicle_description' => ['nullable', 'string', 'max:120'],
            'bus_company' => ['nullable', 'string', 'max:120'],
            'departure_at' => ['nullable', 'date'],
            'photo_taken_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            // The photo is the point of the flow: no photo, no handover.
            'proof_photo' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $user = $request->user();
        $hub = $this->hub($request);

        if (! $hub) {
            return $this->failed('No hub is assigned to this account.', 403);
        }

        $item = null;

        if (! empty($validated['package_id'])) {
            $item = ShipmentItem::query()->whereKey((int) $validated['package_id'])->first();
        } elseif (! empty($validated['code'])) {
            // Same resolution the scan uses, so a shipment number submitted
            // directly still lands on the right parcel.
            $item = $this->resolveScannableParcel($validated['code'], $hub)['item'];
        }

        if (! $item) {
            return $this->failed('Scan or select the package being handed over.', 422);
        }

        $result = $this->service->handOver(
            $hub,
            $user,
            $item,
            $validated,
            $request->file('proof_photo'),
            $this->destinationLabel($item)
        );

        if (! ($result['success'] ?? false)) {
            return $this->failed($result['message'], $result['status'] ?? 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'],
        ], 201);
    }

    /**
     * Handovers this hub has recorded, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        $hub = $this->hub($request);

        if (! $hub) {
            return $this->failed('No hub is assigned to this account.', 403);
        }

        // A bus handoff agent sees the handovers they made; a hub agent sees the
        // whole hub's, since they run the warehouse.
        $query = HubBusHandoff::query()
            ->with(['shipmentItem.shipment', 'hub', 'handedOffBy'])
            ->where('hub_id', $hub->id);

        if ($this->isBusHandoffOnly($request)) {
            $query->where('handed_off_by', $user->id);
        }

        if ($search = trim((string) ($validated['search'] ?? ''))) {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('driver_name', 'like', "%{$search}%")
                    ->orWhere('vehicle_plate', 'like', "%{$search}%")
                    ->orWhere('bus_company', 'like', "%{$search}%")
                    ->orWhereHas('shipmentItem', fn ($itemQuery) => $itemQuery
                        ->where('tracking_code', 'like', CodeResolver::likeTerm($search))
                        ->orWhere('delivery_recipient_name', 'like', "%{$search}%")
                        ->orWhere('delivery_recipient_phone', 'like', "%{$search}%")
                    );
            });
        }

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);
        $total = (clone $query)->count();

        $rows = $query
            ->latest('created_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (HubBusHandoff $handoff) => $this->service->payload($handoff))
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'handoffs' => $rows,
                'pagination' => [
                    'offset' => $offset,
                    'limit' => $limit,
                    'total' => $total,
                    'has_more' => ($offset + $rows->count()) < $total,
                    'next_offset' => ($offset + $rows->count()) < $total ? $offset + $limit : null,
                ],
                'summary' => [
                    'total' => $total,
                    'today' => (clone $query)->whereDate('created_at', today())->count(),
                    // Counted across every handover in the database, not just the
                    // page that was returned. The app used to derive this from the
                    // loaded rows, so the tile read "0" for a hub with 40 untethered
                    // handovers once only 20 of them were on the page.
                    'not_texted' => (clone $query)->whereNull('sms_sent_at')->count(),
                ],
            ],
        ]);
    }

    /**
     * One handover, with its photo.
     */
    public function show(Request $request, HubBusHandoff $handoff): JsonResponse
    {
        $hub = $this->hub($request);

        if (! $hub) {
            return $this->failed('No hub is assigned to this account.', 403);
        }

        if ((int) $handoff->hub_id !== (int) $hub->id) {
            return $this->failed('That handover belongs to another hub.', 403);
        }

        $handoff->load(['shipmentItem.shipment', 'hub', 'handedOffBy']);

        return response()->json([
            'success' => true,
            'data' => ['handoff' => $this->service->payload($handoff)],
        ]);
    }

    /**
     * The hub of the signed in user.
     */
    private function hub(Request $request): ?Warehouse
    {
        return $request->user()?->warehouse;
    }

    /**
     * Is this account only a bus handoff agent (so it sees just its own work)?
     */
    private function isBusHandoffOnly(Request $request): bool
    {
        $slugs = $request->user()?->roles
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug)) ?? collect();

        return $slugs->contains('external_bus_handoff_agent')
            && ! $slugs->contains('external_hub_agent');
    }

    /**
     * The parcel a scanned code refers to, under any prefix scheme.
     */
    private function findItemByCode($code): ?ShipmentItem
    {
        return CodeResolver::resolveShipmentItem(is_scalar($code) ? (string) $code : null);
    }

    /**
     * @return array<string, mixed>
     */
    private function parcelPayload(ShipmentItem $item, Warehouse $hub): array
    {
        return [
            'id' => (string) $item->id,
            'tracking_code' => $item->tracking_code,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'status' => $item->status instanceof \App\Enums\ItemStatus
                ? $item->status->value
                : (string) $item->status,
            'recipient_name' => $item->delivery_recipient_name,
            'recipient_phone' => $item->delivery_recipient_phone,
            'shelf_location' => $item->shelf_location,
            'destination' => $this->destinationLabel($item),
            'hub' => [
                'id' => $hub->id,
                'name' => $hub->name,
                'code' => $hub->code,
            ],
        ];
    }

    /**
     * Where this parcel is going, as a readable snapshot for the handover record.
     */
    private function destinationLabel(ShipmentItem $item): ?string
    {
        $parts = [];

        if ($item->delivery_district_id) {
            $parts[] = District::query()->whereKey($item->delivery_district_id)->value('name');
        }

        if ($item->delivery_region_id) {
            $parts[] = Region::query()->whereKey($item->delivery_region_id)->value('name');
        }

        if (blank($parts)) {
            $parts[] = $item->delivery_town;
        }

        return collect($parts)->filter()->implode(', ') ?: null;
    }

    private function failed(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $status);
    }
}
