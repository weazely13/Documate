<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends Component
{
    public $login = ''; // student_number OR email
    public $password = '';
    public $remember = false; // ✅ ADD THIS

    public function loginUser()
    {
        $this->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        // Detect if email or student number
        $field = filter_var($this->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'student_number';

        // ✅ ADD REMEMBER HERE
        if (!Auth::attempt([
            $field => $this->login,
            'password' => $this->password
        ], $this->remember)) {

            throw ValidationException::withMessages([
                'login' => 'Invalid credentials',
            ]);
        }

        // IMPORTANT (SECURITY BEST PRACTICE)
        request()->session()->regenerate();

        $user = Auth::user();

        // ADMIN BYPASS
        if ($user->role->role_name === 'Admin') {
            return $this->redirect('/admin/dashboard', navigate: true);
        }

        // CHECK ACCOUNT STATUS
        if ($user->account_status !== 'active') {
            return $this->redirect('/verify', navigate: true);
        }

        // NORMAL USER FLOW
        return $this->redirect('/student/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('layouts.auth-full');
    }
}
