<?php

namespace App\Livewire\Admin\Workflow;

use App\Enums\TransactionStatus;
use App\Exceptions\Workflow\WorkflowException;
use App\Jobs\ReviewTransactionSubmission;
use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Services\Workflow\TransactionWorkflow;
use App\Support\Issuers;
use App\Support\ReviewQueue;
use App\Support\WaitingTime;
use App\Workflow\AuditLabels;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * VPSD action queue for digital (workflow) transactions: only what is still open.
 * Finished ones (completed, rejected, voided) are looked up in Transaction Records.
 *
 * A row opens a side panel with the AI pre-check and the quick decisions, so most
 * transactions can be handled without leaving the list. Anything that needs more
 * (reviewer fields, a rater identity check) links to the full record.
 */
class Index extends Component
{
    use WithPagination;

    public const TABS = [
        // In the order a request finishes (the queue cards show them in this order)
        'issuance' => 'Issuance',
        'signing' => 'Signing',
        'action_required' => 'Revision',
        'attention' => 'Review',
    ];

    /** Tabs whose waiting time is the office's (or a signatory's) to act on, so it is flagged. */
    public const FLAGGED_TABS = ReviewQueue::FLAGGED;

    /** Sortable columns: key => label. */
    public const SORTS = [
        'student' => 'Student',
        'status' => 'Status',
        'waiting' => 'Time elapsed',
        'risk' => 'AI suggestion',
    ];

    /** AI filter: key => label. */
    public const AI_FILTERS = [
        '' => 'All',
        'flagged' => 'Flagged',
        'clean' => 'Clear',
        'unchecked' => 'Unchecked',
    ];

    /** Most transactions the AI check is queued for in one click. */
    private const BULK_LIMIT = 50;

    #[Url(except: 'attention')]
    public string $tab = 'attention';

    #[Url(except: '')]
    public string $search = '';

    /** "key:asc" or "key:desc". Empty means the default order for the tab. */
    #[Url(except: '')]
    public string $sort = '';

    #[Url(except: '')]
    public string $ai = '';

    /* Filters pop-up (besides AI suggestion): time elapsed word, document type, date filed range, year level, program. */
    #[Url(except: '')]
    public string $elapsed = '';

    #[Url(as: 'doc', except: '')]
    public string $docType = '';

    #[Url(as: 'from', except: '')]
    public string $dateFrom = '';

    #[Url(as: 'to', except: '')]
    public string $dateTo = '';

    #[Url(except: '')]
    public string $year = '';

    #[Url(except: '')]
    public string $program = '';

    /** The transaction shown in the side panel. */
    #[Url(as: 'open', except: null)]
    public ?int $selected = null;

    /** Rows ticked for a bulk action. @var array<int, string|int> */
    public array $picked = [];

