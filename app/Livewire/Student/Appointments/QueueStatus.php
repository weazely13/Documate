<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QueueStatus extends Component
{
    public Appointment $appointment;

    public function mount(Appointment $appointment): void
    {
        abort_unless($appointment->user_id === Auth::id(), 403);
        $this->appointment = $appointment;
    }

    #[Computed]
    public function queueList()
    {
        return Appointment::with('user', 'workspace.template')
            ->where('appointment_date', $this->appointment->appointment_date)
            ->where('session', $this->appointment->session)
            ->whereIn('status', ['approved', 'attended', 'retracted'])
            ->orderBy('queue_number')
            ->get();
    }

    #[Computed]
    public function currentServingNumber(): ?int
    {
        // Numbers are served strictly in order; retracted numbers are skipped
        // automatically since they're excluded here, but they still don't get reused.
        $next = $this->queueList
            ->where('status', 'approved')
            ->sortBy('queue_number')
            ->first();

        return $next?->queue_number;
    }

    public function refreshStatus(): void
    {
        $this->appointment->refresh();
        unset($this->queueList, $this->currentServingNumber);
    }

    public function render()
    {
        return view('livewire.student.appointments.queue-status')
            ->layout('layouts.app', ['title' => 'Appointment Status']);
    }
}