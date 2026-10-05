<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The contact agent's second confirmation on the hub's pickup code.
 *
 * The customer already quotes a `pickup_code` to the external hub agent, who
 * verifies it in HubController::release before the parcel is handed over. The
 * contact agent must now confirm the same code too, which is what makes a day's
 * commission unlockable.
 *
 * The confirmation is stored on the call log rather than in a table of its own:
 * a call is the act being confirmed, and the log already carries the agent, the
 * item and the outcome. Because an agent may call an item several times (a
 * reschedule then a confirmation), "this item is confirmed" is derived as "any of
 * this agent's logs for it is confirmed" — see AgentCallConfirmationService.
 *
 * Nothing here is backfilled. The columns are nullable and default to "not
 * confirmed", which is the correct reading of history: no existing call was
 * confirmed against a code, and the quotas those calls belong to are already
 * locked and were released by hand under the old rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agent_call_logs', function (Blueprint $table) {
            $table->timestamp('pickup_code_confirmed_at')->nullable()->after('payment_proof_path');

            // Where the agent got the code: 'customer' or 'hub_agent'. Recorded
            // because the two are not equally strong evidence — a hub agent always
            // knows the code, so a run of hub-sourced confirmations is the signal
            // that the follow-up is being short-cut. Costs nothing to capture and
            // cannot be recovered later if it is not.
            $table->string('pickup_code_source', 20)->nullable()->after('pickup_code_confirmed_at');

            // Who confirmed — 'agent' or 'admin'. The admin may confirm on the
            // agent's behalf, and that must stay visible so the admin does not
            // quietly become a rubber stamp.
            $table->string('pickup_code_confirmed_by', 20)->nullable()->after('pickup_code_source');

            $table->unsignedBigInteger('pickup_code_confirmed_by_user_id')->nullable()->after('pickup_code_confirmed_by');

            // Whether the parcel had already been released to the recipient at the
            // moment of confirmation. Nullable: null means "not confirmed". This is
            // the data that says whether the gate is proving receipt or only
            // proving the parcel reached the hub.
            $table->boolean('pickup_code_confirmed_before_release')->nullable()->after('pickup_code_confirmed_by_user_id');

            // Failed submissions, for rate limiting. Kept per log rather than per
            // item so a guess cannot be retried indefinitely by re-calling.
            $table->unsignedTinyInteger('pickup_code_attempts')->default(0)->after('pickup_code_confirmed_before_release');

            $table->index(['shipment_item_id', 'pickup_code_confirmed_at'], 'agent_call_logs_item_confirmed_index');
            $table->index(['agent_id', 'pickup_code_confirmed_at'], 'agent_call_logs_agent_confirmed_index');

            $table->foreign('pickup_code_confirmed_by_user_id')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('agent_call_logs', function (Blueprint $table) {
            $table->dropForeign(['pickup_code_confirmed_by_user_id']);
            $table->dropIndex('agent_call_logs_item_confirmed_index');
            $table->dropIndex('agent_call_logs_agent_confirmed_index');
            $table->dropColumn([
                'pickup_code_confirmed_at',
                'pickup_code_source',
                'pickup_code_confirmed_by',
                'pickup_code_confirmed_by_user_id',
                'pickup_code_confirmed_before_release',
                'pickup_code_attempts',
            ]);
        });
    }
};
