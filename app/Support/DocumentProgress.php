<?php

namespace App\Support;

use App\Enums\ApprovalStatus;
use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Str;

/**
 * How a student's document is doing, in plain words: a status label, a tone, which of the
 * four steps it is on, and one sentence on what happens next.
 * Expects approvals_count, approved_count and approvals.role to be loaded for paperless rows.
 */
class DocumentProgress
{
    /** Tones: you = the student has to act, office = with the VPSD office, signing, done, closed. */
    public const TONES = [
        'you' => 'bg-amber-50 text-amber-800',
        'office' => 'bg-[#2A57B4]/10 text-[#1E4090]',
        'signing' => 'bg-slate-100 text-slate-700',
        'done' => 'bg-emerald-50 text-emerald-700',
        'closed' => 'bg-slate-100 text-slate-500',
    ];

    /**
     * @return array{title: string, href: string, paperless: bool, issued: bool, label: string, tone: string, step: int, next: string, closed: bool}
     */
    public static function describe(StudentDocumentWorkspace $workspace): array
    {
        $wf = $workspace->usesWorkflow();

        if (! $wf) {
            $done = $workspace->isCompleted();
            $display = $workspace->displayStatus();

            [$label, $tone, $step, $next] = $done
                ? ['Completed', 'done', 4, 'Completed. Open it to view or download.']
                : [$display['label'], $display['tone'] === 'red' ? 'you' : 'office', 1, 'Paper form: print it and bring it to the VPSD office.'];

            return [
                'title' => $workspace->template?->name ? Str::title(str_replace('_', ' ', $workspace->template->name)) : 'Untitled document',
                'href' => route('student.documents.show', $workspace->workspace_id),
                'paperless' => false,
                'issued' => false,
                'label' => $label,
                'tone' => $tone,
                'step' => $step,
                'next' => $next,
                'closed' => false,
            ];
        }

        $document = DocumentRegistry::find($workspace->document_type);
        $status = $workspace->workflow_status?->value;
        $signing = $status === 'under_review' && $workspace->approvals_count > 0;
        $current = $signing ? $workspace->approvals->firstWhere('status', ApprovalStatus::Pending) : null;
        $finishedAt = $workspace->issued_at ?? $workspace->completed_at ?? $workspace->updated_at;

        [$label, $tone, $step, $next] = match (true) {
            $status === 'draft' => ['Draft', 'you', 0, 'Not sent yet. Finish the form and submit it.'],
            $status === 'action_required' => ['Needs your changes', 'you', 1,
                'The office asked for changes' . ($workspace->workflow_note ? ': ' . Str::limit($workspace->workflow_note, 90) : '.')],
            $status === 'submitted' => ['In review', 'office', 1, 'The VPSD office will check it. Nothing to do yet.'],
            $signing => ['In signing', 'signing', 2,
                'Signed ' . $workspace->approved_count . ' of ' . $workspace->approvals_count . '.'
                    . ($current ? ' Waiting for ' . ($current->role->label ?? $current->label) . '.' : '')],
            $status === 'under_review' => ['In review', 'office', 1, 'The VPSD office is checking it. Nothing to do yet.'],
            $status === 'approved' => ['Almost done', 'office', 3, 'Everyone has signed. The VPSD office will finish it.'],
            $status === 'completed' => ['Completed', 'done', 4,
                'Completed' . ($finishedAt ? ' ' . $finishedAt->format('M j, Y') : '') . '. Open it to download the PDF.'],
            $status === 'rejected' => ['Rejected', 'closed', 0, $workspace->workflow_note ? Str::limit($workspace->workflow_note, 100) : 'This transaction was rejected.'],
            $status === 'voided' => ['Voided', 'closed', 0, $workspace->void_reason ? Str::limit($workspace->void_reason, 100) : 'Withdrawn by the VPSD office.'],
            default => [$workspace->workflow_status?->label() ?? '-', 'office', 1, ''],
        };

        $issued = (bool) $document?->issuedByOffice();

        // Issued documents describe themselves (e.g. "Report to the VPSD office on ...").
        if ($issued && ! in_array($status, ['completed', 'rejected', 'voided'], true)
            && ($line = $document->noticeLine($workspace->field_values ?? []))) {
            $next = $line;
        }

        return [
            'title' => $document?->name() ?? (string) $workspace->document_type,
            'href' => route('student.transactions.show', $workspace->workspace_id),
            'paperless' => true,
            'issued' => $issued,
            'label' => $label,
            'tone' => $tone,
            'step' => $step,
            'next' => $next,
            'closed' => $tone === 'closed',
        ];
    }
}
