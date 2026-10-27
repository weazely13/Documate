<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Workflow\Documents\DocumentRegistry;

/**
 * Numbers behind the dashboard charts: a metric's daily trend against the period before it,
 * documents by type, and when submissions come in. Each takes a query, so the admin dashboard
 * passes everyone's records and the student dashboard passes only the student's.
 */
final class DashboardInsights
{
    public const RANGES = [7, 30, 90];

    /** When a document reached the office: its submission, or its creation for paper forms. */
    public const SUBMITTED_AT = 'COALESCE(student_document_workspaces.submitted_at, student_document_workspaces.created_at)';

    private const DAY_NAMES = ['Sundays', 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays'];

    private const DAY_SHORT = ['Su', 'M', 'Tu', 'W', 'Th', 'F', 'Sa'];

    /** Three-hour windows, starting at midnight. */
    private const WINDOW_LABELS = ['12a', '3a', '6a', '9a', '12p', '3p', '6p', '9p'];

    public static function range(int $days): int
    {
        return in_array($days, self::RANGES, true) ? $days : 30;
    }

    /**
     * One headline number with its daily series over the last $days days, and the change
     * against the $days before that.
     *
     * @param  string  $at  column or SQL expression holding the event time
     * @return array{key: string, label: string, total: int, previous: int, delta: ?float, series: list<array{d: string, v: int}>}
     */
    public static function metric(string $key, string $label, Builder $query, string $at, int $days): array
    {
        return self::metricBetween($key, $label, $query, $at, today()->subDays($days - 1), today());
    }

    /**
     * The same, for any date range. The series is per day up to about three months,
     * per week up to about a year, and per month beyond that.
     */
    public static function metricBetween(string $key, string $label, Builder $query, string $at, Carbon $from, Carbon $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();
        $days = (int) $start->diffInDays($end) + 1;
        $previousStart = $start->copy()->subDays($days);

        $times = (clone $query)
            ->whereRaw("{$at} >= ?", [$previousStart])
            ->whereRaw("{$at} <= ?", [$end])
            ->toBase()
            ->selectRaw("{$at} as event_at")
            ->pluck('event_at')
            ->map(fn ($t) => Carbon::parse($t));

        [$unit, $format] = match (true) {
            $days <= 92 => ['day', 'M j'],
            $days <= 400 => ['week', 'M j'],
            default => ['month', 'M Y'],
        };
        $bucket = fn (Carbon $t) => $t->copy()->startOf($unit)->toDateString();

        $current = $times->filter(fn (Carbon $t) => $t->gte($start));
        $counts = $current->countBy($bucket);
        $total = $current->count();
        $previous = $times->count() - $total;

        $series = [];
        for ($point = $start->copy()->startOf($unit); $point->lte($end); $point->add(1, $unit)) {
            $series[] = ['d' => ($unit === 'week' ? 'Week of ' : '') . $point->format($format), 'v' => (int) ($counts[$point->toDateString()] ?? 0)];
        }

        return [
            'key' => $key,
            'label' => $label,
            'total' => $total,
            'previous' => $previous,
            'delta' => $previous > 0 ? round(($total - $previous) / $previous * 100, 1) : null,
            'series' => $series,
        ];
    }

    /**
     * Documents per form, split into completed and still moving. Rejected and voided ones are left out.
     *
     * @return list<array{label: string, completed: int, open: int, total: int}>
     */
    public static function byType(Builder $workspaces, int $limit = 6): array
    {
        // Built-in forms are named by their registry entry; paper forms by their template.
        return (clone $workspaces)
            ->leftJoin('templates', 'templates.template_id', '=', 'student_document_workspaces.template_id')
            ->toBase()
            ->get([DB::raw('templates.name as template_name'), 'student_document_workspaces.document_type',
                'student_document_workspaces.workflow_status', 'student_document_workspaces.status'])
            ->reject(fn ($r) => in_array($r->workflow_status, ['rejected', 'voided'], true))
            ->groupBy(fn ($r) => DocumentRegistry::find($r->document_type)?->name()
                ?? $r->template_name
                ?? ($r->document_type ? Str::headline($r->document_type) : 'Other'))
            ->map(function (Collection $rows, string $name) {
                $completed = $rows->filter(fn ($r) => $r->workflow_status === 'completed'
                    || ($r->workflow_status === null && $r->status === 'completed'))->count();

                return ['label' => $name, 'completed' => $completed, 'open' => $rows->count() - $completed, 'total' => $rows->count()];
            })
            ->sortByDesc('total')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * Share of submissions by the student's college and by their program.
     *
     * @return array{colleges: list<array{label: string, count: int, pct: float}>, programs: list<array{label: string, count: int, pct: float}>}
     */
    public static function origins(Builder $workspaces, int $limit = 6): array
    {
        $rows = (clone $workspaces)
            ->join('users', 'users.id', '=', 'student_document_workspaces.user_id')
            ->leftJoin('programs', 'programs.id', '=', 'users.program_id')
            ->leftJoin('colleges', 'colleges.id', '=', 'programs.college_id')
            ->toBase()
            ->get([DB::raw('colleges.code as college'), DB::raw('programs.name as program')]);

        $share = fn (string $field) => $rows
            ->countBy(fn ($r) => $r->{$field} ?: 'Not set')
            ->sortDesc()
            ->take($limit)
            ->map(fn ($n, $label) => ['label' => $label, 'count' => $n, 'pct' => round($n / max(1, $rows->count()) * 100, 1)])
            ->values()
            ->all();

        return ['colleges' => $share('college'), 'programs' => $share('program')];
    }

    /**
     * Submissions by weekday and three-hour window over the last $days days, with the busiest windows.
     *
     * @return array{days: list<array{short: string, name: string, bars: list<int>}>, labels: list<string>, top: list<array{day: string, window: string, count: int}>, total: int, timezone: string}
     */
    public static function activeTimes(Builder $workspaces, int $days): array
    {
        return self::activeTimesBetween($workspaces, today()->subDays($days - 1), today());
    }

    public static function activeTimesBetween(Builder $workspaces, Carbon $from, Carbon $to): array
    {
        $grid = array_fill(0, 7, array_fill(0, 8, 0));

        (clone $workspaces)
            ->whereRaw(self::SUBMITTED_AT . ' >= ?', [$from->copy()->startOfDay()])
            ->whereRaw(self::SUBMITTED_AT . ' <= ?', [$to->copy()->endOfDay()])
            ->toBase()
            ->selectRaw(self::SUBMITTED_AT . ' as event_at')
            ->pluck('event_at')
            ->each(function ($t) use (&$grid) {
                $t = Carbon::parse($t);
                $grid[$t->dayOfWeek][intdiv($t->hour, 3)]++;
            });

        $top = collect($grid)
            ->flatMap(fn ($bars, $day) => collect($bars)->map(fn ($n, $w) => ['day' => $day, 'w' => $w, 'count' => $n]))
            ->filter(fn ($c) => $c['count'] > 0)
            ->sortByDesc('count')
            ->take(3)
            ->map(fn ($c) => [
                'day' => self::DAY_NAMES[$c['day']],
                'window' => sprintf('%d:00 – %d:00', $c['w'] * 3, $c['w'] * 3 + 3),
                'count' => $c['count'],
            ])
            ->values()
            ->all();

        return [
            'days' => collect($grid)->map(fn ($bars, $day) => ['short' => self::DAY_SHORT[$day], 'name' => self::DAY_NAMES[$day], 'bars' => $bars])->all(),
            'labels' => self::WINDOW_LABELS,
            'top' => $top,
            'total' => (int) collect($grid)->flatten()->sum(),
            'timezone' => config('app.timezone') . ' (GMT' . now()->format('P') . ')',
        ];
    }
}
