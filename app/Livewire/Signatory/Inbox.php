<?php

namespace App\Livewire\Signatory;

use App\Enums\ApprovalStatus;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Workflow\Documents\DocumentRegistry;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Signing inbox: open approvals for the roles the user holds today, plus their own history. */
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
        $oldest = $this->pendingQuery($user)->orderBy('opened_at')->first()?->opened_at;

        $pending = $this->filtered($this->pendingQuery($user))
            ->with(['workspace.user', 'role'])
            ->orderBy('opened_at', $this->sort === 'newest' ? 'desc' : 'asc')
            ->paginate(8, pageName: 'waitingPage');

        $history = $this->filtered(
                TransactionApproval::where('acted_by', $user->id)
                    ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
            )
            ->when($this->result === 'approved', fn ($q) => $q->where('status', ApprovalStatus::Approved->value))
            ->when($this->result === 'rejected', fn ($q) => $q->where('status', ApprovalStatus::Rejected->value))
            ->with(['workspace', 'role'])
            ->orderByDesc('acted_at')
            ->paginate(10, pageName: 'historyPage');

        $mine = fn () => TransactionApproval::where('acted_by', $user->id);

        $avgMinutes = $mine()
            ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
            ->whereNotNull('opened_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, opened_at, acted_at)) AS m')
            ->value('m');

        $stats = [
            'waiting' => $waitingTotal,
            'oldest_waiting' => $oldest,
            'signed' => $mine()->where('status', ApprovalStatus::Approved->value)->count(),
            'rejected' => $mine()->where('status', ApprovalStatus::Rejected->value)->count(),
            'this_month' => $mine()->where('status', ApprovalStatus::Approved->value)
                ->where('acted_at', '>=', now()->startOfMonth())->count(),
            'avg_response' => $avgMinutes !== null
                ? CarbonInterval::minutes((int) $avgMinutes)->cascade()->forHumans(['parts' => 2, 'short' => true])
                : null,
        ];

        $byRole = DB::table('transaction_approvals as a')
            ->join('signatory_roles as r', 'r.id', '=', 'a.signatory_role_id')
            ->where('a.acted_by', $user->id)
            ->where('a.status', ApprovalStatus::Approved->value)
            ->selectRaw('r.label, COUNT(*) AS signed')
            ->groupBy('r.label')
            ->orderByDesc('signed')
            ->get();

        $docTypes = collect(DocumentRegistry::all())->map(fn ($d) => $d->name())->sort()->all();
        // Roles this user holds today (same "current" rule the inbox uses), with open work per role.
        $myRoles = $user->signatoryAssignments()->current()->with('role')->get()
            ->sortBy(fn ($a) => $a->role->label ?? '')->values();

        $waitingByRole = $this->pendingQuery($user)->toBase()
            ->selectRaw('signatory_role_id, COUNT(*) AS c')
            ->groupBy('signatory_role_id')
            ->pluck('c', 'signatory_role_id');

        return view('livewire.signatory.inbox', compact('pending', 'history', 'stats', 'byRole', 'tab', 'docTypes', 'waitingTotal', 'myRoles', 'waitingByRole'))
            ->layout('layouts.app', ['title' => 'Signing Inbox']);
    }
}