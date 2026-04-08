<?php

namespace App\Livewire\Admin;

use App\Models\StudentDocumentWorkspace;
use App\Models\StudentVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class TransactionRecords extends Component
{
    public string $search = '';
    public string $statusFilter = 'All';
    public string $viewMode = 'table';
    public int $currentPage = 1;
    public int $perPage = 10;

    protected array $statusTabs = [
        'All',
        'Pending',
        'For Appointment',
        'Waiting Upload',
        'Completed',
        'Missed',
    ];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'viewMode'], true)) {
            $this->currentPage = 1;
        }
    }

    public function setStatusFilter(string $status): void
    {
        if (! in_array($status, $this->statusTabs, true)) {
            return;
        }

        $this->statusFilter = $status;
        $this->currentPage = 1;
    }

    public function setViewMode(string $mode): void
    {
        if (! in_array($mode, ['table', 'folder'], true)) {
            return;
        }

        $this->viewMode = $mode;
        $this->currentPage = 1;
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

    protected function workspaces(): Collection
    {
        return StudentDocumentWorkspace::query()
            ->with([
                'user.latestVerification',
                'template.currentVersion',
                'version',
            ])
            ->latest('updated_at')
            ->get();
    }

    protected function buildRecords(): Collection
    {
        return $this->workspaces()->map(function (StudentDocumentWorkspace $workspace) {
            $user = $workspace->user;
            $verification = $user?->latestVerification;
            $status = $this->resolveStatus($workspace, $verification);

            return [
                'workspace_id' => $workspace->workspace_id,
                'transaction_id' => str_pad((string) $workspace->workspace_id, 3, '0', STR_PAD_LEFT),
                'student_name' => $user ? $this->fullName($user) : 'Unknown student',
                'student_number' => $user?->student_number ?: 'N/A',
                'type' => $workspace->template?->name ?: 'Untitled template',
                'date_label' => optional($workspace->created_at)->format('F j, Y') ?: 'N/A',
                'date_sort' => optional($workspace->created_at)?->timestamp ?: 0,
                'appointment' => $this->resolveSessionLabel($workspace),
                'status' => $status,
                'preview_url' => $workspace->version?->image_path
                    ? Storage::disk('public')->url($workspace->version->image_path)
                    : null,
                'updated_label' => optional($workspace->updated_at)->diffForHumans() ?: 'Recently',
            ];
        })->values();
    }

    protected function filteredRecords(): Collection
    {
        return $this->buildRecords()
            ->when($this->search !== '', function (Collection $records) {
                $needle = Str::lower($this->search);

                return $records->filter(function (array $record) use ($needle) {
                    return Str::contains(Str::lower(implode(' ', [
                        $record['student_name'],
                        $record['student_number'],
                        $record['type'],
                        $record['status'],
                        $record['appointment'],
                    ])), $needle);
                });
            })
            ->when($this->statusFilter !== 'All', fn (Collection $records) => $records->where('status', $this->statusFilter))
            ->sortByDesc('date_sort')
            ->values();
    }

    protected function resolveStatus(StudentDocumentWorkspace $workspace, ?StudentVerification $verification): string
    {
        $hasPdf = filled($workspace->generated_pdf_path);
        $hasUpload = filled($verification?->e_slip_path);
        $createdAt = $workspace->created_at instanceof Carbon ? $workspace->created_at : Carbon::parse($workspace->created_at);

        return match (true) {
            $hasPdf && $hasUpload && Str::lower((string) $verification?->status) === StudentVerification::STATUS_VERIFIED => 'Completed',
            $hasPdf && $hasUpload => 'For Appointment',
            $hasPdf => 'Waiting Upload',
            $createdAt->lt(now()->subDays(14)) => 'Missed',
            default => 'Pending',
        };
    }

    protected function resolveSessionLabel(StudentDocumentWorkspace $workspace): string
    {
        $hour = (int) optional($workspace->created_at)->format('H');

        return $hour >= 12 ? 'Afternoon' : 'Morning';
    }

    protected function fullName(User $user): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
            $user->suffix,
        ])))) ?: ('User #' . $user->id);
    }

    protected function statusCount(Collection $records, string $status): int
    {
        return $records->where('status', $status)->count();
    }

    protected function recentRecord(Collection $records): ?array
    {
        return $records->sortByDesc('date_sort')->first();
    }

    public function render()
    {
        $allRecords = $this->buildRecords();
        $filteredRecords = $this->filteredRecords();
        $totalPages = max(1, (int) ceil($filteredRecords->count() / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $records = $filteredRecords
            ->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)
            ->values();

        return view('livewire.admin.transaction-records', [
            'records' => $records,
            'statusTabs' => $this->statusTabs,
            'totalPages' => $totalPages,
            'pendingCount' => $this->statusCount($allRecords, 'Pending'),
            'completedCount' => $this->statusCount($allRecords, 'Completed'),
            'appointmentCount' => $this->statusCount($allRecords, 'For Appointment'),
            'recentRecord' => $this->recentRecord($allRecords),
        ])->layout('layouts.app', ['title' => 'Transactions']);
    }
}
