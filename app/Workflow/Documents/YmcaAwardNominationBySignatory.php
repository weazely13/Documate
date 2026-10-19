<?php

namespace App\Workflow\Documents;

/** F-SDM-027 Rev. 1 (03-31-23): YMCA Outstanding Student Award Nomination Form (issued by a signatory). */
final class YmcaAwardNominationBySignatory extends CollegeModelNominationBySignatory
{
    public function key(): string { return 'ymca_award_nomination_signatory'; }
    public function name(): string { return 'YMCA Outstanding Student Award Nomination (by signatory)'; }
    public function description(): string { return 'A signatory nominates a student for the YMCA Outstanding Student Award.'; }
    public function formCode(): string { return 'F-SDM-027 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.ymca-award-nomination'; }
    public function summary(array $values): string { return 'YMCA Outstanding Student Award nomination'; }

    public function issuedMessage(array $values): string
    {
        return 'You were nominated for the YMCA Outstanding Student Award. Once the VPSD office completes it, you can download the PDF from your Documents page.';
    }

    public function noticeTitle(array $values): string { return 'YMCA Outstanding Student Award Nomination'; }
}