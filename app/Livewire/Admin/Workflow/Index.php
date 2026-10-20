<?php

namespace App\Livewire\Admin\Workflow;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** VPSD review queue for digital (workflow) transactions. Drafts are never shown. */
class Index extends Component
{
    use WithPagination;

    public const TABS = [
        'attention' => 'Needs action',
        'submitted' => 'New',
        'signing' => 'In signing',
        'action_required' => 'Waiting on student',
        'approved' => 'Ready to complete',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'voided' => 'Voided',
        'issued' => 'Issued by office',
        'all' => 'All',
    ];

    /** Sortable columns: key => [label, column or special handler]. */
    public const SORTS = [
        'code' => 'Code',
        'student' => 'Student',
        'document' => 'Document',
        'submitted' => 'Submitted',
        'signatures' => 'Signatures',
        'status' => 'Status',
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
                'submitted' => 'Submitted: oldest first',
                'signatures' => 'Signatures: fewest first',
                default => "{$label}: A to Z",
            };
            $options["{$key}:desc"] = match ($key) {
                'submitted' => 'Submitted: newest first',
                'signatures' => 'Signatures: most first',
                default => "{$label}: Z to A",
            };
        }

        return $options;
    }

    /** Document keys that the VPSD office or a role holder issues to students. */
    private function officeKeys(): array
    {
        return collect(DocumentRegistry::all())
            ->filter(fn ($d) => $d->issuedByOffice())
            ->keys()
            ->all();
    }

    private function staffKeys(): array
    {
        return collect(DocumentRegistry::all())
            ->filter(fn ($d) => $d->issuerIsStaff())
            ->keys()
            ->all();
    }

    private function base(): Builder
    {
        return StudentDocumentWorkspace::query()
            ->usingWorkflow()
            ->whereNotNull('document_type')
            ->where('workflow_status', '!=', 'draft');
    }

    private function applyTab(Builder $q, string $tab): Builder
    {
        return match ($tab) {
            'attention' => $q->where(fn ($w) => $w
                ->whereIn('workflow_status', ['submitted', 'approved'])
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereDoesntHave('approvals'))
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereIn('document_type', $this->staffKeys()))),
            'signing' => $q->where('workflow_status', 'under_review')->whereHas('approvals'),
            'submitted', 'action_required', 'approved', 'completed', 'rejected', 'voided' => $q->where('workflow_status', $tab),
            'issued' => $q->whereIn('document_type', $this->officeKeys()),
            default => $q,
        };
    }

    private function applySort(Builder $q): void
    {
        [$key, $dir] = $this->parsedSort();

        if (! $key) {
            // Default: oldest first for the action queue, most recently touched first elsewhere.
            $this->tab === 'attention' ? $q->orderBy('submitted_at') : $q->latest('updated_at');

            return;
        }

        $table = $q->getModel()->getTable();

        match ($key) {
            'code' => $q->orderBy('transaction_code', $dir),
            'document' => $q->orderBy('document_type', $dir),
            'submitted' => $q->orderBy('submitted_at', $dir),
            'signatures' => $q->orderBy('approvals_count', $dir),
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
        $counts = collect(self::TABS)->map(fn ($label, $key) => $this->applyTab($this->base(), $key)->count());

        $q = $this->applyTab($this->base(), $this->tab)
            ->with('user')
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($a) => $a->where('status', 'approved'),
            ]);

        if (trim($this->search) !== '') {
            $s = '%' . trim($this->search) . '%';
            $q->where(fn ($w) => $w->where('transaction_code', 'like', $s)
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', $s)
                    ->orWhere('last_name', 'like', $s)
                    ->orWhere('student_number', 'like', $s)));
        }

        $this->applySort($q);

        [$sortKey, $sortDir] = $this->parsedSort();

        return view('livewire.admin.workflow.index', [
            'rows' => $q->paginate(20),
            'counts' => $counts,
            'tabs' => self::TABS,
            'sortKey' => $sortKey,
            'sortDir' => $sortDir,
            'sortOptions' => $this->sortOptions(),
        ])->layout('layouts.app', ['title' => 'Workflow Review']);
    }
}