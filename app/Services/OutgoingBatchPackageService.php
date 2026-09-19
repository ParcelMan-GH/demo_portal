<?php

namespace App\Services;

use App\Models\OutgoingBatch;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Assigning packages to an outgoing batch.
 *
 * The commerce eligibility rule lives here and nowhere else, so the picker the
 * operator sees, the scan path and the API all enforce exactly the same thing
 * and cannot drift apart.
 */
class OutgoingBatchPackageService
{
    public const ERROR_NOT_COMMERCE = 'Cannot add package: This batch only accepts Commerce packages.';
    public const ERROR_BATCH_NOT_OPEN = 'Cannot add package: This batch has already left and no longer accepts packages.';
    public const ERROR_ALREADY_ASSIGNED = 'Cannot add package: This package is already assigned to another batch.';
    public const ERROR_NOT_FOUND = 'Cannot add package: Package not found.';
    public const ERROR_NONE_SELECTED = 'Select at least one package to add.';

    /**
     * Packages that could be placed in this batch: everything not already
     * committed to a different batch. Commerce eligibility is reported per item
     * rather than filtered out, so the operator can see why something is blocked.
     */
    public function candidateQuery(OutgoingBatch $batch): Builder
    {
        return ShipmentItem::query()
            ->where(function (Builder $query) use ($batch) {
                $query->whereNull('outgoing_batch_id')
                    ->orWhere('outgoing_batch_id', $batch->id);
            });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function candidates(OutgoingBatch $batch, ?string $search = null, int $limit = 100): array
    {
        $query = $this->candidateQuery($batch)->with('shipment:id,shipment_number,vendor_id');

        $term = trim((string) $search);
        if ($term !== '') {
            $query->where(function (Builder $inner) use ($term) {
                $inner->where('tracking_code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('delivery_recipient_name', 'like', "%{$term}%")
                    ->orWhere('delivery_recipient_phone', 'like', "%{$term}%");
            });
        }

        return $query->orderByDesc('id')
            ->limit(max(1, min($limit, 300)))
            ->get()
            ->reject(fn (ShipmentItem $item) => (int) $item->outgoing_batch_id === (int) $batch->id)
            ->map(function (ShipmentItem $item) use ($batch) {
                $reason = $this->rejectionReason($batch, $item);

                return $this->present($item) + [
                    'selectable' => $reason === null,
                    'blocked_reason' => $reason,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Why this package cannot join this batch, or null when it can.
     */
    public function rejectionReason(OutgoingBatch $batch, ShipmentItem $item): ?string
    {
        if (! $batch->isOpen()) {
            return $this->closedMessage($batch);
        }

        if ($item->outgoing_batch_id !== null && (int) $item->outgoing_batch_id !== (int) $batch->id) {
            return self::ERROR_ALREADY_ASSIGNED;
        }

        if ($batch->acceptsOnlyCommerce() && ! $item->is_commerce) {
            return self::ERROR_NOT_COMMERCE;
        }

        return null;
    }

    public function canAccept(OutgoingBatch $batch, ShipmentItem $item): bool
    {
        return $this->rejectionReason($batch, $item) === null;
    }

    /**
     * Name the state the batch is actually in, rather than a bare "not open".
     * "no longer open" left the operator with no way to tell a dispatched batch
     * from an unexpected status value.
     */
    public function closedMessage(OutgoingBatch $batch): string
    {
        $status = strtolower(trim((string) $batch->status));

        if ($status === '') {
            return self::ERROR_BATCH_NOT_OPEN;
        }

        return sprintf(
            'Cannot add package: This batch is already %s and no longer accepts packages.',
            strtolower($batch->statusLabel())
        );
    }

    /**
     * Attach packages to the batch.
     *
     * Every package is validated before anything is written, so an addition
     * either lands completely or not at all — a partly-filled batch would be
     * discovered only when the van was already loaded.
     *
     * @param  array<int, int|string>  $itemIds
     * @return array{added: int, items: array<int, array<string, mixed>>}
     *
     * @throws ValidationException
     */
    public function addItems(OutgoingBatch $batch, array $itemIds): array
    {
        $itemIds = array_values(array_unique(array_map('intval', $itemIds)));
        $itemIds = array_values(array_filter($itemIds, fn (int $id) => $id > 0));

        if ($itemIds === []) {
            throw ValidationException::withMessages(['package_ids' => self::ERROR_NONE_SELECTED]);
        }

        /** @var Collection<int, ShipmentItem> $items */
        $items = ShipmentItem::query()->whereIn('id', $itemIds)->get()->keyBy('id');

        foreach ($itemIds as $id) {
            $item = $items->get($id);

            if (! $item) {
                throw ValidationException::withMessages(['package_ids' => self::ERROR_NOT_FOUND]);
            }

            $reason = $this->rejectionReason($batch, $item);
            if ($reason !== null) {
                throw ValidationException::withMessages(['package_ids' => $reason]);
            }
        }

        ShipmentItem::query()
            ->whereIn('id', $itemIds)
            ->update(['outgoing_batch_id' => $batch->id]);

        return [
            'added' => count($itemIds),
            'items' => $this->assignedItems($batch),
        ];
    }

    /**
     * Packages currently in the batch.
     *
     * @return array<int, array<string, mixed>>
     */
    public function assignedItems(OutgoingBatch $batch): array
    {
        return $batch->shipmentItems()
            ->with('shipment:id,shipment_number,vendor_id')
            ->orderByDesc('id')
            ->get()
            ->map(fn (ShipmentItem $item) => $this->present($item))
            ->values()
            ->all();
    }

    /**
     * Batch metadata for the detail view.
     *
     * @return array<string, mixed>
     */
    public function summary(OutgoingBatch $batch): array
    {
        return [
            'id' => $batch->id,
            'batch_number' => $batch->batch_number,
            'destination_type' => $batch->destination_type,
            'destination_type_label' => $batch->destinationTypeLabel(),
            'accepts_only_commerce' => $batch->acceptsOnlyCommerce(),
            'status' => $batch->status,
            'status_label' => $batch->statusLabel(),
            'is_open' => $batch->isOpen(),
            'destination_warehouse' => "Region #{$batch->delivery_region_id} / District #{$batch->delivery_district_id}",
            'items_count' => $batch->shipmentItems()->count(),
            'created_at' => optional($batch->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function present(ShipmentItem $item): array
    {
        return [
            'id' => $item->id,
            'tracking_code' => $item->tracking_code,
            'description' => $item->description,
            'quantity' => (int) $item->quantity,
            'is_commerce' => (bool) $item->is_commerce,
            'recipient_name' => $item->delivery_recipient_name,
            'recipient_phone' => $item->delivery_recipient_phone,
            'delivery_town' => $item->delivery_town,
            'status' => $item->status instanceof \BackedEnum ? $item->status->value : $item->status,
            'shipment_number' => $item->shipment?->shipment_number,
        ];
    }
}
