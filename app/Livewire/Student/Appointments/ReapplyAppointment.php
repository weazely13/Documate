<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentReapplication;
use App\Models\OfficeAvailability;
use App\Models\User;
use App\Notifications\NewAppointmentBooked;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ReapplyAppointment extends Component
{
    public Appointment $appointment;
    public ?string $selectedDate = null;
    public ?string $selectedSession = null;
    public string $currentMonth;

    public function mount(Appointment $appointment): void
    {
        abort_unless($appointment->user_id === Auth::id(), 403);
        abort_unless($appointment->canReapply(), 403);

        $this->appointment = $appointment;
        $this->selectedSession = $appointment->session;
        $this->currentMonth = now()->format('Y-m');
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

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
    }

    public function selectSlot(string $date, string $session): void
    {
        $this->selectedDate = $date;
        $this->selectedSession = $session;
    }

    public function confirmReapply()
    {
        $this->validate([
            'selectedDate' => 'required|date|after_or_equal:today',
            'selectedSession' => 'required|in:morning,afternoon',
        ]);

        $availability = OfficeAvailability::where('date', $this->selectedDate)->first();
        $cap = $this->selectedSession === 'morning'
            ? ($availability?->morning_slots ?? 25)
            : ($availability?->afternoon_slots ?? 25);

        $activeCount = Appointment::where('appointment_date', $this->selectedDate)
            ->where('session', $this->selectedSession)
            ->whereIn('status', ['pending', 'approved'])
            ->lockForUpdate()
            ->count();

        if ($activeCount >= $cap) {
            $this->addError('selectedSession', 'This slot just filled up. Please pick another.');
            return;
        }

        AppointmentReapplication::create([
            'appointment_id' => $this->appointment->appointment_id,
            'old_date' => $this->appointment->appointment_date,
            'old_session' => $this->appointment->session,
            'new_date' => $this->selectedDate,
            'new_session' => $this->selectedSession,
        ]);

        $this->appointment->update([
            'appointment_date' => $this->selectedDate,
            'session' => $this->selectedSession,
            'queue_number' => null,
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'ai_flag' => 'pending',
        ]);

        $this->appointment->logStatus('pending', 'Reapplied for new date after being marked missed.');

        \App\Jobs\ReviewAppointmentSubmission::dispatch($this->appointment);

        return $this->redirect(route('student.appointments.show', $this->appointment->appointment_id), navigate: true);
    }

    public function render()
    {
        return view('livewire.student.appointments.reapply-appointment', [
            'monthLabel' => Carbon::parse($this->currentMonth . '-01')->format('F Y'),
        ])->layout('layouts.app', ['title' => 'Reapply for Appointment']);
    }
}