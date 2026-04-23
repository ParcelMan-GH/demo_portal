<?php

namespace App\Services\Warehouse;

use App\Enums\FulfillmentType;
use App\Models\PackageContactAttempt;
use App\Models\PackageContactTask;
use App\Models\ShipmentItem;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;

class PackageContactService
{
    public const CODE_LENGTH = 6;
    public const CODE_TTL_MINUTES = 15;
    public const CODE_MAX_ATTEMPTS = 5;
    public const CODE_RESEND_SECONDS = 60;

    // Uppercase alphanumeric with confusable characters removed (no O/0/I/1).
    private const CODE_ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    public function __construct(
        private SmsService $smsService,
    ) {}

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

    /**
     * Generate a fresh alphanumeric confirmation code, SMS it to the recipient,
     * and persist it on the task. Any prior unverified code is superseded.
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public function sendConfirmationCode(PackageContactTask $task): array
    {
        if (!$task->recipient_phone) {
            return ['success' => false, 'message' => 'Task has no recipient phone on file.'];
        }

        // Throttle: last send must be at least CODE_RESEND_SECONDS ago.
        if ($task->confirmation_code_sent_at) {
            $secondsSince = $task->confirmation_code_sent_at->diffInSeconds(now());
            if ($secondsSince < self::CODE_RESEND_SECONDS) {
                $wait = self::CODE_RESEND_SECONDS - $secondsSince;
                return [
                    'success' => false,
                    'message' => "Please wait {$wait}s before resending.",
                    'data' => ['resend_after_seconds' => $wait],
                ];
            }
        }

        $code = $this->generateAlphanumericCode(self::CODE_LENGTH);
        $now = now();
        $expiresAt = $now->copy()->addMinutes(self::CODE_TTL_MINUTES);

        $task->update([
            'confirmation_code' => $code,
            'confirmation_code_sent_at' => $now,
            'confirmation_code_expires_at' => $expiresAt,
            'confirmation_code_verified_at' => null,
            'confirmation_attempts' => 0,
        ]);

        $recipientName = $task->recipient_name ?: 'Hello';
        $message = "Parcelman: {$recipientName}, your confirmation code is {$code}. "
            . "Share it with the agent on the call to confirm your delivery. "
            . "Do NOT share with anyone else.";

        $this->smsService->send($task->recipient_phone, $message);

        return [
            'success' => true,
            'message' => 'Confirmation code sent to recipient.',
            'data' => [
                'sent_at' => $now->toIso8601String(),
                'expires_at' => $expiresAt->toIso8601String(),
                'resend_after_seconds' => self::CODE_RESEND_SECONDS,
                'ttl_minutes' => self::CODE_TTL_MINUTES,
            ],
        ];
    }

    /**
     * Resolve the task. For outcomes that require verification (deliver,
     * self_pickup), the caller must pass the code the recipient read off
     * their phone; invalid/expired/too-many-attempts codes are rejected.
     *
     * @return array{success: bool, message: string, code?: string}
     *         code is one of: missing, expired, invalid, exhausted
     */
    public function resolveTask(
        PackageContactTask $task,
        string $outcome,
        ?string $notes = null,
        ?\DateTime $callbackAt = null,
        ?string $confirmationCode = null,
    ): array {
        if (in_array($outcome, PackageContactTask::VERIFIED_OUTCOMES, true)) {
            $verification = $this->verifyConfirmationCode($task, $confirmationCode);
            if (!$verification['success']) {
                return $verification;
            }
        }

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

        return ['success' => true, 'message' => 'Task resolved.'];
    }

    /**
     * Validate a code against a task. Increments attempt counter on misses.
     * Marks the task's code as verified on success so it can't be re-used.
     */
    private function verifyConfirmationCode(PackageContactTask $task, ?string $code): array
    {
        $code = is_string($code) ? strtoupper(trim($code)) : '';

        if ($code === '') {
            return ['success' => false, 'message' => 'Confirmation code is required.', 'code' => 'missing'];
        }

        if (!$task->confirmation_code || !$task->confirmation_code_expires_at) {
            return ['success' => false, 'message' => 'No confirmation code has been sent yet.', 'code' => 'missing'];
        }

        if ($task->confirmation_code_expires_at->isPast()) {
            return ['success' => false, 'message' => 'Confirmation code has expired. Please resend.', 'code' => 'expired'];
        }

        if ($task->confirmation_attempts >= self::CODE_MAX_ATTEMPTS) {
            return ['success' => false, 'message' => 'Too many attempts. Please resend a new code.', 'code' => 'exhausted'];
        }

        if (!hash_equals(strtoupper((string) $task->confirmation_code), $code)) {
            $task->increment('confirmation_attempts');
            $remaining = max(0, self::CODE_MAX_ATTEMPTS - ($task->confirmation_attempts));
            return [
                'success' => false,
                'message' => "Invalid code. {$remaining} attempt(s) remaining.",
                'code' => 'invalid',
            ];
        }

        $task->update(['confirmation_code_verified_at' => now()]);

        return ['success' => true, 'message' => 'Confirmation verified.'];
    }

    private function generateAlphanumericCode(int $length): string
    {
        $alphabet = self::CODE_ALPHABET;
        $max = strlen($alphabet) - 1;
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }
        return $code;
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
