<?php

namespace App\Livewire\Admin\DocumentUploads;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentUploadsIndex extends Component
{
    use WithPagination;

    public const PER_PAGE = 20;

    public const FILTERS = [
        'needs_action' => 'Needs action',
        'verified' => 'Verified',
        'all' => 'All',
    ];

    public const SORTS = [
        'student' => 'Student',
        'forms' => 'Forms',
        'latest' => 'Latest attended',
        'status' => 'Status',
    ];

    #[Url(except: 'needs_action')]
    public string $filter = 'needs_action'; // needs_action | verified | all

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    /** "key:asc" or "key:desc". Empty means latest attended first. */
    #[Url(except: '')]
    public string $sort = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilter(): void { $this->resetPage(); }
    public function updatingDateFrom(): void { $this->resetPage(); }
    public function updatingDateTo(): void { $this->resetPage(); }
    public function updatingSort(): void { $this->resetPage(); }

    public function clearDateFilter(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    /** Header click: ascending, then descending, then back to the default order. */
    public function sortBy(string $key): void
    {
        if (! array_key_exists($key, self::SORTS)) {
            return;
        }

        [$current, $dir] = $this->parsedSort();

        $this->sort = match (true) {
            $current !== $key => "{$key}:asc",
            $dir === 'asc' => "{$key}:desc",
            default => '',
        };

        $this->resetPage();
    }

    public function clearSort(): void
    {
        $this->sort = '';
        $this->resetPage();
    }

    /** @return array{0: ?string, 1: ?string} */
    private function parsedSort(): array
    {
        [$key, $dir] = array_pad(explode(':', $this->sort, 2), 2, null);

        if (! array_key_exists((string) $key, self::SORTS) || ! in_array($dir, ['asc', 'desc'], true)) {
            return [null, null];
        }

        return [$key, $dir];
    }

    private function sortOptions(): array
    {
        $options = ['' => 'Default: latest attended first'];

        foreach (self::SORTS as $key => $label) {
            $options["{$key}:asc"] = match ($key) {
                'student' => 'Student: A to Z',
                'forms' => 'Forms: fewest first',
                'latest' => 'Latest attended: oldest first',
                'status' => 'Status: fewest needing action first',
            };
            $options["{$key}:desc"] = match ($key) {
                'student' => 'Student: Z to A',
                'forms' => 'Forms: most first',
                'latest' => 'Latest attended: newest first',
                'status' => 'Status: most needing action first',
            };
        }

        return $options;
    }

    /** One entry per form, with the search and date filters applied. */
    protected function rows(): Collection
    {
        return StudentDocumentWorkspace::query()
            ->whereHas('appointments', fn ($q) => $q->where('status', 'attended'))
            ->with(['user', 'template', 'appointments'])
            ->get()
            ->map(fn (StudentDocumentWorkspace $workspace) => [
                'workspace_id' => $workspace->workspace_id,
                'user_id' => $workspace->user_id,
                'student_name' => $this->fullName($workspace->user),
                'student_number' => $workspace->user?->student_number ?: 'N/A',
                'type' => $workspace->template?->name ?: 'Untitled template',
                'attended_at' => optional($workspace->appointments->firstWhere('status', 'attended'))?->updated_at,
                'doc_status' => $workspace->documentVerificationStatus(),
            ])
            ->filter(function (array $r) {
                if ($this->dateFrom === '' && $this->dateTo === '') {
                    return true;
                }
                if (! $r['attended_at']) {
                    return false;
                }
                $attendedDate = $r['attended_at']->format('Y-m-d');
                if ($this->dateFrom !== '' && $attendedDate < $this->dateFrom) return false;
                if ($this->dateTo !== '' && $attendedDate > $this->dateTo) return false;

                return true;
            })
            ->filter(fn (array $r) => $this->search === '' || str_contains(
                strtolower($r['student_name'] . ' ' . $r['student_number'] . ' ' . $r['type']),
                strtolower($this->search)
            ))
            ->values();
    }

    private function forTab(Collection $rows, string $tab): Collection
    {
        return $rows->filter(fn (array $r) => match ($tab) {
            'needs_action' => in_array($r['doc_status'], ['Needs Upload', 'Needs Re-upload']),
            'verified' => $r['doc_status'] === 'Verified',
            default => true,
        });
    }

    /** One row per student, forms newest first. */
    private function group(Collection $rows): Collection
    {
        return $rows
            ->groupBy('user_id')
            ->map(function (Collection $forms) {
                $forms = $forms
                    ->sortByDesc(fn (array $r) => $r['attended_at']?->timestamp ?? 0)
                    ->values();
                $first = $forms->first(); // latest form

                return [
                    'user_id' => $first['user_id'],
                    'student_name' => $first['student_name'],
                    'student_number' => $first['student_number'],
                    'forms' => $forms,
                    'total' => $forms->count(),
                    'needs_action' => $forms->whereIn('doc_status', ['Needs Upload', 'Needs Re-upload'])->count(),
                    'all_verified' => $forms->every(fn ($f) => $f['doc_status'] === 'Verified'),
                    'latest_attended' => $first['attended_at'],
                ];
            })
            ->values();
    }

    private function sorted(Collection $students): Collection
    {
        [$key, $dir] = $this->parsedSort();

        if (! $key) {
            return $students->sortByDesc(fn (array $s) => $s['latest_attended']?->timestamp ?? 0)->values();
        }

        $value = match ($key) {
            'student' => fn (array $s) => strtolower($s['student_name']),
            'forms' => fn (array $s) => $s['total'],
            'latest' => fn (array $s) => $s['latest_attended']?->timestamp ?? 0,
            'status' => fn (array $s) => $s['needs_action'] * 10 + ($s['all_verified'] ? 0 : 1),
        };

        return $students->sortBy($value, SORT_REGULAR, $dir === 'desc')->values();
    }

    private function fullName(?User $user): string
    {
        if (! $user) return 'Unknown student';

        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name, $user->middle_name, $user->last_name, $user->suffix,
        ])))) ?: ('User #' . $user->id);
    }

    public function render()
    {
        $rows = $this->rows();

        $counts = collect(self::FILTERS)->map(
            fn ($label, $key) => $this->group($this->forTab($rows, $key))->count()
        );

        $students = $this->sorted($this->group($this->forTab($rows, $this->filter)));

        $total = $students->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $this->getPage()), $lastPage);

        [$sortKey, $sortDir] = $this->parsedSort();

        return view('livewire.admin.document-uploads.index', [
            'students' => $students->forPage($page, self::PER_PAGE)->values(),
            'total' => $total,
            'page' => $page,
            'lastPage' => $lastPage,
            'counts' => $counts,
            'filters' => self::FILTERS,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
            'sortOptions' => $this->sortOptions(),
        ])->layout('layouts.app', ['title' => 'Document Uploads']);
    }
}