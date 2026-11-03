<?php

namespace App\Console\Commands;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\TransactionSnapshot;
use Illuminate\Console\Command;

/** Freezes the online transactions made before snapshots existed (or every one again with --all). */
class SnapshotTransactions extends Command
{
    protected $signature = 'documate:snapshot-transactions {--all : Capture again even when a snapshot exists}';

    protected $description = 'Save a snapshot (answers and rendered page) of each submitted online transaction';

    public function handle(TransactionSnapshot $snapshots): int
    {
        $done = 0;

        StudentDocumentWorkspace::withTrashed()
            ->whereNotNull('document_type')
            ->where('workflow_status', '!=', TransactionStatus::Draft->value)
            ->when(! $this->option('all'), fn ($q) => $q->whereNull('snapshot'))
            ->chunkById(50, function ($rows) use ($snapshots, &$done) {
                foreach ($rows as $ws) {
                    $snapshots->capture($ws, $ws->workflow_status === TransactionStatus::Completed ? 'completed' : 'submitted');
                    $done++;
                }
            }, 'workspace_id');

        $this->info("Snapshots saved for {$done} transaction(s).");

        return self::SUCCESS;
    }
}
