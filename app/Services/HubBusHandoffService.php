<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Helpers\PhoneHelper;
use App\Models\HubBusHandoff;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Handing a single parcel from a hub to an external bus driver.
 *
 * The bus handoff agent scans a parcel, photographs the handover, and types in
 * the driver's and vehicle's details — the driver is not a ParcelMan user, so
 * nothing here links to a `drivers` row. Submitting the form writes the record
 * the admin sees, moves the parcel to `dispatched_to_bus`, and texts the
 * customer a link where they can look at the handover photo themselves.
 *
 * The link is a random token stored only as a hash, with its own expiry, so the
 * customer needs no account and the link cannot be guessed or reused forever.
 */
class HubBusHandoffService
{
    /**
     * How long a customer's photo link stays valid.
     */
    public const LINK_TTL_DAYS = 30;

    /**
     * Where handover photographs are kept: `storage/app/public/handovers/`.
     *
     * Every proof photo — single parcel or whole load — lands here, so the
     * served URL is always `/storage/handovers/<file>`.
     */
    public const PHOTO_DIRECTORY = 'handovers';

    /**
     * How long the photo itself is signed for when the page renders. Long enough
     * to read the page, short enough that a leaked URL is not a permanent leak.
     */
    public const PHOTO_URL_TTL_MINUTES = 60;

    /**
     * Statuses a parcel can be handed to a bus from.
     *
     * Wider than `HubController::AT_HUB_STATUSES` on purpose. That constant
     * answers "what is sitting in my hub's inventory" and stays narrow; this one
     * answers "what am I allowed to put on a bus", which also covers a parcel
     * taken in over the counter at the main office (`at_warehouse`) and one
     * already sorted against its destination and waiting for a departure.
     *
     * `pending` is only ever allowed alongside positive proof that the parcel is
     * physically at this hub — see `isAtHub()`. A parcel that was never checked
     * in anywhere must not be dispatchable: that is how freight goes missing
     * with no custody record to trace it by.
     *
     * @var array<int, ItemStatus>
     */
    private const BUS_HANDOFF_STATUSES = [
        ItemStatus::AT_WAREHOUSE,
        ItemStatus::ARRIVED_AT_HUB,
        ItemStatus::SORTED,
        ItemStatus::READY_FOR_HUB_TRANSFER,
        ItemStatus::PENDING,
    ];

    public function __construct(
        private SmsService $smsService,
        private StorageService $storageService,
    ) {}

    /**
     * Why this parcel cannot be handed over, or null when it can.
     *
     * Checked before the form is filled in as well as on submit, so the agent
     * finds out immediately rather than after taking a photo.
     *
     * @return array{message: string, status: int}|null
     */
    public function eligibilityError(Warehouse $hub, ShipmentItem $item): ?array
    {
        // The thing an agent can actually act on. A parcel that has not been
        // given a tracking code yet is named by its id rather than shown blank.
        $label = filled($item->tracking_code) ? $item->tracking_code : "Parcel #{$item->id}";

        $status = $item->status instanceof ItemStatus
            ? $item->status
            : ItemStatus::tryFrom((string) $item->status);

        if (! $this->isAtHub($hub, $item)) {
            // Somewhere else we can name: point at the right hub instead of
            // pretending the parcel does not exist.
            if ($heldAt = $this->heldAtHubName($hub, $item)) {
                return [
                    'message' => "{$label} is held at {$heldAt}, not {$hub->name}. A parcel is handed to a bus at the hub holding it.",
                    'status' => 403,
                ];
            }

            // No custody record anywhere. The useful instruction is "intake it",
            // not a flat statement that it is missing from inventory — which is
            // what used to leave the agent with no idea what to do next.
            return [
                'message' => "{$label} must be intake-scanned at {$hub->name} before it can be handed to a bus."
                    .($status ? " It is currently {$status->label()}." : ' Its status could not be read.'),
                'status' => 422,
            ];
        }

        if (! $status || ! in_array($status, self::BUS_HANDOFF_STATUSES, true)) {
            return [
                'message' => $this->statusRefusal($label, $status, $hub),
                'status' => 422,
            ];
        }

        $existing = HubBusHandoff::query()
            ->where('shipment_item_id', $item->id)
            ->latest('id')
            ->first();

        if ($existing) {
            return [
                'message' => "{$label} was already handed to a bus driver on "
                    .$existing->created_at?->format('j M Y, H:i').'.',
                'status' => 422,
            ];
        }

        return null;
    }

