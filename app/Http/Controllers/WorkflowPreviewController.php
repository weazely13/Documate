<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Services\Workflow\DocumentPdf;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;

/** Serves the document exactly as the PDF will look, as HTML, to be shown in an iframe. */
class WorkflowPreviewController extends Controller
{
    public function admin(StudentDocumentWorkspace $workspace)
    {
        $this->guard($workspace);

        return $this->html($workspace);
    }

    public function signatory(TransactionApproval $approval)
    {
        $user = Auth::user();
        $approval->loadMissing('workspace');
        $ws = $approval->workspace;

        // Same rules as Signatory\Show::mount()
        abort_unless($user->isSignatory() || (int) $approval->acted_by === (int) $user->id, 403);
        abort_if((int) $ws->user_id === (int) $user->id, 403);

        $mine = (int) $approval->acted_by === (int) $user->id;
        $holds = $approval->isAssignedTo($user);
        abort_unless($mine || ($holds && $approval->status === ApprovalStatus::Pending), 404);

        $this->guard($ws);

        return $this->html($ws);
    }

    private function guard(StudentDocumentWorkspace $ws): void
    {
        // Without its form's code, a transaction can still be shown from its saved snapshot.
        abort_unless($ws->usesWorkflow() && (DocumentRegistry::has($ws->document_type) || \App\Services\Workflow\TransactionSnapshot::hasAny($ws)), 404);
        abort_if($ws->workflow_status === TransactionStatus::Draft, 404);
    }
    /** A student's own transaction (or one that lists them), e.g. the document cards when booking a visit. */
    public function student(StudentDocumentWorkspace $workspace)
    {
        abort_unless($workspace->isVisibleTo(Auth::id()), 403);
        $this->guard($workspace);

        return $this->html($workspace);
    }

    public function oversight(StudentDocumentWorkspace $workspace)
    {
        abort_unless(Auth::user()->isVpsd(), 403);
        $this->guard($workspace);

        return $this->html($workspace);
    }

    /**
     * A completely blank copy of a form the student can start: the cards on Home's "Explore documents".
     * No student details, answers, signatures or verification details; nothing is saved.
     */
    public function blank(string $type)
    {
        $document = DocumentRegistry::find($type);
        abort_unless($document && ! $document->issuedByOffice() && ! $document->retired(), 404);

        // Identical for everyone, and the cards ask for many at once, so each form is kept for 10 minutes.
        $html = \Illuminate\Support\Facades\Cache::remember("blank-preview:{$type}", now()->addMinutes(10), function () use ($type) {
            $ws = (new StudentDocumentWorkspace())->forceFill([
                'document_type' => $type,
                'field_values' => [],
            ]);

            return app(DocumentPdf::class)->previewHtml($ws);
        });

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, max-age=300')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Robots-Tag', 'noindex');
    }

    /** A blank copy of a report the signed-in person may issue (Home > Explore cards). Same cache as blank(). */
    public function issuable(string $type)
    {
        $document = DocumentRegistry::find($type);
        abort_unless($document && $document->canBeIssuedBy(auth()->user()), 404);

        $html = \Illuminate\Support\Facades\Cache::remember("blank-preview:{$type}", now()->addMinutes(10), fn () => app(DocumentPdf::class)->previewHtml(
            (new StudentDocumentWorkspace())->forceFill(['document_type' => $type, 'field_values' => []])
        ));

        return response($html)
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, max-age=300')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Robots-Tag', 'noindex');
    }

    private function html(StudentDocumentWorkspace $ws)
    {
        // A completed transaction shows the page saved when it was finished; one whose form's code is gone,
        // its last saved page. Everything else is drawn live.
        $saved = \App\Services\Workflow\TransactionSnapshot::savedPage($ws);
        abort_if($saved === null && ! DocumentRegistry::has($ws->document_type), 404);

        // Pages saved before the mobile fix lack it: stop phones from enlarging the text.
        if ($saved !== null) {
            $saved = str_ireplace('</head>', '<style>html,html *{-webkit-text-size-adjust:100%!important;text-size-adjust:100%!important}</style></head>', $saved);
        }

        return response($saved ?? app(DocumentPdf::class)->previewHtml($ws->load('user')))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Robots-Tag', 'noindex');
    }
}