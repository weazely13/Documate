<?php

namespace App\Livewire\Profile;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProfilePage extends Component
{
    public string $student_number = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $suffix = '';
    public string $sex = '';
    public string $date_of_birth = '';
    public string $email = '';
    public string $contact_number = '';
    public string $program = '';
    public string $year_level = '';
    public string $academic_status = '';
    public string $account_status = '';
    public string $role_name = '';
    public ?string $profile_picture = null;

    protected array $messages = [
        'email.regex' => 'Only Gmail, Yahoo, or Outlook emails are allowed.',
        'contact_number.regex' => 'Enter a valid PH number (09XXXXXXXXX or +639XXXXXXXXX).',
    ];

    public function mount(): void
    {
        $this->fillFromUser(Auth::user()->loadMissing('role'));
    }

    public function save(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $this->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', Rule::in(['Male', 'Female'])],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.com$/',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'contact_number' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
        ]);

        $validated['email'] = strtolower($validated['email']);

        $user->fill($validated);
        $user->save();

        $freshUser = $user->fresh(['role']);
        Auth::setUser($freshUser);
        $this->fillFromUser($freshUser);

        session()->flash('profile_saved', 'Profile updated successfully.');
    }

    public function getFullNameProperty(): string
    {
        $parts = [
            $this->first_name,
            $this->middle_name,
            $this->last_name,
        ];

        $fullName = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter($parts))));

        return trim($fullName . ($this->suffix ? ' ' . $this->suffix : '')) ?: 'DocuMate User';
    }

    public function getInitialsProperty(): string
    {
        $first = strtoupper(substr(trim($this->first_name), 0, 1));
        $last = strtoupper(substr(trim($this->last_name), 0, 1));

        return trim($first . $last) ?: 'DM';
    }

    public function getAcademicLineProperty(): string
    {
        $program = trim((string) preg_replace('/^Bachelor of (Science|Arts) in /i', 'B$1 ', $this->program));
        $program = str_replace(['BScience ', 'BArts '], ['BS ', 'BA '], $program);

        $yearLabel = match ($this->year_level) {
            '1' => '1st Year',
            '2' => '2nd Year',
            '3' => '3rd Year',
            '4' => '4th Year',
            default => $this->year_level ? $this->year_level . ' Year' : null,
        };

        return collect([$program ?: $this->role_name, $yearLabel])
            ->filter()
            ->implode(' • ');
    }

    protected function fillFromUser(User $user): void
    {
        $this->student_number = (string) ($user->student_number ?? '');
        $this->first_name = (string) ($user->first_name ?? '');
        $this->middle_name = (string) ($user->middle_name ?? '');
        $this->last_name = (string) ($user->last_name ?? '');
        $this->suffix = (string) ($user->suffix ?? '');
        $this->sex = (string) ($user->sex ?? '');
        $this->date_of_birth = $user->date_of_birth ? (string) $user->date_of_birth : '';
        $this->email = (string) ($user->email ?? '');
        $this->contact_number = (string) ($user->contact_number ?? '');
        $this->program = (string) ($user->program ?? '');
        $this->year_level = (string) ($user->year_level ?? '');
        $this->academic_status = (string) ($user->academic_status ?? '');
        $this->account_status = (string) ($user->account_status ?? '');
        $this->role_name = (string) ($user->role->role_name ?? 'User');
        $this->profile_picture = $user->profile_picture;
    }

    public function render()
    {
        return view('livewire.profile.profile-page')
            ->layout('layouts.app', ['title' => 'Profile']);
    }
}
