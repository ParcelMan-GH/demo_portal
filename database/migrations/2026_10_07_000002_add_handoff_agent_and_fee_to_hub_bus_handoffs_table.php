<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Handoff agent and agreed delivery fee on a bus handoff.
 *
 * ## The table is `hub_bus_handoffs`
 *
 * The request named `handovers` / `shipment_handovers`; neither table exists in
 * this schema. The bus handoff — hub agent hands a parcel to a bus driver, with a
 * proof photo and a public link for the recipient — is `hub_bus_handoffs`, model
 * `HubBusHandoff`. (`rider_team_handovers` is a different thing: rider-to-rider
 * team allocation, no bus driver and no fee.) So this lands there.
 *
 * ## Why a handoff agent is not the driver
 *
 * `driver_name`, `driver_phone`, `driver_id_number` and `vehicle_plate` already
 * exist and describe the person taking the parcel onto the bus. The handoff agent
 * is the other end — the agent who meets the bus at the destination. They were
 * previously unrecorded, so a dispute about who received the parcel had nothing to
 * point at.
 *
 * ## Why the fee is stored here and not read from the parcel
 *
 * `shipment_items.delivery_fee` already exists (decimal 10,2, added 2026-07-31)
 * and is untouched by this migration. That column is what the parcel costs
 * *commercially*. This one is what was actually *agreed at the handover*, which can
 * differ and is the number a driver or agent is paid against. Writing through to
 * `shipment_items.delivery_fee` would silently overwrite the commercial figure with
 * a transport arrangement, so the two are deliberately kept separate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hub_bus_handoffs', function (Blueprint $table) {
            // Guarded individually: this project auto-deploys on push to main, and a
            // half-applied run would otherwise stop the next one at the first
            // duplicate column.
            if (! Schema::hasColumn('hub_bus_handoffs', 'handoff_agent_name')) {
                $table->string('handoff_agent_name', 120)->nullable()->after('handed_off_by');
            }

            if (! Schema::hasColumn('hub_bus_handoffs', 'handoff_agent_phone')) {
                $table->string('handoff_agent_phone', 30)->nullable()->after('handoff_agent_name');
            }

            if (! Schema::hasColumn('hub_bus_handoffs', 'delivery_fee')) {
                // Nullable, not default 0.00: "no fee agreed" and "a fee of zero"
                // are different answers, and the SMS only quotes a fee when one
                // was actually agreed.
                $table->decimal('delivery_fee', 10, 2)->nullable()->after('handoff_agent_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hub_bus_handoffs', function (Blueprint $table) {
            foreach (['handoff_agent_name', 'handoff_agent_phone', 'delivery_fee'] as $column) {
                if (Schema::hasColumn('hub_bus_handoffs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
