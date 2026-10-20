<?php

namespace App\Listeners\Workflow;

use App\Events\Workflow\ApprovalRequested;
use App\Notifications\WorkflowNotification;
use App\Workflow\Documents\DocumentRegistry;
use App\Services\Workflow\ExternalSigning;
use Illuminate\Support\Facades\Notification;
use App\Models\User;

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
            if ($approval->signer_user_id) {
                    User::find($approval->signer_user_id)?->notify(WorkflowNotification::forSigner($approval));
                    return;
                }
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
            report($e);
            if (isset($approval) && ($approval->signer_type ?? null) === 'external') {
                app(\App\Services\Workflow\TransactionWorkflow::class)
                    ->recordExternalEvent($approval->workspace, 'external.invite_failed', [], $approval->id);
            }
        }
    }
}