<?php

namespace App\Services\Warehouse;

use App\Enums\ItemStatus;
use App\Models\DeliveryRun;
use App\Models\DeliveryRunStop;
use App\Models\PackageContactTask;
use App\Models\RecipientPaymentCallAttempt;
use App\Models\RecipientPaymentSession;
use App\Models\RecipientPaymentSessionEntry;
use App\Models\RecipientPaymentTask;
use App\Models\SortBatch;
use App\Models\TransportManifest;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseReceipt;
use App\Models\WarehouseReceiptItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class WarehouseDashboardService
{
    public function __construct(private WarehousePortalService $portalService)
    {
    }

    public function data(Warehouse $warehouse, User $user): array
    {
        $counts = $this->counts($warehouse, $user);

        return [
            'counts' => $counts,
            'work_queues' => $this->filterByPermission($user, $this->workQueues($counts)),
            'workflow_lanes' => $this->filterWorkflowLanes($user, $this->workflowLanes($counts)),
            'exceptions' => $this->filterByPermission($user, $this->exceptions($counts)),
            'activity' => $this->recentActivity($warehouse, $user),
            'actions' => $this->filterByPermission($user, $this->actions($user)),
            'payment_dashboard' => $this->paymentDashboard($warehouse, $user, $counts),
        ];
    }

    private function counts(Warehouse $warehouse, User $user): array
    {
        $receivedItems = $this->receivedItemsQuery($warehouse);
        $paymentTasks = $this->paymentTasks($warehouse, $user);
        $personalPaymentQueue = !$this->canManagePaymentQueue($user);

        return [
            'pending_intake' => $this->portalService->pendingReceiptsQuery($warehouse)->count(),
            'received_today' => (clone $receivedItems)->whereDate('received_at', today())->count(),
            'warehouse_packages' => (clone $receivedItems)->count(),
            'unsorted_packages' => $this->unsortedPackagesQuery($warehouse)->count(),
            'open_sort_batches' => $this->sortBatches($warehouse)->where('status', SortBatch::STATUS_OPEN)->count(),
            'sealed_transfer_batches' => $this->sortBatches($warehouse)
                ->where('status', SortBatch::STATUS_SEALED)
                ->where('dispatch_mode', SortBatch::DISPATCH_TRANSFER)
                ->whereDoesntHave('transportManifest')
                ->count(),
            'sealed_delivery_batches' => $this->sortBatches($warehouse)
                ->where('status', SortBatch::STATUS_SEALED)
                ->where('dispatch_mode', SortBatch::DISPATCH_LOCAL_DELIVERY)
                ->whereDoesntHave('deliveryRun')
                ->count(),
            'outgoing_manifests' => $this->outgoingManifests($warehouse)
                ->whereIn('status', [TransportManifest::STATUS_DRAFT, TransportManifest::STATUS_ASSIGNED, TransportManifest::STATUS_LOADING, TransportManifest::STATUS_IN_TRANSIT, TransportManifest::STATUS_ARRIVED])
                ->count(),
            'incoming_manifests' => $this->incomingManifests($warehouse)
                ->whereIn('status', [TransportManifest::STATUS_IN_TRANSIT, TransportManifest::STATUS_ARRIVED])
                ->count(),
            'active_delivery_runs' => $this->deliveryRuns($warehouse)
                ->whereIn('status', [DeliveryRun::STATUS_ASSIGNED, DeliveryRun::STATUS_OUT_FOR_DELIVERY, DeliveryRun::STATUS_PARTIALLY_DELIVERED])
                ->count(),
            'pending_confirmations' => $this->pendingConfirmations($warehouse)->count(),
            'payment_due' => (clone $paymentTasks)
                ->whereIn('status', [RecipientPaymentTask::STATUS_PENDING, RecipientPaymentTask::STATUS_ASSIGNED, RecipientPaymentTask::STATUS_IN_PROGRESS])
                ->count(),
            'payment_due_label' => $personalPaymentQueue ? 'My Active Payments' : 'Payment Due',
            'payment_due_detail' => $personalPaymentQueue ? 'Payments assigned to you' : 'Recipient payment work waiting',
            'contact_queue' => $this->contactTasks($warehouse)
                ->whereIn('status', [PackageContactTask::STATUS_PENDING, PackageContactTask::STATUS_ASSIGNED, PackageContactTask::STATUS_IN_PROGRESS])
                ->count(),
            'discrepancies' => $this->discrepancyItems($warehouse)->count(),
            'failed_deliveries' => $this->failedDeliveryStops($warehouse)->count(),
        ];
    }

    private function workQueues(array $counts): array
    {
        return [
            ['key' => 'pending_intake', 'label' => 'Pending Intake', 'value' => $counts['pending_intake'], 'tone' => 'orange', 'detail' => 'Picked up and waiting to be received', 'url' => route('warehouse.receipts.pending.index'), 'permission' => 'warehouse.receiving.manage'],
            ['key' => 'unsorted_packages', 'label' => 'At Warehouse / Unsorted', 'value' => $counts['unsorted_packages'], 'tone' => 'slate', 'detail' => 'Received packages not yet in a sort batch', 'url' => route('warehouse.packages.index'), 'permission' => 'warehouse.items.scan'],
            ['key' => 'open_sort_batches', 'label' => 'Open Sort Batches', 'value' => $counts['open_sort_batches'], 'tone' => 'violet', 'detail' => 'Editable batches that need work or sealing', 'url' => route('warehouse.sorting.index'), 'permission' => 'warehouse.sorting.manage'],
            ['key' => 'ready_transport', 'label' => 'Ready for Transport', 'value' => $counts['sealed_transfer_batches'], 'tone' => 'blue', 'detail' => 'Sealed transfer batches needing manifest action', 'url' => route('warehouse.sorting.index'), 'permission' => 'warehouse.sorting.manage'],
            ['key' => 'incoming_manifests', 'label' => 'Incoming Manifests', 'value' => $counts['incoming_manifests'], 'tone' => 'cyan', 'detail' => 'Inbound transfers to receive', 'url' => route('warehouse.manifests.incoming.index'), 'permission' => 'warehouse.receiving.manage'],
            ['key' => 'active_delivery_runs', 'label' => 'Out for Delivery', 'value' => $counts['active_delivery_runs'], 'tone' => 'emerald', 'detail' => 'Assigned or active delivery runs', 'url' => route('warehouse.deliveries.runs.index'), 'permission' => 'warehouse.delivery.assign'],
            ['key' => 'payment_due', 'label' => $counts['payment_due_label'], 'value' => $counts['payment_due'], 'tone' => 'amber', 'detail' => $counts['payment_due_detail'], 'url' => route('warehouse.recipient-payments.index'), 'permission' => 'warehouse.recipient_payments.view'],
            ['key' => 'exceptions', 'label' => 'Exceptions', 'value' => $counts['discrepancies'] + $counts['failed_deliveries'] + $counts['pending_confirmations'], 'tone' => 'rose', 'detail' => 'Discrepancies, failures and confirmations', 'url' => route('warehouse.deliveries.pending-confirmations'), 'permission' => 'warehouse.delivery.assign'],
        ];
    }

    private function workflowLanes(array $counts): array
    {
        return [
            [
                'label' => 'Intake',
                'detail' => 'Receive packages into the warehouse',
                'items' => [
                    ['label' => 'Walk-in Receiving', 'value' => null, 'url' => route('warehouse.walkin.create'), 'permission' => 'warehouse.receiving.manage'],
                    ['label' => 'Pending Receipts', 'value' => $counts['pending_intake'], 'url' => route('warehouse.receipts.pending.index'), 'permission' => 'warehouse.receiving.manage'],
                    ['label' => 'Received Today', 'value' => $counts['received_today'], 'url' => route('warehouse.pickups.received.index'), 'permission' => 'warehouse.receiving.manage'],
                ],
            ],
            [
                'label' => 'Warehouse Floor',
                'detail' => 'Find, sort and control packages',
                'items' => [
                    ['label' => 'Warehouse Packages', 'value' => $counts['warehouse_packages'], 'url' => route('warehouse.packages.index'), 'permission' => 'warehouse.items.scan'],
                    ['label' => 'Unsorted Packages', 'value' => $counts['unsorted_packages'], 'url' => route('warehouse.packages.index'), 'permission' => 'warehouse.items.scan'],
                    ['label' => 'Sorting', 'value' => $counts['open_sort_batches'], 'url' => route('warehouse.sorting.index'), 'permission' => 'warehouse.sorting.manage'],
                ],
            ],
            [
                'label' => 'Transport',
                'detail' => 'Move stock between warehouses',
                'items' => [
                    ['label' => 'Create / Load Manifest', 'value' => $counts['sealed_transfer_batches'], 'url' => route('warehouse.manifests.transport.index'), 'permission' => 'warehouse.manifest.manage'],
                    ['label' => 'Outgoing Manifests', 'value' => $counts['outgoing_manifests'], 'url' => route('warehouse.manifests.transport.index'), 'permission' => 'warehouse.manifest.manage'],
                    ['label' => 'Incoming Manifests', 'value' => $counts['incoming_manifests'], 'url' => route('warehouse.manifests.incoming.index'), 'permission' => 'warehouse.receiving.manage'],
                ],
            ],
            [
                'label' => 'Delivery',
                'detail' => 'Dispatch, confirm and collect',
                'items' => [
                    ['label' => 'Delivery Runs', 'value' => $counts['active_delivery_runs'], 'url' => route('warehouse.deliveries.runs.index'), 'permission' => 'warehouse.delivery.assign'],
                    ['label' => 'Pending Confirmations', 'value' => $counts['pending_confirmations'], 'url' => route('warehouse.deliveries.pending-confirmations'), 'permission' => 'warehouse.delivery.assign'],
                    ['label' => 'Collections', 'value' => null, 'url' => route('warehouse.collections.index'), 'permission' => 'warehouse.delivery.assign'],
                ],
            ],
            [
                'label' => 'Money / Admin',
                'detail' => 'Payment, calls and staff',
                'items' => [
                    ['label' => $counts['payment_due_label'], 'value' => $counts['payment_due'], 'url' => route('warehouse.recipient-payments.index'), 'permission' => 'warehouse.recipient_payments.view'],
                    ['label' => 'Contact Queue', 'value' => $counts['contact_queue'], 'url' => route('warehouse.contacts.index'), 'permission' => 'warehouse.contacts.manage'],
                    ['label' => 'Warehouse Users', 'value' => null, 'url' => route('warehouse.users.index'), 'permission' => 'warehouse.users.view'],
                ],
            ],
        ];
    }

    private function exceptions(array $counts): array
    {
        return [
            ['label' => 'Receipt discrepancies', 'value' => $counts['discrepancies'], 'detail' => 'Short, excess, damaged or unresolved receipt lines', 'url' => route('warehouse.packages.index'), 'permission' => 'warehouse.receiving.manage'],
            ['label' => 'Failed deliveries', 'value' => $counts['failed_deliveries'], 'detail' => 'Stops marked failed by rider or confirmation', 'url' => route('warehouse.deliveries.runs.index'), 'permission' => 'warehouse.delivery.assign'],
            ['label' => 'Bus handoff confirmations', 'value' => $counts['pending_confirmations'], 'detail' => 'Courier handoffs still needing follow-up', 'url' => route('warehouse.deliveries.pending-confirmations'), 'permission' => 'warehouse.delivery.assign'],
            ['label' => $counts['payment_due_label'], 'value' => $counts['payment_due'], 'detail' => $counts['payment_due_detail'], 'url' => route('warehouse.recipient-payments.index'), 'permission' => 'warehouse.recipient_payments.view'],
        ];
    }

    private function actions(User $user): array
    {
        return [
            ['label' => 'Walk-in Receiving', 'url' => route('warehouse.walkin.create'), 'permission' => 'warehouse.receiving.manage'],
            ['label' => 'Receive Pickup', 'url' => route('warehouse.receipts.pending.index'), 'permission' => 'warehouse.receiving.manage'],
            ['label' => 'Find Package', 'url' => route('warehouse.packages.index'), 'permission' => 'warehouse.items.scan'],
            ['label' => 'Create Sort Batch', 'url' => route('warehouse.sorting.index'), 'permission' => 'warehouse.sorting.manage'],
            ['label' => 'Transport Manifest', 'url' => route('warehouse.manifests.transport.index'), 'permission' => 'warehouse.manifest.manage'],
            ['label' => 'Receive Manifest', 'url' => route('warehouse.manifests.incoming.index'), 'permission' => 'warehouse.transport.assign'],
            ['label' => 'Delivery Runs', 'url' => route('warehouse.deliveries.runs.index'), 'permission' => 'warehouse.delivery.assign'],
            ['label' => 'Process Payments', 'url' => route('warehouse.recipient-payments.index'), 'permission' => 'warehouse.recipient_payments.view'],
        ];
    }

    private function paymentDashboard(Warehouse $warehouse, User $user, array $counts): array
    {
        if (!$this->can($user, 'warehouse.recipient_payments.view')) {
            return [];
        }

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $paymentTasks = $this->paymentTasks($warehouse, $user);
        $todayCalls = RecipientPaymentCallAttempt::query()
            ->where('attempted_by_user_id', $user->id)
            ->whereBetween('attempted_at', [$todayStart, $todayEnd]);
        $todaySession = RecipientPaymentSession::query()
            ->with(['paymentWallet:id,name,provider,phone_number', 'entries'])
            ->where('user_id', $user->id)
            ->where('warehouse_id', $warehouse->id)
            ->where(function (Builder $query) use ($todayStart, $todayEnd) {
                $query->whereBetween('started_at', [$todayStart, $todayEnd])
                    ->orWhere('status', RecipientPaymentSession::STATUS_OPEN);
            })
            ->latest('started_at')
            ->first();

        $recordedToday = RecipientPaymentSessionEntry::query()
            ->where('recorded_by_user_id', $user->id)
            ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
            ->whereBetween('created_at', [$todayStart, $todayEnd]);

        $recordedInSession = $todaySession
            ? (float) $todaySession->entries
                ->where('entry_type', RecipientPaymentSessionEntry::TYPE_PAYMENT)
                ->sum(fn (RecipientPaymentSessionEntry $entry) => (float) $entry->amount)
            : 0.0;

        $expectedClosing = null;
        if ($todaySession) {
            $adjustments = (float) $todaySession->entries
                ->where('entry_type', RecipientPaymentSessionEntry::TYPE_ADJUSTMENT)
                ->sum(fn (RecipientPaymentSessionEntry $entry) => (float) $entry->amount);
            $expectedClosing = $todaySession->expected_closing_balance !== null
                ? (float) $todaySession->expected_closing_balance
                : round((float) $todaySession->opening_balance + $recordedInSession - $adjustments, 2);
        }

        $paidToday = (clone $paymentTasks)
            ->whereIn('status', [RecipientPaymentTask::STATUS_PAID, RecipientPaymentTask::STATUS_WAIVED, RecipientPaymentTask::STATUS_OVERRIDDEN])
            ->whereBetween('updated_at', [$todayStart, $todayEnd])
            ->count();

        $callCounts = [
            'answered' => (clone $todayCalls)->where('outcome', 'answered')->count(),
            'no_answer' => (clone $todayCalls)->where('outcome', 'no_answer')->count(),
            'callback' => (clone $todayCalls)->where('outcome', 'callback')->count(),
            'pay_later' => (clone $todayCalls)->where('outcome', 'payment_promised')->count(),
            'wrong_number' => (clone $todayCalls)->where('outcome', 'wrong_number')->count(),
            'busy' => (clone $todayCalls)->where('outcome', 'busy')->count(),
        ];

        return [
            'active_count' => $counts['payment_due'],
            'paid_today' => $paidToday,
            'recorded_today' => (float) (clone $recordedToday)->sum('amount'),
            'recorded_today_count' => (clone $recordedToday)->count(),
            'calls_today' => array_sum($callCounts),
            'call_counts' => $callCounts,
            'session' => $todaySession ? [
                'status' => $todaySession->status,
                'wallet' => $todaySession->paymentWallet?->name,
                'wallet_phone' => $todaySession->paymentWallet?->phone_number,
                'started_at' => $todaySession->started_at?->format('g:i A'),
                'closed_at' => $todaySession->closed_at?->format('g:i A'),
                'opening_balance' => (float) $todaySession->opening_balance,
                'closing_balance' => $todaySession->closing_balance !== null ? (float) $todaySession->closing_balance : null,
                'expected_closing_balance' => $expectedClosing,
                'variance' => $todaySession->variance !== null ? (float) $todaySession->variance : null,
                'recorded_amount' => $recordedInSession,
            ] : null,
        ];
    }

    private function recentActivity(Warehouse $warehouse, User $user): array
    {
        $activity = collect();

        if ($this->can($user, 'warehouse.receiving.manage') || $this->can($user, 'warehouse.items.scan')) {
            $activity = $activity->merge($this->receivedItemsQuery($warehouse)->with(['shipmentItem:id,description,tracking_code', 'receivedBy:id,name'])->latest('received_at')->limit(5)->get()->map(fn ($item) => [
                'label' => 'Package received',
                'detail' => $item->shipmentItem?->tracking_code ?: $item->shipmentItem?->description,
                'actor' => $item->receivedBy?->name,
                'at' => $item->received_at,
                'url' => route('warehouse.packages.show', $item),
                'tone' => 'emerald',
            ]));
        }

        if ($this->can($user, 'warehouse.sorting.manage')) {
            $activity = $activity->merge($this->sortBatches($warehouse)->with(['createdBy:id,name', 'sealedBy:id,name'])->latest('updated_at')->limit(5)->get()->map(fn ($batch) => [
                'label' => $batch->status === SortBatch::STATUS_SEALED ? 'Sort batch sealed' : 'Sort batch updated',
                'detail' => $batch->batch_number,
                'actor' => $batch->sealedBy?->name ?: $batch->createdBy?->name,
                'at' => $batch->sealed_at ?: $batch->updated_at,
                'url' => route('warehouse.sorting.show', $batch),
                'tone' => 'violet',
            ]));
        }

        if ($this->can($user, 'warehouse.manifest.manage') || $this->can($user, 'warehouse.transport.assign')) {
            $activity = $activity->merge($this->outgoingManifests($warehouse)->with('assignedRider:id,name')->latest('updated_at')->limit(5)->get()->map(fn ($manifest) => [
                'label' => 'Transport manifest',
                'detail' => $manifest->manifest_number . ' / ' . $this->label($manifest->status),
                'actor' => $manifest->assignedDriver?->name,
                'at' => $manifest->received_at ?: $manifest->arrived_at ?: $manifest->dispatched_at ?: $manifest->updated_at,
                'url' => route('warehouse.manifests.transport.show', $manifest),
                'tone' => 'blue',
            ]));
        }

        if ($this->can($user, 'warehouse.delivery.assign')) {
            $activity = $activity->merge($this->deliveryRuns($warehouse)->with('assignedRider:id,name')->latest('updated_at')->limit(5)->get()->map(fn ($run) => [
                'label' => 'Delivery run',
                'detail' => $run->run_number . ' / ' . $this->label($run->status),
                'actor' => $run->assignedDriver?->name,
                'at' => $run->completed_at ?: $run->dispatched_at ?: $run->updated_at,
                'url' => route('warehouse.deliveries.runs.show', $run),
                'tone' => 'orange',
            ]));
        }

        if ($this->can($user, 'warehouse.recipient_payments.view')) {
            $activity = $activity->merge($this->paymentTasks($warehouse, $user)->latest('updated_at')->limit(5)->get()->map(fn ($task) => [
                'label' => 'Recipient payment',
                'detail' => collect([$task->recipient_name, $task->recipient_phone, $this->label($task->status)])->filter()->join(' / '),
                'actor' => null,
                'at' => $task->paid_at ?: $task->updated_at,
                'url' => route('warehouse.recipient-payments.index'),
                'tone' => 'amber',
            ]));
        }

        return $activity
            ->filter(fn ($activity) => $activity['at'])
            ->sortByDesc('at')
            ->take(10)
            ->map(fn ($activity) => [
                ...$activity,
                'time' => $activity['at']?->format('M j, Y g:i A'),
            ])
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function filterByPermission(User $user, array $items): array
    {
        return collect($items)
            ->filter(fn (array $item) => empty($item['permission']) || $this->can($user, (string) $item['permission']))
            ->values()
            ->all();
    }

    /**
     * @param array<int, array<string, mixed>> $lanes
     * @return array<int, array<string, mixed>>
     */
    private function filterWorkflowLanes(User $user, array $lanes): array
    {
        return collect($lanes)
            ->map(function (array $lane) use ($user) {
                $lane['items'] = $this->filterByPermission($user, $lane['items'] ?? []);

                return $lane;
            })
            ->filter(fn (array $lane) => !empty($lane['items']))
            ->values()
            ->all();
    }

    private function can(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    private function receivedItemsQuery(Warehouse $warehouse): Builder
    {
        return WarehouseReceiptItem::query()
            ->whereHas('receipt', fn (Builder $query) => $query
                ->where('warehouse_id', $warehouse->id)
                ->where('status', WarehouseReceipt::STATUS_FINALIZED));
    }

    private function unsortedPackagesQuery(Warehouse $warehouse): Builder
    {
        return $this->receivedItemsQuery($warehouse)
            ->whereHas('shipmentItem', fn (Builder $query) => $query->whereIn('status', [ItemStatus::AT_WAREHOUSE->value, ItemStatus::AT_DESTINATION->value]))
            ->whereDoesntHave('activeSortBatchItem');
    }

    private function sortBatches(Warehouse $warehouse): Builder
    {
        return SortBatch::query()->where('origin_warehouse_id', $warehouse->id);
    }

    private function outgoingManifests(Warehouse $warehouse): Builder
    {
        return TransportManifest::query()->where('origin_warehouse_id', $warehouse->id);
    }

    private function incomingManifests(Warehouse $warehouse): Builder
    {
        return TransportManifest::query()->where('destination_warehouse_id', $warehouse->id);
    }

    private function deliveryRuns(Warehouse $warehouse): Builder
    {
        return DeliveryRun::query()->where('warehouse_id', $warehouse->id);
    }

    private function pendingConfirmations(Warehouse $warehouse): Builder
    {
        return DeliveryRunStop::query()
            ->where('status', DeliveryRunStop::STATUS_HANDED_OFF)
            ->where('delivery_method', DeliveryRunStop::METHOD_BUS_HANDOFF)
            ->whereHas('run', fn (Builder $query) => $query->where('warehouse_id', $warehouse->id));
    }

    private function failedDeliveryStops(Warehouse $warehouse): Builder
    {
        return DeliveryRunStop::query()
            ->where('status', DeliveryRunStop::STATUS_FAILED)
            ->whereHas('run', fn (Builder $query) => $query->where('warehouse_id', $warehouse->id));
    }

    private function paymentTasks(Warehouse $warehouse, ?User $user = null): Builder
    {
        $query = Schema::hasTable('recipient_payment_tasks')
            ? RecipientPaymentTask::query()->where('warehouse_id', $warehouse->id)
            : RecipientPaymentTask::query()->whereRaw('1 = 0');

        if ($user && !$this->canManagePaymentQueue($user)) {
            $query->where('assigned_to_user_id', $user->id);
        }

        return $query;
    }

    private function contactTasks(Warehouse $warehouse): Builder
    {
        return Schema::hasTable('package_contact_tasks')
            ? PackageContactTask::query()->where('warehouse_id', $warehouse->id)
            : PackageContactTask::query()->whereRaw('1 = 0');
    }

    private function discrepancyItems(Warehouse $warehouse): Builder
    {
        return $this->receivedItemsQuery($warehouse)
            ->where(function (Builder $query) {
                $query->whereNotNull('discrepancy_type')
                    ->where('discrepancy_type', '!=', 'none');
            });
    }

    private function label(?string $value): string
    {
        return $value ? str($value)->replace('_', ' ')->title()->toString() : '-';
    }

    private function canManagePaymentQueue(User $user): bool
    {
        return $user->hasAnyPermission([
            'warehouse.recipient_payments.assign',
            'warehouse.recipient_payments.reconcile',
            'warehouse.recipient_payments.override',
            'warehouse.recipient_payments.manage_wallets',
        ]);
    }
}
