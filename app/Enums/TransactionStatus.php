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
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::ActionRequired => 'Action Required',
            self::Rejected => 'Rejected',
            self::Approved => 'Approved',
            self::Completed => 'Completed',
            self::Voided => 'Voided',
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