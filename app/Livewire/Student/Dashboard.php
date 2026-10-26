<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;
use App\Models\Appointment;
use App\Models\OfficeAvailability;
use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Support\DocumentProgress;
use App\Workflow\Documents\DocumentRegistry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * The student's home: what needs them now, where their documents stand,
 * their next appointment, clearance and account status.
 */
class Dashboard extends Component
{
    use BuildsClearanceStatusViewData;

    private const FINISHED = ['completed', 'rejected', 'voided'];

    /** Profile details most forms print, checked so the student is not blocked at submit time. */
    private const PROFILE_FIELDS = [
        'contact_number' => 'contact number',
        'address' => 'address',
        'program_id' => 'program',
        'year_level' => 'year level',
        'section' => 'section',
    ];

    private function documents(int $userId): Builder
    {
        return StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->with(['template', 'approvals.role'])
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ]);
    }

    /** Documents still moving, the ones needing the student first. */
    private function activeDocuments(int $userId): Collection
    {
        return $this->documents($userId)
            ->where(fn (Builder $w) => $w
                ->where(fn (Builder $x) => $x->whereNotNull('workflow_status')->whereNotIn('workflow_status', self::FINISHED))
                ->orWhere(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', '!=', 'completed')))
            ->orderByRaw("CASE workflow_status WHEN 'action_required' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->get();
    }

    private function nextAppointment(int $userId): ?array
    {
        $appointment = Appointment::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'pending'])
            ->whereDate('appointment_date', '>=', today())
            ->orderBy('appointment_date')
            ->orderBy('session')
            ->first();

        if (! $appointment) {
            return null;
        }

        $date = Carbon::parse($appointment->appointment_date);

        return [
            'date' => $date,
            'is_today' => $date->isToday(),
            'session' => ucfirst((string) $appointment->session),
            'status' => $appointment->status,
            'queue_number' => $appointment->queue_number,
            'now_serving' => $date->isToday() && $appointment->session
                ? OfficeAvailability::nowServingFor($date->toDateString(), $appointment->session)
                : null,
            'purpose' => $appointment->purpose,
        ];
    }

    /**
     * Things only the student can move forward, most urgent first.
     *
     * @return array<int, array{tone: string, title: string, text: string, url: string, action: string}>
     */
    private function attention(User $user, Collection $active, ?array $appointment, array $clearance, Collection $missingProfile): array
    {
        $items = [];

        foreach ($active as $ws) {
            $p = DocumentProgress::describe($ws);
            $status = $ws->workflow_status?->value;

            if ($status === 'action_required') {
                $items[] = ['tone' => 'amber', 'title' => 'Changes needed: ' . $p['title'], 'text' => $p['next'], 'url' => $p['href'], 'action' => 'Update'];
            } elseif ($status === 'draft') {
                $items[] = ['tone' => 'amber', 'title' => 'Not sent yet: ' . $p['title'], 'text' => 'Finish the form and submit it to the VPSD office.', 'url' => $p['href'], 'action' => 'Continue'];
            }
        }

        foreach (StudentDocumentWorkspace::officeNoticesFor($user->id) as $notice) {
            $doc = DocumentRegistry::get($notice->document_type);
            $values = $notice->field_values ?? [];
            $items[] = [
                'tone' => $doc->noticeTone() === 'rose' ? 'rose' : 'blue',
                'title' => $doc->noticeTitle($values),
                'text' => $doc->noticeLine($values),
                'url' => route('student.transactions.show', $notice->workspace_id),
                'action' => 'Open',
            ];
        }

        if ($appointment && $appointment['is_today'] && $appointment['status'] === 'approved') {
            $text = $appointment['session'] . ' session'
                . ($appointment['queue_number'] ? ' · your number is ' . $appointment['queue_number'] : '')
                . ($appointment['now_serving'] ? ' · now serving ' . $appointment['now_serving'] : '');
            $items[] = ['tone' => 'blue', 'title' => 'Your appointment is today', 'text' => $text, 'url' => route('student.appointments.index'), 'action' => 'View'];
        }

        if (($clearance['status'] ?? '') === 'Uncleared') {
            $items[] = [
                'tone' => 'rose',
                'title' => 'You are not yet cleared for ' . ($clearance['period_label'] ?? 'this semester'),
                'text' => $clearance['remarks'] ?: 'See which office has a hold on your clearance.',
                'url' => route('student.clearance-status'),
                'action' => 'Details',
            ];
        }

        if ($missingProfile->isNotEmpty()) {
            $items[] = ['tone' => 'slate', 'title' => 'Complete your profile', 'text' => 'Missing ' . $missingProfile->implode(', ') . '. Forms print these details.', 'url' => route('profile'), 'action' => 'Update'];
        }

        if (! $user->signature_path) {
            $items[] = ['tone' => 'slate', 'title' => 'Add your e-signature', 'text' => 'Several forms are signed with it. Save it once in your profile.', 'url' => route('profile'), 'action' => 'Add'];
        }

        return $items;
    }

    public function render()
    {
        $userId = (int) Auth::id();
        $user = $this->loadStudentWithClearance($userId);
        $user->loadMissing(['program', 'role']);

        $clearance = $this->buildCurrentClearanceStatus($user);
        $active = $this->activeDocuments($userId);
        $appointment = $this->nextAppointment($userId);

        $missing = collect(self::PROFILE_FIELDS)
            ->filter(fn ($label, $field) => blank($user->{$field}))
            ->values();

        return view('livewire.student.dashboard', [
            'user' => $user,
            'firstName' => $user->first_name ?: $this->fullName($user),
            'facts' => collect([
                $user->student_number,
                $user->program?->name,
                $user->year_level ? $this->formatYearLevel((string) $user->year_level) . ($user->section ? ', Section ' . $user->section : '') : null,
            ])->filter()->values()->all(),
            'attention' => $this->attention($user, $active, $appointment, $clearance, $missing),
            'active' => $active->take(3)->map(fn ($ws) => DocumentProgress::describe($ws) + ['id' => $ws->workspace_id]),
            'activeCount' => $active->count(),
            'appointment' => $appointment,
            'clearance' => $clearance,
        ])->layout('layouts.app', ['title' => 'Dashboard']);
    }
}
