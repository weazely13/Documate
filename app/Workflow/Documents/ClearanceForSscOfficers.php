<?php

namespace App\Workflow\Documents;

/** F-SDM-022 Rev. 2 (05-09-25): Clearance for SSC/Student Publication Officers. */
final class ClearanceForSscOfficers extends ClearanceBase
{
    public function key(): string { return 'clearance_ssc_officers'; }
    public function name(): string { return 'Clearance for SSC/Student Publication Officers'; }
    public function description(): string { return 'Clearance of property and financial responsibilities for SSC and Student Publication officers.'; }
    public function formCode(): string { return 'F-SDM-022 Rev. 2 (05-09-25)'; }
    public function pdfView(): string { return 'pdf.ssc-clearance'; }

    protected function officersOnly(): bool { return true; }

    public function chain(array $values = []): array
    {
        return [
            $this->step('ssc_treasurer', 'SSC Treasurer/SPO Managing Editor'),
            $this->step('ssc_secretary', 'SSC Secretary'),
            $this->step('ssc_president', 'SSC President/SPO Editor-in-Chief'),
            $this->step('ssc_adviser', 'SSC/SPO Adviser'),
            $this->step('chief_librarian', 'College Chief Librarian'),
            $this->step('university_accountant', 'University Accountant'),
            $this->step('director_soa', 'Director, Student Organizations and Activities'),
            $this->step('vpsd', 'Vice President for Student Development'),
        ];
    }

    public function studentNote(): ?string
    {
        return 'Your position and organization are taken from your officer record. The SSC officers and Adviser, the College Chief Librarian, the University Accountant, the Director for Student Organizations and Activities and the Vice President sign online. Your profile e-signature is applied.';
    }
}