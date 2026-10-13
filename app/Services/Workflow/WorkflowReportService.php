<?php

namespace App\Services\Workflow;

use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Enums\TransactionStatus;
use Carbon\Carbon; use Carbon\CarbonInterval;

/** Read-only aggregates for the admin Workflow Reports page. $f = ['from' => ?, 'to' => ?, 'type' => ?] */
class WorkflowReportService
{
    private const OPEN = ['submitted', 'under_review', 'action_required', 'approved'];

    private function base(array $f)
    {
        return DB::table('student_document_workspaces as w')
            ->whereNotNull('w.workflow_status')
            ->where('w.workflow_status', '!=', 'draft')
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('w.submitted_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('w.submitted_at', '<=', $d))
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('w.document_type', $t));
    }
    /** Months inside the selected range, zero-filled, newest 12 at most. */
    private function monthly(array $f): array
    {
        $to = Carbon::parse($f['to'] ?? now())->startOfMonth();
        $from = ! empty($f['from'])
            ? Carbon::parse($f['from'])->startOfMonth()
            : Carbon::parse($this->base($f)->min('w.submitted_at') ?? now())->startOfMonth();

        $span = ($to->year - $from->year) * 12 + ($to->month - $from->month);
        if ($span < 0) { $from = $to->copy(); $span = 0; }
        if ($span > 11) { $from = $to->copy()->subMonths(11); $span = 11; }

        $rows = $this->base($f)
            ->selectRaw("DATE_FORMAT(w.submitted_at, '%Y-%m') AS ym, COUNT(*) AS submitted, SUM(w.workflow_status = 'completed') AS completed")
            ->groupBy('ym')->get()->keyBy('ym');

        $out = ['labels' => [], 'submitted' => [], 'completed' => []];
        for ($i = 0; $i <= $span; $i++) {
            $m = $from->copy()->addMonths($i);
            $r = $rows->get($m->format('Y-m'));
            $out['labels'][] = $m->format('M Y');
            $out['submitted'][] = (int) ($r->submitted ?? 0);
            $out['completed'][] = (int) ($r->completed ?? 0);
        }

        return $out;
    }

    public function kpis(array $f): object
    {
        $r = $this->base($f)->selectRaw("
            COUNT(*) AS total,
            COALESCE(SUM(w.workflow_status = 'completed'), 0) AS completed,
            COALESCE(SUM(w.workflow_status = 'rejected'), 0)  AS rejected,
            COALESCE(SUM(w.workflow_status = 'voided'), 0)    AS voided,
            COALESCE(SUM(w.workflow_status IN ('submitted','under_review','action_required','approved')), 0) AS in_progress,
            AVG(CASE WHEN w.issued_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, w.submitted_at, w.issued_at) END) AS avg_hours
        ")->first();

        $r->completion_rate = $r->total > 0 ? round($r->completed / $r->total * 100) : 0;

        return $r;
    }

    public function byType(array $f): Collection
    {
        return $this->base($f)->selectRaw("
            w.document_type,
            COUNT(*) AS total,
            SUM(w.workflow_status = 'completed') AS completed,
            SUM(w.workflow_status = 'rejected')  AS rejected,
            SUM(w.workflow_status = 'voided')    AS voided,
            SUM(w.workflow_status IN ('submitted','under_review','action_required','approved')) AS in_progress,
            AVG(CASE WHEN w.issued_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, w.submitted_at, w.issued_at) END) AS avg_hours
        ")->groupBy('w.document_type')->get()
            ->each(fn ($r) => $r->name = DocumentRegistry::find($r->document_type)?->name() ?? $r->document_type);
    }

