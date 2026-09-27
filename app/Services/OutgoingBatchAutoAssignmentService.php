<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Models\OutgoingBatch;
use App\Models\OutgoingBatchAssignmentEvent;
use App\Models\ShipmentItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Places a package into the outgoing batch for its destination.
 *
 * Extracted so the agent app (confirmed payment call outcome) and the agent
 * dashboard both behave identically: join the open batch for this destination,
 * or create one when there is none.
 */
class OutgoingBatchAutoAssignmentService
{
    /** No batch existed for the destination, so one was created. */
    public const RESULT_BATCH_CREATED = 'batch_created';

    /** The package joined a batch already open for its destination. */
    public const RESULT_BATCH_ATTACHED = 'batch_attached';

    /** The package was already sitting in a batch; nothing was changed. */
    public const RESULT_ALREADY_BATCHED = 'already_batched';

    /** Region or district is missing, so there is no destination to batch to. */
    public const RESULT_MISSING_DESTINATION = 'missing_destination';

    /** Delivered or returned parcels are never moved back into a batch. */
    public const RESULT_NOT_ELIGIBLE = 'not_eligible';

    /**
     * Statuses that mean the parcel has finished its journey.
     */
    private const FINISHED_STATUSES = [
        ItemStatus::DELIVERED->value,
        ItemStatus::RETURNED->value,
    ];

    /**
     * Assign a package to the open batch for its destination, creating that
     * batch when necessary.
     *
     * Idempotent: a package that already belongs to a batch is left alone.
     *
     * @return array{result: string, batch: ?OutgoingBatch, created: bool, message: string}
     */
    public function assignForDestination(ShipmentItem $item, string $source, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($item, $source, $actorUserId) {
            // Re-read under a row lock so two agents confirming payment for the
            // same destination at the same moment cannot both create a batch.
            $locked = ShipmentItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return $this->result(self::RESULT_NOT_ELIGIBLE, null, false, 'This parcel no longer exists.');
            }

            if ($locked->outgoing_batch_id) {
                $existing = OutgoingBatch::query()->find($locked->outgoing_batch_id);

                if ($existing) {
                    return $this->result(
                        self::RESULT_ALREADY_BATCHED,
                        $existing,
                        false,
                        "Parcel is already in batch {$existing->batch_number}."
                    );
                }
            }

            $status = $locked->status instanceof ItemStatus
                ? $locked->status->value
                : (string) $locked->status;

            if (in_array($status, self::FINISHED_STATUSES, true)) {
                return $this->result(
                    self::RESULT_NOT_ELIGIBLE,
                    null,
                    false,
                    "Parcel is already {$status} and cannot be added to an outgoing batch."
                );
            }

            $regionId = $locked->delivery_region_id;
            $districtId = $locked->delivery_district_id;

            if (empty($regionId) || empty($districtId)) {
                return $this->result(
                    self::RESULT_MISSING_DESTINATION,
                    null,
                    false,
                    'Cannot auto-batch: this parcel is missing its Region or District routing information.'
                );
            }

            $batch = OutgoingBatch::query()
                ->where('delivery_region_id', $regionId)
                ->where('delivery_district_id', $districtId)
                ->whereNotIn('status', OutgoingBatch::CLOSED_STATUSES)
                ->orderBy('id')
                ->first();

            $created = false;

            if (! $batch) {
                $batch = OutgoingBatch::create([
                    'batch_number' => $this->generateBatchNumber(),
                    'delivery_region_id' => $regionId,
                    'delivery_district_id' => $districtId,
                    'status' => OutgoingBatch::STATUS_OPEN,
                ]);

                $created = true;
            }

            $locked->forceFill([
                'outgoing_batch_id' => $batch->id,
                'status' => ItemStatus::READY_FOR_HUB_TRANSFER->value,
            ])->save();

            OutgoingBatchAssignmentEvent::create([
                'shipment_item_id' => $locked->id,
                'outgoing_batch_id' => $batch->id,
                'actor_user_id' => $actorUserId,
                'source' => $source,
                'event_type' => $created
                    ? OutgoingBatchAssignmentEvent::EVENT_BATCH_CREATED
                    : OutgoingBatchAssignmentEvent::EVENT_BATCH_ATTACHED,
                'delivery_region_id' => $regionId,
                'delivery_district_id' => $districtId,
                'metadata' => [
                    'batch_number' => $batch->batch_number,
                    'previous_status' => $status,
                    'previous_batch_id' => $item->outgoing_batch_id,
                ],
            ]);

            // Keep the caller's instance in step with what was just written.
            $item->refresh();

            return $this->result(
                $created ? self::RESULT_BATCH_CREATED : self::RESULT_BATCH_ATTACHED,
                $batch,
                $created,
                $created
                    ? "New batch {$batch->batch_number} created for this destination."
                    : "Added to open batch {$batch->batch_number}."
            );
        });
    }

    /**
     * A batch number that is not already taken.
     */
    private function generateBatchNumber(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $candidate = 'BATCH-'.strtoupper(Str::random(6));

            if (! OutgoingBatch::query()->where('batch_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'BATCH-'.strtoupper(Str::random(12));
    }

    /**
     * @return array{result: string, batch: ?OutgoingBatch, created: bool, message: string}
     */
    private function result(string $result, ?OutgoingBatch $batch, bool $created, string $message): array
    {
        return [
            'result' => $result,
            'batch' => $batch,
            'created' => $created,
            'message' => $message,
        ];
    }
}
