<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;
use App\Models\ClearanceStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClearanceStatusPage extends Component
{
    use BuildsClearanceStatusViewData;

    public string $semesterFilter = '';
    public string $historyStatusFilter = '';
    public string $sortBy = 'latest';

    public function render()
    {
        $user = $this->loadStudentWithClearance((int) Auth::id());
        $fullName = $this->fullName($user);

        // Build full history collection first (used to extract current status safely)
        $fullHistory = $this->buildClearanceHistory($user);

        // Build Current Clearance Hero Data (always accurate, ignores filters)
        $currentStatus = $this->buildCurrentClearanceStatus($user, $fullHistory);

        // Clone history collection for filtering & sorting
        $history = clone $fullHistory;

        // 1. Filter by Semester Label
        if ($this->semesterFilter !== '') {
            $history = $history->filter(fn (array $item) => $item['period_label'] === $this->semesterFilter);
        }

        // 2. Filter by Status (Cleared, Pending, Uncleared)
        if ($this->historyStatusFilter !== '') {
            $history = $history->filter(fn (array $item) => $item['status'] === $this->historyStatusFilter);
        }

        /// 3. Apply Dynamic Academic Chronological Sorting
        $history = $history->sort(function (array $a, array $b) {
            // Keep active term at top for latest sort; invert for oldest sort
            if ($a['is_current'] !== $b['is_current']) {
                return $this->sortBy === 'oldest' 
                    ? ($a['is_current'] ? 1 : -1) 
                    : ($a['is_current'] ? -1 : 1);
            }

            // Helper: Extract starting numeric year (e.g., "2025-2026" -> 2025)
            $getStartYear = function (string $ay) {
                preg_match('/\d{4}/', $ay, $matches);
                return isset($matches[0]) ? (int) $matches[0] : 0;
            };

            // Helper: Map term names to chronological rank
            $getSemesterRank = function (string $sem) {
                $normalized = strtolower($sem);
                if (str_contains($normalized, '1st') || str_contains($normalized, 'first')) return 1;
                if (str_contains($normalized, '2nd') || str_contains($normalized, 'second')) return 2;
                if (str_contains($normalized, '3rd') || str_contains($normalized, 'third')) return 3;
                if (str_contains($normalized, 'summer') || str_contains($normalized, 'midyear')) return 4;
                return 0;
            };

            $yearA = $getStartYear($a['academic_year']);
            $yearB = $getStartYear($b['academic_year']);

            if ($yearA !== $yearB) {
                return $this->sortBy === 'oldest' ? ($yearA <=> $yearB) : ($yearB <=> $yearA);
            }

            $rankA = $getSemesterRank($a['semester']);
            $rankB = $getSemesterRank($b['semester']);

            return $this->sortBy === 'oldest' ? ($rankA <=> $rankB) : ($rankB <=> $rankA);
        })->values();

        // Extract available dynamic semester options for the dropdown select
        $semesterOptions = $fullHistory->pluck('period_label')->unique()->values();

        return view('livewire.student.clearance-status-page', [
            'user' => $user,
            'fullName' => $fullName,
            'formattedYearLevel' => $this->formatYearLevel((string) $user->year_level),
            'currentStatus' => $currentStatus,
            'history' => $history,
            'semesterOptions' => $semesterOptions,
            'statusOptions' => collect(ClearanceStatus::STATUSES),
        ])->layout('layouts.app', ['title' => 'Clearance Status']);
    }
}