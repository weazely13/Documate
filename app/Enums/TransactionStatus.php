<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case ActionRequired = 'action_required';
    case Rejected = 'rejected';
    case Approved = 'approved';   // all required signatories have approved
    case Completed = 'completed'; // final verification done, official PDF may be issued
    case Voided = 'voided';       // a completed document withdrawn by VPSD (kept for the record)

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Pending',
            self::UnderReview => 'Reviewing',
            self::ActionRequired => 'Revision',
            self::Rejected => 'Rejected',
            self::Approved => 'Signed',
            self::Completed => 'Completed',
            self::Voided => 'Voided',
        };
    }

    /** The status palette hue (App\Support\StatusStyle::TONES), as on Reports. */
    public function tone(): string
    {
        return match ($this) {
            self::Completed => 'emerald',
            self::Submitted => 'yellow',       // Pending: waiting to be reviewed
            self::UnderReview => 'blue',       // Reviewing: being reviewed
            // (Signing, not a stored status, is purple: StatusStyle::transaction)
            self::ActionRequired => 'orange',  // Revision: needs changes before it can go on
            self::Approved => 'green',         // Signed: ready to issue to the student
            self::Rejected => 'rose',
            self::Voided => 'slate',
            self::Draft => 'slate',
        };
    }

    /** @return array<self> */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::UnderReview, self::Rejected],
            self::UnderReview => [self::ActionRequired, self::Rejected, self::Approved],
            self::ActionRequired => [self::UnderReview, self::Rejected],
            self::Approved => [self::Completed],
            self::Completed => [self::Voided],
            self::Rejected, self::Voided => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    public function isTerminal(): bool
    {
        return $this->allowedTransitions() === [];
    }
}