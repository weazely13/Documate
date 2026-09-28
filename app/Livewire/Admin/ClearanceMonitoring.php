<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ManagesClearanceTagging;
use App\Models\ClearanceStatus;
use App\Models\Organization;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class ClearanceMonitoring extends Component
{
    use ManagesClearanceTagging;

    public const STATUS_NOT_TAGGED = 'Not Tagged';

    public ?int $selectedSemesterId = null;

    public string $search = '';
    public string $statusFilter = '';
    public string $organizationFilter = '';
    public string $yearFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public bool $showFilters = false;
    public int $currentPage = 1;
    
    // 1. Updated perPage to 50
    public int $perPage = 50;

    // --- Multi-select + bulk tag ---
    public array $selectedIds = [];
    public string $bulkStatus = '';
    public string $bulkRemarks = '';

    public function mount(): void
    {
        $this->selectedSemesterId = Semester::current()?->id;
    }

    public function updated($property): void
    {
        if (in_array($property, [
            'search',
            'statusFilter',
            'organizationFilter',
            'yearFilter',
            'dateFrom',
            'dateTo',
        ], true)) {
            $this->currentPage = 1;
        }

        if ($property === 'selectedSemesterId') {
            $this->currentPage = 1;
            $this->selectedIds = [];
        }
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function applyFilters(): void
    {
        $this->currentPage = 1;
        $this->showFilters = false;
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'organizationFilter', 'yearFilter', 'dateFrom', 'dateTo']);
        $this->currentPage = 1;
        $this->showFilters = false;
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function nextPage(int $totalPages): void
    {
        if ($this->currentPage < $totalPages) {
            $this->currentPage++;
        }
    }

    public function toggleSelectAllOnPage(array $pageUserIds): void
    {
        $allSelected = collect($pageUserIds)->every(fn ($id) => in_array($id, $this->selectedIds));

        $this->selectedIds = $allSelected
            ? array_values(array_diff($this->selectedIds, $pageUserIds))
            : array_values(array_unique(array_merge($this->selectedIds, $pageUserIds)));
    }

    protected function profileRouteName(): string
    {
        return 'admin.clearance-monitoring.show';
    }

    public function goToProfile(int $userId)
    {
        return redirect()->route($this->profileRouteName(), $userId);
    }

    public function bulkTag(): void
    {
        if (empty($this->selectedIds)) {
            $this->dispatch('alert', type: 'error', message: 'Select at least one student first.');

            return;
        }

        if (! $this->selectedSemesterId) {
            $this->dispatch('alert', type: 'error', message: 'Select a semester first.');

            return;
        }

        $tagged = 0;

        foreach ($this->selectedIds as $id) {
            if ($this->createClearanceRecord((int) $id, $this->selectedSemesterId, $this->bulkStatus, $this->bulkRemarks ?: null)) {
                $tagged++;
            }
        }

        $this->selectedIds = [];
        $this->bulkStatus = '';
        $this->bulkRemarks = '';

        $this->dispatch('alert', type: 'success', message: "Tagged {$tagged} student(s).");
    }

    protected function buildRecords(): Collection
    {
        $users = $this->monitoredUsersQuery()
            ->with(['role', 'program', 'organization'])
            ->get([
                'users.id', 'users.student_number', 'users.first_name', 'users.middle_name',
                'users.last_name', 'users.suffix', 'users.email', 'users.program_id',
                'users.organization_id', 'users.year_level', 'users.account_status',
                'users.role_id', 'users.profile_picture', 'users.created_at',
            ]);

        $latestForSemester = collect();

        if ($this->selectedSemesterId) {
            $latestForSemester = ClearanceStatus::query()
                ->where('semester_id', $this->selectedSemesterId)
                ->whereIn('user_id', $users->pluck('id'))
                ->with('taggedByUser')
                ->orderByDesc('tagged_at')
                ->orderByDesc('id')
                ->get()
                ->groupBy('user_id')
                ->map(fn (Collection $g) => $g->first());
        }

        return $users->map(function (User $user) use ($latestForSemester) {
            $record = $latestForSemester->get($user->id);
            $organization = $user->organization?->name ?: ($record?->organization ?: 'Unassigned');


            // 3. Resolve profile picture URL if stored on public disk or external URL
            $profilePictureUrl = null;
            if ($user->profile_picture) {
                $profilePictureUrl = Str::startsWith($user->profile_picture, ['http://', 'https://'])
                    ? $user->profile_picture
                    : Storage::url($user->profile_picture);
            }

            return [
                'user_id' => $user->id,
                'student_name' => $this->fullName($user),
                'student_number' => $user->student_number ?: 'N/A',
                'organization' => $organization,
                'status' => $record?->status ?? self::STATUS_NOT_TAGGED,
                'year_level' => $this->formatYearLevel((string) $user->year_level),
                'year_sort_value' => ctype_digit((string) $user->year_level) ? (int) $user->year_level : 99,
                'tagged_by' => $record?->taggedByUser ? $this->fullName($record->taggedByUser) : '-',
                'tagged_date' => $record?->tagged_at?->toDateString() ?? '',
                'remarks' => $record?->remarks ?: '',
                'account_status' => ucfirst((string) ($user->account_status ?: 'inactive')),
                'profile_picture' => $profilePictureUrl, // 4. Added to response array
            ];
        })->values();
    }

    protected function filteredRecords(): Collection
    {
        return $this->buildRecords()
            ->when($this->search !== '', function (Collection $records) {
                $needle = Str::lower($this->search);

                return $records->filter(fn (array $r) => Str::contains(
                    Str::lower($r['student_name'] . ' ' . $r['student_number'] . ' ' . $r['organization']),
                    $needle
                ));
            })
            ->when($this->statusFilter !== '', fn (Collection $r) => $r->where('status', $this->statusFilter))
            ->when($this->organizationFilter !== '', fn (Collection $r) => $r->where('organization', $this->organizationFilter))
            ->when($this->yearFilter !== '', fn (Collection $r) => $r->filter(fn (array $rec) => (string) $rec['year_sort_value'] === $this->yearFilter))
            ->when($this->dateFrom !== '', fn (Collection $r) => $r->filter(fn (array $rec) => $rec['tagged_date'] !== '' && $rec['tagged_date'] >= $this->dateFrom))
            ->when($this->dateTo !== '', fn (Collection $r) => $r->filter(fn (array $rec) => $rec['tagged_date'] !== '' && $rec['tagged_date'] <= $this->dateTo))
            ->sortBy('year_sort_value', SORT_NUMERIC)
            ->values();
    }

    public function render()
    {
        $allRecords = $this->buildRecords();
        $filteredRecords = $this->filteredRecords();
        $totalPages = max(1, (int) ceil($filteredRecords->count() / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $records = $filteredRecords->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();

        return view('livewire.admin.clearance-monitoring', [
            'records' => $records,
            'organizations' => Organization::query()
                ->orderBy('name')
                ->pluck('name')
                ->push('Unassigned') // keep this option so students with no org can still be filtered
                ->values(),
            'statuses' => collect(ClearanceStatus::STATUSES),
            'filterStatuses' => collect(ClearanceStatus::STATUSES)->push(self::STATUS_NOT_TAGGED),
            'semesters' => Semester::orderByDesc('school_year')->orderBy('semester_label')->get(),
            'totalPages' => $totalPages,
        ])->layout('layouts.app', ['title' => 'Clearance Monitoring']);
    }
}