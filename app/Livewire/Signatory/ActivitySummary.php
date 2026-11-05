<?php

namespace App\Livewire\Signatory;

use App\Enums\ApprovalStatus;
use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Support\DashboardInsights;
use App\Workflow\Documents\DocumentRegistry;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * Activity summary for dedicated signing accounts, the counterpart of the student's: how much they signed,
 * rejected and issued over a period, how fast they answer, and where the work came from.
 * The figures that used to crowd the signing inbox live here now.
 */
class ActivitySummary extends Component
{
    public int $range = 30;

    /** Show the AI summary card. Kept for the session; while hidden, the AI is not asked at all. */
    #[Session]
    public bool $showAiSummary = true;

    public function mount()
    {
        $user = Auth::user();
        if (! $user->isSignatoryAccount()) {
            return $this->redirectRoute($user->isAdmin() ? 'admin.dashboard' : 'student.home', navigate: true);
        }
    }

    public function render()
    {
        $user = Auth::user();
        $days = $this->range = DashboardInsights::range($this->range);
        $since = now()->subDays($days)->startOfDay();
        $decided = [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value];

        $mine = fn () => TransactionApproval::where('acted_by', $user->id);
        $inPeriod = fn () => $mine()->whereIn('status', $decided)->where('acted_at', '>=', $since);

        $pending = TransactionApproval::assignableTo($user)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', '!=', $user->id));

        $avg = $inPeriod()->whereNotNull('opened_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, opened_at, acted_at)) AS m')->value('m');

        $issued = StudentDocumentWorkspace::usingWorkflow()->whereNotNull('document_type')
            ->where('workflow_status', '!=', TransactionStatus::Draft->value)
            ->issuedBy($user);

        $figures = [
            'waiting' => (clone $pending)->count(),
            'oldest' => (clone $pending)->orderBy('opened_at')->first()?->opened_at,
            'signed' => $inPeriod()->where('status', ApprovalStatus::Approved->value)->count(),
            'rejected' => $inPeriod()->where('status', ApprovalStatus::Rejected->value)->count(),
            'avg' => $avg !== null ? CarbonInterval::minutes((int) $avg)->cascade()->forHumans(['parts' => 2, 'short' => true]) : null,
            'issued' => (clone $issued)->where('created_at', '>=', $since)->count(),
            'issuedOpen' => (clone $issued)->whereIn('workflow_status', ['submitted', 'under_review', 'action_required', 'approved'])->count(),
            'allTime' => $mine()->where('status', ApprovalStatus::Approved->value)->count(),
        ];

        // Decisions per day (7 days) or per week (30 and 90 days), oldest first.
        $acts = $inPeriod()->get(['status', 'acted_at']);
        $step = $days <= 7 ? 1 : 7;
        $buckets = [];
        for ($start = $since->copy(); $start->lt(now()); $start->addDays($step)) {
            $end = $start->copy()->addDays($step);
            $slice = $acts->filter(fn ($a) => $a->acted_at >= $start && $a->acted_at < $end);
            $buckets[] = [
                'label' => $step === 1 ? $start->format('D') : $start->format('M j'),
                'name' => $step === 1 ? $start->format('l, M j') : 'Week of ' . $start->format('M j'),
                'signed' => $slice->filter(fn ($a) => $a->status === ApprovalStatus::Approved)->count(),
                'rejected' => $slice->filter(fn ($a) => $a->status === ApprovalStatus::Rejected)->count(),
            ];
        }

        $byRole = $inPeriod()->where('status', ApprovalStatus::Approved->value)->with('role')->get()
            ->groupBy(fn ($a) => $a->role->label ?? 'Account signer')
            ->map->count()->sortDesc();

        $byDocument = $inPeriod()->with('workspace')->get()
            ->groupBy(fn ($a) => DocumentRegistry::find($a->workspace?->document_type)?->name() ?? 'Other documents')
            ->map->count()->sortDesc()->take(6);

        $recent = $mine()->whereIn('status', $decided)->with(['workspace', 'role'])
            ->orderByDesc('acted_at')->limit(6)->get();

        // What the AI summary may use: only figures already on this page (see ActivitySummaryService).
        $aiFacts = [
            'audience' => 'signatory',
            'period_days' => $days,
            'waiting_for_signature_now' => $figures['waiting'],
            'oldest_waiting_since' => $figures['oldest']?->diffForHumans(),
            'signed_in_period' => $figures['signed'],
            'rejected_in_period' => $figures['rejected'],
            'average_response_time' => $figures['avg'],
            'reports_issued_in_period' => $figures['issued'],
            'issued_reports_still_in_progress' => $figures['issuedOpen'],
            'signed_by_role' => $byRole->all(),
            'decisions_by_document' => $byDocument->all(),
            'latest_decisions' => $recent->map(fn ($a) => [
                'document' => DocumentRegistry::find($a->workspace?->document_type)?->name(),
                'result' => $a->status->value === 'approved' ? 'signed' : 'rejected',
                'as_role' => $a->role->label ?? null,
                'on' => $a->acted_at?->toDateString(),
            ])->all(),
        ];

        return view('livewire.signatory.activity-summary', compact('figures', 'buckets', 'byRole', 'byDocument', 'recent', 'days', 'aiFacts'))
            ->layout('layouts.app', ['title' => 'Activity Summary']);
    }
}
