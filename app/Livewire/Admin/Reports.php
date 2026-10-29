<?php

namespace App\Livewire\Admin;

use App\Enums\TransactionStatus;
use App\Exports\AdminReportSpreadsheet;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ClearanceStatus;
use App\Models\Organization;
use App\Models\SavedReport;
use App\Models\Semester;
use App\Models\StudentDocumentWorkspace;
use App\Models\StudentVerification;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Support\Audit;
use App\Support\ReportAssessment;
use Illuminate\Support\Facades\Cache;
use App\Support\DashboardInsights;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Services\Admin\AdminReportService;
use App\Services\Admin\ReportInsightsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Url;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\Workflow\WorkflowReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Student records three ways: who availed each transaction form, the students of each
 * organization, and every student's clearance for a semester. Each list can be downloaded.
 */
class Reports extends Component
{
    /** Only these three exist school-wide — never pulled from distinct DB values. */
    public const SEMESTERS = ['First Semester', 'Second Semester', 'Summer'];

    protected const PRESET_DAYS = ['7d' => 7, '28d' => 28, '30d' => 30, '90d' => 90, '180d' => 180, '365d' => 365];

    /**
     * Columns the admin can add to each report's table, in groups, like a metrics picker:
     * tab => [group => [key => label]]. The student column is always shown.
     */
    public const METRICS = [
        'forms' => [
            'Request' => ['form' => 'Form', 'code' => 'Transaction code', 'status' => 'Status', 'submitted' => 'Filed on', 'completed' => 'Completed on', 'days' => 'Days to complete'],
            'Student' => ['number' => 'Student number', 'program' => 'Program', 'organization' => 'Organization', 'year' => 'Year level'],
        ],
        'organizations' => [
            'Student' => ['number' => 'Student number', 'program' => 'Program', 'year' => 'Year level', 'organization' => 'Organization'],
            'Activity' => ['requests' => 'Requests', 'forms' => 'Forms requested', 'last_filed' => 'Last filed'],
            'Clearance' => ['clearance' => 'Clearance', 'by' => 'Tagged by', 'tagged' => 'Tagged on'],
        ],
        'clearance' => [
            'Student' => ['number' => 'Student number', 'program' => 'Program', 'year' => 'Year level', 'organization' => 'Organization'],
            'Clearance' => ['clearance' => 'Clearance', 'by' => 'Tagged by', 'tagged' => 'Tagged on', 'remarks' => 'Remarks'],
            'Activity' => ['requests' => 'Requests in period', 'last_filed' => 'Last filed'],
        ],
    ];

    public const DEFAULT_METRICS = [
        'forms' => ['program', 'organization', 'form', 'status', 'code', 'submitted'],
        'organizations' => ['number', 'organization', 'requests', 'forms', 'clearance'],
        'clearance' => ['number', 'organization', 'by', 'tagged', 'clearance'],
    ];

    /** Columns that hold numbers: right-aligned and totalled. */
    public const NUMERIC_METRICS = ['requests', 'days'];

    /** The two colours of a metric or item comparison (YouTube Studio's blue and purple). */
    public const COMPARE_COLOURS = ['#0ea5e9', '#9333ea'];

    /** Presets that follow the calendar instead of counting back from today. */
    protected const PRESET_CALENDAR = ['today', 'yesterday', 'month', 'lastmonth', 'year', 'lastyear'];

    protected const STUDENT_ROLES = ['Student', 'Officer'];

    public const PER_PAGE = 20;

    public const REPORTS = ['forms', 'organizations', 'clearance'];

    #[Url]
    public string $tab = 'forms';

    public string $datePreset = '180d';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $academicYear = null;
    public ?string $semester = null;

    /** Clearance (and the clearance columns of Organizations) are read for one semester. */
    public ?int $semesterId = null;

    public string $search = '';
    /** Selected transaction form (its group key), or null for every form. */
    public ?string $form = null;
    /** Selected organization (its name), or null for the overview of all organizations. */
    public ?string $org = null;
    /** Clearance list filter: Cleared, Pending, Uncleared, none (not tagged), or '' for all. */
    public string $status = '';
    public string $clearanceOrg = '';
    public int $page = 1;

    /** The columns shown in the table (keys of METRICS for the tab). */
    public array $metrics = [];

    public bool $showChart = true;
    /** line (over time) or bar (by form / organization). */
    public string $chartType = 'line';
    /** auto, day, week, month or year: how the line chart groups the period. */
    public string $interval = 'auto';
    /**
     * What the chart compares, as in YouTube Studio's "Select what to compare":
     * none; metric (two series against each other); item (two forms / organizations);
     * previous (period-over-period); lastyear (year-over-year); custom (chosen dates or semester).
     */
    public string $compare = 'none';
    /** metric: the two series keys; item: the two form keys / organization names. */
    public ?string $compareA = null;
    public ?string $compareB = null;
    /** custom: the dates compared with (forms and organizations) or the semester (clearance). */
    public ?string $compareFrom = null;
    public ?string $compareTo = null;
    public ?int $compareSemesterId = null;

    /** The saved report on screen, so the link can be bookmarked and opened again. */
    #[Url(as: 'saved')]
    public ?int $savedId = null;

    // PDF report: the admin reviews the AI-drafted wording, adds the sign-off, then downloads
    public bool $pdfPreviewOpen = false;
    /** @var array{ok?: bool, conclusion?: string[], context?: string[], details?: array<string, string>, actions?: string[]} */
    public array $pdfDraft = [];
    public string $pdfPreparedFor = '';
    public string $pdfNotedBy = '';
    public string $pdfNotedByPosition = '';
    public bool $pdfIncludeRecords = true;

    // AI insights side panel
    public bool $aiOpen = false;
    public string $aiTitle = '';
    public string $aiQuestion = '';
    /** @var array<int, array{role: string, text: string}> */
    public array $aiMessages = [];

    protected AdminReportService $service;

    public function boot(AdminReportService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $setting = function_exists('systemSetting') ? systemSetting() : null;

        $this->academicYear = $setting->academic_year ?? null;
        $this->semester = $setting->current_semester ?? null;
        $this->semesterId = Semester::current()?->id ?? Semester::query()->latest('id')->value('id');

        if (! in_array($this->tab, self::REPORTS, true)) {
            $this->tab = 'forms';
        }

        $this->metrics = self::DEFAULT_METRICS[$this->tab];
        $this->applyPreset('180d');

        if ($this->savedId) {
            $this->loadSavedReport($this->savedId);
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::REPORTS, true) ? $tab : 'forms';
        $this->metrics = self::DEFAULT_METRICS[$this->tab];
        if (in_array($this->compare, ['metric', 'item', 'custom'], true)) {
            $this->compare = 'none';
        }
        $this->search = '';
        $this->page = 1;
        $this->savedId = null;
    }

    // ------------------------------------------------------------------
    // Report setup: what a saved report or the spreadsheet tab carries
    // ------------------------------------------------------------------

    /** Everything that decides what the report shows. */
    public function state(): array
    {
        return [
            'tab' => $this->tab,
            'datePreset' => $this->datePreset,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'semesterId' => $this->semesterId,
            'form' => $this->form,
            'org' => $this->org,
            'status' => $this->status,
            'clearanceOrg' => $this->clearanceOrg,
            'search' => $this->search,
            'metrics' => array_values($this->metrics),
            'showChart' => $this->showChart,
            'chartType' => $this->chartType,
            'interval' => $this->interval,
            'compare' => $this->compare,
            'compareA' => $this->compareA,
            'compareB' => $this->compareB,
            'compareFrom' => $this->compareFrom,
            'compareTo' => $this->compareTo,
            'compareSemesterId' => $this->compareSemesterId,
        ];
    }

