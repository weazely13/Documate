<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\DocumentPdf;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/** Preview and PDF for a document the logged-in signatory issued. */
class IssuedDocumentController extends Controller
{
    private function authorizeIssuer(StudentDocumentWorkspace $ws): void
    {
        abort_unless(Auth::user()->isSignatory(), 403);
        abort_unless($ws->usesWorkflow() && DocumentRegistry::has($ws->document_type), 404);
        abort_if($ws->workflow_status === TransactionStatus::Draft, 404);
        abort_unless($ws->wasIssuedBy(Auth::user()), 404);
    }

    public function preview(StudentDocumentWorkspace $workspace)
    {
        $this->authorizeIssuer($workspace);

        return response(app(DocumentPdf::class)->previewHtml($workspace->load('user')))
            ->header('Content-Type', 'text/html; charset=UTF-8')
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-Robots-Tag', 'noindex');
    }

    public function download(StudentDocumentWorkspace $workspace)
    {
        $this->authorizeIssuer($workspace);
        abort_unless($workspace->workflow_status === TransactionStatus::Completed, 403);

        $path = $workspace->generated_pdf_path;
        if (! $path || ! Storage::disk(DocumentPdf::DISK)->exists($path)) {
            $path = app(DocumentPdf::class)->generateFor($workspace);
        }

        app(TransactionWorkflow::class)->recordDownload($workspace, Auth::user());

        return Storage::disk(DocumentPdf::DISK)->download($path, $workspace->transaction_code . '.pdf');
    }
}