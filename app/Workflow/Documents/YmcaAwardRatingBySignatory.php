<?php

namespace App\Workflow\Documents;

/** F-SDM-028 Rev. 1 (03-31-23), issued by a signatory. */
final class YmcaAwardRatingBySignatory extends ModelStudentRatingBySignatory
{
    public function key(): string { return 'ymca_award_rating_signatory'; }
    public function name(): string { return 'YMCA Outstanding Student Award Peer Rating (by signatory)'; }
    public function description(): string { return 'A faculty member or other signatory rates a student for the YMCA Outstanding Student Award.'; }
    public function formCode(): string { return 'F-SDM-028 Rev. 1 (03-31-23)'; }
    public function pdfView(): string { return 'pdf.ymca-award-rating'; }
    public function summary(array $values): string { return 'YMCA Outstanding Student Award rating'; }
}