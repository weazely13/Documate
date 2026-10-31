<?php

namespace App\Jobs;

use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\TransactionReviewAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

/** Runs the AI pre-check on a submitted workflow transaction (see TransactionReviewAgent). */
class ReviewTransactionSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 15;

    public function __construct(public StudentDocumentWorkspace $workspace)
    {
    }

    /** One check per transaction at a time; a second request just waits its turn. */
    public function middleware(): array
    {
        return [(new WithoutOverlapping('transaction-ai-' . $this->workspace->getKey()))->releaseAfter(10)->expireAfter(120)];
    }

    public function handle(TransactionReviewAgent $agent): void
    {
        $agent->review($this->workspace->fresh() ?? $this->workspace);
    }

    public function failed(\Throwable $e): void
    {
        report($e);
        app(TransactionReviewAgent::class)->markFailed($this->workspace);
    }

    /** Marks the row as waiting and queues the check once the surrounding transaction commits. */
    public static function start(StudentDocumentWorkspace $ws): void
    {
        StudentDocumentWorkspace::whereKey($ws->getKey())->update(['ai_flag' => 'pending']);
        static::dispatch($ws)->afterCommit();
    }
}