    /** Puts a stored or linked setup back, ignoring anything that is not valid any more. */
    public function applyState(array $s): void
    {
        $this->tab = in_array($s['tab'] ?? null, self::REPORTS, true) ? $s['tab'] : 'forms';
        $this->semesterId = isset($s['semesterId']) && Semester::whereKey((int) $s['semesterId'])->exists() ? (int) $s['semesterId'] : $this->semesterId;
        $this->form = filled($s['form'] ?? null) ? (string) $s['form'] : null;
        $this->org = filled($s['org'] ?? null) ? (string) $s['org'] : null;
        $this->status = in_array($s['status'] ?? '', ['Cleared', 'Pending', 'Uncleared', 'none'], true) ? $s['status'] : '';
        $this->clearanceOrg = (string) ($s['clearanceOrg'] ?? '');
        $this->search = Str::limit((string) ($s['search'] ?? ''), 100, '');
        isset($s['metrics']) ? $this->setMetrics((array) $s['metrics']) : $this->resetMetrics();
        $this->showChart = filter_var($s['showChart'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->chartType = in_array($s['chartType'] ?? '', ['line', 'bar'], true) ? $s['chartType'] : 'line';
        $this->interval = in_array($s['interval'] ?? '', ['auto', 'day', 'week', 'month', 'year'], true) ? $s['interval'] : 'auto';
        $this->applyComparison([
            'mode' => $s['compare'] ?? 'none',
            'a' => $s['compareA'] ?? null,
            'b' => $s['compareB'] ?? null,
            'from' => $s['compareFrom'] ?? null,
            'to' => $s['compareTo'] ?? null,
            'semester' => $s['compareSemesterId'] ?? null,
        ]);

        $preset = (string) ($s['datePreset'] ?? '180d');
        if ($preset === 'custom') {
            $this->datePreset = 'custom';
            $this->dateFrom = $this->validDate($s['dateFrom'] ?? null);
            $this->dateTo = $this->validDate($s['dateTo'] ?? null) ?? today()->toDateString();
        } else {
            $this->applyPreset($this->isValidPreset($preset) ? $preset : '180d');
        }
        $this->page = 1;
    }

    protected function validDate(mixed $value): ?string
    {
        try {
            return filled($value) ? Carbon::parse((string) $value)->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Picks the table's columns from the metrics panel; unknown keys are dropped. */
    public function setMetrics(array $keys): void
    {
        $allowed = collect(self::METRICS[$this->tab])->flatMap(fn ($group) => array_keys($group))->all();
        // Keep the catalogue order so columns don't jump around as they are ticked.
        $this->metrics = array_values(array_intersect($allowed, array_map('strval', $keys)));
    }

    public function resetMetrics(): void
    {
        $this->metrics = self::DEFAULT_METRICS[$this->tab];
    }

    // ------------------------------------------------------------------
    // Saved reports
    // ------------------------------------------------------------------

    public function saveReport(string $name): void
    {
        $name = Str::limit(trim(strip_tags($name)), 80, '');
        if ($name === '') {
            $this->addError('reportName', 'Give the report a name.');
            return;
        }
        if (SavedReport::where('user_id', auth()->id())->count() >= 50) {
            $this->addError('reportName', 'You can keep up to 50 saved reports. Delete one first.');
            return;
        }

        $report = SavedReport::create([
            'user_id' => auth()->id(),
            'name' => $name,
            'tab' => $this->tab,
            'settings' => $this->state(),
            'last_opened_at' => now(),
        ]);
        $this->savedId = $report->id;

        Audit::record('created', 'Reports', "Saved the report \"{$name}\"");
        $this->dispatch('report-saved');
        $this->dispatch('toast', type: 'success', title: 'Report saved', message: 'Open it again any time from the Report menu.');
    }

    /** Overwrites the open saved report with what is on screen now. */
    public function updateSavedReport(): void
    {
        $report = $this->ownSavedReport($this->savedId);
        if (! $report) {
            return;
        }

        $report->update(['tab' => $this->tab, 'settings' => $this->state()]);
        $this->dispatch('toast', type: 'success', title: 'Report updated', message: "“{$report->name}” now matches what is on screen.");
    }

    public function loadSavedReport(int $id): void
    {
        $report = $this->ownSavedReport($id);
        if (! $report) {
            $this->savedId = null;
            return;
        }

        $this->applyState($report->settings + ['tab' => $report->tab]);
        $this->savedId = $report->id;
        $report->forceFill(['last_opened_at' => now()])->save();
        $this->aiMessages = [];
        $this->aiTitle = '';
    }

    public function deleteSavedReport(int $id): void
    {
        $report = $this->ownSavedReport($id);
        if (! $report) {
            return;
        }

        $report->delete();
        if ($this->savedId === $id) {
            $this->savedId = null;
        }
        Audit::record('deleted', 'Reports', "Deleted the saved report \"{$report->name}\"");
    }

    protected function ownSavedReport(?int $id): ?SavedReport
    {
        return $id ? SavedReport::where('user_id', auth()->id())->find($id) : null;
    }

    // ------------------------------------------------------------------
    // AI insights
    // ------------------------------------------------------------------

    /** Ready-made questions shown as chips above the report. */
    public function aiSuggestions(): array
    {
        return match ($this->tab) {
            'organizations' => ['Summarize this report', 'Which organizations file the fewest forms?', 'Which organizations have the most uncleared members?'],
            'clearance' => ['Summarize clearance progress', 'Which organizations need follow-up?', 'How does this compare with last semester?'],
            default => ['Summarize this report', 'Which forms are returned most often?', 'How does this period compare with the one before?'],
        };
    }

    public function askAi(?string $question = null): void
    {
        $question = Str::limit(trim($question ?? $this->aiQuestion), 500, '');
        if ($question === '') {
            return;
        }

        $this->aiOpen = true;
        $this->aiQuestion = '';
        if (! $this->aiMessages) {
            $this->aiTitle = Str::limit($question, 48);
        }

        $key = 'report-ai:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 12)) {
            $this->aiMessages[] = ['role' => 'user', 'text' => $question];
            $this->aiMessages[] = ['role' => 'model', 'text' => 'That is a lot of questions in a short time. Please wait ' . RateLimiter::availableIn($key) . ' seconds and ask again.', 'error' => true];
            return;
        }
        RateLimiter::hit($key, 60);

        $history = $this->aiMessages;
        $this->aiMessages[] = ['role' => 'user', 'text' => $question];

        $answer = app(ReportInsightsService::class)->ask($question, $this->insightContext(), array_filter($history, fn ($m) => empty($m['error'])));
        $this->aiMessages[] = ['role' => 'model', 'text' => $answer['text'], 'error' => ! $answer['ok']];
        $this->dispatch('ai-scroll');
    }

    public function newAiChat(): void
    {
        $this->aiMessages = [];
        $this->aiTitle = '';
        $this->aiQuestion = '';
    }

    /**
     * The figures the AI may read: totals, comparisons, the per-form / per-organization summary
     * and the trend. Aggregates only — no student names or numbers leave the system.
     */
    protected function insightContext(): array
    {
        [$from, $to] = $this->period();
        $forms = $this->formRecords();
        $students = $this->clearanceRecords();
        $prev = $this->previousPeriod();
        $prevForms = $prev ? $this->formRecords($prev) : collect();
        $totals = fn (Collection $rows) => [
            'requests' => $rows->count(),
            'students_who_filed' => $rows->pluck('user_id')->unique()->count(),
            'completed' => $rows->where('state', 'completed')->count(),
            'returned_rejected_or_voided' => $rows->whereIn('state', ['rejected', 'voided'])->count(),
        ];
        $trend = $this->trend($forms, $students);
        $prevSemester = $this->comparisonSemester();

        return [
            'report' => ['forms' => 'Transaction forms', 'organizations' => 'Organizations', 'clearance' => 'Clearance'][$this->tab],
            'period' => ['name' => $this->periodName(), 'from' => $from->toDateString(), 'to' => $to->toDateString()],
            'compared_with' => $prev ? ['from' => $prev[0]->toDateString(), 'to' => $prev[1]->toDateString()] : null,
            'filters' => array_filter([
                'form' => $this->form ? ($forms->firstWhere('key', $this->form)['form'] ?? $this->form) : null,
                'organization' => $this->org ?? ($this->clearanceOrg ?: null),
                'clearance_status' => $this->status ?: null,
                'search' => $this->search ?: null,
            ]),
            'forms_this_period' => $totals($forms),
            'forms_previous_period' => $prev ? $totals($prevForms) : null,
            'by_form' => $this->formSummary($forms)->take(15)->map(fn ($f) => [
                'form' => $f['label'], 'requests' => $f['requests'], 'students' => $f['students'],
                'completed' => $f['completed'], 'returned' => $f['returned'],
            ])->all(),
            'semester' => Semester::find($this->semesterId)?->label(),
            'active_students' => $students->count(),
            'clearance' => $students->countBy('status')->all(),
            'clearance_previous_semester' => $prevSemester ? ['semester' => $prevSemester->label(), 'counts' => $this->clearanceRecords($prevSemester->id)->countBy('status')->all()] : null,
            'by_organization' => $this->organizationSummary($students, $forms)->take(20)->all(),
            'trend' => [
                'unit' => $trend['unit'],
                'labels' => array_column($trend['buckets'], 'label'),
                'series' => collect($trend['series'])->mapWithKeys(fn ($s) => [$s[1] => $s[3]])->all(),
            ],
        ];
    }

    public function pickForm(?string $key): void
    {
        $this->form = $this->form === $key ? null : $key;
        $this->page = 1;
    }

    public function pickOrg(?string $name): void
    {
        $this->org = $name;
        $this->search = '';
        $this->page = 1;
    }

    public function pickStatus(string $status): void
    {
        $this->status = $this->status === $status ? '' : $status;
        $this->page = 1;
    }

    public function clearClearanceFilters(): void
    {
        $this->status = '';
        $this->clearanceOrg = '';
        $this->page = 1;
    }

    public function goToPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    /**
     * Quick date-range chip (7D / 30D / 90D / 6M / 1Y / All). Selecting one
     * clears any custom range and instantly refreshes the lists.
     */
    public function setDatePreset(string $preset): void
    {
        $this->applyPreset($preset);
        $this->page = 1;
    }

    protected function applyPreset(string $preset): void
    {
        $this->datePreset = $preset;

        if ($preset === 'all') {
            $this->dateFrom = null;
            $this->dateTo = now()->toDateString();
            return;
        }

        if ($preset === 'custom') {
            return; // dateFrom/dateTo are driven by the custom inputs instead
        }

        // A calendar year ("year-2025") or month ("month-2026-09"), cut off at today.
        if (preg_match('/^year-(\d{4})$/', $preset, $m)) {
            $start = Carbon::create((int) $m[1])->startOfYear();
            $this->dateFrom = $start->toDateString();
            $this->dateTo = $start->copy()->endOfYear()->min(today())->toDateString();
            return;
        }
        if (preg_match('/^month-(\d{4})-(\d{2})$/', $preset, $m)) {
            $start = Carbon::create((int) $m[1], (int) $m[2])->startOfMonth();
            $this->dateFrom = $start->toDateString();
            $this->dateTo = $start->copy()->endOfMonth()->min(today())->toDateString();
            return;
        }

        if ($semester = $this->presetSemester($preset)) {
            $this->semesterId = $semester->id;
            $this->dateFrom = $semester->starts_on->toDateString();
            $this->dateTo = $semester->ends_on->min(today())->toDateString();
            return;
        }

        if (in_array($preset, self::PRESET_CALENDAR, true)) {
            [$from, $to] = match ($preset) {
                'today' => [today(), today()],
                'yesterday' => [today()->subDay(), today()->subDay()],
                'month' => [today()->startOfMonth(), today()],
                'lastmonth' => [today()->subMonthNoOverflow()->startOfMonth(), today()->subMonthNoOverflow()->endOfMonth()],
                'year' => [today()->startOfYear(), today()],
                'lastyear' => [today()->subYear()->startOfYear(), today()->subYear()->endOfYear()],
            };
            $this->dateFrom = $from->toDateString();
            $this->dateTo = $to->toDateString();
            return;
        }

        $days = self::PRESET_DAYS[$preset] ?? 180;
        $this->dateTo = now()->toDateString();
        $this->dateFrom = now()->subDays($days - 1)->toDateString();
    }

    public function useCustomRange(): void
    {
        $this->datePreset = 'custom';
    }

    /** "Apply" in the custom range panel of the period dropdown. */
    public function applyCustomRange(?string $from, ?string $to): void
    {
        $from = $this->validDate($from);
        $to = $this->validDate($to) ?? today()->toDateString();
        if ($from && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->datePreset = 'custom';
        $this->dateFrom = $from;
        $this->dateTo = $to;
        $this->page = 1;
    }

    protected function isValidPreset(string $preset): bool
    {
        return array_key_exists($preset, self::PRESET_DAYS)
            || in_array($preset, ['all', ...self::PRESET_CALENDAR], true)
            || preg_match('/^(year-\d{4}|month-\d{4}-(0[1-9]|1[0-2]))$/', $preset)
            || $this->presetSemester($preset) !== null;
    }

    /** The period's name as the dropdown shows it ("Last 28 days", "2025", "September 2026" …). */
    public function periodName(): string
    {
        $names = [
            'today' => 'Today', 'yesterday' => 'Yesterday', '7d' => 'Last 7 days', '28d' => 'Last 28 days', '30d' => 'Last 30 days',
            'month' => 'This month', 'lastmonth' => 'Last month', '90d' => 'Last 90 days', '180d' => 'Last 180 days',
            'year' => 'This year', 'lastyear' => 'Last year', '365d' => 'Last 365 days', 'all' => 'Lifetime', 'custom' => 'Custom',
        ];

        return match (true) {
            isset($names[$this->datePreset]) => $names[$this->datePreset],
            str_starts_with($this->datePreset, 'year-') => substr($this->datePreset, 5),
            str_starts_with($this->datePreset, 'month-') => Carbon::parse(substr($this->datePreset, 6) . '-01')->format('F Y'),
            ($s = $this->presetSemester($this->datePreset)) !== null => $s->school_year . ' ' . $s->semester_label . ' Semester',
            default => 'Selected period',
        };
    }

    public function resetFilters(): void
    {
        $this->applyPreset('180d');
        $this->semesterId = Semester::current()?->id ?? $this->semesterId;
        $this->search = '';
        $this->form = null;
        $this->status = '';
        $this->clearanceOrg = '';
        $this->interval = 'auto';
        $this->page = 1;
    }

    /** Typing a date switches the range to "Custom"; any filter change starts the list over. */
    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo'], true)) {
            $this->datePreset = 'custom';
        }

        if ($propertyName === 'datePreset') {
            $valid = $this->datePreset === 'custom' || $this->isValidPreset($this->datePreset);
            $this->applyPreset($valid ? $this->datePreset : '180d');
        }

        if ($propertyName === 'chartType' && ! in_array($this->chartType, ['line', 'bar'], true)) {
            $this->chartType = 'line';
        }
        if ($propertyName === 'interval' && ! in_array($this->interval, ['auto', 'day', 'week', 'month', 'year'], true)) {
            $this->interval = 'auto';
        }
        if ($propertyName === 'compare' && ! in_array($this->compare, ['none', 'metric', 'item', 'previous', 'lastyear', 'custom'], true)) {
            $this->compare = 'none';
        }

        // While the period is a semester, picking another semester moves the period with it.
        if ($propertyName === 'semesterId' && str_starts_with($this->datePreset, 'sem-')) {
            $semester = Semester::find($this->semesterId);
            $this->applyPreset($semester?->isDated() && $semester->hasStarted() ? 'sem-' . $semester->id : '180d');
        }

        // Choosing another report from the dropdown starts it fresh.
        if ($propertyName === 'tab') {
            $this->setTab($this->tab);
        }

        if ($propertyName !== 'page') {
            $this->page = 1;
        }
    }

    // ------------------------------------------------------------------
    // Records
    // ------------------------------------------------------------------

    /** Active students and officers: the people these records are about. */
    protected function students(): Builder
    {
        return User::query()
            ->where('account_status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', self::STUDENT_ROLES));
    }

    /** Every form a student submitted in the period, one row per request. */
    protected function formRecords(?array $range = null): Collection
    {
        [$from, $to] = $range ?? $this->period();

        return $this->submittedDocuments()
            ->whereRaw(DashboardInsights::SUBMITTED_AT . ' >= ?', [$from->copy()->startOfDay()])
            ->whereRaw(DashboardInsights::SUBMITTED_AT . ' <= ?', [$to->copy()->endOfDay()])
            ->with(['user.program', 'user.organization', 'template'])
            ->orderByRaw(DashboardInsights::SUBMITTED_AT . ' desc')
            ->get()
            ->map(function (StudentDocumentWorkspace $w) {
                $paperless = $w->workflow_status !== null;
                $state = $paperless ? $w->workflow_status->value : ((string) $w->status ?: 'pending');

                return [
                    'key' => $w->document_type ?: 'paper-' . $w->template_id,
                    'form' => DocumentRegistry::find($w->document_type)?->name()
                        ?? $w->template?->name
                        ?? ($w->document_type ? Str::headline($w->document_type) : 'Other'),
                    'user_id' => $w->user_id,
                    'student' => ($w->user?->full_name ?? '') ?: 'Unknown student',
                    'avatar' => $w->user?->avatarUrl(),
                    'number' => $w->user?->student_number ?: '—',
                    'program' => $w->user?->program?->name ?: '—',
                    'organization' => $w->user?->organization?->name ?: '—',
                    'year' => $w->user?->year_level,
                    'days' => $w->completed_at && ($w->submitted_at ?? $w->created_at)
                        ? round(($w->submitted_at ?? $w->created_at)->diffInHours($w->completed_at) / 24, 1)
                        : null,
                    'code' =>$w->transaction_code ?: '#' . str_pad((string) $w->workspace_id, 4, '0', STR_PAD_LEFT),
                    'state' => $state,
                    'status' => $paperless ? $w->workflow_status->label() : Str::headline($state),
                    'submitted' => $w->submitted_at ?? $w->created_at,
                    'completed' => $w->completed_at,
                    'url' => $paperless
                        ? route('admin.workflow.show', $w->workspace_id)
                        : route('admin.transactions.show', $w->workspace_id),
                ];
            });
    }

    /** One line per form: how many requests, how many different students, how many finished. */
    protected function formSummary(Collection $records): Collection
    {
        return $records->groupBy('key')
            ->map(fn (Collection $rows, string $key) => [
                'key' => $key,
                'label' => $rows->first()['form'],
                'requests' => $rows->count(),
                'students' => $rows->pluck('user_id')->unique()->count(),
                'completed' => $rows->where('state', 'completed')->count(),
                'returned' => $rows->whereIn('state', ['rejected', 'voided'])->count(),
            ])
            ->sortByDesc('requests')
            ->values();
    }

    /** Each student's latest clearance record for the chosen semester, keyed by student. */
    protected function latestClearance(?int $semesterId = null): Collection
    {
        $semesterId ??= $this->semesterId;
        if (! $semesterId) {
            return collect();
        }

        return ClearanceStatus::query()
            ->where('semester_id', $semesterId)
            ->with('taggedByUser:id,first_name,middle_name,last_name,suffix')
            ->orderBy('id')
            ->get()
            ->keyBy('user_id');
    }

    /** Every active student with their clearance for the semester ("Not tagged" when none). */
    protected function clearanceRecords(?int $semesterId = null): Collection
    {
        $latest = $this->latestClearance($semesterId);

        return $this->students()
            ->with(['program', 'organization'])
            ->orderBy('last_name')->orderBy('first_name')
            ->get()
            ->map(function (User $u) use ($latest) {
                $r = $latest->get($u->id);

                return [
                    'user_id' => $u->id,
                    'student' => $u->full_name ?: $u->email,
                    'avatar' => $u->avatarUrl(),
                    'number' => $u->student_number ?: '—',
                    'program' => $u->program?->name ?: '—',
                    'year' => $u->year_level,
                    // Named the way Clearance Monitoring names it: the student's organization, else the record's.
                    'organization' => $u->organization?->name ?: ($r?->organization ?: 'No organization'),
                    'status' => $r?->status ?? 'Not tagged',
                    'by' => $r?->taggedByUser?->full_name,
                    'tagged' => $r ? ($r->tagged_at ?? $r->updated_at) : null,
                    'remarks' => $r?->remarks,
                    'url' => route('admin.clearance-monitoring.show', $u->id),
                ];
            });
    }

    /** One line per organization: its students, what they requested in the period, and their clearance. */
    protected function organizationSummary(Collection $students, Collection $forms): Collection
    {
        $requestsBy = $forms->groupBy('user_id');

        return $students->groupBy('organization')
            ->map(function (Collection $rows, string $name) use ($requestsBy) {
                $ids = $rows->pluck('user_id');
                $requests = $ids->sum(fn ($id) => $requestsBy->get($id)?->count() ?? 0);

                return [
                    'label' => $name,
                    'members' => $rows->count(),
                    'availed' => $ids->filter(fn ($id) => $requestsBy->has($id))->count(),
                    'requests' => $requests,
                    'Cleared' => $rows->where('status', 'Cleared')->count(),
                    'Pending' => $rows->where('status', 'Pending')->count(),
                    'Uncleared' => $rows->where('status', 'Uncleared')->count(),
                    'Not tagged' => $rows->where('status', 'Not tagged')->count(),
                ];
            })
            // Real organizations first by size; students without one at the end.
            ->sortBy([fn ($a, $b) => ($a['label'] === 'No organization') <=> ($b['label'] === 'No organization'), ['members', 'desc']])
            ->values();
    }

    protected function matchesSearch(array $row, array $fields): bool
    {
        $term = Str::lower(trim($this->search));

        return $term === '' || Str::contains(Str::lower(implode(' ', array_map(fn ($f) => (string) ($row[$f] ?? ''), $fields))), $term);
    }

    /** The rows the current tab lists, filtered but not paged. */
    protected function listedRows(Collection $forms, Collection $students): Collection
    {
        $byStudent = $forms->groupBy('user_id');
        $withActivity = fn ($s) => $s + [
            'requests' => $byStudent->get($s['user_id'])?->count() ?? 0,
            'forms' => $byStudent->get($s['user_id'])?->pluck('form')->unique()->values()->all() ?? [],
            'last_filed' => $byStudent->get($s['user_id'])?->max('submitted'),
        ];

        return match ($this->tab) {
            'organizations' => $students
                ->when($this->org !== null, fn ($c) => $c->where('organization', $this->org))
                ->map($withActivity)
                ->filter(fn ($r) => $this->matchesSearch($r, ['student', 'number', 'program', 'organization']))
                ->values(),
            'clearance' => $students
                ->when($this->status !== '', fn ($c) => $c->where('status', $this->status === 'none' ? 'Not tagged' : $this->status))
                ->when($this->clearanceOrg !== '', fn ($c) => $c->where('organization', $this->clearanceOrg))
                ->map($withActivity)
                ->filter(fn ($r) => $this->matchesSearch($r, ['student', 'number', 'program', 'organization', 'by']))
                ->values(),
            default => $forms
                ->when($this->form !== null, fn ($c) => $c->where('key', $this->form))
                ->filter(fn ($r) => $this->matchesSearch($r, ['student', 'number', 'program', 'organization', 'code', 'form']))
                ->values(),
        };
    }

    /** Columns that sit right after the student, before the rest in the order they were picked. */
    public const LEADING_METRICS = [
        'forms' => ['form', 'code'],
        'organizations' => [],
        'clearance' => ['clearance'],
    ];

    /** The chosen columns as the table shows them: the report's leading ones first. */
    public function orderedMetrics(): array
    {
        $lead = array_values(array_intersect(self::LEADING_METRICS[$this->tab], $this->metrics));

        return [...$lead, ...array_values(array_diff($this->metrics, $lead))];
    }

    /** Every metric of the tab as [key => label]. */
    public function metricLabels(): array
    {
        return collect(self::METRICS[$this->tab])->flatMap(fn ($group) => $group)->all();
    }

    /** One cell as plain text or a number, the way the CSV and the spreadsheet tab show it. */
    public function columnValue(array $r, string $key): string|int|float|null
    {
        return match ($key) {
            'status' => $r['status'],
            'clearance' => $r['status'],
            'submitted', 'completed' => $r[$key]?->format('Y-m-d'),
            'tagged', 'last_filed' => $r[$key]?->format('Y-m-d'),
            'forms' => implode('; ', $r['forms'] ?? []),
            'year' => $r['year'] ?: null,
            'remarks', 'by' => $r[$key] ?: null,
            default => $r[$key] ?? null,
        };
    }

    /**
     * The table on screen with its chosen columns: [title, header, rows of plain values, totals].
     * Only the ticked rows when keys are given (transaction codes for forms, student ids otherwise).
     */
    public function table(array $keys = []): array
    {
        $rows = $this->listedRows($this->formRecords(), $this->clearanceRecords());
        if ($keys) {
            $keys = array_map('strval', $keys);
            $rows = $rows->filter(fn ($r) => in_array((string) ($r['code'] ?? $r['user_id']), $keys, true))->values();
        }
        $term = Semester::find($this->semesterId)?->label() ?? 'no semester';
        [$from, $to] = $this->period();
        $range = $from->format('M j, Y') . ' – ' . $to->format('M j, Y');

        $title = match ($this->tab) {
            'organizations' => 'Students of ' . ($this->org ?? 'every organization') . ', ' . $range,
            'clearance' => 'Clearance, ' . $term,
            default => ($this->form ? $rows->first()['form'] ?? 'Form' : 'All forms') . ', ' . $range,
        };
        $labels = $this->metricLabels();
        $columns = $this->orderedMetrics();
        $head = ['Student', ...array_map(fn ($k) => $labels[$k], $columns)];
        $body = $rows->map(fn ($r) => [$r['student'], ...array_map(fn ($k) => $this->columnValue($r, $k), $columns)])->all();
        $totals = ['Total (' . $rows->count() . ' ' . Str::plural('row', $rows->count()) . ')', ...array_map(fn ($k) => match ($k) {
            'requests' => $rows->sum('requests'),
            'days' => ($d = $rows->whereNotNull('days')->avg('days')) !== null ? round($d, 1) . ' avg' : null,
            default => null,
        }, $columns)];

        return ['title' => $title, 'head' => $head, 'rows' => $body, 'totals' => $totals, 'count' => $rows->count()];
    }

    /** Downloads the table on screen (every page of it, with its chosen columns) as a CSV that Excel opens. */
    public function downloadList(array $keys = []): StreamedResponse
    {
        $table = $this->table($keys);
        $count = $table['count'];

        Audit::record('exported', 'Reports', "Downloaded {$table['title']} as CSV ({$count} " . ($keys ? 'selected ' : '') . Str::plural('row', $count) . ')');

        $filename = 'documate-' . Str::slug(Str::limit($table['title'], 60, '')) . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(fn () => self::writeCsv($table), $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Writes a table() to the output as UTF-8 CSV, totals first like the table on screen. */
    public static function writeCsv(array $table): void
    {
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // so Excel reads UTF-8
        fputcsv($out, $table['head'], ',', '"', '');
        fputcsv($out, $table['totals'], ',', '"', '');
        foreach ($table['rows'] as $row) {
            fputcsv($out, $row, ',', '"', '');
        }
        fclose($out);
    }

    /** Link to the table as a spreadsheet in a new browser tab. */
    public function sheetUrl(): string
    {
        return route('admin.reports.sheet', array_filter($this->state(), fn ($v) => $v !== null && $v !== '' && $v !== []));
    }

    // ------------------------------------------------------------------
    // Full report downloads (PDF and Excel)
    // ------------------------------------------------------------------

    protected function filters(): array
    {
        return [
            'date_from'     => $this->dateFrom,
            'date_to'       => $this->dateTo,
            'academic_year' => $this->academicYear ?: null,
            'semester'      => $this->semester ?: null,
        ];
    }

    /** Everything both downloads contain: the page's numbers plus the records behind them. */
    protected function exportData(): array
    {
        $data = $this->withWorkflow($this->service->build($this->filters()));
        $data['insights'] = $this->insights();
        [$from, $to] = [$data['insights']['from'], $data['insights']['to']];

        $data['period'] = [
            'range' => $from->format('M j, Y') . ' – ' . $to->format('M j, Y'),
            'term' => ($this->semester ?: 'All semesters') . ', ' . ($this->academicYear ? 'AY ' . $this->academicYear : 'all academic years'),
        ];

        $name = fn ($u) => $u ? trim($u->last_name . ', ' . $u->first_name, ', ') : '—';

        $data['register'] = $this->submittedDocuments()
            ->whereRaw(DashboardInsights::SUBMITTED_AT . ' >= ?', [$from->copy()->startOfDay()])
            ->whereRaw(DashboardInsights::SUBMITTED_AT . ' <= ?', [$to->copy()->endOfDay()])
            ->with(['user.program', 'template'])
            ->orderByRaw(DashboardInsights::SUBMITTED_AT . ' desc')
            ->get()
            ->map(function (StudentDocumentWorkspace $w) use ($name) {
                $submitted = $w->submitted_at ?? $w->created_at;

                return [
                    'code' => $w->transaction_code ?: '#' . $w->workspace_id,
                    'document' => DocumentRegistry::find($w->document_type)?->name() ?? $w->template?->name ?? 'Document',
                    'student' => $name($w->user),
                    'student_number' => $w->user?->student_number ?: '—',
                    'program' => $w->user?->program?->name ?: '—',
                    'status' => $w->workflow_status?->label() ?? ucfirst((string) ($w->status ?: 'pending')),
                    'submitted' => $submitted,
                    'completed' => $w->completed_at,
                    'days' => $w->completed_at && $submitted ? round($submitted->diffInHours($w->completed_at) / 24, 1) : null,
                ];
            })
            ->all();

        $data['appointment_list'] = Appointment::with('user')
            ->when($this->dateFrom, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->whereDate('created_at', '<=', $to)
            ->orderBy('appointment_date')->orderBy('session')->orderBy('queue_number')
            ->get()
            ->map(fn (Appointment $a) => [
                'date' => $a->appointment_date,
                'session' => ucfirst((string) $a->session),
                'queue' => $a->queue_number,
                'student' => $name($a->user),
                'student_number' => $a->user?->student_number ?: '—',
                'purpose' => $a->purpose ?: '—',
                'status' => ucfirst((string) $a->status),
                'reschedules' => (int) $a->reschedule_count,
            ])
            ->all();

        return $data;
    }

    /** The key areas of the open report and how each is doing, by ReportAssessment's fixed rules. */
    public function assessment(): array
    {
        return ReportAssessment::for($this->tab, $this->viewData(), $this->formRecords());
    }

    /** "Download → PDF": draft the report's wording with the AI (cached per report setup) and open the review panel. */
    public function openPdfPreview(bool $fresh = false): void
    {
        $this->pdfPreviewOpen = true;
        $cacheKey = 'report-draft:' . auth()->id() . ':' . md5(json_encode($this->state()));

        if (! $fresh && ($cached = Cache::get($cacheKey))) {
            $this->pdfDraft = $cached;
            return;
        }

        $assessment = $this->assessment();
        $limiter = 'report-draft:' . auth()->id();
        if (RateLimiter::tooManyAttempts($limiter, 6)) {
            // too many drafts in a minute: plain sentences now, the AI again later
            $this->pdfDraft = app(ReportInsightsService::class)->fallbackDraft($this->insightContext(), $assessment);
            return;
        }
        RateLimiter::hit($limiter, 60);

        $this->pdfDraft = app(ReportInsightsService::class)->draftReport($this->insightContext(), $assessment);
        // only an AI draft is worth keeping; a fallback should try the AI again next time
        if ($this->pdfDraft['ok'] ?? false) {
            Cache::put($cacheKey, $this->pdfDraft, now()->addMinutes(15));
        } else {
            Cache::forget($cacheKey);
        }
    }

    public function regeneratePdfDraft(): void
    {
        $this->openPdfPreview(fresh: true);
    }

    /** Adds or removes a bullet in the conclusion, context or actions list of the draft. */
    public function addDraftLine(string $section): void
    {
        if (in_array($section, ['conclusion', 'context', 'actions'], true) && count($this->pdfDraft[$section] ?? []) < 6) {
            $this->pdfDraft[$section][] = '';
        }
    }

    public function removeDraftLine(string $section, int $i): void
    {
        if (in_array($section, ['conclusion', 'context', 'actions'], true)) {
            unset($this->pdfDraft[$section][$i]);
            $this->pdfDraft[$section] = array_values($this->pdfDraft[$section]);
        }
    }

    /** The reviewed draft, cleaned: plain text, sensible lengths, empty bullets dropped. */
    protected function cleanDraft(array $assessment): array
    {
        $line = fn ($v) => Str::limit(trim(strip_tags((string) $v)), 400, '…');
        $list = fn ($v) => array_values(array_slice(array_filter(array_map($line, (array) $v), fn ($x) => $x !== ''), 0, 6));
        $draft = $this->pdfDraft ?: app(ReportInsightsService::class)->fallbackDraft($this->insightContext(), $assessment);
        $details = [];
        foreach ($assessment as $a) {
            $text = $line($draft['details'][$a['key']] ?? '');
            $details[$a['key']] = $text !== '' ? $text : $a['basis'];
        }

        return [
            'ai' => (bool) ($draft['ok'] ?? false),
            'conclusion' => $list($draft['conclusion'] ?? []),
            'context' => $list($draft['context'] ?? []),
            'details' => $details,
            'actions' => $list($draft['actions'] ?? []),
        ];
    }

    /** "Download PDF" in the review panel. */
    public function downloadPdf()
    {
        $this->pdfPreparedFor = Str::limit(trim(strip_tags($this->pdfPreparedFor)), 120, '');
        $this->pdfNotedBy = Str::limit(trim(strip_tags($this->pdfNotedBy)), 120, '');
        $this->pdfNotedByPosition = Str::limit(trim(strip_tags($this->pdfNotedByPosition)), 120, '');

        return $this->exportPdf();
    }

    /** Most records the PDF prints; the CSV and spreadsheet carry every row. */
    public const PDF_ROW_CAP = 300;

    /**
     * The printable report: the Reports page as it stands on screen (report, period, filters,
     * comparison, chart, Metrics columns), and nothing else.
     */
    public function exportPdf()
    {
        $bytes = $this->pdfBytes();

        Audit::record('exported', 'Reports', 'Downloaded the ' . ['forms' => 'Transaction forms', 'organizations' => 'Organizations', 'clearance' => 'Clearance'][$this->tab] . ' report as PDF (' . $this->periodName() . ')');

        $filename = 'documate-' . $this->tab . '-report-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn () => print ($bytes),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    public function exportExcel()
    {
        $spreadsheet = (new AdminReportSpreadsheet())->build($this->exportData());

        $filename = 'documate-report-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * The PDF's typeface, as in the sample report: Calibri. Licensed copies placed in storage/fonts
     * win, then the system's own (Windows). On Linux servers Carlito is used — a free font with the
     * same letter widths as Calibri (Debian's fonts-crosextra-carlito), so pages lay out the same.
     * Null when neither is found; the PDF then uses DejaVu Sans.
     *
     * @return array{name: string, regular: string, bold: string, italic: string, bolditalic?: string}|null
     */
    public static function pdfFonts(): ?array
    {
        $sets = [
            'Calibri' => [[storage_path('fonts'), 'C:/Windows/Fonts'], ['regular' => 'calibri.ttf', 'bold' => 'calibrib.ttf', 'italic' => 'calibrii.ttf', 'bolditalic' => 'calibriz.ttf']],
            'Carlito' => [[storage_path('fonts'), '/usr/share/fonts/truetype/crosextra'], ['regular' => 'Carlito-Regular.ttf', 'bold' => 'Carlito-Bold.ttf', 'italic' => 'Carlito-Italic.ttf', 'bolditalic' => 'Carlito-BoldItalic.ttf']],
        ];

        foreach ($sets as $name => [$dirs, $files]) {
            foreach ($dirs as $dir) {
                $found = array_map(fn ($f) => is_file($dir . '/' . $f) ? $dir . '/' . $f : null, $files);
                if ($found['regular'] && $found['bold'] && $found['italic']) {
                    return ['name' => $name] + array_filter($found);
                }
            }
        }

        return null;
    }

    /** The PDF file: A4, rendered, with "Page X of Y" stamped in the footer of every page. */
    public function pdfBytes(): string
    {
        $fonts = self::pdfFonts();
        // dompdf keeps the fonts it loads here; it may only read files inside its chroot
        if (! is_dir(storage_path('fonts'))) {
            @mkdir(storage_path('fonts'), 0775, true);
        }
        $dompdf = Pdf::loadView('exports.admin-report-pdf', $this->pdfData() + ['fonts' => $fonts])
            ->setPaper('a4', 'portrait')
            ->setOption([
                'defaultFont' => $fonts ? 'ReportSans' : 'DejaVu Sans',
                'dpi' => 96,
                'isFontSubsettingEnabled' => true,
                'chroot' => array_values(array_unique(array_filter([realpath(base_path()), $fonts ? dirname($fonts['regular']) : null]))),
            ])
            ->getDomPDF();
        $dompdf->render();

        // The total is only known after rendering, so the page count is drawn on the canvas.
        // Units are points; the number sits about 10 mm above the paper's edge.
        $mm = 72 / 25.4;
        $dompdf->getCanvas()->page_script(function (int $page, int $pages, $canvas, $fonts) use ($mm) {
            // centred under the footer title in grey italics, as the sample report numbers its pages
            $font = $fonts->getFont('ReportSans', 'italic') ?? $fonts->getFont('DejaVu Sans', 'italic');
            $text = "Page {$page} of {$pages}";
            $size = 9;
            $canvas->text(($canvas->get_width() - $fonts->getTextWidth($text, $font, $size)) / 2, $canvas->get_height() - 10 * $mm, $text, $font, $size, [0.5, 0.5, 0.5]);
        });

        return $dompdf->output();
    }

    /** What the PDF view needs: the page's figures, the key areas, the reviewed wording, the table and the sign-off. */
    public function pdfData(): array
    {
        $page = $this->viewData();
        $table = $this->table();
        $saved = $this->ownSavedReport($this->savedId);
        $assessment = ReportAssessment::for($this->tab, $page, $this->formRecords());

        return [
            'page' => $page,
            'assessment' => $assessment,
            'legend' => ReportAssessment::legend($this->tab),
            'draft' => $this->cleanDraft($assessment),
            'signoff' => [
                'preparedFor' => $this->pdfPreparedFor,
                'notedBy' => $this->pdfNotedBy,
                'notedByPosition' => $this->pdfNotedByPosition,
            ],
            'includeRecords' => $this->pdfIncludeRecords,
            'state' => $this->state(),
            'table' => $table + ['shown' => array_slice($table['rows'], 0, self::PDF_ROW_CAP)],
            'meta' => [
                'tab' => $this->tab,
                'title' => $saved?->name ?? ['forms' => 'Transaction forms report', 'organizations' => 'Organizations report', 'clearance' => 'Clearance report'][$this->tab],
                'saved' => (bool) $saved,
                'periodName' => $this->periodName(),
                'compare' => $this->compare,
                'compareA' => $this->compareA,
                'compareB' => $this->compareB,
                'showChart' => $this->showChart,
                'chartType' => $this->chartType,
                'search' => $this->search,
                'preparedBy' => auth()->user()?->full_name ?: 'Administrator',
                'generatedAt' => now(),
                'rowCap' => self::PDF_ROW_CAP,
                'metrics' => $this->orderedMetrics(),
                'numeric' => self::NUMERIC_METRICS,
            ],
        ];
    }

    protected function withWorkflow(array $data): array
    {
        $data['workflow'] = app(WorkflowReportService::class)->build([
            'from' => $this->dateFrom,
            'to' => $this->dateTo,
        ]);

        return $data;
    }

    /** The selected range as dates. "All" starts at the first document ever filed. */
    protected function period(): array
    {
        $to = $this->dateTo ? Carbon::parse($this->dateTo) : today();
        $first = StudentDocumentWorkspace::min('created_at');
        $from = $this->dateFrom ? Carbon::parse($this->dateFrom) : ($first ? Carbon::parse($first) : $to->copy()->subYear());

        return $from->gt($to) ? [$to, $from] : [$from, $to];
    }

    /** Documents that reached the office (drafts never did). */
    protected function submittedDocuments(): Builder
    {
        return StudentDocumentWorkspace::query()->where(fn ($q) => $q
            ->whereNull('student_document_workspaces.workflow_status')
            ->orWhere('student_document_workspaces.workflow_status', '!=', 'draft'));
    }

    protected function insights(): array
    {
        [$from, $to] = $this->period();
        $inRange = $this->submittedDocuments()
            ->whereRaw(DashboardInsights::SUBMITTED_AT . ' >= ?', [$from->copy()->startOfDay()])
            ->whereRaw(DashboardInsights::SUBMITTED_AT . ' <= ?', [$to->copy()->endOfDay()]);
        $students = User::whereHas('role', fn ($q) => $q->whereIn('role_name', ['Student', 'Officer']));

        return [
            'from' => $from,
            'to' => $to,
            'trend' => [
                DashboardInsights::metricBetween('submitted', 'Submitted', $this->submittedDocuments(), DashboardInsights::SUBMITTED_AT, $from, $to),
                DashboardInsights::metricBetween('completed', 'Completed', StudentDocumentWorkspace::query(), 'student_document_workspaces.completed_at', $from, $to),
                DashboardInsights::metricBetween('appointments', 'Appointments booked', Appointment::query(), 'appointments.created_at', $from, $to),
                DashboardInsights::metricBetween('accounts', 'New accounts', $students, 'users.created_at', $from, $to),
            ],
            'visits' => [
                DashboardInsights::metricBetween('visits', 'Visits scheduled', Appointment::whereNotIn('status', ['retracted', 'rejected']), 'appointments.appointment_date', $from, $to),
            ],
            'byType' => DashboardInsights::byType($inRange, 10),
            'times' => DashboardInsights::activeTimesBetween($this->submittedDocuments(), $from, $to),
        ];
    }

    /** The dated, already-started semester a "sem-<id>" period stands for. */
    protected function presetSemester(string $preset): ?Semester
    {
        if (! str_starts_with($preset, 'sem-')) {
            return null;
        }
        $semester = Semester::find((int) substr($preset, 4));

        return $semester?->isDated() && $semester->hasStarted() ? $semester : null;
    }

    /** "Add" in the Select-what-to-compare dialog. Anything incomplete falls back to no comparison. */
    public function applyComparison(array $c): void
    {
        $mode = in_array($c['mode'] ?? '', ['none', 'metric', 'item', 'previous', 'lastyear', 'custom'], true) ? $c['mode'] : 'none';
        $a = filled($c['a'] ?? null) ? (string) $c['a'] : null;
        $b = filled($c['b'] ?? null) ? (string) $c['b'] : null;

        if ($mode === 'metric') {
            $keys = array_keys($this->seriesOptions());
            if (! in_array($a, $keys, true) || ! in_array($b, $keys, true) || $a === $b) {
                $mode = 'none';
            }
        }
        if ($mode === 'item' && (! $a || ! $b || $a === $b)) {
            $mode = 'none';
        }

        $this->compare = $mode;
        $this->compareA = in_array($mode, ['metric', 'item'], true) ? $a : null;
        $this->compareB = in_array($mode, ['metric', 'item'], true) ? $b : null;

        if ($mode === 'custom') {
            if ($this->tab === 'clearance') {
                $id = (int) ($c['semester'] ?? 0);
                $this->compareSemesterId = $id && $id !== $this->semesterId && Semester::whereKey($id)->exists() ? $id : null;
                $this->compare = $this->compareSemesterId ? 'custom' : 'none';
            } else {
                $from = $this->validDate($c['from'] ?? null);
                $to = $this->validDate($c['to'] ?? null);
                if ($from && $to && $from > $to) {
                    [$from, $to] = [$to, $from];
                }
                [$this->compareFrom, $this->compareTo] = [$from, $to];
                $this->compare = $from && $to ? 'custom' : 'none';
            }
        }
    }

    public function removeComparison(): void
    {
        $this->applyComparison(['mode' => 'none']);
    }

    /** The series of the open report that can be compared with each other: key => label. */
    public function seriesOptions(): array
    {
        return match ($this->tab) {
            'organizations' => ['requests' => 'Requests filed', 'filers' => 'Students who filed'],
            'clearance' => ['cleared' => 'Cleared', 'pending' => 'Pending', 'uncleared' => 'Uncleared'],
            default => ['filed' => 'Filed', 'completed' => 'Completed', 'returned' => 'Returned'],
        };
    }

    /** Two series of the trend against each other, each on its own scale. */
    protected function metricTrend(array $trend): array
    {
        $byKey = collect($trend['series'])->keyBy(0);
        $a = $byKey->get($this->compareA);
        $b = $byKey->get($this->compareB);
        if (! $a || ! $b) {
            return $trend;
        }

        return ['unit' => $trend['unit'], 'buckets' => $trend['buckets'], 'axes' => 'dual', 'series' => [
            [$a[0], $a[1], self::COMPARE_COLOURS[0], $a[3]],
            [$b[0], $b[1], self::COMPARE_COLOURS[1], $b[3]],
        ]];
    }

    /** The main series (filed / requests / cleared) of two forms or organizations, side by side. */
    protected function itemTrend(Collection $forms, Collection $students, array $trend, array $labels): array
    {
        $keep = [$this->form, $this->org, $this->clearanceOrg];
        $series = [];
        foreach ([$this->compareA, $this->compareB] as $i => $item) {
            match ($this->tab) {
                'organizations' => $this->org = $item,
                'clearance' => $this->clearanceOrg = (string) $item,
                default => $this->form = $item,
            };
            $one = $this->trend($forms, $students, null, null, $trend['unit']);
            $series[] = ['item' . $i, $labels[$item] ?? (string) $item, self::COMPARE_COLOURS[$i], $one['series'][0][3]];
        }
        [$this->form, $this->org, $this->clearanceOrg] = $keep;

        return ['unit' => $trend['unit'], 'buckets' => $trend['buckets'], 'series' => $series];
    }

    /**
     * The semester clearance is compared with: the one a year before the chosen semester when
     * "last year" is the comparison, otherwise the one just before it.
     */
    protected function comparisonSemester(?string $mode = null): ?Semester
    {
        $semester = $this->semesterId ? Semester::find($this->semesterId) : null;
        if (! $semester) {
            return null;
        }

        if (($mode ?? $this->compare) === 'custom') {
            return $this->compareSemesterId ? Semester::find($this->compareSemesterId) : null;
        }

        if (($mode ?? $this->compare) === 'lastyear') {
            if (! $semester->isDated()) {
                return null;
            }
            $target = $semester->starts_on->copy()->subYear();

            return Semester::query()->whereNotNull('starts_on')->whereNotNull('ends_on')
                ->whereBetween('starts_on', [$target->copy()->subDays(75), $target->copy()->addDays(75)])
                ->get()
                ->sortBy(fn (Semester $s) => abs($s->starts_on->diffInDays($target)))
                ->first();
        }

        return Semester::query()->where('id', '<', $semester->id)->orderByDesc('id')->first();
    }

    /** What the "Add comparison" menu offers: mode => [name, dates or semester], or null when there is nothing to compare with. */
    public function comparisonOptions(): array
    {
        $options = [];
        foreach (['previous' => 'Previous period', 'lastyear' => 'Same period last year'] as $mode => $name) {
            if ($this->tab === 'clearance') {
                $s = $this->comparisonSemester($mode);
                $options[$mode] = $s ? [$mode === 'previous' ? 'Previous semester' : 'Same semester last year', $s->label()] : null;
            } else {
                $r = $this->previousPeriod($mode);
                $options[$mode] = $r ? [$name, $r[0]->format('M j, Y') . ' – ' . $r[1]->format('M j, Y')] : null;
            }
        }

        return $options;
    }

    /** The dated semester just before the given one, for semester-to-semester comparisons. */
    protected function semesterBefore(Semester $semester): ?Semester
    {
        return Semester::query()->whereNotNull('starts_on')->whereNotNull('ends_on')
            ->whereDate('ends_on', '<', $semester->starts_on)
            ->orderByDesc('ends_on')
            ->first();
    }

    /**
     * The dates the period is compared with: the same dates a year earlier when "last year" is
     * the comparison, otherwise the same number of days just before. Null for "All".
     */
    protected function previousPeriod(?string $mode = null): ?array
    {
        if ($this->datePreset === 'all') {
            return null;
        }

        if (($mode ?? $this->compare) === 'custom') {
            return $this->compareFrom && $this->compareTo ? [Carbon::parse($this->compareFrom), Carbon::parse($this->compareTo)] : null;
        }

        if (($mode ?? $this->compare) === 'lastyear') {
            [$from, $to] = $this->period();

            return [$from->copy()->subYearNoOverflow(), $to->copy()->subYearNoOverflow()];
        }

        // A semester is compared with the whole semester before it.
        if ($semester = $this->presetSemester($this->datePreset)) {
            $before = $this->semesterBefore($semester);

            return $before ? [$before->starts_on->copy(), $before->ends_on->copy()] : null;
        }

        [$from, $to] = $this->period();
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $prevTo = $from->copy()->subDay();

        return [$prevTo->copy()->subDays($days - 1), $prevTo];
    }

    /** The range cut into days (up to a month), weeks (up to six months) or months, for the trend chart. */
    protected function buckets(Carbon $from, Carbon $to, ?string $unit = null): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();
        $days = (int) $from->diffInDays($to) + 1;
        $unit ??= $this->interval !== 'auto' ? $this->interval : ($days <= 31 ? 'day' : ($days <= 190 ? 'week' : 'month'));
        // Too many points to draw: step up to the next coarser unit.
        if ($unit === 'day' && $days > 400) {
            $unit = 'week';
        }
        if ($unit === 'week' && $days > 2800) {
            $unit = 'month';
        }
        $format = match ($unit) {
            'day', 'week' => 'M j',
            'month' => $from->year === $to->year ? 'M' : 'M Y',
            'year' => 'Y',
        };

        $buckets = [];
        $cursor = $from->copy();
        while ($cursor->lte($to) && count($buckets) < 400) {
            $end = (match ($unit) {
                'day' => $cursor->copy()->endOfDay(),
                'week' => $cursor->copy()->addDays(6)->endOfDay(),
                'year' => $cursor->copy()->endOfYear(),
                default => $cursor->copy()->endOfMonth(),
            })->min($to);
            $buckets[] = [
                'start' => $cursor->copy(),
                'end' => $end,
                'label' => $cursor->format($format),
                'dates' => $unit === 'day' ? $cursor->format('D, M j, Y') : $cursor->format('M j, Y') . ' – ' . $end->format('M j, Y'),
            ];
            $cursor = $end->copy()->addDay()->startOfDay();
        }

        return [$unit, $buckets];
    }

    /**
     * What the trend chart draws for the open report, following the item picked in the list:
     * forms filed, completed and returned; an organization's requests and filers; or when the
     * semester's clearance statuses were set.
     */
    protected function trend(Collection $forms, Collection $students, ?array $range = null, ?int $semesterId = null, ?string $unit = null): array
    {
        $count = fn (array $buckets, Collection $rows, string $field) => array_map(
            fn ($b) => $rows->filter(fn ($r) => $r[$field] && $r[$field]->between($b['start'], $b['end']))->count(),
            $buckets
        );

        if ($this->tab === 'clearance') {
            $rows = $students->when($this->clearanceOrg !== '', fn ($c) => $c->where('organization', $this->clearanceOrg))->filter(fn ($r) => $r['tagged']);
            $semester = Semester::find($semesterId ?? $this->semesterId);
            $to = $semester?->isDated() ? $semester->ends_on->copy()->min(today()) : today();
            $from = $semester?->isDated() ? $semester->starts_on->copy() : ($rows->min('tagged') ? Carbon::parse($rows->min('tagged')) : today()->subDays(29));
            [$unit, $buckets] = $this->buckets($from->min($to), $to, $unit);

            return ['unit' => $unit, 'buckets' => $buckets, 'series' => [
                ['cleared', 'Cleared', '#0d9488', $count($buckets, $rows->where('status', 'Cleared'), 'tagged')],
                ['pending', 'Pending', '#d97706', $count($buckets, $rows->where('status', 'Pending'), 'tagged')],
                ['uncleared', 'Uncleared', '#e11d48', $count($buckets, $rows->where('status', 'Uncleared'), 'tagged')],
            ]];
        }

        [$from, $to] = $range ?? $this->period();
        [$unit, $buckets] = $this->buckets($from, $to, $unit);

        if ($this->tab === 'organizations') {
            $rows = $this->org === null ? $forms : $forms->whereIn('user_id', $students->where('organization', $this->org)->pluck('user_id'));

            return ['unit' => $unit, 'buckets' => $buckets, 'series' => [
                ['requests', 'Requests filed', '#2A57B4', $count($buckets, $rows, 'submitted')],
                ['filers', 'Students who filed', '#d97706', array_map(
                    fn ($b) => $rows->filter(fn ($r) => $r['submitted'] && $r['submitted']->between($b['start'], $b['end']))->pluck('user_id')->unique()->count(),
                    $buckets
                )],
            ]];
        }

        $rows = $this->form === null ? $forms : $forms->where('key', $this->form);

        return ['unit' => $unit, 'buckets' => $buckets, 'series' => [
            ['filed', 'Filed', '#2A57B4', $count($buckets, $rows, 'submitted')],
            ['completed', 'Completed', '#0d9488', $count($buckets, $rows->where('state', 'completed'), 'completed')],
            ['returned', 'Returned', '#e11d48', $count($buckets, $rows->whereIn('state', ['rejected', 'voided']), 'submitted')],
        ]];
    }

    /**
     * The comparison drawn in the chart and the list when one is added: the same series over
     * the comparison dates, lined up bucket by bucket with the period on screen.
     */
    protected function comparison(array $trend, Collection $forms, Collection $students): ?array
    {
        if (! in_array($this->compare, ['previous', 'lastyear', 'custom'], true)) {
            return null;
        }

        if ($this->tab === 'clearance') {
            $semester = $this->comparisonSemester();
            if (! $semester) {
                return null;
            }
            $theirStudents = $this->clearanceRecords($semester->id);
            $theirTrend = $this->trend($forms, $theirStudents, null, $semester->id, $trend['unit']);
            $summary = $this->organizationSummary($theirStudents, collect())->keyBy('label');
            $list = ['__all' => $theirStudents->where('status', 'Cleared')->count()] + $summary->map(fn ($o) => $o['Cleared'])->all();
            $label = $semester->label();
        } else {
            $range = $this->previousPeriod();
            if (! $range) {
                return null;
            }
            $theirForms = $this->formRecords($range);
            $theirTrend = $this->trend($theirForms, $students, $range, null, $trend['unit']);
            if ($this->tab === 'forms') {
                $summary = $this->formSummary($theirForms)->keyBy('key');
                $list = ['__all' => $theirForms->count()] + $summary->map(fn ($f) => $f['requests'])->all();
            } else {
                $summary = $this->organizationSummary($students, $theirForms)->keyBy('label');
                $list = ['__all' => $summary->sum('availed')] + $summary->map(fn ($o) => $o['availed'])->all();
            }
            $label = $range[0]->format('M j, Y') . ' – ' . $range[1]->format('M j, Y');
        }

        $n = count($trend['buckets']);
        $fit = fn (array $values) => array_slice(array_pad($values, $n, null), 0, $n);

        return [
            'label' => $label,
            'dates' => $fit(array_column($theirTrend['buckets'], 'dates')),
            'series' => collect($theirTrend['series'])->mapWithKeys(fn ($s) => [$s[0] => $fit($s[3])])->all(),
            'list' => $list,
            'summary' => $summary->all(),
        ];
    }

    /**
     * The comparison table, as in YouTube Studio: one row per form (or organization) with the
     * report's figures as columns and each value's share of the total. A metric comparison puts
     * its two columns first in their colours; an item comparison pins and colours its two rows;
     * a period comparison shows the earlier figure and the change in every cell.
     */
    protected function breakdown(Collection $formSummary, Collection $orgSummary, ?array $comparison): ?array
    {
        if ($this->compare === 'none' || (! $comparison && ! in_array($this->compare, ['metric', 'item'], true))) {
            return null;
        }

        // [series key the chart uses, field in the summary, header]
        $columns = match ($this->tab) {
            'organizations' => [['requests', 'requests', 'Requests filed'], ['filers', 'availed', 'Students who filed'], [null, 'members', 'Members']],
            'clearance' => [['cleared', 'Cleared', 'Cleared'], ['pending', 'Pending', 'Pending'], ['uncleared', 'Uncleared', 'Uncleared'], [null, 'Not tagged', 'Not tagged'], [null, 'members', 'Students']],
            default => [['filed', 'requests', 'Filed'], ['completed', 'completed', 'Completed'], ['returned', 'returned', 'Returned'], [null, 'students', 'Students']],
        };
        $columns = array_map(fn ($c) => ['series' => $c[0], 'field' => $c[1], 'label' => $c[2], 'colour' => null], $columns);

        if ($this->compare === 'metric') {
            $pick = [$this->compareA => 0, $this->compareB => 1];
            foreach ($columns as &$c) {
                $c['colour'] = isset($pick[$c['series']]) ? self::COMPARE_COLOURS[$pick[$c['series']]] : null;
                $c['order'] = $pick[$c['series']] ?? 9;
            }
            unset($c);
            usort($columns, fn ($a, $b) => $a['order'] <=> $b['order']);
        }

        $source = $this->tab === 'forms' ? $formSummary : $orgSummary;
        $then = $comparison['summary'] ?? null;
        $rows = $source->map(fn ($r) => [
            'key' => $r['key'] ?? $r['label'],
            'label' => $r['label'],
            'values' => collect($columns)->mapWithKeys(fn ($c) => [$c['field'] => (int) ($r[$c['field']] ?? 0)])->all(),
            'then' => $then !== null ? collect($columns)->mapWithKeys(fn ($c) => [$c['field'] => (int) ($then[$r['key'] ?? $r['label']][$c['field']] ?? 0)])->all() : null,
            'colour' => null,
        ])->values();

        if ($this->compare === 'item') {
            $rows = $rows->map(fn ($r) => ['colour' => $r['key'] === $this->compareA ? self::COMPARE_COLOURS[0] : ($r['key'] === $this->compareB ? self::COMPARE_COLOURS[1] : null)] + $r)
                ->sortBy(fn ($r) => $r['key'] === $this->compareA ? 0 : ($r['key'] === $this->compareB ? 1 : 2))
                ->values();
        }

        $totals = collect($columns)->mapWithKeys(fn ($c) => [$c['field'] => $rows->sum(fn ($r) => $r['values'][$c['field']])])->all();
        // Students are counted once per person, not once per form they filed.
        if ($this->tab === 'forms') {
            $totals['students'] = $this->formRecords()->pluck('user_id')->unique()->count();
        }
        $thenTotals = $then !== null
            ? collect($columns)->mapWithKeys(fn ($c) => [$c['field'] => collect($then)->sum(fn ($r) => (int) ($r[$c['field']] ?? 0))])->all()
            : null;
        if ($thenTotals !== null && $this->tab === 'forms' && ($range = $this->previousPeriod())) {
            $thenTotals['students'] = $this->formRecords($range)->pluck('user_id')->unique()->count();
        }

        return [
            'item' => $this->tab === 'forms' ? 'Form' : 'Organization',
            'columns' => $columns,
            'rows' => $rows->all(),
            'totals' => $totals,
            'thenTotals' => $thenTotals,
            'thenLabel' => $comparison['label'] ?? null,
        ];
    }

    /**
     * Everything the page shows, computed once: the page renders it, and the PDF download
     * lays out the same figures, so the two always agree.
     */
    protected function viewData(): array
    {
        [$from, $to] = $this->period();
        $forms = $this->formRecords();
        $students = $this->clearanceRecords();
        $rows = $this->listedRows($forms, $students);

        $pages = max(1, (int) ceil($rows->count() / self::PER_PAGE));
        $this->page = min($this->page, $pages);

        // Comparisons: forms against the previous period of equal length, clearance against the semester before.
        $prev = $this->previousPeriod();
        $prevForms = $prev ? $this->formRecords($prev) : collect();
        $prevSemester = $this->tab === 'clearance' ? $this->comparisonSemester() : null;
        $trend = $this->trend($forms, $students);
        $formSummary = $this->formSummary($forms);
        $orgSummary = $this->organizationSummary($students, $forms);
        $itemLabels = $this->tab === 'forms'
            ? $formSummary->pluck('label', 'key')->all()
            : $orgSummary->pluck('label', 'label')->all();
        $comparison = $this->comparison($trend, $forms, $students);
        $chart = match ($this->compare) {
            'metric' => $this->metricTrend($trend),
            'item' => $this->itemTrend($forms, $students, $trend, $itemLabels),
            default => $trend,
        };
        $compareTitle = match ($this->compare) {
            'metric' => ($this->seriesOptions()[$this->compareA] ?? '') . ' with ' . ($this->seriesOptions()[$this->compareB] ?? ''),
            'item' => ($itemLabels[$this->compareA] ?? $this->compareA) . ' with ' . ($itemLabels[$this->compareB] ?? $this->compareB),
            default => $comparison ? 'this period with ' . $comparison['label'] : null,
        };

        $data = [
            'from' => $from,
            'to' => $to,
            'prev' => $prev,
            'prevTotals' => $prev ? [
                'requests' => $prevForms->count(),
                'students' => $prevForms->pluck('user_id')->unique()->count(),
                'completed' => $prevForms->where('state', 'completed')->count(),
                'returned' => $prevForms->whereIn('state', ['rejected', 'voided'])->count(),
            ] : null,
            'trend' => $chart,
            'comparison' => $comparison,
            'compareOptions' => $this->comparisonOptions(),
            'compareTitle' => $compareTitle,
            'breakdown' => $this->breakdown($formSummary, $orgSummary, $comparison),
            'seriesOptions' => $this->seriesOptions(),
            'itemLabels' => $itemLabels,
            'compareColours' => self::COMPARE_COLOURS,
            'prevSemesterLabel' => $prevSemester?->label(),
            'prevClearanceCounts' => $prevSemester ? $this->clearanceRecords($prevSemester->id)->countBy('status') : null,
            'formSummary' => $formSummary,
            'formTotals' => [
                'requests' => $forms->count(),
                'students' => $forms->pluck('user_id')->unique()->count(),
                'completed' => $forms->where('state', 'completed')->count(),
                'returned' => $forms->whereIn('state', ['rejected', 'voided'])->count(),
            ],
            'orgSummary' => $orgSummary,
            'clearanceCounts' => $students->countBy('status'),
            'clearanceOrgs' => $students->pluck('organization')->unique()->sort()->values(),
            'studentTotal' => $students->count(),
            'rows' => $rows->forPage($this->page, self::PER_PAGE)->values(),
            'rowTotal' => $rows->count(),
            'pages' => $pages,
            'semesters' => Semester::all()->sortByDesc(fn (Semester $s) => $s->sortKey())->values(),
            'periodSemesters' => Semester::all()
                ->filter(fn (Semester $s) => $s->isDated() && $s->hasStarted())
                ->sortByDesc(fn (Semester $s) => $s->starts_on)->values(),
            'semesterLabel' => Semester::find($this->semesterId)?->label(),
            'periodName' => $this->periodName(),
            'metricGroups' => self::METRICS[$this->tab],
            'metricLabels' => $this->metricLabels(),
            'columnOrder' => $this->orderedMetrics(),
            'columnTotals' => [
                'requests' => $rows->sum('requests'),
                'days' => ($d = $rows->whereNotNull('days')->avg('days')) !== null ? round($d, 1) : null,
            ],
            'savedReports' => SavedReport::where('user_id', auth()->id())->latest('updated_at')->get(['id', 'name', 'tab', 'updated_at']),
            'currentSaved' => $this->ownSavedReport($this->savedId),
            'sheetUrl' => $this->sheetUrl(),
            'aiSuggestions' => $this->aiSuggestions(),
        ];

        return $data + ['summary' => $this->summary($data)];
    }

    /**
     * What the report says at a glance, in words and figures: the headline, the four figures with
     * their comparison and help text, the details box, the one-line finding, the pick list and the
     * records title. Styling stays in the views; this is the content both the page and the PDF use.
     */
    protected function summary(array $d): array
    {
        $pct = fn ($n, $t) => $t > 0 ? (int) round($n / $t * 100) : 0;
        $rangeLabel = $d['from']->format('M j, Y') . ' – ' . $d['to']->format('M j, Y');
        $formSummary = $d['formSummary'];
        $orgSummary = $d['orgSummary'];
        $formTotals = $d['formTotals'];
        $prevTotals = $d['prevTotals'];
        $counts = $d['clearanceCounts'];
        $prevCounts = $d['prevClearanceCounts'];
        $studentTotal = $d['studentTotal'];
        $semesterLabel = $d['semesterLabel'];
        $cleared = $counts['Cleared'] ?? 0;
        $realOrgs = $orgSummary->where('label', '!=', 'No organization')->values();
        $selectedForm = $this->form ? $formSummary->firstWhere('key', $this->form) : null;
        $versus = $this->tab === 'clearance'
            ? $d['prevSemesterLabel']
            : ($d['compareOptions'][$this->compare === 'lastyear' ? 'lastyear' : 'previous'][1] ?? null);

        $topForm = $formSummary->first();
        $topOrg = $realOrgs->sortByDesc('members')->first();
        $insight = match ($this->tab) {
            'organizations' => $topOrg
                ? $topOrg['label'] . ' has the most students (' . $topOrg['members'] . '); ' . $topOrg['availed'] . ' of them filed a form in this period and ' . $topOrg['Cleared'] . ' are cleared.'
                : 'No active student belongs to an organization yet.',
            'clearance' => $pct($cleared, $studentTotal) . '% of active students are cleared for ' . ($semesterLabel ?? 'this semester') . '. '
                . ($counts['Not tagged'] ?? 0) . ' still need an officer to check them and ' . ($counts['Uncleared'] ?? 0) . ' are uncleared.',
            default => $topForm
                ? $topForm['label'] . ' was the most requested form: ' . $topForm['requests'] . ' ' . Str::plural('request', $topForm['requests']) . ' from ' . $topForm['students'] . ' ' . Str::plural('student', $topForm['students']) . '. ' . $formTotals['completed'] . ' of ' . $formTotals['requests'] . ' requests are completed.'
                : 'No transaction forms were filed in this period. Try a longer period.',
        };

        // [value, what it means, tone]
        $headline = match ($this->tab) {
            'organizations' => [$pct($realOrgs->sum('availed'), $realOrgs->sum('members')) . '%', 'of members filed a form', 'blue'],
            'clearance' => [$pct($cleared, $studentTotal) . '%', 'of students cleared', 'teal'],
            default => [$pct($formTotals['completed'], $formTotals['requests']) . '%', 'of requests completed', 'emerald'],
        };

        // key names the figure (the views pick its icon and colour); before is null when nothing compares
        $tile = fn ($key, $label, $value, $before = null, $lower = false, $click = null) => compact('key', 'label', 'value', 'before', 'lower', 'click');
        $tiles = match ($this->tab) {
            'organizations' => [
                $tile('orgs', 'Organizations', $realOrgs->count()),
                $tile('members', 'Students in one', $realOrgs->sum('members')),
                $tile('filed', 'Members who filed', $realOrgs->sum('availed')),
                $tile('cleared', 'Members cleared', $realOrgs->sum('Cleared')),
            ],
            'clearance' => array_map(fn ($label) => $tile($label, $label, $counts[$label] ?? 0, $prevCounts ? ($prevCounts[$label] ?? 0) : null, $label !== 'Cleared', $label === 'Not tagged' ? 'none' : $label),
                ['Cleared', 'Pending', 'Uncleared', 'Not tagged']),
            default => [
                $tile('requests', 'Requests filed', $formTotals['requests'], $prevTotals['requests'] ?? null),
                $tile('students', 'Students who filed', $formTotals['students'], $prevTotals['students'] ?? null),
                $tile('completed', 'Completed', $formTotals['completed'], $prevTotals['completed'] ?? null),
                $tile('returned', 'Returned', $formTotals['returned'], $prevTotals['returned'] ?? null, true),
            ],
        };

        // label => [what it counts, what it shows]
        $vs = $versus ? ' The badge is the change against ' . $versus . ', and “better” or “worse” says whether that is good news.' : ' There is no earlier period to compare with.';
        $help = match ($this->tab) {
            'organizations' => [
                'Organizations' => ['Organizations with at least one active student member.', 'How many organizations appear in this report.'],
                'Students in one' => ['Active students whose profile names an organization.', 'The members across every organization. Students with none are listed as “No organization”.'],
                'Members who filed' => ['Members who submitted at least one form from ' . $rangeLabel . '.', 'How many members used the office in the period.'],
                'Members cleared' => ['Members whose clearance for ' . ($semesterLabel ?? 'the chosen semester') . ' is Cleared.', 'How many members are cleared, set by the Semester picker.'],
            ],
            'clearance' => [
                'Cleared' => ['Students an officer marked Cleared for ' . ($semesterLabel ?? 'the semester') . '.', 'The number and share of active students.' . $vs],
                'Pending' => ['Students marked Pending: an officer started checking them but has not finished.', 'The number and share of active students. Fewer is better.' . $vs],
                'Uncleared' => ['Students marked Uncleared: something is still owed or missing.', 'The number and share of active students. Fewer is better.' . $vs],
                'Not tagged' => ['Students no officer has checked yet this semester. This is not the same as Uncleared.', 'The number and share of active students still waiting.' . $vs],
            ],
            default => [
                'Requests filed' => ['Every form students submitted from ' . $rangeLabel . ', one per request. Drafts are not counted, and one student can file many.', 'The number of requests.' . $vs],
                'Students who filed' => ['Different students who submitted at least one form in the period. A student who filed five forms counts once.', 'How many students used the office.' . $vs],
                'Completed' => ['Requests that were finished and issued, counted on the day they were completed.', 'The number completed in the period.' . $vs],
                'Returned' => ['Requests that were rejected or voided, counted on the day they were filed.', 'The number returned. Fewer is better.' . $vs],
            ],
        };

        $details = match ($this->tab) {
            'organizations' => [['Forms filed', $rangeLabel], ['Clearance', $semesterLabel ?? 'No semester'], ['Organization', $this->org ?? 'All organizations']],
            'clearance' => [
                ['Semester', $semesterLabel ?? 'No semester'],
                ['Dates', $d['semesters']->firstWhere('id', $this->semesterId)?->dateLabel() ?? 'No dates set'],
                ['Compared with', $d['prevSemesterLabel'] ?? 'No earlier semester'],
            ],
            default => [['Period', $rangeLabel], ['Compared with', $versus ?? 'Nothing (all time)'], ['Form', $selectedForm['label'] ?? 'All forms']],
        };

        // pick list: [value, label, big number, small number, selected]
        $listAll = match ($this->tab) {
            'organizations' => [null, 'All organizations', $orgSummary->sum('availed'), $orgSummary->sum('members'), $this->org === null],
            'clearance' => ['', 'All organizations', $cleared, $studentTotal, $this->clearanceOrg === ''],
            default => [null, 'All forms', $formTotals['requests'], $formTotals['students'], $this->form === null],
        };
        $listItems = ($this->tab === 'forms'
            ? $formSummary->map(fn ($f) => [$f['key'], $f['label'], $f['requests'], $f['students'], $this->form === $f['key']])
            : $orgSummary->map(fn ($o) => [$o['label'], $o['label'], $this->tab === 'organizations' ? $o['availed'] : $o['Cleared'], $o['members'], $this->tab === 'organizations' ? $this->org === $o['label'] : $this->clearanceOrg === $o['label']])
        )->values()->all();
        $list = match ($this->tab) {
            'organizations' => ['Organizations', 'Filed / members', 'Organization', 'Members who filed', 'Members'],
            'clearance' => ['Organizations', 'Cleared / students', 'Organization', 'Cleared', 'Students'],
            default => ['Forms', 'Requests / students', 'Form', 'Requests', 'Students'],
        };

        $records = match ($this->tab) {
            'organizations' => [$this->org ? 'Students of ' . $this->org : 'Every student, by organization', 'Forms filed in the period, and clearance for ' . ($semesterLabel ?? 'the chosen semester') . '.', 'student'],
            'clearance' => [($this->status === '' ? 'Every student' : ($this->status === 'none' ? 'Not tagged' : $this->status)) . ($this->clearanceOrg !== '' ? ' in ' . $this->clearanceOrg : ''), '“Tagged by” is the officer who set the status.', 'student'],
            default => [$selectedForm ? 'Students who filed ' . $selectedForm['label'] : 'Every request', 'One row per request. Click the transaction code to open it.', 'request'],
        };

        return [
            'rangeLabel' => $rangeLabel,
            'versus' => $versus,
            'selectedForm' => $selectedForm,
            'insight' => $insight,
            'headline' => $headline,
            'tiles' => $tiles,
            'help' => $help,
            'details' => $details,
            'listAll' => $listAll,
            'listItems' => $listItems,
            'list' => $list,
            'records' => $records,
            'filtered' => ($this->tab === 'forms' && $this->form) || ($this->tab === 'organizations' && $this->org)
                || ($this->tab === 'clearance' && ($this->status !== '' || $this->clearanceOrg !== '')),
        ];
    }

    public function render()
    {
        return view('livewire.admin.reports', $this->viewData())->layout('layouts.app', ['title' => 'Reports']);
    }
}
