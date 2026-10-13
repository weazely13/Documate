<?php

namespace App\Workflow;

final class AuditLabels
{
    public const ADMIN = [
        'transaction.created' => 'Draft created',
        'transaction.submitted' => 'Submitted by student',
        'review.started' => 'Review started',
        'review.action_requested' => 'Changes requested',
        'student.responded' => 'Student resubmitted',
        'review.accepted' => 'Sent for signing',
        'approval.approved' => 'Signed',
        'approval.rejected' => 'Rejected by signatory',
        'approvals.completed' => 'All signatures collected',
        'review.rejected' => 'Rejected by VPSD',
        'transaction.completed' => 'Completed',
        'document.generated' => 'Official PDF generated',
        'document.downloaded' => 'PDF downloaded',
        'external.invited' => 'Invitation emailed to external signer',
        'external.code_sent' => 'Verification code emailed',
        'external.authenticated' => 'External signer confirmed their email',
        'external.identity_checked' => 'Rater identity checked by VPSD',
        'document.voided' => 'Document voided by VPSD',
        'transaction.reissued' => 'Corrected draft created',
        'report.generated' => 'Transaction report generated',
        'transaction.issued_by_office' => 'Issued by the VPSD office',
        'external.email_corrected' => 'Member email corrected by the Chairman',
        'transaction.issued_by_role' => 'Issued by university staff',
    ];

    public static function label(string $action): string
    {
        return self::ADMIN[$action] ?? $action;
    }
}