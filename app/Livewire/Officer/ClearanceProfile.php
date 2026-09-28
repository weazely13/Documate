<?php

namespace App\Livewire\Officer;

use App\Livewire\Admin\ClearanceProfile as AdminClearanceProfile;
use App\Models\ClearanceStatus;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ClearanceProfile extends AdminClearanceProfile
{
    protected function organizationScope(): ?int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        $user = User::with(['role', 'program'])->findOrFail($this->studentId);

        return view('livewire.officer.clearance-profile', [
            'user' => $user,
            'fullName' => $this->fullName($user),
            'yearLevelLabel' => $this->formatYearLevel((string) $user->year_level),
            'programLabel' => $user->program?->name
                ?? ($user->legacy_program ? $this->formatProgram($user->legacy_program) : 'Not set'),
            'historyGroups' => $this->buildHistoryGroups($user),
            'statuses' => collect(ClearanceStatus::STATUSES),
            'semesters' => Semester::orderByDesc('school_year')->orderBy('semester_label')->get(),
            'backRoute' => 'officer.clearance',
        ])->layout('layouts.app', ['title' => 'Clearance Profile']);
    }
}