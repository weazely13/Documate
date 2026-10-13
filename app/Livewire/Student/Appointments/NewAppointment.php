<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use App\Models\OfficeAvailability;
use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Notifications\NewAppointmentBooked;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class NewAppointment extends Component
{
    public int $step = 1;

    public string $purpose = '';
    public ?int $workspaceId = null;
    public ?string $selectedDate = null;
    public ?string $selectedSession = null;

    public ?int $reapplyFromId = null;
    public string $currentMonth;

    public array $purposeOptions = [
        'Request for Student Records or Documents',
        'Request for Certificate of Good Moral Character',
        'Submit Clearance Requirements',
        'Process Graduation Clearance',
        'Request for Off-Campus Activity Permit',
        'Request for Student Activity/Organization Permit',
        'Submit Organization-Related Documents',
        'Update or Correct Student Record',
        'Clarification Regarding Student Records or Requirements',
        'Report a Student Concern or Incident',
        'File a Complaint or Grievance',
    ];

    public ?string $selectedPurposeOption = null;
    public string $customPurpose = '';

    public function mount(?StudentDocumentWorkspace $workspace = null): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->selectedDate = now()->addDay()->toDateString();

        if ($workspace && $workspace->user_id === Auth::id() && $workspace->status === 'pending') {
            $this->workspaceId = $workspace->workspace_id;
        }
    }

    #[Computed]
    public function selectedWorkspace()
    {
        return $this->workspaceId
            ? StudentDocumentWorkspace::with('template')->find($this->workspaceId)
            : null;
    }

        public function selectPurposeOption(string $option): void
    {
        $this->selectedPurposeOption = $option;

        if ($option !== 'Others') {
            $this->purpose = $option;
            $this->customPurpose = '';
        } else {
            $this->purpose = $this->customPurpose;
        }

        $this->resetErrorBag(['selectedPurposeOption', 'customPurpose']);
    }


    public function continueFromPurpose(): void
    {
        $this->validate([
            'selectedPurposeOption' => 'required|string',
            'customPurpose' => $this->selectedPurposeOption === 'Others' ? 'required|string|min:5' : 'nullable|string',
        ]);

        $this->purpose = $this->selectedPurposeOption === 'Others'
            ? $this->customPurpose
            : $this->selectedPurposeOption;

        // If a document was already selected (e.g. arriving from the document
        // workspace), skip the "select a pending document" step entirely.
        $this->goToStep($this->workspaceId ? 3 : 2);
    }

    public function prevMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth . '-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->currentMonth = Carbon::parse($this->currentMonth . '-01')->addMonth()->format('Y-m');
    }

    #[Computed]
    public function pendingDocuments()
    {
        return StudentDocumentWorkspace::query()
            ->with('template')
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->whereDoesntHave('appointments', fn ($q) => $q->whereIn('status', ['pending', 'approved']))
            ->latest('updated_at')
            ->get();
    }

    #[Computed]
    public function calendarDays(): array
    {
        $start = Carbon::parse($this->currentMonth . '-01')->startOfMonth();
        $calendarStart = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $calendarEnd = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $availabilities = OfficeAvailability::whereBetween('date', [$calendarStart->toDateString(), $calendarEnd->toDateString()])
            ->get()->keyBy(fn ($a) => $a->date->toDateString());

        $now = now();
        $days = [];

        for ($d = $calendarStart->copy(); $d->lte($calendarEnd); $d->addDay()) {
            $iso = $d->toDateString();
            $availability = $availabilities->get($iso);

            // Sundays are completely closed by default
            $isSunday = $d->isSunday();

            $morningOpen = $availability?->morning_open ?? !$isSunday;
            $afternoonOpen = $availability?->afternoon_open ?? !$isSunday;
            $morningCap = $availability?->morning_slots ?? 25;
            $afternoonCap = $availability?->afternoon_slots ?? 25;

            $morningTaken = Appointment::where('appointment_date', $iso)
                ->where('session', 'morning')->whereIn('status', ['pending', 'approved'])->count();
            $afternoonTaken = Appointment::where('appointment_date', $iso)
                ->where('session', 'afternoon')->whereIn('status', ['pending', 'approved'])->count();

            // Morning slot remains open on "today" until 11:30 AM
            $morningLapsed = $d->isToday() && $now->gte(Carbon::today()->setHour(11)->setMinute(30));
            
            // Afternoon slot remains open on "today" until 4:30 PM (16:30)
            $afternoonLapsed = $d->isToday() && $now->gte(Carbon::today()->setHour(16)->setMinute(30));

            $mAvail = $morningOpen && !$morningLapsed && $morningTaken < $morningCap;
            $aAvail = $afternoonOpen && !$afternoonLapsed && $afternoonTaken < $afternoonCap;

            $days[] = [
                'date' => $iso,
                'day' => $d->day,
                'inMonth' => $d->month === $start->month,
                'isPast' => $d->isPast() && !$d->isToday(),
                'isSunday' => $isSunday,
                'morning_open' => $mAvail,
                'afternoon_open' => $aAvail,
                'morning_remaining' => max($morningCap - $morningTaken, 0),
                'afternoon_remaining' => max($afternoonCap - $afternoonTaken, 0),
                'has_available' => ($mAvail || $aAvail) && (!$d->isPast() || $d->isToday()),
            ];
        }

        return $days;
    }

    public function goToStep(int $step): void
    {
        $this->step = $step;
    }

    public function selectDocument(int $workspaceId): void
    {
        $this->workspaceId = $workspaceId;
        $this->step = 3;
    }

    public function skipDocument(): void
    {
        $this->workspaceId = null;
        $this->step = 3;
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedSession = null;
    }

    public function selectSlot(string $date, string $session): void
    {
        $this->selectedDate = $date;
        $this->selectedSession = $session;
        $this->step = 4;
    }

    public function confirmAppointment()
    {
        $this->validate([
            'purpose' => 'required|string|max:255',
            'selectedDate' => 'required|date',
            'selectedSession' => 'required|in:morning,afternoon',
        ]);

        $result = DB::transaction(function () {
            // Serialize all submissions from this user: a second request
            // waits here until the first one commits.
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
            ]);

            $appointment->logStatus('pending');

            return $appointment;
        });

        if ($result === 'full') {
            $this->addError('selectedSession', 'This slot just filled up. Please pick another.');
            return;
        }

        // Duplicate click: the first request already created it, so just move on.
        if ($result instanceof Appointment) {
            \App\Jobs\ReviewAppointmentSubmission::dispatch($result);

            Notification::send(
                User::whereHas('role', fn ($q) => $q->where('role_name', 'Admin'))->get(),
                new NewAppointmentBooked($result)
            );
        }

        return $this->redirect(route('student.appointments.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.student.appointments.new-appointment', [
            'monthLabel' => Carbon::parse($this->currentMonth . '-01')->format('F Y'),
        ])->layout('layouts.app', ['title' => 'New Appointment']);
    }
}