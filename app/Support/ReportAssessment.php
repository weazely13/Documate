<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * The key areas of a Reports PDF and how each is doing, decided by fixed rules on the numbers —
 * the same data always gives the same result. The AI only explains these results; it never sets them.
 * The thresholds are printed in the PDF's appendix, so a reader can check every mark.
 */
final class ReportAssessment
{
    /**
     * key => [area, objective, rule]. For a "higher is better" figure the rule is [ok at or above,
     * medium at or above]; for "lower is better" it is [ok at or below, medium at or below].
     * Anything past the medium band is High. Informational areas have no rule.
     */
    public const THRESHOLDS = [
        'completion' => ['Completion', 'How many requests filed in the period are finished and issued.', ['higher', 70, 40, '%']],
        'returns' => ['Returns', 'How many requests were rejected or voided.', ['lower', 5, 15, '%']],
        'turnaround' => ['Turnaround', 'How long a completed request took, from filing to completion.', ['lower', 3, 7, ' days']],
        'waiting' => ['Waiting work', 'Requests still open that were filed more than 7 days ago.', ['lower', 0, 5, '']],
        'demand' => ['Demand', 'How the number of requests changed against the comparison period.', null],
        'participation' => ['Participation', 'How many organization members filed at least one form in the period.', ['higher', 50, 25, '%']],
        'members_cleared' => ['Members cleared', 'How many organization members are cleared for the semester.', ['higher', 70, 40, '%']],
        'no_org' => ['Students without an organization', 'Active students whose profile names no organization.', ['lower', 0, PHP_INT_MAX, '']],
        'cleared' => ['Cleared', 'How many active students are cleared for the semester.', ['higher', 70, 40, '%']],
        'not_tagged' => ['Not yet checked', 'Students no officer has checked yet this semester.', ['lower', 10, 30, '%']],
        'uncleared' => ['Uncleared', 'Students found to still owe something.', ['lower', 5, 15, '%']],
        'semester_change' => ['Change from the earlier semester', 'How the cleared count moved against the semester compared with.', null],
    ];

    /** Days a request may stay open before it counts as waiting work. */
    public const WAITING_DAYS = 7;

    /**
     * @param array $d the Reports page's viewData()
     * @param Collection $forms the period's form records (state, submitted, days, user_id)
     * @return array<int, array{key: string, area: string, objective: string, value: float|int|null, valueText: string, result: string, priority: ?string, basis: string}>
     */
    public static function for(string $tab, array $d, Collection $forms): array
    {
        $pct = fn ($n, $t) => $t > 0 ? round($n / $t * 100) : null;

        $rows = match ($tab) {
            'organizations' => (function () use ($d, $pct) {
                $real = $d['orgSummary']->where('label', '!=', 'No organization');
                $none = (int) ($d['orgSummary']->firstWhere('label', 'No organization')['members'] ?? 0);

                return [
                    self::rate('participation', $pct($real->sum('availed'), $real->sum('members')), $real->sum('availed') . ' of ' . $real->sum('members') . ' members filed'),
                    self::rate('members_cleared', $pct($real->sum('Cleared'), $real->sum('members')), $real->sum('Cleared') . ' of ' . $real->sum('members') . ' members cleared'),
                    self::rate('no_org', $none, $none . ' ' . Str::plural('student', $none)),
                ];
            })(),
            'clearance' => (function () use ($d, $pct) {
                $c = $d['clearanceCounts'];
                $total = $d['studentTotal'];
                $prev = $d['prevClearanceCounts'];
                $rows = [
                    self::rate('cleared', $pct($c['Cleared'] ?? 0, $total), ($c['Cleared'] ?? 0) . ' of ' . $total . ' students'),
                    self::rate('not_tagged', $pct($c['Not tagged'] ?? 0, $total), ($c['Not tagged'] ?? 0) . ' of ' . $total . ' students'),
                    self::rate('uncleared', $pct($c['Uncleared'] ?? 0, $total), ($c['Uncleared'] ?? 0) . ' of ' . $total . ' students'),
                ];
                if ($prev !== null) {
                    $rows[] = self::info('semester_change', self::changeText((int) ($c['Cleared'] ?? 0), (int) ($prev['Cleared'] ?? 0), 'cleared', $d['prevSemesterLabel']));
                }

                return $rows;
            })(),
            default => (function () use ($d, $forms, $pct) {
                $t = $d['formTotals'];
                $done = $forms->whereNotNull('days');
                $avg = $done->count() ? round($done->avg('days'), 1) : null;
                $cutoff = now()->subDays(self::WAITING_DAYS);
                $waiting = $forms->filter(fn ($r) => ! in_array($r['state'], ['completed', 'rejected', 'voided'], true) && $r['submitted'] && $r['submitted']->lt($cutoff))->count();
                $rows = [
                    self::rate('completion', $pct($t['completed'], $t['requests']), $t['completed'] . ' of ' . $t['requests'] . ' requests'),
                    self::rate('returns', $pct($t['returned'], $t['requests']), $t['returned'] . ' of ' . $t['requests'] . ' requests'),
                    self::rate('turnaround', $avg, $avg === null ? 'no request was completed yet' : 'average of ' . $done->count() . ' completed'),
                    self::rate('waiting', $waiting, $waiting . ' ' . Str::plural('request', $waiting) . ' open for more than ' . self::WAITING_DAYS . ' days'),
                ];
                if ($d['prevTotals'] !== null) {
                    $rows[] = self::info('demand', self::changeText($t['requests'], $d['prevTotals']['requests'], 'requests', $d['summary']['versus']));
                }

                return $rows;
            })(),
        };

        return $rows;
    }

