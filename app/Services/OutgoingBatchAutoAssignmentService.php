<?php

namespace App\Services;

use App\Enums\ItemStatus;
use App\Models\District;
use App\Models\OutgoingBatch;
use App\Models\OutgoingBatchAssignmentEvent;
use App\Models\ShipmentItem;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    /** Where a parcel's destination was resolved from. */
    public const DESTINATION_ITEM = 'parcel';
    public const DESTINATION_SHIPMENT = 'shipment';
    public const DESTINATION_DISTRICT_NAME = 'district_name';
    public const DESTINATION_PRIMARY_HUB = 'primary_hub';

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

                /*
                 * Only an *open* batch counts as "already batched".
                 *
                 * A closed batch — dispatched, received, in transit — has already
                 * left the building, so a parcel still pointing at one can never
                 * be collected by it. Treating that as done stranded such parcels:
                 * they held an outgoing_batch_id, so every later attempt
                 * short-circuited here and they never reached an open batch. That
                 * is exactly the state PM-Y7AWOERH was in.
                 *
                 * The previous batch is recorded on the assignment event
                 * (`previous_batch_id`), so the move stays traceable.
                 */
                if ($existing && $existing->isOpen()) {
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

            /*
             * Resolve a destination before giving up.
             *
             * 48 of the 64 parcels in this database carry no region or district,
             * and a confirmed parcel with no destination was abandoned right
             * here — so no batch ever collected it. The routing is recorded in
             * three places and only the parcel's own row was ever consulted.
             */
            [$regionId, $districtId, $destinationSource] = $this->resolveDestination($locked);

            if (empty($regionId)) {
                return $this->result(
                    self::RESULT_MISSING_DESTINATION,
                    null,
                    false,
                    'Cannot auto-batch: this parcel is missing its Region or District routing information.'
                );
            }

            /*
             * Write the routing back onto the parcel so the next attempt and
             * every downstream screen agree on it.
             *
             * Skipped for the primary-hub fallback: that region is a triage
             * guess, and stamping it on the parcel would make a guess look like
             * data. The batch is still created; only the parcel is left honest.
             */
            if ($destinationSource !== self::DESTINATION_PRIMARY_HUB) {
                $routing = [];

                if (empty($locked->delivery_region_id)) {
                    $routing['delivery_region_id'] = $regionId;
                }

                if (empty($locked->delivery_district_id) && ! empty($districtId)) {
                    $routing['delivery_district_id'] = $districtId;
                }

                if ($routing) {
                    ShipmentItem::whereKey($locked->getKey())->update($routing);
                    $locked->forceFill($routing);
                }
            }

            $batch = OutgoingBatch::query()
                ->where('delivery_region_id', $regionId)
                ->where('delivery_district_id', $districtId)
                ->whereNotIn('status', OutgoingBatch::CLOSED_STATUSES)
                ->orderBy('id')
                ->first();

            $created = false;

            if (! $batch) {
                $attributes = [
                    // Numbering lives on the model now, so every caller issues
                    // the same shape and checks for a collision.
                    'batch_number' => OutgoingBatch::generateBatchNumber(),
                    'delivery_region_id' => $regionId,
                    'delivery_district_id' => $districtId,
                    'status' => OutgoingBatch::STATUS_OPEN,
                ];

                // Guarded: a deployment that has not yet run the migration must
                // still be able to batch.
                if (Schema::hasColumn('outgoing_batches', 'destination_warehouse_id')) {
                    $attributes['destination_warehouse_id'] = OutgoingBatch::resolveDestinationWarehouseId(
                        (int) $regionId,
                        (int) $districtId
                    );
                }

                $batch = OutgoingBatch::create($attributes);

                $created = true;
            } elseif (
                Schema::hasColumn('outgoing_batches', 'destination_warehouse_id')
                && empty($batch->destination_warehouse_id)
            ) {
                // An open batch formed before this column existed is reused rather
                // than recreated, so without this it would stay permanently
                // unassigned and its parcels could never count as bus work.
                $batch->forceFill([
                    'destination_warehouse_id' => OutgoingBatch::resolveDestinationWarehouseId(
                        (int) $regionId,
                        (int) $districtId
                    ),
                ])->save();
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
                    // Recorded so a triage-routed parcel can be told apart from
                    // one that knew where it was going.
                    'destination_source' => $destinationSource,
                ],
            ]);

            // Keep the caller's instance in step with what was just written.
            $item->refresh();

            $triage = $destinationSource === self::DESTINATION_PRIMARY_HUB
                ? ' No destination on file — routed to the primary hub for sorting.'
                : '';

            return $this->result(
                $created ? self::RESULT_BATCH_CREATED : self::RESULT_BATCH_ATTACHED,
                $batch,
                $created,
                ($created
                    ? "New batch {$batch->batch_number} created for this destination."
                    : "Added to open batch {$batch->batch_number}.").$triage
            );
        });
    }

    // Batch numbering moved to OutgoingBatch::generateBatchNumber() so the two
    // places that create batches cannot drift apart.

    /**
     * The destination to batch this parcel under, and where that came from.
     *
     * Tried in order of how much the source can be trusted:
     *
     *  1. the parcel's own routing fields;
     *  2. its shipment's — the same column names, and the shipment is often
     *     filled in when the item row was never backfilled;
     *  3. a district matched by name from the town. This catches "Kumasi" and
     *     "Accra" but not "Aburi", because districts are named for municipal
     *     assemblies rather than towns;
     *  4. the primary hub. A last resort, and a deliberate one: a parcel that
     *     reaches a hub it can be re-sorted from is better than a confirmed
     *     parcel no batch will ever collect. The caller must not stamp this
     *     region onto the parcel as though it were known.
     *
     * @return array{0: ?int, 1: ?int, 2: ?string}
     */
    private function resolveDestination(ShipmentItem $item): array
    {
        if (! empty($item->delivery_region_id)) {
            return [
                (int) $item->delivery_region_id,
                $item->delivery_district_id ? (int) $item->delivery_district_id : null,
                self::DESTINATION_ITEM,
            ];
        }

        $shipment = $item->shipment;

        if ($shipment && ! empty($shipment->delivery_region_id)) {
            return [
                (int) $shipment->delivery_region_id,
                $shipment->delivery_district_id ? (int) $shipment->delivery_district_id : null,
                self::DESTINATION_SHIPMENT,
            ];
        }

        foreach ([$item->delivery_town, $shipment?->delivery_town] as $town) {
            $town = trim((string) $town);

            if ($town === '') {
                continue;
            }

            $district = District::query()
                ->where('is_active', true)
                ->where('name', 'like', $town.'%')
                ->orderBy('id')
                ->first();

            if ($district && ! empty($district->region_id)) {
                return [
                    (int) $district->region_id,
                    (int) $district->id,
                    self::DESTINATION_DISTRICT_NAME,
                ];
            }
        }

        $primary = Warehouse::query()
            ->where('is_active', true)
            ->orderByDesc('is_hq')
            ->orderBy('id')
            ->first();

        if ($primary && ! empty($primary->region_id)) {
            return [(int) $primary->region_id, null, self::DESTINATION_PRIMARY_HUB];
        }

        return [null, null, null];
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
