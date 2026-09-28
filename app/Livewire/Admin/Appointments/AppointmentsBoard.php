<?php

namespace App\Livewire\Admin\Appointments;

use App\Jobs\ReviewAppointmentSubmission;
use App\Models\Appointment;
use App\Models\AppointmentReschedule;
use App\Models\OfficeAvailability;
use App\Notifications\AppointmentStatusChanged;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AppointmentsBoard extends Component
{
    public string $month;
    public string $selectedDate;
    use WithPagination;
    
    // View switching & History filters
    public string $viewMode = 'board'; // 'board' or 'history'
    public string $filterDate = '';
    public string $filterStatus = '';
    public string $search = '';

    // Bulk selection
    public array $selectedIds = [];

    // Bulk reject
    public bool $showBulkReject = false;
    public string $bulkRejectionReason = '';

    // Bulk reschedule
    public bool $showBulkReschedule = false;
    public ?string $bulkRescheduleDate = null;
    public ?string $bulkRescheduleSession = null;
    public string $bulkRescheduleReason = '';
    protected string $paginationTheme = 'tailwind';

    public ?array $capacityWarning = null;
    public $showBulkApprove = false;
    public $customBulkRescheduleReason = '';
    public $bulkRejectReason = '';
    public $customBulkRejectReason = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->selectedDate = now()->toDateString();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        $this->clearSelection();
    }

    public function prevMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->addMonth()->format('Y-m');
    }
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilterStatus()
    {
        $this->resetPage();
    }
    public function jumpToday(): void
    {
        $this->month = now()->format('Y-m');
        $this->selectedDate = now()->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->month = Carbon::parse($date)->format('Y-m');
        $this->clearSelection();
    }

    #[Computed]
    public function historyAppointments()
    {
        return Appointment::with('user', 'workspace.template')
            ->when($this->filterDate, fn($q) => $q->whereDate('appointment_date', $this->filterDate))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->whereHas('user', function ($userQuery) {
                        $userQuery->where('first_name', 'like', "%{$this->search}%")
                            ->orWhere('last_name', 'like', "%{$this->search}%")
                            ->orWhere('student_number', 'like', "%{$this->search}%");
                    })
                    ->orWhere('purpose', 'like', "%{$this->search}%")
                    ->orWhereHas('workspace.template', function ($templateQuery) {
                        $templateQuery->where('name', 'like', "%{$this->search}%");
                    });
                });
            })
            ->orderByDesc('appointment_date')
            ->orderBy('session')
            ->orderBy('queue_number')
            ->paginate(20);
    }

    #[Computed]
    public function calendarDays(): array
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();
        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $availabilities = OfficeAvailability::whereBetween('date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->get()
            ->keyBy(fn ($a) => $a->date->toDateString());

        $booked = Appointment::selectRaw('appointment_date, session, COUNT(*) as cnt')
            ->whereBetween('appointment_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->whereIn('status', ['pending', 'approved', 'attended'])
            ->groupBy('appointment_date', 'session')
            ->get()
            ->groupBy(fn ($row) => $row->appointment_date->toDateString());

        $pending = Appointment::selectRaw('appointment_date, COUNT(*) as cnt')
            ->whereBetween('appointment_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->where('status', 'pending')
            ->groupBy('appointment_date')
            ->pluck('cnt', 'appointment_date');

        $flagged = Appointment::selectRaw('appointment_date, COUNT(*) as cnt')
            ->whereBetween('appointment_date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->where('status', 'pending')
            ->where('ai_flag', 'flagged')
            ->groupBy('appointment_date')
            ->pluck('cnt', 'appointment_date');

        $days = [];

        for ($d = $calendarStart->copy(); $d->lte($calendarEnd); $d->addDay()) {
            $iso = $d->toDateString();
            $availability = $availabilities->get($iso);

            $morningOpen = $availability?->morning_open ?? true;
            $afternoonOpen = $availability?->afternoon_open ?? true;
            $morningSlots = $availability?->morning_slots ?? 25;
            $afternoonSlots = $availability?->afternoon_slots ?? 25;

            $dayBooked = $booked->get($iso, collect());
            $bookedMorning = (int) ($dayBooked->firstWhere('session', 'morning')->cnt ?? 0);
            $bookedAfternoon = (int) ($dayBooked->firstWhere('session', 'afternoon')->cnt ?? 0);

            $remainingMorning = $morningOpen ? max($morningSlots - $bookedMorning, 0) : 0;
            $remainingAfternoon = $afternoonOpen ? max($afternoonSlots - $bookedAfternoon, 0) : 0;

            $days[] = [
                'date' => $iso,
                'day' => $d->day,
                'inMonth' => $d->month === $start->month,
                'isToday' => $d->isToday(),
                'isPast' => $d->lt(now()->startOfDay()),
                'isSelected' => $iso === $this->selectedDate,
                'isWeekend' => $d->isSunday(),
                'closed' => !$morningOpen && !$afternoonOpen,
                'remaining' => $remainingMorning + $remainingAfternoon,
                'totalSlots' => ($morningOpen ? $morningSlots : 0) + ($afternoonOpen ? $afternoonSlots : 0),
                'pendingCount' => (int) ($pending->get($iso) ?? 0),
                'flaggedCount' => (int) ($flagged->get($iso) ?? 0),
            ];
        }

        return $days;
    }

    #[Computed]
    public function selectedAvailability(): ?OfficeAvailability
    {
        return OfficeAvailability::where('date', $this->selectedDate)->first();
    }

    #[Computed]
    public function selectionHasApproved(): bool
    {
        return Appointment::whereIn('appointment_id', $this->selectedIds)
            ->where('status', 'approved')
            ->exists();
    }

    #[Computed]
    public function queueBoard(): array
    {
        $availability = $this->selectedAvailability;

        $build = function (string $session) use ($availability) {
            $list = Appointment::with('user')
                ->whereDate('appointment_date', $this->selectedDate)
                ->where('session', $session)
                ->whereIn('status', ['approved', 'attended', 'retracted'])
                ->whereNotNull('queue_number')
                ->orderBy('queue_number')
                ->get();

            $open = $availability?->{$session . '_open'} ?? true;
            $slots = $availability?->{$session . '_slots'} ?? 25;
            $confirmed = $list->whereIn('status', ['approved', 'attended'])->count();

            return [
                'list' => $list,
                'now_serving' => $availability?->{'now_serving_' . $session},
                'open' => $open,
                'slots' => $open ? $slots : 0,
                'remaining' => $open ? max($slots - $confirmed, 0) : 0,
            ];
        };

        return ['morning' => $build('morning'), 'afternoon' => $build('afternoon')];
    }

    #[Computed]
    public function selectedDateAppointments(): Collection
    {
        return Appointment::with('user', 'workspace.template')
            ->whereDate('appointment_date', $this->selectedDate)
            ->orderByRaw("FIELD(status, 'pending', 'approved', 'attended', 'missed', 'rejected', 'retracted')")
            ->orderByRaw("FIELD(ai_flag, 'flagged', 'review_failed', 'pending', 'clean')")
            ->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_findings, '$.incorrect_probability')) AS UNSIGNED) DESC")
            ->orderBy('session')
            ->orderBy('created_at')
            ->get();
    }

    #[Computed]
    public function groupedAppointments(): array
    {
        $all = Appointment::with('user', 'workspace.template')
            ->whereDate('appointment_date', $this->selectedDate)
            ->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_findings, '$.incorrect_probability')) AS UNSIGNED) DESC")
            ->orderBy('created_at')
            ->get();

        $pending = $all->where('status', 'pending');
        $rejectCandidates = $pending->filter(fn ($a) => ($a->ai_findings['recommendation'] ?? null) === 'reject')->values();

        $categoryOrder = ['identity_mismatch', 'missing_fields', 'garbled_text', 'purpose_mismatch', 'invalid_format', 'other'];

        $rejectGrouped = $rejectCandidates
            ->groupBy(fn ($a) => $a->ai_reason_category ?? 'other')
            ->sortBy(fn ($group, $key) => array_search($key, $categoryOrder) !== false ? array_search($key, $categoryOrder) : 999)
            ->map(fn ($group, $key) => [
                'label' => $group->first()->ai_reason_category_label ?? 'Other Issues',
                'items' => $group->values(),
            ]);

        return [
            'reject' => $rejectGrouped,
            'approve' => $pending->filter(fn ($a) => ($a->ai_findings['recommendation'] ?? null) !== 'reject')->values(),
            'approved' => $all->where('status', 'approved')->sortBy('queue_number')->values(),
            'rejected' => $all->where('status', 'rejected')->values(),
        ];
    }

    public function setNowServing(string $session, int $queueNumber): void
    {
        OfficeAvailability::updateOrCreate(
            ['date' => $this->selectedDate],
            ['now_serving_' . $session => $queueNumber]
        );
    }

    public function skipNowServing(string $session): void
    {
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

    public function toggleSelect(int $appointmentId): void
    {
        if (in_array($appointmentId, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$appointmentId]));
        } else {
            $this->selectedIds[] = $appointmentId;
        }

        $this->capacityWarning = null;
    }

    public function selectAllActionable(): void
    {
        $this->selectedIds = $this->selectedDateAppointments
            ->where('status', 'pending')
            ->pluck('appointment_id')
            ->all();

        $this->capacityWarning = null;
    }

    public function emergencyRescheduleDay(): void
    {
        $this->selectedIds = Appointment::whereDate('appointment_date', $this->selectedDate)
            ->where('status', 'approved')
            ->pluck('appointment_id')
            ->all();

        if (empty($this->selectedIds)) {
            return;
        }

        $this->showBulkReschedule = true;
    }

    #[Computed]
    public function pendingSummary(): array
    {
        $overallPending = Appointment::where('status', 'pending')->get();
        $todaysAll = Appointment::whereDate('appointment_date', $this->selectedDate)->get();
        $todaysPending = $todaysAll->where('status', 'pending');

        return [
            'overall' => [
                'pending' => $overallPending->count(),
                'ai_approve' => $overallPending->filter(fn ($a) => ($a->ai_findings['recommendation'] ?? null) === 'approve')->count(),
                'ai_reject' => $overallPending->filter(fn ($a) => ($a->ai_findings['recommendation'] ?? null) === 'reject')->count(),
                'approved' => Appointment::where('status', 'approved')->count(),
            ],
            'selected' => [
                'pending' => $todaysPending->count(),
                'ai_approve' => $todaysPending->filter(fn ($a) => ($a->ai_findings['recommendation'] ?? null) === 'approve')->count(),
                'ai_reject' => $todaysPending->filter(fn ($a) => ($a->ai_findings['recommendation'] ?? null) === 'reject')->count(),
                'approved' => $todaysAll->where('status', 'approved')->count(),
            ],
        ];
    }

    public function selectAllInColumn(string $column): void
    {
        if ($column === 'reject') {
            $ids = $this->groupedAppointments['reject']
                ->flatMap(fn ($group) => $group['items'])
                ->whereIn('status', ['pending', 'approved'])
                ->pluck('appointment_id')
                ->all();
        } else {
            $ids = ($this->groupedAppointments[$column] ?? collect())
                ->whereIn('status', ['pending', 'approved'])
                ->pluck('appointment_id')
                ->all();
        }

        $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $ids)));
        $this->capacityWarning = null;
    }

    #[Computed]
    public function missedAppointments(): Collection
    {
        return Appointment::with('user', 'workspace.template')
            ->whereDate('appointment_date', $this->selectedDate)
            ->where('status', 'missed')
            ->orderBy('session')
            ->orderBy('created_at')
            ->get();
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->capacityWarning = null;
        $this->showBulkReject = false;
        $this->showBulkReschedule = false;
        $this->bulkRejectionReason = '';
        $this->bulkRescheduleDate = null;
        $this->bulkRescheduleSession = null;
        $this->bulkRescheduleReason = '';
    }

    #[Computed]
    public function selectedPendingCount(): int
    {
        return Appointment::whereIn('appointment_id', $this->selectedIds)->where('status', 'pending')->count();
    }

    public function bulkApprove(bool $force = false): void
    {
        $targets = Appointment::where('status', 'pending')
            ->whereIn('appointment_id', $this->selectedIds)
            ->get();

        if ($targets->isEmpty()) {
            $this->selectedIds = [];
            return;
        }

        $bySession = $targets->groupBy('session');
        $board = $this->queueBoard;

        if (!$force) {
            $warnings = [];

            foreach ($bySession as $session => $items) {
                $remaining = $board[$session]['remaining'] ?? 0;
                $needsCapacityWarning = $items->count() > $remaining;

                // FCFS check: is there a pending appointment for this date/session,
                // NOT in this selection, that was submitted earlier than the earliest one we're approving?
                $earliestInBatch = $items->min('created_at');
                $fcfsViolation = Appointment::where('appointment_date', $this->selectedDate)
                    ->where('session', $session)
                    ->where('status', 'pending')
                    ->whereNotIn('appointment_id', $this->selectedIds)
                    ->where('created_at', '<', $earliestInBatch)
                    ->exists();

                if ($needsCapacityWarning || $fcfsViolation) {
                    $warnings[$session] = [
                        'needed' => $items->count(),
                        'remaining' => $remaining,
                        'open' => $board[$session]['open'] ?? true,
                        'fcfs_violation' => $fcfsViolation,
                    ];
                }
            }

            if (!empty($warnings)) {
                $this->capacityWarning = $warnings;
                return;
            }
        }

        $this->capacityWarning = null;

        foreach ($bySession as $session => $items) {
            $nextQueueNumber = (Appointment::where('appointment_date', $this->selectedDate)
                ->where('session', $session)
                ->whereNotNull('queue_number')
                ->max('queue_number') ?? 0) + 1;

            foreach ($items->sortBy('created_at') as $appointment) {
                $appointment->update([
                    'status' => 'approved',
                    'queue_number' => $nextQueueNumber,
                    'reviewed_by' => Auth::id(),
                    'reviewed_at' => now(),
                ]);

                $appointment->logStatus('approved', 'Bulk approved', Auth::id());
                $appointment->user->notify(new AppointmentStatusChanged($appointment, 'approved'));

                $nextQueueNumber++;
            }
        }

        $this->clearSelection();
    }

    public function dismissCapacityWarning(): void
    {
        $this->capacityWarning = null;
    }

    public function bulkReject()
    {
        // Resolve the final reason based on dropdown selection
        $reason = $this->bulkRejectReason === 'Custom'
            ? $this->customBulkRejectReason
            : $this->bulkRejectReason;

        if (empty($reason)) {
            $this->addError('bulkRejectReason', 'Please select or provide a rejection reason.');
            return;
        }

        $targets = Appointment::where('status', 'pending')
            ->whereIn('appointment_id', $this->selectedIds)
            ->get();

        if ($targets->isEmpty()) {
            $this->reset(['showBulkReject', 'bulkRejectReason', 'customBulkRejectReason']);
            $this->clearSelection();
            return;
        }

        foreach ($targets as $appointment) {
            $appointment->update([
                'status' => 'rejected',
                'rejection_reason' => $reason,
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            $appointment->logStatus('rejected', $reason, Auth::id());
            $appointment->user->notify(new AppointmentStatusChanged($appointment, 'rejected'));
        }

        $this->reset(['showBulkReject', 'bulkRejectReason', 'customBulkRejectReason']);
        $this->clearSelection();
    }

    public function bulkReschedule(): void
    {
        $this->validate([
            'bulkRescheduleDate' => 'required|date|after_or_equal:today',
            'bulkRescheduleSession' => 'required|in:morning,afternoon',
            'bulkRescheduleReason' => 'required|string|min:5',
        ]);

        $targets = Appointment::whereIn('status', ['pending', 'approved'])
            ->whereIn('appointment_id', $this->selectedIds)
            ->get();

        foreach ($targets as $appointment) {
            AppointmentReschedule::create([
                'appointment_id' => $appointment->appointment_id,
                'old_date' => $appointment->appointment_date,
                'old_session' => $appointment->session,
                'new_date' => $this->bulkRescheduleDate,
                'new_session' => $this->bulkRescheduleSession,
                'reason' => $this->bulkRescheduleReason,
                'rescheduled_by' => Auth::id(),
            ]);

            $appointment->update([
                'appointment_date' => $this->bulkRescheduleDate,
                'session' => $this->bulkRescheduleSession,
                'queue_number' => null,
                'status' => 'pending',
                'reschedule_reason' => $this->bulkRescheduleReason,
                'reschedule_count' => $appointment->reschedule_count + 1,
            ]);

            $appointment->logStatus('pending', 'Bulk rescheduled to ' . $this->bulkRescheduleDate . ' (' . $this->bulkRescheduleSession . ').', Auth::id());
            $appointment->user->notify(new AppointmentStatusChanged($appointment, 'rescheduled'));
        }

        $this->clearSelection();
    }

    public function rerunReview(int $appointmentId): void
    {
        $appointment = Appointment::findOrFail($appointmentId);
        $appointment->update(['ai_flag' => 'pending']);
        ReviewAppointmentSubmission::dispatch($appointment);
    }

    public function bulkRerunReview(): void
    {
        $targets = Appointment::whereIn('appointment_id', $this->selectedIds)->get();

        foreach ($targets as $appointment) {
            $appointment->update(['ai_flag' => 'pending']);
            ReviewAppointmentSubmission::dispatch($appointment);
        }

        $this->clearSelection();
    }

    public function render()
    {
        return view('livewire.admin.appointments.appointments-board', [
            'monthLabel' => Carbon::parse($this->month . '-01')->format('F Y'),
        ])->layout('layouts.app', ['title' => 'Appointments Board']);
    }
}