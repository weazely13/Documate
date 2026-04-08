<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    use BuildsClearanceStatusViewData;

    public function render()
    {
        $user = $this->loadStudentWithClearance((int) Auth::id());
        $fullName = $this->fullName($user);

        return view('livewire.student.dashboard', [
            'user' => $user,
            'fullName' => $fullName,
            'formattedYearLevel' => $this->formatYearLevel((string) $user->year_level),
            'currentClearanceStatus' => $this->buildCurrentClearanceStatus($user),
        ])->layout('layouts.app', ['title' => 'Student Dashboard']);
    }
}
