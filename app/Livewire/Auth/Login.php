<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Support\Audit;
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

    // Forgot password: 'ask' -> ('mismatch' -> hint) -> 'sent'
    public bool $showForgot = false;
    public string $forgotStep = 'ask';
    public string $resetEmail = '';
    public string $resetStudentNumber = '';
    public string $resetLastName = '';
    public string $hintStudentNumber = '';
    public ?string $hintEmail = null;    // masked email of the account found by student number
    public ?string $sentTo = null;       // masked email the link went to
    #[Locked]
    public ?int $hintUserId = null;

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
            Audit::record('login_failed', 'Authentication', "Sign-in blocked for \"{$login}\": too many attempts", actor: null);
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
            Audit::record('login_failed', 'Authentication', "Failed sign-in for \"{$login}\": no such account", actor: null);
            throw ValidationException::withMessages([
                'login' => "No account found with that {$label}. Check for typos, or create an account.",
            ]);
        }

        // 2. Removed account: offer recovery by email
        if ($user->trashed()) {
            RateLimiter::hit($throttleKey, 300);
            Audit::record('login_failed', 'Authentication', "Failed sign-in for \"{$login}\": account was removed", $user, actor: null);
            $this->canRecover = true;
            $this->recoverUserId = $user->id;

            throw ValidationException::withMessages([
                'login' => 'This account was removed. We can email a recovery link to ' . $this->maskEmail($user->email) . '.',
            ]);
        }

        // 3. Wrong password
        if (! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            Audit::record('login_failed', 'Authentication', "Failed sign-in for \"{$login}\": wrong password", $user, actor: null);
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

            return $this->redirect(route('signatory.home'), navigate: true);
        }

        if ($user->account_status !== 'active') {
            return $this->redirect('/verify', navigate: true);
        }

        return $this->redirect('/student/home', navigate: true);
    }

    /** "kh*****a@gmail.com": enough to recognise your own address, not enough to read someone else's. */
    protected function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $len = mb_strlen($name);
        $show = $len <= 3 ? 1 : 2;

        return mb_substr($name, 0, $show) . str_repeat('*', max(3, $len - $show - 1)) . ($len > 3 ? mb_substr($name, -1) : '') . '@' . $domain;
    }

    // ---------- Forgot password ----------
    // The link only ever goes to the email saved on the account. Typing an email that isn't on any account
    // opens a pop-up instead; a student number then shows a masked hint of the right address.

    public function openForgot(): void
    {
        $this->resetErrorBag();
        $this->reset('resetEmail', 'resetStudentNumber', 'resetLastName', 'hintStudentNumber', 'hintEmail', 'hintUserId', 'sentTo');
        $this->forgotStep = 'ask';
        $entry = trim((string) $this->login);
        if (filter_var($entry, FILTER_VALIDATE_EMAIL)) {
            $this->resetEmail = $entry;
        } elseif (preg_match('/^\d{5,}$/', $entry)) {
            $this->resetStudentNumber = $entry;
        }
        $this->showForgot = true;
    }

    public function closeForgot(): void
    {
        $this->showForgot = false;
        $this->forgotStep = 'ask';
        $this->resetErrorBag();
    }

    /**
     * The email plus ONE of: the last name, or the student / ID number (signatories may have no number).
     * Whatever is given must belong to the same account, and the link only ever goes to that account's email.
     */
    public function sendResetLink(): void
    {
        $this->validate([
            'resetEmail' => ['required', 'email', 'max:255'],
            'resetLastName' => ['nullable', 'string', 'max:120', 'required_without:resetStudentNumber'],
            'resetStudentNumber' => ['nullable', 'string', 'max:40'],
        ], [
            'resetLastName.required_without' => 'Enter your last name or your student / ID number.',
        ], ['resetLastName' => 'last name', 'resetEmail' => 'email', 'resetStudentNumber' => 'student or ID number']);

        if (! $this->throttle('pw-reset:' . request()->ip(), 6, 'resetEmail')) {
            return;
        }

        $email = Str::lower(trim($this->resetEmail));
        $number = trim($this->resetStudentNumber);
        $name = trim($this->resetLastName);

        // With an ID number: that account, checked against the name too if one was typed
        if ($number !== '') {
            $user = User::where('student_number', $number)->first();
            if (! $user || ($name !== '' && ! $this->nameMatches($user, $name))) {
                Audit::record('password_reset_mismatch', 'Authentication', "Password reset refused for ID \"{$number}\": no matching account", actor: null);
                $this->addError('resetStudentNumber', $name !== ''
                    ? 'That name and ID number don\'t belong to the same account. Check both.'
                    : 'No account uses that student / ID number.');

                return;
            }

            if (Str::lower((string) $user->email) === $email) {
                $this->deliverReset($user);

                return;
            }

            // Right account, wrong email: show the account's email, masked
            Audit::record('password_reset_mismatch', 'Authentication', 'Password reset asked with an email that is not on the account', $user, actor: null);
            $this->hintEmail = $this->maskEmail($user->email);
            $this->hintUserId = $user->id;
            $this->forgotStep = 'mismatch';

            return;
        }

        // With a name only: the account with this email must carry that name
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();
        if ($user && $this->nameMatches($user, $name)) {
            $this->deliverReset($user);

            return;
        }

        Audit::record('password_reset_mismatch', 'Authentication', "Password reset refused for \"{$email}\": no account with that email and name", actor: null);
        $this->hintEmail = null;
        $this->hintUserId = null;
        $this->forgotStep = 'mismatch';
    }

    /** From the pop-up: send to the account's own email, found by the matching student number and name. */
    public function sendToHint(): void
    {
        $user = $this->hintUserId ? User::find($this->hintUserId) : null;
        if (! $user) {
            $this->forgotStep = 'ask';

            return;
        }

        if (! $this->throttle('pw-reset:' . request()->ip(), 6, 'resetEmail')) {
            return;
        }

        $this->deliverReset($user);
    }

    /** "Dela Cruz", "dela  cruz", "DELA-CRUZ" and "Juan Dela Cruz" all match the last name Dela Cruz. */
    private function nameMatches(User $user, string $typed): bool
    {
        $norm = fn ($v) => preg_replace('/[^a-z]/', '', Str::lower(Str::ascii((string) $v)));
        $typed = $norm($typed);
        $last = $norm($user->last_name);

        return $typed !== '' && $last !== '' && in_array($typed, [
            $last,
            $norm($user->first_name . $user->last_name),
            $norm($user->first_name . $user->middle_name . $user->last_name),
        ], true);
    }

    private function deliverReset(User $user): void
    {
        try {
            $status = Password::sendResetLink(['email' => $user->email]);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('resetEmail', 'The reset email could not be sent right now. Please try again in a few minutes.');

            return;
        }

        if ($status === Password::RESET_THROTTLED) {
            $this->addError('resetEmail', 'A reset link was sent to this account a moment ago. Check your inbox (and spam), or try again in a minute.');

            return;
        }

        Audit::record('password_reset_sent', 'Authentication', 'Password reset link sent to the account email', $user, actor: null);
        $this->sentTo = $this->maskEmail($user->email);
        $this->forgotStep = 'sent';
        $this->reset('hintStudentNumber', 'hintUserId', 'hintEmail');
    }

    private function throttle(string $key, int $max, string $field): bool
    {
        if (RateLimiter::tooManyAttempts($key, $max)) {
            $this->addError($field, 'Too many attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.');

            return false;
        }
        RateLimiter::hit($key, 600);

        return true;
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