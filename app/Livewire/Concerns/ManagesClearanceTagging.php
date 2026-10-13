<?php

namespace App\Livewire\Concerns;

use App\Models\ClearanceStatus;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ManagesClearanceTagging
{
    protected const MONITORED_ROLES = ['Student', 'Officer'];

    /**
     * Base (admin) sees everyone. Officer components override this to
     * return the logged-in officer's own organization_id, which locks
     * down listing, direct-URL profile access, AND the write path.
     */
    protected function organizationScope(): ?int
    {
        return null;
    }

    protected function monitoredUsersQuery(): Builder
    {
        return User::query()
            ->select('users.*')
            ->leftJoin('organizations', 'organizations.id', '=', 'users.organization_id')
            ->whereHas('role', fn (Builder $query) => $query->whereIn('role_name', self::MONITORED_ROLES))
            ->when(
                $this->organizationScope(),
                fn (Builder $query, int $orgId) => $query->where('users.organization_id', $orgId)
            )
            ->orderBy('organizations.name')
            ->orderByRaw("CASE WHEN users.year_level REGEXP '^[0-9]+$' THEN CAST(users.year_level AS UNSIGNED) ELSE 999 END ASC")
            ->orderBy('users.last_name')
            ->orderBy('users.first_name');
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

    protected function formatProgram(string $program): string
    {
        return match ($program) {
            'Bachelor of Science in Information Technology' => 'Bachelor of Science in Information Technology',
            'BSIT' => 'Bachelor of Science in Information Technology',
            '' => 'No program on file',
            default => $program,
        };
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

    protected function remarksFor(string $status, string $organization): string
    {
        return match ($status) {
            ClearanceStatus::STATUS_CLEARED => 'No pending organization obligations for ' . $organization . '.',
            ClearanceStatus::STATUS_PENDING => 'Awaiting final officer review for ' . $organization . '.',
            ClearanceStatus::STATUS_UNCLEARED => 'Outstanding clearance requirements remain under ' . $organization . '.',
            default => 'No remarks available.',
        };
    }

    protected function createClearanceRecord(int $userId, int $semesterId, string $status, ?string $remarks): bool
    {
        if (! in_array($status, ClearanceStatus::STATUSES, true)) {
            $this->dispatch('alert', type: 'error', message: 'Select a valid clearance status.');

            return false;
        }

        $semester = Semester::find($semesterId);

        if (! $semester) {
            $this->dispatch('alert', type: 'error', message: 'Select a valid semester.');

            return false;
        }

        if (! $this->monitoredUsersQuery()->whereKey($userId)->exists()) {
            $this->dispatch('alert', type: 'error', message: 'You are not allowed to tag this student.');

            return false;
        }

        $user = User::with(['role', 'organization'])
            ->whereKey($userId)
            ->whereHas('role', fn (Builder $query) => $query->whereIn('role_name', self::MONITORED_ROLES))
            ->first();

        if (! $user) {
            return false;
        }

        // Belt-and-suspenders: re-check organization_id directly against the
        // fetched model, don't rely solely on the pre-filtered query above.
        $scope = $this->organizationScope();

        if ($scope !== null && $user->organization_id !== $scope) {
            $this->dispatch('alert', type: 'error', message: 'You can only tag students in your own organization.');

            return false;
        }

        $organization = $user->organization?->name ?: 'Unassigned';
        $finalRemarks = $remarks ?: $this->remarksFor($status, $organization);

        $user->clearanceStatuses()->create([
            'tagged_by' => auth()->id(),
            'organization' => $organization,
            'status' => $status,
            'semester_id' => $semester->id,
            'academic_year' => $semester->school_year,
            'semester' => $semester->semester_label,
            'remarks' => $finalRemarks,
            'tagged_at' => now(),
        ]);

        $user->notify(new \App\Notifications\ClearanceStatusUpdated($status, $organization, $semester));

        return true;
    }

    protected function buildHistoryGroups(User $user): Collection
    {
        // unchanged — no edits needed here
        $records = $user->clearanceStatuses()
            ->with(['taggedByUser', 'semesterPeriod'])
            ->orderByDesc('tagged_at')
            ->orderByDesc('id')
            ->get();

        return $records
            ->groupBy(fn (ClearanceStatus $r) => $r->semester_id ?? ('legacy:' . $r->academic_year . ':' . $r->semester))
            ->map(function (Collection $group) {
                $first = $group->first();
                $label = $first->semesterPeriod
                    ? $first->semesterPeriod->label()
                    : 'AY ' . ($first->academic_year ?: 'Not set') . ' | ' . ($first->semester ?: 'Not set') . ' Semester';

                $entries = $group->map(fn (ClearanceStatus $r) => [
                    'id' => $r->id,
                    'status' => $r->status,
                    'remarks' => $r->remarks,
                    'tagged_by' => $r->taggedByUser ? $this->fullName($r->taggedByUser) : 'System (auto)',
                    'tagged_at' => $r->tagged_at?->format('F j, Y, g:i a') ?? 'Not available',
                ])->values();

                return [
                    'label' => $label,
                    'is_current' => (bool) ($first->semesterPeriod?->is_current ?? false),
                    'semester_id' => $first->semester_id,
                    'latest_status' => $first->status,
                    'latest_entry' => $entries->first(),
                    'older_entries' => $entries->slice(1)->values(),
                    'entries' => $entries,
                ];
            })
            ->sort(function (array $a, array $b) {
                if ($a['is_current'] !== $b['is_current']) {
                    return $a['is_current'] ? -1 : 1;
                }

                return ($b['semester_id'] ?? 0) <=> ($a['semester_id'] ?? 0);
            })
            ->values();
    }
}