    /**
     * Is this parcel physically at this hub?
     *
     * There are two ways for a parcel to be here, because parcels arrive two
     * ways, and only one of them touches `shipment_items.hub_id`:
     *
     *  1. Hub intake — a transporter's batch is scanned in and `hub_id` is set
     *     on every parcel in it.
     *  2. Over the counter — a walk-in is booked at the main office. It gets a
     *     finalized warehouse receipt naming the warehouse it was taken in at,
     *     and `hub_id` is never touched. This is why walk-in parcels were being
     *     turned away: the check only ever looked at `hub_id`.
     *
     * Reading the receipt is what makes "at the main office" provable rather
     * than assumed, so a parcel sitting at another warehouse cannot be handed
     * over from this one.
     */
    private function isAtHub(Warehouse $hub, ShipmentItem $item): bool
    {
        // `hub_id` wins when it is set. It is the more recent fact: a parcel
        // taken in over the counter at Accra Main and *later* received at Kumasi
        // keeps its Accra receipt, and must not still count as being at Accra.
        if (filled($item->hub_id)) {
            return (int) $item->hub_id === (int) $hub->id;
        }

        // Never checked into a hub, so the warehouse receipt is the only record
        // of where the parcel physically is.
        return $item->warehouseReceiptItems()
            ->whereHas('receipt', function ($query) use ($hub) {
                $query->where('warehouse_id', $hub->id)
                    ->where('status', WarehouseReceipt::STATUS_FINALIZED);
            })
            ->exists();
    }

    /**
     * The name of the other hub this parcel is being held at, if we can name one.
     *
     * Used to say "it is at Kumasi Center" instead of a bare refusal.
     */
    private function heldAtHubName(Warehouse $hub, ShipmentItem $item): ?string
    {
        if (filled($item->hub_id)) {
            return (int) $item->hub_id === (int) $hub->id
                ? null
                : ($item->hub?->name ?: 'another hub');
        }

        $viaReceipt = $item->warehouseReceiptItems()
            ->whereHas('receipt', function ($query) use ($hub) {
                $query->where('warehouse_id', '!=', $hub->id)
                    ->where('status', WarehouseReceipt::STATUS_FINALIZED);
            })
            ->with('receipt.warehouse')
            ->get()
            ->map(fn (WarehouseReceiptItem $receiptItem) => $receiptItem->receipt?->warehouse?->name)
            ->filter()
            ->first();

        return $viaReceipt ?: null;
    }

    /**
     * Why this status cannot go on a bus, in words the agent can act on.
     */
    private function statusRefusal(string $label, ?ItemStatus $status, Warehouse $hub): string
    {
        if (! $status) {
            return "{$label} has a status this system does not recognise, so it cannot be handed to a bus. "
                .'Ask an administrator to check the parcel record.';
        }

        return match ($status) {
            ItemStatus::DISPATCHED_TO_BUS, ItemStatus::IN_TRANSIT => "{$label} is already on its way "
                ."({$status->label()}). It cannot be handed to a second bus.",

            ItemStatus::DELIVERED => "{$label} has already been delivered and cannot go on a bus.",

            ItemStatus::OUT_FOR_DELIVERY, ItemStatus::AT_DESTINATION, ItemStatus::HANDED_TO_COURIER => "{$label} has "
                ."already left for delivery ({$status->label()}) and cannot go on a bus.",

            default => "{$label} is {$status->label()} and cannot be handed to a bus from {$hub->name}. "
                .'A parcel goes on a bus from the warehouse counter, or once it has been received at the hub.',
        };
    }

