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
        '30d' => 'Last 30 days',
        'year' => 'This year',
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
        if (in_array($property, ['search', 'result', 'period'], true)) {
            $this->currentPage = 1;
        }
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
                    ? ($document?->name() ?? $workspace->document_type)
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
            ->filter(fn (array $r) => match ($this->period) {
                '30d' => $r['date_sort'] >= now()->subDays(30)->timestamp,
                'year' => $r['date_sort'] >= now()->startOfYear()->timestamp,
                default => true,
            });

        return $records->sortByDesc('date_sort')->values();
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

        // Skip building transaction data entirely while on the Logbooks view.
        $records = $this->activeTab === 'logbooks' ? collect() : $this->filteredRecords();

        // One group per student so the name is shown once. Newest activity first, inside and across groups.
        $students = $records
            ->groupBy(fn (array $r) => $r['user_id'] ?? 'unknown')
            ->map(fn ($forms, $key) => [
                'key' => $key,
                'student_name' => $forms->first()['student_name'],
                'student_number' => $forms->first()['student_number'],
                'latest_label' => $forms->first()['date_label'],
                'open_count' => $forms->where('result', 'open')->count(),
                'forms' => $forms->values(),
            ])
            ->values();

        $totalCount = $students->count();
        $totalPages = max(1, (int) ceil($totalCount / $this->perPage));
        $this->currentPage = min(max(1, $this->currentPage), $totalPages);

        return view('livewire.admin.transaction-records', [
            'students' => $students->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values(),
            'recordCount' => $records->count(),
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'results' => self::RESULTS,
            'periods' => self::PERIODS,
        ])->layout('layouts.app', ['title' => 'Transaction Records']);
    }
}