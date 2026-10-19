<?php

namespace App\Workflow\Documents;

/** F-SDM-027 Rev. 1 (03-31-23): YMCA Outstanding Student Award Nomination Form (student-filed). */
final class YmcaAwardNomination extends CollegeModelNomination
{
    public function key(): string { return 'ymca_award_nomination'; }
    public function name(): string { return 'YMCA Outstanding Student Award Nomination'; }
    public function description(): string { return 'Nominate a fellow student for the YMCA Outstanding Student Award.'; }
    public function formCode(): string { return 'F-SDM-027 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.ymca-award-nomination'; }
    public function summary(array $values): string { return 'YMCA Outstanding Student Award nomination'; }
}