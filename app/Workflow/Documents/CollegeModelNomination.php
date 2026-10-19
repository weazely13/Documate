<?php

namespace App\Workflow\Documents;

use App\Models\User;
use App\Services\Workflow\FormSupport;

class CollegeModelNomination extends CollegeModelNominationBase
{
    public function key(): string { return 'college_model_nomination'; }
    public function name(): string { return 'College Model Student Nomination'; }
    public function description(): string { return 'Nominate a fellow student as a College Model Student.'; }

    public function requiresStudentSignature(): bool { return true; }

    /** The logged-in student is the nominator. */
    public function systemFields(): array
    {
        return [
            'student_name' => 'Nominator',
            'student_number' => 'Student number',
            'student_program' => 'Program, year and section',
        ];
    }

    /** The nominee is searched and picked, so there is nothing to type. */
    public function fields(): array { return []; }

    public function usesNomineePicker(): bool { return true; }

    public function nomineeSnapshot(User $nominee): array
    {
        $nominee->loadMissing('program.college');

        return [
            'nominee_user_id' => (string) $nominee->id,
            'nominee_name' => FormSupport::systemValue($nominee, 'formatted_full_name'),
            'nominee_college' => FormSupport::systemValue($nominee, 'college'),
            'nominee_program' => FormSupport::systemValue($nominee, 'program'),
            'nominee_year_section' => FormSupport::systemValue($nominee, 'year_section'),
            'nominee_address' => FormSupport::systemValue($nominee, 'address'),
            'nominee_contact' => FormSupport::systemValue($nominee, 'contact_number'),
        ];
    }

    public function studentNote(): ?string
    {
        return 'Search for the nominee. Their college, program, year and section are filled in from their profile. You are the nominator, and your profile e-signature is applied. The VPSD office then does the final verification.';
    }
}