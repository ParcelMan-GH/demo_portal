<?php

namespace App\Observers;

use App\Events\TransportManifestStatusChanged;
use App\Models\TransportManifest;

class TransportManifestObserver
{
    public function updating(TransportManifest $manifest): void
    {
        // Fire status change event
        if ($manifest->isDirty('status')) {
            $oldStatus = (string) $manifest->getOriginal('status');
            $newStatus = (string) $manifest->status;

            if ($oldStatus !== $newStatus) {
                event(new TransportManifestStatusChanged($manifest, $oldStatus, $newStatus));
            }
        }

    }
}
