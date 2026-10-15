<?php

namespace App\Livewire\Signatory;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Read-only view of every submitted transaction, for the Vice President for Student Development. */
class Oversight extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $type = '';

    #[Url(except: '')]
    public string $status = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->isVpsd(), 403);
    }

    public function updated($name): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type', 'status']);
        $this->resetPage();
    }

    private function base()
    {
        return StudentDocumentWorkspace::usingWorkflow()
            ->whereNotNull('document_type')
            ->where('workflow_status', '!=', TransactionStatus::Draft->value);
    }

    public function render()
    {
        $term = trim($this->search);

        $rows = $this->base()
            ->with('user')
            ->withCount(['approvals', 'approvals as approved_count' => fn ($q) => $q->where('status', 'approved')])
            ->when($this->type !== '', fn ($q) => $q->where('document_type', $this->type))
            ->when($this->status !== '', fn ($q) => $q->where('workflow_status', $this->status))
            ->when($term !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('transaction_code', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->whereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$term}%"])
                    ->orWhere('student_number', 'like', "%{$term}%"))))
            ->orderByDesc('updated_at')
            ->paginate(15);

        $counts = [
            'all' => $this->base()->count(),
            'open' => $this->base()->whereIn('workflow_status', ['submitted', 'under_review', 'action_required', 'approved'])->count(),
            'completed' => $this->base()->where('workflow_status', 'completed')->count(),
            'closed' => $this->base()->whereIn('workflow_status', ['rejected', 'voided'])->count(),
        ];

        return view('livewire.signatory.oversight', [
            'rows' => $rows,
            'counts' => $counts,
            'docTypes' => collect(DocumentRegistry::all())->map(fn ($d) => $d->name())->sort()->all(),
            'statuses' => collect(TransactionStatus::cases())
                ->reject(fn ($s) => $s === TransactionStatus::Draft)
                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
        ])->layout('layouts.app', ['title' => 'All Documents']);
    }
}