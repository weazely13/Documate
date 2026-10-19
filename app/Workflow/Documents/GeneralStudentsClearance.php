<?php

namespace App\Workflow\Documents;

use App\Models\Organization;
use App\Models\User;

/**
 * F-SDM-020 Rev. 1 (03-31-23): General Students' Clearance.
 * Nothing to fill in: everything comes from the student's profile.
 *  - Profile organization (never a year level org): if academic -> its Treasurer and Adviser
 *    sign as the Academic Organization; if non-academic -> as the Non-Academic Organization.
 *  - Profile year level -> the year level organization (organizations.year_level = 1..4),
 *    completely independent of programs and of the profile organization. Its Treasurer signs.
 *  - The Homeroom Adviser (student's program), College Chief Librarian and the VP always sign.
 * The organizations are frozen into the record when the student saves/submits, so the chain is stable.
 * No matching organization = that step is left out and its line stays blank on the printed form.
 */
final class GeneralStudentsClearance extends ClearanceBase
{
    public function key(): string { return 'general_students_clearance'; }
    public function name(): string { return "General Students' Clearance"; }
    public function description(): string { return 'Clearance of property and financial responsibilities for the semester.'; }
    public function formCode(): string { return 'F-SDM-020 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.general-clearance'; }

    /** Organizations worked out from the profile at save time. */
    public function studentSnapshotExtras(User $user): array
    {
        // Profile organization: a year level org is ignored here, it is derived from year level below.
        $org = $user->organization_id
            ? Organization::active()->notYearLevel()->find($user->organization_id)
            : null;
        $isAcademic = $org && ($org->is_academic ?? true);
        $isNonAcademic = $org && ! ($org->is_academic ?? true);

        // Year level organization: matched purely by the student's year level.
        $year = (int) preg_replace('/\D/', '', (string) $user->year_level);
        $yearOrg = $year
            ? Organization::active()->yearLevel()->where('year_level', $year)->first()
            : null;

        return [
            'clr_academic_org_id' => $isAcademic ? (string) $org->id : null,
            'clr_academic_org' => $isAcademic ? $org->name : null,
            'clr_non_academic_org_id' => $isNonAcademic ? (string) $org->id : null,
            'clr_non_academic_org' => $isNonAcademic ? $org->name : null,
            'clr_year_org_id' => $yearOrg ? (string) $yearOrg->id : null,
            'clr_year_org' => $yearOrg?->name,
        ];
    }

    public function chain(array $values = []): array
    {
        $steps = [];

        if ($id = (int) ($values['clr_academic_org_id'] ?? 0)) {
            $steps[] = $this->step('org_treasurer', 'Academic Organization Treasurer', 'organization', $id);
            $steps[] = $this->step('org_adviser', 'Academic Organization Adviser', 'organization', $id);
        }
        if ($id = (int) ($values['clr_non_academic_org_id'] ?? 0)) {
            $steps[] = $this->step('org_treasurer', 'Non-Academic Organization Treasurer', 'organization', $id);
            $steps[] = $this->step('org_adviser', 'Non-Academic Organization Adviser', 'organization', $id);
        }
        if ($id = (int) ($values['clr_year_org_id'] ?? 0)) {
            $steps[] = $this->step('org_treasurer', 'Year Level Organization Treasurer', 'organization', $id);
        }

        $steps[] = $this->step('homeroom_adviser', 'Homeroom Adviser', 'program');
        $steps[] = $this->step('chief_librarian', 'College Chief Librarian');
        $steps[] = $this->step('vpsd', 'Vice President for Student Development');

        return $steps;
    }

    public function studentNote(): ?string
    {
        return 'Nothing to fill in. The signers are picked from your profile: your organization, the organization for your year level, your Homeroom Adviser, the College Chief Librarian and the Vice President. Keep your organization, program and year level up to date on your profile before submitting. Your profile e-signature is applied.';
    }
}