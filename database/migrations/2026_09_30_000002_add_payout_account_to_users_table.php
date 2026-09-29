<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A payout account for users who are not vendors.
 *
 * The agent app has had a payout screen all along, with no save and nowhere to
 * save to: `payout_momo_network`, `payout_account_name`, `payout_account_number`
 * and `payout_account_updated_at` were added to **`vendors`**
 * (`2026_06_01_000001_add_payout_account_fields_to_vendors_table`), and a hub
 * agent is a `users` row, not a vendor. So there was no column to write to, and
 * the screen's Save button faked it with a `setTimeout`.
 *
 * Two columns are new rather than mirrored. The vendor set is Momo-only — its
 * validation restricts the network to mtn/telecel/airteltigo with no method
 * field — while the agent screen offers bank as well. Storing a bank account
 * without a method and a bank name would leave the two indistinguishable from a
 * wallet, so both are added here.
 *
 * All nullable: an account that has never been set is a legitimate state, and
 * every existing row is in it.
 */
return new class extends Migration
{
    /** column => [type, args] */
    private const COLUMNS = [
        'payout_method' => ['string', [20]],
        'payout_momo_network' => ['string', [40]],
        'payout_bank_name' => ['string', [120]],
        'payout_account_name' => ['string', [255]],
        // 20 mirrors the vendors column; Ghanaian bank and wallet numbers are
        // well inside it.
        'payout_account_number' => ['string', [20]],
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (self::COLUMNS as $column => [$type, $args]) {
                if (! Schema::hasColumn('users', $column)) {
                    $table->{$type}($column, ...$args)->nullable();
                }
            }

            if (! Schema::hasColumn('users', 'payout_account_updated_at')) {
                $table->timestamp('payout_account_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (array_keys(self::COLUMNS) as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('users', 'payout_account_updated_at')) {
                $table->dropColumn('payout_account_updated_at');
            }
        });
    }
};
