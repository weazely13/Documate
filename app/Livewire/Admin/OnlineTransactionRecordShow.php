<?php

namespace App\Livewire\Admin;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\DocumentPdf;
use App\Services\Workflow\Seal;
use App\Services\Workflow\TransactionSnapshot;
use Illuminate\Support\Str;
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
        // A form whose code is gone still opens, from the snapshot saved when it was submitted or completed.
        abort_unless($workspace->usesWorkflow() && (DocumentRegistry::has($workspace->document_type) || TransactionSnapshot::hasAny($workspace)), 404);
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
            // Only the form's code can draw a new PDF; without it the saved one is all there is.
            abort_unless(DocumentRegistry::has($ws->document_type), 404, 'The official PDF was not saved and this form no longer exists.');
            $path = app(DocumentPdf::class)->generateFor($ws);
        }

        app(TransactionWorkflow::class)->recordDownload($ws, Auth::user());

        return Storage::disk(DocumentPdf::DISK)->download($path, $ws->transaction_code . '.pdf');
    }

    /** Appointments tied to this transaction: booked, then every status change (approved, attended, missed…). */
    private function appointmentHistory(StudentDocumentWorkspace $ws): \Illuminate\Support\Collection
    {
        $labels = [
            'approved' => 'Appointment approved',
            'rejected' => 'Appointment rejected',
            'attended' => 'Visit attended',
            'missed' => 'Appointment missed',
            'retracted' => 'Appointment cancelled',
            'cancelled' => 'Appointment cancelled',
            'rescheduled' => 'Appointment rescheduled',
        ];

        return $ws->appointments()->with(['user', 'statusLogs.changedBy'])->get()->flatMap(function ($a) use ($labels) {
            $when = trim(($a->appointment_date?->format('M j, Y') ?? '') . ($a->session ? ' · ' . ucfirst((string) $a->session) : ''));
            $queue = $a->queue_number ? ' · queue no. ' . $a->queue_number : '';

            $events = collect([[
                'text' => 'Appointment booked',
                'who' => $a->user?->full_name ?: 'Student',
                'role' => null,
                'at' => $a->created_at,
                'note' => 'For ' . $when . $queue,
                'kind' => 'appointment',
            ]]);

            foreach ($a->statusLogs as $log) {
                if ($log->status === 'pending') {
                    continue; // the booking itself
                }
                $events->push([
                    'text' => $labels[$log->status] ?? 'Appointment ' . str_replace('_', ' ', (string) $log->status),
                    'who' => $log->changedBy?->full_name ?: 'System',
                    'role' => null,
                    'at' => $log->created_at,
                    'note' => $log->note ?: ($log->status === 'rejected' ? $a->rejection_reason : null) ?: ('Visit on ' . $when),
                    'kind' => 'appointment',
                ]);
            }

            return $events;
        });
    }

    /** A stored answer as text, whatever shape it was saved in. */
    private function plain(mixed $v): string
    {
        return match (true) {
            is_bool($v) => $v ? 'Yes' : 'No',
            is_scalar($v) || $v === null => (string) $v,
            default => collect($v)->flatten()->filter(fn ($x) => is_scalar($x) && (string) $x !== '')->implode(', '),
        };
    }

    public function render()
    {
        $ws = $this->workspace;
        $document = DocumentRegistry::find($ws->document_type);
        $snap = $ws->snapshot ?? [];
        $values = $ws->field_values ?? [];
        $status = $ws->workflow_status;

        [$result, $tone] = match ($status) {
            TransactionStatus::Completed => ['Completed', 'emerald'],
            TransactionStatus::Rejected => ['Rejected', 'slate'],
            TransactionStatus::Voided => ['Voided', 'slate'],
            TransactionStatus::ActionRequired => ['In progress: for revision', 'blue'],
            default => ['In progress: in review or signing', 'blue'],
        };

        // Every answer the form holds, as the student (and the office) filled it in. From the form's code
        // when it still exists, otherwise from the snapshot taken when it was submitted or completed.
        $answers = $document
            ? collect($document->fields() + $document->reviewerFields())
                ->reject(fn ($f, $name) => ($f['type'] ?? '') === 'slots')
                ->map(fn ($f, $name) => [
                    'name' => (string) $name,
                    'label' => $f['label'] ?? $name,
                    'value' => $document->formatValue($f, $values[$name] ?? null),
                    'retired' => false,
                ])
            : collect($snap['answers'] ?? [])->map(fn ($a) => $a + ['retired' => false]);
        $answers = $answers->values();

        // Answers stored for fields the form no longer has (renamed or removed in the code): still shown,
        // labelled as they were when last saved, or by their stored name.
        $known = $answers->pluck('name')->filter()->all();
        $snapLabels = collect($snap['answers'] ?? [])->pluck('label', 'name');
        $orphans = collect($values)
            ->reject(fn ($v, $name) => in_array((string) $name, $known, true)
                || in_array($name, ['student_name', 'student_number', 'student_program'], true)
                || str_ends_with((string) $name, '_path'))
            ->map(fn ($v, $name) => [
                'name' => (string) $name,
                'label' => $snapLabels[$name] ?? Str::headline((string) $name),
                'value' => $this->plain($v),
                'retired' => (bool) $document,
            ]);
        $answers = $answers->concat($orphans->values())->filter(fn ($a) => trim((string) $a['value']) !== '')->values();

        $user = $ws->user;

        return view('livewire.admin.online-transaction-record-show', [
            'ws' => $ws,
            'document' => $document,
            'documentName' => $document?->name() ?? ($snap['document_name'] ?? Str::headline((string) $ws->document_type)),
            'codeGone' => $document === null,
            'result' => $result,
            'tone' => $tone,
            'studentName' => $values['student_name'] ?? ($user?->full_name ?? '') ?: 'Unknown student',
            'studentNumber' => $user?->student_number ?: ($values['student_number'] ?? '') ?: ($user?->role?->role_name ?? ''),
            'program' => $user?->program?->name ?: ($values['student_program'] ?? ''),
            'answers' => $answers,
            'approvals' => $ws->approvals()->with(['role', 'actor'])->get(),
            // The document's own trail, with the visits booked for it woven in. Newest first.
            'audit' => AuditLabels::history($ws)
                ->concat($this->appointmentHistory($ws))
                ->sortByDesc(fn ($e) => $e['at']?->timestamp ?? 0)
                ->values(),
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
            'issuer' => \App\Support\Issuers::forOne($ws),
        ])->layout('layouts.app', ['title' => $ws->transaction_code ?: 'Transaction Records']);
    }
}
