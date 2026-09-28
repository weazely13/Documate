<?php

namespace App\Services\Admin;

use App\Support\Charts\SvgChart;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Builds every number and chart shown on the Admin Reports page, and reused
 * verbatim by the PDF and Excel exports so all three surfaces always agree.
 *
 * Deliberately queries tables directly via DB::table() (matching the
 * migrations 1:1) instead of going through Eloquent models, so this module
 * has no dependency on model relationships/casts defined elsewhere in the
 * app and can't drift out of sync with them.
 */
class AdminReportService
{
    /**
     * @param array{date_from?:string|null, date_to?:string|null, academic_year?:string|null, semester?:string|null} $filters
     */
        public function build(array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        return [
            'filters'      => $filters,
            'generated_at' => now(),
            'overview'     => $this->overview($filters),
            'users'        => $this->usersReport($filters),
            'transactions' => $this->transactionsReport($filters),
            'appointments' => $this->appointmentsReport($filters),
            'clearance'    => $this->clearanceReport($filters),
            'verification' => $this->verificationReport($filters),
            'logbook'      => $this->logbookReport($filters),
        ];
    }

    public function normalizeFilters(array $filters): array
    {
        return [
            'date_from'     => $filters['date_from'] ?? null,
            'date_to'       => $filters['date_to'] ?? null,
            'academic_year' => $filters['academic_year'] ?? null,
            'semester'      => $filters['semester'] ?? null,
        ];
    }

    /** Distinct academic years available across the tables that track it, for the filter dropdown. */
    public function availableAcademicYears(): array
    {
        return DB::table('settings')->select('academic_year')
            ->union(DB::table('clearance_statuses')->select('academic_year'))
            ->union(DB::table('student_verifications')->select('academic_year'))
            ->distinct()
            ->pluck('academic_year')
            ->filter()
            ->sortDesc()
            ->values()
            ->all();
    }

    public function availableSemesters(): array
    {
        return DB::table('settings')->select('current_semester as semester')
            ->union(DB::table('clearance_statuses')->select('semester'))
            ->union(DB::table('student_verifications')->select('semester'))
            ->distinct()
            ->pluck('semester')
            ->filter()
            ->values()
            ->all();
    }

    // ------------------------------------------------------------------
    // OVERVIEW
    // ------------------------------------------------------------------

    protected function overview(array $filters): array
    {
        $usersQ = $this->dateScoped(DB::table('users'), $filters, 'users.created_at');
        $workspacesQ = $this->dateScoped(DB::table('student_document_workspaces'), $filters, 'created_at');
        $appointmentsQ = $this->dateScoped(DB::table('appointments'), $filters, 'created_at');
        $clearanceQ = $this->semesterScoped(DB::table('clearance_statuses'), $filters);
        $verificationQ = $this->semesterScoped(DB::table('student_verifications'), $filters);

        $totalStudents = (clone $usersQ)->whereIn('role_id', $this->roleIds(['Student', 'Officer']))->count();
        $activeAccounts = (clone $usersQ)->where('account_status', 'active')->count();
        $pendingVerification = (clone $verificationQ)->where('status', 'pending')->count();
        $totalTransactions = $workspacesQ->count();
        $completedTransactions = (clone $workspacesQ)->where('status', 'completed')->count();
        $totalAppointments = $appointmentsQ->count();
        $todaysAppointments = DB::table('appointments')->whereDate('appointment_date', today())->count();
        $clearedCount = (clone $clearanceQ)->where('status', 'Cleared')->count();
        $clearanceTotal = (clone $clearanceQ)->count();

        $registrationTrend = $this->monthlyTrend('users', $filters);
        $appointmentTrend = $this->monthlyTrend('appointments', $filters);

        // NOTE: previously queried DB::table('users') directly here with no
        // date scoping at all, so this pie chart silently ignored the date
        // range chips while every other Overview number respected them.
        // Scope it off the same $usersQ the cards above already use.
        $usersByRole = (clone $usersQ)
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->select('roles.role_name', DB::raw('count(*) as total'))
            ->groupBy('roles.role_name')
            ->pluck('total', 'role_name')
            ->all();

        return [
            'cards' => [
                'total_students'         => $totalStudents,
                'active_accounts'        => $activeAccounts,
                'pending_verifications'  => $pendingVerification,
                'total_transactions'     => $totalTransactions,
                'completed_transactions' => $completedTransactions,
                'total_appointments'     => $totalAppointments,
                'todays_appointments'    => $todaysAppointments,
                'clearance_rate'         => $clearanceTotal > 0 ? round(($clearedCount / $clearanceTotal) * 100) : 0,
            ],
            'charts' => [
                'users_by_role'        => $this->chartData($usersByRole),
                'registration_trend'   => $registrationTrend,
                'appointment_trend'    => $appointmentTrend,
            ],
        ];
    }

