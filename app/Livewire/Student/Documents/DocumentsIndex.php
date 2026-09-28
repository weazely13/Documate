<?php

namespace App\Livewire\Student\Documents;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DocumentsIndex extends Component
{
    public string $tab = 'pending';
    public string $search = '';
    public string $sort = 'desc';
    public array $selected = [];
    public bool $selectAll = false;
    public ?string $deletingId = null;
    public bool $confirmingBulkDelete = false;

    public function updatedTab(): void
    {
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

    private function currentWorkspaceIds(): array
    {
        return $this->baseQuery()->pluck('workspace_id')->map(fn ($id) => (string) $id)->all();
    }

    private function baseQuery()
    {
        return StudentDocumentWorkspace::query()
            ->with('template')
            ->where('user_id', Auth::id())
            ->where('status', $this->tab)
            ->when($this->search !== '', fn ($q) => $q->whereHas(
                'template',
                fn ($q2) => $q2->where('name', 'like', '%' . $this->search . '%')
            ));
    }

    #[Computed]
    public function pendingCount(): int
    {
        return StudentDocumentWorkspace::where('user_id', Auth::id())->where('status', 'pending')->count();
    }

    #[Computed]
    public function completedCount(): int
    {
        return StudentDocumentWorkspace::where('user_id', Auth::id())->where('status', 'completed')->count();
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
        $workspaces = $this->baseQuery()->orderBy('updated_at', $this->sort)->get();

        return view('livewire.student.documents.documents-index', [
            'workspaces' => $workspaces,
        ])->layout('layouts.app', ['title' => 'Documents']);
    }
}