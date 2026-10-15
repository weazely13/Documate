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

class DocumentsIndex extends Component
{
    /** Paperless statuses per tab. Old (template) rows keep using the `status` column. */
    private const WORKFLOW_TABS = [
        'pending' => ['draft', 'submitted', 'under_review', 'action_required', 'approved'],
        'completed' => ['completed'],
        'closed' => ['rejected', 'voided'],
        'issued' => []
    ];

    public string $tab = 'pending';
    public string $search = '';
    public string $sort = 'desc';
    public array $selected = [];
    public bool $selectAll = false;
    public ?string $deletingId = null;
    public bool $confirmingBulkDelete = false;

    public function updatedTab(): void
    {
        if (! array_key_exists($this->tab, self::WORKFLOW_TABS)) {
            $this->tab = 'pending';
        }
        $this->resetSelection();
    }

    public function updatedSearch(): void
    {
        $this->resetSelection();
    }

    public function resetSelection(): void
    {
        $this->selected = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;
        $this->selected = $this->selectAll ? $this->currentWorkspaceIds() : [];
    }
    /** Documents the VPSD office issued to this student that still need their attention. */
    #[Computed]
    public function officeNotices()
    {
        return StudentDocumentWorkspace::officeNoticesFor((int) Auth::id());
    }
    /** Only old template rows can be bulk-selected; paperless records are never deleted from here. */
    private function currentWorkspaceIds(): array
    {
        return $this->baseQuery()->whereNull('workflow_status')
            ->pluck('workspace_id')->map(fn ($id) => (string) $id)->all();
    }

    private function issuedKeys(): array
    {
        return collect(DocumentRegistry::all())->filter(fn ($d) => $d->issuedByOffice())->keys()->all();
    }

    private function tabQuery(string $tab): Builder
    {
        $issued = $this->issuedKeys();
        $q = StudentDocumentWorkspace::query()->where('user_id', Auth::id());

        if ($tab === 'issued') {
            return $q->whereNotNull('workflow_status')->whereIn('document_type', $issued);
        }

        return $q
            ->where(function (Builder $q) use ($tab) {
                if ($tab !== 'closed') {
                    $q->where(fn (Builder $l) => $l->whereNull('workflow_status')->where('status', $tab));
                }
                $q->orWhereIn('workflow_status', self::WORKFLOW_TABS[$tab] ?? []);
            })
            ->where(fn (Builder $w) => $w->whereNull('document_type')->orWhereNotIn('document_type', $issued));
    }

    #[Computed]
    public function issuedCount(): int
    {
        return $this->tabQuery('issued')->count();
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

    #[Computed]
    public function pendingCount(): int
    {
        return $this->tabQuery('pending')->count();
    }

    #[Computed]
    public function completedCount(): int
    {
        return $this->tabQuery('completed')->count();
    }

    #[Computed]
    public function closedCount(): int
    {
        return $this->tabQuery('closed')->count();
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
            ->first();

        if ($workspace) {
            $this->purgeWorkspaceFiles($workspace);
            $workspace->delete();
        }

        $this->deletingId = null;
        $this->resetSelection();
    }

    public function confirmBulkDelete(): void
    {
        if (! empty($this->selected)) {
            $this->confirmingBulkDelete = true;
        }
    }

    public function cancelBulkDelete(): void
    {
        $this->confirmingBulkDelete = false;
    }

    public function bulkDelete(): void
    {
        StudentDocumentWorkspace::where('user_id', Auth::id())
            ->whereNull('workflow_status')
            ->whereIn('workspace_id', $this->selected)
            ->get()
            ->each(function (StudentDocumentWorkspace $workspace) {
                $this->purgeWorkspaceFiles($workspace);
                $workspace->delete();
            });

        $this->confirmingBulkDelete = false;
        $this->resetSelection();
    }

    private function purgeWorkspaceFiles(StudentDocumentWorkspace $workspace): void
    {
        foreach (['generated_pdf_path', 'supporting_file_path', 'reference_image_path'] as $field) {
            if ($workspace->{$field}) {
                Storage::disk('public')->delete($workspace->{$field});
            }
        }
    }

    public function render()
    {
        $workspaces = $this->baseQuery()
            ->with('template')
            ->withCount([
                'approvals',
                'approvals as approved_count' => fn ($q) => $q->where('status', 'approved'),
            ])
            ->orderBy('updated_at', $this->sort)
            ->get();

        $issuedGroups = null;
        if ($this->tab === 'issued') {
            $isDone = fn ($w) => $w->workflow_status?->value === 'completed';
            $isClosed = fn ($w) => in_array($w->workflow_status?->value, ['rejected', 'voided'], true);

            $issuedGroups = [
                'open' => $workspaces->reject($isDone)->reject($isClosed)->values(),
                'completed' => $workspaces->filter($isDone)->values(),
                'closed' => $workspaces->filter($isClosed)->values(),
            ];
        }

        return view('livewire.student.documents.documents-index', [
            'workspaces' => $workspaces,
            'issuedGroups' => $issuedGroups,
        ])->layout('layouts.app', ['title' => 'Documents']);
    }
}