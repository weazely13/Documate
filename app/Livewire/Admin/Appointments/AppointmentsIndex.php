<?php

namespace App\Livewire\Admin\Appointments;

use App\Models\Appointment;
use Livewire\Component;
use App\Models\OfficeAvailability;

class AppointmentsIndex extends Component
{
    public string $tab = 'review';

    // Only used by the History tab
    public string $filterDate = '';
    public string $filterStatus = '';
    public string $search = '';

    public function updatedTab(): void
    {
        $this->filterDate = '';
        $this->filterStatus = '';
        $this->search = '';
    }

    protected function baseQuery()
    {
        return Appointment::with('user', 'workspace.template');
    }

    public function render()
    {
        $appointments = match ($this->tab) {
            'review' => $this->baseQuery()
                ->where('status', 'pending')
                ->orderByRaw("FIELD(ai_flag, 'flagged', 'review_failed', 'pending', 'clean')")
                ->orderByRaw("CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_findings, '$.incorrect_probability')) AS UNSIGNED) DESC")
                ->orderBy('created_at') // first-come-first-served within the same risk tier
                ->get(),
            'daily' => $this->baseQuery()->whereDate('appointment_date', now())->where('status', 'approved')->orderBy('session')->orderBy('queue_number')->get(),
            'completed' => $this->baseQuery()->whereDate('appointment_date', now())->whereIn('status', ['attended', 'rejected', 'retracted'])->orderBy('session')->orderBy('queue_number')->get(),
            'missed' => $this->baseQuery()->where('status', 'missed')->orderByDesc('appointment_date')->get(),
            'history' => $this->filteredHistoryQuery()->get(),
            default => collect(),
        };

        return view('livewire.admin.appointments.appointments-index', [
            'appointments' => $appointments,
            'summary' => $this->summaryFor($this->tab),
            'queueSnapshot' => $this->queueSnapshot(), // always available now
        ])->layout('layouts.app', ['title' => 'Appointments']);
    }

    protected function queueSnapshot(): array
    {
        $availability = OfficeAvailability::where('date', now()->toDateString())->first();

        $build = function (string $session) use ($availability) {
            $list = Appointment::with('user')
                ->whereDate('appointment_date', now())
                ->where('session', $session)
                ->whereIn('status', ['approved', 'attended', 'retracted'])
                ->whereNotNull('queue_number')
                ->orderBy('queue_number')
                ->get();

            return [
                'list' => $list,
                'now_serving' => $availability?->{'now_serving_' . $session},
            ];
        };

        return ['morning' => $build('morning'), 'afternoon' => $build('afternoon')];
    }

    public function setNowServing(string $session, int $queueNumber): void
    {
        OfficeAvailability::updateOrCreate(
            ['date' => now()->toDateString()],
            ['now_serving_' . $session => $queueNumber]
        );
    }

    public function skipNowServing(string $session): void
    {
        $availability = OfficeAvailability::firstOrCreate(['date' => now()->toDateString()]);
        $current = $availability->{'now_serving_' . $session};

        $next = Appointment::whereDate('appointment_date', now())
            ->where('session', $session)
            ->where('status', 'approved')
            ->whereNotNull('queue_number')
            ->when($current, fn ($q) => $q->where('queue_number', '>', $current))
            ->orderBy('queue_number')
            ->first();

        $availability->update(['now_serving_' . $session => $next?->queue_number]);
    }
    protected function nowServingMap(): array
    {
        return [
            'morning' => Appointment::whereDate('appointment_date', now())->where('session', 'morning')->where('status', 'approved')->min('queue_number'),
            'afternoon' => Appointment::whereDate('appointment_date', now())->where('session', 'afternoon')->where('status', 'approved')->min('queue_number'),
        ];
    }

    protected function filteredHistoryQuery()
    {
        $query = $this->baseQuery()->orderByDesc('appointment_date')->orderBy('session')->orderBy('queue_number');

        if ($this->filterDate) {
            $query->whereDate('appointment_date', $this->filterDate);
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->where('first_name', 'like', "%{$this->search}%")
                  ->orWhere('last_name', 'like', "%{$this->search}%")
                  ->orWhere('student_number', 'like', "%{$this->search}%");
            });
        }

        return $query;
    }

    protected function summaryFor(string $tab): array
    {
        if ($tab === 'history') {
            return [
                ['label' => 'Total appointments', 'value' => Appointment::count(), 'tone' => 'default'],
                ['label' => 'Total completed', 'value' => Appointment::where('status', 'attended')->count(), 'tone' => 'green'],
                ['label' => 'Total missed', 'value' => Appointment::where('status', 'missed')->count(), 'tone' => 'red'],
                ['label' => 'Total rejected', 'value' => Appointment::where('status', 'rejected')->count(), 'tone' => 'red'],
            ];
        }

        return [
            ['label' => "Today's appointments", 'value' => Appointment::whereDate('appointment_date', now())->count(), 'tone' => 'default'],
            ['label' => 'Awaiting review', 'value' => Appointment::where('status', 'pending')->count(), 'tone' => 'amber'],
            ['label' => 'Approved for today', 'value' => Appointment::whereDate('appointment_date', now())->where('status', 'approved')->count(), 'tone' => 'green'],
            ['label' => 'Completed today', 'value' => Appointment::whereDate('appointment_date', now())->where('status', 'attended')->count(), 'tone' => 'green'],
        ];
    }
}