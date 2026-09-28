<?php

namespace App\Livewire\Auth;

use App\Models\Organization;
use App\Models\Program;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Services\GeminiOCRService;
use App\Support\NameMatcher;
use App\Models\StudentVerification;


class Register extends Component
{
    use WithFileUploads;

    public $step = 1;

    // STEP 1
    public $student_number, $first_name, $middle_name, $last_name, $suffix;
    public $sex, $date_of_birth, $email, $contact_number;

    // STEP 2
    public $college, $year_level, $academic_status;
    public ?int $program_id = null;
    public ?int $organization_id = null;

    // STEP 3
    public $profile_picture, $password, $password_confirmation;
    public $e_slip;
    public $e_slip_path;   

    public $isVerified = false;
    public $ocrResult = [];
    public $showOcrModal = false;
    public $matchScore = 0;
    public $matchDetails = [];
    public $fieldStatus = [];

    protected $messages = [
        'email.regex' => 'Only Gmail, Yahoo, or Outlook emails are allowed.',
        'contact_number.regex' => 'Enter a valid PH number (09XXXXXXXXX or +639XXXXXXXXX).',
        'date_of_birth.before_or_equal' => 'You must be at least 15 years old to register.',
    ];

    /**
     * Bachelor's programs, pulled live from the admin-curated list.
     */
    public function getBachelorProgramsProperty()
    {
        return Program::active()->bachelor()->orderBy('name')->get();
    }

    /**
     * Master's programs, pulled live from the admin-curated list.
     */
    public function getMasterProgramsProperty()
    {
        return Program::active()->master()->orderBy('name')->get();
    }

    /**
     * Organizations available to select at registration.
     */
    public function getOrganizationOptionsProperty()
    {
        return Organization::active()->orderBy('name')->get();
    }

