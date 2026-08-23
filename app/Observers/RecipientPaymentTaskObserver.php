<?php

namespace App\Observers;

use App\Models\RecipientPaymentTask;
use App\Models\AgentDailyQuota;

class RecipientPaymentTaskObserver
{
    public function updated(RecipientPaymentTask $task): void
    {
        // Listen for when a task gets assigned to an agent (like from a mobile scan)
        if ($task->wasChanged('assigned_to_user_id') && $task->assigned_to_user_id) {
            
            $quota = AgentDailyQuota::firstOrCreate(
                [
                    'user_id'       => $task->assigned_to_user_id,
                    'tracking_date' => today(),
                ],
                [
                    'assigned_tasks'   => 0,
                    'completed_tasks'  => 0,
                    'collected_amount' => 0.00,
                ]
            );

            // Add this new scan to their daily target!
            $quota->increment('assigned_tasks');
        }
    }
}