<?php

namespace App\Livewire\Admin\Appointments;

use App\Jobs\ReviewAppointmentSubmission;
use App\Models\Appointment;
use App\Models\AppointmentReschedule;
use App\Models\OfficeAvailability;
use App\Notifications\AppointmentStatusChanged;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The office's appointments. Opens on the calendar (a week of sessions, or the month), where the office's
 * opening hours are also set. The day list runs the queue in two phases: review the requests (with the AI
 * check), then serve the approved ones and record who came. Every row opens the full record.
 */
class AppointmentsBoard extends Component
{
    use WithPagination;

    public const VIEWS = ['calendar' => 'Calendar', 'list' => 'Day list', 'history' => 'All appointments'];

    public const PHASES = ['review' => 'Review requests', 'queue' => 'Queue and attendance'];

    public const REJECT_REASONS = [
        'Incomplete or unreadable supporting documents submitted.',
        'Duplicate or conflicting appointment request detected.',
        'Ineligible for requested appointment type or service.',
        'The purpose does not match the document attached.',
    ];

    public const RESCHEDULE_REASONS = [
        'Official event conflict or university closure.',
        'Assigned staff unavailable on original date.',
        'System adjustment / Queue capacity reallocation.',
    ];

    #[Url(as: 'view', except: 'calendar')]
    public string $viewMode = 'calendar';

    /** Calendar: 'week' (sessions per day, like a schedule) or 'month'. */
    #[Url(as: 'cal', except: 'week')]
    public string $calendarMode = 'week';

    #[Url(as: 'date')]
    public string $selectedDate = '';

    #[Url(as: 'phase', except: 'review')]
    public string $phase = 'review';

    #[Url(except: '')]
    public string $search = '';

    public string $month = '';

    // History filters
    public string $filterDate = '';
    public string $filterStatus = '';

    /** The appointment whose calendar popover is open (and that one-appointment dialogs act on). */
    public ?int $openId = null;

    // Bulk selection (review phase)
    public array $selectedIds = [];
    public ?array $capacityWarning = null;

    /** 'approve' | 'reject' | 'reschedule' | 'availability' while a dialog is open. */
    public ?string $dialog = null;
    public string $dialogScope = 'selected'; // 'selected' | 'one'

    public string $reasonOption = '';
    public string $reasonText = '';
    public ?string $rescheduleDate = null;
    public ?string $rescheduleSession = null;

