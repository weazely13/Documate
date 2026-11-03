<?php

namespace App\Listeners\Workflow;

use App\Events\Workflow\TransactionCompleted;
use App\Services\Workflow\TransactionSnapshot;

/** Auto-discovered from handle(). Freezes the finished transaction: its answers and its page as it now looks. */
class SnapshotCompletedTransaction
{
    public function handle(TransactionCompleted $event): void
    {
        try {
            app(TransactionSnapshot::class)->capture($event->workspace->fresh(), 'completed');
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
