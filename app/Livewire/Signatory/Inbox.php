<?php

namespace App\Livewire\Signatory;

use App\Enums\ApprovalStatus;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Signing inbox: open approvals for the roles the user holds today, plus their own decisions. Figures live on the Activity summary. */
class Inbox extends Component
{
    use WithPagination;

    #[Url(as: 'tab', except: 'waiting')]
    public string $tab = 'waiting';      // waiting | history

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $docType = '';

    #[Url(except: 'oldest')]
    public string $sort = 'oldest';      // waiting tab: oldest | newest

    #[Url(except: 'all')]
    public string $result = 'all';       // history tab: all | approved | rejected
    public int $lastWaiting = 0;

    public function mount(): void
    {
        abort_unless(Auth::user()->isSignatory(), 403);
        $this->lastWaiting = $this->pendingQuery(Auth::user())->count();
    }

    public function checkNew(): void
    {
        $n = $this->pendingQuery(Auth::user())->count();
        if ($n > $this->lastWaiting) {
            $this->dispatch('toast', type: 'success', title: 'Ready for your signature',
                message: $n === 1 ? 'A document is now waiting for your signature.' : "{$n} documents are now waiting for your signature.");
        }
        $this->lastWaiting = $n;
    }

    public function updated($name): void
    {
        if (in_array($name, ['search', 'docType', 'sort', 'result', 'tab'], true)) {
            $this->resetPage('waitingPage');
            $this->resetPage('historyPage');
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'docType', 'sort', 'result']);
        $this->resetPage('waitingPage');
        $this->resetPage('historyPage');
    }

    private function pendingQuery(User $user): Builder
    {
        return TransactionApproval::assignableTo($user)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', '!=', $user->id)); // never your own document
    }

    /** Document type and search (student name, student number, transaction code). */
    private function filtered(Builder $query): Builder
    {
        $term = trim($this->search);

        return $query
            ->when($this->docType !== '', fn ($q) => $q->whereHas('workspace', fn ($w) => $w->where('document_type', $this->docType)))
            ->when($term !== '', fn ($q) => $q->whereHas('workspace', fn ($w) => $w->where(fn ($s) => $s
                ->where('transaction_code', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->whereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$term}%"])
                    ->orWhere('student_number', 'like', "%{$term}%")))));
    }

    public function render()
    {
        $user = Auth::user();
        $tab = $this->tab === 'history' ? 'history' : 'waiting';

        $waitingTotal = $this->pendingQuery($user)->count();

        $pending = $this->filtered($this->pendingQuery($user))
            ->with(['workspace.user', 'role'])
            ->orderBy('opened_at', $this->sort === 'newest' ? 'desc' : 'asc')
            ->paginate(50, pageName: 'waitingPage');

        $history = $this->filtered(
                TransactionApproval::where('acted_by', $user->id)
                    ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
            )
            ->when($this->result === 'approved', fn ($q) => $q->where('status', ApprovalStatus::Approved->value))
            ->when($this->result === 'rejected', fn ($q) => $q->where('status', ApprovalStatus::Rejected->value))
            ->with(['workspace.user', 'role'])
            ->orderByDesc('acted_at')
            ->paginate(50, pageName: 'historyPage');

        // Only the document types actually in this tab of the inbox, so the filter never offers an empty list.
        $typesHere = ($tab === 'history'
                ? TransactionApproval::where('acted_by', $user->id)->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
                : $this->pendingQuery($user))
            ->select('workspace_id');
        $typesHere = \App\Models\StudentDocumentWorkspace::whereIn('workspace_id', $typesHere)
            ->distinct()->pluck('document_type')->filter()->values()->all();
        if ($this->docType !== '' && ! in_array($this->docType, $typesHere, true)) {
            $typesHere[] = $this->docType; // keep a chosen filter visible so it can be cleared
        }
        $docTypes = collect($typesHere)->mapWithKeys(fn ($k) => [$k => DocumentRegistry::find($k)?->name() ?? $k])->sort()->all();
        return view('livewire.signatory.inbox', compact('pending', 'history', 'tab', 'docTypes', 'waitingTotal'))
            ->layout('layouts.app', ['title' => 'Signing Inbox']);
    }
}