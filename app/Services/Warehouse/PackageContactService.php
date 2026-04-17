<?php

namespace App\Services\Warehouse;

use App\Enums\FulfillmentType;
use App\Models\PackageContactAttempt;
use App\Models\PackageContactTask;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class PackageContactService
{
    public function createTasksForWarehouseItems(Warehouse $warehouse, array $shipmentItemIds): int
    {
        $created = 0;

        $items = ShipmentItem::with('shipment')
            ->whereIn('id', $shipmentItemIds)
            ->get();

        foreach ($items as $item) {
            if (PackageContactTask::where('shipment_item_id', $item->id)->exists()) {
                continue;
            }

            $recipientName = $item->delivery_recipient_name ?? $item->shipment?->delivery_recipient_name;
            $recipientPhone = $item->delivery_recipient_phone ?? $item->shipment?->delivery_recipient_phone;
            $town = $item->delivery_town ?? $item->shipment?->delivery_town;

            if (!$recipientPhone) {
                continue;
            }

            PackageContactTask::create([
                'shipment_item_id' => $item->id,
                'shipment_id' => $item->shipment_id,
                'warehouse_id' => $warehouse->id,
                'status' => PackageContactTask::STATUS_PENDING,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'delivery_town' => $town,
            ]);

            $created++;
        }

        return $created;
    }

    public function assignToWorker(PackageContactTask $task, User $worker): void
    {
        $task->update([
            'assigned_to_user_id' => $worker->id,
            'assigned_at' => now(),
            'status' => PackageContactTask::STATUS_ASSIGNED,
        ]);
    }

    public function autoAssignRoundRobin(Warehouse $warehouse): int
    {
        $workers = User::where('warehouse_id', $warehouse->id)
            ->where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('is_warehouse_role', true))
            ->get()
            ->filter(fn ($u) => $u->hasPermission('warehouse.contacts.manage'));

        if ($workers->isEmpty()) {
            return 0;
        }

        $pendingTasks = PackageContactTask::where('warehouse_id', $warehouse->id)
            ->where('status', PackageContactTask::STATUS_PENDING)
            ->whereNull('assigned_to_user_id')
            ->orderBy('created_at')
            ->get();

        if ($pendingTasks->isEmpty()) {
            return 0;
        }

        $workerIds = $workers->pluck('id')->values()->toArray();
        $workerCount = count($workerIds);
        $assigned = 0;
        $now = now();

        foreach ($pendingTasks as $index => $task) {
            $workerId = $workerIds[$index % $workerCount];
            $task->update([
                'assigned_to_user_id' => $workerId,
                'assigned_at' => $now,
                'status' => PackageContactTask::STATUS_ASSIGNED,
            ]);
            $assigned++;
        }

        return $assigned;
    }

    public function logAttempt(PackageContactTask $task, User $worker, string $callOutcome, ?string $notes = null): PackageContactAttempt
    {
        $attempt = PackageContactAttempt::create([
            'contact_task_id' => $task->id,
            'attempted_by_user_id' => $worker->id,
            'outcome' => $callOutcome,
            'notes' => $notes,
            'attempted_at' => now(),
        ]);

        $task->increment('attempts_count');

        if ($task->status === PackageContactTask::STATUS_ASSIGNED) {
            $task->update(['status' => PackageContactTask::STATUS_IN_PROGRESS]);
        }

        return $attempt;
    }

    public function resolveTask(PackageContactTask $task, string $outcome, ?string $notes = null, ?\DateTime $callbackAt = null): void
    {
        $task->update([
            'outcome' => $outcome,
            'notes' => $notes,
            'resolved_at' => $outcome === PackageContactTask::OUTCOME_CALLBACK ? null : now(),
            'status' => $outcome === PackageContactTask::OUTCOME_CALLBACK
                ? PackageContactTask::STATUS_IN_PROGRESS
                : PackageContactTask::STATUS_RESOLVED,
            'callback_at' => $callbackAt,
        ]);

        if ($outcome === PackageContactTask::OUTCOME_SELF_PICKUP && $task->shipmentItem?->shipment) {
            $shipment = $task->shipmentItem->shipment;
            if ($shipment->fulfillment_type?->value !== 'self_pickup') {
                $shipment->update(['fulfillment_type' => FulfillmentType::SELF_PICKUP]);
            }
        }
    }

    public function getWorkerStats(User $worker, Warehouse $warehouse): array
    {
        $query = PackageContactTask::where('warehouse_id', $warehouse->id)
            ->where('assigned_to_user_id', $worker->id);

        $total = (clone $query)->count();
        $pending = (clone $query)->whereIn('status', [PackageContactTask::STATUS_ASSIGNED, PackageContactTask::STATUS_IN_PROGRESS])->count();
        $resolved = (clone $query)->where('status', PackageContactTask::STATUS_RESOLVED)->count();
        $callbacksDue = (clone $query)
            ->where('status', PackageContactTask::STATUS_IN_PROGRESS)
            ->where('outcome', PackageContactTask::OUTCOME_CALLBACK)
            ->where('callback_at', '<=', now())
            ->count();

        $avgFirstCallMinutes = null;
        $tasksWithAttempts = (clone $query)->whereHas('attempts')->with(['attempts' => fn ($q) => $q->oldest('attempted_at')->limit(1)])->get();
        if ($tasksWithAttempts->isNotEmpty()) {
            $totalMinutes = 0;
            $count = 0;
            foreach ($tasksWithAttempts as $t) {
                $firstAttempt = $t->attempts->first();
                if ($firstAttempt && $t->assigned_at) {
                    $totalMinutes += $t->assigned_at->diffInMinutes($firstAttempt->attempted_at);
                    $count++;
                }
            }
            if ($count > 0) {
                $avgFirstCallMinutes = round($totalMinutes / $count, 1);
            }
        }

        $outcomes = (clone $query)->where('status', PackageContactTask::STATUS_RESOLVED)
            ->select('outcome', DB::raw('count(*) as count'))
            ->groupBy('outcome')
            ->pluck('count', 'outcome')
            ->toArray();

        return [
            'total_assigned' => $total,
            'pending' => $pending,
            'resolved' => $resolved,
            'callbacks_due' => $callbacksDue,
            'avg_first_call_minutes' => $avgFirstCallMinutes,
            'outcomes' => $outcomes,
        ];
    }

    public function getWarehouseStats(Warehouse $warehouse): array
    {
        $query = PackageContactTask::where('warehouse_id', $warehouse->id);

        return [
            'total' => (clone $query)->count(),
            'unassigned' => (clone $query)->where('status', PackageContactTask::STATUS_PENDING)->count(),
            'assigned' => (clone $query)->where('status', PackageContactTask::STATUS_ASSIGNED)->count(),
            'in_progress' => (clone $query)->where('status', PackageContactTask::STATUS_IN_PROGRESS)->count(),
            'resolved' => (clone $query)->where('status', PackageContactTask::STATUS_RESOLVED)->count(),
            'callbacks_due' => (clone $query)
                ->where('outcome', PackageContactTask::OUTCOME_CALLBACK)
                ->where('callback_at', '<=', now())
                ->count(),
            'resolved_today' => (clone $query)
                ->where('status', PackageContactTask::STATUS_RESOLVED)
                ->whereDate('resolved_at', today())
                ->count(),
        ];
    }
}
