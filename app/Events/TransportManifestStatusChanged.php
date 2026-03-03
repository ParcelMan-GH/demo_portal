<?php

namespace App\Events;

use App\Models\TransportManifest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransportManifestStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TransportManifest $manifest,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
