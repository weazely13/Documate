<?php

namespace App\Console\Commands;

use App\Models\Semester;
use App\Support\Audit;
use Illuminate\Console\Command;

/**
 * Keeps the current semester in step with the calendar.
 *
 * - Whichever dated semester contains today becomes the current one.
 * - Between semesters (no semester contains today) the current one stays as it is.
 * - A semester is closed out (never-tagged students marked Uncleared) once its end date has
 *   passed, and only once. Semesters nobody used, with no clearance records at all, are left
 *   alone, so adding an old term for the record never marks every student Uncleared for it.
 */
class SyncCurrentSemester extends Command
{
    protected $signature = 'semesters:sync-current';

    protected $description = 'Make the semester that contains today the current one, and close out semesters that have ended.';

    public function handle(): int
    {
        $current = Semester::current();
        $target = Semester::forDate(today());

        if ($target && $target->id !== $current?->id) {
            $previous = $target->makeCurrent(onlyCloseEndedSemester: true);
            $message = 'Current semester set to ' . $target->label() . ' by its dates (' . $target->dateLabel() . ')'
                . ($previous ? ', replacing ' . $previous->label() : '');
            Audit::record('updated', 'Semesters & Verification', $message, subject: $target);
            $this->info($message . '.');
        } elseif (! $target) {
            $this->info('No semester covers today; keeping ' . ($current?->label() ?? 'no current semester') . '.');
        } else {
            $this->info($current->label() . ' is already current.');
        }

        $ended = Semester::query()
            ->where('is_current', false)
            ->whereNull('finalized_at')
            ->whereNotNull('ends_on')
            ->whereDate('ends_on', '<', today())
            ->whereHas('clearanceStatuses')
            ->get();

        foreach ($ended as $semester) {
            $semester->finalizeClearance();
            Audit::record('updated', 'Semesters & Verification', 'Closed out ' . $semester->label() . ' after it ended on ' . $semester->ends_on->format('M j, Y') . '; students never tagged were marked Uncleared', subject: $semester);
            $this->info('Closed out ' . $semester->label() . '.');
        }

        return self::SUCCESS;
    }
}
