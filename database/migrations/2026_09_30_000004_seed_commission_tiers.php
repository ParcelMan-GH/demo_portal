<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Put the agent commission bands into the database.
 *
 * `commission_tiers` was empty in production, and that alone made the whole
 * commission feature read as zero. The payout an agent earns is not a column
 * anywhere — it is resolved from these bands at the call's collected amount
 * (`CommissionTier::findTierForAmount()`). With no bands, that lookup returns
 * null, every payout is 0.00, and the agent's balance is GH₵ 0.00 no matter how
 * many payments they confirm. The app looked broken; the table was simply empty.
 *
 * The bands already existed as `CommissionTierSeeder`, but a seeder only runs
 * when someone seeds, and it was not registered in `DatabaseSeeder` either — so
 * it had never run here. A migration is the right home for them: this is
 * configuration the application cannot work without, and migrations are what the
 * deploy actually runs.
 *
 * The values are copied from that seeder unchanged. None of them are invented
 * here — inventing payout amounts would be inventing the business's money.
 *
 * Idempotent, and deliberately non-destructive:
 *  - keyed on `min_collection`, so re-running updates instead of duplicating;
 *  - `is_active` is only set on INSERT. An admin who has since switched a band
 *    off in the ledger keeps it off; a deploy must not silently re-enable it.
 */
return new class extends Migration
{
    /**
     * min_collection => [max_collection, payout_amount]
     *
     * Mirrors CommissionTierSeeder. The last band has no upper bound so anything
     * above 4000 stays on the top tier.
     */
    private const TIERS = [
        2100.00 => [2199.99, 10.00],
        2200.00 => [2299.99, 20.00],
        2300.00 => [2399.99, 30.00],
        2400.00 => [2499.99, 40.00],
        2500.00 => [2599.99, 50.00],
        2600.00 => [2699.99, 60.00],
        2700.00 => [2799.99, 70.00],
        2800.00 => [2899.99, 80.00],
        2900.00 => [2999.99, 90.00],
        3000.00 => [3000.99, 100.00],
        3001.00 => [3500.99, 120.00],
        3501.00 => [4000.99, 150.00],
        4001.00 => [null, 200.00],
    ];

    public function up(): void
    {
        foreach (self::TIERS as $min => [$max, $payout]) {
            $existing = DB::table('commission_tiers')->where('min_collection', $min)->first();

            if ($existing) {
                DB::table('commission_tiers')->where('id', $existing->id)->update([
                    'max_collection' => $max,
                    'payout_amount' => $payout,
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('commission_tiers')->insert([
                'min_collection' => $min,
                'max_collection' => $max,
                'payout_amount' => $payout,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Nothing to undo.
     *
     * These are the payout bands the ledger already computes against, and an
     * admin may have edited them since. Deleting them on a rollback would blank
     * every agent's payout rather than restore anything, and the rows carry no
     * record of having come from here — so this stays deliberately empty.
     */
    public function down(): void
    {
    }
};