    /**
     * Record the handover: photo, driver, vehicle, and the customer's link.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{success: bool, message: string, status?: int, data?: array<string, mixed>}
     */
    public function handOver(
        Warehouse $hub,
        User $agent,
        ShipmentItem $item,
        array $attributes,
        UploadedFile $photo,
        ?string $destination = null
    ): array {
        if ($error = $this->eligibilityError($hub, $item)) {
            return ['success' => false, 'message' => $error['message'], 'status' => $error['status']];
        }

        $handoff = DB::transaction(function () use ($hub, $agent, $item, $attributes, $photo, $destination) {
            // Re-check inside the transaction: two agents scanning the same
            // parcel at the same moment must not both write a handoff.
            $item = ShipmentItem::query()->whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($error = $this->eligibilityError($hub, $item)) {
                return $error;
            }

            // One flat folder for handover evidence, at the path the ops team
            // looks in: storage/app/public/handovers/<file>, served from
            // https://new.parcelmanexpress.com/storage/handovers/<file>. The
            // parcel and hub a photo belongs to are on the handoff record.
            $upload = $this->storageService->upload($photo, self::PHOTO_DIRECTORY);

            $now = now();

            $handoff = HubBusHandoff::query()->create([
                'shipment_item_id' => $item->id,
                'hub_id' => $hub->id,
                'outgoing_batch_id' => $item->outgoing_batch_id,
                'handed_off_by' => $agent->id,
                'driver_name' => $attributes['driver_name'],
                'driver_phone' => $attributes['driver_phone'] ?? null,
                'driver_id_number' => $attributes['driver_id_number'] ?? null,
                'vehicle_plate' => $attributes['vehicle_plate'] ?? null,
                'vehicle_description' => $attributes['vehicle_description'] ?? null,
                'bus_company' => $attributes['bus_company'] ?? null,
                'destination' => $destination,
                'departure_at' => $attributes['departure_at'] ?? $now,
                'proof_photo_path' => $upload['path'],
                'proof_photo_size' => $upload['size'] ?? null,
                'proof_photo_taken_at' => $attributes['photo_taken_at'] ?? $now,
                'notes' => $attributes['notes'] ?? null,
            ]);

            $item->update([
                'status' => ItemStatus::DISPATCHED_TO_BUS->value,
                'dispatched_to_bus_at' => $now,
            ]);

            ShipmentItemTracking::query()->create([
                'shipment_item_id' => $item->id,
                'status' => ItemStatus::DISPATCHED_TO_BUS->value,
                'location' => $hub->name,
                'notes' => "Handed to bus driver {$handoff->driver_name}"
                    .($handoff->vehicle_plate ? " ({$handoff->vehicle_plate})" : '')
                    .($destination ? " for {$destination}" : ''),
                'meta' => array_filter([
                    'source' => 'hub_bus_handoff',
                    'handoff_id' => $handoff->id,
                    'bus_company' => $handoff->bus_company,
                    'driver_name' => $handoff->driver_name,
                    'vehicle_plate' => $handoff->vehicle_plate,
                    'proof_photo_path' => $handoff->proof_photo_path,
                    'departure_at' => $handoff->departure_at?->toIso8601String(),
                ], fn ($value) => $value !== null && $value !== ''),
                'created_by' => $agent->id,
                'created_at' => $now,
            ]);

            return $handoff;
        });

        // The lock re-check can return an error array instead of a model.
        if (is_array($handoff)) {
            return ['success' => false, 'message' => $handoff['message'], 'status' => $handoff['status']];
        }

        $sms = $this->notifyCustomer($handoff);

        return [
            'success' => true,
            'message' => $sms['sent']
                ? 'Handover recorded and the customer has been texted the photo.'
                : 'Handover recorded. The customer could not be texted — see the handover record for details.',
            'data' => [
                'handoff' => $this->payload($handoff->fresh()),
                'sms' => $sms,
            ],
        ];
    }

