<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use App\Services\GeminiOCRService;


class Register extends Component
{
    use WithFileUploads;

    public $step = 1;

    // STEP 1
    public $student_number, $first_name, $middle_name, $last_name, $suffix;
    public $sex, $date_of_birth, $email, $contact_number;

    // STEP 2
    public $college, $program, $year_level, $academic_status, $organization;

    // STEP 3
    public $profile_picture, $password, $password_confirmation;
    public $orgSuggestions = [];
    public $e_slip;

    public $isVerified = false;
    public $ocrResult = [];
    public $showOcrModal = false;
    public $matchScore = 0;
    public $matchDetails = [];
    public $fieldStatus = [];

    public $allPrograms = [
        'Bachelor of Science in Information Technology',
        'Bachelor of Science in Computer Science',
        'Bachelor of Science in Information Systems',
        'Bachelor of Science in Accountancy',
        'Bachelor of Science in Business Administration'
    ];

    public $allOrganizations = [
        'DIGITS Organization',
        'Mathindig Quadrilateral (Math Students Society)',
        'Association of Political Science Students (APSS)',
        'Tourism Circle',
        'English',
        'Science Questers Unlimited (SQU)',
    ];
    

    protected $messages = [
        'email.regex' => 'Only Gmail, Yahoo, or Outlook emails are allowed.',
        'contact_number.regex' => 'Enter a valid PH number (09XXXXXXXXX or +639XXXXXXXXX).',
    ];

    public function updatedOrganization()
    {
        if (!$this->organization) {
            $this->orgSuggestions = [];
            return;
        }

        $this->orgSuggestions = collect($this->allOrganizations)
            ->filter(fn($o) => stripos($o, $this->organization) !== false)
            ->take(5)
            ->values()
            ->toArray();
    }

    public function selectOrg($value)
    {
        $this->organization = $value;
        $this->orgSuggestions = [];
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
                    'date_of_birth' => 'required|date',
                    'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.com$/','unique:users,email'],
                    'contact_number' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
                ]);
            }

            if ($this->step == 2) {
                $this->validate([
                    'college' => 'required',
                    'program' => 'required',
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
            'course' => $this->program,
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
        // 🔥 MATCH CHECKS
        $checks = [
            'Student Number' => $ocrStudent === $inputStudent,
            'First Name' => $ocrFirst === $inputFirst,
            'Last Name' => $ocrLast === $inputLast,

            // 🔥 NEW
            'Semester' => $ocrSemester === $systemSemester,
            'Academic Year' => $ocrYear === $systemYear,

            'College' => $normalize($ocrData['college'] ?? '') === $normalize($this->college),
            'Course' => $normalize($ocrData['course'] ?? '') === $normalize($this->program),
            'Year Level' => $ocrYearLevel === $inputYearLevel,

            'Enrolled Stamp' => $ocrData['officially_enrolled'] ?? false,
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

            // optional fields
            'enrollment_date' => 'neutral',
            'birth_date' => 'neutral',
            'section' => 'neutral',
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
            $checks['Year Level'];
        $this->activateAccountIfVerified();
        if (!$this->isVerified) {
            $this->dispatch('alert', type: 'error', message: 'Verification failed. Please upload a valid enrollment slip.');
        }
        $this->showOcrModal = true;
    }
    public function verifyESlip()
    {
        $this->validate([
            'e_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120'
        ]);

        $path = $this->e_slip->store('e_slips');

        // 🔥 TEMP OCR
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

    public function activateAccountIfVerified()
    {
        if ($this->isVerified && auth()->check()) {
            auth()->user()->update([
                'account_status' => 'active'
            ]);
        }
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
            'date_of_birth' => 'required|date',
            'college' => 'required',
            'program' => 'required',
            'year_level' => 'required',
            'academic_status' => 'required',
            'password' => ['required', 'confirmed'],
            'email' => ['required', 'email', 'regex:/^[a-zA-Z0-9._%+-]+@(gmail|yahoo|outlook)\.com$/'],
            'contact_number' => ['required', 'regex:/^(09|\+639)\d{9}$/'],
        ]);

        // ✅ Handle profile upload
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
            'program' => $this->program,
            'year_level' => $this->year_level,
            'academic_status' => $this->academic_status,
            'organization' => $this->organization,
            'password' => Hash::make($this->password),
            'role_id' => \App\Models\Role::where('role_name', 'Student')->value('id'),
            'profile_picture' => $profilePath,
            'account_status' => 'active',
        ]);

        Auth::login($user);

        // ✅ FIX REDIRECT (IMPORTANT)
        return $this->redirect('/student/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('layouts.auth-full'); // IMPORTANT
    }
}
