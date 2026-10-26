<?php

namespace App\Workflow;

use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\TransactionWorkflow;
use Illuminate\Support\Collection;

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
        'schedule.set' => 'Counselling schedule set',
        'approval.routed_to_account' => 'Signer has an account, so signing moved to their account',
    ];

    public static function label(string $action): string
    {
        return self::ADMIN[$action] ?? $action;
    }

    /**
     * The transaction's activity for staff pages, newest first.
     *
     * @return Collection<int, array{text: string, who: string, role: ?string, at: \Carbon\CarbonInterface, note: ?string}>
     */
    public static function history(StudentDocumentWorkspace $ws): Collection
    {
        return $ws->auditLogs()->with('actor')->get()->map(fn ($log) => [
            'text' => self::label($log->action),
            'who' => $log->actor ? trim($log->actor->first_name . ' ' . $log->actor->last_name) : 'System',
            'role' => $log->actor_role,
            'at' => $log->created_at,
            'note' => $log->meta['message']
                ?? $log->meta['reason']
                ?? ($log->action === 'external.identity_checked'
                    ? (TransactionWorkflow::IDENTITY_METHODS[$log->meta['method'] ?? ''] ?? '')
                        . (! empty($log->meta['note']) ? ': ' . $log->meta['note'] : '')
                    : null),
        ])->reverse()->values();
    }
}