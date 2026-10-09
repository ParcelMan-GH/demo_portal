<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The hub agent's commission ledger.
 *
 * ## Why a table of its own, and not a `role` column on `agent_daily_quotas`
 *
 * The contact agent's money is a *band function of the day's cumulative
 * collection*: `agent_daily_quotas.collected_amount` is fed to
 * `CommissionTier::findTierForAmount()` and the payout is whatever band that
 * total lands in. A hub agent's money is the opposite shape — a flat amount per
 * parcel, GHC 0.50 when a parcel is checked in (INBOUND) and GHC 1.00 when it
 * leaves (OUTBOUND). There is no "amount collected" to band, and no daily
 * ladder: two parcels are worth exactly twice one parcel.
 *
 * Putting hub credits into `agent_daily_quotas` would force a fabricated
 * `collected_amount` that the tier lookup would then read as if it were a
 * customer payment, changing the band for the agent's real collections too (the
 * ladder is cumulative). And `agent_daily_quotas` is unique on
 * `(user_id, tracking_date)`, so it also cannot hold the two distinct credits —
 * inbound and outbound — a single parcel produces. So the hub agent needs its
 * own ledger; this is that ledger. The *money model* is unchanged — a row of
 * earned money that is later paid out — only the table differs, because the
 * shape of the money differs.
 *
 * ## The idempotency guard is the schema, not application code
 *
 * `unique(['shipment_item_id', 'direction'])` is the whole "ONE credit per item
 * per direction" rule. A re-scan of an already checked-in parcel hits the unique
 * index and inserts nothing, so the guarantee holds even if two requests race —
 * unlike an application-level `exists()` check followed by an insert, which two
 * concurrent scans can both pass. A parcel can therefore be credited at most
 * 0.50 once (inbound) and 1.00 once (outbound), ever.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hub_agent_commissions')) {
            return;
        }

        Schema::create('hub_agent_commissions', function (Blueprint $table) {
            $table->id();

            // The hub agent who is paid. A hub agent is a `users` row, exactly
            // like the contact agent whose commission lives in agent_daily_quotas.
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // The hub the work happened at. Nullable and nullOnDelete because the
            // credit is owed even if the hub row is later removed — the money must
            // not vanish with the warehouse.
            $table->foreignId('hub_id')->nullable()->constrained('warehouses')->nullOnDelete();

            // The parcel the work was done on. This plus `direction` is the unit
            // that may be paid exactly once.
            $table->foreignId('shipment_item_id')->constrained('shipment_items')->cascadeOnDelete();

            // 'inbound' for a check-in (arrival scan), 'outbound' for a release
            // (counter handover or doorstep dispatch). See the service's constants.
            $table->string('direction', 10);

            // The money, and a snapshot of the rate that produced it. The rate is
            // copied rather than read back from settings because the setting is
            // live and can change after the fact — a credit must keep the amount
            // it was actually paid at, or the ledger would silently re-value
            // history the next time an admin edits the rate.
            $table->decimal('amount', 8, 2);
            $table->decimal('rate', 8, 2);

            // Mirrors VendorEarning's per-item earnings ledger: rows start
            // 'approved' and become 'paid' when a payout settles them. No payout
            // flow is wired for hub agents yet, so today every row is 'approved'.
            $table->string('status', 20)->default('approved');

            // When the credit was written, kept explicit so it does not depend on
            // `created_at` being in a particular timezone.
            $table->timestamp('credited_at')->nullable();

            $table->timestamps();

            // ONE credit per item per direction — the guard against double-paying.
            $table->unique(['shipment_item_id', 'direction']);

            // The ledger is read per agent (their earnings) and per hub (a hub's
            // payout run), so both are indexed.
            $table->index(['user_id', 'created_at']);
            $table->index(['hub_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_agent_commissions');
    }
};
