<?php

namespace App\Livewire\Admin;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

class TransactionRecords extends Component
{
    // NEW: which top-level section is showing — 'transactions' or 'logbooks'.
    public string $activeTab = 'transactions';

    public string $search = '';
    public string $statusFilter = 'All';
    public string $viewMode = 'table';
    public string $sortBy = 'date_desc';
    public ?string $selectedFolder = null;
    public int $currentPage = 1;
    public int $perPage = 30;

    protected array $mainTabs = [
        'transactions' => 'Transaction Records',
        'logbooks' => 'Logbooks',
    ];

    protected array $statusTabs = [
        'All',
        'Pending',
        'For Appointment',
        'Completed',
    ];

    protected array $sortOptions = [
        'date_desc' => 'Newest First',
        'date_asc' => 'Oldest First',
        'name_asc' => 'Student Name (A–Z)',
        'name_desc' => 'Student Name (Z–A)',
        'status' => 'Status',
    ];

    // NEW
    public function setActiveTab(string $tab): void
    {
        if (! array_key_exists($tab, $this->mainTabs)) {
            return;
        }
        $this->activeTab = $tab;
        $this->currentPage = 1;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'viewMode', 'sortBy'], true)) {
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
        $this->selectedFolder = null;
    }

    public function openFolder(string $type): void
    {
        $this->selectedFolder = $type;
        $this->currentPage = 1;
    }

    public function closeFolder(): void
    {
        $this->selectedFolder = null;
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
                'template.currentVersion.fields',
                'version',
            ])
            ->latest('updated_at')
            ->get();
    }

    protected function buildRecords(): Collection
    {
        return $this->workspaces()->map(function (StudentDocumentWorkspace $workspace) {
            $user = $workspace->user;
            $status = $this->resolveStatus($workspace);
            $analysisData = $workspace->analysis_data ?? [];

            return [
                'workspace' => $workspace,
                'workspace_id' => $workspace->workspace_id,
                'transaction_id' => str_pad((string) $workspace->workspace_id, 4, '0', STR_PAD_LEFT),
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
                'ocr_text' => $workspace->ocr_text ?: '',
                'analysis_summary' => $workspace->analysis_summary ?: '',
                'analysis_missing_fields' => !empty($analysisData['missing_fields'])
                    ? implode(', ', $analysisData['missing_fields'])
                    : '',
                'field_matches_text' => !empty($analysisData['field_matches'])
                    ? collect($analysisData['field_matches'])
                        ->map(fn ($fm) => trim(($fm['label'] ?? '') . ': ' . ($fm['expected_value'] ?? '') . ' / ' . ($fm['found_value'] ?? '')))
                        ->implode(' — ')
                    : '',
                'search_snippet' => null,
                'search_snippet_label' => null,
            ];
        })->values();
    }

    protected function filteredRecords(): Collection
    {
        $records = $this->buildRecords()
            ->when($this->search !== '', function (Collection $records) {
                $needle = Str::lower($this->search);

                return $records->filter(function (array $record) use ($needle) {
                    return Str::contains(Str::lower(implode(' ', [
                        $record['student_name'],
                        $record['student_number'],
                        $record['type'],
                        $record['status'],
                        $record['appointment'],
                        $record['transaction_id'],
                        $record['ocr_text'],
                        $record['analysis_summary'],
                        $record['analysis_missing_fields'],
                        $record['field_matches_text'],
                    ])), $needle);
                })->map(fn (array $record) => $this->attachSearchSnippet($record));
            })
            ->when($this->statusFilter !== 'All', fn (Collection $r) => $r->where('status', $this->statusFilter));

        return $this->applySort($records);
    }

    protected function attachSearchSnippet(array $record): array
    {
        $needle = $this->search;

        $visibleHaystack = Str::lower(implode(' ', [
            $record['student_name'],
            $record['student_number'],
            $record['type'],
            $record['status'],
            $record['transaction_id'],
            $record['appointment'],
        ]));

        if (Str::contains($visibleHaystack, Str::lower($needle))) {
            return $record;
        }

        $sources = [
            'analysis_summary' => 'AI Summary',
            'ocr_text' => 'Extracted Text',
            'field_matches_text' => 'Field Verification',
            'analysis_missing_fields' => 'Missing Fields',
        ];

        foreach ($sources as $field => $label) {
            $snippet = $this->buildSnippet($record[$field] ?? '', $needle);
            if ($snippet !== null) {
                $record['search_snippet'] = $snippet;
                $record['search_snippet_label'] = $label;
                break;
            }
        }

        return $record;
    }

    protected function buildSnippet(string $haystack, string $needle, int $radius = 50): ?string
    {
        if (trim($needle) === '' || trim($haystack) === '') {
            return null;
        }

        $pos = mb_stripos($haystack, $needle);
        if ($pos === false) {
            return null;
        }

        $start = max(0, $pos - $radius);
        $length = mb_strlen($needle) + ($radius * 2);
        $excerpt = mb_substr($haystack, $start, $length);

        $prefix = $start > 0 ? '…' : '';
        $suffix = ($start + $length) < mb_strlen($haystack) ? '…' : '';

        $escaped = e($excerpt);
        $escapedNeedle = preg_quote(e($needle), '/');

        $highlighted = preg_replace(
            '/(' . $escapedNeedle . ')/i',
            '<mark class="rounded bg-amber-200 px-0.5 text-slate-900">$1</mark>',
            $escaped
        );

        return $prefix . $highlighted . $suffix;
    }

    protected function applySort(Collection $records): Collection
    {
        return match ($this->sortBy) {
            'date_asc' => $records->sortBy('date_sort')->values(),
            'name_asc' => $records->sortBy(fn ($r) => Str::lower($r['student_name']))->values(),
            'name_desc' => $records->sortByDesc(fn ($r) => Str::lower($r['student_name']))->values(),
            'status' => $records->sortBy('status')->values(),
            default => $records->sortByDesc('date_sort')->values(),
        };
    }

    protected function foldersFromRecords(Collection $records): Collection
    {
        return $records
            ->groupBy('type')
            ->map(function (Collection $items, string $type) {
                $matchedItem = $items->first(fn ($i) => filled($i['search_snippet'] ?? null));

                return [
                    'type' => $type,
                    'count' => $items->count(),
                    'pending_count' => $items->where('status', 'Pending')->count(),
                    'for_appointment_count' => $items->where('status', 'For Appointment')->count(),
                    'completed_count' => $items->where('status', 'Completed')->count(),
                    'preview_url' => $items->first()['preview_url'] ?? null,
                    'latest_date' => $items->max('date_sort'),
                    'items' => $items->values(),
                    'search_snippet' => $matchedItem['search_snippet'] ?? null,
                    'search_snippet_label' => $matchedItem['search_snippet_label'] ?? null,
                ];
            })
            ->sortByDesc('latest_date')
            ->values();
    }

    protected function resolveStatus(StudentDocumentWorkspace $workspace): string
    {
        $createdAt = $workspace->created_at instanceof Carbon ? $workspace->created_at : Carbon::parse($workspace->created_at);
        $hasAttendedAppointment = $workspace->hasAttendedAppointment();
        $hasActiveAppointment = $workspace->appointments()->whereIn('status', ['pending', 'approved'])->exists();

        return match (true) {
            $workspace->isCompleted() => 'Completed',
            $workspace->isProcessing() => 'Processing',
            $hasAttendedAppointment => 'Waiting Upload',
            $hasActiveAppointment => 'For Appointment',
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

    public function render()
    {
        // NEW: skip building transaction data entirely while on the Logbooks tab.
        $sharedData = [
            'mainTabs' => $this->mainTabs,
            'activeTab' => $this->activeTab,
            'statusTabs' => $this->statusTabs,
            'sortOptions' => $this->sortOptions,
        ];

        if ($this->activeTab === 'logbooks') {
            return view('livewire.admin.transaction-records', array_merge($sharedData, [
                'records' => collect(),
                'folders' => collect(),
                'totalPages' => 1,
                'totalCount' => 0,
                'selectedFolder' => null,
                'pendingCount' => 0,
                'forAppointmentCount' => 0,
                'completedCount' => 0,
            ]))->layout('layouts.app', ['title' => 'Transactions']);
        }

        $allRecords = $this->buildRecords();
        $filteredRecords = $this->filteredRecords();

        $sharedData += [
            'pendingCount' => $this->statusCount($allRecords, 'Pending'),
            'forAppointmentCount' => $this->statusCount($allRecords, 'For Appointment'),
            'completedCount' => $this->statusCount($allRecords, 'Completed'),
        ];

        if ($this->viewMode === 'folder') {
            $folders = $this->foldersFromRecords($filteredRecords);

            $records = collect();
            $totalCount = 0;
            $totalPages = 1;

            if ($this->selectedFolder !== null) {
                $activeFolder = $folders->firstWhere('type', $this->selectedFolder);
                $folderItems = $activeFolder['items'] ?? collect();
                $totalCount = $folderItems->count();
                $totalPages = max(1, (int) ceil($totalCount / $this->perPage));
                $this->currentPage = min($this->currentPage, $totalPages);
                $records = $folderItems->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();
            }

            return view('livewire.admin.transaction-records', array_merge($sharedData, [
                'records' => $records,
                'folders' => $folders,
                'totalPages' => $totalPages,
                'totalCount' => $totalCount,
                'selectedFolder' => $this->selectedFolder,
            ]))->layout('layouts.app', ['title' => 'Transactions']);
        }

        $totalCount = $filteredRecords->count();
        $totalPages = max(1, (int) ceil($totalCount / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $records = $filteredRecords
            ->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)
            ->values();

        return view('livewire.admin.transaction-records', array_merge($sharedData, [
            'records' => $records,
            'folders' => collect(),
            'totalPages' => $totalPages,
            'totalCount' => $totalCount,
            'selectedFolder' => null,
        ]))->layout('layouts.app', ['title' => 'Transactions']);
    }
}