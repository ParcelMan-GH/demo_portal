<?php

namespace App\Observers;

use App\Events\DriverAssignedToTransport;
use App\Events\DriverUnassignedFromTransport;
use App\Events\TransportManifestStatusChanged;
use App\Models\Driver;
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

        // Fire driver assignment events
        if ($manifest->isDirty('assigned_driver_id')) {
            $oldDriverId = $manifest->getOriginal('assigned_driver_id');
            $newDriverId = $manifest->assigned_driver_id;

            if ($newDriverId && !$oldDriverId) {
                // Driver assigned
                $driver = Driver::find($newDriverId);
                if ($driver) {
                    event(new DriverAssignedToTransport($manifest, $driver));
                }
            } elseif (!$newDriverId && $oldDriverId) {
                // Driver unassigned
                $driver = Driver::find($oldDriverId);
                if ($driver) {
                    event(new DriverUnassignedFromTransport($manifest, $driver));
                }
            }
        }
    }
}
