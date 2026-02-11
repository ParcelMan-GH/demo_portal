<?php

use App\Enums\InvoiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('current_invoice_id')
                ->nullable()
                ->after('status')
                ->constrained('invoices')
                ->nullOnDelete();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->text('cancel_reason')->nullable()->after('rejection_reason');
            $table->timestamp('cancelled_at')->nullable()->after('rejected_at');
        });

        $activeStatuses = InvoiceStatus::activeValues();

        DB::table('shipments')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($shipments) use ($activeStatuses) {
                foreach ($shipments as $shipment) {
                    $activeInvoice = DB::table('invoices')
                        ->where('shipment_id', $shipment->id)
                        ->whereNull('deleted_at')
                        ->whereIn('status', $activeStatuses)
                        ->orderByDesc('id')
                        ->first();

                    if ($activeInvoice) {
                        DB::table('shipments')
                            ->where('id', $shipment->id)
                            ->update(['current_invoice_id' => $activeInvoice->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeign(['current_invoice_id']);
            $table->dropColumn('current_invoice_id');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['cancel_reason', 'cancelled_at']);
        });
    }
};

