<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ties each semester to the calendar. Nullable so semesters created before this
     * keep working; the Semesters page asks the admin to fill their dates in.
     */
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->date('starts_on')->nullable()->after('semester_label');
            $table->date('ends_on')->nullable()->after('starts_on');
            // When the semester was closed out (never-tagged students marked Uncleared). Done once.
            $table->timestamp('finalized_at')->nullable()->after('is_current');
        });

        // Semesters older than the current one were closed out when the admin switched away from
        // them, so mark them done; the daily job must never close them out a second time.
        $order = fn ($s) => $s->school_year . '-' . (['First' => 1, 'Second' => 2, 'Summer' => 3][$s->semester_label] ?? 9);
        $all = DB::table('semesters')->get(['id', 'school_year', 'semester_label', 'is_current']);
        $current = $all->firstWhere('is_current', true);
        if ($current) {
            $older = $all->filter(fn ($s) => $order($s) < $order($current))->pluck('id');
            DB::table('semesters')->whereIn('id', $older)->update(['finalized_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['starts_on', 'ends_on', 'finalized_at']);
        });
    }
};