    /** 'request' | 'reject' while the decision dialog is open. */
    public ?string $decision = null;
    public string $message = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSort(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, self::TABS)) {
            $this->tab = $tab;
            $this->picked = [];
            $this->resetPage();
        }
    }

    public function setAi(string $filter): void
    {
        if (array_key_exists($filter, self::AI_FILTERS)) {
            $this->ai = $filter;
            $this->picked = [];
            $this->resetPage();
        }
    }

    /** Any filter changed: back to page 1, nothing ticked. */
    public function updated(string $name): void
    {
        if (in_array($name, ['ai', 'elapsed', 'docType', 'dateFrom', 'dateTo', 'year', 'program'], true)) {
            $this->picked = [];
            $this->resetPage();
        }
    }

    /** Filters pop-up: every filter off. */
    public function resetFilters(): void
    {
        $this->reset(['ai', 'elapsed', 'docType', 'dateFrom', 'dateTo', 'year', 'program']);
        $this->picked = [];
        $this->resetPage();
    }

    /** How many filters in the pop-up are on (the number on the Filters button). */
    private function activeFilters(): int
    {
        return collect([$this->ai, $this->elapsed, $this->docType, $this->dateFrom . $this->dateTo, $this->year, $this->program])
            ->filter(fn ($v) => $v !== '')->count();
    }

    /** The pop-up's own filters on the list (AI suggestion is applied by applyAiFilter). */
    private function applyFilters(Builder $q): void
    {
        if (isset(WaitingTime::FILTER[$this->elapsed])) {
            [$atLeast, $under] = WaitingTime::FILTER[$this->elapsed];
            ReviewQueue::waited($q, $atLeast, $under);
        }
        if ($this->docType !== '' && DocumentRegistry::find($this->docType)) {
            $q->where('document_type', $this->docType);
        }
        $date = fn (string $d) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
        if ($from = $date($this->dateFrom)) {
            $q->whereDate('submitted_at', '>=', $from);
        }
        if ($to = $date($this->dateTo)) {
            $q->whereDate('submitted_at', '<=', $to);
        }
        if (in_array($this->year, ['1', '2', '3', '4'], true)) {
            $q->whereHas('user', fn ($u) => $u->where('year_level', $this->year));
        }
        if (ctype_digit($this->program)) {
            $q->whereHas('user', fn ($u) => $u->where('program_id', (int) $this->program));
        }
    }

    /** Choices for the pop-up's selects: documents in the open queues, and programs. */
    private function filterOptions(): array
    {
        $docs = ReviewQueue::base()->whereNotIn('workflow_status', ['completed', 'rejected', 'voided'])
            ->distinct()->pluck('document_type')
            ->mapWithKeys(fn ($k) => [$k => DocumentRegistry::find($k)?->name()])
            ->filter()->sort()->all();

        return [
            'elapsed' => ['today' => 'Today', 'recent' => 'Recent', 'slow' => 'Slow', 'late' => 'Late', 'overdue' => 'Overdue'],
            'docs' => $docs,
            'years' => ['1' => '1st year', '2' => '2nd year', '3' => '3rd year', '4' => '4th year'],
            'programs' => \App\Models\Program::query()->orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    /** A queue card: the whole queue, without the AI filter. */
    public function openQueue(string $tab): void
    {
        $this->ai = '';
        $this->setTab($tab);
    }

    /** The "Flagged by AI" card: the review queue, narrowed to what the AI wants a person to look at. */
    public function showFlagged(): void
    {
        $this->tab = 'attention';
        $this->ai = 'flagged';
        $this->picked = [];
        $this->resetPage();
    }

    /** Column header click: ascending, then descending, then back to the default order. */
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

    /* --------------------------- side panel --------------------------- */

    public function select(int $id): void
    {
        $this->selected = $this->selected === $id ? null : $id;
        $this->closeDecision();
    }

    public function closePanel(): void
    {
        $this->selected = null;
        $this->closeDecision();
    }

    /* ---------------------------- AI check ---------------------------- */

    public function runAiCheck(int $id): void
    {
        $ws = $this->checkable()->whereKey($id)->first();

        if (! $ws) {
            $this->toast('error', 'Nothing to check', 'This transaction is no longer open.');

            return;
        }

        ReviewTransactionSubmission::start($ws);
    }

    /** Bulk bar: run the AI check on the ticked rows. */
    public function checkPicked(): void
    {
        $ids = collect($this->picked)->map(fn ($id) => (int) $id)->filter()->take(self::BULK_LIMIT)->all();
        $rows = $this->checkable()->whereKey($ids)->get();

        $rows->each(fn ($ws) => ReviewTransactionSubmission::start($ws));
        $this->picked = [];

        $this->toast('success', 'AI check started', $rows->count() . ' ' . Str::plural('transaction', $rows->count()) . ' queued. Results appear here in a moment.');
    }

    /** Toolbar: run the AI check on everything in this queue that has no result yet. */
    public function checkUnchecked(): void
    {
        $rows = ReviewQueue::apply($this->checkable(), $this->tab)
            ->where(fn ($q) => $q->whereNull('ai_flag')->orWhere('ai_flag', 'review_failed'))
            ->limit(self::BULK_LIMIT)
            ->get();

        $rows->each(fn ($ws) => ReviewTransactionSubmission::start($ws));

        $this->toast('success', 'AI check started', $rows->count() . ' ' . Str::plural('transaction', $rows->count()) . ' queued. Results appear here in a moment.');
    }

    public function clearPicked(): void
    {
        $this->picked = [];
    }

    /* ---------------------------- decisions --------------------------- */

    /** Accepts the answers and opens the signing chain (starting the review first if needed). */
    public function quickSend(int $id): void
    {
        $ws = ReviewQueue::base()->whereKey($id)->first();
        if (! $ws || ! $this->decisions($ws)['send']) {
            return;
        }

        if ($reason = $this->needsFullRecord($ws)) {
            $this->addError('action', $reason);

            return;
        }

        $this->act(function (TransactionWorkflow $wf, User $u) use ($ws) {
            if ($ws->workflow_status === TransactionStatus::Submitted) {
                $ws = $wf->startReview($ws, $u);
            }
            $wf->acceptForSigning($ws, $u, null, []);
        }, 'Sent for signing', 'The first signatory has been notified.');
    }

    public function finish(int $id): void
    {
        $ws = ReviewQueue::base()->whereKey($id)->first();
        if (! $ws || ! $this->decisions($ws)['finish']) {
            return;
        }

        $this->act(fn (TransactionWorkflow $wf, User $u) => $wf->complete($ws, $u),
            'Completed', 'The record is sealed and the official PDF is being generated.');
    }

    public function openDecision(string $which): void
    {
        $ws = $this->selected ? ReviewQueue::base()->whereKey($this->selected)->first() : null;
        if (! $ws || ! in_array($which, ['request', 'reject'], true) || ! $this->decisions($ws)[$which]) {
            return;
        }

        $this->resetErrorBag();
        $this->decision = $which;
        $this->message = '';

        // Start from the AI's wording when it suggested this same decision; the reviewer edits or replaces it.
        $ai = StudentDocumentWorkspace::whereKey($this->selected)->value('ai_findings');
        $ai = is_string($ai) ? (json_decode($ai, true) ?: []) : ($ai ?? []);
        $suggests = ['request' => 'request_changes', 'reject' => 'reject'][$which];
        if (($ai['recommendation'] ?? null) === $suggests && ! empty($ai['message'])) {
            $this->message = $ai['message'];
        }
    }

    public function closeDecision(): void
    {
        $this->decision = null;
        $this->message = '';
        $this->resetErrorBag();
    }

    public function submitDecision(): void
    {
        $ws = $this->selected ? ReviewQueue::base()->whereKey($this->selected)->first() : null;
        if (! $ws || ! $this->decision || ! ($this->decisions($ws)[$this->decision] ?? false)) {
            $this->addError('action', 'This decision is no longer available for this transaction.');

            return;
        }

        $text = $this->message;

        if ($this->decision === 'request') {
            $this->act(function (TransactionWorkflow $wf, User $u) use ($ws, $text) {
                if ($ws->workflow_status === TransactionStatus::Submitted) {
                    $ws = $wf->startReview($ws, $u);
                }
                $wf->requestAction($ws, $u, $text);
            }, 'Changes requested', 'The student has been notified.');
        } else {
            $this->act(fn (TransactionWorkflow $wf, User $u) => $wf->reject($ws, $u, $text),
                'Transaction rejected', 'The student has been notified.');
        }
    }

    /** Which decisions the panel offers for this transaction (the same ones the full record allows). */
    private function decisions(StudentDocumentWorkspace $ws): array
    {
        $status = $ws->workflow_status->value;
        $doc = DocumentRegistry::find($ws->document_type);
        $opened = $ws->approvals()->exists();
        $signed = $ws->approvals()->where('status', 'approved')->exists();

        return [
            'send' => in_array($status, ['submitted', 'under_review'], true) && ! $opened,
            'request' => in_array($status, ['submitted', 'under_review'], true) && $doc && ! $doc->issuedByOffice() && ! $signed,
            'reject' => in_array($status, ['submitted', 'under_review', 'action_required'], true),
            'finish' => $status === 'approved',
        ];
    }

    /** Why this one has to be handled on the full record page, or null when the panel can do it. */
    private function needsFullRecord(StudentDocumentWorkspace $ws): ?string
    {
        $doc = DocumentRegistry::find($ws->document_type);

        return match (true) {
            ! $doc => 'This document type is no longer available.',
            (bool) $doc->reviewerFields() => 'This document needs a few details from the office first. Open the record to fill them in.',
            Show::identityCheckNeeded($ws) => 'The rater has to be checked by hand first. Open the record to confirm who they are.',
            default => null,
        };
    }

    private function act(callable $fn, string $title, string $text): void
    {
        $this->resetErrorBag();

        try {
            // One unit: "start review, then send" either happens fully or not at all.
            DB::transaction(fn () => $fn(app(TransactionWorkflow::class), Auth::user()));
        } catch (WorkflowException $e) {
            $this->addError('action', $e->getMessage());

            return;
        }

        $this->decision = null;
        $this->message = '';
        $this->toast('success', $title, $text);
    }

    private function toast(string $type, string $title, string $message): void
    {
        $this->dispatch('toast', type: $type, title: $title, message: $message);
    }

    /** Open transactions the AI check applies to: every one, including documents issued by the office or staff. */
    private function checkable(): Builder
    {
        return ReviewQueue::base()->whereNotIn('document_type', self::uncheckedKeys());
    }

    /** Document keys the AI does not check. None: issued documents are checked too. */
    public static function uncheckedKeys(): array
    {
        return [];
    }

    /* ----------------------------- sorting ---------------------------- */

    /** @return array{0: ?string, 1: ?string} */
    private function parsedSort(): array
    {
        [$key, $dir] = array_pad(explode(':', $this->sort, 2), 2, null);

        if (! array_key_exists((string) $key, self::SORTS) || ! in_array($dir, ['asc', 'desc'], true)) {
            return [null, null];
        }

        return [$key, $dir];
    }

    /** Options for the sort menu. */
    private function sortOptions(): array
    {
        // One word each. The column headers still flip any of these.
        $options = [
            '' => 'Oldest',
            'waiting:desc' => 'Newest',
            'student:asc' => 'Name',
            'status:asc' => 'Status',
            'risk:asc' => 'AI suggestion',
        ];
        // A header click can pick an order not listed: name it so the menu still shows it.
        if ($this->sort !== '' && ! isset($options[$this->sort])) {
            [$k, $d] = array_pad(explode(':', $this->sort, 2), 2, 'asc');
            $options[$this->sort] = (self::SORTS[$k] ?? 'Custom') . ($d === 'desc' ? ' ↓' : ' ↑');
        }

        return $options;
    }

    private function applySort(Builder $q): void
    {
        [$key, $dir] = $this->parsedSort();

        // Default and "waiting": the transaction that has waited longest comes first.
        $changed = 'COALESCE(status_changed_at, submitted_at)';
        $waitingSince = "CASE WHEN last_signed_at IS NOT NULL AND last_signed_at > {$changed} THEN last_signed_at ELSE {$changed} END";

        if (! $key) {
            $q->orderByRaw("{$waitingSince} asc");

            return;
        }

        $table = $q->getModel()->getTable();

        // "asc" on the AI column = most concerns first: flagged, failed, checking, unchecked, clean; then by risk.
        $flipped = $dir === 'asc' ? 'asc' : 'desc';
        $riskDir = $dir === 'asc' ? 'desc' : 'asc';
        $risk = $q->getConnection()->getDriverName() === 'sqlite'
            ? "CAST(json_extract(ai_findings, '$.risk') AS INTEGER)"
            : "CAST(JSON_UNQUOTE(JSON_EXTRACT(ai_findings, '$.risk')) AS UNSIGNED)";

        match ($key) {
            'waiting' => $q->orderByRaw("{$waitingSince} {$dir}"),
            // In the order work moves: New first, Ready to finish last (not the stored value's A-Z,
            // which put "approved" = Ready to finish before "submitted" = New).
            'status' => $q->orderByRaw("CASE workflow_status WHEN 'submitted' THEN 0 WHEN 'under_review' THEN 1 WHEN 'action_required' THEN 2 WHEN 'approved' THEN 3 ELSE 4 END {$dir}"),
            'risk' => $q->orderByRaw("CASE ai_flag WHEN 'flagged' THEN 0 WHEN 'review_failed' THEN 1 WHEN 'pending' THEN 2 WHEN 'clean' THEN 4 ELSE 3 END {$flipped}")
                ->orderByRaw("{$risk} {$riskDir}"),
            'student' => $q->orderBy(
                User::select('last_name')->whereColumn('users.id', "{$table}.user_id")->limit(1),
                $dir
            )->orderBy(
                User::select('first_name')->whereColumn('users.id', "{$table}.user_id")->limit(1),
                $dir
            ),
        };

        // Stable order when many rows share the same value.
        $q->orderByDesc('updated_at');
    }

    private function applyAiFilter(Builder $q): void
    {
        match ($this->ai) {
            'flagged' => $q->where('ai_flag', 'flagged'),
            'clean' => $q->where('ai_flag', 'clean'),
            'unchecked' => $q->whereNotIn('document_type', self::uncheckedKeys())
                ->where(fn ($w) => $w->whereNull('ai_flag')->orWhereIn('ai_flag', ['review_failed', 'pending'])),
            default => null,
        };
    }

    /* ------------------------------ render ---------------------------- */

    /** The selected transaction with everything the side panel shows, or null when it is not open any more. */
    private function panel(): ?array
    {
        if (! $this->selected) {
            return null;
        }

        $ws = ReviewQueue::withWaitingSince(ReviewQueue::base())
            ->with(['user.program', 'approvals.role', 'approvals.actor'])
            ->whereKey($this->selected)
            ->first();

        if (! $ws) {
            $this->selected = null;

            return null;
        }

        $doc = DocumentRegistry::find($ws->document_type);
        $status = $ws->workflow_status->value;
        $approvals = $ws->approvals->sortBy('step_order')->values();
        $signed = $approvals->filter(fn ($a) => $a->status->value === 'approved')->count();
        // Same counts the list rows carry, so the status reads the same in both places.
        $ws->approved_count = $signed;
        $ws->approvals_count = $approvals->count();
        $queue = collect(array_keys(self::TABS))->first(fn ($k) => ReviewQueue::query($k)->whereKey($ws->getKey())->exists());
        $can = $this->decisions($ws);

        return [
            'ws' => $ws,
            'document' => $doc,
            'queue' => $queue,
            'issuer' => Issuers::label(Issuers::forOne($ws)),
            'waiting' => WaitingTime::describe(ReviewQueue::waitingSince($ws), flag: in_array($queue, self::FLAGGED_TABS, true)),
            'approvals' => $approvals,
            'signed' => $signed,
            'audit' => AuditLabels::history($ws)->take(6),
            'checkable' => $doc && ! in_array($ws->document_type, self::uncheckedKeys(), true),
            // What the panel can decide on the spot.
            'canSend' => $can['send'],
            'sendBlocker' => $can['send'] ? $this->needsFullRecord($ws) : null,
            'canRequest' => $can['request'],
            'canReject' => $can['reject'],
            'canFinish' => $can['finish'],
        ];
    }

    public function render()
    {
        // Old bookmarks may still carry a removed tab (completed, voided, ...).
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'attention';
        }
        $queue = $this->tab;
        if (! array_key_exists($this->ai, self::AI_FILTERS)) {
            $this->ai = '';
        }

        $counts = collect(self::TABS)->map(fn ($label, $key) => ReviewQueue::query($key)->count());

        // Who has waited longest in each queue (the cards show the first few faces).
        $faces = collect(self::TABS)->map(fn ($label, $key) => ReviewQueue::query($key)
            ->with('user')
            ->orderBy('submitted_at')
            ->limit(3)
            ->get());

        $lateCounts = collect(self::FLAGGED_TABS)
            ->mapWithKeys(fn ($key) => [$key => ReviewQueue::apply(ReviewQueue::overdue(), $key)->count()]);

        $flaggedQuery = ReviewQueue::query('attention')->where('ai_flag', 'flagged');
        $flaggedCount = (clone $flaggedQuery)->count();
        $flaggedFaces = (clone $flaggedQuery)->with('user')->latest('ai_reviewed_at')->limit(3)->get();

        $q = ReviewQueue::withWaitingSince(ReviewQueue::query($queue))
            ->with('user.role')
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($a) => $a->where('status', 'approved'),
            ]);

        if (trim($this->search) !== '') {
            $term = trim($this->search);
            $s = '%' . $term . '%';
            // Document types are stored as registry keys, so match the readable name here.
            $docKeys = collect(DocumentRegistry::all())
                ->filter(fn ($doc, $key) => Str::contains($doc->name(), $term, ignoreCase: true) || Str::contains($key, $term, ignoreCase: true))
                ->keys()
                ->all();

            $q->where(fn ($w) => $w->where('transaction_code', 'like', $s)
                ->when($docKeys, fn ($x) => $x->orWhereIn('document_type', $docKeys))
                ->orWhere('field_values->reporter_name', 'like', $s)
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('student_number', 'like', $s)));
        }

        $this->applyAiFilter($q);
        $this->applyFilters($q);

        $this->applySort($q);

        [$sortKey, $sortDir] = $this->parsedSort();

        $rows = $q->paginate(50);
        $flagged = in_array($queue, self::FLAGGED_TABS, true);
        $skipAi = self::uncheckedKeys();
        // Waiting on student: the student has the next move, so show the plain age only.
        $issuers = Issuers::for($rows->getCollection());
        $rows->getCollection()->each(function (StudentDocumentWorkspace $row) use ($flagged, $issuers, $skipAi) {
            $row->waiting = WaitingTime::describe(ReviewQueue::waitingSince($row), flag: $flagged);
            $row->issuer = Issuers::label($issuers[$row->workspace_id] ?? null);
            $row->ai_applies = ! in_array($row->document_type, $skipAi, true);
        });

        $panel = $this->panel();

        return view('livewire.admin.workflow.index', [
            'rows' => $rows,
            'counts' => $counts,
            'faces' => $faces,
            'lateCounts' => $lateCounts,
            'flaggedCount' => $flaggedCount,
            'flaggedFaces' => $flaggedFaces,
            'tabs' => self::TABS,
            'aiFilters' => self::AI_FILTERS,
            'filterOptions' => $this->filterOptions(),
            'activeFilters' => $this->activeFilters(),
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
            'sortOptions' => $this->sortOptions(),
            'panel' => $panel,
            // Keep the list fresh while any AI check on screen is still running.
            'polling' => $rows->getCollection()->contains('ai_flag', 'pending') || ($panel && $panel['ws']->ai_flag === 'pending'),
        ])->layout('layouts.app', ['title' => 'Workflow Review']);
    }
}
