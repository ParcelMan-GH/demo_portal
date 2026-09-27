<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Helpers\PhoneHelper;
use App\Models\HubBusHandoff;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Warehouse;
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
     * How long the photo itself is signed for when the page renders. Long enough
     * to read the page, short enough that a leaked URL is not a permanent leak.
     */
    public const PHOTO_URL_TTL_MINUTES = 60;

    /**
     * Statuses that mean the parcel is physically sitting in the hub and can
     * therefore be put on a bus. Mirrors `HubController::AT_HUB_STATUSES`.
     *
     * @var array<int, ItemStatus>
     */
    private const AT_HUB_STATUSES = [ItemStatus::ARRIVED_AT_HUB, ItemStatus::AT_WAREHOUSE];

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
        if ((int) $item->hub_id !== (int) $hub->id) {
            return [
                'message' => "Package {$item->tracking_code} is not held at {$hub->name}.",
                'status' => 403,
            ];
        }

        $status = $item->status instanceof ItemStatus
            ? $item->status
            : ItemStatus::tryFrom((string) $item->status);

        if (! $status || ! in_array($status, self::AT_HUB_STATUSES, true)) {
            return [
                'message' => "Package {$item->tracking_code} is not in this hub"
                    .($status ? " (it is {$status->label()})" : '')
                    .'.',
                'status' => 422,
            ];
        }

        $existing = HubBusHandoff::query()
            ->where('shipment_item_id', $item->id)
            ->latest('id')
            ->first();

        if ($existing) {
            return [
                'message' => "Package {$item->tracking_code} was already handed to a bus driver on "
                    .$existing->created_at?->format('j M Y, H:i').'.',
                'status' => 422,
            ];
        }

        return null;
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

            $upload = $this->storageService->upload(
                $photo,
                "hubs/{$hub->id}/bus-handoffs/{$item->id}"
            );

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
