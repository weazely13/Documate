<?php

namespace App\Livewire\Admin\Appointments;

use App\Models\Appointment;
use App\Support\BuildsAppointmentTimeline;
use App\Models\AppointmentRejectionTemplate;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection; // <-- ADD THIS
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;

class AppointmentShow extends Component
{
    use BuildsAppointmentTimeline, BuildsClearanceStatusViewData; // <-- keep only ONE use line, remove the duplicate

    public Appointment $appointment;
    // ...rest unchanged
    public bool $showReject = false;
    public bool $showReschedule = false;
    public string $rejectionReason = '';
    public ?string $rescheduleDate = null;
    public ?string $rescheduleSession = null;
    public string $rescheduleReason = '';
    public string $adminNotes = '';
    public bool $showAttendConfirm = false;

    public string $clearanceSemesterFilter = '';


    public bool $showApproveConfirm = false; // NEW
    public string $rejectionReasonOption = '';
    public string $rescheduleReasonOption = ''; 

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment->load('user', 'workspace.template', 'reschedules', 'reapplications', 'statusLogs.changedBy');
        $this->appointment->refreshMissedStatusIfLapsed();

        if (
            $this->appointment->status === 'approved'
            && $this->appointment->queue_number
            && $this->appointment->appointment_date->isToday()
        ) {
            \App\Models\OfficeAvailability::updateOrCreate(
                ['date' => $this->appointment->appointment_date->toDateString()],
                ['now_serving_' . $this->appointment->session => $this->appointment->queue_number]
            );
        }
    }
    public function rerunAiReview(): void
   {
       $this->appointment->update(['ai_flag' => 'pending']);
       \App\Jobs\ReviewAppointmentSubmission::dispatch($this->appointment);
       $this->appointment->refresh();
   }
   public function useRejectionOption(string $value): void
    {
        $this->rejectionReasonOption = $value;
        if ($value !== 'Custom') {
            $this->rejectionReason = $value;
        } else {
            $this->rejectionReason = '';
        }
    }

    public function useRescheduleOption(string $value): void
    {
        $this->rescheduleReasonOption = $value;
        if ($value !== 'Custom') {
            $this->rescheduleReason = $value;
        } else {
            $this->rescheduleReason = '';
        }
    }

    #[Computed]
    public function clearanceHistory(): Collection
    {
        return $this->buildClearanceHistory($this->appointment->user);
    }

    // NEW — dropdown options
    #[Computed]
    public function clearanceSemesterOptions(): Collection
    {
        return $this->clearanceHistory->pluck('period_label')->unique()->values();
    }

    // NEW — the single record to display: current term by default,
    // or whichever semester the admin switched to
    #[Computed]
    public function selectedClearance(): array
    {
        if ($this->clearanceSemesterFilter === '') {
            return $this->buildCurrentClearanceStatus($this->appointment->user, $this->clearanceHistory);
        }

        return $this->clearanceHistory->firstWhere('period_label', $this->clearanceSemesterFilter) ?? [
            'status' => 'Not Tagged',
            'status_word' => 'Not Tagged',
            'period_label' => $this->clearanceSemesterFilter,
            'is_current' => false,
            'tagged_by' => null,
            'remarks' => null,
            'last_updated' => null,
        ];
    }

    #[Computed]
    public function isNowServing(): bool
    {
        if (!$this->appointment->queue_number || !$this->appointment->appointment_date->isToday()) {
            return false;
        }

        $current = \App\Models\OfficeAvailability::nowServingFor(
            $this->appointment->appointment_date->toDateString(),
            $this->appointment->session
        );

        return $current === $this->appointment->queue_number;
    }

    public function useTemplate(int $templateId): void
    {
        $this->rejectionReason = AppointmentRejectionTemplate::find($templateId)?->message ?? '';
    }

    public function approve(): void
    {
        $nextQueueNumber = (Appointment::where('appointment_date', $this->appointment->appointment_date)
            ->where('session', $this->appointment->session)
            ->whereNotNull('queue_number')
            ->max('queue_number') ?? 0) + 1;

        $this->appointment->update([
            'status' => 'approved',
            'queue_number' => $nextQueueNumber,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->appointment->logStatus('approved', null, Auth::id());
        $this->appointment->user->notify(new AppointmentStatusChanged($this->appointment, 'approved'));
        $this->appointment->refresh();
    }

    public function reject(): void
    {
        $this->validate(['rejectionReason' => 'required|string|min:5']);

        $this->appointment->update([
            'status' => 'rejected',
            'rejection_reason' => $this->rejectionReason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->appointment->logStatus('rejected', $this->rejectionReason, Auth::id());
        $this->appointment->user->notify(new AppointmentStatusChanged($this->appointment, 'rejected'));
        $this->showReject = false;
        $this->appointment->refresh();
    }

    public function reschedule(): void
    {
        $this->validate([
            'rescheduleDate' => 'required|date|after_or_equal:today',
            'rescheduleSession' => 'required|in:morning,afternoon',
            'rescheduleReason' => 'required|string|min:5',
        ]);

        \App\Models\AppointmentReschedule::create([
            'appointment_id' => $this->appointment->appointment_id,
            'old_date' => $this->appointment->appointment_date,
            'old_session' => $this->appointment->session,
            'new_date' => $this->rescheduleDate,
            'new_session' => $this->rescheduleSession,
            'reason' => $this->rescheduleReason,
            'rescheduled_by' => Auth::id(),
        ]);

        $this->appointment->update([
            'appointment_date' => $this->rescheduleDate,
            'session' => $this->rescheduleSession,
            'queue_number' => null, // cleared — reassigned only when re-approved
            'status' => 'pending',
            'reschedule_reason' => $this->rescheduleReason,
            'reschedule_count' => $this->appointment->reschedule_count + 1,
        ]);

        $this->appointment->logStatus('pending', 'Rescheduled to ' . $this->rescheduleDate . ' (' . $this->rescheduleSession . ').', Auth::id());
        $this->appointment->user->notify(new AppointmentStatusChanged($this->appointment, 'rescheduled'));
        $this->showReschedule = false;
        $this->appointment->refresh();
    }

    public function markAttended(): void
    {
        $this->appointment->update([
            'status' => 'attended',
            'admin_notes' => $this->adminNotes ?: $this->appointment->admin_notes,
        ]);

        $this->appointment->logStatus('attended', $this->adminNotes ?: null, Auth::id());

        if ($this->appointment->workspace) {
            $this->appointment->workspace->update(['status' => 'completed', 'completed_at' => now()]);
        }

        $this->showAttendConfirm = false;
        $this->appointment->refresh();
    }
        #[Computed]
    public function timeline(): array
    {
        return $this->buildTimeline($this->appointment);
    }
    public function render()
    {
        return view('livewire.admin.appointments.appointment-show', [
            'templates' => AppointmentRejectionTemplate::all(),
        ])->layout('layouts.app', ['title' => 'Appointment Review']);
    }
}