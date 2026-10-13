<?php

namespace App\Enums;

enum ApprovalStatus: string
{
    case Waiting = 'waiting';   // earlier step not finished yet
    case Pending = 'pending';   // open: holders of the role may act
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Skipped = 'skipped';   // cancelled because the transaction was rejected

    /** Raw values of the statuses that mean "not acted on yet" (handy for query builders). */
    public static function openValues(): array
    {
        return [self::Waiting->value, self::Pending->value];
    }
}
