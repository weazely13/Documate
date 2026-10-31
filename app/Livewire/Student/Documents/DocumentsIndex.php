<?php

namespace App\Livewire\Student\Documents;

use App\Models\StudentDocumentWorkspace;
use App\Support\Issuers;
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
 * The student's documents: Active (theirs, still moving), Issued to you (documents the VPSD office
 * or a signatory issued to them, and other students' transactions that list them) and History (theirs, finished).
 */
class DocumentsIndex extends Component
{
    /** Paperless statuses that are finished. Paper-based rows use `status = completed`. */
    private const FINISHED = ['completed', 'rejected', 'voided'];

    public const TABS = ['active' => 'Active', 'issued' => 'Issued to you', 'history' => 'History'];

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
        $issued = $this->issuedKeys;

        if ($tab === 'issued') {
            // Issued to the student, plus other students' transactions that list them (their read-only copy).
            return StudentDocumentWorkspace::query()
                ->whereNotNull('workflow_status')
                ->where(fn (Builder $w) => $w
                    ->where(fn (Builder $own) => $own->where('user_id', Auth::id())->whereIn('document_type', $issued))
                    ->orWhere(fn (Builder $named) => $named
                        ->where('workflow_status', '!=', 'draft')
                        ->whereHas('participants', fn (Builder $p) => $p->where('users.id', Auth::id()))));
        }

        $q = StudentDocumentWorkspace::query()->where('user_id', Auth::id());

        // Active and History hold the student's own documents only.
        $q->where(fn (Builder $w) => $w->whereNull('document_type')->orWhereNotIn('document_type', $issued));

        return $tab === 'history'
            ? $q->where(fn (Builder $w) => $w
                ->whereIn('workflow_status', self::FINISHED)
                ->orWhere(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', 'completed')))
            : $q->where(fn (Builder $w) => $w
                ->where(fn (Builder $x) => $x->whereNotNull('workflow_status')->whereNotIn('workflow_status', self::FINISHED))
                ->orWhere(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', '!=', 'completed')));
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        return collect(self::TABS)->map(fn ($label, $tab) => $this->tabQuery($tab)->count())->all();
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

        // Active: what needs the student first (changes asked, drafts). Issued: open ones first. Then newest.
        if ($this->tab === 'active') {
            $q->orderByRaw("CASE workflow_status WHEN 'action_required' THEN 0 WHEN 'draft' THEN 1 ELSE 2 END");
        } elseif ($this->tab === 'issued') {
            $q->orderByRaw("CASE WHEN workflow_status IN ('completed', 'rejected', 'voided') THEN 1 ELSE 0 END");
        }

        $workspaces = $q->latest('updated_at')->get();

        return view('livewire.student.documents.documents-index', [
            'workspaces' => $workspaces,
            'issuers' => $this->tab === 'issued' ? Issuers::for($workspaces) : [],
            'tabs' => self::TABS,
        ])->layout('layouts.app', ['title' => 'My Documents']);
    }
}