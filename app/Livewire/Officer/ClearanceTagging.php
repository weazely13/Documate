<?php

namespace App\Livewire\Officer;

use App\Livewire\Admin\ClearanceMonitoring;
use App\Models\ClearanceStatus;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;

class ClearanceTagging extends ClearanceMonitoring
{
    /**
     * Officers may only ever see/tag students in their own organization.
     * This single override locks down listing, direct-URL profile
     * access, and the write path (via createClearanceRecord in the
     * shared trait, which re-checks this before creating a row).
     */
    protected function organizationScope(): ?int
    {
        return Auth::user()->organization_id;
    }

    protected function profileRouteName(): string
    {
        return 'officer.clearance.show';
    }

    public function render()
    {
        $allRecords = $this->buildRecords();
        $filteredRecords = $this->filteredRecords();
        $totalPages = max(1, (int) ceil($filteredRecords->count() / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $records = $filteredRecords->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();

        return view('livewire.officer.clearance-tagging', [
            'records' => $records,
            'organizations' => $allRecords->pluck('organization')->unique()->sort()->values(),
            'statuses' => collect(ClearanceStatus::STATUSES),
            'filterStatuses' => collect(ClearanceStatus::STATUSES)->push(self::STATUS_NOT_TAGGED),
            'semesters' => Semester::orderByDesc('school_year')->orderBy('semester_label')->get(),
            'totalPages' => $totalPages,
            'pageHeading' => 'Clearance Tagging',
            'pageDescription' => 'Tag and review clearance statuses for students in your organization',
        ])->layout('layouts.app', ['title' => 'Clearance Tagging']);
    }
}