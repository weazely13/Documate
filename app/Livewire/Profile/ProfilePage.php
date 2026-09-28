<?php

namespace App\Livewire\Profile;

use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfilePage extends Component
{
    use WithFileUploads;

    // Mode
    public bool $editing = false;

    // Identity (never editable by the user)
    public string $student_number = '';

    // Personal Information
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $suffix = '';
    public string $sex = '';
    public string $date_of_birth = '';
    public string $contact_number = '';

    // Academic Profile
    public string $college = '';
    public ?int $program_id = null;
    public ?int $organization_id = null;
    public string $year_level = '';
    public string $section = '';
    public string $academic_status = '';

    // Account Details
    public string $email = '';
    public string $account_status = '';
    public string $role_name = '';
    public ?string $profile_picture = null;

    // Whether this user currently holds an officer post (locks organization)
    public bool $isOfficerLocked = false;

    // Photo upload (temporary Livewire file)
    public $photo = null;

    // Inline password change
    public bool $showPasswordForm = false;
    public string $current_password = '';
    public string $new_password = '';
    public string $new_password_confirmation = '';

    // Select options
    public array $sexOptions = ['Male', 'Female'];

    public array $yearLevelOptions = [
        '1' => '1st Year',
        '2' => '2nd Year',
        '3' => '3rd Year',
        '4' => '4th Year',
        '5' => '5th Year',
    ];

    public array $academicStatusOptions = [
        'Regular', 'Irregular', 'Transferee', 'Returning Student', 'On Leave', 'Graduated',
    ];

    protected array $messages = [
        'email.regex' => 'Only Gmail, Yahoo, or Outlook emails are allowed.',
        'contact_number.regex' => 'Enter a valid PH number (09XXXXXXXXX or +639XXXXXXXXX).',
        'photo.image' => 'The profile photo must be an image.',
        'photo.max' => 'The profile photo may not be larger than 2MB.',
        'new_password.confirmed' => 'The new password confirmation does not match.',
    ];

    public function mount(): void
    {
        $this->fillFromUser(Auth::user()->loadMissing('role', 'organization', 'program', 'officerRecord'));
    }

    public function toggleEdit(): void
    {
        if ($this->editing) {
            $this->fillFromUser(Auth::user()->fresh(['role', 'organization', 'program', 'officerRecord']));
            $this->reset('photo');
            $this->resetErrorBag();
        }

        $this->editing = ! $this->editing;
    }

    public function save(): void
    {
        /** @var User $user */
        $user = Auth::user();

        $rules = [
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:50'],
            'sex' => ['required', Rule::in($this->sexOptions)],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'email' => [
                'required',
                'email',
                'max:255',
                'regex:/^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.com$/',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'contact_number' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];

        // Only enforce Academic field validation if the user is NOT an Admin
        if (! $this->isAdmin) {
            $rules['college'] = ['required', 'string', 'max:255'];
            $rules['program_id'] = ['required', Rule::exists('programs', 'id')->where('is_active', true)];
            $rules['organization_id'] = ['nullable', Rule::exists('organizations', 'id')->where('is_active', true)];
            $rules['year_level'] = ['required', Rule::in(array_keys($this->yearLevelOptions))];
            $rules['section'] = ['nullable', 'string', 'max:50'];
            $rules['academic_status'] = ['required', Rule::in($this->academicStatusOptions)];
        }

        $validated = $this->validate($rules);
        $validated['email'] = strtolower($validated['email']);

        // Officers cannot change their organization — keep it pinned to whatever
        // is currently on the record, regardless of what was submitted.
        if (! $this->isAdmin) {
            if ($this->isOfficerLocked) {
                $validated['organization_id'] = $user->organization_id;
            }
        }

        if ($this->photo) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $validated['profile_picture'] = $this->photo->store('profile-photos', 'public');
        }

        unset($validated['photo']);

        $user->fill($validated);
        $user->save();

        $freshUser = $user->fresh(['role', 'organization', 'program', 'officerRecord']);
        Auth::setUser($freshUser);
        $this->fillFromUser($freshUser);
        $this->reset('photo');
        $this->editing = false;

        session()->flash('profile_saved', 'Profile updated successfully.');
    }

    public function togglePasswordForm(): void
    {
        $this->showPasswordForm = ! $this->showPasswordForm;
        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        $this->resetErrorBag(['current_password', 'new_password', 'new_password_confirmation']);
    }

    public function updatePassword(): void
    {
        $validated = $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'new_password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        $this->reset('current_password', 'new_password', 'new_password_confirmation');
        $this->showPasswordForm = false;

        session()->flash('password_saved', 'Password updated successfully.');
    }

    public function getIsAdminProperty(): bool
    {
        return strtolower($this->role_name) === 'admin';
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
        if ($this->isAdmin) {
            return 'System Administrator';
        }

        $programName = optional(Program::find($this->program_id))->name;
        $program = trim((string) preg_replace('/^Bachelor of (Science|Arts) in /i', 'B$1 ', (string) $programName));
        $program = str_replace(['BScience ', 'BArts '], ['BS ', 'BA '], $program);

        $yearLabel = $this->yearLevelOptions[$this->year_level] ?? ($this->year_level ? $this->year_level . ' Year' : null);

        return collect([$program ?: $this->role_name, $yearLabel, $this->section])
            ->filter()
            ->implode(' • ');
    }

    public function getPhotoUrlProperty(): ?string
    {
        return $this->profile_picture ? Storage::url($this->profile_picture) : null;
    }

    public function getStatusLabelProperty(): string
    {
        return match ($this->account_status) {
            'pending_verification' => 'Pending Verification',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            default => ucfirst(str_replace('_', ' ', $this->account_status)),
        };
    }

    public function getStatusBadgeClassProperty(): string
    {
        return match ($this->account_status) {
            'active' => 'bg-emerald-400/20 text-emerald-50 ring-1 ring-inset ring-emerald-300/40',
            'pending_verification' => 'bg-amber-400/20 text-amber-50 ring-1 ring-inset ring-amber-300/40',
            'inactive' => 'bg-gray-400/20 text-gray-50 ring-1 ring-inset ring-gray-300/40',
            'suspended' => 'bg-red-400/20 text-red-50 ring-1 ring-inset ring-red-300/40',
            default => 'bg-white/15 text-white ring-1 ring-inset ring-white/20',
        };
    }

    /**
     * Available programs for the dropdown, grouped by level.
     */
    public function getBachelorProgramsProperty()
    {
        return Program::active()->bachelor()->orderBy('name')->get();
    }

    public function getMasterProgramsProperty()
    {
        return Program::active()->master()->orderBy('name')->get();
    }

    public function getOrganizationOptionsProperty()
    {
        return Organization::active()->orderBy('name')->get();
    }

    public function getSelectedProgramNameProperty(): ?string
    {
        return $this->program_id ? optional(Program::find($this->program_id))->name : null;
    }

    public function getSelectedOrganizationNameProperty(): ?string
    {
        return $this->organization_id ? optional(Organization::find($this->organization_id))->name : null;
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
        $this->contact_number = (string) ($user->contact_number ?? '');
        $this->college = (string) ($user->college ?? '');
        $this->program_id = $user->program_id;
        $this->organization_id = $user->organization_id;
        $this->year_level = (string) ($user->year_level ?? '');
        $this->section = (string) ($user->section ?? '');
        $this->academic_status = (string) ($user->academic_status ?? '');
        $this->email = (string) ($user->email ?? '');
        $this->account_status = (string) ($user->account_status ?? '');
        $this->role_name = (string) ($user->role->role_name ?? 'User');
        $this->profile_picture = $user->profile_picture;
        $this->isOfficerLocked = method_exists($user, 'isOfficer') ? $user->isOfficer() : false;
    }

    public function render()
    {
        return view('livewire.profile.profile-page')
            ->layout('layouts.app', ['title' => 'Profile']);
    }
}