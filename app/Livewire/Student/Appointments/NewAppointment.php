<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use App\Models\OfficeAvailability;
use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Notifications\NewAppointmentBooked;
use App\Support\AppointmentPurposes;
use App\Workflow\Documents\DocumentRegistry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Book a visit in four steps: what it is about (one of the system's transactions), which of
 * your documents it concerns (shown as previews), when, and a last look before booking.
 */
class NewAppointment extends Component
{
    public const STEPS = [1 => 'Purpose', 2 => 'Document', 3 => 'Date and time', 4 => 'Review'];

    /** Workflow states a document can be in and still be worth a visit. */
    private const VISIT_STATES = ['submitted', 'under_review', 'action_required', 'approved', 'completed'];

    /** Most documents offered for a general visit. */
    private const GENERAL_LIMIT = 12;

    public int $step = 1;

    public ?string $purposeKey = null;
    public string $customPurpose = '';
    public string $purposeSearch = '';
    public string $purpose = '';

    public ?int $workspaceId = null;
    public ?string $selectedDate = null;
    public ?string $selectedSession = null;

    public ?int $reapplyFromId = null;
    public string $currentMonth;

    public function mount(?StudentDocumentWorkspace $workspace = null): void
    {
        $this->currentMonth = now()->format('Y-m');

        // Started from a document: it is already chosen, and so is the purpose that fits it.
        if ($workspace?->exists && $this->candidates()->contains('workspace_id', $workspace->workspace_id)) {
            $this->workspaceId = $workspace->workspace_id;
            $key = AppointmentPurposes::keyForWorkspace($workspace);
            if ($key && ($p = AppointmentPurposes::find($key))) {
                $this->purposeKey = $key;
                $this->purpose = $p['label'];
            }
        }
    }

    /* ----------------------------- purpose ----------------------------- */

    #[Computed]
    public function purposeOptions(): array
    {
        return AppointmentPurposes::options();
    }

    #[Computed]
    public function selectedPurpose(): ?array
    {
        return $this->purposeKey && $this->purposeKey !== AppointmentPurposes::OTHER
            ? AppointmentPurposes::find($this->purposeKey)
            : null;
    }

    public function selectPurpose(string $key): void
    {
        if ($key !== AppointmentPurposes::OTHER && ! AppointmentPurposes::find($key)) {
            return;
        }

        $this->purposeKey = $key;
        $this->resetErrorBag(['purposeKey', 'customPurpose']);

        // A document chosen earlier that is about something else no longer fits.
        if ($this->workspaceId && ! $this->documentFits($this->selectedWorkspace)) {
            $this->workspaceId = null;
        }
    }

    public function continueFromPurpose(): void
    {
        $this->validate([
            'purposeKey' => 'required|string',
            'customPurpose' => $this->purposeKey === AppointmentPurposes::OTHER ? 'required|string|min:5|max:255' : 'nullable',
        ], [
            'purposeKey.required' => 'Choose what your visit is about.',
            'customPurpose.required' => 'Tell us what your visit is about.',
            'customPurpose.min' => 'Add a little more detail (at least 5 characters).',
        ]);

        $this->purpose = $this->purposeKey === AppointmentPurposes::OTHER
            ? trim($this->customPurpose)
            : ($this->selectedPurpose['label'] ?? '');

        $this->step = 2;
    }

    /* ----------------------------- document ---------------------------- */

    /** The student's documents a visit can be about, not already tied to an open appointment. */
    private function candidates(): Collection
    {
        return StudentDocumentWorkspace::query()
            ->with('template')
            ->where('user_id', Auth::id())
            ->where(fn ($q) => $q
                ->where(fn ($legacy) => $legacy->whereNull('workflow_status')->where('status', 'pending'))
                ->orWhere(fn ($wf) => $wf->whereNotNull('document_type')->whereIn('workflow_status', self::VISIT_STATES)))
            ->whereDoesntHave('appointments', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
            ->latest('updated_at')
            ->get()
            ->filter(fn ($ws) => ! $ws->document_type || DocumentRegistry::has($ws->document_type))
            ->values();
    }

    /** Documents split by whether they are the transaction the visit is about. */
    #[Computed]
    public function documents(): array
    {
        $purpose = $this->selectedPurpose;
        [$match, $other] = $this->candidates()->partition(fn ($ws) => AppointmentPurposes::matches($purpose, $ws));

        // A visit about a specific kind of transaction only offers those documents.
        $specific = AppointmentPurposes::isSpecific($purpose);

        // General visits list the most recent documents only; each card loads a live preview.
        $others = $specific ? collect() : $other->values();

        return [
            'match' => $match->values(),
            'other' => $others->take(self::GENERAL_LIMIT),
            'hidden' => max(0, $others->count() - self::GENERAL_LIMIT),
            'specific' => $specific,
        ];
    }

    #[Computed]
    public function selectedWorkspace(): ?StudentDocumentWorkspace
    {
        return $this->workspaceId ? StudentDocumentWorkspace::with('template')->find($this->workspaceId) : null;
    }

    private function documentFits(?StudentDocumentWorkspace $ws): bool
    {
        if (! $ws) {
            return false;
        }
        $purpose = $this->selectedPurpose;

        return ! AppointmentPurposes::isSpecific($purpose) || AppointmentPurposes::matches($purpose, $ws);
    }

    public function selectDocument(int $workspaceId): void
    {
        $ws = $this->candidates()->firstWhere('workspace_id', $workspaceId);
        if (! $ws || ! $this->documentFits($ws)) {
            return;
        }

        $this->workspaceId = $workspaceId;
    }

    public function clearDocument(): void
    {
        $this->workspaceId = null;
    }

    public function continueFromDocument(): void
    {
        $this->step = 3;
    }

    /* --------------------------- date and time ------------------------- */

    public function prevMonth(): void
    {
        $prev = Carbon::parse($this->currentMonth . '-01')->subMonth();
        if ($prev->endOfMonth()->gte(now()->startOfDay())) {
            $this->currentMonth = $prev->format('Y-m');
        }
    }

    public function nextMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth . '-01')->addMonth()->format('Y-m');
    }

    #[Computed]
    public function calendarDays(): array
    {
        $start = Carbon::parse($this->currentMonth . '-01')->startOfMonth();
        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $range = [$calendarStart->toDateString(), $calendarEnd->toDateString()];

        $availabilities = OfficeAvailability::whereBetween('date', $range)
            ->get()->keyBy(fn ($a) => $a->date->toDateString());

        // One query for the whole month instead of two per day.
        $taken = Appointment::selectRaw('appointment_date, session, COUNT(*) as cnt')
            ->whereBetween('appointment_date', $range)
            ->whereIn('status', ['pending', 'approved'])
            ->groupBy('appointment_date', 'session')
            ->get()
            ->groupBy(fn ($r) => Carbon::parse($r->appointment_date)->toDateString());

        $now = now();
        $days = [];

        for ($d = $calendarStart->copy(); $d->lte($calendarEnd); $d->addDay()) {
            $iso = $d->toDateString();
            $availability = $availabilities->get($iso);
            $isWeekend = OfficeAvailability::isWeekend($d);

            $morningOpen = ! $isWeekend && ($availability?->morning_open ?? true);
            $afternoonOpen = ! $isWeekend && ($availability?->afternoon_open ?? true);
            $morningCap = $availability?->morning_slots ?? 25;
            $afternoonCap = $availability?->afternoon_slots ?? 25;

            $dayTaken = $taken->get($iso, collect());
            $morningTaken = (int) ($dayTaken->firstWhere('session', 'morning')->cnt ?? 0);
            $afternoonTaken = (int) ($dayTaken->firstWhere('session', 'afternoon')->cnt ?? 0);

            // Today's morning stays bookable until 11:30 AM, the afternoon until 4:30 PM.
            $morningLapsed = $d->isToday() && $now->gte(Carbon::today()->setTime(11, 30));
            $afternoonLapsed = $d->isToday() && $now->gte(Carbon::today()->setTime(16, 30));
            $isPast = $d->lt($now->copy()->startOfDay());

            $mAvail = ! $isPast && $morningOpen && ! $morningLapsed && $morningTaken < $morningCap;
            $aAvail = ! $isPast && $afternoonOpen && ! $afternoonLapsed && $afternoonTaken < $afternoonCap;

            $days[] = [
                'date' => $iso,
                'day' => $d->day,
                'inMonth' => $d->month === $start->month,
                'isToday' => $d->isToday(),
                'isPast' => $isPast,
                'isWeekend' => $isWeekend,
                'morning_open' => $mAvail,
                'afternoon_open' => $aAvail,
                'morning_remaining' => max($morningCap - $morningTaken, 0),
                'afternoon_remaining' => max($afternoonCap - $afternoonTaken, 0),
                'has_available' => $mAvail || $aAvail,
            ];
        }

        return $days;
    }

    public function selectDate(string $date): void
    {
        $day = collect($this->calendarDays)->firstWhere('date', $date);
        if ($day && $day['has_available']) {
            $this->selectedDate = $date;
            $this->selectedSession = null;
        }
    }

    public function selectSession(string $session): void
    {
        $day = collect($this->calendarDays)->firstWhere('date', $this->selectedDate);
        if ($day && in_array($session, ['morning', 'afternoon'], true) && $day[$session . '_open']) {
            $this->selectedSession = $session;
            $this->resetErrorBag('selectedSession');
        }
    }

    public function continueFromDate(): void
    {
        $this->validate([
            'selectedDate' => 'required|date|after_or_equal:today',
            'selectedSession' => 'required|in:morning,afternoon',
        ], [
            'selectedDate.required' => 'Choose a day.',
            'selectedSession.required' => 'Choose the morning or the afternoon.',
        ]);

        $this->step = 4;
    }

    /** Back to an earlier step (never forward past what has been filled in). */
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step < $this->step) {
            $this->step = $step;
        }
    }

    /* ------------------------------ booking ---------------------------- */

    public function confirmAppointment()
    {
        $this->validate([
            'purpose' => 'required|string|max:255',
            'selectedDate' => 'required|date|after_or_equal:today',
            'selectedSession' => 'required|in:morning,afternoon',
        ]);

        if ($this->workspaceId && ! $this->candidates()->contains('workspace_id', $this->workspaceId)) {
            $this->workspaceId = null;
            $this->step = 2;
            $this->addError('workspaceId', 'That document already has an open appointment. Choose another or continue without one.');

            return;
        }

        if (! OfficeAvailability::sessionOpen($this->selectedDate, $this->selectedSession)) {
            $this->addError('selectedSession', OfficeAvailability::isWeekend($this->selectedDate)
                ? 'The office is closed on Saturdays and Sundays. Please pick a weekday.'
                : 'The office is closed for that session. Please pick another.');

            return;
        }

        $result = DB::transaction(function () {
            // Serialize all submissions from this user: a second request waits here until the first one commits.
            User::whereKey(Auth::id())->lockForUpdate()->first();

            $alreadyBooked = Appointment::where('user_id', Auth::id())
                ->where('appointment_date', $this->selectedDate)
                ->where('session', $this->selectedSession)
                ->whereIn('status', ['pending', 'approved'])
                ->exists();

            if ($alreadyBooked) {
                return 'duplicate';
            }

            $availability = OfficeAvailability::where('date', $this->selectedDate)->first();
            $cap = $this->selectedSession === 'morning'
                ? ($availability?->morning_slots ?? 25)
                : ($availability?->afternoon_slots ?? 25);

            $activeCount = Appointment::where('appointment_date', $this->selectedDate)
                ->where('session', $this->selectedSession)
                ->whereIn('status', ['pending', 'approved'])
                ->count();

            if ($activeCount >= $cap) {
                return 'full';
            }

            $appointment = Appointment::create([
                'user_id' => Auth::id(),
                'workspace_id' => $this->workspaceId,
                'purpose' => $this->purpose,
                'appointment_date' => $this->selectedDate,
                'session' => $this->selectedSession,
                'queue_number' => null,
                'status' => 'pending',
                'ai_flag' => 'pending',
            ]);

            $appointment->logStatus('pending');

            return $appointment;
        });

        if ($result === 'full') {
            $this->step = 3;
            $this->addError('selectedSession', 'This session just filled up. Please pick another.');

            return;
        }
        if ($result === 'duplicate') {
            $this->step = 3;
            $this->addError('selectedSession', 'You already have an appointment for this day and session. Please pick another.');

            return;
        }

        if ($result instanceof Appointment) {
            \App\Jobs\ReviewAppointmentSubmission::dispatch($result);

            Notification::send(
                User::whereHas('role', fn ($q) => $q->where('role_name', 'Admin'))->get(),
                new NewAppointmentBooked($result)
            );
        }

        session()->flash('appointment-booked', 'Your appointment request was sent. You will be notified once the office approves it.');

        return $this->redirect(route('student.appointments.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.student.appointments.new-appointment', [
            'monthLabel' => Carbon::parse($this->currentMonth . '-01')->format('F Y'),
            'canGoBackMonth' => Carbon::parse($this->currentMonth . '-01')->subMonth()->endOfMonth()->gte(now()->startOfDay()),
        ])->layout('layouts.app', ['title' => 'Book an appointment']);
    }
}
