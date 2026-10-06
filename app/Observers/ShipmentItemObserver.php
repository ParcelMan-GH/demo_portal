<?php

namespace App\Observers;

use App\Events\ShipmentItemStatusChanged;
use App\Models\ShipmentItem;

/**
 * Emits the parcel-level status event.
 *
 * Attached to the model rather than called from the controllers on purpose. There
 * are many writers of `ShipmentItem::status` — hub intake, bus dispatch, hub
 * release, warehouse scanning, rider scans, admin corrections — and the one thing
 * that is certain about a hand-maintained list of `notify()` calls is that the next
 * writer will forget. The vendor's silence about the hub leg was exactly that
 * failure, so the fix is to hang the notification off the write itself.
 *
 * `updated`, not `updating`: the parent observer already established why — firing
 * while the write is still pending describes a change that may yet roll back, and
 * the listener would have texted the vendor about it.
 */
class ShipmentItemObserver
{
    public function updated(ShipmentItem $item): void
    {
        // `wasChanged`, not `isDirty`: this observer runs after the write.
        if (! $item->wasChanged('status')) {
            return;
        }

        $old = $item->getOriginal('status');
        $new = $item->status;

        // The column is cast to ItemStatus, so either side may arrive as an enum or
        // a raw string depending on how the write was made. Normalising both is what
        // keeps a no-op rewrite from looking like a transition.
        $oldValue = $old instanceof \BackedEnum ? $old->value : (string) $old;
        $newValue = $new instanceof \BackedEnum ? $new->value : (string) $new;

        if ($oldValue === $newValue) {
            return;
        }

        event(new ShipmentItemStatusChanged($item, $oldValue, $newValue));
    }
}
