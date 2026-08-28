<?php

use App\Enums\ItemStatus;
use App\Enums\ShipmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize legacy dirty enum values.
     *
     * Some shipment_items / shipments rows were written with an empty string
     * (or another invalid value) in the status column, which crashes every
     * enum cast read with a ValueError. Coerce them back to a valid state.
     */
    public function up(): void
    {
        $itemValues = array_map(fn ($case) => $case->value, ItemStatus::cases());

        DB::table('shipment_items')
            ->where(function ($query) use ($itemValues) {
                $query->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhereNotIn('status', $itemValues);
            })
            ->update(['status' => ItemStatus::PENDING->value]);

        $shipmentValues = array_map(fn ($case) => $case->value, ShipmentStatus::cases());

        DB::table('shipments')
            ->where(function ($query) use ($shipmentValues) {
                $query->whereNull('status')
                    ->orWhere('status', '')
                    ->orWhereNotIn('status', $shipmentValues);
            })
            ->update(['status' => ShipmentStatus::DRAFT->value]);
    }

    public function down(): void
    {
        // Data normalization cannot be reversed.
    }
};
