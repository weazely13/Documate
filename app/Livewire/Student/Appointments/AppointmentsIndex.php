<?php

namespace App\Livewire\Student\Appointments;

use App\Models\Appointment;
use App\Models\OfficeAvailability;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class AppointmentsIndex extends Component
{
    public ?int $retractingId = null;

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
        $this->retractingId = null;
    }

    // Refresh listener for event broadcasting or livewire polling
    #[On('queue-updated')]
    public function refreshQueue(): void
    {
        // Triggers re-render automatically
    }

    public function render()
    {
        $appointments = Appointment::with('workspace.template')
            ->where('user_id', Auth::id())
            ->orderByDesc('appointment_date')
            ->get();

        $pending = $appointments->whereIn('status', ['pending', 'approved'])->map(function ($appt) {
            if ($appt->status === 'approved' && $appt->appointment_date) {
                // Fetch the availability record for the specific appointment date
                $availability = OfficeAvailability::where('date', $appt->appointment_date->toDateString())->first();
                
                // Get now_serving for morning/afternoon session
                $servingNumber = $availability?->{'now_serving_' . $appt->session};

                $appt->setAttribute('now_serving', $servingNumber);
            }
            return $appt;
        });

        return view('livewire.student.appointments.appointments-index', [
            'pending' => $pending,
            'history' => $appointments->whereIn('status', ['rejected', 'attended', 'missed', 'retracted']),
        ])->layout('layouts.app', ['title' => 'Appointments']);
    }
}