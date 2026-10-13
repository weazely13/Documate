<?php

namespace App\Livewire\Signatory;

use App\Enums\ApprovalStatus;
use App\Models\TransactionApproval;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;

/** Signing inbox: open approvals for the roles the user holds today, plus their own history. */
class Inbox extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::user()->isSignatory(), 403);
    }

    public function render()
    {
        $user = Auth::user();

        $pending = TransactionApproval::assignableTo($user)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', '!=', $user->id)) // never your own document
            ->with(['workspace.user', 'role'])
            ->orderBy('opened_at')
            ->get();

        $history = TransactionApproval::where('acted_by', $user->id)
            ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
            ->with(['workspace', 'role'])
            ->latest('acted_at')
            ->limit(25)
            ->get();

        $mine = fn () => TransactionApproval::where('acted_by', $user->id);

        $avgMinutes = $mine()
            ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
            ->whereNotNull('opened_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, opened_at, acted_at)) AS m')
            ->value('m');

        $stats = [
            'waiting' => $pending->count(),
            'oldest_waiting' => $pending->first()?->opened_at,   // already ordered by opened_at
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

        return view('livewire.signatory.inbox', compact('pending', 'history', 'stats', 'byRole'))
            ->layout('layouts.app', ['title' => 'Signing Inbox']);

    }
}
