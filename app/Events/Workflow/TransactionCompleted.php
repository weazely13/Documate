<?php

namespace App\Events\Workflow;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired (after commit) when final verification is done. Listener: generate the official PDF and notify the student. */
class TransactionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public StudentDocumentWorkspace $workspace) {}
}