    /**
     * Hand a whole consignment to one bus: many parcels, one photo, one load.
     *
     * The photo is taken once and shared by every parcel's record — it is the
     * same physical handover, and copying the bytes per parcel would only waste
     * storage. Each parcel still gets its own `hub_bus_handoffs` row, because
     * each one has its own recipient, its own link, and its own SMS outcome to
     * report on.
     *
     * Parcels that cannot go on this bus (not at this hub, not in a dispatchable
     * status, already handed over) are skipped and named in the result rather
     * than failing the whole dispatch — a hub does not hold up a loaded bus for
     * one parcel that is not in the pile.
     *
     * @param  \Illuminate\Support\Collection<int, ShipmentItem>  $items
     * @return array{success: bool, message: string, status: int, data?: array}
     */
    public function handOverBatch(
        Warehouse $hub,
        User $agent,
        $items,
        array $attributes,
        UploadedFile $photo,
        ?string $destination = null,
        ?string $groupLabel = null
    ): array {
        $notify = [];   // handoffs to text once the transaction has committed
        $skipped = [];

        $created = DB::transaction(function () use ($hub, $agent, $items, $attributes, $photo, $destination, $groupLabel, &$notify, &$skipped) {
            // One upload for the load. Grouped under the batch when there is one
            // so the admin's file listing lines up with the dispatch.
            // Same folder as a single handover: one photo per load, shared by
            // every parcel's record.
            $upload = $this->storageService->upload($photo, self::PHOTO_DIRECTORY);

            $now = now();
            $handoffs = [];

            foreach ($items as $item) {
                $locked = ShipmentItem::query()->whereKey($item->id)->lockForUpdate()->first();

                if (! $locked) {
                    $skipped[] = "{$item->tracking_code}: no longer exists";

                    continue;
                }

                if ($error = $this->eligibilityError($hub, $locked)) {
                    $skipped[] = "{$locked->tracking_code}: {$error['message']}";

                    continue;
                }

                $handoff = HubBusHandoff::query()->create([
                    'shipment_item_id' => $locked->id,
                    'hub_id' => $hub->id,
                    'outgoing_batch_id' => $locked->outgoing_batch_id,
                    'handed_off_by' => $agent->id,
                    'driver_name' => $attributes['driver_name'],
                    'driver_phone' => $attributes['driver_phone'] ?? null,
                    'driver_id_number' => $attributes['driver_id_number'] ?? null,
                    'vehicle_plate' => $attributes['vehicle_plate'] ?? null,
                    'vehicle_description' => $attributes['vehicle_description'] ?? null,
                    'bus_company' => $attributes['bus_company'] ?? null,
                    'destination' => $destination,
                    'departure_at' => $attributes['departure_at'] ?? $now,
                    'proof_photo_path' => $upload['path'],
                    'proof_photo_size' => $upload['size'] ?? null,
                    'proof_photo_taken_at' => $attributes['photo_taken_at'] ?? $now,
                    'notes' => $attributes['notes'] ?? null,
                ]);

                $locked->update([
                    'status' => ItemStatus::DISPATCHED_TO_BUS->value,
                    'dispatched_to_bus_at' => $now,
                ]);

                ShipmentItemTracking::query()->create([
                    'shipment_item_id' => $locked->id,
                    'status' => ItemStatus::DISPATCHED_TO_BUS->value,
                    'location' => $hub->name,
                    'notes' => 'Handed to bus driver '.$handoff->driver_name
                        .($handoff->vehicle_plate ? " ({$handoff->vehicle_plate})" : '')
                        .($destination ? " for {$destination}" : '')
                        .($groupLabel ? " with {$groupLabel}" : ''),
                    'meta' => array_filter([
                        'source' => 'hub_bus_handoff_batch',
                        'handoff_id' => $handoff->id,
                        'group' => $groupLabel,
                        'bus_company' => $handoff->bus_company,
                        'driver_name' => $handoff->driver_name,
                        'vehicle_plate' => $handoff->vehicle_plate,
                        'proof_photo_path' => $handoff->proof_photo_path,
                        'departure_at' => $handoff->departure_at?->toIso8601String(),
                    ], fn ($value) => $value !== null && $value !== ''),
                    'created_by' => $agent->id,
                    'created_at' => $now,
                ]);

                $handoffs[] = $handoff;
                $notify[] = $handoff;
            }

            return $handoffs;
        });

        if (empty($created)) {
            return [
                'success' => false,
                'message' => $skipped
                    ? 'Nothing could be dispatched. '.implode(' ', array_slice($skipped, 0, 3))
                    : 'There was nothing in this selection to dispatch.',
                'status' => 422,
            ];
        }

        // Text each recipient their own link, after the dispatch has committed.
        $smsSent = 0;

        foreach ($notify as $handoff) {
            if (($this->notifyCustomer($handoff)['sent'] ?? false) === true) {
                $smsSent++;
            }
        }

        $dispatched = count($created);
        $message = $dispatched.' '.Str::plural('parcel', $dispatched).' handed to '
            .($attributes['driver_name'] ?? 'the bus driver')
            .($groupLabel ? " with {$groupLabel}" : '').'.';

        if ($smsSent > 0) {
            $message .= " {$smsSent} ".Str::plural('recipient', $smsSent).' texted the handover photo.';
        }

        if ($skipped) {
            $message .= ' Skipped '.count($skipped).': '.implode(' ', array_slice($skipped, 0, 3));
        }

        return [
            'success' => true,
            'message' => $message,
            'status' => 201,
            'data' => [
                'handoffs' => collect($created)
                    ->map(fn (HubBusHandoff $handoff) => $this->payload($handoff->fresh()))
                    ->values()
                    ->all(),
                'dispatched_count' => $dispatched,
                'sms_sent_count' => $smsSent,
                'skipped' => $skipped,
            ],
        ];
    }

