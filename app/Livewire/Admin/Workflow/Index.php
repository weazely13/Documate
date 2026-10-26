<?php

namespace App\Livewire\Admin\Workflow;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Support\Issuers;
use App\Support\ReviewQueue;
use App\Support\WaitingTime;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * VPSD action queue for digital (workflow) transactions: only what is still open.
 * Finished ones (completed, rejected, voided) are looked up in Transaction Records.
 */
class Index extends Component
{
    use WithPagination;

    public const TABS = [
        'attention' => 'To review',
        'signing' => 'In signing',
        'action_required' => 'Waiting on student',
    ];

    /** Tabs whose waiting time is the office's (or a signatory's) to act on, so it is flagged. */
    public const FLAGGED_TABS = ReviewQueue::FLAGGED;

    /** Sortable columns: key => label. */
    public const SORTS = [
        'student' => 'Student',
        'status' => 'Status',
        'waiting' => 'Waiting',
    ];

    #[Url(except: 'attention')]
    public string $tab = 'attention';

    #[Url(except: '')]
    public string $search = '';

    /** "key:asc" or "key:desc". Empty means the default order for the tab. */
    #[Url(except: '')]
    public string $sort = '';

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
            $this->resetPage();
        }
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

    /** @return array{0: ?string, 1: ?string} */
    private function parsedSort(): array
    {
        [$key, $dir] = array_pad(explode(':', $this->sort, 2), 2, null);

        if (! array_key_exists((string) $key, self::SORTS) || ! in_array($dir, ['asc', 'desc'], true)) {
            return [null, null];
        }

        return [$key, $dir];
    }

    /** Options for the mobile sort menu. */
    private function sortOptions(): array
    {
        $options = ['' => 'Default order'];

        foreach (self::SORTS as $key => $label) {
            $options["{$key}:asc"] = match ($key) {
                'waiting' => 'Waiting: longest first',
                default => "{$label}: A to Z",
            };
            $options["{$key}:desc"] = match ($key) {
                'waiting' => 'Waiting: shortest first',
                default => "{$label}: Z to A",
            };
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

        match ($key) {
            'waiting' => $q->orderByRaw("{$waitingSince} {$dir}"),
            'status' => $q->orderBy('workflow_status', $dir),
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

    public function render()
    {
        // Old bookmarks may still carry a removed tab (completed, voided, ...).
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'attention';
        }

        $counts = collect(self::TABS)->map(fn ($label, $key) => ReviewQueue::query($key)->count());

        $overdue = ReviewQueue::withWaitingSince(ReviewQueue::overdue())->with('user')->get()
            ->each(fn ($w) => $w->waiting_since = ReviewQueue::waitingSince($w))
            ->sortBy('waiting_since')
            ->values();
        $lateCounts = collect(self::FLAGGED_TABS)
            ->mapWithKeys(fn ($key) => [$key => ReviewQueue::apply(ReviewQueue::overdue(), $key)->count()]);

        $q = ReviewQueue::withWaitingSince(ReviewQueue::query($this->tab))
            ->with('user.role')
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($a) => $a->where('status', 'approved'),
            ]);

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $q->where(fn ($w) => $w->where('transaction_code', 'like', $s)
                ->orWhere('field_values->reporter_name', 'like', $s)
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('student_number', 'like', $s)));
        }

        $this->applySort($q);

        [$sortKey, $sortDir] = $this->parsedSort();

        $rows = $q->paginate(20);
        $flagged = in_array($this->tab, self::FLAGGED_TABS, true);
        // Waiting on student: the student has the next move, so show the plain age only.
        $issuers = Issuers::for($rows->getCollection());
        $rows->getCollection()->each(function (StudentDocumentWorkspace $row) use ($flagged, $issuers) {
            $row->waiting = WaitingTime::describe(ReviewQueue::waitingSince($row), flag: $flagged);
            $row->issuer = Issuers::label($issuers[$row->workspace_id] ?? null);
        });

        return view('livewire.admin.workflow.index', [
            'rows' => $rows,
            'counts' => $counts,
            'lateCounts' => $lateCounts,
            'overdue' => $overdue,
            'readyCount' => ReviewQueue::base()->where('workflow_status', 'approved')->count(),
            'tabs' => self::TABS,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
            'sortOptions' => $this->sortOptions(),
        ])->layout('layouts.app', ['title' => 'Workflow Review']);
    }
}