    public function nextStep()
    {
        try {

            if ($this->step == 1) {
                $this->validate([
                    'student_number' => 'required|unique:users,student_number',
                    'first_name' => 'required',
                    'last_name' => 'required',
                    'sex' => 'required',
                    'date_of_birth' => $this->dobRules(),
                    'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.com$/','unique:users,email'],
                    'contact_number' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
                    
                ]);
            }

            if ($this->step == 2) {
                $this->validate([
                    'college' => 'required',
                    'program_id' => 'required|exists:programs,id',
                    'organization_id' => 'nullable|exists:organizations,id',
                    'year_level' => 'required',
                    'academic_status' => 'required',
                ]);
            }

            // ✅ clear previous errors before moving step
            $this->resetErrorBag();
            $this->resetValidation();

            $this->step++;

        } catch (\Illuminate\Validation\ValidationException $e) {

            // ✅ only trigger popup when validation fails
            $this->dispatch('validation-error');

            throw $e;
        }
                    
    }   

    public function prevStep()
    {
        $this->step--;
    }

    public function getUserDataProperty()
    {
        return [
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'birth_date' => $this->date_of_birth,
            'college' => $this->college,
            'course' => optional(Program::find($this->program_id))->name,
            'year' => $this->year_level,
            'section' => null,
            'semester' => currentSemester(),
            'academic_year' => currentAcademicYear(),
        ];
    }
    
    public function updatedESlip()
    {
        if (!$this->student_number || !$this->first_name || !$this->last_name) {
            $this->dispatch('validation-error');
            return;
        }

        $this->validate([
            'e_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120'
        ]);

        $path = $this->e_slip->store('e_slips');
        $this->e_slip_path = $path;

        $ocrData = GeminiOCRService::extractText($path);

        $this->ocrResult = $ocrData ?? [];

        if (!$ocrData) {
            $this->isVerified = false;
            $this->showOcrModal = true;
            return;
        }

        // 🔥 NORMALIZATION
        $normalize = function ($value) {
            return strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $value)));
        };
        $normalizeSemester = function ($value) {
            $value = strtolower(trim($value));

            $map = [
                '1' => '1st semester',
                '1st' => '1st semester',
                'first' => '1st semester',

                '2' => '2nd semester',
                '2nd' => '2nd semester',
                'second' => '2nd semester',
            ];

            foreach ($map as $key => $normalized) {
                if (str_contains($value, $key)) {
                    return $normalized;
                }
            }

            return $value;
        };
        $normalizeYear = function ($value) {
            $value = strtolower($value);

            if (str_contains($value, '1')) return '1';
            if (str_contains($value, '2')) return '2';
            if (str_contains($value, '3')) return '3';
            if (str_contains($value, '4')) return '4';

            return preg_replace('/\D/', '', $value);
        };


        // INPUTS
        $inputStudent = preg_replace('/\D/', '', $this->student_number);
        $inputFirst = $normalize($this->first_name);
        $inputLast = $normalize($this->last_name);
        $inputProgramName = optional(Program::find($this->program_id))->name;

        // OCR
        $ocrStudent = preg_replace('/\D/', '', $ocrData['student_number'] ?? '');
        $ocrFirst = $normalize($ocrData['first_name'] ?? '');
        $ocrLast = $normalize($ocrData['last_name'] ?? '');
        $currentSemester = currentSemester();
        $currentYear = currentAcademicYear();

        $ocrSemester = $normalizeSemester($ocrData['semester'] ?? '');
        $systemSemester = $normalizeSemester($currentSemester);

        $ocrYear = trim($ocrData['academic_year'] ?? '');
        $systemYear = $currentYear;
        $ocrYearLevel = $normalizeYear($ocrData['year'] ?? '');
        $inputYearLevel = $normalizeYear($this->year_level);

        $firstMatch = NameMatcher::match($ocrData['first_name'] ?? '', $this->first_name);
        $lastMatch  = NameMatcher::match($ocrData['last_name'] ?? '', $this->last_name);
        $filled = fn($v) => filled(trim((string) $v));

        if (!$firstMatch || !$lastMatch) {
            $fullMatch = NameMatcher::match(
                trim($this->first_name . ' ' . $this->middle_name . ' ' . $this->last_name),
                $ocrData['full_name'] ?? ($ocrData['first_name'] . ' ' . $ocrData['last_name'])
            );
            $firstMatch = $firstMatch || $fullMatch;
            $lastMatch  = $lastMatch || $fullMatch;
        }
        // 🔥 MATCH CHECKS
        $checks = [
            'Student Number' => $ocrStudent === $inputStudent,
            'First Name' => $firstMatch,
            'Last Name' => $lastMatch,

            // NEW
            'Semester' => $ocrSemester === $systemSemester,
            'Academic Year' => $ocrYear === $systemYear,

            'College' => $normalize($ocrData['college'] ?? '') === $normalize($this->college),
            'Course' => $normalize($ocrData['course'] ?? '') === $normalize((string) $inputProgramName),
            'Year Level' => $ocrYearLevel === $inputYearLevel,

            'Enrolled Stamp' => $ocrData['officially_enrolled'] ?? false,
            'Processed By'          => $filled($ocrData['processed_by'] ?? null),
            'Processor Signature'   => (bool) ($ocrData['has_signature'] ?? false),
            'Processed Date & Time' => $filled($ocrData['processed_date'] ?? null)
                                    && $filled($ocrData['processed_time'] ?? null),
        ];
        $this->fieldStatus = [
            'student_number' => $checks['Student Number'] ? 'match' : 'mismatch',
            'first_name' => $checks['First Name'] ? 'match' : 'mismatch',
            'last_name' => $checks['Last Name'] ? 'match' : 'mismatch',
            'semester' => $checks['Semester'] ? 'match' : 'mismatch',
            'academic_year' => $checks['Academic Year'] ? 'match' : 'mismatch',
            'college' => $checks['College'] ? 'match' : 'mismatch',
            'course' => $checks['Course'] ? 'match' : 'mismatch',
            'year' => $checks['Year Level'] ? 'match' : 'mismatch',
            'enrollment_date' => 'neutral',
            'birth_date' => 'neutral',
            'section' => 'neutral',
            'processed_by'   => $checks['Processed By'] ? 'match' : 'mismatch',
            'has_signature'  => $checks['Processor Signature'] ? 'match' : 'mismatch',
            'processed_at'   => $checks['Processed Date & Time'] ? 'match' : 'mismatch',
        ];

        $this->matchDetails = $checks;

        $score = collect($checks)->filter(fn($v) => $v)->count();
        $total = count($checks);

        $this->matchScore = round(($score / $total) * 100);

        $this->isVerified =
            $checks['Student Number'] &&
            $checks['First Name'] &&
            $checks['Last Name'] &&
            $checks['Semester'] &&
            $checks['Academic Year'] &&
            $checks['Enrolled Stamp']&& 
            $checks['Year Level']&&
            $checks['Processed By'] &&
            $checks['Processor Signature'] &&
            $checks['Processed Date & Time'];
        if (!$this->isVerified) {
            $this->dispatch('alert', type: 'error', message: 'Verification failed. Please upload a valid enrollment slip.');
        }
        $this->showOcrModal = true;
    }
    protected function dobRules(): array
    {
        return [
            'required',
            'date',
            'before_or_equal:' . now()->subYears(15)->toDateString(),
        ];
    }
    public function updatedDateOfBirth()
    {
        $this->validateOnly('date_of_birth', ['date_of_birth' => $this->dobRules()]);
    }
    public function verifyESlip()
    {
        $this->validate([
            'e_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120'
        ]);

        $path = $this->e_slip->store('e_slips');

        $ocrData = [
            'student_number' => '2100200',
            'name' => 'Juan Dela Cruz'
        ];

        $this->ocrResult = $ocrData;

        $fullName = strtolower(trim($this->first_name . ' ' . $this->last_name));

        $isMatch =
            $ocrData['student_number'] == $this->student_number &&
            strtolower($ocrData['name']) == $fullName;

        $this->isVerified = $isMatch;
        $this->showOcrModal = true;
    }

    public function register()
    {
        if (!$this->isVerified) {
            $this->dispatch('validation-error');
            return;
        }
        $this->validate([
            'student_number' => 'required|unique:users,student_number',
            'first_name' => 'required',
            'last_name' => 'required',
            'sex' => 'required',
            'date_of_birth' => $this->dobRules(),
            'college' => 'required',
            'program_id' => 'required|exists:programs,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'year_level' => 'required',
            'academic_status' => 'required',
            'password' => ['required', 'confirmed'],
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.com$/'],
            'contact_number' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
        ]);

        $profilePath = null;
        if ($this->profile_picture) {
            $profilePath = $this->profile_picture->store('profiles', 'public');
        }

        $user = User::create([
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'sex' => $this->sex,
            'date_of_birth' => $this->date_of_birth,
            'email' => $this->email,
            'contact_number' => $this->contact_number,
            'college' => $this->college,
            'program_id' => $this->program_id,
            'year_level' => $this->year_level,
            'academic_status' => $this->academic_status,
            'organization_id' => $this->organization_id,
            'password' => Hash::make($this->password),
            'role_id' => \App\Models\Role::where('role_name', 'Student')->value('id'),
            'profile_picture' => $profilePath,
            'account_status' => 'active',
        ]);

        $currentPeriod = \App\Models\Setting::query()->latest('id')->first();
            StudentVerification::create([
            'user_id' => $user->id,
            'setting_id' => $currentPeriod?->id,
            'student_number' => $user->student_number,
            'e_slip_path' => $this->e_slip_path,
            'semester' => currentSemester(),
            'academic_year' => currentAcademicYear(),
            'status' => StudentVerification::STATUS_VERIFIED,
            'ocr_data' => $this->ocrResult,
            'verified_at' => now(),
        ]);
        Auth::login($user);


        return $this->redirect('/student/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.auth-full');
    }
}