    /**
     * Text the customer a link to the handover photo.
     *
     * @return array{sent: bool, phone: string|null, error: string|null}
     */
    public function notifyCustomer(HubBusHandoff $handoff): array
    {
        $handoff->loadMissing('shipmentItem');

        $phone = $handoff->shipmentItem?->delivery_recipient_phone;

        if (blank($phone)) {
            $error = 'The parcel has no recipient phone number.';
            $handoff->update(['sms_failed_at' => now(), 'sms_error' => $error]);

            return ['sent' => false, 'phone' => null, 'error' => $error];
        }

        $token = $this->issuePublicLink($handoff);
        $link = $this->urlForToken($token);
        $tracking = $handoff->shipmentItem?->tracking_code ?: 'your package';

        $message = "ParcelMan: {$tracking} has been handed to the bus. "
            .'See the photo of the handover: '.$link;

        try {
            $sent = $this->smsService->send(
                PhoneHelper::format($phone) ?? $phone,
                $message
            );
        } catch (\Throwable $e) {
            $sent = false;
            report($e);
        }

        $handoff->update($sent
            ? ['sms_sent_at' => now(), 'sms_failed_at' => null, 'sms_error' => null]
            : ['sms_failed_at' => now(), 'sms_error' => 'The SMS provider did not accept the message.']);

        return [
            'sent' => $sent,
            'phone' => $phone,
            'error' => $sent ? null : 'The SMS provider did not accept the message.',
        ];
    }

    /**
     * Mint a fresh public token for this handoff and store only its hash.
     *
     * The plain token is returned once, for the SMS; it is never recoverable.
     */
    public function issuePublicLink(HubBusHandoff $handoff): string
    {
        do {
            $token = Str::upper(Str::random(12));
        } while (HubBusHandoff::query()->where('public_token_hash', $this->hashValue($token))->exists());

        $handoff->update([
            'public_token_hash' => $this->hashValue($token),
            'public_token_expires_at' => now()->addDays(self::LINK_TTL_DAYS),
        ]);

        return $token;
    }

