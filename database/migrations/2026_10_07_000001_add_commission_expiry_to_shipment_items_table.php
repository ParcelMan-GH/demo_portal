<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commission expiry: the 72-hour SLA and the 10-unconfirmed cap.
 *
 * Marked on `shipment_items`, not on a table of its own, because the parcel is
 * what expires. The commission is not a row anywhere either — it is derived from
 * the agent's call logs against this item (see AgentCommissionController::lineItems),
 * and the codebase's own doctrine is that the figure is derived in exactly one
 * place rather than stored and re-derived. So expiry records *that* an item was
 * forfeited, *when*, and *why*; it deliberately does not snapshot an amount.
 * A stored amount would be a second source of truth for money, and the first
 * thing that happens after a tier change is that it disagrees with the ledger.
 *
 * `commission_expiry_reason` is the rule that fired, kept separate from
 * `commission_expiry_source`:
 *
 *   reason: sla_72h | cap_10
 *   source: system | admin
 *
 * The split matters for audit. "The sweeper forfeited this at 03:00" and "an
 * admin forfeited this by hand" are different events, and collapsing them would
 * make an admin's decision indistinguishable from an automatic one. It also
 * leaves room for an admin to reverse and re-apply by hand without the row
 * claiming the system did it.
 *
 * Nothing is backfilled. Every existing row reads as "not expired", which is the
 * correct reading of history: no parcel was ever subject to this rule. The
 * sweeper applies only to items that cross the threshold after deploy unless it
 * is explicitly asked to look backwards — see `agents:expire-commissions
 * --backfill`. On the live database 13 parcels would forfeit the moment a
 * retroactive pass ran, so that decision belongs to a human, not to a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if (! Schema::hasColumn('shipment_items', 'commission_expired_at')) {
                $table->timestamp('commission_expired_at')->nullable()->after('claimed_at');
            }

            if (! Schema::hasColumn('shipment_items', 'commission_expiry_reason')) {
                $table->string('commission_expiry_reason', 30)->nullable()->after('commission_expired_at');
            }

            if (! Schema::hasColumn('shipment_items', 'commission_expiry_source')) {
                $table->string('commission_expiry_source', 20)->nullable()->after('commission_expiry_reason');
            }

            // Why the cap fired, or why an admin reversed it. Free text rather
            // than an enum: this is the only place the specific circumstance is
            // recorded, and it is read by humans.
            if (! Schema::hasColumn('shipment_items', 'commission_expiry_note')) {
                $table->string('commission_expiry_note', 255)->nullable()->after('commission_expiry_source');
            }

            if (! Schema::hasColumn('shipment_items', 'commission_expiry_reversed_at')) {
                $table->timestamp('commission_expiry_reversed_at')->nullable()->after('commission_expiry_note');
            }
        });

        Schema::table('shipment_items', function (Blueprint $table) {
            // The sweeper's own query: "unexpired items for this agent, past the
            // threshold". Scoped by agent because both the cap and the per-agent
            // counter read it that way.
            if (! $this->hasIndex('shipment_items', 'shipment_items_agent_expiry_index')) {
                $table->index(['agent_id', 'commission_expired_at'], 'shipment_items_agent_expiry_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            if ($this->hasIndex('shipment_items', 'shipment_items_agent_expiry_index')) {
                $table->dropIndex('shipment_items_agent_expiry_index');
            }

            foreach ([
                'commission_expired_at',
                'commission_expiry_reason',
                'commission_expiry_source',
                'commission_expiry_note',
                'commission_expiry_reversed_at',
            ] as $column) {
                if (Schema::hasColumn('shipment_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Guarded because both this deployment and its siblings were hand-patched in
     * places, so an index created by hand must not make the migration explode.
     */
    private function hasIndex(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$database, $table, $index]
        );

        return $rows !== [];
    }
};
