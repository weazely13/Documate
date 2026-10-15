<?php

namespace App\Livewire\Admin\Workflow;

use App\Enums\TransactionStatus;
use App\Exceptions\Workflow\WorkflowException;
use App\Models\SignatoryRole;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\DocumentPdf;
use App\Services\Workflow\Seal;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Services\Workflow\ExternalSigning;
use App\Services\Workflow\SignatoryScope;

/** Review one workflow transaction. Every button maps to one TransactionWorkflow method. */
class Show extends Component
{
    public int $workspaceId;
    public ?string $modal = null;   // 'request' | 'reject'
    public string $message = '';
    public ?string $notice = null;
    public string $identityMethod = '';
    public string $identityNote = '';
    public bool $identityConfirmed = false;

    public bool $reissue = true;
    public array $reviewerValues = [];

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        abort_unless($workspace->usesWorkflow() && DocumentRegistry::has($workspace->document_type), 404);
        abort_if($workspace->workflow_status === TransactionStatus::Draft, 404);

        $this->workspaceId = $workspace->workspace_id;
        $saved = $workspace->field_values ?? [];
        foreach (array_keys(DocumentRegistry::get($workspace->document_type)->reviewerFields()) as $name) {
            $this->reviewerValues[$name] = $saved[$name] ?? '';
        }
    }

    #[Computed]
    public function workspace(): StudentDocumentWorkspace
    {
        return StudentDocumentWorkspace::with('user')->findOrFail($this->workspaceId);
    }

    /* ------------------------------ actions ------------------------------ */

    public function startReview(): void
    {
        $this->act(fn ($w, $ws, $u) => $w->startReview($ws, $u), 'Review started.');
    }

    public function sendForSigning(): void
    {
        $this->resetErrorBag();
        $check = null;

        if ($this->needsIdentityCheck()) {  
            $this->validate([
                'identityMethod' => ['required', 'in:' . implode(',', array_keys(TransactionWorkflow::IDENTITY_METHODS))],
                'identityNote' => ['nullable', 'string', 'max:300'],
                'identityConfirmed' => ['accepted'],
            ], [
                'identityMethod.required' => 'Choose how you verified the rater.',
                'identityConfirmed.accepted' => 'Confirm that you checked the rater.',
            ]);

            $check = [
                'method' => $this->identityMethod,
                'note' => trim($this->identityNote) ?: null,
                'confirmed' => true,
            ];
        }

        $this->act(
            fn ($w, $ws, $u) => $w->acceptForSigning($ws, $u, $check, $this->reviewerValues),
            'Sent for signing. The first signatory has been notified.'
        );

        if (! $this->getErrorBag()->has('action')) {
            $this->reset(['identityMethod', 'identityNote', 'identityConfirmed']);
        }
    }

    private function needsIdentityCheck(): bool
    {
        return collect(DocumentRegistry::get($this->workspace->document_type)->chain($this->workspace->field_values ?? []))
            ->contains(fn ($s) => ($s['type'] ?? 'internal') === 'external');
    }

    public function complete(): void
    {
        $this->act(fn ($w, $ws, $u) => $w->complete($ws, $u), 'Completed. The official PDF is being generated.');
    }

    public function openModal(string $which): void
    {
        if (in_array($which, ['request', 'reject', 'void'], true)) {
            $this->resetErrorBag();
            $this->message = '';
            $this->reissue = true;
            $this->modal = $which;
        }
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->message = '';
        $this->resetErrorBag();
    }

    public function submitModal(): void
    {
        $text = $this->message;

        match ($this->modal) {
            'request' => $this->act(fn ($w, $ws, $u) => $w->requestAction($ws, $u, $text), 'Changes requested. The student has been notified.'),
            'reject' => $this->act(fn ($w, $ws, $u) => $w->reject($ws, $u, $text), 'Transaction rejected. The student has been notified.'),
            'void' => $this->act(
                fn ($w, $ws, $u) => $w->void($ws, $u, $text, $this->reissue),
                $this->reissue
                    ? 'Document voided. A corrected draft was created and the student was notified.'
                    : 'Document voided. The student was notified.'
            ),
            default => null,
        };
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

    private function act(callable $fn, string $success): void
    {
        $this->resetErrorBag();

        try {
            $fn(app(TransactionWorkflow::class), $this->workspace, Auth::user());
        } catch (WorkflowException $e) {
            $this->addError('action', $e->getMessage());

            return;
        }

        $this->modal = null;
        $this->message = '';
        $this->notice = $success;
        unset($this->workspace);
    }
    public function resendInvite(): void
    {
        $this->resetErrorBag();
        $ws = $this->workspace;
        $approval = $ws->approvals()->where('status', 'pending')->where('signer_type', 'external')->first();

        if (! $approval || $ws->workflow_status !== TransactionStatus::UnderReview) {
            $this->addError('action', 'There is no pending external signer to resend to.');

            return;
        }

        try {
            app(ExternalSigning::class)->invite($approval);
            $this->notice = 'A new invitation was emailed. The previous link no longer works.';
        } catch (\Throwable $e) {
            report($e);
            $this->addError('action', 'The invitation email could not be sent. Please try again shortly.');
        }
}

    /* ------------------------------- render ------------------------------ */

    public function render()
    {
        $ws = $this->workspace;
        $document = DocumentRegistry::get($ws->document_type);
        $approvals = $ws->approvals()->with(['role', 'actor'])->get();

        $values = $ws->field_values ?? [];
        $ws->loadMissing('user.program');

        $chainPreview = collect($document->chain($values))->map(function ($step) use ($values, $ws) {
            if (($step['type'] ?? 'internal') === 'external') {
                $name = trim((string) ($values[$step['name_field']] ?? ''));
                $email = trim((string) ($values[$step['email_field']] ?? ''));

                return [
                    'label' => $step['label'],
                    'role' => 'External signer (no account)',
                    'holders' => array_filter([$name !== '' ? "{$name} <{$email}>" : $email]),
                ];
            }

            $role = SignatoryRole::where('key', $step['role'])->first();
            $type = $step['scope'] ?? null;
            $id = $type ? (($step['scope_id'] ?? null) ?: SignatoryScope::resolve($type, $ws->user)) : null;
            $holders = $role ? $role->holdersFor($type, $id)->get() : collect();

            return [
                'label' => $step['label'],
                'role' => ($role?->label ?? $step['role']) . ($type ? ' (' . (SignatoryScope::name($type, $id) ?? "no {$type} on account") . ')' : ''),
                'holders' => $holders->map(fn ($u) => trim($u->first_name . ' ' . $u->last_name))->all(),
            ];
        });

        $audit = $ws->auditLogs()->with('actor')->get()->map(fn ($log) => [
            'text' => \App\Workflow\AuditLabels::label($log->action),
            'who' => $log->actor ? trim($log->actor->first_name . ' ' . $log->actor->last_name) : 'System',
            'role' => $log->actor_role,
            'at' => $log->created_at,
            'note' => $log->meta['message']
                ?? $log->meta['reason']
                ?? ($log->action === 'external.identity_checked'
                    ? (TransactionWorkflow::IDENTITY_METHODS[$log->meta['method'] ?? ''] ?? '')
                        . (! empty($log->meta['note']) ? ': ' . $log->meta['note'] : '')
                    : null),
        ])->reverse()->values();

        return view('livewire.admin.workflow.show', [
            'ws' => $ws,
            'document' => $document,
            'approvals' => $approvals,
            'chainPreview' => $chainPreview,
            'audit' => $audit,
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
            'needsIdentityCheck' => $this->needsIdentityCheck(),
            'identityMethods' => TransactionWorkflow::IDENTITY_METHODS,
            'replacementCode' => $ws->superseded_by
                ? \App\Models\StudentDocumentWorkspace::whereKey($ws->superseded_by)->value('transaction_code')
                : null,
            'reviewerFields' => $document->reviewerFields(),
        ])->layout('layouts.app', ['title' => $ws->transaction_code ?: 'Workflow Review']);
    }
}
