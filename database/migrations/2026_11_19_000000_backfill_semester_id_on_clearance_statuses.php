<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Clearance records tagged before `semester_id` existed (2026_08_12) were never linked to their semester: they only
 * carry the old `academic_year` / `semester` text. Everything that reads clearance by semester (Clearance Monitoring,
 * the admin dashboard, nav counts, end-of-semester finalization) skipped them, and the student's Clearance Status
 * page and the clearance profiles showed them as a second card for the same semester, often with a stale status.
 *
 * This links each such record to the semester with the same school year and term. Records whose semester no longer
 * exists stay unlinked and keep showing under their own label, as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        $semesters = DB::table('semesters')->get(['id', 'school_year', 'semester_label']);

        foreach ($semesters as $semester) {
            DB::table('clearance_statuses')
                ->whereNull('semester_id')
                ->where('academic_year', $semester->school_year)
                ->where('semester', $semester->semester_label)
                ->update(['semester_id' => $semester->id]);
        }
    }

    public function down(): void
    {
        // Not reversible: after this runs, a backfilled link looks the same as one made when the record was tagged.
    }
};
