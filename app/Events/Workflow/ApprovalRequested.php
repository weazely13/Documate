<?php

namespace App\Events\Workflow;

use App\Models\TransactionApproval;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Fired (after commit) when an approval step opens. Listener: notify current holders of the role. */
class ApprovalRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public TransactionApproval $approval) {}
}
