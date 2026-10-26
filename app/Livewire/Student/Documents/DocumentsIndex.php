<?php

namespace App\Livewire\Student\Documents;

use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Enums\TransactionStatus;
use Carbon\Carbon;

/**
 * The student's documents in two lists: Active (still moving) and History (finished).
 * Documents the office issued to the student sit in the same lists, tagged "From VPSD".
 */
class DocumentsIndex extends Component
{
    /** Paperless statuses that are finished. Paper-based rows use `status = completed`. */
    private const FINISHED = ['completed', 'rejected', 'voided'];

    public const TABS = ['active' => 'Active', 'history' => 'History'];

    public string $tab = 'active';
    public string $search = '';
    public ?string $deletingId = null;

    public function updatedTab(): void
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'active';
        }
    }

    /** Keys of documents the office (or a staff member) issues to students. */
    #[Computed]
    public function issuedKeys(): array
    {
        return collect(DocumentRegistry::all())->filter(fn ($d) => $d->issuedByOffice())->keys()->all();
    }

    private function tabQuery(string $tab): Builder
    {
        $q = StudentDocumentWorkspace::query()->where('user_id', Auth::id());

        return $tab === 'history'
            ? $q->where(fn (Builder $w) => $w
                ->whereIn('workflow_status', self::FINISHED)
                ->orWhere(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', 'completed')))
            : $q->where(fn (Builder $w) => $w
                ->where(fn (Builder $x) => $x->whereNotNull('workflow_status')->whereNotIn('workflow_status', self::FINISHED))
                ->orWhere(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', '!=', 'completed')));
    }

    #[Computed]
    public function activeCount(): int
    {
        return $this->tabQuery('active')->count();
    }

    #[Computed]
    public function historyCount(): int
    {
        return $this->tabQuery('history')->count();
    }

    private function baseQuery(): Builder
    {
        $term = trim($this->search);

        return $this->tabQuery($this->tab)->when($term !== '', function (Builder $q) use ($term) {
            $keys = collect(DocumentRegistry::all())
                ->filter(fn ($d) => Str::contains(Str::lower($d->name()), Str::lower($term)))
                ->keys()->all();

            $q->where(fn (Builder $s) => $s
                ->whereHas('template', fn ($t) => $t->where('name', 'like', "%{$term}%"))
                ->orWhereIn('document_type', $keys)
                ->orWhere('transaction_code', 'like', "%{$term}%"));
        });
    }

    public function confirmDelete(string $workspaceId): void
    {
        $this->deletingId = $workspaceId;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
    }

    public function deleteWorkspace(): void
    {
        $workspace = StudentDocumentWorkspace::where('workspace_id', $this->deletingId)
            ->where('user_id', Auth::id())
            ->whereNull('workflow_status')   // paperless records are kept for the audit trail
            ->where('status', '!=', 'completed')
            ->first();

        if ($workspace) {
            $workspace->appointments()
                ->whereIn('status', ['pending', 'approved'])
                ->get()
                ->each(function ($appt) {
                    $appt->update(['status' => 'retracted']);
                    $appt->logStatus('retracted', 'Document deleted by student.');
                });

            $workspace->delete();
        }

        $this->deletingId = null;
    }

    public function render()
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'active';
        }

        $q = $this->baseQuery()
            ->with(['template', 'approvals.role'])
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ]);

        // Active: what needs the student goes first (changes asked, drafts), then newest.
        if ($this->tab === 'active') {
            $q->orderByRaw("CASE workflow_status WHEN 'action_required' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END");
        }

        return view('livewire.student.documents.documents-index', [
            'workspaces' => $q->latest('updated_at')->get(),
            'tabs' => self::TABS,
        ])->layout('layouts.app', ['title' => 'My Documents']);
    }
}