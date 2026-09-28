<?php

namespace App\Observers;

use App\Models\AppointmentStatusLog;

class AppointmentStatusLogObserver
{
    public function created(AppointmentStatusLog $log): void
    {
        if ($log->status !== 'attended') {
            return;
        }

        $workspace = $log->appointment?->workspace;

        if (! $workspace || $workspace->isCompleted()) {
            return;
        }

        $workspace->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}