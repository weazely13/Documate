<?php
namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class AccountVerifyBadge extends Component
{
    #[On('account-status-updated')]
    public function refresh()
    {
        // Listener alone is enough — Livewire re-renders this component
        // when the event fires, and render() below reads fresh data.
    }

    public function render()
    {
        $user = auth()->user();
        $periodVerified = $user->isVerifiedForCurrentPeriod();

        return view('livewire.account-verify-badge', [
            'periodVerified' => $periodVerified,
            'isVerified' => $periodVerified && $user->account_status === 'active',
        ]);
    }
}