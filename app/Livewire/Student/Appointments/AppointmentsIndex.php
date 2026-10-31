<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use App\Models\OfficeAvailability;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

/** The student's visits: upcoming and past, as a simple list or a calendar. */
class AppointmentsIndex extends Component
{
    public const FILTERS = [
        'upcoming' => 'Upcoming',
        'past' => 'Past visits',
    ];

    #[Url(as: 'show', except: 'upcoming')]
    public string $filter = 'upcoming';

    #[Url(as: 'view', except: 'list')]
    public string $viewMode = 'list';

    #[Url(except: '')]
    public string $search = '';

    public string $month = '';

    public ?int $retractingId = null;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        if (! array_key_exists($this->filter, self::FILTERS)) {
            $this->filter = 'upcoming';
        }
        if (! in_array($this->viewMode, ['list', 'calendar'], true)) {
            $this->viewMode = 'list';
        }
    }

    public function setFilter(string $filter): void
    {
        if (array_key_exists($filter, self::FILTERS)) {
            $this->filter = $filter;
        }
    }

    public function setViewMode(string $mode): void
    {
        if (in_array($mode, ['list', 'calendar'], true)) {
            $this->viewMode = $mode;
        }
    }

    public function prevMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::parse($this->month . '-01')->addMonth()->format('Y-m');
    }

    public function thisMonth(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function confirmRetract(int $appointmentId): void
    {
        $this->retractingId = $appointmentId;
    }

    public function cancelRetract(): void
    {
        $this->retractingId = null;
    }

    public function retract(): void
    {
        $appointment = Appointment::where('appointment_id', $this->retractingId)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        abort_unless($appointment->canBeDeleted(), 403);

        $appointment->update(['status' => 'retracted']);
        $appointment->logStatus('retracted', null, Auth::id());
        $this->retractingId = null;
        $this->dispatch('toast', type: 'success', title: 'Appointment withdrawn', message: 'Your place in the queue was released.');
    }

    // Refreshed by broadcasts and polling.
    #[On('queue-updated')]
    public function refreshQueue(): void
    {
    }

    /** All of the student's appointments, with the live "now serving" number on approved ones. */
    #[Computed]
    public function all(): Collection
    {
        $list = Appointment::with('workspace.template')
            ->where('user_id', Auth::id())
            ->orderByDesc('appointment_date')
            ->orderByDesc('created_at')
            ->get()
            ->each(fn ($a) => $a->refreshMissedStatusIfLapsed());

        $serving = OfficeAvailability::whereIn('date', $list->where('status', 'approved')->map(fn ($a) => $a->appointment_date->toDateString())->unique())
            ->get()->keyBy(fn ($o) => $o->date->toDateString());

        return $list->each(function ($a) use ($serving) {
            if ($a->status === 'approved') {
                $a->setAttribute('now_serving', $serving->get($a->appointment_date->toDateString())?->{'now_serving_' . $a->session});
            }
        });
    }

    #[Computed]
    public function counts(): array
    {
        $all = $this->all;

        return [
            'upcoming' => $all->whereIn('status', ['pending', 'approved'])->count(),
            'past' => $all->whereIn('status', ['attended', 'missed', 'rejected', 'retracted'])->count(),
        ];
    }

    #[Computed]
    public function rows(): Collection
    {
        $rows = match ($this->filter) {
            'past' => $this->all->whereIn('status', ['attended', 'missed', 'rejected', 'retracted']),
            default => $this->all->whereIn('status', ['pending', 'approved']),
        };

        $term = mb_strtolower(trim($this->search));
        if ($term !== '') {
            $rows = $rows->filter(fn ($a) => str_contains(mb_strtolower($a->purpose . ' ' . $a->workspace?->documentName()), $term));
        }

        // Upcoming visits soonest first; past ones newest first.
        return ($this->filter === 'past' ? $rows : $rows->sortBy(fn ($a) => $a->appointment_date->toDateString() . ($a->session === 'morning' ? '0' : '1')))->values();
    }

    #[Computed]
    public function calendarDays(): array
    {
        $start = Carbon::parse($this->month . '-01')->startOfMonth();
        $from = $start->copy()->startOfWeek(Carbon::SUNDAY);
        $to = $start->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $byDay = $this->all->where('status', '!=', 'retracted')->groupBy(fn ($a) => $a->appointment_date->toDateString());

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $iso = $d->toDateString();
            $days[] = [
                'date' => $iso,
                'day' => $d->day,
                'inMonth' => $d->month === $start->month,
                'isToday' => $d->isToday(),
                'weekend' => OfficeAvailability::isWeekend($d),
                'items' => $byDay->get($iso, collect())->values(),
            ];
        }

        return $days;
    }

    public function render()
    {
        // Poll only while a visit is today, so the "now serving" number stays live.
        $live = $this->all->contains(fn ($a) => $a->status === 'approved' && $a->appointment_date->isToday());

        return view('livewire.student.appointments.appointments-index', [
            'live' => $live,
            'monthLabel' => Carbon::parse($this->month . '-01')->format('F Y'),
            'booked' => session('appointment-booked'),
        ])->layout('layouts.app', ['title' => 'Appointments']);
    }
}
