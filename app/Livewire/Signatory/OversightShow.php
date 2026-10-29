<?php

namespace App\Livewire\Signatory;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\Seal;
use App\Workflow\AuditLabels;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** One transaction, read-only, for the Vice President for Student Development. */
class OversightShow extends Component
{
    public int $workspaceId;

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        abort_unless(Auth::user()->isVpsd(), 403);
        abort_unless($workspace->usesWorkflow() && DocumentRegistry::has($workspace->document_type), 404);
        abort_if($workspace->workflow_status === TransactionStatus::Draft, 404);

        $this->workspaceId = $workspace->workspace_id;
    }

    public function render()
    {
        $ws = StudentDocumentWorkspace::with('user')->where('workspace_id', $this->workspaceId)->firstOrFail();

        $audit = $ws->auditLogs()->with('actor')->get()->map(fn ($log) => [
            'at' => $log->created_at,
            'text' => AuditLabels::label($log->action),
            'who' => $log->actor
                ? $log->actor->full_name
                : ($log->meta['signer'] ?? 'System'),
            'note' => $log->meta['message'] ?? $log->meta['reason'] ?? null,
        ])->reverse()->values();

        return view('livewire.signatory.oversight-show', [
            'ws' => $ws,
            'document' => DocumentRegistry::get($ws->document_type),
            'approvals' => $ws->approvals()->with(['role', 'actor'])->get(),
            'audit' => $audit,
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
        ])->layout('layouts.app', ['title' => 'Document overview']);
    }
}