    // ------------------------------------------------------------------
    // USERS
    // ------------------------------------------------------------------

    protected function usersReport(array $filters): array
    {
        $q = $this->dateScoped(DB::table('users'), $filters, 'created_at');

        $byStatus = (clone $q)
            ->select('account_status', DB::raw('count(*) as total'))
            ->groupBy('account_status')
            ->pluck('total', 'account_status')
            ->all();

        $byRole = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->select('roles.role_name', DB::raw('count(*) as total'))
            ->groupBy('roles.role_name')
            ->pluck('total', 'role_name')
            ->all();

        $byProgramQuery = DB::table('users')
            ->join('programs', 'programs.id', '=', 'users.program_id')
            ->select('programs.name as program', DB::raw('count(*) as total'));

        $byProgram = $this->dateScoped($byProgramQuery, $filters, 'users.created_at')
            ->whereNotNull('users.program_id')
            ->groupBy('programs.id', 'programs.name')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'program')
            ->all();

        $byYearLevel = (clone $q)
            ->select('year_level', DB::raw('count(*) as total'))
            ->whereNotNull('year_level')
            ->groupBy('year_level')
            ->orderBy('year_level')
            ->pluck('total', 'year_level')
            ->all();

        return [
            'total'          => (clone $q)->count(),
            'by_status'      => $byStatus,
            'by_role'        => $byRole,
            'by_program'     => $byProgram,
            'by_year_level'  => $byYearLevel,
            'charts' => [
                'by_status'     => $this->chartData($byStatus),
                'by_role'       => $this->chartData($byRole),
                'by_program'    => $this->chartData($byProgram),
                'by_year_level' => $this->chartData($byYearLevel),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // TRANSACTIONS (document workspaces)
    // ------------------------------------------------------------------

    protected function transactionsReport(array $filters): array
    {
        $q = $this->dateScoped(DB::table('student_document_workspaces'), $filters, 'created_at');

        $byStatus = (clone $q)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status')->all();

        $byAnalysisStatus = (clone $q)->select('analysis_status', DB::raw('count(*) as total'))
            ->groupBy('analysis_status')->pluck('total', 'analysis_status')->all();

        $byTemplate = DB::table('student_document_workspaces as w')
            ->join('templates as t', 't.template_id', '=', 'w.template_id')
            ->select('t.name', DB::raw('count(*) as total'));
        $byTemplate = $this->dateScoped($byTemplate, $filters, 'w.created_at')
            ->groupBy('t.name')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'name')
            ->all();

        $avgCompletionHours = (clone $q)
            ->whereNotNull('completed_at')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, created_at, completed_at)) as avg_hours'))
            ->value('avg_hours');

