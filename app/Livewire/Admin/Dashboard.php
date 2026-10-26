<?php

namespace App\Livewire\Admin;

use App\Models\Appointment;
use App\Models\ClearanceStatus;
use App\Models\OfficeAvailability;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\StudentDocumentWorkspace;
use App\Models\StudentVerification;
use App\Models\User;
use App\Support\ReviewQueue;
use App\Support\WaitingTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * The office's overview: what needs action now, how long things have waited,
 * today's queue, and this semester's clearance and verification.
 */
class Dashboard extends Component
{
    private const STUDENT_ROLES = ['Student', 'Officer'];

    private function students(): Builder
    {
        return User::query()
            ->where('account_status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', self::STUDENT_ROLES));
    }

    /** Counts that each lead to a page where someone has to act. */
    private function workload(): array
    {
        return [
            'to_review' => ReviewQueue::query('attention')->count(),
            'overdue' => ReviewQueue::overdue()->count(),
            'ready' => ReviewQueue::base()->where('workflow_status', 'approved')->count(),
            'signing' => ReviewQueue::query('signing')->count(),
            'signing_late' => ReviewQueue::apply(ReviewQueue::overdue(), 'signing')->count(),
            'appointment_requests' => Appointment::where('status', 'pending')->count(),
            // Paper forms whose visit is done but whose signed copy has not been checked yet.
            'uploads' => StudentDocumentWorkspace::query()
                ->whereNull('workflow_status')
                ->where('status', '!=', 'completed')
                ->whereHas('appointments', fn ($q) => $q->where('status', 'attended'))
                ->where(fn ($q) => $q->whereNull('analysis_status')->orWhere('analysis_status', '!=', 'approved'))
                ->count(),
        ];
    }

    /** How long the open queue has been waiting, in the same bands as Workflow Review. */
    private function waitingBreakdown(): array
    {
        $bands = ['new' => 0, 'due' => 0, 'follow_up' => 0, 'urgent' => 0, 'overdue' => 0];

        ReviewQueue::withWaitingSince(
            ReviewQueue::base()->where(fn ($w) => ReviewQueue::apply($w, 'attention')
                ->orWhere(fn ($x) => ReviewQueue::apply($x, 'signing')))
        )->get(['workspace_id', 'submitted_at'])->each(function ($ws) use (&$bands) {
            $bands[WaitingTime::level(WaitingTime::schoolDays(ReviewQueue::waitingSince($ws))) ?? 'new']++;
        });

        return $bands;
    }

    private function today(): array
    {
        $appointments = Appointment::query()
            ->whereDate('appointment_date', today())
            ->whereNotIn('status', ['retracted', 'rejected'])
            ->get(['session', 'status']);

        $sessions = collect(['morning', 'afternoon'])->mapWithKeys(fn ($s) => [$s => [
            'total' => $appointments->where('session', $s)->count(),
            'attended' => $appointments->where('session', $s)->where('status', 'attended')->count(),
            'now_serving' => OfficeAvailability::nowServingFor(today()->toDateString(), $s),
        ]]);

        return [
            'sessions' => $sessions,
            'total' => $appointments->count(),
        ];
    }

    private function clearance(?Semester $semester): array
    {
        $total = $this->students()->count();

        if (! $semester) {
            return ['total' => $total, 'Cleared' => 0, 'Pending' => 0, 'Uncleared' => 0, 'not_tagged' => $total];
        }

        // A student can have several records for a semester: their latest one counts.
        // Only active students, the same group as the total.
        $latest = ClearanceStatus::query()
            ->where('semester_id', $semester->id)
            ->whereIn('user_id', $this->students()->select('id'))
            ->orderBy('id')
            ->get(['user_id', 'status'])
            ->keyBy('user_id');

        $counts = $latest->countBy('status');

        return [
            'total' => $total,
            'Cleared' => $counts['Cleared'] ?? 0,
            'Pending' => $counts['Pending'] ?? 0,
            'Uncleared' => $counts['Uncleared'] ?? 0,
            'not_tagged' => max(0, $total - $latest->count()),
        ];
    }

    private function verification(?Setting $period): ?array
    {
        if (! $period) {
            return null;
        }

        $total = $this->students()->count();
        $verified = $this->students()
            ->whereHas('verifications', fn ($q) => $q
                ->where('status', StudentVerification::STATUS_VERIFIED)
                ->where(fn ($p) => $p->where('setting_id', $period->id)
                    ->orWhere(fn ($old) => $old->whereNull('setting_id')
                        ->where('semester', $period->current_semester)
                        ->where('academic_year', $period->academic_year))))
            ->count();

        return [
            'total' => $total,
            'verified' => $verified,
            'deadline' => $period->verification_end_date ? Carbon::parse($period->verification_end_date) : null,
        ];
    }

    public function render()
    {
        $semester = Semester::current();
        $period = Setting::query()->latest('id')->first();
        $verification = $this->verification($period);

        return view('livewire.admin.dashboard', [
            'firstName' => auth()->user()?->first_name ?: 'there',
            'semesterLabel' => $semester?->label(),
            'work' => $this->workload(),
            'waiting' => $this->waitingBreakdown(),
            'today' => $this->today(),
            'clearance' => $this->clearance($semester),
            'verification' => $verification,
            'unverified' => $verification ? max(0, $verification['total'] - $verification['verified']) : 0,
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
