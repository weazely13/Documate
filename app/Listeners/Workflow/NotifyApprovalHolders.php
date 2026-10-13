<?php

namespace App\Listeners\Workflow;

use App\Events\Workflow\ApprovalRequested;
use App\Notifications\WorkflowNotification;
use App\Services\Workflow\DocumentRegistry;
use App\Services\Workflow\ExternalSigning;
use Illuminate\Support\Facades\Notification;

/**
 * Auto-discovered from the handle() type-hint.
 *  - internal step: notify everyone who holds the role today (database notification)
 *  - external step: create the signing request and email the invitation link
 */
class NotifyApprovalHolders
{
    public function handle(ApprovalRequested $event): void
    {
        try {
            $approval = $event->approval->fresh(['workspace', 'role']);
            if (! $approval) {
                return;
            }

            if (($approval->signer_type ?? 'internal') === 'external') {
                // Manual-invite documents: the Chairman sends the link himself.
                if (DocumentRegistry::get($approval->workspace->document_type)->manualExternalInvites()) {
                    return;
                }

                app(ExternalSigning::class)->invite($approval);

                return;
            }

            Notification::send($approval->role->holdersFor($approval->scope_type, $approval->scope_id)->get(), WorkflowNotification::forSigner($approval));
        } catch (\Throwable $e) {
            report($e); // never let a notification failure undo an approval step
        }
    }
}