        return [
            'total'                  => (clone $q)->count(),
            'completed'              => $byStatus['completed'] ?? 0,
            'pending'                => $byStatus['pending'] ?? 0,
            'avg_completion_hours'   => $avgCompletionHours ? round((float) $avgCompletionHours, 1) : null,
            'by_status'              => $byStatus,
            'by_analysis_status'     => $byAnalysisStatus,
            'by_template'            => $byTemplate,
            'charts' => [
                'by_status'   => $this->chartData($byStatus),
                'by_template' => $this->chartData($byTemplate),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // APPOINTMENTS
    // ------------------------------------------------------------------

    protected function appointmentsReport(array $filters): array
    {
        $q = $this->dateScoped(DB::table('appointments'), $filters, 'created_at');

        $byStatus = (clone $q)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status')->all();

        $bySession = (clone $q)->select('session', DB::raw('count(*) as total'))
            ->groupBy('session')->pluck('total', 'session')->all();

        $total = array_sum($byStatus);
        $missed = $byStatus['missed'] ?? 0;
        $attended = $byStatus['attended'] ?? 0;
        $resolved = $missed + $attended;

        $perDay = (clone $q)
            ->select('appointment_date', DB::raw('count(*) as total'))
            ->groupBy('appointment_date')
            ->orderBy('appointment_date')
            ->get()
            ->map(fn ($row) => [
                'label' => Carbon::parse($row->appointment_date)->format('M d'),
                'value' => (int) $row->total,
            ])->values()->all();

        $rescheduleCount = DB::table('appointment_reschedules')
            ->when($filters['date_from'], fn ($q2) => $q2->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q2) => $q2->whereDate('created_at', '<=', $filters['date_to']))
            ->count();

        return [
            'total'             => $total,
            'missed_rate'       => $resolved > 0 ? round(($missed / $resolved) * 100) : 0,
            'reschedule_count'  => $rescheduleCount,
            'by_status'         => $byStatus,
            'by_session'        => $bySession,
            'per_day'           => $perDay,
            'charts' => [
                'by_status'  => $this->chartData($byStatus),
                'by_session' => $this->chartData($bySession),
                'per_day'    => [
                    'labels' => array_column($perDay, 'label'),
                    'values' => array_column($perDay, 'value'),
                ],
            ],
        ];
    }

    // ------------------------------------------------------------------
    // CLEARANCE
    // ------------------------------------------------------------------

    protected function clearanceReport(array $filters): array
    {
        $q = $this->semesterScoped(DB::table('clearance_statuses'), $filters);

        $byStatus = (clone $q)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status')->all();

        $byOrganization = (clone $q)
            ->select('organization', 'status', DB::raw('count(*) as total'))
            ->whereNotNull('organization')
            ->groupBy('organization', 'status')
            ->get()
            ->groupBy('organization')
            ->map(fn ($rows) => $rows->pluck('total', 'status')->all())
            ->all();

        $total = array_sum($byStatus);
        $cleared = $byStatus['Cleared'] ?? 0;

        return [
            'total'            => $total,
            'clearance_rate'   => $total > 0 ? round(($cleared / $total) * 100) : 0,
            'by_status'        => $byStatus,
            'by_organization'  => $byOrganization,
            'charts' => [
                'by_status' => $this->chartData($byStatus),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // VERIFICATION (e-slip)
    // ------------------------------------------------------------------

    protected function verificationReport(array $filters): array
    {
        $q = $this->semesterScoped(DB::table('student_verifications'), $filters);

        $byStatus = (clone $q)->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status')->all();

        $total = array_sum($byStatus);
        $verified = $byStatus['verified'] ?? 0;

        return [
            'total'           => $total,
            'verified_rate'   => $total > 0 ? round(($verified / $total) * 100) : 0,
            'by_status'       => $byStatus,
            'charts' => [
                'by_status' => $this->chartData($byStatus),
            ],
        ];
    }

    // ------------------------------------------------------------------
    // LOGBOOK
    // ------------------------------------------------------------------

    /**
     * Logbook entries are pasted-in paper-logbook records with free-text
     * dates that have nothing to do with the report's date range / academic
     * year / semester filters, so this section always reflects every
     * logbook ever uploaded — it deliberately ignores $filters entirely.
     */
    protected function logbookReport(array $filters): array
    {
        $entries = DB::table('logbook_entries as e')
            ->select('e.purpose', 'e.program', 'e.entry_date', 'e.logbook_upload_id')
            ->get()
            ->map(fn ($row) => [
                'purpose'   => $row->purpose,
                'program'   => $row->program,
                'date'      => $this->parseLogbookEntryDate($row->entry_date),
                'upload_id' => $row->logbook_upload_id,
            ])
            ->values();

        $byPurpose = $entries->pluck('purpose')->filter(fn ($p) => filled($p))
            ->countBy()->sortDesc()->take(10)->all();

        $byProgram = $entries->pluck('program')->filter(fn ($p) => filled($p))
            ->countBy()->sortDesc()->take(10)->all();

        $monthly = [];
        foreach ($entries as $row) {
            if (! $row['date']) {
                continue;
            }
            $ym = $row['date']->format('Y-m');
            $monthly[$ym] = ($monthly[$ym] ?? 0) + 1;
        }
        ksort($monthly);

        $monthlyLabeled = [];
        foreach ($monthly as $ym => $total) {
            $monthlyLabeled[Carbon::createFromFormat('Y-m', $ym)->format('M Y')] = $total;
        }

        return [
            'total_uploads' => DB::table('logbook_uploads')->count(),
            'total_entries' => $entries->count(),
            'by_purpose'    => $byPurpose,
            'by_program'    => $byProgram,
            'monthly'       => $monthlyLabeled,
            'charts' => [
                'by_purpose' => $this->chartData($byPurpose),
                'by_program' => $this->chartData($byProgram),
                'monthly'    => [
                    'labels' => array_keys($monthlyLabeled),
                    'values' => array_values($monthlyLabeled),
                ],
            ],
        ];
    }

    /** Same defensive parse as LogbookManager::parseEntryDate(). */
    protected function parseLogbookEntryDate(?string $raw): ?Carbon
    {
        if (blank($raw)) {
            return null;
        }
        try {
            return Carbon::parse($raw);
        } catch (Throwable $e) {
            return null;
        }
    }

    // ------------------------------------------------------------------
    // Chart rendering (used by the Blade view + PDF export)
    // ------------------------------------------------------------------

    public function renderBarChart(array $chartData, array $opts = []): string
    {
        return SvgChart::bar($chartData['labels'], $chartData['values'], $opts);
    }

    public function renderPieChart(array $chartData, array $opts = []): string
    {
        return SvgChart::pie($chartData['labels'], $chartData['values'], $opts);
    }

    public function renderLineChart(array $chartData, array $opts = []): string
    {
        return SvgChart::line($chartData['labels'], $chartData['values'], $opts);
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    protected function chartData(array $assoc): array
    {
        return [
            'labels' => array_map(fn ($k) => ucwords(str_replace('_', ' ', (string) $k)), array_keys($assoc)),
            'values' => array_values(array_map('intval', $assoc)),
        ];
    }

    protected function dateScoped($query, array $filters, string $column)
    {
        if ($filters['date_from']) {
            $query->whereDate($column, '>=', $filters['date_from']);
        }
        if ($filters['date_to']) {
            $query->whereDate($column, '<=', $filters['date_to']);
        }
        return $query;
    }

    protected function semesterScoped($query, array $filters)
    {
        if ($filters['academic_year']) {
            $query->where('academic_year', $filters['academic_year']);
        }
        if ($filters['semester']) {
            $query->where('semester', $filters['semester']);
        }
        return $query;
    }

    protected function monthlyTrend(string $table, array $filters, string $column = 'created_at'): array
    {
        $query = DB::table($table)
            ->select(DB::raw("DATE_FORMAT($column, '%Y-%m') as ym"), DB::raw('count(*) as total'))
            ->when($filters['date_from'], fn ($q) => $q->whereDate($column, '>=', $filters['date_from']))
            ->when($filters['date_to'], fn ($q) => $q->whereDate($column, '<=', $filters['date_to']))
            ->when(!$filters['date_from'] && !$filters['date_to'], fn ($q) => $q->where($column, '>=', now()->subMonths(6)))
            ->groupBy('ym')
            ->orderBy('ym')
            ->get();

        return [
            'labels' => $query->map(fn ($r) => Carbon::createFromFormat('Y-m', $r->ym)->format('M Y'))->all(),
            'values' => $query->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    protected function roleIds(array $roleNames): array
    {
        return DB::table('roles')->whereIn('role_name', $roleNames)->pluck('id')->all();
    }
}