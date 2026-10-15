<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\Seal;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class WorkflowReportController extends Controller
{
    public function download(StudentDocumentWorkspace $workspace)
    {
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isVpsd(), 403);
        abort_unless($workspace->usesWorkflow() && DocumentRegistry::has($workspace->document_type), 404);
        abort_if($workspace->workflow_status === TransactionStatus::Draft, 404);

        $ws = $workspace->load('user');
        $document = DocumentRegistry::get($ws->document_type);
        $values = $ws->field_values ?? [];

        $answers = [];
        foreach ($document->allFields() as $name => $f) {
            $answers[$f['label']] = $document->formatValue($f, $values[$name] ?? null) ?: '-';
        }

        $approvals = $ws->approvals()->with(['role', 'actor'])->get();

        $audit = $ws->auditLogs()->with('actor')->get()->map(fn ($log) => [
            'at' => $log->created_at,
            'text' => \App\Workflow\AuditLabels::label($log->action),
            'who' => $log->actor
                ? trim($log->actor->first_name . ' ' . $log->actor->last_name)
                : ($log->meta['signer'] ?? 'System'),
            'role' => $log->actor_role ?? ($log->meta['role'] ?? null),
            'note' => $log->meta['message'] ?? $log->meta['reason'] ?? null,
        ]);

        $turnaround = $ws->submitted_at && $ws->issued_at
            ? $ws->submitted_at->diffForHumans($ws->issued_at, true)
            : null;

        $pdf = Pdf::loadView('pdf.workflow-report', [
            'ws' => $ws,
            'document' => $document,
            'answers' => $answers,
            'approvals' => $approvals,
            'audit' => $audit,
            'seal' => $ws->sealed_at ? Seal::verify($ws) : null,
            'turnaround' => $turnaround,
            'generatedBy' => trim(Auth::user()->first_name . ' ' . Auth::user()->last_name),
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        // Audit the export AFTER the trail was read, so the report doesn't list itself.
        app(TransactionWorkflow::class)->recordReportGenerated($ws, Auth::user());

        return $pdf->download($ws->transaction_code . '-report.pdf');
    }
}