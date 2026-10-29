<?php

namespace App\Livewire\Admin;

use App\Enums\ApprovalStatus;
use App\Models\Appointment;
use App\Models\ClearanceStatus;
use App\Models\OfficeAvailability;
use App\Models\Semester;
use App\Models\SignatoryRole;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Support\DashboardInsights;
use App\Support\ReviewQueue;
use App\Support\WaitingTime;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * The office's overview on one page: what needs action now, then a section each for
 * transactions, signatories, appointments, clearance records and documents.
 */
class Dashboard extends Component
{
    private const STUDENT_ROLES = ['Student', 'Officer'];

    /** Fewest finished items before a "typical time" is shown; below it the figure reads "–". */
    private const MIN_SAMPLE = 3;

    /** Days the figures cover. Fixed: the admin dashboard has no period picker. */
    public int $range = 30;

    /** The appointment calendar's month (Y-m) and picked day (Y-m-d); empty means the current one. */
    public string $calMonth = '';

    public string $calDay = '';

    public function shiftMonth(int $by): void
    {
        $this->calMonth = $this->calendarMonth()->addMonthsNoOverflow($by)->format('Y-m');
    }

    public function pickDay(string $date): void
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->calDay = $date;
        }
    }

    public function backToToday(): void
    {
        $this->reset('calMonth', 'calDay');
    }

    private function calendarMonth(): Carbon
    {
        try {
            return $this->calMonth ? Carbon::createFromFormat('!Y-m', $this->calMonth)->startOfMonth() : today()->startOfMonth();
        } catch (\Throwable) {
            return today()->startOfMonth();
        }
    }

    private function students(): Builder
    {
        return User::query()
            ->where('account_status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', self::STUDENT_ROLES));
    }

    /**
     * Counts that each lead to a page where someone has to act. They do not overlap: the 'attention'
     * queue also holds the fully signed (approved) ones, which are counted under ready instead.
     */
    private function workload(): array
    {
        $ready = ReviewQueue::base()->where('workflow_status', 'approved')->count();

        return [
            'to_review' => ReviewQueue::query('attention')->where('workflow_status', '!=', 'approved')->count(),
            'overdue' => ReviewQueue::apply(ReviewQueue::overdue(), 'attention')->where('workflow_status', '!=', 'approved')->count(),
            'ready' => $ready,
            'signing' => ReviewQueue::query('signing')->count(),
            'signing_late' => ReviewQueue::apply(ReviewQueue::overdue(), 'signing')->count(),
            'appointment_requests' => Appointment::where('status', 'pending')->count(),
        ];
    }

    /**
     * A month of appointments as calendar weeks (Sunday first), with the picked day's bookings
     * and today's live queue.
     */
    private function calendar(): array
    {
        $month = $this->calendarMonth();
        $start = $month->copy()->startOfWeek(Carbon::SUNDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

        $booked = Appointment::query()
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->whereNotIn('status', ['retracted', 'rejected'])
            ->get(['appointment_date', 'status'])
            ->groupBy(fn ($a) => $a->appointment_date->toDateString());

        $picked = preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->calDay) ? $this->calDay : today()->toDateString();

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->toDateString();
            $day = $booked->get($key, collect());
            $days[] = [
                'date' => $key,
                'day' => $d->day,
                'in' => $d->month === $month->month,
                'today' => $d->isToday(),
                'picked' => $key === $picked,
                'closed' => $d->isWeekend(),
                'total' => $day->count(),
                'pending' => $day->where('status', 'pending')->count(),
            ];
        }

        $list = Appointment::query()
            ->whereDate('appointment_date', $picked)
            ->whereNotIn('status', ['retracted', 'rejected'])
            ->with('user')
            ->orderByRaw("CASE WHEN session = 'morning' THEN 0 ELSE 1 END")
            ->orderBy('queue_number')
            ->get()
            ->map(fn (Appointment $a) => [
                'name' => ($a->user?->full_name ?? '') ?: 'Unknown student',
                'session' => ucfirst((string) $a->session),
                'queue' => $a->queue_number,
                'status' => $a->status,
                'url' => route('admin.appointments.show', $a->appointment_id),
            ]);

        $today = Appointment::query()
            ->whereDate('appointment_date', today())
            ->whereNotIn('status', ['retracted', 'rejected'])
            ->get(['session', 'status']);

        return [
            'month' => $month->format('F Y'),
            'isCurrent' => $month->isSameMonth(today()) && $picked === today()->toDateString(),
            'days' => $days,
            'monthTotal' => collect($days)->where('in', true)->sum('total'),
            'picked' => Carbon::parse($picked),
            'list' => $list->take(5)->all(),
            'listMore' => max(0, $list->count() - 5),
            'listTotal' => $list->count(),
            'sessions' => collect(['morning', 'afternoon'])->mapWithKeys(fn ($s) => [$s => [
                'total' => $today->where('session', $s)->count(),
                'attended' => $today->where('session', $s)->where('status', 'attended')->count(),
                'now_serving' => OfficeAvailability::nowServingFor(today()->toDateString(), $s),
            ]])->all(),
        ];
    }

    private function clearance(?Semester $semester): array
    {
        $total = $this->students()->count();

        if (! $semester) {
            return ['total' => $total, 'Cleared' => 0, 'Pending' => 0, 'Uncleared' => 0, 'not_tagged' => $total,
                'tagged_in_range' => 0, 'organizations' => [], 'recent' => []];
        }

        // A student can have several records for a semester: their latest one counts.
        // Only active students, the same group as the total.
        $latest = ClearanceStatus::query()
            ->where('semester_id', $semester->id)
            ->whereIn('user_id', $this->students()->select('id'))
            ->orderBy('id')
            ->get(['user_id', 'status', 'organization'])
            ->keyBy('user_id');

        $counts = $latest->countBy('status');

        // Named the way Clearance Monitoring names them: the student's organization, else the record's.
        $orgOf = User::query()->whereIn('id', $latest->keys())->with('organization:id,name')->get(['id', 'organization_id'])
            ->mapWithKeys(fn (User $u) => [$u->id => $u->organization?->name]);
        $organizations = $latest
            ->groupBy(fn ($r) => ($orgOf[$r->user_id] ?? null) ?: ($r->organization ?: 'Unassigned'))
            ->map(fn ($rows, $name) => [
                'label' => $name,
                'Cleared' => $rows->where('status', 'Cleared')->count(),
                'Pending' => $rows->where('status', 'Pending')->count(),
                'Uncleared' => $rows->where('status', 'Uncleared')->count(),
                'total' => $rows->count(),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values()
            ->all();

        $recent = ClearanceStatus::query()
            ->where('semester_id', $semester->id)
            ->with(['user', 'taggedByUser'])
            ->latest('updated_at')
            ->take(5)
            ->get()
            ->map(fn (ClearanceStatus $r) => [
                'name' => ($r->user?->full_name ?? '') ?: 'Unknown student',
                'avatar' => $r->user?->avatarUrl(),
                'status' => $r->status,
                'organization' => $r->organization,
                'by' => $r->taggedByUser?->first_name,
                'when' => ($r->tagged_at ?? $r->updated_at)?->diffForHumans(short: true),
                'url' => $r->user_id ? route('admin.clearance-monitoring.show', $r->user_id) : null,
            ])
            ->all();

        return [
            'total' => $total,
            'Cleared' => $counts['Cleared'] ?? 0,
            'Pending' => $counts['Pending'] ?? 0,
            'Uncleared' => $counts['Uncleared'] ?? 0,
            'not_tagged' => max(0, $total - $latest->count()),
            'tagged_in_range' => ClearanceStatus::query()
                ->where('semester_id', $semester->id)
                ->where('updated_at', '>=', today()->subDays(DashboardInsights::range($this->range) - 1))
                ->count(),
            'organizations' => $organizations,
            'recent' => $recent,
        ];
    }

    /** Documents that reached the office (drafts never did). */
    private function submittedDocuments(): Builder
    {
        return StudentDocumentWorkspace::query()->where(fn ($q) => $q
            ->whereNull('student_document_workspaces.workflow_status')
            ->orWhere('student_document_workspaces.workflow_status', '!=', 'draft'));
    }

    /**
     * Where transactions stand: the period's totals against the period before, how long completed
     * ones took, and every open one by who has it now (the stages do not overlap).
     */
    private function transactions(array $work): array
    {
        $days = $this->range = DashboardInsights::range($this->range);
        $since = today()->subDays($days - 1);

        $submitted = DashboardInsights::metric('submitted', 'Submitted', $this->submittedDocuments(), DashboardInsights::SUBMITTED_AT, $days);
        $completed = DashboardInsights::metric('completed', 'Completed', StudentDocumentWorkspace::query(), 'student_document_workspaces.completed_at', $days);

        $closed = StudentDocumentWorkspace::query()
            ->whereIn('workflow_status', ['rejected', 'voided'])
            ->where('updated_at', '>=', $since)
            ->count();

        // Hours from reaching the office to completion, for those completed in the period.
        $turnaround = StudentDocumentWorkspace::query()
            ->where('completed_at', '>=', $since)
            ->toBase()
            ->selectRaw(DashboardInsights::SUBMITTED_AT . ' as started_at, completed_at')
            ->get()
            ->map(fn ($r) => Carbon::parse($r->started_at)->diffInMinutes(Carbon::parse($r->completed_at)) / 60)
            ->filter(fn ($h) => $h >= 0);

        // [label, count, colour, what it means]. Workload already keeps the to-review and ready counts apart.
        // Colours follow the page-wide code (cyan = on the office's desk, violet = signatories, amber = student,
        // green = ready) and pass the dataviz palette checks; paper forms are the neutral "other" slice.
        $stages = [
            ['With the office', $work['to_review'], '#0891b2', 'Staff need to review these'],
            ['With signatories', $work['signing'], '#9061f9', 'Waiting for officials to sign'],
            ['With the student', ReviewQueue::query('action_required')->count(), '#d97706', 'Sent back to the student to fix'],
            ['Ready to finish', $work['ready'], '#0e9f6e', 'Fully signed, just needs completing'],
            // Paper forms count as open until missed (two weeks without a visit), as in Transaction Records.
            ['Paper forms', StudentDocumentWorkspace::query()->whereNull('workflow_status')->where('status', '!=', 'completed')
                ->where('created_at', '>=', now()->subDays(14))->count(), '#64748b', 'Printed forms awaiting a visit or upload'],
        ];

        $finished = $completed['total'] + $closed;

        return [
            'submitted' => $submitted,
            'completed' => $completed,
            'chart' => [
                'labels' => array_column($submitted['series'], 'd'),
                // Cyan = coming in, blue = finished: the same meaning as everywhere else on the page.
                'series' => [
                    ['Submitted', '#0891b2', array_column($submitted['series'], 'v')],
                    ['Completed', '#2A57B4', array_column($completed['series'], 'v')],
                ],
            ],
            'closed' => $closed,
            'rate' => $finished ? (int) round($completed['total'] / $finished * 100) : null,
            // In hours; left out until there are enough completions for a typical value to mean anything.
            'turnaround' => $turnaround->count() >= self::MIN_SAMPLE ? $turnaround->median() : null,
            'stages' => $stages,
            'open' => array_sum(array_column($stages, 1)),
        ];
    }

    /**
     * The officials who sign transactions: what is waiting on them now, what they signed in the
     * period, how quickly, and which steps have waited the longest.
     */
    private function signatories(): array
    {
        $days = DashboardInsights::range($this->range);
        $since = today()->subDays($days - 1);

        $signed = DashboardInsights::metric('signed', 'Signed',
            TransactionApproval::query()->where('status', ApprovalStatus::Approved->value), 'transaction_approvals.acted_at', $days);

        $acted = TransactionApproval::query()
            ->where('status', ApprovalStatus::Approved->value)
            ->where('acted_at', '>=', $since)
            ->get(['id', 'signatory_role_id', 'signer_type', 'opened_at', 'acted_at']);

        // Open steps on live transactions (whereHas drops soft-deleted ones), oldest first.
        $pending = TransactionApproval::query()
            ->where('status', ApprovalStatus::Pending->value)
            ->whereHas('workspace')
            ->with(['role', 'workspace.user', 'workspace.template'])
            ->orderBy('opened_at')
            ->get();

        $hours = fn ($rows) => $rows
            ->filter(fn ($a) => $a->opened_at && $a->acted_at)
            ->map(fn ($a) => $a->opened_at->diffInMinutes($a->acted_at) / 60)
            ->filter(fn ($h) => $h >= 0);
        $median = fn ($rows) => ($h = $hours($rows))->count() >= self::MIN_SAMPLE ? $h->median() : null;

        $name = fn (?User $u) => $u ? ($u->full_name ?: $u->email) : null;

        // Roles with activity, each with the accounts that currently hold it.
        $roles = SignatoryRole::query()
            ->whereIn('id', $pending->pluck('signatory_role_id')->merge($acted->pluck('signatory_role_id'))->unique())
            ->with(['assignments' => fn ($q) => $q->current()->with('user')])
            ->get(['id', 'label']);

        $byRole = $roles
            ->map(function (SignatoryRole $role) use ($pending, $acted, $median, $name) {
                $waiting = $pending->where('signatory_role_id', $role->id);

                return [
                    'label' => $role->label,
                    'holders' => $role->assignments->pluck('user')->filter()->unique('id')
                        ->map(fn (User $u) => ['name' => $name($u), 'avatar' => $u->avatarUrl()])->values()->all(),
                    'waiting' => $waiting->count(),
                    'signed' => $acted->where('signatory_role_id', $role->id)->count(),
                    'median' => $median($acted->where('signatory_role_id', $role->id)),
                    'oldest' => $waiting->first()?->opened_at?->diffInDays(now()),
                ];
            })
            ->sortBy(fn ($r) => [-$r['waiting'], -$r['signed']])
            ->take(6)
            ->values()
            ->all();

        $longest = $pending->take(5)->map(function (TransactionApproval $a) use ($name) {
            $ws = $a->workspace;

            // Who has to sign: a named account, an emailed signer (shown with the account that uses
            // that email, when there is one), or whoever holds the role for this scope.
            $accounts = match (true) {
                $a->signer_type === 'external' => $a->external_email
                    ? User::query()->whereRaw('LOWER(email) = ?', [Str::lower($a->external_email)])->limit(1)->get()
                    : collect(),
                (bool) $a->signer_user_id => collect([User::find($a->signer_user_id)])->filter(),
                default => $a->role?->holdersFor($a->scope_type, $a->scope_id)->get() ?? collect(),
            };
            $signer = $a->signer_type === 'external'
                ? ($a->external_name ?: $a->external_email)
                : $accounts->map($name)->implode(', ');

            return [
                'role' => $a->role?->label ?? $a->label,
                'signer' => $signer ?: 'Nobody assigned',
                'people' => $accounts->take(3)->map(fn (User $u) => ['name' => $name($u), 'avatar' => $u->avatarUrl()])->values()->all(),
                'opened' => $a->opened_at?->format('M j'),
                'document' => DocumentRegistry::find($ws?->document_type)?->name() ?? ($ws?->template?->name ?: 'Transaction'),
                'student' => ($ws?->user?->full_name ?? '') ?: 'Unknown student',
                'external' => $a->signer_type === 'external',
                // School days, the same unit and thresholds as the waiting bands and Workflow Review.
                'days' => $a->opened_at ? WaitingTime::schoolDays($a->opened_at) : null,
                'since' => $a->opened_at?->diffForHumans(),
                'url' => route('admin.workflow.show', $a->workspace_id),
            ];
        })->all();

        return [
            'signed' => $signed,
            'pending' => $pending->count(),
            'pending_external' => $pending->where('signer_type', 'external')->count(),
            'returned' => TransactionApproval::query()->where('status', ApprovalStatus::Rejected->value)->where('acted_at', '>=', $since)->count(),
            'median' => $median($acted),
            'byRole' => $byRole,
            'longest' => $longest,
            'unassigned' => SignatoryRole::query()->active()->whereDoesntHave('assignments', fn ($q) => $q->current())->count(),
        ];
    }

    private function insights(): array
    {
        $days = $this->range = DashboardInsights::range($this->range);
        $inRange = $this->submittedDocuments()->whereRaw(DashboardInsights::SUBMITTED_AT . ' >= ?', [today()->subDays($days - 1)]);

        return [
            'byType' => DashboardInsights::byType($inRange),
            'origins' => DashboardInsights::origins($inRange),
        ];
    }

    public function render()
    {
        $semester = Semester::current();
        $work = $this->workload();

        return view('livewire.admin.dashboard', [
            'firstName' => auth()->user()?->first_name ?: 'there',
            'semesterLabel' => $semester?->label(),
            'work' => $work,
            'transactions' => $this->transactions($work),
            'signatories' => $this->signatories(),
            'calendar' => $this->calendar(),
            'clearance' => $this->clearance($semester),
            'insights' => $this->insights(),
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
