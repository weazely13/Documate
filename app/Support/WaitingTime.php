<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * How long a transaction has waited on the office, in school days, and how urgent that is.
 * Saturdays and Sundays are skipped because the VPSD office is closed on weekends.
 */
class WaitingTime
{
    /** Minimum school days for each level, most urgent first. */
    public const LEVELS = [
        'overdue' => 7,
        'urgent' => 5,
        'follow_up' => 3,
        'due' => 1,
    ];

    /** Whole school days elapsed between $since and $now. */
    public static function schoolDays(?CarbonInterface $since, ?CarbonInterface $now = null): int
    {
        if (! $since) {
            return 0;
        }

        $now ??= now();
        $cursor = $since->copy();
        $days = 0;

        while ($cursor->addDay()->lte($now)) {
            if (! $cursor->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }

    public static function level(int $days): ?string
    {
        foreach (self::LEVELS as $level => $min) {
            if ($days >= $min) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Display data for a waiting time: the text to show, its urgency level and the dot colour.
     * With $flag off the age is shown plainly, without an urgency level.
     *
     * @return array{level: ?string, days: int, text: string, dot: string}
     */
    public static function describe(?CarbonInterface $since, bool $flag = true, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $days = self::schoolDays($since, $now);
        $level = $flag ? self::level($days) : null;

        $span = match (true) {
            ! $since => '-',
            $days >= 1 => $days . ' ' . ($days === 1 ? 'day' : 'days'),
            $since->diffInHours($now) >= 1 => (int) $since->diffInHours($now) . ' ' . ((int) $since->diffInHours($now) === 1 ? 'hour' : 'hours'),
            default => 'Just now',
        };

        $label = match ($level) {
            'overdue' => 'Overdue',
            'urgent' => 'Urgent',
            'follow_up' => 'Follow up',
            default => null,
        };

        return [
            'level' => $level,
            'days' => $days,
            'text' => $label ? "{$label} · {$span}" : $span,
            'dot' => match ($level) {
                'overdue' => 'bg-red-900',
                'urgent' => 'bg-red-600',
                'follow_up' => 'bg-orange-500',
                'due' => 'bg-amber-300',
                default => 'bg-slate-300',
            },
        ];
    }

    /** The latest moment that already counts as overdue. */
    public static function overdueCutoff(?CarbonInterface $now = null): CarbonInterface
    {
        $cursor = ($now ?? now())->copy();
        $days = 0;

        while ($days < self::LEVELS['overdue']) {
            if (! $cursor->isWeekend()) {
                $days++;
            }
            $cursor->subDay();
        }

        return $cursor;
    }
}
