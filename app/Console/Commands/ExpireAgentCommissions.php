<?php

namespace App\Console\Commands;

use App\Models\ShipmentItem;
use App\Services\Agent\AgentCommissionExpirationService;
use Illuminate\Console\Command;

/**
 * Forfeit the commission on parcels that were never confirmed in time.
 *
 * Runs idempotently and is safe to re-run: an item that is already forfeited is
 * left with the reason that first fired.
 *
 * Two flags carry the decisions that should belong to a human:
 *
 *   --backfill  Look backwards through parcels whose window closed before this
 *               command first ran. Without it the rule only bites going forward.
 *               On the live database 13 parcels were already past 72 hours when
 *               this was written, so a backfill is a real forfeiture of real
 *               earnings and is never the default.
 *
 *   --dry-run   Report what would happen and write nothing.
 *
 * The scheduled run passes neither, so production behaviour is "apply the rule
 * from now on".
 */
class ExpireAgentCommissions extends Command
{
    protected $signature = 'agents:expire-commissions
        {--backfill : Also expire parcels whose 72-hour window closed before this command first ran}
        {--dry-run : Report what would be forfeited without writing anything}
        {--agent= : Limit to a single agent id}';

    protected $description = 'Forfeit agent commission on parcels unconfirmed past the 72-hour SLA or the 10-unconfirmed cap';

    public function handle(AgentCommissionExpirationService $expiry): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $backfill = (bool) $this->option('backfill');
        $agentId = $this->option('agent') !== null ? (int) $this->option('agent') : null;

        if ($agentId !== null && $agentId <= 0) {
            $this->error('--agent must be a positive id.');

            return self::FAILURE;
        }

        if ($backfill && ! $dryRun) {
            /*
             * Loud on purpose. This is the one invocation that can forfeit money
             * that was already earned, in bulk, on the first run. An operator who
             * typed it should see exactly what it is about to do before it does it.
             */
            $this->warn('--backfill will forfeit parcels whose window closed BEFORE this command first ran.');
            $this->warn('That can include commission already earned. Use --dry-run first to see the list.');

            if (! $this->confirm('Continue and write the forfeitures?', false)) {
                $this->info('Aborted. Nothing was written.');

                return self::SUCCESS;
            }
        }

        $result = $expiry->sweep(backfill: $backfill, agentId: $agentId, dryRun: $dryRun);

        $this->newLine();
        $this->table(['Metric', 'Count'], [
            ['Items examined', $result['examined']],
            [$dryRun ? 'Would forfeit (72h SLA)' : 'Forfeited (72h SLA)', $result['sla_expired']],
            [$dryRun ? 'Would forfeit (10-cap)' : 'Forfeited (10-cap)', $result['cap_expired']],
            ['Agents at the cap', count($result['agents_at_cap'])],
        ]);

        foreach ($result['agents_at_cap'] as $ownerId => $count) {
            $this->warn("  Agent #{$ownerId} is at {$count} unconfirmed parcels past the window (cap is ".AgentCommissionExpirationService::UNCONFIRMED_CAP.').');
        }

        if ($dryRun) {
            $this->newLine();
            $this->info('Dry run — nothing was written.');
        } elseif ($result['sla_expired'] === 0 && $result['cap_expired'] === 0) {
            $this->newLine();
            $this->info('Nothing to expire.');
        }

        if (! $backfill) {
            $this->newLine();
            $this->line('Parcels whose window closed before this command first ran were skipped. Use --backfill to include them.');
        }

        return self::SUCCESS;
    }
}
