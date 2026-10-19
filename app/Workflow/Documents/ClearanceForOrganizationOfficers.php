<?php

namespace App\Workflow\Documents;

use Illuminate\Support\Str;

/**
 * F-SDM-021 Rev. 1 (03-31-23): Clearance for Organization Officers.
 * The SSC Treasurer signs only for an organization Treasurer, and the SSC Secretary only for an
 * organization Secretary ("For Organization Treasurer/Secretary Only" on the paper form).
 * Position and organization come from the officer record.
 */
final class ClearanceForOrganizationOfficers extends ClearanceBase
{
    public function key(): string { return 'clearance_organization_officers'; }
    public function name(): string { return 'Clearance for Organization Officers'; }
    public function description(): string { return 'Clearance of property and financial responsibilities for organization officers.'; }
    public function formCode(): string { return 'F-SDM-021 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.officers-clearance'; }

    protected function officersOnly(): bool { return true; }

    public function chain(array $values = []): array
    {
        $position = Str::lower((string) ($values['officer_position'] ?? ''));

        $steps = [$this->step('org_adviser', 'Academic Organization Adviser', 'organization')];

        if (str_contains($position, 'treasurer')) {
            $steps[] = $this->step('ssc_treasurer', 'SSC Treasurer');
        }
        if (str_contains($position, 'secretary')) {
            $steps[] = $this->step('ssc_secretary', 'SSC Secretary');
        }

        $steps[] = $this->step('ssc_president', 'SSC President');
        $steps[] = $this->step('chief_librarian', 'Chief Librarian');
        $steps[] = $this->step('university_accountant', 'University Accountant');
        $steps[] = $this->step('vpsd', 'Vice President for Student Development');

        return $steps;
    }

    public function studentNote(): ?string
    {
        return 'Your position and organization are taken from your officer record. Your Adviser, the SSC officers, the Chief Librarian, the University Accountant and the Vice President sign online. Your profile e-signature is applied.';
    }
}