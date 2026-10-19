<?php

namespace App\Livewire\Signatory;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** File manager: every document the logged-in signatory issued, grouped in folders by document type. */
class Issued extends Component
{
    use WithPagination;

    #[Url(as: 'folder', except: '')]
    public string $folder = '';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: 'newest')]
    public string $sort = 'newest';   // newest | oldest

    #[Url(except: 'list')]
    public string $view = 'list';     // list | grid

    public function mount(): void
    {
        abort_unless(Auth::user()->isSignatory(), 403);
    }

    public function updated($name): void
    {
        if (in_array($name, ['search', 'status', 'sort', 'folder'], true)) {
            $this->resetPage();
        }
    }

    public function setFolder(string $key): void
    {
        $this->folder = ($key === '' || DocumentRegistry::has($key)) ? $key : '';
        $this->resetPage();
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'grid' ? 'grid' : 'list';
    }

    public function clearFilters(): void
    {
        $this->reset(['folder', 'search', 'status', 'sort']);
        $this->resetPage();
    }

    private function base(): Builder
    {
        return StudentDocumentWorkspace::usingWorkflow()
            ->whereNotNull('document_type')
            ->where('workflow_status', '!=', TransactionStatus::Draft->value)
            ->issuedBy(Auth::user());
    }

    public function render()
    {
        $folders = $this->base()->toBase()
            ->selectRaw('document_type, COUNT(*) AS c')->groupBy('document_type')->pluck('c', 'document_type');

        $statusCounts = $this->base()->toBase()
            ->selectRaw('workflow_status, COUNT(*) AS c')->groupBy('workflow_status')->pluck('c', 'workflow_status');

        $term = trim($this->search);

        $rows = $this->base()
            ->with('user')
            ->withCount(['approvals', 'approvals as approved_count' => fn ($q) => $q->where('status', 'approved')])
            ->when($this->folder !== '', fn ($q) => $q->where('document_type', $this->folder))
            ->when($this->status !== '', fn ($q) => $q->where('workflow_status', $this->status))
            ->when($term !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('transaction_code', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->whereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$term}%"])
                    ->orWhere('student_number', 'like', "%{$term}%"))))
            ->orderBy('submitted_at', $this->sort === 'oldest' ? 'asc' : 'desc')
            ->paginate($this->view === 'grid' ? 12 : 15);

        $stats = [
            'total' => (int) $folders->sum(),
            'completed' => (int) ($statusCounts['completed'] ?? 0),
            'open' => (int) collect(['submitted', 'under_review', 'action_required', 'approved'])->sum(fn ($s) => $statusCounts[$s] ?? 0),
            'closed' => (int) collect(['rejected', 'voided'])->sum(fn ($s) => $statusCounts[$s] ?? 0),
        ];

        return view('livewire.signatory.issued', [
            'rows' => $rows,
            'folders' => $folders,
            'stats' => $stats,
            'statuses' => collect(TransactionStatus::cases())
                ->reject(fn ($s) => $s === TransactionStatus::Draft)
                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
        ])->layout('layouts.app', ['title' => 'Issued Documents']);
    }
}