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
        abort_unless($ws->usesWorkflow() && DocumentRegistry::has($ws->document_type), 404);
        abort_if($ws->workflow_status === TransactionStatus::Draft, 404);
    }

    private function html(StudentDocumentWorkspace $ws)
    {
        return response(app(DocumentPdf::class)->previewHtml($ws->load('user')))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Robots-Tag', 'noindex');
    }
}