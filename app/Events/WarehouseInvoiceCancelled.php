<?php

namespace App\Events;

use App\Models\Admin;
use App\Models\Invoice;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WarehouseInvoiceCancelled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly Admin $cancelledBy,
    ) {}
}
