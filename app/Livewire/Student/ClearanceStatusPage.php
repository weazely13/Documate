<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;
use App\Models\ClearanceStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClearanceStatusPage extends Component
{
    use BuildsClearanceStatusViewData;

    public string $historyStatusFilter = '';

    public function render()
    {
        $user = $this->loadStudentWithClearance((int) Auth::id());
        $fullName = $this->fullName($user);
        $history = $this->buildClearanceHistory($user);

        if ($this->historyStatusFilter !== '') {
            $history = $history
                ->where('status', $this->historyStatusFilter)
                ->values();
        }

        return view('livewire.student.clearance-status-page', [
            'user' => $user,
            'fullName' => $fullName,
            'formattedYearLevel' => $this->formatYearLevel((string) $user->year_level),
            'currentStatus' => $this->buildCurrentClearanceStatus($user),
            'history' => $history,
            'statusOptions' => collect(ClearanceStatus::STATUSES),
        ])->layout('layouts.app', ['title' => 'Clearance Status']);
    }
}
