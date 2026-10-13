<?php

namespace App\Workflow\Documents;

use Carbon\Carbon;
use App\Models\User;

/**
 * A paperless document defined in code (no template editor, no template tables).
 * To add another VPSD form later: copy AdmissionSlip, change the fields / chain / PDF view,
 * and register it in DocumentRegistry.
 */
abstract class WorkflowDocument
{
    /** Fields the VPSD reviewer fills in before sending for signing (not the student). */
    public function reviewerFields(): array
    {
        return [];
    }

    public function allFields(): array
    {
        return $this->fields() + $this->reviewerFields();
    }

    /** Cross-field checks that can't be expressed as per-field rules. Return an error message or null. */
    public function check(array $values): ?string
    {
        return null;
    }

    
    /** One place that turns a stored answer into display text (dates, ticks, plain text). */
    public function formatValue(array $f, mixed $v): string
    {
        $type = $f['type'] ?? 'text';

        if ($type === 'checkbox') {
            return $v ? 'Yes' : 'No';
        }
        if ($v === null || $v === '') {
            return '';
        }
        if ($type === 'date') {
            return Carbon::parse($v)->format('F j, Y');
        }
        if ($type === 'time') {
            return Carbon::createFromFormat('H:i', substr((string) $v, 0, 5))->format('g:i A');
        }

        return (string) $v;
    }
    public function requiresStudentSignature(): bool
    {
        return false;
    }
    /** True when the VPSD office fills in and issues this document TO a student (the student never starts it). */
    public function issuedByOffice(): bool
    {
        return false;
    }
    /** Stored in student_document_workspaces.document_type. */
    abstract public function key(): string;

    abstract public function name(): string;

    abstract public function description(): string;

    /** Official form number printed on the PDF. */
    abstract public function formCode(): string;

    /**
     * What the student fills in. name => [label, type (text|textarea|date), required, max?, rules?, placeholder?, help?]
     * Student details (name, program, year, section) are NOT listed: they come from the account.
     */
    abstract public function fields(): array;
    

    /** Approval chain in order: [['role' => signatory_roles.key, 'label' => 'Recommending Approval'], ...] */
    abstract public function chain(array $values = []): array;

    abstract public function pdfView(): string;

    /** How many copies are printed on the page. */
    public function pdfCopies(): int
    {
        return 1;
    }

    /** One line for lists, built from the saved answers. */
    public function summary(array $values): string
    {
        return '';
    }
    /**
     * True when external signers are invited BY HAND by a signatory (not automatically):
     * their steps all open together, nothing is emailed until the sender clicks Send, and the
     * sender records the identity check at that moment.
     */
    public function manualExternalInvites(): bool
    {
        return false;
    }

    /** Field keys an external signer is shown on the signing page (review kind). */
    public function externalOverviewFields(): array
    {
        return [];
    }
    /** Signatory role key that issues this document instead of the VPSD admin. null = admin issues it. */
    public function issuedByRole(): ?string
    {
        return null;
    }

    /** Appears on the student's "Dates to Remember" card. */
    public function showsAsNotice(): bool
    {
        return false;
    }

    /** Date on the card's calendar badge. null = falls back to the issue date. */
    public function noticeDate(array $values): ?string
    {
        return null;
    }

    public function noticeTitle(array $values): string
    {
        return $this->name();
    }

    public function noticeLine(array $values): string
    {
        return '';
    }

    /** 'blue' or 'rose' */
    public function noticeTone(): string
    {
        return 'blue';
    }
    /** true = any current signatory (faculty, guard, counselor...) may issue it. The admin may not. */
    public function issuedBySignatory(): bool
    {
        return false;
    }

    /** Issued by staff with a signatory role instead of by the VPSD admin. */
    public function issuerIsStaff(): bool
    {
        return $this->issuedByRole() !== null || $this->issuedBySignatory();
    }

    public function canBeIssuedBy(User $user): bool
    {
        if ($role = $this->issuedByRole()) {
            return $user->holdsSignatoryRole($role);
        }
        if ($this->issuedBySignatory()) {
            return $user->isSignatory();
        }

        return $user->isAdmin();
    }

    /** Shown to the signer before they sign. */
    public function signingNote(): ?string
    {
        return null;
    }

    /** Intro text on the Issue page. null = default wording. */
    public function issueNote(): ?string
    {
        return null;
    }

    /** Fill in values the issuer left blank, before validation. */
    public function beforeIssue(array $answers): array
    {
        return $answers;
    }
}