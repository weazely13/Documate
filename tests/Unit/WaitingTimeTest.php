<?php

namespace Tests\Unit;

use App\Support\WaitingTime;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class WaitingTimeTest extends TestCase
{
    public function test_school_days_skip_sundays(): void
    {
        // Saturday noon to the next Tuesday noon: Sunday is skipped, Monday and Tuesday count.
        $since = Carbon::parse('2026-10-24 12:00');
        $now = Carbon::parse('2026-10-27 12:00');

        $this->assertSame(2, WaitingTime::schoolDays($since, $now));
    }

    public function test_levels_follow_the_waiting_days(): void
    {
        $this->assertNull(WaitingTime::level(0));
        $this->assertSame('due', WaitingTime::level(1));
        $this->assertSame('due', WaitingTime::level(2));
        $this->assertSame('follow_up', WaitingTime::level(3));
        $this->assertSame('urgent', WaitingTime::level(5));
        $this->assertSame('overdue', WaitingTime::level(7));
        $this->assertSame('overdue', WaitingTime::level(30));
    }

    public function test_overdue_cutoff_matches_school_day_count(): void
    {
        $now = Carbon::parse('2026-10-27 12:00');
        $cutoff = WaitingTime::overdueCutoff($now);

        $this->assertSame(7, WaitingTime::schoolDays($cutoff, $now));
        $this->assertSame(6, WaitingTime::schoolDays($cutoff->copy()->addDay(), $now));
    }

    public function test_text_shows_level_and_age(): void
    {
        $now = Carbon::parse('2026-10-27 12:00');

        $this->assertSame('Overdue · 8 days', WaitingTime::describe($now->copy()->subDays(9), now: $now)['text']);
        $thursday = Carbon::parse('2026-10-29 12:00'); // Monday to Thursday, no Sunday in between
        $this->assertSame('Follow up · 3 days', WaitingTime::describe($thursday->copy()->subDays(3), now: $thursday)['text']);
        $this->assertSame('2 days', WaitingTime::describe($now->copy()->subDays(3), now: $now)['text']); // crosses a Sunday
        $this->assertSame('5 hours', WaitingTime::describe($now->copy()->subHours(5), now: $now)['text']);
    }

    public function test_plain_age_when_not_flagged(): void
    {
        $now = Carbon::parse('2026-10-27 12:00');
        $info = WaitingTime::describe($now->copy()->subDays(10), flag: false, now: $now);

        $this->assertNull($info['level']);
        $this->assertSame('8 days', $info['text']); // 10 calendar days, two of them Sundays
    }
}
