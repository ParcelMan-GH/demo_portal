<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The agent API (AgentParcelController) has always written `agent_id` and
 * `claimed_at` on shipment_items, but no migration ever created those columns,
 * and they were missing from the model's $fillable list. Claiming a parcel
 * therefore never actually recorded the agent.
 *
 * Guarded with hasColumn so it is safe on databases where the columns were
 * added by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_items', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable()->after('outgoing_batch_id');
                $table->foreign('agent_id')->references('id')->on('users')->nullOnDelete();
                $table->index(['agent_id', 'status']);
            }

            if (! Schema::hasColumn('shipment_items', 'claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->after('agent_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('shipment_items', 'agent_id')) {
                $table->dropForeign(['agent_id']);
                $table->dropIndex(['agent_id', 'status']);
                $table->dropColumn('agent_id');
            }

            if (Schema::hasColumn('shipment_items', 'claimed_at')) {
                $table->dropColumn('claimed_at');
            }
        });
    }
};