    // Office availability (merged in from the old Availability page)
    #[Url(as: 'edit', except: false)]
    public bool $editingHours = false;
    public array $hoursDates = [];
    public bool $morningOpen = true;
    public bool $afternoonOpen = true;
    public int $morningSlots = 25;
    public int $afternoonSlots = 25;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        if (! array_key_exists($this->viewMode, self::VIEWS)) {
            $this->viewMode = 'calendar';
        }
        if (! in_array($this->calendarMode, ['week', 'month'], true)) {
            $this->calendarMode = 'week';
        }
        if (! array_key_exists($this->phase, self::PHASES)) {
            $this->phase = 'review';
        }
        if (! $this->selectedDate || ! strtotime($this->selectedDate)) {
            $this->selectedDate = now()->toDateString();
        }
        $this->month = Carbon::parse($this->selectedDate)->format('Y-m');
    }

    /* ------------------------------ navigation ------------------------------ */

    public function setViewMode(string $mode): void
    {
        if (array_key_exists($mode, self::VIEWS)) {
            $this->viewMode = $mode;
            $this->openId = null;
            $this->clearSelection();
            $this->resetPage();
        }
    }

    public function setCalendarMode(string $mode): void
    {
        if (in_array($mode, ['week', 'month'], true)) {
            $this->calendarMode = $mode;
            $this->openId = null;
            $this->month = Carbon::parse($this->selectedDate)->format('Y-m');
        }
    }

    public function setPhase(string $phase): void
    {
        if (array_key_exists($phase, self::PHASES)) {
            $this->phase = $phase;
            $this->clearSelection();
        }
    }

    /** Previous / next: a day in the list, a week or a month on the calendar. */
    public function step(int $dir): void
    {
        $dir = $dir < 0 ? -1 : 1;
        $this->openId = null;

        if ($this->viewMode === 'calendar' && $this->calendarMode === 'month') {
            $this->month = Carbon::parse($this->month . '-01')->addMonths($dir)->format('Y-m');

            return;
        }

        $this->selectDate(Carbon::parse($this->selectedDate)->addDays($this->viewMode === 'calendar' ? 7 * $dir : $dir)->toDateString());
    }

    public function jumpToday(): void
    {
        $this->openId = null;
        $this->selectDate(now()->toDateString());
    }

    public function selectDate(string $date): void
    {
        if (! strtotime($date)) {
            return;
        }
        $this->selectedDate = Carbon::parse($date)->toDateString();
        $this->month = Carbon::parse($date)->format('Y-m');
        $this->clearSelection();
    }

    public function updatedSelectedDate(string $value): void
    {
        $this->selectDate($value ?: now()->toDateString());
    }

    /** Calendar: open that day's list. */
    public function openDay(string $date): void
    {
        $this->selectDate($date);
        $this->viewMode = 'list';
        $this->openId = null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatedFilterDate(): void
    {
        $this->resetPage();
    }

    public function clearHistoryFilters(): void
    {
        $this->reset(['filterDate', 'filterStatus', 'search']);
        $this->resetPage();
    }

    /* ------------------------------- popover ------------------------------- */

    public function pop(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
    }

    public function closePop(): void
    {
        $this->openId = null;
    }

    #[Computed]
    public function popped(): ?Appointment
    {
        return $this->openId
            ? Appointment::with('user.program', 'workspace.template', 'statusLogs.changedBy')->find($this->openId)
            : null;
    }

    /* ------------------------------- queries ------------------------------- */

    private function applySearch(Builder $q): Builder
    {
        $term = trim($this->search);
        if ($term === '') {
            return $q;
        }
        $s = "%{$term}%";

        return $q->where(fn ($w) => $w
            ->whereHas('user', fn ($u) => $u->where('first_name', 'like', $s)
                ->orWhere('last_name', 'like', $s)
                ->orWhere('student_number', 'like', $s))
            ->orWhere('purpose', 'like', $s));
    }

    /** The selected day's appointments for the current phase. */
    #[Computed]
    public function dayRows(): Collection
    {
        $q = $this->applySearch(Appointment::query()->with('user', 'workspace.template')->whereDate('appointment_date', $this->selectedDate));

        if ($this->phase === 'review') {
            // The AI check's worries first, then first come, first served.
            $q->where('status', 'pending')
                ->orderByRaw("CASE ai_flag WHEN 'flagged' THEN 0 WHEN 'review_failed' THEN 1 WHEN 'pending' THEN 2 ELSE 3 END")
                ->orderByAiRisk()
                ->orderBy('created_at');
        } else {
            $q->whereIn('status', ['approved', 'attended', 'missed'])
                ->orderByRaw("CASE session WHEN 'morning' THEN 0 ELSE 1 END")
                ->orderBy('queue_number');
        }

        return $q->get()->each(fn ($a) => $a->refreshMissedStatusIfLapsed());
    }

    #[Computed]
    public function phaseCounts(): array
    {
        $day = Appointment::whereDate('appointment_date', $this->selectedDate);

        return [
            'review' => (clone $day)->where('status', 'pending')->count(),
            'queue' => (clone $day)->whereIn('status', ['approved', 'attended', 'missed'])->count(),
            'flagged' => (clone $day)->where('status', 'pending')->where('ai_flag', 'flagged')->count(),
            'attended' => (clone $day)->where('status', 'attended')->count(),
        ];
    }

    #[Computed]
    public function queueBoard(): array
    {
        $availability = OfficeAvailability::where('date', $this->selectedDate)->first();

        $build = function (string $session) use ($availability) {
            $list = Appointment::with('user')
                ->whereDate('appointment_date', $this->selectedDate)
                ->where('session', $session)
                ->whereIn('status', ['approved', 'attended', 'missed', 'retracted'])
                ->whereNotNull('queue_number')
                ->orderBy('queue_number')
                ->get();

            $open = OfficeAvailability::sessionOpen($this->selectedDate, $session);
            $slots = $availability?->{$session . '_slots'} ?? 25;
            $confirmed = $list->whereIn('status', ['approved', 'attended'])->count();
            $now = $availability?->{'now_serving_' . $session};

            return [
                'list' => $list,
                'now_serving' => $now,
                'serving' => $now ? $list->firstWhere('queue_number', $now) : null,
                'next' => $list->where('status', 'approved')->first(fn ($a) => ! $now || $a->queue_number > $now),
                'open' => $open,
                'slots' => $open ? $slots : 0,
                'remaining' => $open ? max($slots - $confirmed, 0) : 0,
                'done' => $list->where('status', 'attended')->count(),
                'waiting' => $list->where('status', 'approved')->filter(fn ($a) => ! $now || $a->queue_number > $now)->count(),
            ];
        };

        return ['morning' => $build('morning'), 'afternoon' => $build('afternoon')];
    }

    /** Opening hours and bookings for a range of days, keyed by date. */
    private function days(Carbon $from, Carbon $to): array
    {
        $availabilities = OfficeAvailability::whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()->keyBy(fn ($a) => $a->date->toDateString());

        $byDay = Appointment::with('user', 'workspace.template')
            ->whereBetween('appointment_date', [$from->toDateString(), $to->toDateString()])
            ->whereIn('status', ['pending', 'approved', 'attended', 'missed'])
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 ELSE 0 END")
            ->orderBy('queue_number')->orderBy('created_at')
            ->get()
            ->groupBy(fn ($a) => $a->appointment_date->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $iso = $d->toDateString();
            $a = $availabilities->get($iso);
            $weekend = OfficeAvailability::isWeekend($d);
            $list = $byDay->get($iso, collect());

            $session = function (string $s) use ($a, $weekend, $list) {
                $open = ! $weekend && ($a?->{$s . '_open'} ?? true);
                $slots = $a?->{$s . '_slots'} ?? 25;
                $items = $list->where('session', $s)->values();
                $taken = $items->whereIn('status', ['pending', 'approved'])->count();

                return ['open' => $open, 'slots' => $slots, 'left' => $open ? max($slots - $taken, 0) : 0, 'items' => $items];
            };

            $m = $session('morning');
            $af = $session('afternoon');

            $days[$iso] = [
                'date' => $iso,
                'carbon' => $d->copy(),
                'day' => $d->day,
                'inMonth' => $d->format('Y-m') === $this->month,
                'isToday' => $d->isToday(),
                'isPast' => $d->lt(now()->startOfDay()),
                'isSelected' => $iso === $this->selectedDate,
                'weekend' => $weekend,
                'custom' => (bool) $a,
                'closed' => ! $m['open'] && ! $af['open'],
                'morning' => $m,
                'afternoon' => $af,
                'count' => $list->count(),
                'pending' => $list->where('status', 'pending')->count(),
                'flagged' => $list->where('status', 'pending')->where('ai_flag', 'flagged')->count(),
                'people' => $list->take(3)->map(fn ($x) => $x->user)->filter()->values(),
                'names' => $list->take(2)->map(fn ($x) => $x->user?->first_name)->filter()->implode(', '),
            ];
        }

        return $days;
    }

    #[Computed]
    public function weekDays(): array
    {
        $start = Carbon::parse($this->selectedDate)->startOfWeek(Carbon::SUNDAY);

        return $this->days($start, $start->copy()->addDays(6));
    }

    #[Computed]
    public function monthDays(): array
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();

        return $this->days($start->copy()->startOfWeek(Carbon::SUNDAY), $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY));
    }

    #[Computed]
    public function historyRows()
    {
        return $this->applySearch(Appointment::query()->with('user', 'workspace.template'))
            ->when($this->filterDate, fn ($q) => $q->whereDate('appointment_date', $this->filterDate))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->orderByDesc('appointment_date')
            ->orderByRaw("CASE session WHEN 'morning' THEN 0 ELSE 1 END")
            ->orderBy('queue_number')
            ->paginate(15);
    }

    /* --------------------------- office availability ------------------------ */

    public function toggleEditingHours(): void
    {
        $this->editingHours = ! $this->editingHours;
        $this->hoursDates = [];
        $this->openId = null;
        if ($this->editingHours) {
            $this->viewMode = 'calendar';
        }
    }

    /** Month view while editing hours: pick the days to change. */
    public function toggleHoursDate(string $date): void
    {
        if (! strtotime($date) || OfficeAvailability::isWeekend($date)) {
            return;
        }

        $this->hoursDates = in_array($date, $this->hoursDates, true)
            ? array_values(array_diff($this->hoursDates, [$date]))
            : [...$this->hoursDates, $date];

        $this->loadHours();
    }

    /** One day's hours, straight from its column header. */
    public function editDayHours(string $date): void
    {
        if (! strtotime($date) || OfficeAvailability::isWeekend($date)) {
            return;
        }
        $this->hoursDates = [Carbon::parse($date)->toDateString()];
        $this->loadHours();
        $this->openDialog('availability');
    }

    private function loadHours(): void
    {
        if (count($this->hoursDates) === 1) {
            $a = OfficeAvailability::where('date', $this->hoursDates[0])->first();
            $this->morningOpen = $a?->morning_open ?? true;
            $this->afternoonOpen = $a?->afternoon_open ?? true;
            $this->morningSlots = $a?->morning_slots ?? 25;
            $this->afternoonSlots = $a?->afternoon_slots ?? 25;
        }
    }

    public function saveHours(): void
    {
        $this->validate([
            'hoursDates' => 'required|array|min:1',
            'morningSlots' => 'required|integer|min:0|max:500',
            'afternoonSlots' => 'required|integer|min:0|max:500',
        ], ['hoursDates.required' => 'Pick at least one day.']);

        $dates = array_filter($this->hoursDates, fn ($d) => ! OfficeAvailability::isWeekend($d));
        foreach ($dates as $date) {
            OfficeAvailability::updateOrCreate(['date' => $date], [
                'morning_open' => $this->morningOpen,
                'afternoon_open' => $this->afternoonOpen,
                'morning_slots' => $this->morningSlots,
                'afternoon_slots' => $this->afternoonSlots,
            ]);
        }

        $count = count($dates);
        $this->hoursDates = [];
        $this->dialog = null;
        $this->toast('Hours saved', 'Office hours updated for ' . $count . ' ' . str('day')->plural($count) . '.');
    }

    /* --------------------------------- queue -------------------------------- */

    public function setNowServing(string $session, int $queueNumber): void
    {
        if (! in_array($session, ['morning', 'afternoon'], true)) {
            return;
        }
        OfficeAvailability::updateOrCreate(['date' => $this->selectedDate], ['now_serving_' . $session => $queueNumber]);
    }

    public function skipNowServing(string $session): void
    {
        if (! in_array($session, ['morning', 'afternoon'], true)) {
            return;
        }
        $availability = OfficeAvailability::firstOrCreate(['date' => $this->selectedDate]);
        $current = $availability->{'now_serving_' . $session};

        $next = Appointment::whereDate('appointment_date', $this->selectedDate)
            ->where('session', $session)
            ->where('status', 'approved')
            ->whereNotNull('queue_number')
            ->when($current, fn ($q) => $q->where('queue_number', '>', $current))
            ->orderBy('queue_number')
            ->first();

        $availability->update(['now_serving_' . $session => $next?->queue_number]);
    }

    public function markAttended(int $id): void
    {
        $appt = Appointment::with('workspace', 'user')->find($id);
        if (! $appt || $appt->status !== 'approved') {
            return;
        }

        if ($appt->workspace && ! $appt->workspace->usesWorkflow()) {
            $appt->workspace->update(['status' => 'completed', 'completed_at' => now()]);
        }
        $appt->update(['status' => 'attended']);
        $appt->logStatus('attended', null, Auth::id());
        $this->toast('Marked as attended', ($appt->user?->full_name ?? 'The student') . ' attended their visit.');
    }

    /* ------------------------------- selection ------------------------------ */

    public function selectAllPending(): void
    {
        $this->selectedIds = $this->dayRows->where('status', 'pending')->pluck('appointment_id')->map(fn ($id) => (string) $id)->all();
        $this->capacityWarning = null;
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->capacityWarning = null;
        if ($this->dialog !== 'availability') {
            $this->closeDialog();
        }
    }

    #[Computed]
    public function selectedPendingCount(): int
    {
        return Appointment::whereIn('appointment_id', $this->ids())->where('status', 'pending')->count();
    }

    private function ids(): array
    {
        return collect($this->selectedIds)->map(fn ($id) => (int) $id)->filter()->values()->all();
    }

    /** Emergency: move every approved visit of the day. */
    public function emergencyRescheduleDay(): void
    {
        $this->selectedIds = Appointment::whereDate('appointment_date', $this->selectedDate)
            ->where('status', 'approved')
            ->pluck('appointment_id')->map(fn ($id) => (string) $id)->all();

        if ($this->selectedIds) {
            $this->openDialog('reschedule');
        }
    }

    /* -------------------------------- dialogs ------------------------------- */

    public function openDialog(string $which, string $scope = 'selected', ?int $id = null): void
    {
        if (! in_array($which, ['approve', 'reject', 'reschedule', 'availability'], true)) {
            return;
        }
        if ($id) {
            $this->openId = $id;
        }
        $this->resetErrorBag();
        $this->dialog = $which;
        $this->dialogScope = $scope === 'one' && $this->openId ? 'one' : 'selected';
        $this->reasonOption = '';
        $this->reasonText = '';
        $this->rescheduleDate = null;
        $this->rescheduleSession = null;

        // Rejecting one the AI suggested rejecting: start from its summary.
        $one = $this->dialogScope === 'one' ? Appointment::find($this->openId) : null;
        if ($which === 'reject' && ($one?->ai_findings['recommendation'] ?? null) === 'reject') {
            $this->reasonOption = 'Custom';
            $this->reasonText = (string) ($one->ai_findings['summary'] ?? '');
        }
    }

    public function closeDialog(): void
    {
        if ($this->dialog === 'availability' && ! $this->editingHours) {
            $this->hoursDates = [];
        }
        $this->dialog = null;
        $this->reasonOption = '';
        $this->reasonText = '';
        $this->resetErrorBag();
    }

    /** The appointments a dialog acts on. */
    private function targets(array $statuses): Collection
    {
        $ids = $this->dialogScope === 'one' ? [$this->openId] : $this->ids();

        return Appointment::with('user')->whereIn('appointment_id', $ids)->whereIn('status', $statuses)->get();
    }

    private function reason(int $min = 5): ?string
    {
        $reason = trim($this->reasonOption === 'Custom' || $this->reasonOption === '' ? $this->reasonText : $this->reasonOption);
        if (mb_strlen($reason) < $min) {
            $this->addError('reasonText', $this->reasonOption === '' ? 'Choose a reason or write one.' : 'Write a reason of at least ' . $min . ' characters.');

            return null;
        }

        return mb_substr($reason, 0, 500);
    }

    private function afterAction(): void
    {
        if ($this->dialogScope !== 'one') {
            $this->selectedIds = [];
        }
        $this->closeDialog();
    }

    /** Approve: queue numbers in order of submission, after checking room and first-come-first-served. */
    public function approve(bool $force = false): void
    {
        $targets = $this->targets(['pending']);
        if ($targets->isEmpty()) {
            $this->afterAction();

            return;
        }

        $groups = $targets->groupBy(fn ($a) => $a->appointment_date->toDateString() . '|' . $a->session);

        if (! $force) {
            $warnings = [];
            foreach ($groups as $key => $items) {
                [$date, $session] = explode('|', $key);
                $availability = OfficeAvailability::where('date', $date)->first();
                $open = OfficeAvailability::sessionOpen($date, $session);
                $slots = $availability?->{$session . '_slots'} ?? 25;
                $confirmed = Appointment::whereDate('appointment_date', $date)->where('session', $session)->whereIn('status', ['approved', 'attended'])->count();
                $remaining = $open ? max($slots - $confirmed, 0) : 0;

                $fcfs = Appointment::whereDate('appointment_date', $date)
                    ->where('session', $session)
                    ->where('status', 'pending')
                    ->whereNotIn('appointment_id', $items->pluck('appointment_id'))
                    ->where('created_at', '<', $items->min('created_at'))
                    ->exists();

                if ($items->count() > $remaining || $fcfs) {
                    $warnings[$key] = [
                        'label' => Carbon::parse($date)->format('M j') . ', ' . $session,
                        'needed' => $items->count(),
                        'remaining' => $remaining,
                        'open' => $open,
                        'fcfs_violation' => $fcfs,
                    ];
                }
            }

            if ($warnings) {
                $this->capacityWarning = $warnings;
                $this->dialog = null;

                return;
            }
        }

        DB::transaction(function () use ($groups) {
            foreach ($groups as $key => $items) {
                [$date, $session] = explode('|', $key);
                $next = (Appointment::whereDate('appointment_date', $date)->where('session', $session)->whereNotNull('queue_number')->max('queue_number') ?? 0) + 1;

                foreach ($items->sortBy('created_at') as $appointment) {
                    $appointment->update([
                        'status' => 'approved',
                        'queue_number' => $next++,
                        'reviewed_by' => Auth::id(),
                        'reviewed_at' => now(),
                    ]);
                    $appointment->logStatus('approved', $items->count() > 1 ? 'Approved in a batch' : null, Auth::id());
                    $appointment->user?->notify(new AppointmentStatusChanged($appointment, 'approved'));
                }
            }
        });

        $count = $targets->count();
        $this->capacityWarning = null;
        $this->afterAction();
        $this->toast('Approved', $count . ' ' . str('appointment')->plural($count) . ' approved. The students were notified.');
    }

    public function reject(): void
    {
        $reason = $this->reason();
        if ($reason === null) {
            return;
        }

        $targets = $this->targets(['pending']);
        foreach ($targets as $appointment) {
            $appointment->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);
            $appointment->logStatus('rejected', $reason, Auth::id());
            $appointment->user?->notify(new AppointmentStatusChanged($appointment, 'rejected'));
        }

        $count = $targets->count();
        $this->afterAction();
        $this->toast('Rejected', $count . ' ' . str('appointment')->plural($count) . ' rejected. The students were notified.');
    }

    public function reschedule(): void
    {
        $this->validate([
            'rescheduleDate' => 'required|date|after_or_equal:today',
            'rescheduleSession' => 'required|in:morning,afternoon',
        ], [
            'rescheduleDate.required' => 'Choose the new day.',
            'rescheduleSession.required' => 'Choose the new session.',
        ]);

        $reason = $this->reason();
        if ($reason === null) {
            return;
        }

        if (! OfficeAvailability::sessionOpen($this->rescheduleDate, $this->rescheduleSession)) {
            $this->addError('rescheduleDate', OfficeAvailability::isWeekend($this->rescheduleDate)
                ? 'The office is closed on Saturdays and Sundays. Please pick a weekday.'
                : 'The office is closed for that session. Please pick another.');

            return;
        }

        $targets = $this->targets(['pending', 'approved']);
        foreach ($targets as $appointment) {
            AppointmentReschedule::create([
                'appointment_id' => $appointment->appointment_id,
                'old_date' => $appointment->appointment_date,
                'old_session' => $appointment->session,
                'new_date' => $this->rescheduleDate,
                'new_session' => $this->rescheduleSession,
                'reason' => $reason,
                'rescheduled_by' => Auth::id(),
            ]);

            $appointment->update([
                'appointment_date' => $this->rescheduleDate,
                'session' => $this->rescheduleSession,
                'queue_number' => null,
                'status' => 'pending',
                'reschedule_reason' => $reason,
                'reschedule_count' => $appointment->reschedule_count + 1,
            ]);

            $appointment->logStatus('pending', 'Rescheduled to ' . $this->rescheduleDate . ' (' . $this->rescheduleSession . ').', Auth::id());
            $appointment->user?->notify(new AppointmentStatusChanged($appointment, 'rescheduled'));
        }

        $count = $targets->count();
        $to = Carbon::parse($this->rescheduleDate)->format('M j');
        $this->afterAction();
        $this->toast('Rescheduled', $count . ' ' . str('appointment')->plural($count) . ' moved to ' . $to . '. The students were notified.');
    }

    public function dismissCapacityWarning(): void
    {
        $this->capacityWarning = null;
    }

    /* -------------------------------- AI check ------------------------------ */

    public function rerunReview(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->update(['ai_flag' => 'pending']);
        ReviewAppointmentSubmission::dispatch($appointment);
    }

    public function bulkRerunReview(): void
    {
        foreach (Appointment::whereIn('appointment_id', $this->ids())->get() as $appointment) {
            $appointment->update(['ai_flag' => 'pending']);
            ReviewAppointmentSubmission::dispatch($appointment);
        }

        $count = count($this->ids());
        $this->clearSelection();
        $this->toast('AI check started', $count . ' ' . str('appointment')->plural($count) . ' queued.');
    }

    private function toast(string $title, string $message): void
    {
        $this->dispatch('toast', type: 'success', title: $title, message: $message);
    }

    public function render()
    {
        $polling = $this->viewMode === 'list' && $this->phase === 'review' && $this->dayRows->contains('ai_flag', 'pending');

        return view('livewire.admin.appointments.appointments-board', [
            'monthLabel' => Carbon::parse($this->month . '-01')->format('F Y'),
            'day' => Carbon::parse($this->selectedDate),
            'polling' => $polling,
        ])->layout('layouts.app', ['title' => 'Appointments']);
    }
}
