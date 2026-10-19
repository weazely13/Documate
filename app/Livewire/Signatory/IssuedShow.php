<?php

namespace App\Livewire\Signatory;

use App\Enums\TransactionStatus as S;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Services\Workflow\Seal;
use App\Workflow\AuditLabels;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** One document the signatory issued: preview, progress, history and PDF. Read-only. */
class IssuedShow extends Component
{
    public int $workspaceId;

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        $user = Auth::user();

        abort_unless($user->isSignatory(), 403);
        abort_unless($workspace->usesWorkflow() && DocumentRegistry::has($workspace->document_type), 404);
        abort_if($workspace->workflow_status === S::Draft, 404);
        abort_unless($workspace->wasIssuedBy($user), 404);

        $this->workspaceId = $workspace->workspace_id;
    }

    public function render()
    {
        $user = Auth::user();
        $ws = StudentDocumentWorkspace::with('user')->where('workspace_id', $this->workspaceId)->firstOrFail();
        $document = DocumentRegistry::get($ws->document_type);
        $approvals = $ws->approvals()->with(['role', 'actor'])->get();

        $audit = $ws->auditLogs()->with('actor')->get()->map(fn ($log) => [
            'at' => $log->created_at,
            'text' => AuditLabels::label($log->action),
            'who' => $log->actor
                ? trim($log->actor->first_name . ' ' . $log->actor->last_name)
                : ($log->meta['signer'] ?? 'System'),
            'note' => $log->meta['message'] ?? $log->meta['reason'] ?? null,
        ])->reverse()->values();

        $pending = $approvals->first(fn ($a) => $a->status->value === 'pending');

        $nextStep = match ($ws->workflow_status) {
            S::Submitted => 'Waiting for the VPSD office to review.',
            S::UnderReview => $pending
                ? 'Waiting for ' . ($pending->role->label ?? ($pending->external_name ?: 'the signer')) . ' to sign.'
                : 'Waiting for the VPSD office.',
            S::ActionRequired => 'The VPSD office asked for changes.',
            S::Approved => 'All signatures are in. The VPSD office will do the final verification.',
            S::Completed => 'Completed. You can download the official PDF.',
            S::Rejected => 'Rejected. ' . $ws->workflow_note,
            S::Voided => 'Voided by the VPSD office. ' . $ws->void_reason,
            default => '',
        };

        // Link to the signing page when this user also has a step on the chain.
        $mine = (int) $ws->user_id === (int) $user->id ? null
            : ($approvals->first(fn ($a) => (int) $a->acted_by === (int) $user->id)
                ?? TransactionApproval::assignableTo($user)->where('workspace_id', $ws->workspace_id)->first());

        return view('livewire.signatory.issued-show', [
            'ws' => $ws,
            'document' => $document,
            'approvals' => $approvals,
            'audit' => $audit,
            'nextStep' => $nextStep,
            'mine' => $mine,
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
        ])->layout('layouts.app', ['title' => $ws->transaction_code ?: 'Issued document']);
    }
}