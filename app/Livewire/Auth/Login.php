<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Illuminate\Support\Str;

class Login extends Component
{
    public $login = '';
    public $password = '';
    public $remember = false;

    // Forgot password
    public bool $showForgot = false;
    public string $resetEmail = '';

    // Removed-account recovery
    public bool $canRecover = false;
    #[Locked]
    public ?int $recoverUserId = null;

    public function loginUser()
    {
        $this->validate([
            'login' => 'required',
            'password' => 'required',
        ]);

        $this->canRecover = false;
        $this->recoverUserId = null;

        $login = trim($this->login);
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'student_number';
        $label = $isEmail ? 'email' : 'student number';

        $throttleKey = 'login:' . Str::lower($login) . '|' . request()->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'login' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        $query = User::withTrashed()->with('role');
        $user = $isEmail
            ? $query->whereRaw('LOWER(email) = ?', [Str::lower($login)])->first()
            : $query->where('student_number', $login)->first();

        // 1. No account
        if (! $user) {
            RateLimiter::hit($throttleKey, 300);
            throw ValidationException::withMessages([
                'login' => "No account found with that {$label}. Check for typos, or create an account.",
            ]);
        }

        // 2. Removed account: offer recovery by email
        if ($user->trashed()) {
            RateLimiter::hit($throttleKey, 300);
            $this->canRecover = true;
            $this->recoverUserId = $user->id;

            throw ValidationException::withMessages([
                'login' => 'This account was removed. We can email a recovery link to ' . $this->maskEmail($user->email) . '.',
            ]);
        }

        // 3. Wrong password
        if (! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            throw ValidationException::withMessages([
                'password' => 'Incorrect password. Try again, or use "Forgot password?".',
            ]);
        }

        Auth::login($user, $this->remember);
        RateLimiter::clear($throttleKey);
        request()->session()->regenerate();

        if ($user->role->role_name === 'Admin') {
            return $this->redirect('/admin/dashboard', navigate: true);
        }

        if ($user->role->role_name === 'Signatory') {
            if ($user->account_status !== 'active') {
                Auth::logout();
                request()->session()->invalidate();

                throw ValidationException::withMessages([
                    'login' => 'This account is inactive. Contact the VPSD office.',
                ]);
            }

            return $this->redirect(route('signatory.inbox'), navigate: true);
        }

        if ($user->account_status !== 'active') {
            return $this->redirect('/verify', navigate: true);
        }

        return $this->redirect('/student/dashboard', navigate: true);
    }

    protected function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($name, 0, 1) . str_repeat('*', max(2, strlen($name) - 1)) . '@' . $domain;
    }

    // ---------- Forgot password ----------
    public function sendResetLink(): void
    {
        $this->validate(['resetEmail' => ['required', 'email']], [], ['resetEmail' => 'email']);

        $key = 'pw-reset:' . request()->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('resetEmail', 'Too many attempts. Please try again in a few minutes.');
            return;
        }
        RateLimiter::hit($key, 300);

        try {
            // Soft-deleted users are skipped by the broker automatically.
            Password::sendResetLink(['email' => strtolower(trim($this->resetEmail))]);
        } catch (\Throwable $e) {
            report($e);
        }

        // Same message whether or not the email exists.
        session()->flash('status', 'If that email belongs to an account, a password reset link has been sent.');
        $this->reset('resetEmail', 'showForgot');
    }

    // ---------- Recover a removed account ----------
    public function sendRecovery(): void
    {
        if (! $this->recoverUserId) {
            return;
        }

        $key = 'recover:' . $this->recoverUserId;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('login', 'Too many recovery requests. Please try again later.');
            return;
        }
        RateLimiter::hit($key, 3600);

        $user = User::onlyTrashed()->find($this->recoverUserId);

        if ($user) {
            $url = URL::temporarySignedRoute('account.recover', now()->addMinutes(60), ['user' => $user->id]);

            try {
                Mail::raw(
                    "Hi {$user->first_name},\n\nWe received a request to recover your DocuMate account.\n\n"
                    . "Open this link within 60 minutes to restore it:\n{$url}\n\n"
                    . "If this wasn't you, ignore this email and your account stays removed.",
                    fn ($m) => $m->to($user->email)->subject('Recover your DocuMate account')
                );
            } catch (\Throwable $e) {
                report($e);
                $this->addError('login', 'The recovery email could not be sent. Please try again later.');
                return;
            }
        }

        session()->flash('status', 'A recovery link was sent to the email on file. It expires in 60 minutes.');
        $this->reset('canRecover', 'recoverUserId', 'password');
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('layouts.auth-full');
    }
}