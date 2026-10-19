<?php

namespace App\Workflow\Documents;

/** F-SDM-022 Rev. 1 (03-31-23): Clearance for SSC/Student Publication Officers. */
final class ClearanceForSscOfficers extends ClearanceBase
{
    public function key(): string { return 'clearance_ssc_officers'; }
    public function name(): string { return 'Clearance for SSC/Student Publication Officers'; }
    public function description(): string { return 'Clearance of property and financial responsibilities for SSC and Student Publication officers.'; }
    public function formCode(): string { return 'F-SDM-022 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.ssc-clearance'; }

    protected function officersOnly(): bool { return true; }

    public function chain(array $values = []): array
    {
        return [
            $this->step('ssc_treasurer', 'SSC Treasurer'),
            $this->step('ssc_secretary', 'SSC Secretary'),
            $this->step('ssc_president', 'SSC President'),
            $this->step('ssc_adviser', 'SSC Adviser'),
            $this->step('chief_librarian', 'Chief Librarian'),
            $this->step('university_accountant', 'University Accountant'),
            $this->step('vpsd', 'Vice President for Student Development'),
        ];
    }

    public function studentNote(): ?string
    {
        return 'Your position and organization are taken from your officer record. The SSC officers and Adviser, the Chief Librarian, the University Accountant and the Vice President sign online. Your profile e-signature is applied.';
    }
}