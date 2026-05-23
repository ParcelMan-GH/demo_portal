<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipments')
            ->whereIn('status', ['invoice_sent', 'invoice_accepted'])
            ->update(['status' => 'processing']);
    }

    public function down(): void
    {
        // The old billing workflow is intentionally not restored.
    }
};
