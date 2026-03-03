<?php

namespace App\Events;

use App\Models\DeliveryRunStop;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliveryRunStopStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly DeliveryRunStop $stop,
        public readonly string $oldStatus,
        public readonly string $newStatus,
    ) {}
}
