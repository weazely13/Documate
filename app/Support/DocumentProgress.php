<?php

namespace App\Support;

use App\Enums\ApprovalStatus;
use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Str;

/**
 * How a student's document is doing, in plain words: its status (the system-wide words and
 * colours, see StatusStyle), who acts next (tone), which of the four steps it is on, and one
 * sentence on what happens next.
 * Expects approvals_count, approved_count and approvals.role to be loaded for paperless rows.
 */
class DocumentProgress
{
    // Tones say who acts next: you = the student, office = the VPSD office, signing, done, closed.
    // The status pill itself is <x-status-badge :status="state" />.

    /**
     * @return array{title: string, href: string, paperless: bool, issued: bool, state: string, label: string, tone: string, step: int, next: string, closed: bool}
     */
    public static function describe(StudentDocumentWorkspace $workspace): array
    {
        $wf = $workspace->usesWorkflow();

        if (! $wf) {
            $done = $workspace->isCompleted();
            $display = $workspace->displayStatus();

            [$tone, $step, $next] = $done
                ? ['done', 4, 'Completed. Open it to view or download.']
                : [$display['tone'] === 'red' ? 'you' : 'office', 1,
                    $display['label'] === 'Appointment visit completed'
                        ? 'Visit completed. The VPSD personnel will upload a photo of this document for it to become an online copy.'
                        : 'Paper form: print it and bring it to the VPSD office.'];

            return [
                'title' => $workspace->template?->name ? Str::title(str_replace('_', ' ', $workspace->template->name)) : 'Untitled document',
                'href' => route('student.documents.show', $workspace->workspace_id),
                'paperless' => false,
                'issued' => false,
                'state' => $done ? 'completed' : 'pending',
                'label' => StatusStyle::paper($done)['label'],
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

        [$tone, $step, $next] = match (true) {
            $status === 'draft' => ['you', 0, 'Not sent yet. Finish the form and submit it.'],
            $status === 'action_required' => ['you', 1,
                'The office asked for changes' . ($workspace->workflow_note ? ': ' . Str::limit($workspace->workflow_note, 90) : '.')],
            $status === 'submitted' => ['office', 1, 'The VPSD office will check it. Nothing to do yet.'],
            $signing => ['signing', 2,
                'Signed ' . $workspace->approved_count . ' of ' . $workspace->approvals_count . '.'
                    . ($current ? ' Waiting for ' . ($current->role->label ?? $current->label) . '.' : '')],
            $status === 'under_review' => ['office', 1, 'The VPSD office is checking it. Nothing to do yet.'],
            $status === 'approved' => ['office', 3, 'Everyone has signed. The VPSD office will finish it.'],
            $status === 'completed' => ['done', 4,
                'Completed' . ($finishedAt ? ' ' . $finishedAt->format('M j, Y') : '') . '. Open it to download the PDF.'],
            $status === 'rejected' => ['closed', 0, $workspace->workflow_note ? Str::limit($workspace->workflow_note, 100) : 'This transaction was rejected.'],
            $status === 'voided' => ['closed', 0, $workspace->void_reason ? Str::limit($workspace->void_reason, 100) : 'Voided by the VPSD office.'],
            default => ['office', 1, ''],
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
            'state' => StatusStyle::state($workspace),
            'label' => StatusStyle::transaction($status)['label'],
            'tone' => $tone,
            'step' => $step,
            'next' => $next,
            'closed' => $tone === 'closed',
        ];
    }

    /**
     * What a document's thumbnail shows: [url, isImage]. Sent paperless documents show themselves as they print,
     * drafts a blank copy of their form, paper forms their template image; null when there is nothing to show.
     */
    public static function thumbnail(StudentDocumentWorkspace $workspace): array
    {
        if (! $workspace->usesWorkflow()) {
            $image = $workspace->template?->currentVersion?->image_path;

            return $image ? [\Illuminate\Support\Facades\Storage::disk('public')->url($image), true] : [null, false];
        }

        $doc = DocumentRegistry::find($workspace->document_type);

        return match (true) {
            ! $doc => [null, false],
            $workspace->workflow_status?->value !== 'draft' => [route('student.transactions.preview', $workspace->workspace_id), false],
            ! $doc->issuedByOffice() => [route('student.forms.preview', $workspace->document_type), false],
            default => [null, false],
        };
    }

}
