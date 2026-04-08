<?php

namespace App\Livewire\Student\Concerns;

use App\Models\ClearanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait BuildsClearanceStatusViewData
{
    protected const MONITORED_ROLES = ['Student', 'Officer'];

    protected function loadStudentWithClearance(int $userId): User
    {
        return User::query()
            ->with([
                'role',
                'clearanceStatus.taggedByUser',
                'clearanceStatuses.taggedByUser',
            ])
            ->findOrFail($userId);
    }

    protected function buildCurrentClearanceStatus(User $user): array
    {
        $fallback = $this->defaultStatusData($user);
        $statusRecord = $user->clearanceStatus;
        $organization = $user->organization ?: ($statusRecord?->organization ?: $fallback['organization']);
        $status = $statusRecord?->status ?: $fallback['status'];
        $academicYear = $statusRecord?->academic_year ?: $fallback['academic_year'];
        $semester = $statusRecord?->semester ?: $fallback['semester'];

        return [
            'status' => $status,
            'status_word' => Str::upper($status),
            'organization' => $organization,
            'academic_year' => $academicYear,
            'semester' => $semester,
            'period_label' => 'AY ' . $academicYear . ' | ' . Str::upper($semester) . ' SEMESTER',
            'tagged_by' => $statusRecord?->taggedByUser ? $this->fullName($statusRecord->taggedByUser) : '',
            'remarks' => $statusRecord?->remarks ?: '',
            'last_updated' => $statusRecord?->tagged_at ? $statusRecord->tagged_at->format('F j, Y') : 'Awaiting admin update',
            'status_updated_at' => $statusRecord?->tagged_at ? $statusRecord->tagged_at->format('F j, Y, g:i a') : 'Awaiting admin update',
            'is_generated' => ! $statusRecord,
        ];
    }

    protected function buildClearanceHistory(User $user): Collection
    {
        return $user->clearanceStatuses
            ->sortByDesc(function (ClearanceStatus $status) {
                return sprintf('%020d-%020d', $status->tagged_at?->timestamp ?? 0, $status->id);
            })
            ->values()
            ->map(function (ClearanceStatus $status) {
                $organization = $status->organization ?: 'Unassigned';

                return [
                    'id' => $status->id,
                    'status' => $status->status,
                    'status_word' => Str::upper($status->status),
                    'organization' => $organization,
                    'academic_year' => $status->academic_year ?: ($this->primaryAcademicYear() ?: 'Not set'),
                    'semester' => $status->semester ?: $this->primarySemester(),
                    'period_label' => 'AY ' . ($status->academic_year ?: ($this->primaryAcademicYear() ?: 'Not set')) . ' | ' . Str::upper(($status->semester ?: $this->primarySemester())) . ' SEMESTER',
                    'tagged_by' => $status->taggedByUser ? $this->fullName($status->taggedByUser) : 'System Administrator',
                    'remarks' => $status->remarks ?: $this->remarksFor($status->status, $organization),
                    'last_updated' => $status->tagged_at ? $status->tagged_at->format('F j, Y') : 'Not available',
                    'status_updated_at' => $status->tagged_at ? $status->tagged_at->format('F j, Y, g:i a') : 'Not available',
                ];
            });
    }

    protected function fullName(User $user): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
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

    protected function primaryAcademicYear(): string
    {
        if (currentAcademicYear()) {
            return currentAcademicYear();
        }

        $year = now()->year - 1;

        return $year . '-' . ($year + 1);
    }

    protected function secondaryAcademicYear(): string
    {
        [$startYear] = explode('-', $this->primaryAcademicYear());
        $previousStart = ((int) $startYear) - 1;

        return $previousStart . '-' . ($previousStart + 1);
    }

    protected function primarySemester(): string
    {
        $semester = Str::lower((string) currentSemester());

        if (Str::contains($semester, 'first') || Str::contains($semester, '1st')) {
            return 'First';
        }

        return 'Second';
    }

    protected function secondarySemester(): string
    {
        return $this->primarySemester() === 'First' ? 'Second' : 'First';
    }

    protected function remarksFor(string $status, string $organization): string
    {
        return match ($status) {
            ClearanceStatus::STATUS_CLEARED => 'No pending organization obligations for ' . $organization . '.',
            ClearanceStatus::STATUS_PENDING => 'Awaiting final officer review for ' . $organization . '.',
            ClearanceStatus::STATUS_UNCLEARED => 'Outstanding clearance requirements remain under ' . $organization . '.',
            default => 'No remarks available.',
        };
    }

    protected function defaultStatusData(User $user): array
    {
        $userIds = $this->monitoredUsersQuery()->pluck('id');
        $index = $userIds->search($user->id);
        $statusCycle = ClearanceStatus::STATUSES;
        $semesterOptions = [$this->primarySemester(), $this->secondarySemester()];
        $academicYearOptions = [$this->primaryAcademicYear(), $this->secondaryAcademicYear()];

        if ($index === false) {
            $index = 0;
        }

        return [
            'organization' => $user->organization ?: 'Unassigned',
            'status' => $statusCycle[$index % count($statusCycle)],
            'academic_year' => $academicYearOptions[$index % count($academicYearOptions)],
            'semester' => $semesterOptions[$index % count($semesterOptions)],
        ];
    }

    protected function monitoredUsersQuery(): Builder
    {
        return User::query()
            ->whereHas('role', fn (Builder $query) => $query->whereIn('role_name', self::MONITORED_ROLES))
            ->orderBy('organization')
            ->orderByRaw("CASE WHEN year_level REGEXP '^[0-9]+$' THEN CAST(year_level AS UNSIGNED) ELSE 999 END ASC")
            ->orderBy('last_name')
            ->orderBy('first_name');
    }
}