    public function signers(array $f): Collection
    {
        return DB::table('transaction_approvals as a')
            ->join('student_document_workspaces as w', 'w.workspace_id', '=', 'a.workspace_id')
            ->leftJoin('signatory_roles as r', 'r.id', '=', 'a.signatory_role_id')
            ->whereNotNull('w.workflow_status')
            ->where('w.workflow_status', '!=', 'draft')
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('w.submitted_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('w.submitted_at', '<=', $d))
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('w.document_type', $t))
            ->selectRaw("
                COALESCE(r.label, 'External signer') AS label,
                SUM(a.status = 'approved') AS signed,
                SUM(a.status = 'rejected') AS rejected,
                SUM(a.status = 'pending')  AS pending,
                AVG(CASE WHEN a.status IN ('approved','rejected') AND a.opened_at IS NOT NULL
                    THEN TIMESTAMPDIFF(MINUTE, a.opened_at, a.acted_at) END) AS avg_minutes,
                MIN(CASE WHEN a.status = 'pending' THEN a.opened_at END) AS oldest_pending
            ")
            ->groupByRaw("COALESCE(r.label, 'External signer')")
            ->orderByDesc('signed')
            ->get();
    }
    

    public static function humanMinutes(?float $m): string
    {
        return $m === null
            ? '-'
            : CarbonInterval::minutes((int) round($m))->cascade()->forHumans(['parts' => 2, 'short' => true]);
    }

    /** Same shape as the other report sections: totals, label=>count maps, and ['charts' => [..labels/values..]]. */
    public function build(array $f): array
    {
        $kpi = $this->kpis($f);

        $rows = $this->base($f)->selectRaw('w.workflow_status AS s, COUNT(*) AS c')->groupBy('w.workflow_status')->pluck('c', 's');
        $byStatus = [];
        foreach (['submitted', 'under_review', 'action_required', 'approved', 'completed', 'rejected', 'voided'] as $s) {
            if (($rows[$s] ?? 0) > 0) {
                $byStatus[TransactionStatus::from($s)->label()] = (int) $rows[$s];
            }
        }

        $byType = $this->byType($f)->pluck('total', 'name')->map(fn ($v) => (int) $v)->all();

        $monthly = [];
        $months = $this->base($f)->selectRaw("DATE_FORMAT(w.submitted_at, '%Y-%m') AS ym, COUNT(*) AS c")
            ->groupBy('ym')->orderBy('ym')->pluck('c', 'ym');
        foreach ($months as $ym => $c) {
            $monthly[Carbon::parse($ym . '-01')->format('M Y')] = (int) $c;
        }

        $chart = fn (array $m) => ['labels' => array_keys($m), 'values' => array_values($m)];

        return [
            'total' => (int) $kpi->total,
            'completed' => (int) $kpi->completed,
            'in_progress' => (int) $kpi->in_progress,
            'rejected' => (int) $kpi->rejected,
            'voided' => (int) $kpi->voided,
            'completion_rate' => $kpi->completion_rate,
            'avg_issue' => self::humanMinutes($kpi->avg_hours !== null ? (float) $kpi->avg_hours * 60 : null),
            'by_status' => $byStatus,
            'by_type' => $byType,
            'monthly' => $monthly,
            'charts' => [
                'by_status' => $chart($byStatus),
                'by_type' => $chart($byType),
                'monthly' => $this->monthly($f), 
            ],
            'rejections' => $this->rejections($f),
            'signers' => $this->signers($f)->map(fn ($s) => [
                'label' => $s->label,
                'signed' => (int) $s->signed,
                'rejected' => (int) $s->rejected,
                'pending' => (int) $s->pending,
                'avg_response' => self::humanMinutes($s->avg_minutes !== null ? (float) $s->avg_minutes : null),
                'oldest_pending' => $s->oldest_pending ? Carbon::parse($s->oldest_pending)->diffForHumans() : '-',
            ])->all(),
            'stuck' => $this->stuck($f)->map(fn ($r) => [
                'id' => $r->workspace_id,
                'code' => $r->transaction_code,
                'name' => $r->name,
                'status' => TransactionStatus::from($r->workflow_status)->label(),
                'since' => Carbon::parse($r->submitted_at)->diffForHumans(),
            ])->all(),
        ];
    }

    public function stuck(array $f, int $limit = 10): Collection
    {
        return $this->base($f)
            ->whereIn('w.workflow_status', self::OPEN)
            ->orderBy('w.submitted_at')
            ->limit($limit)
            ->get(['w.workspace_id', 'w.transaction_code', 'w.document_type', 'w.workflow_status', 'w.submitted_at'])
            ->each(fn ($r) => $r->name = DocumentRegistry::find($r->document_type)?->name() ?? $r->document_type);
    }

    /** @return array{review: int, signer: int} */
    public function rejections(array $f): array
    {
        $rows = DB::table('transaction_audit_logs as l')
            ->join('student_document_workspaces as w', 'w.workspace_id', '=', 'l.workspace_id')
            ->whereIn('l.action', ['review.rejected', 'approval.rejected'])
            ->when($f['from'] ?? null, fn ($q, $d) => $q->whereDate('w.submitted_at', '>=', $d))
            ->when($f['to'] ?? null, fn ($q, $d) => $q->whereDate('w.submitted_at', '<=', $d))
            ->when($f['type'] ?? null, fn ($q, $t) => $q->where('w.document_type', $t))
            ->selectRaw('l.action, COUNT(*) AS c')->groupBy('l.action')->pluck('c', 'action');

        return ['review' => (int) ($rows['review.rejected'] ?? 0), 'signer' => (int) ($rows['approval.rejected'] ?? 0)];
    }
}