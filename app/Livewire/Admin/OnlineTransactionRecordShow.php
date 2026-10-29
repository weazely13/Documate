<?php

namespace App\Livewire\Admin;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\DocumentPdf;
use App\Services\Workflow\Seal;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\AuditLabels;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Transaction Records' read-only view of a paperless (online) transaction.
 * Reviewing and signing happen in Workflow Review, never here.
 */
class OnlineTransactionRecordShow extends Component
{
    public int $workspaceId;

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        abort_unless($workspace->usesWorkflow() && DocumentRegistry::has($workspace->document_type), 404);
        abort_if($workspace->workflow_status === TransactionStatus::Draft, 404);

        $this->workspaceId = $workspace->workspace_id;
    }

    #[Computed]
    public function workspace(): StudentDocumentWorkspace
    {
        return StudentDocumentWorkspace::withTrashed()->with(['user.program', 'user.role'])->findOrFail($this->workspaceId);
    }

    public function downloadPdf()
    {
        $ws = $this->workspace;
        abort_unless($ws->workflow_status === TransactionStatus::Completed, 403);

        $path = $ws->generated_pdf_path;
        if (! $path || ! Storage::disk(DocumentPdf::DISK)->exists($path)) {
            $path = app(DocumentPdf::class)->generateFor($ws);
        }

        app(TransactionWorkflow::class)->recordDownload($ws, Auth::user());

        return Storage::disk(DocumentPdf::DISK)->download($path, $ws->transaction_code . '.pdf');
    }

    public function render()
    {
        $ws = $this->workspace;
        $document = DocumentRegistry::get($ws->document_type);
        $values = $ws->field_values ?? [];
        $status = $ws->workflow_status;

        [$result, $tone] = match ($status) {
            TransactionStatus::Completed => ['Completed', 'emerald'],
            TransactionStatus::Rejected => ['Rejected', 'slate'],
            TransactionStatus::Voided => ['Voided', 'slate'],
            TransactionStatus::ActionRequired => ['Still open: waiting on student', 'blue'],
            default => ['Still open: in review or signing', 'blue'],
        };

        // Every answer the form holds, as the student (and the office) filled it in.
        $answers = collect($document->fields() + $document->reviewerFields())
            ->reject(fn ($f, $name) => ($f['type'] ?? '') === 'slots')
            ->map(fn ($f, $name) => [
                'label' => $f['label'] ?? $name,
                'value' => $document->formatValue($f, $values[$name] ?? null),
            ])
            ->filter(fn ($a) => trim($a['value']) !== '')
            ->values();

        $user = $ws->user;

        return view('livewire.admin.online-transaction-record-show', [
            'ws' => $ws,
            'document' => $document,
            'result' => $result,
            'tone' => $tone,
            'studentName' => $values['student_name'] ?? ($user?->full_name ?? '') ?: 'Unknown student',
            'studentNumber' => $user?->student_number ?: ($values['student_number'] ?? '') ?: ($user?->role?->role_name ?? ''),
            'program' => $user?->program?->name ?: ($values['student_program'] ?? ''),
            'answers' => $answers,
            'approvals' => $ws->approvals()->with(['role', 'actor'])->get(),
            'audit' => AuditLabels::history($ws),
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
            'issuer' => \App\Support\Issuers::forOne($ws),
        ])->layout('layouts.app', ['title' => $ws->transaction_code ?: 'Transaction Records']);
    }
}
