<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesClearanceTagging;
use App\Models\ClearanceStatus;
use App\Models\Semester;
use App\Models\User;
use Livewire\Component;

class ClearanceProfile extends Component
{
    use ManagesClearanceTagging;

    public int $studentId;

    public ?int $tagSemesterId = null;
    public string $tagStatus = '';
    public string $tagRemarks = '';

    public function mount(int $studentId): void
    {
        if (! $this->monitoredUsersQuery()->whereKey($studentId)->exists()) {
            abort(403, 'You are not allowed to view this student.');
        }

        $this->studentId = $studentId;
        $this->tagSemesterId = Semester::current()?->id;
    }

    public function submitTag(): void
    {
        if (! $this->tagSemesterId) {
            $this->dispatch('alert', type: 'error', message: 'Select a semester first.');

            return;
        }

        if ($this->createClearanceRecord($this->studentId, $this->tagSemesterId, $this->tagStatus, $this->tagRemarks ?: null)) {
            $this->tagStatus = '';
            $this->tagRemarks = '';
            $this->dispatch('alert', type: 'success', message: 'Clearance status tagged.');
        }
    }

    public function render()
    {
        $user = User::with(['role', 'program'])->findOrFail($this->studentId);

        return view('livewire.admin.clearance-profile', [
            'user' => $user,
            'fullName' => $this->fullName($user),
            'yearLevelLabel' => $this->formatYearLevel((string) $user->year_level),
            'programLabel' => $user->program?->name
                ?? ($user->legacy_program ? $this->formatProgram($user->legacy_program) : 'Not set'),
            'historyGroups' => $this->buildHistoryGroups($user),
            'statuses' => collect(ClearanceStatus::STATUSES),
            'semesters' => Semester::orderByDesc('school_year')->orderBy('semester_label')->get(),
            'backRoute' => 'admin.clearance-monitoring',
        ])->layout('layouts.app', ['title' => 'Clearance Profile']);
    }
}
