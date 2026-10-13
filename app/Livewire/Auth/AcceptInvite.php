<?php

namespace App\Livewire\Auth;

use App\Models\UserInvitation;
use App\Services\Workflow\SignatoryInvitations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/** PUBLIC page: a newly invited signatory chooses a password. The emailed token is the only way in. */
class AcceptInvite extends Component
{
    #[Locked]
    public int $invitationId = 0;

    public string $password = '';
    public string $password_confirmation = '';
    public bool $agree = false;

    public function mount(string $token): void
    {
        $this->invitationId = SignatoryInvitations::findValid($token)?->id ?? 0;
    }

    #[Computed]
    public function invitation(): ?UserInvitation
    {
        $invitation = $this->invitationId ? UserInvitation::with('user')->find($this->invitationId) : null;

        return $invitation?->isUsable() ? $invitation : null;
    }

    public function accept()
    {
        $this->resetErrorBag();

        if (! $this->invitation) {
            $this->addError('password', 'This link is no longer valid. Ask the VPSD office for a new one.');

            return;
        }

        $this->validate([
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'agree' => ['accepted'],
        ], [
            'agree.accepted' => 'Please accept the Terms and Privacy Policy to continue.',
        ]);

        $user = null;

        try {
            DB::transaction(function () use (&$user) {
                $invitation = UserInvitation::whereKey($this->invitationId)->lockForUpdate()->firstOrFail();

                if (! $invitation->isUsable()) {
                    throw new \RuntimeException('used');
                }

                $user = $invitation->user;
                $user->forceFill([
                    'password' => $this->password, // hashed by the User model cast
                    'email_verified_at' => now(),
                    'account_status' => 'active',
                ])->save();

                $invitation->forceFill(['used_at' => now()])->save();
            });
        } catch (\RuntimeException $e) {
            $this->addError('password', 'This link was already used. Ask the VPSD office for a new one.');

            return;
        }

        Auth::login($user);
        session()->regenerate();

        return $this->redirect(route('signatory.inbox'), navigate: true);
    }

    public function render()
    {
        $user = $this->invitation?->user;

        return view('livewire.auth.accept-invite', [
            'user' => $user,
            'roles' => $user
                ? $user->signatoryAssignments()->where('is_active', true)->with('role')->get()->pluck('role.label')->filter()->unique()->values()
                : collect(),
        ])->layout('layouts.external', ['title' => 'Set your password']);
    }
}