<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\Seal;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class WorkflowReportController extends Controller
{
    public function download(StudentDocumentWorkspace $workspace)
    {
        $data = $this->reportData($workspace);

        $pdf = Pdf::loadView('pdf.workflow-report', $data)->setPaper('a4', 'portrait');

        // Audit the export AFTER the trail was read, so the report doesn't list itself.
        app(TransactionWorkflow::class)->recordReportGenerated($data['ws'], Auth::user());

        return $pdf->download($data['ws']->transaction_code . '-report.pdf');
    }

    /**
     * The same report as HTML, for the on-screen preview. Shown as A4 sheets and sized by its host
     * frame (it posts its height, like the document preview). Not an export, so it is not audited.
     */
    public function preview(StudentDocumentWorkspace $workspace)
    {
        $html = view('pdf.workflow-report', $this->reportData($workspace))->render();

        $sheet = <<<'HTML'
    <style>
        @media screen {
            html { background: #e7e5e1; }
            body { box-sizing: border-box; width: 595pt; min-height: 842pt; margin: 12pt auto; padding: 36pt 44pt; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.15); }
        }
    </style>
    <script>
        (function () {
            function post() { parent.postMessage({documatePreviewHeight: document.documentElement.scrollHeight}, '*'); }
            window.addEventListener('load', post);
            new ResizeObserver(post).observe(document.documentElement);
        })();
    </script>
HTML;

        return response(str_ireplace('</head>', $sheet . '</head>', $html))
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('Cache-Control', 'no-store');
    }

    private function reportData(StudentDocumentWorkspace $workspace): array
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isVpsd(), 403);
        abort_unless($workspace->usesWorkflow() && DocumentRegistry::has($workspace->document_type), 404);
        abort_if($workspace->workflow_status === TransactionStatus::Draft, 404);

        $ws = $workspace->load('user');
        $document = DocumentRegistry::get($ws->document_type);
        $values = $ws->printedValues();

        $answers = [];
        foreach ($document->allFields() as $name => $f) {
            $answers[$f['label']] = $document->formatValue($f, $values[$name] ?? null) ?: '-';
        }

        $approvals = $ws->approvals()->with(['role', 'actor'])->get();

        $audit = $ws->auditLogs()->with('actor')->get()->map(fn ($log) => [
            'at' => $log->created_at,
            'text' => \App\Workflow\AuditLabels::label($log->action),
            'who' => $log->actor
                ? $log->actor->full_name
                : ($log->meta['signer'] ?? 'System'),
            'role' => $log->actor_role ?? ($log->meta['role'] ?? null),
            'note' => $log->meta['message'] ?? $log->meta['reason'] ?? null,
        ]);

        $turnaround = $ws->submitted_at && $ws->issued_at
            ? $ws->submitted_at->diffForHumans($ws->issued_at, true)
            : null;

        return [
            'ws' => $ws,
            'document' => $document,
            'answers' => $answers,
            'approvals' => $approvals,
            'audit' => $audit,
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
            'turnaround' => $turnaround,
            'generatedBy' => Auth::user()->full_name,
            'generatedAt' => now(),
        ];
    }
}
