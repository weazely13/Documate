<?php

namespace App\Livewire\Admin\Workflow;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use App\Workflow\Documents\DocumentRegistry;

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

    #[Url(except: 'attention')]
    public string $tab = 'attention';

    #[Url(except: '')]
    public string $search = '';

    protected $paginationTheme = 'tailwind';

    public function updatingSearch(): void
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
    /** Document keys that the VPSD office or a role holder issues to students. */
    private function officeKeys(): array
    {
        return collect(DocumentRegistry::all())
            ->filter(fn ($d) => $d->issuedByOffice())
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
                ->orWhere(fn ($x) => $x->where('workflow_status', 'under_review')->whereDoesntHave('approvals'))),
            'signing' => $q->where('workflow_status', 'under_review')->whereHas('approvals'),
            'submitted', 'action_required', 'approved', 'completed', 'rejected', 'voided' => $q->where('workflow_status', $tab),
            'issued' => $q->whereIn('document_type', $this->officeKeys()),
            default => $q,
        };
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

        $this->tab === 'attention' ? $q->orderBy('submitted_at') : $q->latest('updated_at');

        return view('livewire.admin.workflow.index', [
            'rows' => $q->paginate(20),
            'counts' => $counts,
            'tabs' => self::TABS,
        ])->layout('layouts.app', ['title' => 'Workflow Review']);
    }
}
