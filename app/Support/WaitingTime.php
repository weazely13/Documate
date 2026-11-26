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

    /**
     * The processing period in one plain word, the same everywhere (Workflow Review, its legend, the record page):
     * New (within 24 hours) · Recent (1–2 school days) · Pending (3–4) · Delayed (5–6) · Overdue (7+).
     */
    public const LABELS = [
        'overdue' => 'Overdue',
        'urgent' => 'Delayed',
        'follow_up' => 'Pending',
        'due' => 'Recent',
    ];

    public const TODAY = 'New';

    /** What each word means, for the pop-out on a time label and the legend. Keyed by level ('' = Today). */
    public const MEANINGS = [
        '' => ['within 24 hours', 'Just came in. Nothing is late yet.'],
        'due' => ['1–2 school days', 'Still on track. Handle it this week.'],
        'follow_up' => ['3–4 school days', 'Taking a while. Follow it up soon.'],
        'urgent' => ['5–6 school days', 'Running late. Act on it today.'],
        'overdue' => ['7 or more school days', 'Past the 7-day limit. Act on it now.'],
    ];

    /** Time-elapsed filter: word key => [at least, under] school days (null = no bound). */
    public const FILTER = [
        'today' => [null, 1],
        'recent' => [1, 3],
        'slow' => [3, 5],
        'late' => [5, 7],
        'overdue' => [7, null],
    ];

    /** The legend: [dot, word, range, meaning] for every level, from New to Overdue. */
    public static function legend(): array
    {
        $rows = [];
        foreach (['', 'due', 'follow_up', 'urgent', 'overdue'] as $level) {
            [$range, $meaning] = self::MEANINGS[$level];
            $rows[] = [self::dot($level ?: null), $level ? self::LABELS[$level] : self::TODAY, $range, $meaning];
        }

        return $rows;
    }

    /** The processing period pill (word only, no day count), by level ('' = New). */
    public const PILLS = [
        '' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'due' => 'bg-yellow-50 text-yellow-800 ring-yellow-300',
        'follow_up' => 'bg-orange-50 text-orange-700 ring-orange-200',
        'urgent' => 'bg-red-50 text-red-700 ring-red-200',
        'overdue' => 'bg-red-100 text-red-900 ring-red-300',
    ];

    /** The word on the pill: the level's word, or "With student" when the time is the student's (not flagged). */
    public static function pillWord(array $w): string
    {
        return $w['word'] ?? 'With student';
    }

    public static function pillClasses(array $w): string
    {
        return $w['word'] ? self::PILLS[$w['level'] ?? ''] : self::PILLS[''];
    }

    public static function dot(?string $level): string
    {
        return match ($level) {
            'overdue' => 'bg-red-900',
            'urgent' => 'bg-red-500',
            'follow_up' => 'bg-orange-500',
            'due' => 'bg-yellow-400',
            default => 'bg-slate-400',
        };
    }

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
     * 'word' is the one-word term (null when not flagged), 'hint' the plain sentence the pop-out shows.
     *
     * @return array{level: ?string, days: int, word: ?string, text: string, hint: string, dot: string}
     */
    public static function describe(?CarbonInterface $since, bool $flag = true, ?CarbonInterface $now = null): array
    {
        $now ??= now();
        $days = self::schoolDays($since, $now);
        $level = $flag ? self::level($days) : null;
        $hours = $since ? (int) $since->diffInHours($now) : 0;

        // Days (or hours) as one short token: "26d", "3h".
        $span = match (true) {
            ! $since => '-',
            $days >= 1 => $days . 'd',
            $hours >= 1 => $hours . 'h',
            default => '',
        };

        $word = match (true) {
            ! $flag || ! $since => null,
            $level !== null => self::LABELS[$level],
            default => self::TODAY,
        };

        $plain = match (true) {
            ! $since => 'Not started yet.',
            $days >= 1 => $days . ' school ' . ($days === 1 ? 'day' : 'days'),
            $hours >= 1 => $hours . ' ' . ($hours === 1 ? 'hour' : 'hours'),
            default => 'a few minutes',
        };
        $hint = match (true) {
            ! $since => 'Not started yet.',
            ! $flag => 'With the student for ' . $plain . '. Their time, so it is not flagged.',
            default => $word . ': waiting ' . $plain . '. ' . self::MEANINGS[$level ?? ''][1] . ' Weekends do not count.',
        };

        return [
            'level' => $level,
            'days' => $days,
            'word' => $word,
            'text' => $word ? trim($word . ($span !== '' ? ' · ' . $span : '')) : ($span ?: 'Just now'),
            'hint' => $hint,
            'dot' => self::dot($level),
        ];
    }

    /** The latest moment that already counts as overdue. */
    public static function overdueCutoff(?CarbonInterface $now = null): CarbonInterface
    {
        return self::cutoff(self::LEVELS['overdue'], $now);
    }

    /** The latest moment from which $days school days have already passed (so "waiting since" at or before it = at least $days). */
    public static function cutoff(int $days, ?CarbonInterface $now = null): CarbonInterface
    {
        $wanted = $days;
        $cursor = ($now ?? now())->copy();
        $days = 0;

        while ($days < $wanted) {
            if (! $cursor->isWeekend()) {
                $days++;
            }
            $cursor->subDay();
        }

        return $cursor;
    }
}
