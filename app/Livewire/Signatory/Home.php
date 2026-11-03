<?php

namespace App\Livewire\Signatory;

use App\Models\TransactionApproval;
use App\Models\User;
use App\Workflow\Documents\DocumentRegistry;
use App\Workflow\Documents\WorkflowDocument;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Home for dedicated signing accounts (role "Signatory"), the counterpart of the student's Home:
 * the greeting in the sky, a search over the reports they can issue, and their signing roles.
 * Students and admins who also hold a signing role keep their own Home, so they are sent there.
 */
class Home extends Component
{
    public function mount()
    {
        $user = Auth::user();
        if (! $user->isSignatoryAccount()) {
            return $this->redirectRoute($user->isAdmin() ? 'admin.dashboard' : 'student.home', navigate: true);
        }
    }

    /** @return array<int, array<string, mixed>> reports this person may issue, for the search and the Explore cards */
    private function reports(User $user): array
    {
        return collect($user->issuableDocuments())
            ->map(fn (WorkflowDocument $d) => [
                'key' => $d->key(),
                'name' => $d->name(),
                'description' => $d->description(),
                'category' => $d->category()->value,
                'categoryLabel' => $d->category()->label(),
                'code' => $d->formCode(),
                'paper' => false,
                'url' => route('signatory.issue', ['doc' => $d->key()]),
                'preview' => route('signatory.forms.preview', $d->key()),
            ])
            ->sortBy(fn ($d) => $d['categoryLabel'] . ' ' . $d['name'])
            ->values()->all();
    }

    public function render()
    {
        $user = Auth::user();
        $assignments = $user->signatoryAssignments()->current()->with('role')->get()
            ->sortBy(fn ($a) => $a->role->label ?? '')->values();

        $pending = TransactionApproval::assignableTo($user)
            ->whereHas('workspace', fn ($q) => $q->where('user_id', '!=', $user->id));
        $waitingByRole = (clone $pending)->toBase()
            ->selectRaw('signatory_role_id, COUNT(*) AS c')->groupBy('signatory_role_id')
            ->pluck('c', 'signatory_role_id');

        $reports = $this->reports($user);

        return view('livewire.signatory.home', [
            'firstName' => $user->first_name ?: 'there',
            'facts' => $assignments->map(fn ($a) => $a->role->label ?? null)->filter()->unique()->take(2)->values()->all(),
            'reports' => $reports,
            'categories' => collect($reports)->unique('category')->map(fn ($r) => ['key' => $r['category'], 'label' => $r['categoryLabel']])->values()->all(),
            'docsByRole' => \App\Support\SigningRoles::documentsByRole($assignments->map(fn ($a) => $a->role->key ?? null)->filter()->unique()->values()->all()),
            'assignments' => $assignments,
            'waitingByRole' => $waitingByRole,
            'waiting' => (clone $pending)->count(),
            'signing' => (clone $pending)->with(['workspace.user', 'role'])->orderBy('opened_at')->limit(3)->get(),
            'drafts' => \App\Models\IssueDraft::where('issuer_id', $user->id)->with('student')->latest('updated_at')->limit(4)->get(),
            'oldest' => (clone $pending)->orderBy('opened_at')->first()?->opened_at,
            'hasSignature' => (bool) $user->signature_path,
        ])->layout('layouts.app', ['title' => 'Home']);
    }
}
