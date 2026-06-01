<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use App\Models\DeliveryRunItem;
use App\Models\Driver;
use App\Models\LabelCustodyEvent;
use App\Models\RiderTeam;
use App\Models\RiderTeamHandover;
use App\Models\RiderTeamHandoverItem;
use App\Models\RiderTeamMembership;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItemLabel;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RiderTeamHandoverService
{
    public function lookupRider(string $phone): ?Driver
    {
        $formatted = PhoneHelper::format($phone);
        $local = $formatted ? PhoneHelper::toLocal($formatted) : null;
        $normalized = $this->normalizePhone($formatted ?: $phone);
        $exactMatches = collect([$phone, $formatted, $local])
            ->filter()
            ->unique()
            ->values()
            ->all();

        return Driver::query()
            ->where('is_active', true)
            ->where(function ($query) use ($exactMatches, $normalized) {
                $query->whereIn('phone', $exactMatches);
                if ($normalized !== '') {
                    $query->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, '+', ''), ' ', ''), '-', ''), '(', ''), ')', '') = ?", [$normalized]);
                }
            })
            ->first();
    }

    public function addMembership(RiderTeam $team, Driver $driver, string $role, string $actorType, int $actorId): RiderTeamMembership
    {
        if (! in_array($role, [RiderTeamMembership::ROLE_LEADER, RiderTeamMembership::ROLE_MEMBER], true)) {
            throw ValidationException::withMessages(['role' => 'Select a valid rider team role.']);
        }

        return DB::transaction(function () use ($team, $driver, $role, $actorType, $actorId) {
            $existing = RiderTeamMembership::query()
                ->where('rider_team_id', $team->id)
                ->where('driver_id', $driver->id)
                ->where('role', $role)
                ->where('is_active', true)
                ->whereNull('removed_at')
                ->first();

            if ($existing) {
                return $existing;
            }

            return RiderTeamMembership::create([
                'rider_team_id' => $team->id,
                'driver_id' => $driver->id,
                'role' => $role,
                'is_active' => true,
                'added_by_type' => $actorType,
                'added_by_id' => $actorId,
                'joined_at' => now(),
            ]);
        });
    }

    public function removeMembership(RiderTeam $team, Driver $driver, ?string $role = null): int
    {
        return RiderTeamMembership::query()
            ->where('rider_team_id', $team->id)
            ->where('driver_id', $driver->id)
            ->when($role, fn ($query) => $query->where('role', $role))
            ->where('is_active', true)
            ->whereNull('removed_at')
            ->update([
                'is_active' => false,
                'removed_at' => now(),
            ]);
    }

    public function driverCanManageTeam(Driver $driver, RiderTeam $team): bool
    {
        return $this->hasActiveRole($driver, $team, RiderTeamMembership::ROLE_LEADER);
    }

    public function driverBelongsToTeam(Driver $driver, RiderTeam $team): bool
    {
        return RiderTeamMembership::query()
            ->where('rider_team_id', $team->id)
            ->where('driver_id', $driver->id)
            ->where('is_active', true)
            ->whereNull('removed_at')
            ->exists();
    }

    public function createHandover(
        RiderTeam $team,
        Driver $receiver,
        ?Warehouse $warehouse = null,
        ?User $createdByUser = null,
        ?Driver $createdByDriver = null,
        ?string $notes = null
    ): RiderTeamHandover {
        if (! $team->is_active) {
            throw ValidationException::withMessages(['rider_team_id' => 'Selected rider team is inactive.']);
        }

        if (! $receiver->is_active) {
            throw ValidationException::withMessages(['receiver_driver_id' => 'Selected handover receiver is inactive.']);
        }

        if (! $this->driverBelongsToTeam($receiver, $team)) {
            throw ValidationException::withMessages(['receiver_driver_id' => 'Selected rider is not an active member of this rider team.']);
        }

        return RiderTeamHandover::create([
            'handover_number' => $this->generateHandoverNumber($warehouse),
            'warehouse_id' => $warehouse?->id ?? $team->warehouse_id,
            'rider_team_id' => $team->id,
            // Keep the legacy leader column mirrored for old records/code paths; receiver_driver_id is the source of truth.
            'leader_driver_id' => $receiver->id,
            'receiver_driver_id' => $receiver->id,
            'created_by_user_id' => $createdByUser?->id,
            'created_by_driver_id' => $createdByDriver?->id,
            'status' => RiderTeamHandover::STATUS_DRAFT,
            'notes' => $notes,
        ]);
    }

    public function assignLabels(RiderTeamHandover $handover, array $barcodes, ?string $notes = null): array
    {
        $handover->loadMissing('team');
        if (! $handover->team?->is_active) {
            throw ValidationException::withMessages(['rider_team_id' => 'This rider team is inactive.']);
        }

        $barcodes = collect($barcodes)
            ->map(fn ($barcode) => trim((string) $barcode))
            ->filter()
            ->unique()
            ->values();

        if ($barcodes->isEmpty()) {
            throw ValidationException::withMessages(['barcodes' => 'Add at least one package label.']);
        }

        $labels = WarehouseReceiptItemLabel::query()
            ->with(['receiptItem.receipt', 'latestCustody', 'riderTeamHandoverItem.handover'])
            ->whereIn('barcode_value', $barcodes)
            ->get();

        $missing = $barcodes->diff($labels->pluck('barcode_value'))->values();
        if ($missing->isNotEmpty()) {
            throw ValidationException::withMessages(['barcodes' => 'Label not found: ' . $missing->first()]);
        }

        return DB::transaction(function () use ($handover, $labels, $notes) {
            $assigned = 0;
            foreach ($labels as $label) {
                $this->assertLabelAssignableToReceiver($label);

                $item = RiderTeamHandoverItem::create([
                    'rider_team_handover_id' => $handover->id,
                    'warehouse_receipt_item_label_id' => $label->id,
                    'status' => RiderTeamHandoverItem::STATUS_ASSIGNED_TO_LEADER,
                    'assigned_at' => now(),
                    'notes' => $notes,
                ]);

                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $label->id,
                    'event_type' => LabelCustodyEvent::TYPE_ASSIGNED_TO_LEADER,
                    'driver_id' => $this->handoverReceiverId($handover),
                    'notes' => 'Rider team handover ' . $handover->handover_number,
                ]);

                $assigned++;
            }

            $this->refreshHandoverCounts($handover->fresh());

            return ['assigned' => $assigned];
        });
    }

    public function receiveByReceiver(RiderTeamHandover $handover, Driver $receiver, string $barcode): RiderTeamHandoverItem
    {
        $handover->loadMissing('team');
        if (! $handover->team?->is_active) {
            throw ValidationException::withMessages(['barcode' => 'This rider team is inactive.']);
        }

        if ($this->handoverReceiverId($handover) !== (int) $receiver->id) {
            throw ValidationException::withMessages(['barcode' => 'This handover belongs to another rider team receiver.']);
        }

        $item = $this->handoverItemForBarcode($handover, $barcode);

        return DB::transaction(function () use ($handover, $receiver, $item) {
            if (! in_array($item->status, [
                RiderTeamHandoverItem::STATUS_ASSIGNED_TO_LEADER,
                RiderTeamHandoverItem::STATUS_LEADER_RECEIVED,
            ], true)) {
                throw ValidationException::withMessages(['barcode' => 'This package has already moved past receiver confirmation.']);
            }

            $item->update([
                'status' => RiderTeamHandoverItem::STATUS_LEADER_RECEIVED,
                'leader_received_at' => $item->leader_received_at ?? now(),
            ]);

            LabelCustodyEvent::create([
                'warehouse_receipt_item_label_id' => $item->warehouse_receipt_item_label_id,
                'event_type' => LabelCustodyEvent::TYPE_LEADER_RECEIVED,
                'driver_id' => $receiver->id,
                'notes' => 'Receiver accepted handover ' . $handover->handover_number,
            ]);

            $this->refreshHandoverCounts($handover);

            return $item->fresh(['label.receiptItem.shipmentItem']);
        });
    }

    public function allocateLabels(RiderTeamHandover $handover, Driver $receiver, Driver $member, array $barcodes): array
    {
        $handover->loadMissing('team');
        if (! $handover->team?->is_active) {
            throw ValidationException::withMessages(['team' => 'This rider team is inactive.']);
        }

        if (! $member->is_active) {
            throw ValidationException::withMessages(['member' => 'Selected rider is inactive.']);
        }

        $canDistribute = $this->handoverReceiverId($handover) === (int) $receiver->id
            || $this->driverCanManageTeam($receiver, $handover->team);

        if (! $canDistribute) {
            throw ValidationException::withMessages(['team' => 'Only the handover receiver or a team leader can distribute this handover.']);
        }

        if (! $this->driverBelongsToTeam($member, $handover->team)) {
            throw ValidationException::withMessages(['member' => 'Selected rider is not an active member of this rider team.']);
        }

        $barcodes = collect($barcodes)->map(fn ($barcode) => trim((string) $barcode))->filter()->unique()->values();
        if ($barcodes->isEmpty()) {
            throw ValidationException::withMessages(['barcodes' => 'Select at least one package label.']);
        }

        return DB::transaction(function () use ($handover, $member, $barcodes) {
            $items = $this->handoverItemsForBarcodes($handover, $barcodes);
            $allocated = 0;

            foreach ($items as $item) {
                if (! in_array($item->status, [
                    RiderTeamHandoverItem::STATUS_ASSIGNED_TO_LEADER,
                    RiderTeamHandoverItem::STATUS_LEADER_RECEIVED,
                    RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER,
                ], true)) {
                    throw ValidationException::withMessages(['barcodes' => 'One selected package cannot be allocated now.']);
                }

                $item->update([
                    'status' => RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER,
                    'allocated_to_driver_id' => $member->id,
                    'allocated_at' => now(),
                    'leader_received_at' => $item->leader_received_at ?? now(),
                ]);

                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $item->warehouse_receipt_item_label_id,
                    'event_type' => LabelCustodyEvent::TYPE_ALLOCATED_TO_MEMBER,
                    'driver_id' => $member->id,
                    'notes' => 'Allocated from rider team handover ' . $handover->handover_number,
                ]);

                $allocated++;
            }

            $this->refreshHandoverCounts($handover);

            return ['allocated' => $allocated];
        });
    }

    public function receiveTeamCustody(Driver $receiver, RiderTeam $team, string $barcode, ?float $latitude = null, ?float $longitude = null): array
    {
        if (! $team->is_active) {
            throw ValidationException::withMessages(['team_id' => 'This rider team is inactive.']);
        }

        if (! $receiver->is_active || ! $this->driverBelongsToTeam($receiver, $team)) {
            throw ValidationException::withMessages(['team_id' => 'You are not an active member of this rider team.']);
        }

        $barcode = trim($barcode);
        $label = WarehouseReceiptItemLabel::query()
            ->with(['receiptItem.receipt', 'latestCustody', 'riderTeamHandoverItem.handover.team'])
            ->where('barcode_value', $barcode)
            ->first();

        if (! $label) {
            throw ValidationException::withMessages(['barcode' => 'Label not found. Check the barcode and try again.']);
        }

        return DB::transaction(function () use ($receiver, $team, $label, $latitude, $longitude) {
            $existingItem = $label->riderTeamHandoverItem;
            if ($existingItem && ! in_array($existingItem->status, [
                RiderTeamHandoverItem::STATUS_DELIVERED,
                RiderTeamHandoverItem::STATUS_RETURNED,
                RiderTeamHandoverItem::STATUS_RECALLED,
            ], true)) {
                $existingItem->loadMissing('handover.team');
                $existingHandover = $existingItem->handover;

                if ((int) $existingHandover->rider_team_id !== (int) $team->id) {
                    throw ValidationException::withMessages(['barcode' => 'This package is assigned to another rider team.']);
                }

                if ($this->handoverReceiverId($existingHandover) !== (int) $receiver->id) {
                    throw ValidationException::withMessages(['barcode' => 'This package is already in another team handover.']);
                }

                if (! in_array($existingItem->status, [
                    RiderTeamHandoverItem::STATUS_ASSIGNED_TO_LEADER,
                    RiderTeamHandoverItem::STATUS_LEADER_RECEIVED,
                ], true)) {
                    throw ValidationException::withMessages(['barcode' => 'This package has already moved past team receiving.']);
                }

                if (! $existingItem->leader_received_at) {
                    $this->receiveByReceiver($existingHandover, $receiver, $label->barcode_value);
                }

                return [
                    'status' => 'already_in_team',
                    'message' => 'Package is already in ' . $team->name . ' custody.',
                    'handover' => $existingHandover->fresh(),
                    'item' => $existingItem->fresh(['label.receiptItem.shipmentItem']),
                ];
            }

            $this->assertLabelAssignableToReceiver($label);

            $handover = $this->activeReceiverHandover($team, $receiver)
                ?: $this->createHandover($team, $receiver, $team->warehouse, null, $receiver, 'Created from rider team scanner.');

            $item = RiderTeamHandoverItem::create([
                'rider_team_handover_id' => $handover->id,
                'warehouse_receipt_item_label_id' => $label->id,
                'status' => RiderTeamHandoverItem::STATUS_LEADER_RECEIVED,
                'assigned_at' => now(),
                'leader_received_at' => now(),
                'notes' => 'Received from rider team scanner.',
            ]);

            LabelCustodyEvent::create([
                'warehouse_receipt_item_label_id' => $label->id,
                'event_type' => LabelCustodyEvent::TYPE_ASSIGNED_TO_LEADER,
                'driver_id' => $receiver->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'notes' => 'Rider team scanner handover ' . $handover->handover_number,
            ]);

            LabelCustodyEvent::create([
                'warehouse_receipt_item_label_id' => $label->id,
                'event_type' => LabelCustodyEvent::TYPE_LEADER_RECEIVED,
                'driver_id' => $receiver->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'notes' => 'Received into ' . $team->name . ' custody.',
            ]);

            $this->refreshHandoverCounts($handover);

            return [
                'status' => 'team_received',
                'message' => 'Package added to ' . $team->name . ' custody.',
                'handover' => $handover->fresh(),
                'item' => $item->fresh(['label.receiptItem.shipmentItem']),
            ];
        });
    }

    public function claimFromScan(Driver $driver, WarehouseReceiptItemLabel $label, ?float $latitude = null, ?float $longitude = null, ?string $notes = null): array
    {
        $label->loadMissing(['receiptItem.receipt', 'latestCustody', 'riderTeamHandoverItem.handover.team']);
        $latest = $label->latestCustody;

        if ($latest && in_array($latest->event_type, [LabelCustodyEvent::TYPE_CLAIMED, LabelCustodyEvent::TYPE_MEMBER_CLAIMED], true)) {
            if ((int) $latest->driver_id === (int) $driver->id) {
                return ['status' => 'already_claimed', 'message' => 'You already have this package.'];
            }

            $otherDriver = Driver::find($latest->driver_id);
            return [
                'status' => 'conflict',
                'message' => 'This package is already claimed by ' . ($otherDriver?->name ?? 'another rider') . '.',
            ];
        }

        $handoverItem = $label->riderTeamHandoverItem;
        if ($handoverItem && ! in_array($handoverItem->status, [
            RiderTeamHandoverItem::STATUS_DELIVERED,
            RiderTeamHandoverItem::STATUS_RETURNED,
            RiderTeamHandoverItem::STATUS_RECALLED,
        ], true)) {
            return $this->claimReceiverHeldLabel($driver, $handoverItem, $latitude, $longitude, $notes);
        }

        $this->assertLabelCanBeClaimedDirectly($label);

        LabelCustodyEvent::create([
            'warehouse_receipt_item_label_id' => $label->id,
            'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
            'driver_id' => $driver->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'notes' => $notes,
        ]);

        return ['status' => 'claimed', 'message' => 'Package claimed successfully.'];
    }

    public function refreshHandoverCounts(RiderTeamHandover $handover): RiderTeamHandover
    {
        $items = $handover->items()->get(['status', 'leader_received_at', 'allocated_to_driver_id', 'member_claimed_at', 'delivered_at']);
        $assigned = $items->count();
        $received = $items->filter(fn ($item) => $item->leader_received_at !== null || in_array($item->status, [
            RiderTeamHandoverItem::STATUS_LEADER_RECEIVED,
            RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER,
            RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED,
            RiderTeamHandoverItem::STATUS_IN_DELIVERY,
            RiderTeamHandoverItem::STATUS_DELIVERED,
        ], true))->count();
        $distributed = $items->filter(fn ($item) => $item->allocated_to_driver_id !== null || in_array($item->status, [
            RiderTeamHandoverItem::STATUS_ALLOCATED_TO_MEMBER,
            RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED,
            RiderTeamHandoverItem::STATUS_IN_DELIVERY,
            RiderTeamHandoverItem::STATUS_DELIVERED,
        ], true))->count();
        $claimed = $items->filter(fn ($item) => $item->member_claimed_at !== null || in_array($item->status, [
            RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED,
            RiderTeamHandoverItem::STATUS_IN_DELIVERY,
            RiderTeamHandoverItem::STATUS_DELIVERED,
        ], true))->count();
        $delivered = $items->where('status', RiderTeamHandoverItem::STATUS_DELIVERED)->count();
        $failed = $items->where('status', RiderTeamHandoverItem::STATUS_FAILED)->count();

        $status = match (true) {
            $assigned === 0 => RiderTeamHandover::STATUS_DRAFT,
            $delivered > 0 && $delivered === $assigned => RiderTeamHandover::STATUS_CLOSED,
            $distributed > 0 && $distributed === $assigned => RiderTeamHandover::STATUS_DISTRIBUTED,
            $distributed > 0 => RiderTeamHandover::STATUS_PARTIALLY_DISTRIBUTED,
            $received > 0 && $received === $assigned => RiderTeamHandover::STATUS_RECEIVED,
            $received > 0 => RiderTeamHandover::STATUS_PARTIALLY_RECEIVED,
            default => RiderTeamHandover::STATUS_ASSIGNED,
        };

        $handover->update([
            'assigned_count' => $assigned,
            'received_count' => $received,
            'distributed_count' => $distributed,
            'claimed_count' => $claimed,
            'delivered_count' => $delivered,
            'failed_count' => $failed,
            'status' => $status,
            'assigned_at' => $assigned > 0 ? ($handover->assigned_at ?? now()) : null,
            'received_at' => $received === $assigned && $assigned > 0 ? ($handover->received_at ?? now()) : $handover->received_at,
        ]);

        return $handover->fresh();
    }

    private function claimReceiverHeldLabel(Driver $driver, RiderTeamHandoverItem $handoverItem, ?float $latitude, ?float $longitude, ?string $notes): array
    {
        $handoverItem->loadMissing('handover.team');
        $handover = $handoverItem->handover;
        $team = $handover->team;

        if (! $this->driverBelongsToTeam($driver, $team)) {
            return [
                'status' => 'conflict',
                'message' => 'This package is assigned to another rider team.',
            ];
        }

        if ($this->handoverReceiverId($handover) === (int) $driver->id && ! $handoverItem->allocated_to_driver_id) {
            $this->receiveByReceiver($handover, $driver, $handoverItem->label->barcode_value);

            return [
                'status' => 'receiver_received',
                'message' => 'Package received into rider team handover.',
            ];
        }

        if ($handoverItem->allocated_to_driver_id && (int) $handoverItem->allocated_to_driver_id !== (int) $driver->id) {
            $assigned = Driver::find($handoverItem->allocated_to_driver_id);
            return [
                'status' => 'conflict',
                'message' => 'This package is already allocated to ' . ($assigned?->name ?? 'another rider') . '.',
            ];
        }

        if ($this->labelIsInActiveDeliveryRun($handoverItem->label)) {
            return [
                'status' => 'conflict',
                'message' => 'This package is already in an active delivery run.',
            ];
        }

        DB::transaction(function () use ($driver, $handoverItem, $handover, $latitude, $longitude, $notes) {
            if (! $handoverItem->leader_received_at) {
                $handoverItem->leader_received_at = now();
                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $handoverItem->warehouse_receipt_item_label_id,
                    'event_type' => LabelCustodyEvent::TYPE_LEADER_RECEIVED,
                    'driver_id' => $this->handoverReceiverId($handover),
                    'notes' => 'Auto-confirmed before team member claim.',
                ]);
            }

            if (! $handoverItem->allocated_to_driver_id) {
                $handoverItem->allocated_to_driver_id = $driver->id;
                $handoverItem->allocated_at = now();
                LabelCustodyEvent::create([
                    'warehouse_receipt_item_label_id' => $handoverItem->warehouse_receipt_item_label_id,
                    'event_type' => LabelCustodyEvent::TYPE_ALLOCATED_TO_MEMBER,
                    'driver_id' => $driver->id,
                    'notes' => 'Auto-allocated by rider team member scan.',
                ]);
            }

            $handoverItem->status = RiderTeamHandoverItem::STATUS_MEMBER_CLAIMED;
            $handoverItem->member_claimed_at = now();
            $handoverItem->save();

            LabelCustodyEvent::create([
                'warehouse_receipt_item_label_id' => $handoverItem->warehouse_receipt_item_label_id,
                'event_type' => LabelCustodyEvent::TYPE_MEMBER_CLAIMED,
                'driver_id' => $driver->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'notes' => $notes,
            ]);

            LabelCustodyEvent::create([
                'warehouse_receipt_item_label_id' => $handoverItem->warehouse_receipt_item_label_id,
                'event_type' => LabelCustodyEvent::TYPE_CLAIMED,
                'driver_id' => $driver->id,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'notes' => $notes,
            ]);

            $this->refreshHandoverCounts($handover);
        });

        return ['status' => 'claimed', 'message' => 'Package claimed from rider team handover.'];
    }

    private function assertLabelAssignableToReceiver(WarehouseReceiptItemLabel $label): void
    {
        $receipt = $label->receiptItem?->receipt;
        if (! $receipt || $receipt->status !== WarehouseReceipt::STATUS_FINALIZED) {
            throw ValidationException::withMessages(['barcodes' => "{$label->barcode_value} is not ready. Warehouse receiving has not been finalized."]);
        }

        if ($label->riderTeamHandoverItem) {
            throw ValidationException::withMessages(['barcodes' => "{$label->barcode_value} is already attached to a rider team handover."]);
        }

        if ($this->labelIsInActiveDeliveryRun($label)) {
            throw ValidationException::withMessages(['barcodes' => "{$label->barcode_value} is already in an active delivery run."]);
        }

        $latest = $label->latestCustody;
        if ($latest && in_array($latest->event_type, [
            LabelCustodyEvent::TYPE_CLAIMED,
            LabelCustodyEvent::TYPE_MEMBER_CLAIMED,
            LabelCustodyEvent::TYPE_DELIVERED,
        ], true)) {
            throw ValidationException::withMessages(['barcodes' => "{$label->barcode_value} is already claimed or delivered."]);
        }
    }

    private function assertLabelCanBeClaimedDirectly(WarehouseReceiptItemLabel $label): void
    {
        $receipt = $label->receiptItem?->receipt;
        if (! $receipt || $receipt->status !== WarehouseReceipt::STATUS_FINALIZED) {
            throw ValidationException::withMessages(['barcode' => 'This package is not ready for rider pickup. Warehouse receiving has not been finalized yet.']);
        }

        if ($this->labelIsInActiveDeliveryRun($label)) {
            throw ValidationException::withMessages(['barcode' => 'This package is already in an active delivery run.']);
        }
    }

    private function handoverItemForBarcode(RiderTeamHandover $handover, string $barcode): RiderTeamHandoverItem
    {
        $item = $handover->items()
            ->whereHas('label', fn ($query) => $query->where('barcode_value', trim($barcode)))
            ->with('label.receiptItem.shipmentItem')
            ->first();

        if (! $item) {
            throw ValidationException::withMessages(['barcode' => 'This package is not part of this rider team handover.']);
        }

        return $item;
    }

    private function handoverItemsForBarcodes(RiderTeamHandover $handover, Collection $barcodes): EloquentCollection
    {
        $items = $handover->items()
            ->whereHas('label', fn ($query) => $query->whereIn('barcode_value', $barcodes))
            ->with('label')
            ->get();

        if ($items->count() !== $barcodes->count()) {
            throw ValidationException::withMessages(['barcodes' => 'One or more selected labels were not found in this handover.']);
        }

        return $items;
    }

    private function hasActiveRole(Driver $driver, RiderTeam $team, string $role): bool
    {
        return RiderTeamMembership::query()
            ->where('rider_team_id', $team->id)
            ->where('driver_id', $driver->id)
            ->where('role', $role)
            ->where('is_active', true)
            ->whereNull('removed_at')
            ->exists();
    }

    private function activeReceiverHandover(RiderTeam $team, Driver $receiver): ?RiderTeamHandover
    {
        return RiderTeamHandover::query()
            ->where('rider_team_id', $team->id)
            ->where(function ($query) use ($receiver) {
                $query->where('receiver_driver_id', $receiver->id)
                    ->orWhere(function ($query) use ($receiver) {
                        $query->whereNull('receiver_driver_id')
                            ->where('leader_driver_id', $receiver->id);
                    });
            })
            ->whereNotIn('status', [RiderTeamHandover::STATUS_CLOSED, RiderTeamHandover::STATUS_RECALLED])
            ->latest('id')
            ->first();
    }

    private function handoverReceiverId(RiderTeamHandover $handover): int
    {
        return (int) ($handover->receiver_driver_id ?: $handover->leader_driver_id);
    }

    private function labelIsInActiveDeliveryRun(?WarehouseReceiptItemLabel $label): bool
    {
        $shipmentItemId = $label?->receiptItem?->shipment_item_id;
        if (! $shipmentItemId) {
            return false;
        }

        return DeliveryRunItem::query()
            ->where('shipment_item_id', $shipmentItemId)
            ->whereHas('run', fn ($query) => $query->whereNotIn('status', ['completed', 'cancelled']))
            ->exists();
    }

    private function generateHandoverNumber(?Warehouse $warehouse = null): string
    {
        $prefix = 'RTH-' . now()->format('Y') . '-' . ($warehouse?->code ? str_replace('-', '', $warehouse->code) : 'GEN') . '-';
        $last = RiderTeamHandover::query()
            ->where('handover_number', 'like', $prefix . '%')
            ->latest('id')
            ->first();

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last->handover_number, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
