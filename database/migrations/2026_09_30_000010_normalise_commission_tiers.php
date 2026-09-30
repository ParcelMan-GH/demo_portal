<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Replace the coarse commission bands with a normalised progression.
 *
 * The bands in the database were the original 13: wide steps that jumped by 10
 * for the low thousands and then leapt 100 -> 120 -> 150 -> 200 across the top.
 * An agent collecting 3,050 and one collecting 3,499 both earned 120, which is a
 * large dead zone right where most collection days sit. These 17 bands smooth it.
 *
 * Two things this migration does that a plain upsert would not:
 *
 * 1. It DELETES bands that the new set does not contain (mins 2200, 2300, 3001,
 *    3501, 4001). Leaving them would not merely be untidy — it would pay the
 *    wrong amount. `CommissionTier::findTierForAmount()` matches every band that
 *    brackets the amount and takes the one with the highest `min_collection`, so
 *    an orphaned 2200-2299 -> 20 band would still win at 2,200 and pay 20 when
 *    the new progression says 2,100-2,399 pays 10.
 *
 * 2. The top band has no upper bound. 4,000+ stays on the last band whatever the
 *    amount, which is what makes it a fixed ceiling. The agent UI must not draw
 *    attention to that: there is no "maximum reached" state anywhere in this
 *    system, and none should be added — an agent at the ceiling sees the same
 *    figure as any other, it simply stops rising.
 *
 * The values are the ones supplied by the business. They are NOT invented here,
 * and they intentionally LOWER the top band: the old 4,001+ paid 200.00, the new
 * 4,000+ pays 150.00. That is a real reduction in what a high-collecting agent
 * earns, and it is deliberate.
 *
 * Idempotent: keyed on `min_collection`, so re-running updates rather than
 * duplicating. `is_active` is only set on INSERT, so an admin who switches a band
 * off in the new UI keeps it off across deploys.
 */
return new class extends Migration
{
    /**
     * min_collection => [max_collection, payout_amount]
     */
    private const TIERS = [
        2100.00 => [2399.99, 10.00],
        2400.00 => [2499.99, 40.00],
        2500.00 => [2599.99, 50.00],
        2600.00 => [2699.99, 60.00],
        2700.00 => [2799.99, 70.00],
        2800.00 => [2899.99, 80.00],
        2900.00 => [2999.99, 90.00],
        3000.00 => [3099.99, 100.00],
        3100.00 => [3299.99, 105.00],
        3300.00 => [3399.99, 110.00],
        3400.00 => [3499.99, 115.00],
        3500.00 => [3599.99, 120.00],
        3600.00 => [3699.99, 125.00],
        3700.00 => [3799.99, 130.00],
        3800.00 => [3899.99, 135.00],
        3900.00 => [3999.99, 140.00],
        4000.00 => [null, 150.00],
    ];

    public function up(): void
    {
        DB::transaction(function () {
            // Clear the bands this progression supersedes BEFORE upserting, so a
            // failure part-way cannot leave two bands claiming the same amount.
            DB::table('commission_tiers')
                ->whereNotIn('min_collection', array_keys(self::TIERS))
                ->delete();

            foreach (self::TIERS as $min => [$max, $payout]) {
                $existing = DB::table('commission_tiers')->where('min_collection', $min)->first();

                if ($existing) {
                    DB::table('commission_tiers')->where('id', $existing->id)->update([
                        'max_collection' => $max,
                        'payout_amount'  => $payout,
                        'updated_at'     => now(),
                    ]);

                    continue;
                }

                DB::table('commission_tiers')->insert([
                    'min_collection' => $min,
                    'max_collection' => $max,
                    'payout_amount'  => $payout,
                    'is_active'      => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        });
    }

    /**
     * Not reversed.
     *
     * Rolling back would mean re-inserting the deleted bands and guessing which
     * of the surviving rows an admin had since edited, so a rollback would
     * corrupt a tuned ledger rather than restore it. The previous values remain
     * readable in git history if they are ever needed.
     */
    public function down(): void
    {
    }
};
