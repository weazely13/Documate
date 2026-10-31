<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $login = '';
    public string $password = '';

    public function login(): void
    {
        $this->validate([
            'login' => ['required'],
            'password' => ['required'],
        ]);

        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'student_number';

        if (!Auth::attempt([$field => $this->login, 'password' => $this->password])) {
            throw ValidationException::withMessages([
                'login' => 'Invalid credentials',
            ]);
        }

        $user = Auth::user();

        // ✅ FIXED: match your seeded values
        if ($user->role->role_name === 'Admin') {
            $this->redirect('/admin/dashboard', navigate: true);
            return;
        }

        if ($user->role->role_name === 'Officer') {
            $this->redirect('/officer/dashboard', navigate: true);
            return;
        }

        $this->redirect('/student/home', navigate: true);
    }
};
?>

<div>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">

        <!-- Login (Student Number OR Email) -->
        <div>
            <x-input-label for="login" value="Student Number or Email" />
            <x-text-input 
                wire:model="login"
                id="login"
                class="block mt-1 w-full"
                type="text"
                required
                autofocus
            />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Password" />

            <x-text-input 
                wire:model="password"
                id="password"
                class="block mt-1 w-full"
                type="password"
                required
            />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Log in
            </x-primary-button>
        </div>
    </form>
</div>