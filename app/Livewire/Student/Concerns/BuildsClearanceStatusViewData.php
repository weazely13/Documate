<?php

namespace App\Livewire\Student\Concerns;

use App\Models\ClearanceStatus;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Support\Collection;

trait BuildsClearanceStatusViewData
{
    protected function loadStudentWithClearance(int $userId): User
    {
        return User::with('role')->findOrFail($userId);
    }

    protected function fullName(User $user): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->suffix,
        ]))));
    }

    protected function formatYearLevel(string $yearLevel): string
    {
        return match ($yearLevel) {
            '1' => '1st Year',
            '2' => '2nd Year',
            '3' => '3rd Year',
            '4' => '4th Year',
            '', 'N/A' => 'Not set',
            default => $yearLevel,
        };
    }

    protected function periodLabelFor(ClearanceStatus $record): string
    {
        return $record->semesterPeriod
            ? $record->semesterPeriod->label()
            : 'AY ' . ($record->academic_year ?: 'Not set') . ' | ' . ($record->semester ?: 'Not set') . ' Semester';
    }

    protected function buildClearanceHistory(User $user): Collection
    {
        $records = $user->clearanceStatuses()
            ->with(['taggedByUser', 'semesterPeriod'])
            ->orderByDesc('tagged_at')
            ->orderByDesc('id')
            ->get();

        return $records
            ->groupBy(fn (ClearanceStatus $r) => $r->semester_id ?? ('legacy:' . $r->academic_year . ':' . $r->semester))
            ->map(function (Collection $group) {
                $latest = $group->first();

                // Extract Academic Year start year and Semester rank
                $academicYear = $latest->semesterPeriod?->academic_year ?? $latest->academic_year ?? '';
                $semesterName = $latest->semesterPeriod?->semester ?? $latest->semester ?? '';

                return [
                    'status' => $latest->status,
                    'status_word' => $latest->status,
                    'period_label' => $this->periodLabelFor($latest),
                    'is_current' => (bool) ($latest->semesterPeriod?->is_current ?? false),
                    'semester_id' => $latest->semester_id,
                    'academic_year' => $academicYear,
                    'semester' => $semesterName,
                    'tagged_by' => $latest->taggedByUser ? $this->fullName($latest->taggedByUser) : 'System (auto)',
                    'remarks' => $latest->remarks,
                    'last_updated' => $latest->tagged_at?->format('F j, Y, g:i a') ?? 'Not available',
                    'tagged_at' => $latest->tagged_at,
                ];
            })
            ->values();
    }

    protected function buildCurrentClearanceStatus(User $user, ?Collection $historyGroups = null): array
    {
        $historyGroups ??= $this->buildClearanceHistory($user);

        $currentGroup = $historyGroups->firstWhere('is_current', true);

        if ($currentGroup) {
            return [
                'status' => $currentGroup['status'],
                'status_word' => $currentGroup['status_word'],
                'period_label' => $currentGroup['period_label'],
                'is_current' => true,
                'tagged_by' => $currentGroup['tagged_by'],
                'remarks' => $currentGroup['remarks'],
                'last_updated' => $currentGroup['last_updated'],
            ];
        }

        $currentSemester = Semester::current();

        return [
            'status' => 'Not Tagged',
            'status_word' => 'Not Tagged',
            'period_label' => $currentSemester ? $currentSemester->label() : 'No semester is currently set',
            'is_current' => true,
            'tagged_by' => null,
            'remarks' => null,
            'last_updated' => null,
        ];
    }
}