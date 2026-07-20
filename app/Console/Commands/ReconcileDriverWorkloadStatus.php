<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Services\DriverWorkloadService;
use Illuminate\Console\Command;

class ReconcileDriverWorkloadStatus extends Command
{
    protected $signature = 'drivers:reconcile-workload-status {--dry-run}';

    protected $description = 'Reconcile stored rider availability with active pickup, transport, and delivery work';

    public function handle(DriverWorkloadService $workloads): int
    {
        $changed = 0;

        Driver::query()->orderBy('id')->chunkById(100, function ($drivers) use ($workloads, &$changed) {
            $summaries = $workloads->summaries($drivers->pluck('id')->all());

            foreach ($drivers as $driver) {
                $summary = $summaries[$driver->id];
                $expected = $summary['is_busy'] ? 'busy' : ($driver->status === 'offline' ? 'offline' : 'available');

                if ($driver->status === $expected) {
                    continue;
                }

                $changed++;
                $this->line("Rider #{$driver->id}: {$driver->status} -> {$expected}");
                if (! $this->option('dry-run')) {
                    $driver->forceFill(['status' => $expected])->saveQuietly();
                }
            }
        });

        $this->info(($this->option('dry-run') ? 'Would update' : 'Updated')." {$changed} rider(s).");

        return self::SUCCESS;
    }
}
