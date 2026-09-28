<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use App\Support\BuildsAppointmentTimeline;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AppointmentShow extends Component
{
    use BuildsAppointmentTimeline;

    public Appointment $appointment;

    public function mount(Appointment $appointment): void
    {
        abort_unless($appointment->user_id === Auth::id(), 403);
        $this->appointment = $appointment->load('workspace.template', 'reviewer', 'reschedules', 'reapplications', 'statusLogs.changedBy');
        $this->appointment->refreshMissedStatusIfLapsed();
    }

    #[Computed]
    public function timeline(): array
    {
        return $this->buildTimeline($this->appointment);
    }

    #[Computed]
    public function queueList()
    {
        if ($this->appointment->status !== 'approved') {
            return collect();
        }

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
        return \App\Models\OfficeAvailability::nowServingFor(
            $this->appointment->appointment_date->toDateString(),
            $this->appointment->session
        );
    }

    public function refreshStatus(): void
    {
        $this->appointment->refresh();
        unset($this->queueList, $this->currentServingNumber);
    }

    public function render()
    {
        return view('livewire.student.appointments.appointment-show')
            ->layout('layouts.app', ['title' => 'Appointment Details']);
    }
}