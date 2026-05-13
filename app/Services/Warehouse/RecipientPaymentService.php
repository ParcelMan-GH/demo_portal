<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Models\DeliveryRun;
use App\Models\PaymentWallet;
use App\Models\RecipientPaymentCallAttempt;
use App\Models\RecipientPaymentGroup;
use App\Models\RecipientPaymentSession;
use App\Models\RecipientPaymentSessionEntry;
use App\Models\RecipientPaymentTask;
use App\Models\ShipmentCharge;
use App\Models\ShipmentItem;
use App\Models\SortBatch;
use App\Models\SortBatchItem;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use App\Services\ChargesService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RecipientPaymentService
{
    public function __construct(private ChargesService $chargesService) {}

    public function paymentGroupForBatch(SortBatch $batch): string
    {
        return $batch->dispatch_mode === SortBatch::DISPATCH_LOCAL_DELIVERY
            ? RecipientPaymentTask::GROUP_LOCAL_DELIVERY
            : RecipientPaymentTask::GROUP_WAREHOUSE_TRANSFER;
    }

    public function ensureTaskForSortBatchItem(SortBatchItem $batchItem): ?RecipientPaymentTask
    {
        if (!Schema::hasTable('recipient_payment_tasks')) {
            return null;
        }

        $batchItem->loadMissing('sortBatch.originWarehouse', 'shipmentItem.shipment');
        $batch = $batchItem->sortBatch;
        $item = $batchItem->shipmentItem;

        if (!$batch || !$item || !$item->shipment) {
            return null;
        }

        $recipientPhone = $item->delivery_recipient_phone ?: $item->shipment->delivery_recipient_phone;
        if (!$recipientPhone) {
            return null;
        }

        $charge = $this->deliveryFeeChargeForItem($item);
        $task = RecipientPaymentTask::query()
            ->where('shipment_item_id', $item->id)
            ->where('sort_batch_id', $batch->id)
            ->first();

        if (!$task) {
            $task = RecipientPaymentTask::query()
                ->where('shipment_item_id', $item->id)
                ->whereNull('sort_batch_id')
                ->where('payment_group', RecipientPaymentTask::GROUP_LOCAL_DELIVERY)
                ->first();
        }

        if (!$task) {
            $task = new RecipientPaymentTask([
                'shipment_item_id' => $item->id,
            ]);
        }

        $task->fill([
            'shipment_id' => $item->shipment_id,
            'shipment_charge_id' => $charge?->id,
            'sort_batch_id' => $batch->id,
            'sort_batch_item_id' => $batchItem->id,
            'warehouse_id' => $batch->origin_warehouse_id,
            'payment_group' => $this->paymentGroupForBatch($batch),
            'status' => $this->statusForTaskSync($charge, $task->exists ? $task->status : null),
            'recipient_name' => $item->delivery_recipient_name ?: $item->shipment->delivery_recipient_name,
            'recipient_phone' => $recipientPhone,
            'delivery_town' => $item->delivery_town ?: $item->shipment->delivery_town,
            'negotiated_amount' => $charge?->amount,
            'currency' => $charge?->currency ?: ChargesService::DEFAULT_CURRENCY,
            'paid_at' => $charge?->paid_at,
            'payment_reference' => $charge?->payment_reference,
            'cancelled_at' => null,
        ]);
        $task->save();

        RecipientPaymentTask::query()
            ->where('shipment_item_id', $item->id)
            ->whereNull('sort_batch_id')
            ->whereKeyNot($task->id)
            ->whereNull('cancelled_at')
            ->update([
                'status' => RecipientPaymentTask::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

        return $task->fresh(['shipmentItem.shipment', 'shipmentCharge', 'sortBatch']);
    }

    public function cancelTaskForSortBatchItem(SortBatchItem $batchItem): void
    {
        if (!Schema::hasTable('recipient_payment_tasks')) {
            return;
        }

        RecipientPaymentTask::query()
            ->where('sort_batch_item_id', $batchItem->id)
            ->whereNotIn('status', [
                RecipientPaymentTask::STATUS_PAID,
                RecipientPaymentTask::STATUS_WAIVED,
                RecipientPaymentTask::STATUS_OVERRIDDEN,
            ])
            ->update([
                'status' => RecipientPaymentTask::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);
    }

    public function ensureTasksForBatch(SortBatch $batch): int
    {
        $batch->loadMissing('activeItems.shipmentItem.shipment');

        $count = 0;
        foreach ($batch->activeItems as $batchItem) {
            if ($this->ensureTaskForSortBatchItem($batchItem)) {
                $count++;
            }
        }

        return $count;
    }

    public function ensureLocalDeliveryTasksForWarehouse(Warehouse $warehouse, int $limit = 500): int
    {
        if (!Schema::hasTable('recipient_payment_tasks')) {
            return 0;
        }

        $count = 0;

        $this->localDeliveryWarehouseItemsQuery($warehouse)
            ->limit($limit)
            ->get()
            ->each(function (WarehouseReceiptItem $receiptItem) use ($warehouse, &$count) {
                if ($this->ensureLocalDeliveryTaskForReceiptItem($receiptItem, $warehouse)) {
                    $count++;
                }
            });

        return $count;
    }

    public function ensureLocalDeliveryTaskForReceiptItem(WarehouseReceiptItem $receiptItem, ?Warehouse $warehouse = null): ?RecipientPaymentTask
    {
        if (!Schema::hasTable('recipient_payment_tasks')) {
            return null;
        }

        $receiptItem->loadMissing('receipt', 'shipmentItem.shipment');

        $warehouse = $warehouse ?: $receiptItem->receipt?->warehouse;
        $item = $receiptItem->shipmentItem;

        if (!$warehouse || !$item || !$item->shipment) {
            return null;
        }

        if (!$this->isLocalDeliveryWarehouseReceiptItemEligible($receiptItem, $warehouse)) {
            return null;
        }

        return $this->ensureLocalDeliveryTaskForItem($item, $warehouse);
    }

    public function ensureLocalDeliveryTaskForShipmentItem(ShipmentItem $item, Warehouse $warehouse): ?RecipientPaymentTask
    {
        if (!Schema::hasTable('recipient_payment_tasks')) {
            return null;
        }

        $receiptItem = WarehouseReceiptItem::query()
            ->with(['receipt', 'shipmentItem.shipment'])
            ->where('shipment_item_id', $item->id)
            ->where('received_quantity', '>', 0)
            ->whereHas('receipt', fn (Builder $query) => $query
                ->where('warehouse_id', $warehouse->id)
                ->where('status', WarehouseReceipt::STATUS_FINALIZED))
            ->latest('id')
            ->first();

        if (!$receiptItem) {
            return null;
        }

        return $this->ensureLocalDeliveryTaskForReceiptItem($receiptItem, $warehouse);
    }

    public function queueQuery(?Warehouse $warehouse = null): Builder
    {
        $query = RecipientPaymentTask::query()
            ->with([
                'assignedTo:id,name',
                'paymentWallet:id,name,provider,phone_number',
                'shipmentItem.images',
                'shipmentItem.warehouseReceiptItems.photos',
                'shipmentItem.shipment.vendor',
                'shipmentItem.shipment.pickupAssignment.photos',
                'shipmentCharge',
                'sortBatch.originWarehouse',
                'sortBatch.destinationWarehouse',
            ])
            ->whereNull('cancelled_at')
            ->whereIn('status', [
                RecipientPaymentTask::STATUS_PENDING,
                RecipientPaymentTask::STATUS_ASSIGNED,
                RecipientPaymentTask::STATUS_IN_PROGRESS,
                RecipientPaymentTask::STATUS_FAILED,
                RecipientPaymentTask::STATUS_DISPUTED,
                RecipientPaymentTask::STATUS_PAID,
                RecipientPaymentTask::STATUS_WAIVED,
                RecipientPaymentTask::STATUS_OVERRIDDEN,
            ]);

        if ($warehouse) {
            $query->where('warehouse_id', $warehouse->id);
        }

        return $query;
    }

    public function blockingTasksForBatch(SortBatch $batch): Collection
    {
        $this->ensureTasksForBatch($batch);

        $activeItemIds = $batch->activeItems()->pluck('shipment_item_id');
        if ($activeItemIds->isEmpty()) {
            return collect();
        }

        return RecipientPaymentTask::query()
            ->with(['shipmentItem.shipment', 'shipmentCharge'])
            ->where('sort_batch_id', $batch->id)
            ->whereIn('shipment_item_id', $activeItemIds)
            ->whereNull('cancelled_at')
            ->get()
            ->filter(fn (RecipientPaymentTask $task) => !$this->taskIsCleared($task))
            ->values();
    }

    public function blockingSummaryForBatch(SortBatch $batch): array
    {
        $tasks = $this->blockingTasksForBatch($batch);

        return [
            'blocked' => $tasks->isNotEmpty(),
            'count' => $tasks->count(),
            'items' => $tasks->map(fn (RecipientPaymentTask $task) => [
                'task_id' => $task->id,
                'shipment_item_id' => $task->shipment_item_id,
                'shipment_number' => $task->shipmentItem?->shipment?->shipment_number,
                'tracking_code' => $task->shipmentItem?->tracking_code,
                'description' => $task->shipmentItem?->description,
                'recipient_name' => $task->recipient_name,
                'recipient_phone' => $task->recipient_phone,
                'status' => $task->status,
                'amount' => $task->negotiated_amount,
            ])->values()->all(),
        ];
    }

    public function summaryForBatch(SortBatch $batch): array
    {
        $this->ensureTasksForBatch($batch);

        $tasks = RecipientPaymentTask::query()
            ->where('sort_batch_id', $batch->id)
            ->whereNull('cancelled_at')
            ->get();

        return [
            'total' => $tasks->count(),
            'paid' => $tasks->where('status', RecipientPaymentTask::STATUS_PAID)->count(),
            'pending' => $tasks->reject(fn (RecipientPaymentTask $task) => $this->taskIsCleared($task))->count(),
            'waived' => $tasks->where('status', RecipientPaymentTask::STATUS_WAIVED)->count(),
            'overridden' => $tasks->where('status', RecipientPaymentTask::STATUS_OVERRIDDEN)->count(),
            'expected_total' => (float) $tasks->sum(fn ($task) => (float) ($task->negotiated_amount ?? 0)),
            'paid_total' => (float) $tasks->where('status', RecipientPaymentTask::STATUS_PAID)->sum(fn ($task) => (float) ($task->negotiated_amount ?? 0)),
        ];
    }

    public function assignTasks(array $taskIds, User $worker): int
    {
        return RecipientPaymentTask::query()
            ->whereIn('id', $taskIds)
            ->whereNotIn('status', [RecipientPaymentTask::STATUS_PAID, RecipientPaymentTask::STATUS_WAIVED, RecipientPaymentTask::STATUS_OVERRIDDEN])
            ->update([
                'assigned_to_user_id' => $worker->id,
                'assigned_at' => now(),
                'status' => RecipientPaymentTask::STATUS_ASSIGNED,
            ]);
    }

    public function claimTaskForUser(RecipientPaymentTask $task, User $user): array
    {
        return DB::transaction(function () use ($task, $user) {
            $task = RecipientPaymentTask::query()
                ->with('assignedTo:id,name')
                ->lockForUpdate()
                ->findOrFail($task->id);

            if ($this->taskIsCleared($task)) {
                return ['success' => true, 'message' => 'Package loaded.', 'task' => $task];
            }

            if ($task->assigned_to_user_id && (int) $task->assigned_to_user_id !== (int) $user->id) {
                return [
                    'success' => false,
                    'conflict' => true,
                    'message' => 'This package is already being processed by ' . ($task->assignedTo?->name ?: 'another user') . '.',
                    'task' => $task,
                ];
            }

            $task->update([
                'assigned_to_user_id' => $user->id,
                'assigned_at' => $task->assigned_at ?: now(),
                'status' => in_array($task->status, [RecipientPaymentTask::STATUS_PENDING, RecipientPaymentTask::STATUS_FAILED, RecipientPaymentTask::STATUS_DISPUTED], true)
                    ? RecipientPaymentTask::STATUS_ASSIGNED
                    : $task->status,
            ]);

            return ['success' => true, 'message' => 'Package assigned to you.', 'task' => $task->fresh(['assignedTo'])];
        });
    }

    public function releaseTaskForUser(RecipientPaymentTask $task, User $user): array
    {
        return DB::transaction(function () use ($task, $user) {
            $task = RecipientPaymentTask::query()
                ->lockForUpdate()
                ->findOrFail($task->id);

            if ($this->taskIsCleared($task)) {
                return ['success' => false, 'message' => 'Paid or cleared recipient payments cannot be released.'];
            }

            if ((int) $task->assigned_to_user_id !== (int) $user->id) {
                return ['success' => false, 'message' => 'Only the assigned user can release this package.'];
            }

            $task->update([
                'assigned_to_user_id' => null,
                'assigned_at' => null,
                'status' => RecipientPaymentTask::STATUS_PENDING,
            ]);

            return ['success' => true, 'message' => 'Package released.'];
        });
    }

    public function logCall(RecipientPaymentTask $task, User $user, string $outcome, ?string $notes = null): RecipientPaymentCallAttempt
    {
        $attempt = RecipientPaymentCallAttempt::query()->create([
            'recipient_payment_task_id' => $task->id,
            'attempted_by_user_id' => $user->id,
            'outcome' => $outcome,
            'notes' => $notes,
            'attempted_at' => now(),
        ]);

        if (in_array($task->status, [RecipientPaymentTask::STATUS_PENDING, RecipientPaymentTask::STATUS_ASSIGNED], true)) {
            $task->update(['status' => RecipientPaymentTask::STATUS_IN_PROGRESS]);
        }

        return $attempt;
    }

    public function setFee(RecipientPaymentTask $task, float $amount, User $user, ?string $notes = null): array
    {
        if ($this->taskIsCleared($task)) {
            return ['success' => false, 'message' => 'Cleared recipient payment tasks cannot be edited.'];
        }

        return DB::transaction(function () use ($task, $amount, $user, $notes) {
            $task = RecipientPaymentTask::query()->lockForUpdate()->findOrFail($task->id);
            $item = ShipmentItem::query()->with('shipment')->findOrFail($task->shipment_item_id);
            $charge = $task->shipmentCharge ?: $this->deliveryFeeChargeForItem($item);

            if ($charge && !$charge->isOutstanding()) {
                return ['success' => false, 'message' => 'Only outstanding delivery fees can be edited.'];
            }

            if (!$charge) {
                $charge = $this->chargesService->addCharge($item->shipment, [
                    'shipment_item_id' => $item->id,
                    'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
                    'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
                    'due_stage' => ShipmentCharge::STAGE_BEFORE_DELIVERY,
                    'amount' => $amount,
                    'status' => ShipmentCharge::STATUS_PENDING,
                    'notes' => $notes,
                ], $user);
            } else {
                $result = $this->chargesService->updateCharge($charge, [
                    'amount' => $amount,
                    'notes' => $notes ?? $charge->notes,
                ], $user);
                if (!($result['success'] ?? false)) {
                    return $result;
                }
                $charge = $result['charge'];
            }

            $task->update([
                'shipment_charge_id' => $charge->id,
                'negotiated_amount' => $charge->amount,
                'currency' => $charge->currency,
                'status' => $task->assigned_to_user_id ? RecipientPaymentTask::STATUS_IN_PROGRESS : RecipientPaymentTask::STATUS_PENDING,
                'notes' => $notes ?? $task->notes,
            ]);

            return ['success' => true, 'message' => 'Delivery fee saved.', 'task' => $task->fresh('shipmentCharge')];
        });
    }

    public function markPaid(RecipientPaymentTask $task, PaymentWallet $wallet, User $user, ?string $reference, ?string $notes = null, bool $requireAssignedWallet = true): array
    {
        if ($this->taskIsCleared($task)) {
            return ['success' => false, 'message' => 'This recipient payment is already cleared.'];
        }

        if ($requireAssignedWallet && !$wallet->assignedUsers()->whereKey($user->id)->exists()) {
            return ['success' => false, 'message' => 'You can only record payments into an approved wallet assigned to you.'];
        }

        return DB::transaction(function () use ($task, $wallet, $user, $reference, $notes) {
            $task = RecipientPaymentTask::query()->lockForUpdate()->findOrFail($task->id);
            $wallet = PaymentWallet::query()->lockForUpdate()->findOrFail($wallet->id);

            if (!$wallet->is_active) {
                return ['success' => false, 'message' => 'This wallet is inactive.'];
            }

            $charge = $task->shipmentCharge;
            if (!$charge) {
                return ['success' => false, 'message' => 'Set the delivery fee amount before marking payment as paid.'];
            }

            $session = RecipientPaymentSession::query()
                ->where('user_id', $user->id)
                ->where('warehouse_id', $task->warehouse_id)
                ->where('payment_wallet_id', $wallet->id)
                ->where('status', RecipientPaymentSession::STATUS_OPEN)
                ->whereDate('started_at', today())
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return ['success' => false, 'message' => 'Start a payment session for this wallet before recording payments.'];
            }

            $result = $this->chargesService->markPaid($charge, 'momo', $reference, $user);
            if (!($result['success'] ?? false)) {
                return $result;
            }

            $charge = $result['charge'];
            $task->update([
                'status' => RecipientPaymentTask::STATUS_PAID,
                'paid_at' => $charge->paid_at,
                'payment_wallet_id' => $wallet->id,
                'payment_reference' => $reference,
                'notes' => $notes ?? $task->notes,
                'negotiated_amount' => $charge->amount,
            ]);

            RecipientPaymentSessionEntry::query()->create([
                'recipient_payment_session_id' => $session->id,
                'recipient_payment_task_id' => $task->id,
                'shipment_charge_id' => $charge->id,
                'entry_type' => RecipientPaymentSessionEntry::TYPE_PAYMENT,
                'amount' => $charge->amount,
                'currency' => $charge->currency,
                'reference' => $reference,
                'notes' => $notes,
                'recorded_by_user_id' => $user->id,
            ]);

            return ['success' => true, 'message' => 'Recipient payment marked paid.', 'task' => $task->fresh(['shipmentCharge', 'paymentWallet'])];
        });
    }

    public function updateRecipientDetails(Collection $tasks, User $user, ?string $phone = null, ?string $deliveryTown = null): array
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return ['success' => false, 'message' => 'Recipient phone number is required.'];
        }
        $deliveryTown = trim((string) $deliveryTown);

        DB::transaction(function () use ($tasks, $phone, $deliveryTown) {
            foreach ($tasks as $task) {
                $task->loadMissing('shipmentItem');
                $task->update([
                    'recipient_phone' => $phone,
                    'delivery_town' => $deliveryTown !== '' ? $deliveryTown : $task->delivery_town,
                ]);
                $task->shipmentItem?->update([
                    'delivery_recipient_phone' => $phone,
                    'delivery_town' => $deliveryTown !== '' ? $deliveryTown : $task->shipmentItem->delivery_town,
                ]);
            }
        });

        return ['success' => true, 'message' => 'Recipient details updated.'];
    }

    public function markRecipientGroupPaid(Collection $tasks, float $amount, PaymentWallet $wallet, User $user, ?string $reference, ?string $notes = null, bool $requireAssignedWallet = true, ?string $receiptPath = null): array
    {
        if ($tasks->isEmpty()) {
            return ['success' => false, 'message' => 'No recipient payment tasks found.'];
        }

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Enter the agreed delivery fee before marking payment as paid.'];
        }

        if ($requireAssignedWallet && !$wallet->assignedUsers()->whereKey($user->id)->exists()) {
            return ['success' => false, 'message' => 'You can only record payments into an approved wallet assigned to you.'];
        }

        return DB::transaction(function () use ($tasks, $amount, $wallet, $user, $reference, $notes, $receiptPath) {
            $wallet = PaymentWallet::query()->lockForUpdate()->findOrFail($wallet->id);
            if (!$wallet->is_active) {
                return ['success' => false, 'message' => 'This wallet is inactive.'];
            }

            $taskIds = $tasks->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
            $lockedTasks = RecipientPaymentTask::query()
                ->with(['shipmentItem.shipment', 'shipmentCharge', 'paymentGroupRecord', 'sessionEntries'])
                ->whereIn('id', $taskIds)
                ->lockForUpdate()
                ->get()
                ->sortBy(fn (RecipientPaymentTask $task) => array_search($task->id, $taskIds, true))
                ->values();

            $openTasks = $lockedTasks->reject(fn (RecipientPaymentTask $task) => $this->taskIsCleared($task))->values();
            if ($openTasks->isEmpty()) {
                return $this->correctRecipientGroupPayment($lockedTasks, $amount, $wallet, $user, $reference, $notes, $receiptPath);
            }

            /** @var RecipientPaymentTask $primaryTask */
            $primaryTask = $openTasks->first();
            $session = RecipientPaymentSession::query()
                ->where('user_id', $user->id)
                ->where('warehouse_id', $primaryTask->warehouse_id)
                ->where('payment_wallet_id', $wallet->id)
                ->where('status', RecipientPaymentSession::STATUS_OPEN)
                ->whereDate('started_at', today())
                ->lockForUpdate()
                ->first();

            if (!$session) {
                return ['success' => false, 'message' => 'Start a payment session for this wallet before recording payments.'];
            }

            $primaryCharge = $primaryTask->shipmentCharge ?: $this->deliveryFeeChargeForItem($primaryTask->shipmentItem);
            if ($primaryCharge && !$primaryCharge->isOutstanding()) {
                return ['success' => false, 'message' => 'This recipient payment has already been cleared.'];
            }

            if (!$primaryCharge) {
                $primaryCharge = $this->chargesService->addCharge($primaryTask->shipmentItem->shipment, [
                    'shipment_item_id' => $primaryTask->shipment_item_id,
                    'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
                    'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
                    'due_stage' => ShipmentCharge::STAGE_BEFORE_DELIVERY,
                    'amount' => $amount,
                    'status' => ShipmentCharge::STATUS_PENDING,
                    'notes' => $notes,
                ], $user);
            } else {
                $update = $this->chargesService->updateCharge($primaryCharge, [
                    'amount' => $amount,
                    'notes' => $notes ?? $primaryCharge->notes,
                ], $user);
                if (!($update['success'] ?? false)) {
                    return $update;
                }
                $primaryCharge = $update['charge'];
            }

            $paidResult = $this->chargesService->markPaid($primaryCharge, 'momo', $reference, $user);
            if (!($paidResult['success'] ?? false)) {
                return $paidResult;
            }
            $primaryCharge = $paidResult['charge'];

            $group = RecipientPaymentGroup::query()->create([
                'group_key' => $this->groupKeyForTasks($openTasks),
                'payment_group' => $primaryTask->payment_group,
                'warehouse_id' => $primaryTask->warehouse_id,
                'assigned_to_user_id' => $primaryTask->assigned_to_user_id,
                'primary_task_id' => $primaryTask->id,
                'shipment_charge_id' => $primaryCharge->id,
                'payment_wallet_id' => $wallet->id,
                'recipient_name' => $primaryTask->recipient_name,
                'recipient_phone' => $primaryTask->recipient_phone,
                'delivery_town' => $primaryTask->delivery_town,
                'amount' => $primaryCharge->amount,
                'currency' => $primaryCharge->currency,
                'status' => RecipientPaymentGroup::STATUS_PAID,
                'payment_reference' => $reference,
                'receipt_path' => $receiptPath,
                'paid_at' => $primaryCharge->paid_at,
                'created_by_user_id' => $user->id,
                'paid_by_user_id' => $user->id,
                'notes' => $notes,
            ]);

            foreach ($openTasks as $task) {
                if ((int) $task->id === (int) $primaryTask->id) {
                    $task->update([
                        'recipient_payment_group_id' => $group->id,
                        'shipment_charge_id' => $primaryCharge->id,
                        'status' => RecipientPaymentTask::STATUS_PAID,
                        'paid_at' => $primaryCharge->paid_at,
                        'payment_wallet_id' => $wallet->id,
                        'payment_reference' => $reference,
                        'negotiated_amount' => $primaryCharge->amount,
                        'notes' => $notes ?? $task->notes,
                    ]);
                    continue;
                }

                $charge = $task->shipmentCharge ?: ($task->shipmentItem ? $this->deliveryFeeChargeForItem($task->shipmentItem) : null);
                if (!$charge) {
                    $charge = $this->chargesService->addCharge($task->shipmentItem->shipment, [
                        'shipment_item_id' => $task->shipment_item_id,
                        'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
                        'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
                        'due_stage' => ShipmentCharge::STAGE_BEFORE_DELIVERY,
                        'amount' => 0,
                        'status' => ShipmentCharge::STATUS_PENDING,
                        'notes' => 'Covered by recipient group payment ' . $group->id,
                    ], $user);
                }
                if ($charge->isOutstanding()) {
                    $this->chargesService->waive($charge, 'Covered by recipient group payment ' . $group->id, $user);
                }

                $task->update([
                    'recipient_payment_group_id' => $group->id,
                    'shipment_charge_id' => $charge->id,
                    'status' => RecipientPaymentTask::STATUS_PAID,
                    'paid_at' => $primaryCharge->paid_at,
                    'payment_wallet_id' => $wallet->id,
                    'payment_reference' => $reference,
                    'negotiated_amount' => 0,
                    'notes' => $notes ?? $task->notes,
                ]);
            }

            RecipientPaymentSessionEntry::query()->create([
                'recipient_payment_session_id' => $session->id,
                'recipient_payment_task_id' => $primaryTask->id,
                'recipient_payment_group_id' => $group->id,
                'shipment_charge_id' => $primaryCharge->id,
                'entry_type' => RecipientPaymentSessionEntry::TYPE_PAYMENT,
                'amount' => $primaryCharge->amount,
                'currency' => $primaryCharge->currency,
                'reference' => $reference,
                'receipt_path' => $receiptPath,
                'notes' => $notes,
                'recorded_by_user_id' => $user->id,
            ]);

            return ['success' => true, 'message' => 'Recipient payment recorded for this delivery.', 'group' => $group->fresh()];
        });
    }

    private function correctRecipientGroupPayment(Collection $tasks, float $amount, PaymentWallet $wallet, User $user, ?string $reference, ?string $notes = null, ?string $receiptPath = null): array
    {
        $group = $tasks
            ->pluck('paymentGroupRecord')
            ->filter(fn (?RecipientPaymentGroup $group) => $group && $group->status === RecipientPaymentGroup::STATUS_PAID)
            ->first();

        if (!$group) {
            return ['success' => false, 'message' => 'This recipient payment is already cleared.'];
        }

        $primaryTask = $tasks->firstWhere('id', $group->primary_task_id) ?: $tasks->first();
        if (!$primaryTask || !$primaryTask->shipmentItem?->shipment) {
            return ['success' => false, 'message' => 'Could not find the package linked to this payment.'];
        }

        $primaryCharge = $group->shipmentCharge ?: $primaryTask->shipmentCharge ?: $this->deliveryFeeChargeForItem($primaryTask->shipmentItem);
        if (!$primaryCharge) {
            $primaryCharge = $this->chargesService->addCharge($primaryTask->shipmentItem->shipment, [
                'shipment_item_id' => $primaryTask->shipment_item_id,
                'charge_type' => ShipmentCharge::TYPE_DELIVERY_FEE,
                'payer_type' => ShipmentCharge::PAYER_RECIPIENT,
                'due_stage' => ShipmentCharge::STAGE_BEFORE_DELIVERY,
                'amount' => $amount,
                'status' => ShipmentCharge::STATUS_PAID,
                'payment_method' => 'momo',
                'payment_reference' => $reference,
                'notes' => $notes,
            ], $user);
        } else {
            $primaryCharge->update([
                'amount' => round($amount, 2),
                'status' => ShipmentCharge::STATUS_PAID,
                'paid_at' => $primaryCharge->paid_at ?: now(),
                'payment_method' => 'momo',
                'payment_reference' => $reference,
                'notes' => $notes ?? $primaryCharge->notes,
            ]);
            $primaryCharge = $primaryCharge->fresh();
        }

        $group->update([
            'shipment_charge_id' => $primaryCharge->id,
            'payment_wallet_id' => $wallet->id,
            'amount' => $primaryCharge->amount,
            'payment_reference' => $reference,
            'receipt_path' => $receiptPath ?: $group->receipt_path,
            'paid_at' => $primaryCharge->paid_at,
            'paid_by_user_id' => $user->id,
            'notes' => $notes ?? $group->notes,
        ]);

        $entry = RecipientPaymentSessionEntry::query()
            ->where('recipient_payment_group_id', $group->id)
            ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
            ->latest()
            ->first();

        if (!$entry) {
            $entry = RecipientPaymentSessionEntry::query()
                ->whereIn('recipient_payment_task_id', $tasks->pluck('id')->all())
                ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
                ->latest()
                ->first();
        }

        if ($entry) {
            $entry->update([
                'recipient_payment_task_id' => $primaryTask->id,
                'recipient_payment_group_id' => $group->id,
                'shipment_charge_id' => $primaryCharge->id,
                'amount' => $primaryCharge->amount,
                'currency' => $primaryCharge->currency,
                'reference' => $reference,
                'receipt_path' => $receiptPath ?: $entry->receipt_path,
                'notes' => $notes ?? $entry->notes,
                'recorded_by_user_id' => $user->id,
            ]);
        }

        foreach ($tasks as $task) {
            $task->update([
                'recipient_payment_group_id' => $group->id,
                'shipment_charge_id' => (int) $task->id === (int) $primaryTask->id ? $primaryCharge->id : $task->shipment_charge_id,
                'status' => RecipientPaymentTask::STATUS_PAID,
                'paid_at' => $primaryCharge->paid_at,
                'payment_wallet_id' => $wallet->id,
                'payment_reference' => $reference,
                'negotiated_amount' => (int) $task->id === (int) $primaryTask->id ? $primaryCharge->amount : 0,
                'notes' => $notes ?? $task->notes,
            ]);
        }

        return ['success' => true, 'message' => 'Recipient payment updated.', 'group' => $group->fresh()];
    }

    public function override(RecipientPaymentTask $task, User $user, string $reason): array
    {
        if ($task->status === RecipientPaymentTask::STATUS_PAID) {
            return ['success' => false, 'message' => 'Paid recipient payments do not need an override.'];
        }

        $task->update([
            'status' => RecipientPaymentTask::STATUS_OVERRIDDEN,
            'override_by_user_id' => $user->id,
            'override_at' => now(),
            'override_reason' => $reason,
        ]);

        return ['success' => true, 'message' => 'Recipient payment override recorded.', 'task' => $task->fresh()];
    }

    public function startSession(User $user, Warehouse $warehouse, PaymentWallet $wallet, float $openingBalance, ?string $notes = null): array
    {
        if (!$wallet->is_active) {
            return ['success' => false, 'message' => 'Cannot start a session for an inactive wallet.'];
        }

        $existing = RecipientPaymentSession::query()
            ->where('user_id', $user->id)
            ->where('payment_wallet_id', $wallet->id)
            ->where('status', RecipientPaymentSession::STATUS_OPEN)
            ->first();

        if ($existing) {
            if (!$existing->started_at?->isToday()) {
                return ['success' => false, 'message' => 'Close your previous open session before starting today.'];
            }

            return ['success' => false, 'message' => 'You already have an open session for this wallet.'];
        }

        $session = RecipientPaymentSession::query()->create([
            'user_id' => $user->id,
            'warehouse_id' => $warehouse->id,
            'payment_wallet_id' => $wallet->id,
            'opening_balance' => $openingBalance,
            'status' => RecipientPaymentSession::STATUS_OPEN,
            'started_at' => now(),
            'notes' => $notes,
        ]);

        return ['success' => true, 'message' => 'Payment session started.', 'session' => $session];
    }

    public function closeSession(RecipientPaymentSession $session, User $user, float $closingBalance, ?string $notes = null): array
    {
        if ($session->status !== RecipientPaymentSession::STATUS_OPEN) {
            return ['success' => false, 'message' => 'Only open payment sessions can be closed.'];
        }

        $incoming = (float) $session->entries()
            ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
            ->sum('amount');
        $adjustments = (float) $session->entries()
            ->where('entry_type', RecipientPaymentSessionEntry::TYPE_ADJUSTMENT)
            ->sum('amount');
        $expected = round((float) $session->opening_balance + $incoming - $adjustments, 2);
        $variance = round($closingBalance - $expected, 2);

        $session->update([
            'closing_balance' => $closingBalance,
            'expected_closing_balance' => $expected,
            'variance' => $variance,
            'status' => abs($variance) > 0.009 ? RecipientPaymentSession::STATUS_DISPUTED : RecipientPaymentSession::STATUS_SUBMITTED,
            'closed_at' => now(),
            'notes' => $notes ?? $session->notes,
        ]);

        return ['success' => true, 'message' => 'Payment session closed.', 'session' => $session->fresh()];
    }

    public function taskIsCleared(RecipientPaymentTask $task): bool
    {
        if (in_array($task->status, [
            RecipientPaymentTask::STATUS_PAID,
            RecipientPaymentTask::STATUS_WAIVED,
            RecipientPaymentTask::STATUS_OVERRIDDEN,
        ], true)) {
            return true;
        }

        $charge = $task->shipmentCharge ?: ($task->shipmentItem ? $this->deliveryFeeChargeForItem($task->shipmentItem) : null);

        return $charge && in_array($charge->status, [
            ShipmentCharge::STATUS_PAID,
            ShipmentCharge::STATUS_WAIVED,
        ], true);
    }

    private function groupKeyForTasks(Collection $tasks): string
    {
        $first = $tasks->first();
        $phone = preg_replace('/\D+/', '', (string) $first?->recipient_phone);
        return ($phone !== '' ? $phone : 'task-' . $first?->id) . '-' . now()->format('YmdHis');
    }

    public function deliveryFeeChargeForItem(ShipmentItem $item): ?ShipmentCharge
    {
        return $item->charges()
            ->where('charge_type', ShipmentCharge::TYPE_DELIVERY_FEE)
            ->where('payer_type', ShipmentCharge::PAYER_RECIPIENT)
            ->whereNotIn('status', [ShipmentCharge::STATUS_CANCELLED])
            ->latest('id')
            ->first();
    }

    public function blockingSummaryForLocalDeliveryItems(Warehouse $warehouse, Collection $shipmentItems): array
    {
        if (!Schema::hasTable('recipient_payment_tasks')) {
            return ['blocked' => false, 'count' => 0, 'items' => []];
        }

        $itemIds = $shipmentItems
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($itemIds->isEmpty()) {
            return ['blocked' => false, 'count' => 0, 'items' => []];
        }

        foreach ($shipmentItems as $shipmentItem) {
            if ($shipmentItem instanceof ShipmentItem) {
                $this->ensureLocalDeliveryTaskForShipmentItem($shipmentItem, $warehouse);
            }
        }

        $tasks = RecipientPaymentTask::query()
            ->with(['shipmentItem.shipment', 'shipmentCharge'])
            ->where('warehouse_id', $warehouse->id)
            ->where('payment_group', RecipientPaymentTask::GROUP_LOCAL_DELIVERY)
            ->whereIn('shipment_item_id', $itemIds)
            ->whereNull('cancelled_at')
            ->get()
            ->filter(fn (RecipientPaymentTask $task) => !$this->taskIsCleared($task))
            ->values();

        return [
            'blocked' => $tasks->isNotEmpty(),
            'count' => $tasks->count(),
            'items' => $tasks->map(fn (RecipientPaymentTask $task) => [
                'task_id' => $task->id,
                'shipment_item_id' => $task->shipment_item_id,
                'shipment_number' => $task->shipmentItem?->shipment?->shipment_number,
                'tracking_code' => $task->shipmentItem?->tracking_code,
                'description' => $task->shipmentItem?->description,
                'recipient_name' => $task->recipient_name,
                'recipient_phone' => $task->recipient_phone,
                'status' => $task->status,
                'amount' => $task->negotiated_amount,
            ])->values()->all(),
        ];
    }

    private function statusForCharge(?ShipmentCharge $charge): string
    {
        return match ($charge?->status) {
            ShipmentCharge::STATUS_PAID => RecipientPaymentTask::STATUS_PAID,
            ShipmentCharge::STATUS_WAIVED => RecipientPaymentTask::STATUS_WAIVED,
            default => RecipientPaymentTask::STATUS_PENDING,
        };
    }

    private function statusForTaskSync(?ShipmentCharge $charge, ?string $currentStatus): string
    {
        if ($charge && in_array($charge->status, [ShipmentCharge::STATUS_PAID, ShipmentCharge::STATUS_WAIVED], true)) {
            return $this->statusForCharge($charge);
        }

        if ($currentStatus && $currentStatus !== RecipientPaymentTask::STATUS_CANCELLED) {
            return $currentStatus;
        }

        return RecipientPaymentTask::STATUS_PENDING;
    }

    private function ensureLocalDeliveryTaskForItem(ShipmentItem $item, Warehouse $warehouse): ?RecipientPaymentTask
    {
        $item->loadMissing('shipment');

        if (!$item->shipment) {
            return null;
        }

        $recipientPhone = $item->delivery_recipient_phone ?: $item->shipment->delivery_recipient_phone;
        if (!$recipientPhone) {
            return null;
        }

        $charge = $this->deliveryFeeChargeForItem($item);
        $task = RecipientPaymentTask::query()
            ->where('shipment_item_id', $item->id)
            ->whereNull('sort_batch_id')
            ->first() ?: new RecipientPaymentTask([
                'shipment_item_id' => $item->id,
                'sort_batch_id' => null,
            ]);

        $task->fill([
            'shipment_id' => $item->shipment_id,
            'shipment_charge_id' => $charge?->id,
            'sort_batch_item_id' => null,
            'warehouse_id' => $warehouse->id,
            'payment_group' => RecipientPaymentTask::GROUP_LOCAL_DELIVERY,
            'status' => $this->statusForTaskSync($charge, $task->exists ? $task->status : null),
            'recipient_name' => $item->delivery_recipient_name ?: $item->shipment->delivery_recipient_name,
            'recipient_phone' => $recipientPhone,
            'delivery_town' => $item->delivery_town ?: $item->shipment->delivery_town,
            'negotiated_amount' => $charge?->amount,
            'currency' => $charge?->currency ?: ChargesService::DEFAULT_CURRENCY,
            'paid_at' => $charge?->paid_at,
            'payment_reference' => $charge?->payment_reference,
            'cancelled_at' => null,
        ]);
        $task->save();

        return $task->fresh(['shipmentItem.shipment', 'shipmentCharge', 'sortBatch']);
    }

    private function localDeliveryWarehouseItemsQuery(Warehouse $warehouse): Builder
    {
        return WarehouseReceiptItem::query()
            ->with(['receipt', 'shipmentItem.shipment'])
            ->where('received_quantity', '>', 0)
            ->whereHas('receipt', fn (Builder $query) => $query
                ->where('warehouse_id', $warehouse->id)
                ->where('status', WarehouseReceipt::STATUS_FINALIZED))
            ->whereHas('shipmentItem', function (Builder $query) {
                $query->whereIn('status', [
                    ItemStatus::AT_WAREHOUSE->value,
                    ItemStatus::AT_DESTINATION->value,
                ]);

                $query->where(function (Builder $recipientQuery) {
                    $recipientQuery->whereNotNull('delivery_recipient_phone')
                        ->orWhereHas('shipment', fn (Builder $shipmentQuery) => $shipmentQuery->whereNotNull('delivery_recipient_phone'));
                });

                if (Schema::hasTable('delivery_run_items') && Schema::hasTable('delivery_runs')) {
                    $query->whereDoesntHave('deliveryRunItems', fn (Builder $deliveryQuery) => $deliveryQuery
                        ->whereHas('run', fn (Builder $runQuery) => $runQuery->where('status', '!=', DeliveryRun::STATUS_CANCELLED)));
                }

                if (Schema::hasTable('transport_manifest_items') && Schema::hasTable('transport_manifests')) {
                    $query->whereDoesntHave('transportManifestItems', fn (Builder $manifestItemQuery) => $manifestItemQuery
                        ->whereHas('manifest', fn (Builder $manifestQuery) => $manifestQuery->whereNotIn('status', [
                            TransportManifest::STATUS_RECEIVED,
                            TransportManifest::STATUS_CANCELLED,
                        ])));
                }
            })
            ->whereDoesntHave('activeSortBatchItem');
    }

    private function isLocalDeliveryWarehouseReceiptItemEligible(WarehouseReceiptItem $receiptItem, Warehouse $warehouse): bool
    {
        if ((int) $receiptItem->received_quantity <= 0) {
            return false;
        }

        if ((int) $receiptItem->receipt?->warehouse_id !== (int) $warehouse->id || $receiptItem->receipt?->status !== WarehouseReceipt::STATUS_FINALIZED) {
            return false;
        }

        $item = $receiptItem->shipmentItem;
        if (!$item || !$item->shipment) {
            return false;
        }

        $status = $item->status?->value ?? $item->getRawOriginal('status');
        if (!in_array($status, [ItemStatus::AT_WAREHOUSE->value, ItemStatus::AT_DESTINATION->value], true)) {
            return false;
        }

        if ($receiptItem->activeSortBatchItem()->exists()) {
            return false;
        }

        return $this->localDeliveryWarehouseItemsQuery($warehouse)
            ->whereKey($receiptItem->id)
            ->exists();
    }
}
