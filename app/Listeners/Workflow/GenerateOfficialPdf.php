<?php

namespace App\Listeners\Workflow;

use App\Events\Workflow\TransactionCompleted;
use App\Services\Workflow\DocumentPdf;

/**
 * Laravel auto-discovers this from the handle() type-hint. Runs after the completing
 * transaction commits. A failure is reported but never undoes the completion; the student's
 * Download button regenerates the PDF if it is missing.
 */
class GenerateOfficialPdf
{
    public function handle(TransactionCompleted $event): void
    {
        try {
            app(DocumentPdf::class)->generateFor($event->workspace->fresh());
        } catch (\Throwable $e) {
            report($e);
        }
    }
}