    /**
     * The link we text the customer.
     */
    public function urlForToken(string $token): string
    {
        $configuredUrl = (string) (config('app.public_url') ?: config('app.url'));
        $parts = parse_url($configuredUrl);

        if (is_array($parts) && ! empty($parts['scheme']) && ! empty($parts['host'])) {
            $baseUrl = $parts['scheme'].'://'.$parts['host'];

            if (! empty($parts['port'])) {
                $baseUrl .= ':'.$parts['port'];
            }
        } else {
            $baseUrl = request()->getSchemeAndHttpHost();
        }

        return rtrim($baseUrl, '/').'/handover/'.rawurlencode($token);
    }

    /**
     * Resolve a customer's link, or null when it is unknown or expired.
     */
    public function findByToken(string $token): ?HubBusHandoff
    {
        if (blank($token)) {
            return null;
        }

        return HubBusHandoff::query()
            ->with(['shipmentItem.shipment', 'hub', 'handedOffBy'])
            ->where('public_token_hash', $this->hashValue($token))
            ->where('public_token_expires_at', '>', now())
            ->first();
    }

    /**
     * A URL the browser can load the stored photo from.
     *
     * `StorageService::getUrl` already returns a short-lived signed URL when the
     * disk is private S3, so the photo is not world-readable.
     */
    public function photoUrl(HubBusHandoff $handoff, ?int $expiryMinutes = null): ?string
    {
        if (blank($handoff->proof_photo_path)) {
            return null;
        }

        try {
            return $this->storageService->getUrl($handoff->proof_photo_path);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Serialise a handoff.
     *
     * `$includePhotoUrl` exists for listing pages: minting a signed photo URL per
     * row is wasteful when the table only shows a thumbnail badge, so the detail
     * screens ask for it and the listings do not.
     *
     * @return array<string, mixed>
     */
    public function payload(HubBusHandoff $handoff, bool $public = false, bool $includePhotoUrl = true): array
    {
        $item = $handoff->shipmentItem;

        $payload = [
            'id' => $handoff->id,
            'handed_off_at' => $handoff->created_at?->toIso8601String(),
            'departure_at' => $handoff->departure_at?->toIso8601String(),
            'driver' => [
                'name' => $handoff->driver_name,
                'phone' => $public ? $this->maskPhone($handoff->driver_phone) : $handoff->driver_phone,
                'id_number' => $public ? null : $handoff->driver_id_number,
            ],
            'vehicle' => [
                'plate' => $handoff->vehicle_plate,
                'description' => $handoff->vehicle_description,
                'company' => $handoff->bus_company,
            ],
            'destination' => $handoff->destination,
            'has_proof_photo' => filled($handoff->proof_photo_path),
            'proof_photo_url' => $includePhotoUrl ? $this->photoUrl($handoff) : null,
            'notes' => $handoff->notes,
            'sms' => $public ? null : [
                'sent_at' => $handoff->sms_sent_at?->toIso8601String(),
                'failed_at' => $handoff->sms_failed_at?->toIso8601String(),
                'error' => $handoff->sms_error,
            ],
            'hub' => [
                'id' => $handoff->hub?->id,
                'name' => $handoff->hub?->name,
                'code' => $handoff->hub?->code,
            ],
            'package' => [
                'id' => $item?->id,
                'tracking_code' => $item?->tracking_code,
                'description' => $item?->description,
            ],
            'recipient' => [
                'name' => $item?->delivery_recipient_name,
                'phone' => $public
                    ? $this->maskPhone($item?->delivery_recipient_phone)
                    : $item?->delivery_recipient_phone,
                'town' => $item?->delivery_town,
            ],
            'public' => $public,
        ];

        if (! $public) {
            $payload['handed_off_by'] = [
                'id' => $handoff->handedOffBy?->id,
                'name' => $handoff->handedOffBy?->name,
            ];
        }

        return $payload;
    }

    private function maskPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', (string) $phone) ?? '';

        if (strlen($digits) < 5) {
            return '••••';
        }

        return substr($digits, 0, 3).'•••'.substr($digits, -2);
    }

    private function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }
}
