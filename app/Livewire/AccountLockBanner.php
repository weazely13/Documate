<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;

class AccountLockBanner extends Component
{
    #[On('account-status-updated')]
    public function refresh()
    {
        //
    }

    public function render()
    {
        return view('livewire.account-lock-banner', [
            'inactive' => auth()->user()->account_status !== 'active',
        ]);
    }
}