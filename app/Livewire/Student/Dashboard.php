<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;
use App\Models\Appointment;
use App\Models\OfficeAvailability;
use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Support\DashboardInsights;
use App\Support\DocumentProgress;
use App\Workflow\Documents\DocumentRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Session;
use Livewire\Component;

/**
 * The student's Activity summary: analytics of their own account (headline figures, a trend,
 * where their documents stand, turnaround, recent activity) plus what needs them now.
 * Home (the greeting and form search) is the first page; this one sits below Clearance Status.
 */
class Dashboard extends Component
{
    use BuildsClearanceStatusViewData;

    private const FINISHED = ['completed', 'rejected', 'voided'];

    /** Days the insight charts cover: 7, 30 or 90. */
    public int $range = 30;

    /** Show the AI summary card. Kept for the session; while hidden, the AI is not asked at all. */
    #[Session]
    public bool $showAiSummary = true;

    /** Profile details most forms print, checked so the student is not blocked at submit time. */
    private const PROFILE_FIELDS = [
        'contact_number' => 'contact number',
        'address' => 'address',
        'program_id' => 'program',
        'year_level' => 'year level',
        'section' => 'section',
    ];

    private function documents(int $userId): Builder
    {
        return StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->with(['template', 'approvals.role'])
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ]);
    }

    /** Documents still moving, the ones needing the student first. */
    private function activeDocuments(int $userId): Collection
    {
        return $this->documents($userId)
            ->where(fn (Builder $w) => $w
                ->where(fn (Builder $x) => $x->whereNotNull('workflow_status')->whereNotIn('workflow_status', self::FINISHED))
                ->orWhere(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', '!=', 'completed')))
            ->orderByRaw("CASE workflow_status WHEN 'action_required' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->get();
    }

    private function nextAppointment(int $userId): ?array
    {
        $appointment = Appointment::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->orderBy('session')
            ->first();

        if (! $appointment) {
            return null;
        }

        $date = Carbon::parse($appointment->appointment_date);

        return [
            'date' => $date,
            'is_today' => $date->isToday(),
            'session' => ucfirst((string) $appointment->session),
            'status' => $appointment->status,
            'queue_number' => $appointment->queue_number,
            'now_serving' => $date->isToday() && $appointment->session
                ? OfficeAvailability::nowServingFor($date->toDateString(), $appointment->session)
                : null,
            'purpose' => $appointment->purpose,
        ];
    }

    /**
     * Things only the student can move forward, most urgent first.
     *
     * @return array<int, array{tone: string, title: string, text: string, url: string, action: string}>
     */
    private function attention(User $user, Collection $active, ?array $appointment, array $clearance, Collection $missingProfile): array
    {
        $items = [];

        foreach ($active as $ws) {
            $p = DocumentProgress::describe($ws);
            $status = $ws->workflow_status?->value;

            if ($status === 'action_required') {
                $items[] = ['tone' => 'amber', 'title' => 'Changes needed: ' . $p['title'], 'text' => $p['next'], 'url' => $p['href'], 'action' => 'Update'];
            } elseif ($status === 'draft') {
                $items[] = ['tone' => 'amber', 'title' => 'Not sent yet: ' . $p['title'], 'text' => 'Finish the form and submit it to the VPSD office.', 'url' => $p['href'], 'action' => 'Continue'];
            }
        }

        foreach (StudentDocumentWorkspace::officeNoticesFor($user->id) as $notice) {
            $doc = DocumentRegistry::get($notice->document_type);
            $values = $notice->field_values ?? [];
            $items[] = [
                'tone' => $doc->noticeTone() === 'rose' ? 'rose' : 'blue',
                'title' => $doc->noticeTitle($values),
                'text' => $doc->noticeLine($values),
                'url' => route('student.transactions.show', $notice->workspace_id),
                'action' => 'Open',
            ];
        }

        if ($appointment && $appointment['is_today'] && $appointment['status'] === 'approved') {
            $text = $appointment['session'] . ' session'
                . ($appointment['queue_number'] ? ' · your number is ' . $appointment['queue_number'] : '')
                . ($appointment['now_serving'] ? ' · now serving ' . $appointment['now_serving'] : '');
            $items[] = ['tone' => 'blue', 'title' => 'Your appointment is today', 'text' => $text, 'url' => route('student.appointments.index'), 'action' => 'View'];
        }

        if (($clearance['status'] ?? '') === 'Uncleared') {
            $items[] = [
                'tone' => 'rose',
                'title' => 'You are not yet cleared for ' . ($clearance['period_label'] ?? 'this semester'),
                'text' => $clearance['remarks'] ?: 'See which office has a hold on your clearance.',
                'url' => route('student.clearance-status'),
                'action' => 'Details',
            ];
        }

        if ($missingProfile->isNotEmpty()) {
            $items[] = ['tone' => 'slate', 'title' => 'Complete your profile', 'text' => 'Missing ' . $missingProfile->implode(', ') . '. Forms print these details.', 'url' => route('profile'), 'action' => 'Update'];
        }

        if (! $user->signature_path) {
            $items[] = ['tone' => 'slate', 'title' => 'Add your e-signature', 'text' => 'Several forms are signed with it. Save it once in your profile.', 'url' => route('profile'), 'action' => 'Add'];
        }

        return $items;
    }

    /** Documents that reached the office (drafts never did). */
    private function submitted(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereNull('student_document_workspaces.workflow_status')
            ->orWhere('student_document_workspaces.workflow_status', '!=', 'draft'));
    }

    private function insights(int $userId): array
    {
        $days = $this->range = DashboardInsights::range($this->range);
        $mine = fn () => StudentDocumentWorkspace::query()->where('student_document_workspaces.user_id', $userId);

        return [
            'trend' => [
                DashboardInsights::metric('started', 'Started', $mine(), 'student_document_workspaces.created_at', $days),
                DashboardInsights::metric('submitted', 'Submitted', $this->submitted($mine()), DashboardInsights::SUBMITTED_AT, $days),
                DashboardInsights::metric('completed', 'Completed', $mine(), 'student_document_workspaces.completed_at', $days),
                DashboardInsights::metric('appointments', 'Appointments', Appointment::where('appointments.user_id', $userId), 'appointments.created_at', $days),
            ],
            'byType' => DashboardInsights::byType($mine()),
        ];
    }

    /** Headline figures for the period: in progress now, completed, average turnaround, approval rate. */
    private function figures(int $userId, int $activeCount): array
    {
        $since = now()->subDays($this->range)->startOfDay();
        $finishedAt = 'COALESCE(issued_at, completed_at, updated_at)';

        $completed = StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->where('workflow_status', 'completed')
            ->whereRaw("{$finishedAt} >= ?", [$since])
            ->get(['submitted_at', 'issued_at', 'completed_at', 'updated_at']);

        $days = $completed
            ->filter(fn ($w) => $w->submitted_at)
            ->map(fn ($w) => $w->submitted_at->diffInHours($w->issued_at ?? $w->completed_at ?? $w->updated_at) / 24);

        $closed = StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->whereIn('workflow_status', ['completed', 'rejected'])
            ->selectRaw('workflow_status, COUNT(*) as n')
            ->groupBy('workflow_status')
            ->pluck('n', 'workflow_status');
        $decided = (int) $closed->sum();

        return [
            'active' => $activeCount,
            'completed' => $completed->count(),
            'turnaround' => $days->isNotEmpty() ? round($days->avg(), 1) : null,
            'approval' => $decided ? (int) round(100 * (int) ($closed['completed'] ?? 0) / $decided) : null,
            'decided' => $decided,
        ];
    }

    /** Where every document stands right now, for the status bars. */
    private function statusRows(int $userId): array
    {
        $labels = [
            'draft' => 'Drafts',
            'submitted' => 'With the VPSD office',
            'under_review' => 'With the VPSD office',
            'action_required' => 'Changes requested',
            'approved' => 'Signed, awaiting completion',
            'completed' => 'Completed',
            'rejected' => 'Rejected',
            'voided' => 'Voided',
        ];

        $counts = StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->selectRaw('workflow_status, COUNT(*) as n')
            ->groupBy('workflow_status')
            ->pluck('n', 'workflow_status');

        $rows = [];
        foreach ($counts as $status => $n) {
            $label = $labels[$status] ?? 'Paper forms';
            $rows[$label] = ($rows[$label] ?? 0) + (int) $n;
        }

        // Fixed order, so the bars read like the life of a document.
        $order = array_values(array_unique(array_merge(array_values($labels), ['Paper forms'])));

        return collect($order)
            ->filter(fn ($label) => ($rows[$label] ?? 0) > 0)
            ->map(fn ($label) => ['label' => $label, 'n' => $rows[$label]])
            ->values()->all();
    }

    /** The latest steps across all the student's documents, in the words their transaction page uses. */
    private function recentActivity(int $userId): array
    {
        $labels = \App\Livewire\Student\Transactions\Form::TIMELINE;
        $workspaces = StudentDocumentWorkspace::query()->where('user_id', $userId)->with('template')
            ->get(['workspace_id', 'document_type', 'template_id'])->keyBy('workspace_id');

        if ($workspaces->isEmpty()) {
            return [];
        }

        return \App\Models\TransactionAuditLog::query()
            ->whereIn('workspace_id', $workspaces->keys())
            ->whereIn('action', array_keys($labels))
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function ($log) use ($labels, $workspaces) {
                $ws = $workspaces->get($log->workspace_id);
                $meta = $log->meta ?? [];
                $text = $labels[$log->action];
                if (! empty($meta['label']) && str_starts_with($log->action, 'approval.')) {
                    $text .= ' — ' . $meta['label'];
                }

                return [
                    'text' => $text,
                    'document' => DocumentRegistry::find($ws?->document_type)?->name()
                        ?? ($ws?->template?->name ? \Illuminate\Support\Str::title(str_replace('_', ' ', $ws->template->name)) : 'Document'),
                    'url' => $ws?->document_type
                        ? route('student.transactions.show', $log->workspace_id)
                        : route('student.documents.show', $log->workspace_id),
                    'at' => $log->created_at,
                    'tone' => match (true) {
                        in_array($log->action, ['approval.rejected', 'review.rejected', 'document.voided'], true) => 'rose',
                        $log->action === 'review.action_requested' => 'amber',
                        in_array($log->action, ['transaction.completed', 'approvals.completed', 'approval.approved'], true) => 'emerald',
                        default => 'slate',
                    },
                ];
            })
            ->all();
    }

    /**
     * What the AI summary is written from: figures, form names and statuses only (no names, numbers or emails).
     * Stable between polls, so the summary is regenerated only when something actually changed.
     */
    private function aiFacts(array $figures, array $insights, array $statusRows, array $attention, Collection $active, ?array $appointment, array $clearance, array $recent): array
    {
        return [
            'period_days' => $this->range,
            'documents_in_progress_now' => $figures['active'],
            'completed_in_period' => $figures['completed'],
            'average_turnaround_days' => $figures['turnaround'],
            'approval_rate_percent_all_time' => $figures['approval'],
            'decided_all_time' => $figures['decided'],
            'trend_in_period' => collect($insights['trend'])->map(fn ($m) => [
                'metric' => $m['label'], 'total' => $m['total'], 'previous_period' => $m['previous'],
            ])->all(),
            'documents_by_status' => collect($statusRows)->pluck('n', 'label')->all(),
            'documents_by_type' => collect($insights['byType'])->map(fn ($r) => array_intersect_key($r, array_flip(['label', 'completed', 'open', 'total'])))->all(),
            'needs_attention' => collect($attention)->pluck('title')->all(),
            'in_progress' => $active->take(5)->map(function ($ws) {
                $p = DocumentProgress::describe($ws);

                return ['form' => $p['title'], 'status' => $p['label'], 'next' => $p['next']];
            })->values()->all(),
            'recent_activity' => collect($recent)->map(fn ($r) => [
                'event' => $r['text'], 'form' => $r['document'], 'date' => $r['at']->toDateString(),
            ])->all(),
            'next_appointment' => $appointment ? [
                'date' => $appointment['date']->toDateString(), 'session' => $appointment['session'], 'status' => $appointment['status'],
            ] : null,
            'clearance' => ($clearance['status_word'] ?? null) ? $clearance['status_word'] . ' (' . ($clearance['period_label'] ?? 'this semester') . ')' : 'not tagged yet',
            'today' => today()->toDateString(),
        ];
    }

    public function render()
    {
        $userId = (int) Auth::id();
        $user = $this->loadStudentWithClearance($userId);
        $user->loadMissing(['program', 'role']);

        $clearance = $this->buildCurrentClearanceStatus($user);
        $active = $this->activeDocuments($userId);
        $appointment = $this->nextAppointment($userId);

        $missing = collect(self::PROFILE_FIELDS)
            ->filter(fn ($label, $field) => blank($user->{$field}))
            ->values();

        $attention = $this->attention($user, $active, $appointment, $clearance, $missing);
        $insights = $this->insights($userId);
        $figures = $this->figures($userId, $active->count());
        $statusRows = $this->statusRows($userId);
        $recent = $this->recentActivity($userId);

        return view('livewire.student.dashboard', [
            'user' => $user,
            'firstName' => $user->first_name ?: $this->fullName($user),
            'facts' => collect([
                $user->student_number,
                $user->program?->name,
                $user->year_level ? $this->formatYearLevel((string) $user->year_level) . ($user->section ? ', Section ' . $user->section : '') : null,
            ])->filter()->values()->all(),
            'attention' => $attention,
            'active' => $active->take(3)->map(fn ($ws) => DocumentProgress::describe($ws) + ['id' => $ws->workspace_id]),
            'activeCount' => $active->count(),
            'appointment' => $appointment,
            'clearance' => $clearance,
            'insights' => $insights,
            'figures' => $figures,
            'statusRows' => $statusRows,
            'recent' => $recent,
            'aiFacts' => $this->aiFacts($figures, $insights, $statusRows, $attention, $active, $appointment, $clearance, $recent),
        ])->layout('layouts.app', ['title' => 'Activity Summary']);
    }
}
