<?php

namespace App\Workflow\Documents;

/**
 * F-SDM-028 Rev. 1 (03-31-23): YMCA Outstanding Student Award Faculty/Peer Rating Form.
 * Same flow as the Model Student peer rating: the student names a rater (classmate or teacher),
 * who gets an emailed link, confirms their email with a one-time code, then rates and signs.
 */
final class YmcaAwardRating extends ModelStudentRating
{
    public function key(): string { return 'ymca_award_rating'; }

    public function name(): string { return 'YMCA Outstanding Student Award Peer Rating'; }

    public function description(): string
    {
        return 'Ask a classmate or teacher to rate you for the YMCA Outstanding Student Award. They sign online with their email, no account needed.';
    }

    public function formCode(): string { return 'F-SDM-028 Rev. 1 (03-31-23)'; }

    public function pdfView(): string { return 'pdf.ymca-award-rating'; }
}