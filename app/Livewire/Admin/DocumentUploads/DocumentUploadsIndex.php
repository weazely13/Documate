<?php

namespace App\Livewire\Admin\DocumentUploads;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Admin › Templates & Uploads › Uploads: the signed paper forms students brought to the office, filed like
 * Transaction Records: by form (folders), by student, or all of them. Each one opens its check page.
 */
class DocumentUploadsIndex extends Component
{
    public const STATUSES = [
        'needs_action' => 'Needs action',
        'reviewing' => 'Being checked',
        'verified' => 'Verified',
        'all' => 'All',
    ];

    #[Url(as: 'filter', except: 'needs_action')]
    public string $filter = 'needs_action';

    #[Url(as: 'by', except: 'folder')]
    public string $by = 'folder';

    #[Url(as: 'folder', except: '')]
    public string $folder = '';

    #[Url(as: 'student', except: '')]
    public string $student = '';

    #[Url(as: 'view', except: 'cards')]
    public string $view = 'cards';

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    #[Url(as: 'page', except: 1)]
    public int $currentPage = 1;

    public function updated($property): void
    {
        if (in_array($property, ['filter', 'search', 'dateFrom', 'dateTo'], true)) {
            $this->currentPage = 1;
        }
    }

    public function setBy(string $by): void
    {
        $this->by = in_array($by, ['folder', 'student', 'all'], true) ? $by : 'folder';
        $this->folder = $this->student = '';
        $this->currentPage = 1;
    }

    public function openFolder(string $type): void
    {
        $this->folder = $type;
        $this->currentPage = 1;
    }

    public function openStudent(string $key): void
    {
        $this->student = $key;
        $this->currentPage = 1;
    }

    public function closeOpened(): void
    {
        $this->folder = $this->student = '';
        $this->currentPage = 1;
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'table' ? 'table' : 'cards';
    }

    public function clearDates(): void
    {
        $this->dateFrom = $this->dateTo = '';
        $this->currentPage = 1;
    }

    public function previousPage(): void
    {
        $this->currentPage = max(1, $this->currentPage - 1);
    }

    public function nextPage(int $last): void
    {
        $this->currentPage = min($last, $this->currentPage + 1);
    }

    /** Every paper form with an attended visit, newest visit first, with the search and dates applied. */
    private function rows(): Collection
    {
        $term = Str::lower(trim($this->search));

        return StudentDocumentWorkspace::query()
            ->whereHas('appointments', fn ($q) => $q->where('status', 'attended'))
            ->with(['user', 'template.currentVersion', 'appointments'])
            ->get()
            ->map(function (StudentDocumentWorkspace $ws) {
                $visit = $ws->appointments->where('status', 'attended')->sortByDesc('updated_at')->first();
                $status = $ws->documentVerificationStatus();
                $photo = $ws->supporting_file_path && $ws->isSupportingFileImage() ? Storage::disk('public')->url($ws->supporting_file_path) : null;
                $form = $ws->template?->currentVersion?->image_path ? Storage::disk('public')->url($ws->template->currentVersion->image_path) : null;

                return [
                    'id' => $ws->workspace_id,
                    'user' => $ws->user,
                    'user_id' => (string) $ws->user_id,
                    'student_name' => $ws->user?->full_name ?: 'Unknown student',
                    'student_number' => $ws->user?->student_number ?: 'N/A',
                    'type' => $ws->template?->name ?: 'Untitled form',
                    'visited' => $visit?->updated_at,
                    'visited_label' => $visit?->updated_at?->format('M j, Y') ?? '—',
                    'status' => $status,
                    'state' => match ($status) {
                        'Verified' => 'verified',
                        'Reviewing' => 'reviewing',
                        default => 'needs_action',
                    },
                    'photo' => $photo,
                    'form_image' => $form,
                    'has_file' => (bool) $ws->supporting_file_path,
                    'url' => route('admin.document-uploads.show', $ws->workspace_id),
                ];
            })
            ->filter(function ($r) use ($term) {
                $day = $r['visited']?->format('Y-m-d');
                if ($this->dateFrom !== '' && (! $day || $day < $this->dateFrom)) return false;
                if ($this->dateTo !== '' && (! $day || $day > $this->dateTo)) return false;

                return $term === '' || Str::contains(Str::lower($r['student_name'] . ' ' . $r['student_number'] . ' ' . $r['type']), $term);
            })
            ->sortByDesc(fn ($r) => $r['visited']?->timestamp ?? 0)
            ->values();
    }

    public function render()
    {
        $all = $this->rows();
        $counts = collect(self::STATUSES)->map(fn ($l, $k) => $k === 'all' ? $all->count() : $all->where('state', $k)->count());
        $this->filter = array_key_exists($this->filter, self::STATUSES) ? $this->filter : 'needs_action';
        $records = $this->filter === 'all' ? $all : $all->where('state', $this->filter)->values();

        $folders = $records->groupBy('type')->map(fn ($g, $type) => [
            'type' => $type,
            'count' => $g->count(),
            'students' => $g->pluck('user_id')->unique()->count(),
            'needs' => $g->where('state', 'needs_action')->count(),
            'paperless' => false,
            'latest' => $g->first()['visited_label'],
            'thumbs' => $g->map(fn ($r) => $r['photo'] ?? $r['form_image'])->filter()->take(2)->map(fn ($src) => ['src' => $src, 'image' => true])->values(),
        ])->sortByDesc('count')->values();

        $students = $records->groupBy('user_id')->map(fn ($g, $key) => [
            'key' => (string) $key,
            'user' => $g->first()['user'],
            'student_name' => $g->first()['student_name'],
            'student_number' => $g->first()['student_number'],
            'forms' => $g->values(),
            'verified' => $g->where('state', 'verified')->count(),
            'needs' => $g->where('state', 'needs_action')->count(),
            'latest' => $g->first()['visited_label'],
            'folders' => $g->groupBy('type')->map(fn ($x, $t) => ['type' => $t, 'forms' => $x->values()])->values(),
        ])->values();

        $this->by = in_array($this->by, ['folder', 'student', 'all'], true) ? $this->by : 'folder';
        $openStudent = $this->student !== '' ? $students->firstWhere('key', $this->student) : null;
        if ($this->student !== '' && ! $openStudent) $this->student = '';
        if ($this->folder !== '' && ! $folders->contains('type', $this->folder)) $this->folder = '';

        $level = match (true) {
            $this->by === 'all' => 'all',
            $this->by === 'folder' && $this->folder === '' => 'folders',
            $this->by === 'folder' => 'folder',
            $openStudent !== null => 'student',
            default => 'students',
        };
        $items = match ($level) {
            'all' => $records,
            'folder' => $records->where('type', $this->folder)->values(),
            'students' => $students,
            default => collect(),
        };
        $perPage = $level === 'students' ? 20 : 24;
        $lastPage = max(1, (int) ceil($items->count() / $perPage));
        $this->currentPage = min(max(1, $this->currentPage), $lastPage);

        return view('livewire.admin.document-uploads.index', [
            'level' => $level,
            'pageItems' => $items->slice(($this->currentPage - 1) * $perPage, $perPage)->values(),
            'itemCount' => $items->count(),
            'lastPage' => $lastPage,
            'folders' => $folders,
            'openStudent' => $openStudent,
            'recordCount' => $records->count(),
            'studentCount' => $students->count(),
            'counts' => $counts,
            'statuses' => self::STATUSES,
        ])->layout('layouts.app', ['title' => 'Uploads']);
    }
}
