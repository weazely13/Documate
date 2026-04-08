<?php

namespace App\Livewire\Officer;

use App\Livewire\Admin\ClearanceMonitoring;
use App\Models\ClearanceStatus;

class ClearanceTagging extends ClearanceMonitoring
{
    public function render()
    {
        $allRecords = $this->buildRecords();
        $filteredRecords = $this->filteredRecords();
        $totalPages = max(1, (int) ceil($filteredRecords->count() / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $records = $filteredRecords
            ->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)
            ->values();

        return view('livewire.admin.clearance-monitoring', [
            'records' => $records,
            'organizations' => $allRecords->pluck('organization')->unique()->sort()->values(),
            'academicYears' => $allRecords->pluck('academic_year')->unique()->sortDesc()->values(),
            'semesters' => $allRecords->pluck('semester')->unique()->values(),
            'statuses' => collect(ClearanceStatus::STATUSES),
            'totalPages' => $totalPages,
            'pageHeading' => 'Clearance Tagging',
            'pageDescription' => 'Tag and review clearance statuses submitted per academic organization',
        ])->layout('layouts.app', ['title' => 'Clearance Tagging']);
    }
}
