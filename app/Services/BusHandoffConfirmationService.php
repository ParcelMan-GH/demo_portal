<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use App\Helpers\PhoneHelper;
use App\Models\BusHandoffConfirmation;
use App\Models\DeliveryFailureReason;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunItem;
use App\Models\DeliveryRunStop;
use App\Models\Driver;
use App\Models\Shipment;
use App\Models\ShipmentItemTracking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BusHandoffConfirmationService
{
    public const CODE_LENGTH = 6;
    public const CODE_TTL_MINUTES = 15;
    public const CODE_MAX_ATTEMPTS = 5;
    public const CODE_RESEND_SECONDS = 60;
    public const LINK_TTL_DAYS = 14;

    private const CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(
        private SmsService $smsService,
        private VendorCommissionService $commissionService,
        private StorageService $storageService,
    ) {
    }

    public function ensureForRunItem(DeliveryRunItem $runItem, ?Driver $driver = null): BusHandoffConfirmation
    {
        $runItem->loadMissing([
            'run:id,assigned_driver_id',
            'stop:id,delivery_method,status',
            'shipmentItem:id',
        ]);

        return BusHandoffConfirmation::query()->firstOrCreate(
            ['delivery_run_item_id' => $runItem->id],
            [
                'delivery_run_id' => $runItem->delivery_run_id,
                'delivery_run_stop_id' => $runItem->delivery_run_stop_id,
                'shipment_item_id' => $runItem->shipment_item_id,
                'handoff_driver_id' => $driver?->id ?? $runItem->run?->assigned_driver_id,
                'status' => $runItem->status === DeliveryRunItem::STATUS_DELIVERED
                    ? BusHandoffConfirmation::STATUS_ADMIN_CONFIRMED
                    : ($runItem->status === DeliveryRunItem::STATUS_FAILED
                        ? BusHandoffConfirmation::STATUS_FAILED
                        : BusHandoffConfirmation::STATUS_PENDING),
                'source' => $runItem->status === DeliveryRunItem::STATUS_DELIVERED
                    ? BusHandoffConfirmation::SOURCE_ADMIN
                    : null,
                'confirmed_at' => $runItem->status === DeliveryRunItem::STATUS_DELIVERED ? $runItem->delivered_at : null,
            ],
        );
    }

    public function ensureForStop(DeliveryRunStop $stop, ?Driver $driver = null): void
    {
        $items = DeliveryRunItem::query()
            ->where('delivery_run_stop_id', $stop->id)
            ->get();

        foreach ($items as $item) {
            $this->ensureForRunItem($item, $driver);
        }
    }

    public function listForDriver(Driver $driver, Request $request): array
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:40'],
            'search' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ]);

        $query = $this->driverQuery($driver)
            ->with($this->payloadRelations());

        if (!empty($validated['status'])) {
            if ($validated['status'] === 'open') {
                $query->whereIn('status', [
                    BusHandoffConfirmation::STATUS_PENDING,
                    BusHandoffConfirmation::STATUS_CODE_SENT,
                    BusHandoffConfirmation::STATUS_ISSUE_REPORTED,
                ]);
            } elseif ($validated['status'] === 'needs_follow_up') {
                $query->whereIn('status', [
                    BusHandoffConfirmation::STATUS_PENDING,
                    BusHandoffConfirmation::STATUS_CODE_SENT,
                ]);
            } elseif ($validated['status'] === 'confirmed') {
                $query->whereIn('status', [
                    BusHandoffConfirmation::STATUS_CONFIRMED,
                    BusHandoffConfirmation::STATUS_ADMIN_CONFIRMED,
                ]);
            } else {
                $query->where('status', $validated['status']);
            }
        }

        if ($search = trim((string) ($validated['search'] ?? ''))) {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('target_name', 'like', "%{$search}%")
                    ->orWhere('target_phone', 'like', "%{$search}%")
                    ->orWhereHas('shipmentItem', fn (Builder $itemQuery) => $itemQuery
                        ->where('tracking_code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                    )
                    ->orWhereHas('stop', fn (Builder $stopQuery) => $stopQuery
                        ->where('bus_station_name', 'like', "%{$search}%")
                        ->orWhere('recipient_name', 'like', "%{$search}%")
                        ->orWhere('recipient_phone', 'like', "%{$search}%")
                    );
            });
        }

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);
        $total = (clone $query)->count();

        $rows = $query
            ->latest('updated_at')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->map(fn (BusHandoffConfirmation $confirmation) => $this->payload($confirmation))
            ->values();

        return [
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
                    'total' => (clone $this->driverQuery($driver))->count(),
                    'open' => (clone $this->driverQuery($driver))->whereIn('status', [
                        BusHandoffConfirmation::STATUS_PENDING,
                        BusHandoffConfirmation::STATUS_CODE_SENT,
                        BusHandoffConfirmation::STATUS_ISSUE_REPORTED,
                    ])->count(),
                    'needs_follow_up' => (clone $this->driverQuery($driver))->whereIn('status', [
                        BusHandoffConfirmation::STATUS_PENDING,
                        BusHandoffConfirmation::STATUS_CODE_SENT,
                    ])->count(),
                    'pending_only' => (clone $this->driverQuery($driver))->where('status', BusHandoffConfirmation::STATUS_PENDING)->count(),
                    'code_sent' => (clone $this->driverQuery($driver))->where('status', BusHandoffConfirmation::STATUS_CODE_SENT)->count(),
                    'pending' => (clone $this->driverQuery($driver))->whereIn('status', [
                        BusHandoffConfirmation::STATUS_PENDING,
                        BusHandoffConfirmation::STATUS_CODE_SENT,
                    ])->count(),
                    'issues' => (clone $this->driverQuery($driver))->where('status', BusHandoffConfirmation::STATUS_ISSUE_REPORTED)->count(),
                    'confirmed' => (clone $this->driverQuery($driver))->whereIn('status', [
                        BusHandoffConfirmation::STATUS_CONFIRMED,
                        BusHandoffConfirmation::STATUS_ADMIN_CONFIRMED,
                    ])->count(),
                ],
            ],
        ];
    }

    public function showForDriver(Driver $driver, DeliveryRunItem $runItem): array
    {
        $confirmation = $this->confirmationForDriver($driver, $runItem)
            ->with($this->payloadRelations())
            ->first();

        if (!$confirmation) {
            return ['success' => false, 'message' => 'Bus handoff package not found.', 'status' => 404];
        }

        return [
            'success' => true,
            'data' => ['handoff' => $this->payload($confirmation)],
        ];
    }

    public function sendConfirmation(Driver $driver, DeliveryRunItem $runItem, string $targetType): array
    {
        $confirmation = $this->confirmationForDriver($driver, $runItem)
            ->with(['shipmentItem.shipment.vendor', 'stop'])
            ->first();

        if (!$confirmation) {
            return ['success' => false, 'message' => 'Bus handoff package not found.', 'status' => 404];
        }

        if ($confirmation->isFinal()) {
            return ['success' => false, 'message' => 'This handoff is already resolved.', 'status' => 422];
        }

        if ($confirmation->confirmation_code_sent_at) {
            $secondsSince = $confirmation->confirmation_code_sent_at->diffInSeconds(now());
            if ($secondsSince < self::CODE_RESEND_SECONDS) {
                return [
                    'success' => false,
                    'message' => 'Please wait before resending the confirmation code.',
                    'status' => 429,
                    'data' => ['resend_after_seconds' => self::CODE_RESEND_SECONDS - $secondsSince],
                ];
            }
        }

        $target = $this->resolveTarget($confirmation, $targetType);
        if (!$target['phone']) {
            return ['success' => false, 'message' => 'No phone number is available for this target.', 'status' => 422];
        }

        $code = $this->generateCode();
        $token = $this->generatePublicToken();
        $now = now();
        $link = $this->publicConfirmationUrl($token);
        $tracking = $confirmation->shipmentItem?->tracking_code ?: 'your package';
        $message = "Code {$code} confirms you received package {$tracking}. Only share this code if the package is in your hands. You can also confirm or report an issue: {$link}";

        $sent = $this->smsService->send($target['phone'], $message);

        $confirmation->update([
            'status' => BusHandoffConfirmation::STATUS_CODE_SENT,
            'target_type' => $targetType,
            'target_name' => $target['name'],
            'target_phone' => $target['phone'],
            'confirmation_code_hash' => $this->hashValue($code),
            'confirmation_code_sent_at' => $now,
            'confirmation_code_expires_at' => $now->copy()->addMinutes(self::CODE_TTL_MINUTES),
            'confirmation_code_verified_at' => null,
            'confirmation_attempts' => 0,
            'public_token_hash' => $this->hashValue($token),
            'public_token_expires_at' => $now->copy()->addDays(self::LINK_TTL_DAYS),
            'public_link_sent_at' => $now,
        ]);

        return [
            'success' => true,
            'message' => $sent ? 'Confirmation code sent.' : 'Confirmation details saved, but SMS could not be sent.',
            'data' => [
                'sent' => $sent,
                'target_type' => $targetType,
                'target_phone' => $target['phone'],
                'resend_after_seconds' => self::CODE_RESEND_SECONDS,
                'ttl_minutes' => self::CODE_TTL_MINUTES,
                'expires_at' => $confirmation->fresh()->confirmation_code_expires_at?->toIso8601String(),
            ],
        ];
    }

    public function confirmWithCode(Driver $driver, DeliveryRunItem $runItem, string $code): array
    {
        $confirmation = $this->confirmationForDriver($driver, $runItem)
            ->with(['runItem.shipmentItem.shipment', 'stop'])
            ->first();

        if (!$confirmation) {
            return ['success' => false, 'message' => 'Bus handoff package not found.', 'status' => 404];
        }

        if ($confirmation->isFinal()) {
            return ['success' => false, 'message' => 'This handoff is already resolved.', 'status' => 422];
        }

        $verification = $this->verifyCode($confirmation, $code);
        if (!$verification['success']) {
            return $verification;
        }

        return DB::transaction(function () use ($confirmation, $driver) {
            $confirmation->update([
                'status' => BusHandoffConfirmation::STATUS_CONFIRMED,
                'source' => BusHandoffConfirmation::SOURCE_RIDER_CODE,
                'confirmation_code_verified_at' => now(),
                'confirmed_at' => now(),
                'confirmed_by_driver_id' => $driver->id,
                'reason_id' => null,
                'reason_label' => null,
                'reason_type' => null,
                'issue_notes' => null,
                'confirmation_notes' => 'Recipient/vendor confirmation code verified by rider.',
            ]);

            $this->markDelivered($confirmation->fresh($this->payloadRelations()), 'Delivery confirmed by rider with recipient/vendor code.');

            return ['success' => true, 'message' => 'Bus handoff confirmed as delivered.'];
        });
    }

    public function reportIssue(Driver $driver, DeliveryRunItem $runItem, int $reasonId, ?string $notes = null): array
    {
        $confirmation = $this->confirmationForDriver($driver, $runItem)->first();
        if (!$confirmation) {
            return ['success' => false, 'message' => 'Bus handoff package not found.', 'status' => 404];
        }

        if ($confirmation->isFinal()) {
            return ['success' => false, 'message' => 'This handoff is already resolved.', 'status' => 422];
        }

        $reason = DeliveryFailureReason::query()->whereKey($reasonId)->where('is_active', true)->first();
        if (!$reason) {
            return ['success' => false, 'message' => 'Select a valid reason.', 'status' => 422];
        }

        $confirmation->update([
            'status' => BusHandoffConfirmation::STATUS_ISSUE_REPORTED,
            'reason_id' => $reason->id,
            'reason_label' => $reason->label,
            'reason_type' => $reason->type,
            'issue_notes' => $notes,
            'source' => BusHandoffConfirmation::SOURCE_RIDER_CODE,
        ]);

        return ['success' => true, 'message' => 'Issue recorded for admin follow-up.'];
    }

    public function publicPayload(string $token): ?array
    {
        $confirmation = $this->confirmationByToken($token);
        if (!$confirmation) {
            return null;
        }

        if ($confirmation->isFinal()) {
            return null;
        }

        return $this->payload($confirmation, public: true);
    }

    public function publicConfirm(string $token, ?string $notes = null): array
    {
        $confirmation = $this->confirmationByToken($token);
        if (!$confirmation) {
            return ['success' => false, 'message' => 'This confirmation link is invalid or expired.', 'status' => 404];
        }

        if ($confirmation->isFinal()) {
            return ['success' => false, 'message' => 'This handoff has already been resolved.', 'status' => 422];
        }

        return DB::transaction(function () use ($confirmation, $notes) {
            $confirmation->update([
                'status' => BusHandoffConfirmation::STATUS_CONFIRMED,
                'source' => BusHandoffConfirmation::SOURCE_PUBLIC_LINK,
                'confirmed_at' => now(),
                'public_confirmed_at' => now(),
                'reason_id' => null,
                'reason_label' => null,
                'reason_type' => null,
                'issue_notes' => null,
                'confirmation_notes' => $notes,
            ]);

            $this->markDelivered($confirmation->fresh($this->payloadRelations()), 'Delivery confirmed through public bus handoff link.');

            return ['success' => true, 'message' => 'Package confirmed as delivered.'];
        });
    }

    public function publicIssue(string $token, int $reasonId, ?string $notes = null): array
    {
        $confirmation = $this->confirmationByToken($token);
        if (!$confirmation) {
            return ['success' => false, 'message' => 'This confirmation link is invalid or expired.', 'status' => 404];
        }

        if ($confirmation->isFinal()) {
            return ['success' => false, 'message' => 'This handoff has already been resolved.', 'status' => 422];
        }

        $reason = DeliveryFailureReason::query()->whereKey($reasonId)->where('is_active', true)->first();
        if (!$reason) {
            return ['success' => false, 'message' => 'Select a valid reason.', 'status' => 422];
        }

        $confirmation->update([
            'status' => BusHandoffConfirmation::STATUS_ISSUE_REPORTED,
            'source' => BusHandoffConfirmation::SOURCE_PUBLIC_LINK,
            'reason_id' => $reason->id,
            'reason_label' => $reason->label,
            'reason_type' => $reason->type,
            'issue_notes' => $notes,
            'public_reported_at' => now(),
        ]);

        return ['success' => true, 'message' => 'Your issue has been recorded.'];
    }

    public function adminResolveItem(DeliveryRunItem $runItem, User $admin, string $action, ?string $notes = null): array
    {
        $confirmation = $this->ensureForRunItem($runItem)->fresh($this->payloadRelations());

        return DB::transaction(function () use ($confirmation, $admin, $action, $notes) {
            if ($action === 'pending') {
                $confirmation->update([
                    'status' => BusHandoffConfirmation::STATUS_PENDING,
                    'source' => null,
                    'reason_id' => null,
                    'reason_label' => null,
                    'reason_type' => null,
                    'issue_notes' => null,
                    'confirmation_notes' => $notes,
                    'confirmed_at' => null,
                    'confirmed_by_admin_id' => null,
                    'confirmed_by_driver_id' => null,
                    'public_confirmed_at' => null,
                    'public_reported_at' => null,
                    'confirmation_code_hash' => null,
                    'confirmation_code_sent_at' => null,
                    'confirmation_code_expires_at' => null,
                    'confirmation_code_verified_at' => null,
                    'confirmation_attempts' => 0,
                    'public_token_hash' => null,
                    'public_token_expires_at' => null,
                    'public_link_sent_at' => null,
                ]);

                $this->markPending($confirmation->fresh($this->payloadRelations()), $notes);

                return ['success' => true, 'message' => 'Handoff returned to pending confirmation.'];
            }

            if ($action === 'delivered') {
                $confirmation->update([
                    'status' => BusHandoffConfirmation::STATUS_ADMIN_CONFIRMED,
                    'source' => BusHandoffConfirmation::SOURCE_ADMIN,
                    'confirmed_at' => now(),
                    'confirmed_by_admin_id' => $admin->id,
                    'confirmation_notes' => $notes,
                    'reason_id' => null,
                    'reason_label' => null,
                    'reason_type' => null,
                    'issue_notes' => null,
                ]);

                $this->markDelivered($confirmation->fresh($this->payloadRelations()), 'Delivery confirmed by admin. ' . ($notes ?? ''));

                return ['success' => true, 'message' => 'Delivery confirmed.'];
            }

            $confirmation->update([
                'status' => BusHandoffConfirmation::STATUS_FAILED,
                'source' => BusHandoffConfirmation::SOURCE_ADMIN,
                'confirmed_at' => now(),
                'confirmed_by_admin_id' => $admin->id,
                'confirmation_notes' => $notes,
            ]);

            $this->markFailed($confirmation->fresh($this->payloadRelations()), $notes);

            return ['success' => true, 'message' => 'Handoff marked as failed.'];
        });
    }

    public function activeReasons(): Collection
    {
        return DeliveryFailureReason::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->map(fn (DeliveryFailureReason $reason) => $this->reasonPayload($reason));
    }

    public function reasonPayload(DeliveryFailureReason $reason): array
    {
        return [
            'id' => $reason->id,
            'label' => $reason->label,
            'slug' => $reason->slug,
            'type' => $reason->type,
            'sort_order' => $reason->sort_order,
            'is_active' => $reason->is_active,
        ];
    }

    private function markDelivered(BusHandoffConfirmation $confirmation, string $trackingNote): void
    {
        $runItem = $confirmation->runItem;
        $stop = $confirmation->stop;
        $shipmentItem = $confirmation->shipmentItem;
        $now = now();

        if (!$runItem || !$stop || !$shipmentItem) {
            return;
        }

        $runItem->update([
            'status' => DeliveryRunItem::STATUS_DELIVERED,
            'delivered_quantity' => $runItem->expected_quantity,
            'delivered_at' => $now,
            'notes' => trim($trackingNote),
        ]);

        $shipmentItem->update(['status' => ItemStatus::DELIVERED]);

        ShipmentItemTracking::query()->create([
            'shipment_item_id' => $shipmentItem->id,
            'status' => ItemStatus::DELIVERED->value,
            'location' => $stop->bus_station_name ?: ($stop->town ?: $stop->landmark),
            'notes' => trim($trackingNote),
            'meta' => [
                'delivery_run_id' => $confirmation->delivery_run_id,
                'delivery_run_stop_id' => $confirmation->delivery_run_stop_id,
                'delivery_run_item_id' => $confirmation->delivery_run_item_id,
                'confirmation_id' => $confirmation->id,
                'source' => $confirmation->source,
            ],
            'created_at' => $now,
        ]);

        $this->refreshShipmentStatus($shipmentItem->shipment);
        $this->refreshStopAndRun($stop);
    }

    private function markFailed(BusHandoffConfirmation $confirmation, ?string $notes = null): void
    {
        $runItem = $confirmation->runItem;
        $stop = $confirmation->stop;
        $shipmentItem = $confirmation->shipmentItem;

        if (!$runItem || !$stop || !$shipmentItem) {
            return;
        }

        $runItem->update([
            'status' => DeliveryRunItem::STATUS_FAILED,
            'delivered_quantity' => 0,
            'delivered_at' => null,
            'notes' => $notes,
        ]);

        $shipmentItem->update(['status' => ItemStatus::AT_DESTINATION]);
        $this->refreshShipmentStatus($shipmentItem->shipment);
        $this->refreshStopAndRun($stop);
    }

    private function markPending(BusHandoffConfirmation $confirmation, ?string $notes = null): void
    {
        $runItem = $confirmation->runItem;
        $stop = $confirmation->stop;
        $shipmentItem = $confirmation->shipmentItem;

        if (!$runItem || !$stop || !$shipmentItem) {
            return;
        }

        $runItem->update([
            'status' => DeliveryRunItem::STATUS_HANDED_OFF,
            'delivered_quantity' => 0,
            'delivered_at' => null,
            'notes' => $notes,
        ]);

        $shipmentItem->update(['status' => ItemStatus::HANDED_TO_COURIER]);
        $this->refreshShipmentStatus($shipmentItem->shipment);
        $this->refreshStopAndRun($stop);
    }

    private function refreshStopAndRun(DeliveryRunStop $stop): void
    {
        $stop->unsetRelation('items');
        $items = $stop->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        $allResolved = $items->every(fn (DeliveryRunItem $item) => in_array($item->status, [
            DeliveryRunItem::STATUS_DELIVERED,
            DeliveryRunItem::STATUS_FAILED,
        ], true));

        if ($allResolved) {
            $allDelivered = $items->every(fn (DeliveryRunItem $item) => $item->status === DeliveryRunItem::STATUS_DELIVERED);
            $stop->update([
                'status' => $allDelivered ? DeliveryRunStop::STATUS_DELIVERED : DeliveryRunStop::STATUS_FAILED,
                'delivered_at' => $allDelivered ? ($stop->delivered_at ?? now()) : null,
                'confirmed_at' => now(),
            ]);

            if ($allDelivered) {
                $this->commissionService->createEarningsForStop($stop);
            }
        } else {
            $stop->update([
                'status' => DeliveryRunStop::STATUS_HANDED_OFF,
                'delivered_at' => null,
                'confirmed_at' => null,
            ]);
        }

        $run = $stop->run()->with('stops')->first();
        if (!$run || $run->status === DeliveryRun::STATUS_CANCELLED) {
            return;
        }

        $totalStops = $run->stops->count();
        $completedStops = $run->stops
            ->whereIn('status', [DeliveryRunStop::STATUS_DELIVERED, DeliveryRunStop::STATUS_FAILED])
            ->count();

        if ($totalStops > 0 && $completedStops === $totalStops) {
            $run->update(['status' => DeliveryRun::STATUS_COMPLETED, 'completed_at' => $run->completed_at ?? now()]);
        } elseif ($completedStops > 0) {
            $run->update(['status' => DeliveryRun::STATUS_PARTIALLY_DELIVERED, 'completed_at' => null]);
        } else {
            $run->update(['status' => DeliveryRun::STATUS_OUT_FOR_DELIVERY, 'completed_at' => null]);
        }
    }

    private function refreshShipmentStatus(?Shipment $shipment): void
    {
        if (!$shipment) {
            return;
        }

        $items = $shipment->items()->get(['status']);
        if ($items->isNotEmpty() && $items->every(fn ($item) => $item->status === ItemStatus::DELIVERED)) {
            $shipment->update(['status' => ShipmentStatus::DELIVERED]);
            return;
        }

        if ($items->contains(fn ($item) => $item->status === ItemStatus::HANDED_TO_COURIER)) {
            $shipment->update(['status' => ShipmentStatus::HANDED_TO_COURIER]);
        }
    }

    private function verifyCode(BusHandoffConfirmation $confirmation, string $code): array
    {
        $code = strtoupper(trim($code));

        if (!$confirmation->confirmation_code_hash || !$confirmation->confirmation_code_expires_at) {
            return ['success' => false, 'message' => 'No confirmation code has been sent yet.', 'status' => 422, 'code' => 'missing'];
        }

        if ($confirmation->confirmation_code_expires_at->isPast()) {
            return ['success' => false, 'message' => 'Confirmation code has expired. Please resend.', 'status' => 422, 'code' => 'expired'];
        }

        if ($confirmation->confirmation_attempts >= self::CODE_MAX_ATTEMPTS) {
            return ['success' => false, 'message' => 'Too many attempts. Please resend a new code.', 'status' => 422, 'code' => 'exhausted'];
        }

        if (!hash_equals((string) $confirmation->confirmation_code_hash, $this->hashValue($code))) {
            $confirmation->increment('confirmation_attempts');
            return ['success' => false, 'message' => 'Invalid confirmation code.', 'status' => 422, 'code' => 'invalid'];
        }

        return ['success' => true];
    }

    private function resolveTarget(BusHandoffConfirmation $confirmation, string $targetType): array
    {
        $shipmentItem = $confirmation->shipmentItem;
        $shipment = $shipmentItem?->shipment;

        if ($targetType === BusHandoffConfirmation::TARGET_VENDOR) {
            $vendor = $shipment?->vendor;
            $phone = $vendor?->phone;
            return [
                'name' => $vendor?->business_name ?: $vendor?->name,
                'phone' => $phone ? (PhoneHelper::format($phone) ?? $phone) : null,
            ];
        }

        $name = $shipmentItem?->delivery_recipient_name ?: $shipment?->delivery_recipient_name ?: $confirmation->stop?->recipient_name;
        $phone = $shipmentItem?->delivery_recipient_phone ?: $shipment?->delivery_recipient_phone ?: $confirmation->stop?->recipient_phone;

        return [
            'name' => $name,
            'phone' => $phone ? (PhoneHelper::format($phone) ?? $phone) : null,
        ];
    }

    private function confirmationByToken(string $token): ?BusHandoffConfirmation
    {
        return BusHandoffConfirmation::query()
            ->with($this->payloadRelations())
            ->where('public_token_hash', $this->hashValue($token))
            ->where('public_token_expires_at', '>', now())
            ->first();
    }

    private function confirmationForDriver(Driver $driver, DeliveryRunItem $runItem): Builder
    {
        $runItem->loadMissing(['run:id,assigned_driver_id', 'stop:id,delivery_method,status']);

        if (
            (int) $runItem->run?->assigned_driver_id !== (int) $driver->id
            || $runItem->stop?->delivery_method !== DeliveryRunStop::METHOD_BUS_HANDOFF
            || (
                ! in_array($runItem->stop?->status, [
                    DeliveryRunStop::STATUS_HANDED_OFF,
                    DeliveryRunStop::STATUS_DELIVERED,
                    DeliveryRunStop::STATUS_FAILED,
                ], true)
                && ! in_array($runItem->status, [
                    DeliveryRunItem::STATUS_HANDED_OFF,
                    DeliveryRunItem::STATUS_DELIVERED,
                    DeliveryRunItem::STATUS_FAILED,
                ], true)
            )
        ) {
            return BusHandoffConfirmation::query()->whereRaw('1 = 0');
        }

        $confirmation = $this->ensureForRunItem($runItem, $driver);

        return BusHandoffConfirmation::query()
            ->whereKey($confirmation->id)
            ->where('handoff_driver_id', $driver->id);
    }

    private function driverQuery(Driver $driver): Builder
    {
        return BusHandoffConfirmation::query()
            ->where('handoff_driver_id', $driver->id);
    }

    private function payloadRelations(): array
    {
        return [
            'run:id,run_number,assigned_driver_id,status',
            'stop:id,delivery_run_id,bus_station_name,handoff_courier_name,handoff_courier_phone,handoff_vehicle_number,handoff_at,proof_photo_path,status,town,landmark',
            'runItem:id,delivery_run_id,delivery_run_stop_id,shipment_item_id,expected_quantity,delivered_quantity,status,notes,delivered_at',
            'shipmentItem:id,shipment_id,description,tracking_code,quantity,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            'shipmentItem.shipment:id,shipment_number,vendor_id,delivery_recipient_name,delivery_recipient_phone,delivery_town',
            'shipmentItem.shipment.vendor:id,name,business_name,phone',
            'reason:id,label,type',
            'handoffDriver:id,name,phone',
            'confirmedByDriver:id,name,phone',
            'confirmedByAdmin:id,name',
        ];
    }

    private function payload(BusHandoffConfirmation $confirmation, bool $public = false): array
    {
        $item = $confirmation->shipmentItem;
        $shipment = $item?->shipment;
        $vendor = $shipment?->vendor;
        $stop = $confirmation->stop;

        return [
            'id' => $confirmation->id,
            'delivery_run_id' => $confirmation->delivery_run_id,
            'delivery_run_stop_id' => $confirmation->delivery_run_stop_id,
            'delivery_run_item_id' => $confirmation->delivery_run_item_id,
            'delivery_run_number' => $confirmation->run?->run_number,
            'status' => $confirmation->status,
            'status_label' => Str::of($confirmation->status)->replace('_', ' ')->title()->toString(),
            'source' => $confirmation->source,
            'source_label' => $confirmation->source ? Str::of($confirmation->source)->replace('_', ' ')->title()->toString() : null,
            'target_type' => $confirmation->target_type,
            'target_name' => $confirmation->target_name,
            'target_phone' => $confirmation->target_phone,
            'reason' => ($confirmation->reason || $confirmation->reason_label) ? [
                'id' => $confirmation->reason?->id,
                'label' => $confirmation->reason_label ?: $confirmation->reason?->label,
                'type' => $confirmation->reason_type ?: $confirmation->reason?->type,
                'current_label' => $confirmation->reason?->label,
                'is_snapshot' => (bool) $confirmation->reason_label,
            ] : null,
            'issue_notes' => $confirmation->issue_notes,
            'confirmation_notes' => $confirmation->confirmation_notes,
            'code_sent_at' => $confirmation->confirmation_code_sent_at?->toIso8601String(),
            'code_expires_at' => $confirmation->confirmation_code_expires_at?->toIso8601String(),
            'confirmed_at' => $confirmation->confirmed_at?->toIso8601String(),
            'public_confirmed_at' => $confirmation->public_confirmed_at?->toIso8601String(),
            'public_reported_at' => $confirmation->public_reported_at?->toIso8601String(),
            'handoff_driver' => $confirmation->handoffDriver ? [
                'id' => $confirmation->handoffDriver->id,
                'name' => $confirmation->handoffDriver->name,
                'phone' => $confirmation->handoffDriver->phone,
            ] : null,
            'confirmed_by_driver' => $confirmation->confirmedByDriver ? [
                'id' => $confirmation->confirmedByDriver->id,
                'name' => $confirmation->confirmedByDriver->name,
                'phone' => $confirmation->confirmedByDriver->phone,
            ] : null,
            'confirmed_by_admin' => $confirmation->confirmedByAdmin ? [
                'id' => $confirmation->confirmedByAdmin->id,
                'name' => $confirmation->confirmedByAdmin->name,
            ] : null,
            'package' => [
                'shipment_number' => $shipment?->shipment_number,
                'tracking_code' => $item?->tracking_code,
                'description' => $item?->description,
                'quantity' => $item?->quantity,
                'status' => $item?->status?->value ?? $item?->status,
            ],
            'recipient' => [
                'name' => $item?->delivery_recipient_name ?: $shipment?->delivery_recipient_name,
                'phone' => $item?->delivery_recipient_phone ?: $shipment?->delivery_recipient_phone,
                'town' => $item?->delivery_town ?: $shipment?->delivery_town,
            ],
            'vendor' => $vendor ? [
                'name' => $vendor->business_name ?: $vendor->name,
                'phone' => $vendor->phone,
            ] : null,
            'handoff' => [
                'bus_station' => $stop?->bus_station_name,
                'courier_name' => $stop?->handoff_courier_name,
                'courier_phone' => $stop?->handoff_courier_phone,
                'vehicle_number' => $stop?->handoff_vehicle_number,
                'handed_off_at' => $stop?->handoff_at?->toIso8601String(),
                'proof_photo_url' => $this->proofPhotoUrl($stop?->proof_photo_path),
            ],
            'public' => $public,
        ];
    }

    private function proofPhotoUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        return $this->storageService->getUrl($path);
    }

    private function hashValue(string $value): string
    {
        return hash('sha256', $value);
    }

    private function generateCode(): string
    {
        $alphabet = self::CODE_ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
    }

    private function generatePublicToken(): string
    {
        do {
            $token = Str::upper(Str::random(12));
        } while (BusHandoffConfirmation::query()->where('public_token_hash', $this->hashValue($token))->exists());

        return $token;
    }

    private function publicConfirmationUrl(string $token): string
    {
        $configuredUrl = (string) (config('app.public_url') ?: config('app.url'));
        $parts = parse_url($configuredUrl);

        if (is_array($parts) && !empty($parts['scheme']) && !empty($parts['host'])) {
            $baseUrl = $parts['scheme'] . '://' . $parts['host'];

            if (!empty($parts['port'])) {
                $baseUrl .= ':' . $parts['port'];
            }
        } else {
            $baseUrl = request()->getSchemeAndHttpHost();
        }

        return rtrim($baseUrl, '/') . '/h/' . rawurlencode($token);
    }
}
