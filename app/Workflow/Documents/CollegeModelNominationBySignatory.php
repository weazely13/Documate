<?php

namespace App\Workflow\Documents;

class CollegeModelNominationBySignatory extends CollegeModelNominationBase
{
    public function key(): string { return 'college_model_nomination_signatory'; }
    public function name(): string { return 'College Model Student Nomination (by signatory)'; }
    public function description(): string { return 'A signatory nominates a student as a College Model Student.'; }

    /** The student never starts this version. */
    public function issuedByOffice(): bool { return true; }

    /** Any current signatory may issue it. Also triggers the issuer snapshot (name, role, signature). */
    public function issuedBySignatory(): bool { return true; }

    /** The picked student is the nominee. College, program and year/section come from their account. */
    /** Nothing to type: the nominee's address and contact number come from their account. */
    public function fields(): array { return []; }

    public function checkStudent(array $snapshot): ?string
    {
        if (trim((string) ($snapshot['student_address'] ?? '')) === '') {
            return 'This student has no address on their profile. Ask them to add it before nominating.';
        }
        if (trim((string) ($snapshot['student_contact'] ?? '')) === '') {
            return 'This student has no contact number on their profile. Ask them to add it before nominating.';
        }

        return null;
    }
    public function check(array $values): ?string
    {
        $user = auth()->user();

        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this nomination.';
        }

        return null;
    }

    public function issueNote(): ?string
    {
        return 'Pick the nominee. Their college, program, address and contact number are copied from their profile. You appear on the form as the nominator, signed with your profile signature. The VPSD office then does the final verification.';
    }

    public function issuedMessage(array $values): string
    {
        return 'You were nominated as a College Model Student. Once the VPSD office completes it, you can download the PDF from your Documents page.';
    }

    public function showsAsNotice(): bool { return true; }
    public function noticeTitle(array $values): string { return 'College Model Student Nomination'; }
    public function noticeLine(array $values): string { return $this->issuedMessage($values); }
    public function noticeTone(): string { return 'blue'; }
}