    /** A rated area: ✓ inside the first band, Medium inside the second, High past it. */
    private static function rate(string $key, float|int|null $value, string $context): array
    {
        [$area, $objective, [$direction, $ok, $medium, $unit]] = self::THRESHOLDS[$key];
        $valueText = $value === null ? 'No data' : rtrim(rtrim(number_format((float) $value, 1), '0'), '.') . $unit;

        if ($value === null) {
            return compact('key', 'area', 'objective', 'value', 'valueText') + ['result' => 'ok', 'priority' => null, 'basis' => 'Nothing to measure yet (' . $context . ').'];
        }

        $higher = $direction === 'higher';
        $priority = match (true) {
            $higher ? $value >= $ok : $value <= $ok => null,
            $higher ? $value >= $medium : $value <= $medium => 'Medium',
            default => 'High',
        };
        $rule = $priority === null
            ? ($higher ? 'at or above ' . $ok . $unit : ($ok === 0 ? 'none' : 'at or below ' . $ok . $unit)) . ' is on track'
            : ($priority === 'Medium'
                ? ($higher ? 'below ' . $ok . $unit : 'above ' . $ok . $unit) . ' needs attention'
                : ($higher ? 'below ' . $medium . $unit : 'above ' . $medium . $unit) . ' is high priority');

        return compact('key', 'area', 'objective', 'value', 'valueText') + [
            'result' => $priority === null ? 'ok' : 'attention',
            'priority' => $priority,
            'basis' => $valueText . ' (' . $context . '); ' . $rule . '.',
        ];
    }

    /** An informational area: always ✓, it only describes a change. */
    private static function info(string $key, array $change): array
    {
        [$area, $objective] = self::THRESHOLDS[$key];

        return ['key' => $key, 'area' => $area, 'objective' => $objective, 'value' => $change[0], 'valueText' => $change[1], 'result' => 'ok', 'priority' => null, 'basis' => $change[2]];
    }

    /** [value, short text, sentence] for a change against an earlier figure. */
    private static function changeText(int $now, int $before, string $noun, ?string $versus): array
    {
        if ($before === 0) {
            return [null, $now ? 'New' : 'No change', $now . ' ' . $noun . ' now; none in ' . ($versus ?? 'the earlier period') . '.'];
        }
        $c = (int) round(($now - $before) / $before * 100);

        return [$c, ($c > 0 ? '+' : '') . $c . '%', $now . ' ' . $noun . ' now against ' . $before . ' in ' . ($versus ?? 'the earlier period') . '.'];
    }

    /** The thresholds in words for the appendix: [area, on track, medium, high]. */
    public static function legend(string $tab): array
    {
        $keys = match ($tab) {
            'organizations' => ['participation', 'members_cleared', 'no_org'],
            'clearance' => ['cleared', 'not_tagged', 'uncleared'],
            default => ['completion', 'returns', 'turnaround', 'waiting'],
        };

        return array_map(function ($key) {
            [$area, , [$direction, $ok, $medium, $unit]] = self::THRESHOLDS[$key];
            if ($direction === 'higher') {
                return [$area, $ok . $unit . ' or more', $medium . $unit . ' to under ' . $ok . $unit, 'Under ' . $medium . $unit];
            }
            if ($medium === PHP_INT_MAX) {
                return [$area, $ok . $unit, 'Any', '—'];
            }

            if ($ok === 0) {
                return [$area, 'None', '1 to ' . $medium . $unit, 'Over ' . $medium . $unit];
            }

            return [$area, $ok . $unit . ' or less', 'Over ' . $ok . $unit . ' to ' . $medium . $unit, 'Over ' . $medium . $unit];
        }, $keys);
    }
}
