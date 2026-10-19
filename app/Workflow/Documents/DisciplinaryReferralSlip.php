<?php

namespace App\Workflow\Documents;


use Carbon\Carbon;

/**
 * F-SDM-011 Rev. 1 (03-31-23): Disciplinary Referral Slip Form.
 * A faculty member, security guard or other signatory refers a student to the VPSD office.
 * Only the referring staff member signs (on the paper form too), so the chain is empty:
 * their signature is captured when they issue it, then the VPSD does the final verification.
 */
final class DisciplinaryReferralSlip extends WorkflowDocument
{
    public function key(): string
    {
        return 'disciplinary_referral_slip';
    }

    public function name(): string
    {
        return 'Disciplinary Referral Slip';
    }

    public function description(): string
    {
        return 'A referral of a student to the VPSD office for appropriate action.';
    }

    public function formCode(): string
    {
        return 'F-SDM-011 Rev. 1 (03-31-23)';
    }

    /** The student never starts it. */
    public function issuedByOffice(): bool
    {
        return true;
    }

    /** Any current signatory (faculty, guard, personnel) may issue it. */
    public function issuedBySignatory(): bool
    {
        return true;
    }

    public function fields(): array
    {
        return [
            'reason' => [
                'label' => 'Reason for the referral', 'type' => 'textarea', 'required' => true, 'max' => 600,
                'help' => 'State the reason for referring the student to the VPSD office.',
                'sensitive' => true,
            ],
        ];
    }

    public function check(array $values): ?string
    {
        $user = auth()->user();

        // A signature is only required when the issuer has signing turned on.
        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this slip.';
        }

        return null;
    }

    /** No approval steps: the referring staff member's signature is captured at issue time. */
    public function chain(array $values = []): array
    {
        return [];
    }

    public function issueNote(): ?string
    {
        return 'The student is notified right away. You appear on the slip as the referring staff member, signed with your profile signature. The VPSD office then does the final verification.';
    }

    public function pdfView(): string
    {
        return 'pdf.disciplinary-referral-slip';
    }

    public function issuedMessage(array $values): string
    {
        return 'A disciplinary referral slip was issued to you. The VPSD office will contact you about it.';
    }

    public function summary(array $values): string
    {
        return 'Referral to the VPSD office';
    }

    /* ---- Dashboard "Dates to Remember" card ---- */

    public function showsAsNotice(): bool
    {
        return true;
    }

    public function noticeTitle(array $values): string
    {
        return 'Disciplinary Referral';
    }

    public function noticeLine(array $values): string
    {
        return $this->issuedMessage($values);
    }

    public function noticeTone(): string
    {
        return 'rose';
    }
}