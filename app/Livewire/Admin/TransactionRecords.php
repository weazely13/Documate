<?php

namespace App\Livewire\Admin;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Support\Issuers;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use App\Enums\TransactionStatus;
use App\Workflow\Documents\DocumentRegistry;
use Livewire\Attributes\Url;

/**
 * Look-up of every transaction, paperless and paper-based. Nothing is acted on here:
 * open transactions are worked from Workflow Review (paperless) or Appointments (paper).
 */
class TransactionRecords extends Component
{
    #[Url(as: 'tab', except: 'transactions')]
    public string $activeTab = 'transactions';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'result', except: 'all')]
    public string $result = 'all';

    #[Url(as: 'period', except: 'all')]
    public string $period = 'all';

    #[Url(as: 'page', except: 1)]
    public int $currentPage = 1;

    /** Folder: one document type, or '' for every type. */
    #[Url(as: 'folder', except: '')]
    public string $folder = '';

    #[Url(as: 'mode', except: 'all')]
    public string $mode = 'all';

    /** Tagged/submitted between these dates (Y-m-d), when the Date filter is a custom range. */
    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    /** How the archive is filed: by document type (folders), by who transacted (students), or every document. */
    #[Url(as: 'by', except: 'folder')]
    public string $by = 'folder';

    /** The student whose account is open (their user id), or '' for the list of students. */
    #[Url(as: 'student', except: '')]
    public string $student = '';

    /** Inside a student's account: their folders as tiles, or every document (as cards or a table, see $view). */
    #[Url(as: 'arrange', except: 'folders')]
    public string $arrange = 'folders';

    /** Inside a student's account: the order of the folders, or of the documents inside an open folder. */
    #[Url(as: 'sort', except: 'newest')]
    public string $sort = 'newest';

    public const SORTS = [
        'newest' => ['bx-time-five', 'Newest'],
        'az' => ['bx-sort-a-z', 'A–Z'],
        'za' => ['bx-sort-z-a', 'Z–A'],
    ];

    /** cards (thumbnails) or table. */
    #[Url(as: 'view', except: 'cards')]
    public string $view = 'cards';

    public const MODES = [
        'all' => 'Online and paper',
        'online' => 'Online only',
        'paper' => 'Paper only',
    ];

    /** Students per page. */
    public int $perPage = 20;

    protected array $mainTabs = ['transactions', 'logbooks'];

    public const RESULTS = [
        'all' => 'All results',
        'open' => 'Still open',
        'completed' => 'Completed',
        'closed' => 'Rejected, voided or missed',
    ];

    public const PERIODS = [
        'all' => 'All time',
        '7d' => 'Last 7 days',
        '30d' => 'Last 30 days',
        'year' => 'This year',
        'custom' => 'Date range',
    ];

    /** Statuses that end a transaction without completing it. */
    private const CLOSED = ['Rejected', 'Voided', 'Missed'];

    public function setActiveTab(string $tab): void
    {
        if (! in_array($tab, $this->mainTabs, true)) {
            return;
        }
        $this->activeTab = $tab;
        $this->currentPage = 1;
    }

    public function updated($property): void
    {
        if (in_array($property, ['dateFrom', 'dateTo'], true)) {
            $this->period = ($this->dateFrom !== '' || $this->dateTo !== '') ? 'custom' : 'all';
        }
        if ($property === 'period' && $this->period !== 'custom') {
            $this->dateFrom = $this->dateTo = '';
        }
        if (in_array($property, ['search', 'result', 'period', 'folder', 'mode', 'student', 'dateFrom', 'dateTo'], true)) {
            $this->currentPage = 1;
        }
    }

    public function openFolder(string $type): void
    {
        $this->folder = $type;
        $this->currentPage = 1;
    }

    public function openStudent(string $key): void
    {
        // The account has no filters of its own, so none from elsewhere may hide documents in it.
        $this->reset(['result', 'mode', 'period', 'dateFrom', 'dateTo', 'folder']);
        $this->student = $key;
        $this->currentPage = 1;
    }

    /** Back to every folder / every student. */
    public function closeOpened(): void
    {
        $this->student = '';
        $this->folder = '';
        $this->currentPage = 1;
    }

    public function setBy(string $by): void
    {
        $this->by = in_array($by, ['folder', 'student', 'all'], true) ? $by : 'folder';
        $this->folder = '';
        $this->student = '';
        $this->currentPage = 1;
    }

    /** The account's switch: folders, or every document as cards or as a table. */
    public function setArrange(string $arrange): void
    {
        $this->arrange = $arrange === 'folders' ? 'folders' : 'list';
        if (in_array($arrange, ['cards', 'table'], true)) {
            $this->view = $arrange;
        }
        $this->folder = '';
    }

    public function setSort(string $sort): void
    {
        $this->sort = array_key_exists($sort, self::SORTS) ? $sort : 'newest';
    }

    public function setView(string $view): void
    {
        $this->view = in_array($view, ['cards', 'table'], true) ? $view : 'cards';
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'result', 'period', 'mode', 'dateFrom', 'dateTo']);
        if ($this->by !== 'folder') {
            $this->folder = '';
        }
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
        return StudentDocumentWorkspace::withTrashed()
            ->with([
                'user.role',
                'user.latestVerification',
                'template.currentVersion.fields',
            ])
            ->where(fn ($q) => $q->whereNull('workflow_status')->orWhere('workflow_status', '!=', 'draft'))
            ->latest('updated_at')
            ->get();
    }

    protected function buildRecords(): Collection
    {
        $workspaces = $this->workspaces();
        $issuers = Issuers::for($workspaces);

        return $workspaces->map(function (StudentDocumentWorkspace $workspace) use ($issuers) {
            $user = $workspace->user;
            $status = $this->resolveStatus($workspace);
            $analysisData = $workspace->analysis_data ?? [];
            $paperless = $workspace->usesWorkflow();
            $document = $paperless ? DocumentRegistry::find($workspace->document_type) : null;
            $when = $paperless ? ($workspace->submitted_at ?? $workspace->created_at) : $workspace->created_at;
            $padded = str_pad((string) $workspace->workspace_id, 4, '0', STR_PAD_LEFT);
            // The card's preview: the rendered online document, or the paper form's template image.
            $image = $paperless ? null : $workspace->template?->currentVersion?->image_path;
            $thumb = match (true) {
                // Without the form's code, the page saved in its snapshot.
                $paperless => ($document || ! empty($workspace->snapshot['pages'])) ? route('admin.workflow.preview', $workspace->workspace_id) : null,
                default => $image ? Storage::disk('public')->url($image) : null,
            };

            return [
                'workspace' => $workspace,
                'workspace_id' => $workspace->workspace_id,
                'user_id' => $workspace->user_id,
                'paperless' => $paperless,
                'transaction_id' => $paperless ? (string) $workspace->transaction_code : $padded,
                'transaction_label' => $paperless ? (string) $workspace->transaction_code : '#' . $padded,
                'url' => $paperless
                    ? route('admin.workflow.show', $workspace->workspace_id)
                    : route('admin.transactions.show', $workspace->workspace_id),
                'student_name' => $user ? $this->fullName($user) : 'Unknown student',
                // Staff records (e.g. an interviewer's own rating sheet) have no student number: show the role.
                'student_number' => $user?->student_number ?: ($user?->role?->role_name ?? 'N/A'),
                'issuer' => Issuers::label($issuers[$workspace->workspace_id] ?? null),
                'type' => $paperless
                    ? ($document?->name() ?? $workspace->snapshot['document_name'] ?? Str::headline((string) $workspace->document_type))
                    : ($workspace->template?->name ?: 'Untitled template'),
                'summary' => $document ? $document->summary($workspace->field_values ?? []) : '',
                'date_label' => optional($when)->format('M j, Y') ?: 'N/A',
                'date_sort' => optional($when)?->timestamp ?: 0,
                'appointment' => $paperless ? '' : $this->resolveSessionLabel($workspace),
                'session_label' => $paperless ? 'Not Applicable' : $this->resolveSessionLabel($workspace),
                'status' => $status,
                'result' => $this->resultFor($status),
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
                'issued' => (bool) $document?->issuedByOffice(),
                'thumb' => $thumb,
                'thumb_is_image' => ! $paperless && $thumb !== null,
                'user' => $user,
                'deleted_by_student' => $workspace->trashed(),
                'url' => $paperless
                    ? route('admin.transactions.online', $workspace->workspace_id)
                    : route('admin.transactions.show', $workspace->workspace_id),
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
                        $record['issuer'],
                        $record['ocr_text'],
                        $record['analysis_summary'],
                        $record['analysis_missing_fields'],
                        $record['field_matches_text'],
                        $record['summary']
                    ])), $needle);
                })->map(fn (array $record) => $this->attachSearchSnippet($record));
            })
            ->filter(fn (array $r) => match ($this->result) {
                'open' => $r['result'] === 'open',
                'completed' => $r['result'] === 'completed',
                'closed' => $r['result'] === 'closed',
                default => true,
            })
            ->filter(fn (array $r) => match ($this->mode) {
                'online' => $r['paperless'],
                'paper' => ! $r['paperless'],
                default => true,
            })
            ->filter(fn (array $r) => match ($this->period) {
                '7d' => $r['date_sort'] >= now()->subDays(7)->timestamp,
                '30d' => $r['date_sort'] >= now()->subDays(30)->timestamp,
                'custom' => $this->inRange($r['date_sort']),
                'year' => $r['date_sort'] >= now()->startOfYear()->timestamp,
                default => true,
            });

        return $records->sortByDesc('date_sort')->values();
    }

    private function inRange(int $ts): bool
    {
        $from = $this->parseDate($this->dateFrom)?->startOfDay();
        $to = $this->parseDate($this->dateTo)?->endOfDay();

        return (! $from || $ts >= $from->timestamp) && (! $to || $ts <= $to->timestamp);
    }

    private function parseDate(string $value): ?Carbon
    {
        try {
            return $value !== '' ? Carbon::createFromFormat('Y-m-d', $value) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** open / completed / closed, for the Result filter and the row's dot colour. */
    protected function resultFor(string $status): string
    {
        return match (true) {
            $status === 'Completed' => 'completed',
            in_array($status, self::CLOSED, true) => 'closed',
            default => 'open',
        };
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
            $record['issuer'],
        ]));

        if (Str::contains($visibleHaystack, Str::lower($needle))) {
            return $record;
        }

        $sources = [
            'analysis_summary' => 'AI Summary',
            'ocr_text' => 'Extracted Text',
            'field_matches_text' => 'Field Verification',
            'analysis_missing_fields' => 'Missing Fields',
            'summary' => 'Details'
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

    protected function resolveStatus(StudentDocumentWorkspace $workspace): string
    {
        if ($workspace->usesWorkflow()) {
            return match ($workspace->workflow_status) {
                TransactionStatus::Completed => 'Completed',
                TransactionStatus::Rejected => 'Rejected',
                TransactionStatus::Voided => 'Voided',
                TransactionStatus::ActionRequired => 'Waiting on Student',
                default => 'In Review',
            };
        }
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

    public function render()
    {
        if (! in_array($this->activeTab, $this->mainTabs, true)) {
            $this->activeTab = 'transactions';
        }
        $this->result = array_key_exists($this->result, self::RESULTS) ? $this->result : 'all';
        $this->period = array_key_exists($this->period, self::PERIODS) ? $this->period : 'all';
        $this->mode = array_key_exists($this->mode, self::MODES) ? $this->mode : 'all';
        $this->view = in_array($this->view, ['cards', 'table'], true) ? $this->view : 'cards';

        // Skip building transaction data entirely while on the Logbooks view.
        $all = $this->activeTab === 'logbooks' ? collect() : $this->filteredRecords();

        // The archive's folders: one per document type, counted under the other filters.
        $folders = $all->groupBy('type')
            ->map(fn ($g, $type) => [
                'type' => $type,
                'count' => $g->count(),
                'students' => $g->pluck('user_id')->unique()->count(),
                'paperless' => (bool) $g->first()['paperless'],
                'latest' => $g->first()['date_label'],
                // The newest two pages, peeking out of the folder.
                'thumbs' => $g->filter(fn ($r) => $r['thumb'])->take(2)
                    ->map(fn ($r) => ['src' => $r['thumb'], 'image' => $r['thumb_is_image']])->values(),
            ])
            ->sortByDesc('count')
            ->values();
        if ($this->folder !== '' && ! $folders->contains('type', $this->folder)) {
            $this->folder = '';
        }

        $this->by = in_array($this->by, ['folder', 'student', 'all'], true) ? $this->by : 'folder';
        // The type filter only applies inside a student's account and on All documents.
        if ($this->by === 'student' && $this->student === '') {
            $this->folder = '';
        }
        $records = $this->folder === '' ? $all : $all->where('type', $this->folder)->values();

        // Every student's account: newest activity first.
        $students = $records
            ->groupBy(fn (array $r) => (string) ($r['user_id'] ?? 'unknown'))
            ->map(fn ($forms, $key) => [
                'key' => (string) $key,
                'student_name' => $forms->first()['student_name'],
                'student_number' => $forms->first()['student_number'],
                'user' => $forms->first()['user'],
                'completed_count' => $forms->where('result', 'completed')->count(),
                // Inside a student's account, their documents are filed by type; the newest folder first.
                'folders' => $forms->groupBy('type')->map(fn ($g, $type) => ['type' => $type, 'forms' => $g->values()])->values(),
                'latest_label' => $forms->first()['date_label'],
                'open_count' => $forms->where('result', 'open')->count(),
                'forms' => $forms->values(),
            ])
            ->values();

        $openStudent = null;
        if ($this->by === 'student' && $this->student !== '') {
            $openStudent = $students->firstWhere('key', $this->student);
            if (! $openStudent) {
                $this->student = '';
            }
        }

        // What the page lists: folders, one folder's documents, students, or one student's account.
        $level = match (true) {
            $this->by === 'all' => 'all',
            $this->by === 'folder' && $this->folder === '' => 'folders',
            $this->by === 'folder' => 'folder',
            $openStudent !== null => 'student',
            default => 'students',
        };
        $items = match ($level) {
            'folder', 'all' => $records,
            'students' => $students,
            default => collect(),
        };
        $perPage = in_array($level, ['folder', 'all'], true) ? 24 : $this->perPage;
        $totalCount = $items->count();
        $totalPages = max(1, (int) ceil($totalCount / $perPage));
        $this->currentPage = min(max(1, $this->currentPage), $totalPages);
        $page = $items->slice(($this->currentPage - 1) * $perPage, $perPage)->values();

        // Where a record's Back button returns to: this folder, account or All documents, filters and page included.
        if ($this->activeTab === 'transactions') {
            session(['transactions.back' => route('admin.transactions.index', array_filter([
                'by' => $this->by !== 'folder' ? $this->by : null,
                'folder' => $this->folder ?: null,
                'student' => $this->student ?: null,
                'view' => $this->view !== 'cards' ? $this->view : null,
                'arrange' => $this->arrange !== 'folders' ? $this->arrange : null,
                'sort' => $this->sort !== 'newest' ? $this->sort : null,
                'q' => $this->search ?: null,
                'result' => $this->result !== 'all' ? $this->result : null,
                'mode' => $this->mode !== 'all' ? $this->mode : null,
                'period' => $this->period !== 'all' ? $this->period : null,
                'from' => $this->dateFrom ?: null,
                'to' => $this->dateTo ?: null,
                'page' => $this->currentPage > 1 ? $this->currentPage : null,
            ]))]);
        }

        return view('livewire.admin.transaction-records', [
            'level' => $level,
            'sortOptions' => self::SORTS,
            'dateLabel' => $this->period === 'custom'
                ? trim(($this->parseDate($this->dateFrom)?->format('M j, Y') ?? 'Any') . ' – ' . ($this->parseDate($this->dateTo)?->format('M j, Y') ?? 'today'))
                : self::PERIODS[$this->period],
            'pageItems' => $page,
            'openStudent' => $openStudent,
            'studentCount' => $students->count(),
            'recordCount' => $records->count(),
            'folders' => $folders,
            'allCount' => $all->count(),
            'modes' => self::MODES,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'results' => self::RESULTS,
            'periods' => self::PERIODS,
        ])->layout('layouts.app', ['title' => 'Transaction Records']);
    }
}