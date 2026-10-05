<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemStatus;
use App\Helpers\CodeResolver;
use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\OutgoingBatch;
use App\Models\Region;
use App\Models\ShipmentItem;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\HubBusHandoffService;
use App\Services\PushNotificationService;
use App\Services\SmsService;
use App\Services\StorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * The bus handoff side of the app. Batch dispatch goes through it so a
     * whole-load handover writes the same record and evidence as a single one.
     */
    public function __construct(
        private HubBusHandoffService $busHandoffs,
        private PushNotificationService $pushService,
    ) {}

    /**
     * Statuses that mean "this parcel is physically sitting in the hub".
     *
     * `at_warehouse` is included because the warehouse receiving flow already
     * uses it for exactly that state, before this API existed.
     *
     * @var array<int, ItemStatus>
     */
    /**
     * Which statuses count as "in the hub" now lives on the model, alongside the
     * location rule, so inventory and bus handoff cannot drift apart again.
     */
    private const AT_HUB_STATUSES = ShipmentItem::AT_HUB_STATUSES;

    /**
     * How long an intake will spend texting recipients before it stops and reports
     * the rest as deferred. A desk batch is small, but a pathological one must not
     * pin a PHP worker for minutes on a slow gateway.
     */
    private const INTAKE_SMS_BUDGET_SECONDS = 20;

    /**
     * A hard ceiling on pickup SMS per intake request, for the same reason as the
     * budget above: the two together bound the worst case however the clock lies.
     */
    private const INTAKE_SMS_MAX_PER_REQUEST = 40;

    /**
     * Role slugs, as the database knows them, for the two sides of the hub app.
     * See `EnsureHubAgent::ROLES`.
     */
    private const ROLE_HUB_AGENT = 'external_hub_agent';

    private const ROLE_BUS_HANDOFF = 'external_bus_handoff_agent';

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

        $slugs = $user->roles
            ->pluck('slug')
            ->map(fn ($slug) => strtolower((string) $slug));

        // The hub app has two sides — running the hub, and putting parcels on the
        // bus — and knows them in its own vocabulary. Login returns the side the
        // user signed in as; this lets a refresh work out which sides it may show
        // without the client having to remember.
        $appRoles = [];

        if ($slugs->contains(self::ROLE_HUB_AGENT)) {
            $appRoles[] = 'hub_agent';
        }

        if ($slugs->contains(self::ROLE_BUS_HANDOFF)) {
            $appRoles[] = 'bus_handoff';
        }

        return response()->json([
            'success' => true,
            'data' => [
                // The hub header renders the agent's own name, photo and hub, so
                // the server sends them rather than the screen inventing one.
                'user' => array_merge($this->serializeUser($user), [
                    // The side to open first, when the account holds both.
                    'role' => $appRoles[0] ?? null,
                    'roles' => $appRoles,
                ]),
                'hub' => $this->serializeHub($hub),
            ],
        ]);
    }

    /**
     * Everything the hub home screen needs in one call: who is signed in, which
     * hub they run, the live counters and the recent work feed.
     *
     * The figures mirror `inventory`, but are named for the dashboard tiles so
     * the home screen can draw three numbers without pulling the whole
     * inventory down.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $hub = $user->warehouse;

        if (! $hub) {
            return $this->failed('No hub is assigned to this account. Ask an administrator to assign you to a hub.', 403);
        }

        $counts = $this->hubCounts($hub);

        return response()->json([
            'success' => true,
            'data' => [
                'hub' => $this->serializeHub($hub),
                'user' => $this->serializeUser($user),
                'metrics' => [
                    'in_hub_count' => $counts['at_hub'],
                    'inbound_today_count' => $counts['received_today'],
                    'ready_for_bus_count' => $counts['ready_for_bus'],
                    'released_today_count' => $counts['released_today'],
                ],
                // Retained in the shape the hub screens already read.
                'counts' => $counts,
                /*
                 * Newest first, and empty for an account that has done nothing
                 * yet — now actually true. Scoped to this user's own work; the
                 * tiles above stay hub-wide on purpose.
                 */
                'recent_activities' => $this->recentActivities((int) $user->id, 8),
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
    public function intake(Request $request, SmsService $smsService): JsonResponse
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
                ->whereIn('batch_number', CodeResolver::candidates($validated['batch_number']))
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
                    "{$this->parcelLabel($item)} does not belong to batch {$batch->batch_number}.",
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
                            "{$this->parcelLabel($item)} does not belong to batch {$batch->batch_number}.",
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
        // Parcels whose pickup code still needs texting, gathered here and sent
        // only after every check-in write below has finished — see that loop for
        // why the send cannot live inside the write.
        $pendingNotifications = [];

        foreach ($items as $item) {
            // Re-scanning a parcel is normal at a busy desk: report it, don't
            // move it or stamp it twice.
            if ($item->isAtHub((int) $hub->id) && $item->arrived_at_hub_at) {
                $alreadyAtHub[] = $this->serializePackage($item, $hub);

                continue;
            }

            // Whether the code predates this intake decides the duplicate rule: a
            // code minted just now has never been texted, so it is always safe to
            // send, whereas one that already existed may have gone out on an
            // earlier arrival and must not be repeated.
            $codeExisted = filled($item->pickup_code);

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

            $pendingNotifications[] = ['item' => $item, 'code_existed' => $codeExisted];
        }

        // A batch is only "received" once every parcel in it is physically at
        // this hub. Scanning them one at a time is normal, so the batch flips on
        // the last one rather than on the first — and the check is idempotent.
        $batchStatus = $batch?->status;

        if ($batch) {
            $outstanding = $batch->shipmentItems()
                ->where(function ($query) use ($hub) {
                    // Not yet fully in: either it has no arrival stamp, or it is
                    // not at this hub by either route.
                    $query->whereNull('arrived_at_hub_at')
                        ->orWhere(function ($elsewhere) use ($hub) {
                            $elsewhere->notAtHub($hub->id);
                        });
                })
                ->count();

            if ($outstanding === 0 && $batch->status !== OutgoingBatch::STATUS_ARRIVED_AT_HUB) {
                $batch->update(['status' => OutgoingBatch::STATUS_ARRIVED_AT_HUB]);
            }

            $batchStatus = $batch->fresh()?->status;
        }

        // Text each recipient their pickup code, and only now that the check-in
        // rows are written. An SMS cannot be rolled back, so it must not sit in a
        // transaction alongside the check-in: if that transaction later failed,
        // customers would have been texted about parcels the hub never recorded.
        // Sent afterwards, a message can only ever describe a parcel that is
        // really on the shelf. Every failure is caught per parcel — the parcels
        // are physically here, and refusing the whole intake because a gateway is
        // down would be worse than the silence.
        $notified = 0;
        $notificationFailed = 0;
        $skippedNoPhone = 0;
        $alreadyNotified = 0;
        $deferred = 0;
        $attempts = 0;

        // Bounds the request so a batch far larger than a desk ever handles in one
        // go cannot pin a worker for minutes; anything left over is reported as
        // deferred, not lost, and can be sent from the Notify button on inventory.
        $deadline = microtime(true) + self::INTAKE_SMS_BUDGET_SECONDS;

        foreach ($pendingNotifications as $pending) {
            /** @var ShipmentItem $item */
            $item = $pending['item'];
            $phone = $item->delivery_recipient_phone;

            if (blank($phone)) {
                // No number on file: nothing to send, and never a reason to fail
                // the check-in. Counted so the desk can chase the recipient.
                $skippedNoPhone++;
                $smsStatus = 'skipped_no_phone';
            } elseif ($pending['code_existed'] && $this->pickupSmsAlreadySent($item)) {
                // A retry or re-scan whose existing code was already texted for
                // this arrival — do not send the same code twice.
                $alreadyNotified++;
                $smsStatus = 'already_notified';
            } elseif ($attempts >= self::INTAKE_SMS_MAX_PER_REQUEST || microtime(true) >= $deadline) {
                $deferred++;
                $smsStatus = 'deferred';
            } else {
                $attempts++;

                try {
                    $sent = $smsService->send($phone, sprintf(
                        'ParcelMan: your parcel %s has arrived at %s. Collect it with pickup code %s.',
                        $item->tracking_code ?: $item->id,
                        $hub->name,
                        $item->pickup_code
                    ));
                } catch (\Throwable $e) {
                    // A gateway blow-up must never undo a physical check-in.
                    Log::warning('Pickup SMS threw during hub intake', [
                        'shipment_item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);

                    $sent = false;
                }

                if ($sent) {
                    $notified++;
                    $smsStatus = 'sent';

                    // The desk gets the same code the recipient just received, so
                    // it can help confirm a delivery the agent cannot close.
                    $this->notifyAdminsOfPickupCode($item, $hub);
                } else {
                    $notificationFailed++;
                    $smsStatus = 'failed';
                }

                // Same audit shape notifyRecipient writes, so the activity feed
                // reads consistently and a later retry can see this code was sent.
                $this->logTracking(
                    $item,
                    ItemStatus::ARRIVED_AT_HUB,
                    $hub,
                    $sent
                        ? "Pickup code texted to {$item->delivery_recipient_name}"
                        : "Pickup code to {$item->delivery_recipient_name} could not be texted",
                    ['source' => 'hub_intake_sms', 'sent' => $sent],
                    (int) $user->id
                );
            }

            // The per-item outcome travels with the parcel so the app can tell the
            // truth about each one, not just the totals.
            $received[] = $this->serializePackage($item->fresh(), $hub) + [
                'sms_status' => $smsStatus,
            ];
        }

        $message = $this->intakeMessage($received, $alreadyAtHub, $batch);

        if ($batch && $batchStatus === OutgoingBatch::STATUS_ARRIVED_AT_HUB) {
            $message .= " Batch {$batch->batch_number} is now marked received at {$hub->name}.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'hub' => $this->serializeHub($hub),
                'batch_number' => $batch?->batch_number,
                'batch_status' => $batchStatus,
                'received_count' => count($received),
                'already_at_hub_count' => count($alreadyAtHub),
                // Pickup-code SMS outcome, so the app can report what really
                // happened instead of assuming every recipient was texted.
                'notified' => $notified,
                'notification_failed' => $notificationFailed,
                'skipped_no_phone' => $skippedNoPhone,
                'already_notified' => $alreadyNotified,
                'deferred' => $deferred,
                'packages' => $received,
                'already_at_hub' => $alreadyAtHub,
            ],
        ]);
    }

    /**
     * Text the recipient that their parcel is waiting, with the code they need
     * to collect it.
     *
     * A hub desk is often handed a parcel with no pickup code yet (it was
     * created before the hub existed), so one is allocated on the way out rather
     * than refusing to notify.
     */
    public function notifyRecipient(Request $request, string $package, SmsService $smsService): JsonResponse
    {
        $hub = $request->user()->warehouse;

        if (! $hub) {
            return $this->failed('No hub is assigned to this account. Ask an administrator to assign you to a hub.', 403);
        }

        $item = ShipmentItem::query()->atHub($hub->id)->find($package)
            ?? $this->findItemByCode($package);

        if (! $item || ! $item->isAtHub((int) $hub->id)) {
            return $this->failed("No package found for {$package} at this hub.", 404);
        }

        $phone = $item->delivery_recipient_phone;

        if (! $phone) {
            return $this->failed('This parcel has no recipient phone number on file.', 422);
        }

        if (! $item->pickup_code) {
            $item->forceFill(['pickup_code' => $this->generatePickupCode()])->save();
        }

        // Branded like the other customer texts in this codebase. Not
        // config('app.name') — that still reads "Laravel" here, and it also
        // derives the session cookie name, so it is not safe to flip casually.
        $sent = $smsService->send($phone, sprintf(
            'ParcelMan: your parcel %s has arrived at %s. Collect it with pickup code %s.',
            $item->tracking_code ?: $item->id,
            $hub->name,
            $item->pickup_code
        ));

        if ($sent) {
            // Same code to the desk, so an agent who cannot reach the recipient
            // can be helped without re-issuing the code.
            $this->notifyAdminsOfPickupCode($item, $hub);
        }

        $this->logTracking(
            $item,
            $item->status instanceof ItemStatus ? $item->status : ItemStatus::ARRIVED_AT_HUB,
            $hub,
            $sent
                ? "Pickup notification sent to {$item->delivery_recipient_name}"
                : "Pickup notification to {$item->delivery_recipient_name} could not be sent",
            ['source' => 'hub_notify_recipient', 'sent' => $sent],
            (int) $request->user()->id
        );

        return response()->json([
            'success' => $sent,
            'message' => $sent
                ? "Pickup notification sent to {$item->delivery_recipient_name}."
                : 'The pickup notification could not be sent. Check the SMS provider and try again.',
            'data' => [
                'sent' => $sent,
                'package' => $this->serializePackage($item->fresh(), $hub),
            ],
        ], $sent ? 200 : 502);
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

        $user = $request->user();
        $hub = $user->warehouse;
        $perPage = (int) ($validated['per_page'] ?? 30);

        /*
         * Scoped to the signed-in agent: only parcels they were assigned or have
         * handled, rather than everything standing at the hub.
         *
         * This follows the "Recent Work" decision, and the two are consistent —
         * the feed lists what the agent did, this lists what they still hold.
         *
         * Worth knowing before relying on it at a counter: of the 39 parcels at
         * this deployment's busiest hub, the agent who made the most recent
         * intakes is left with 2. A colleague who takes a parcel in is the only
         * one who will see it here, which is fine for a personal worklist and a
         * problem if the screen is being used to look up any parcel a customer
         * arrives for. The hub-wide counts on Home are unchanged and remain the
         * place to answer "is it here at all".
         */
        $query = ShipmentItem::query()
            ->atHub($hub->id)
            ->handledBy((int) $user->id);

        $statuses = $this->statusesFromFilter($validated['status'] ?? null);

        if ($statuses === null) {
            return $this->failed('Unknown status filter: '.$validated['status'], 422);
        }

        $query->whereIn('status', $statuses);

        if (! empty($validated['search'])) {
            // Built through the resolver so a code typed as PCM- also matches
            // a row stored as PM-, and vice versa.
            $term = CodeResolver::likeTerm($validated['search']);

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
                // Scoped like the list above, so the screen's counters describe
                // the agent's own parcels and cannot disagree with what is shown.
                'counts' => $this->hubCounts($hub, (int) $user->id),
                // Says so explicitly, so the app can label the screen as personal
                // rather than leaving an agent to wonder where the rest went.
                'scope' => 'agent',
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
            ->whereHas('shipmentItems', fn ($query) => $query->atHub($hub->id))
            ->whereNotIn('status', OutgoingBatch::CLOSED_STATUSES)
            ->withCount(['shipmentItems' => fn ($query) => $query->atHub($hub->id)])
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
            ->whereIn('batch_number', CodeResolver::candidates((string) $batchNumber))
            ->first();

        /*
         * Scoped to this hub, answering 404 rather than 403.
         *
         * The lookup above matched a batch number anywhere in the system — the
         * only method in this controller with no hub filter — so a hub agent who
         * knew or guessed a number could read another hub's full manifest, and
         * `serializePackages` carries the recipients' names and phone numbers.
         *
         * 404 and not 403 on purpose: a 403 would confirm the batch exists, which
         * turns this into a way to enumerate other hubs' consignments.
         */
        if (! $batch || ! $this->batchBelongsToHub($batch, $hub)) {
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
                    'already_at_this_hub' => $items->filter(fn (ShipmentItem $item) => $item->isAtHub((int) $hub->id))->count(),
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
            // The dispatch form was reduced to four details (car company,
            // driver number, car number, car description), so the driver's name
            // is no longer sent and must not be required. A direct caller may
            // still supply it.
            'driver_name' => ['nullable', 'string', 'max:120'],
            'driver_phone' => ['nullable', 'string', 'max:30'],
            'driver_id_number' => ['nullable', 'string', 'max:60'],
            'vehicle_plate' => ['nullable', 'string', 'max:30'],
            'vehicle_description' => ['nullable', 'string', 'max:120'],
            'bus_company' => ['nullable', 'string', 'max:120'],
            'departure_time' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            // The form no longer captures a handover photo, so it is optional
            // here. The single-parcel flow still requires one, and rows that
            // already have a photo keep it — this only means a batch sent
            // without a photo stores no `proof_photo_path` instead of 422ing.
            'proof_photo' => ['nullable', 'file', 'image', 'max:10240'],
        ]);

        $user = $request->user();
        $hub = $user->warehouse;

        $batch = null;

        if (! empty($validated['batch_number'])) {
            $batch = CodeResolver::resolveOutgoingBatch($validated['batch_number']);

            if (! $batch) {
                return $this->failed("No batch found for {$validated['batch_number']}.", 404);
            }

            if (in_array($batch->status, [OutgoingBatch::STATUS_RECEIVED], true)) {
                return $this->failed("Batch {$batch->batch_number} has already been received downstream.", 422);
            }

            $items = $batch->shipmentItems()->atHub($hub->id)->get();
        } elseif (! empty($validated['package_ids'])) {
            $ids = array_values(array_unique($validated['package_ids']));
            $items = ShipmentItem::query()
                ->whereIn('id', $ids)
                ->atHub($hub->id)
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

        // A whole batch is the same handover as a single parcel, one load at a
        // time: the same evidence, the same per-parcel record, and the same
        // text to each recipient. It goes through the handoff service so both
        // routes cannot drift apart again.
        $result = $this->busHandoffs->handOverBatch(
            $hub,
            $user,
            $items,
            $validated,
            $request->file('proof_photo'),
            $batch
                ? $this->destinationLabelForBatch($batch)
                : $this->destinationLabelForItems($items),
            $batch?->batch_number
        );

        if (! ($result['success'] ?? false)) {
            return $this->failed($result['message'], $result['status'] ?? 422);
        }

        $dispatched = $result['data']['dispatched_count'] ?? 0;

        if ($batch) {
            $batch->update(array_filter([
                'status' => OutgoingBatch::STATUS_DISPATCHED,
                'transport_driver_id' => $validated['transport_driver_id'] ?? null,
            ], fn ($value) => $value !== null));
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'].($batch ? " Batch {$batch->batch_number} is now dispatched." : ''),
            'data' => [
                'hub' => $this->serializeHub($hub),
                'batch_number' => $batch?->batch_number,
                'batch_status' => $batch?->fresh()->status,
                'dispatched_count' => $dispatched,
                'sms_sent_count' => $result['data']['sms_sent_count'] ?? 0,
                'skipped' => $result['data']['skipped'] ?? [],
                'handoffs' => $result['data']['handoffs'] ?? [],
                'packages' => $items->map(fn (ShipmentItem $item) => $this->serializePackage($item->fresh(), $hub))->values(),
            ],
        ], 201);
    }

    /**
     * Where this consignment is headed, for the handover record.
     *
     * The batch knows its own destination; an ad-hoc selection falls back to the
     * first parcel's, which is what the bus driver is told anyway.
     */
    private function destinationLabelForBatch(OutgoingBatch $batch): ?string
    {
        return $this->destinationLabel($batch->delivery_region_id, $batch->delivery_district_id);
    }

    /**
     * Whether this hub has any business with this batch.
     *
     * Three ways it can, in the order they are cheapest to answer:
     *
     * 1. The batch is destined for this hub. This is the intake desk's case: the
     *    transporter is bringing it here, so its parcels are not in this hub's
     *    custody yet and an `atHub`-only test would lock the desk out of the very
     *    manifest it is trying to check in.
     * 2. It is destined here but the destination was never stamped. Older rows
     *    predate `destination_warehouse_id`, and the destination still resolves
     *    from the region and district — the same resolution used when the batch was
     *    formed, so a legacy row is not stranded from its own hub.
     * 3. It is holding parcels here right now, which is how the `batches` list
     *    scopes and the only test that spans both directions of travel.
     *
     * Anything else is another hub's consignment.
     */
    private function batchBelongsToHub(OutgoingBatch $batch, Warehouse $hub): bool
    {
        if ((int) $batch->destination_warehouse_id === (int) $hub->id) {
            return true;
        }

        if (
            ! $batch->destination_warehouse_id
            && (int) OutgoingBatch::resolveDestinationWarehouseId($batch->delivery_region_id, $batch->delivery_district_id) === (int) $hub->id
        ) {
            return true;
        }

        return $batch->shipmentItems()->atHub($hub->id)->exists();
    }

    /** @param  \Illuminate\Support\Collection<int, ShipmentItem>  $items */
    private function destinationLabelForItems($items): ?string
    {
        $first = $items->first();

        return $first
            ? $this->destinationLabel($first->delivery_region_id, $first->delivery_district_id)
            : null;
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

        if (! $item->isAtHub((int) $hub->id)) {
            return $this->failed("{$this->parcelLabel($item)} is not held at {$hub->name}.", 403);
        }

        if ($item->released_at) {
            return $this->failed("{$this->parcelLabel($item)} was already released.", 422);
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

        if (! $item->isAtHub((int) $hub->id)) {
            return $this->failed("{$this->parcelLabel($item)} is not held at {$hub->name}.", 403);
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

        return response()->json([
            'success' => true,
            'data' => [
                'activities' => $this->recentActivities($hub, $limit),
            ],
        ]);
    }

    /**
     * The signed-in agent's most recent work, newest first.
     *
     * Derived from the tracking rows already written, so the feed reflects what
     * actually happened rather than a parallel log that could drift. A new
     * account gets an empty array, which the app renders as an empty state.
     *
     * Scoped to the acting user, and NOT to the hub — see the body for why the
     * old hub-wide version was wrong and why the `atHub` restriction had to go
     * with it. The stat tiles remain hub-wide.
     *
     * @return array<int, array<string, mixed>>
     */
    private function recentActivities(?int $actorId, int $limit): array
    {
        $rows = ShipmentItemTracking::query()
            ->whereActor($actorId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $codes = ShipmentItem::query()
            ->whereIn('id', $rows->pluck('shipment_item_id')->unique())
            ->pluck('tracking_code', 'id');

        return $rows->map(fn (ShipmentItemTracking $row) => [
            'id' => (string) $row->id,
            'package_id' => (string) $row->shipment_item_id,
            'tracking_code' => $codes[$row->shipment_item_id] ?? null,
            'status' => $row->status,
            'title' => ItemStatus::tryFrom((string) $row->status)?->label() ?? (string) $row->status,
            'body' => $row->notes,
            'location' => $row->location,
            'created_at' => $row->created_at?->toIso8601String(),
        ])->values()->all();
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

    /**
     * The parcel a scanned code refers to.
     *
     * Delegates to the resolver so the numeric-as-primary-key fallback sits
     * *after* the code match, which is the other way round from the original
     * inline version: a barcode reading "123" is far more likely to be a
     * tracking code than a request for row 123.
     */
    private function findItemByCode($code): ?ShipmentItem
    {
        return CodeResolver::resolveShipmentItem(is_scalar($code) ? (string) $code : null);
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

    /**
     * Tell the admins the code the recipient was just texted.
     *
     * The desk needs it because the contact agent's commission now depends on the
     * same code being confirmed, and an agent who cannot reach the recipient has to
     * be able to ask someone who already has it. Sent only after the recipient's own
     * text actually went out, so the desk is never told about a code the customer
     * never received.
     *
     * Failures are swallowed for the same reason as everywhere else on this path: a
     * push gateway must never undo a physical check-in.
     */
    private function notifyAdminsOfPickupCode(ShipmentItem $item, Warehouse $hub): void
    {
        if (blank($item->pickup_code)) {
            return;
        }

        try {
            $this->pushService->sendToAllAdmins(
                'Pickup code issued',
                sprintf(
                    '%s at %s — pickup code %s for parcel %s.',
                    $item->delivery_recipient_name ?: 'Recipient',
                    $hub->name,
                    $item->pickup_code,
                    $item->tracking_code ?: $item->id
                ),
                [
                    'shipment_item_id' => (string) $item->id,
                    'pickup_code' => (string) $item->pickup_code,
                    'tracking_code' => (string) ($item->tracking_code ?: $item->id),
                    'source' => 'hub_pickup_code',
                ],
                'pickup_code'
            );
        } catch (\Throwable $e) {
            Log::warning('Admin pickup-code push failed', [
                'shipment_item_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Whether this parcel's pickup code has already been texted to its recipient.
     *
     * The signal is the tracking trail itself: both this intake and the manual
     * Notify button record a successful send as a `shipment_item_tracking` row
     * whose `meta.sent` is true. That is the same durable marker the activity feed
     * is built from, so there is no second flag to drift out of step.
     */
    private function pickupSmsAlreadySent(ShipmentItem $item): bool
    {
        return ShipmentItemTracking::query()
            ->where('shipment_item_id', $item->id)
            ->get()
            ->contains(function (ShipmentItemTracking $row) {
                $meta = is_array($row->meta) ? $row->meta : [];

                return ($meta['sent'] ?? false) === true
                    && in_array($meta['source'] ?? null, ['hub_intake_sms', 'hub_notify_recipient'], true);
            });
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
     * The signed-in hub agent, as the app should show them.
     *
     * `photo_path` is stored at sign-up/upload but was never turned into a URL,
     * which is why the hub header had to hardcode an avatar. The app falls back
     * to initials when this is null.
     */
    private function serializeUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'profile_photo_url' => $user->photo_path
                ? app(StorageService::class)->getUrl($user->photo_path)
                : null,
            // Handy for a header that shows the posting without a second call.
            'hub_name' => $user->warehouse?->name,
            'hub_code' => $user->warehouse?->code,
        ];
    }

    /**
     * How to name a parcel in a message the agent has to read.
     *
     * A parcel only gets a tracking code once it enters package movement, so a
     * freshly booked one has none — and interpolating it produced messages like
     * "Package  is not held at Accra Main." with a hole where the name belongs.
     */
    private function parcelLabel(ShipmentItem $item): string
    {
        return filled($item->tracking_code)
            ? "Package {$item->tracking_code}"
            : "Parcel #{$item->id}";
    }

    /**
     * @return array<string, int>
     */
    /**
     * Inventory counts for a hub, optionally narrowed to one agent.
     *
     * `$actorId` is null for the dashboard tiles, which stay hub-wide on purpose:
     * "what is standing at my station" is not a personal figure, and the agent
     * needs it whether or not they were the one who took the parcel in.
     *
     * The Inventory screen passes the signed-in agent, so its counters describe
     * that agent's own parcels and agree with the list beneath them. Two different
     * numbers from one function, which is why the scope is a parameter rather than
     * a second copy of these queries.
     */
    private function hubCounts(Warehouse $hub, ?int $actorId = null): array
    {
        $base = ShipmentItem::query()->atHub($hub->id);

        if ($actorId !== null) {
            $base->handledBy($actorId);
        }

        // Parcels assigned to a different hub and still waiting to go. Deliberately
        // not the dispatched count — see ShipmentItem::scopeWaitingBus().
        $readyForBus = (clone $base)->waitingBus($hub)->count();

        return [
            'at_hub' => (clone $base)->whereIn('status', array_map(
                fn (ItemStatus $status) => $status->value,
                self::AT_HUB_STATUSES
            ))->count(),
            'ready_for_bus' => $readyForBus,
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
