<?php

namespace App\Workflow\Documents;

/**
 * F-SDM-017 Rev. 1 (03-31-23): Recommendation for Student Assistantship Form.
 * Any current signatory (faculty, guard, personnel, etc.) may recommend a student to the VPSD.
 * Only the issuer signs (on the paper form too), so the chain is empty: their signature is
 * captured when they issue it, then the VPSD does the final verification.
 */
final class StudentAssistantshipRecommendation extends WorkflowDocument
{
    public function key(): string { return 'student_assistantship_recommendation'; }
    public function name(): string { return 'Recommendation for Student Assistantship'; }
    public function description(): string { return 'A recommendation of a student for Student Assistantship, issued by a faculty member or other signatory.'; }
    public function formCode(): string { return 'F-SDM-017 Rev. 1 (03-31-23)'; }

    /** The student never starts it. */
    public function issuedByOffice(): bool { return true; }

    /** Any current signatory may issue it. Also triggers the issuer snapshot (name, role, signature). */
    public function issuedBySignatory(): bool { return true; }

    /** Nothing to fill in: the student comes from the picker, the issuer from their account. */
    public function fields(): array { return []; }

    public function check(array $values): ?string
    {
        $user = auth()->user();

        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this recommendation.';
        }

        return null;
    }

    /** No approval steps. Freeze the VPSD's name as the addressee. */
    public function chain(array $values = []): array { return []; }

    public function beforeSigning(array $values): array { return $this->withVpsdName($values); }

    public function issueNote(): ?string
    {
        return 'The student is notified right away. You appear on the form as the recommending signatory, signed with your profile signature. The VPSD office then does the final verification.';
    }

    public function pdfView(): string { return 'pdf.student-assistantship-recommendation'; }

    public function issuedMessage(array $values): string
    {
        return 'You were recommended for Student Assistantship. Once the VPSD office completes it, you can download the PDF from your Documents page.';
    }

    public function summary(array $values): string { return 'Recommendation for Student Assistantship'; }

    /* ---- Dashboard "Dates to Remember" card ---- */
    public function showsAsNotice(): bool { return true; }
    public function noticeTitle(array $values): string { return 'Student Assistantship Recommendation'; }
    public function noticeLine(array $values): string { return $this->issuedMessage($values); }
    public function noticeTone(): string { return 'blue'; }
}