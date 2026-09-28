<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\HubBusHandoff;
use App\Models\Region;
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

        $item = $this->findItemByCode($validated['code']);

        if (! $item) {
            return $this->failed("No package found for {$validated['code']}.", 404);
        }

        if ($error = $this->service->eligibilityError($hub, $item)) {
            return response()->json([
                'success' => false,
                'message' => $error['message'],
                'data' => ['package' => $this->parcelPayload($item, $hub)],
            ], $error['status']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'package' => $this->parcelPayload($item, $hub),
                'handoff' => null,
            ],
        ]);
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
            $item = $this->findItemByCode($validated['code']);
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
