<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\GeminiOCRService;
use App\Models\StudentVerification;

class VerifyStudent extends Component
{
    use WithFileUploads;

    public $e_slip;

    public $ocrResult = [];
    public $isVerified = false;
    public $matchScore = 0;
    public $matchDetails = [];
    public $fieldStatus = [];
    public $showOcrModal = false;

    
    public function getUserDataProperty()
    {
        $user = auth()->user();

        return [
            'student_number' => $user->student_number,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'year' => $user->year_level,
            'semester' => currentSemester(),
            'academic_year' => currentAcademicYear(),
        ];
    }
    public function verify()
    {
        $this->validate([
            'e_slip' => 'required|image|mimes:jpg,jpeg,png|max:5120'
        ]);

        $path = $this->e_slip->store('e_slips');
        
        $alreadyVerified = auth()->user()->verifications()
            ->where('semester', currentSemester())
            ->where('academic_year', currentAcademicYear())
            ->where('status', 'verified')
            ->exists();

        if ($alreadyVerified) {
            $this->dispatch('alert', type: 'info', message: 'You are already verified, but you can review your new submission.');
        }

        $ocrData = GeminiOCRService::extractText($path);
        $this->ocrResult = $ocrData ?? [];
        

        if (!$ocrData) {
            $this->isVerified = false;
            $this->dispatch('alert', type: 'error', message: 'OCR failed. Try again.');
            return;
        }

        // 🔥 NORMALIZATION
        $normalize = fn($v) => strtolower(trim(preg_replace('/[^a-z0-9]/i', '', $v)));

        $normalizeSemester = function ($value) {
            $value = strtolower(trim($value));

            // FIRST SEMESTER
            if (
                str_contains($value, '1') ||
                str_contains($value, '1st') ||
                str_contains($value, 'first')
            ) {
                return '1st semester';
            }

            // SECOND SEMESTER
            if (
                str_contains($value, '2') ||
                str_contains($value, '2nd') ||
                str_contains($value, 'second')
            ) {
                return '2nd semester';
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

        $user = auth()->user();

        // INPUT (DB USER)
        $inputStudent = preg_replace('/\D/', '', $user->student_number);
        $inputFirst = $normalize($user->first_name);
        $inputLast = $normalize($user->last_name);
        $inputYear = $normalizeYear($user->year_level);

        // OCR
        $ocrStudent = preg_replace('/\D/', '', $ocrData['student_number'] ?? '');
        $ocrFirst = $normalize($ocrData['first_name'] ?? '');
        $ocrLast = $normalize($ocrData['last_name'] ?? '');
        $ocrYear = $normalizeYear($ocrData['year'] ?? '');

        $ocrSemester = $normalizeSemester($ocrData['semester'] ?? '');
        $systemSemester = $normalizeSemester(currentSemester());

        $ocrAY = trim($ocrData['academic_year'] ?? '');
        $systemAY = currentAcademicYear();

        $inputCollege = $normalize($user->college);
        $inputCourse = $normalize($user->program);

        $ocrCollege = $normalize($ocrData['college'] ?? '');
        $ocrCourse = $normalize($ocrData['course'] ?? '');
        // 🔥 CHECKS
        $checks = [
            'Student Number' => $ocrStudent === $inputStudent,
            'First Name' => $ocrFirst === $inputFirst,
            'Last Name' => $ocrLast === $inputLast,

            'College' => $ocrCollege === $inputCollege, // ✅ ADD
            'Course' => $ocrCourse === $inputCourse,   // ✅ ADD

            'Semester' => $ocrSemester === $systemSemester,
            'Academic Year' => $ocrAY === $systemAY,
            'Year Level' => $ocrYear === $inputYear,

            'Enrolled Stamp' => $ocrData['officially_enrolled'] ?? false,
        ];

        $this->matchDetails = $checks;

        $this->matchScore = round(
            (collect($checks)->filter()->count() / count($checks)) * 100
        );

        // 🔥 STRICT VERIFICATION
        $this->isVerified =
            $checks['Student Number'] &&
            $checks['First Name'] &&
            $checks['Last Name'] &&
            $checks['Semester'] &&
            $checks['Academic Year'] &&
            $checks['Year Level'] &&
            $checks['Enrolled Stamp'];

        StudentVerification::create([
            'user_id' => $user->id,
            'student_number' => $user->student_number,
            'e_slip_path' => $path,
            'semester' => currentSemester(),
            'academic_year' => currentAcademicYear(),
            'status' => $this->isVerified
                ? StudentVerification::STATUS_VERIFIED
                : StudentVerification::STATUS_REJECTED,
            'ocr_data' => $this->ocrResult,
            'verified_at' => $this->isVerified ? now() : null,
        ]);
        // 🔥 ACTIVATE ACCOUNT
        if ($this->isVerified) {
            $user->update(['account_status' => 'active']);

            $this->dispatch('alert', type: 'success', message: 'Verification successful!');
        } else {
            $this->dispatch('alert', type: 'error', message: 'Verification failed.');
        }
    }
    public function goToSystem()
    {
        return $this->redirect('/student/dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.verify-student')
            ->layout('layouts.app', ['title' => 'Verify Student Account']); // ✅ since you're using $slot
    }
}
