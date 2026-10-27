<?php

namespace App\Livewire\Admin;

use App\Exports\AdminReportSpreadsheet;
use App\Models\Appointment;
use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use App\Support\DashboardInsights;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Services\Admin\AdminReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Services\Workflow\WorkflowReportService;

class Reports extends Component
{
    /** Only these three exist school-wide — never pulled from distinct DB values. */
    public const SEMESTERS = ['First Semester', 'Second Semester', 'Summer'];

    protected const PRESET_DAYS = ['7d' => 7, '30d' => 30, '90d' => 90, '180d' => 180, '365d' => 365];

    public string $datePreset = '180d';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;
    public ?string $academicYear = null;
    public ?string $semester = null;

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

        $this->applyPreset('180d');
    }

    /**
     * Quick date-range chip (7D / 30D / 90D / 6M / 1Y / All). Selecting one
     * clears any custom range and instantly refreshes the charts.
     */
    public function setDatePreset(string $preset): void
    {
        $this->applyPreset($preset);
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

        $days = self::PRESET_DAYS[$preset] ?? 180;
        $this->dateTo = now()->toDateString();
        $this->dateFrom = now()->subDays($days - 1)->toDateString();
    }

    public function useCustomRange(): void
    {
        $this->datePreset = 'custom';
    }

    public function resetFilters(): void
    {
        $this->applyPreset('180d');
        $this->academicYear = null;
        $this->semester = null;
    }

    /** Typing a date switches the range to "Custom". */
    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo'], true)) {
            $this->datePreset = 'custom';
        }
    }

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

    public function exportPdf()
    {
        $data = $this->exportData();

        $pdf = Pdf::loadView('exports.admin-report-pdf', [
            'report'  => $data,
            'service' => $this->service,
        ])->setPaper('a4', 'portrait');

        $filename = 'documate-report-' . now()->format('Ymd-His') . '.pdf';

        return response()->streamDownload(
            fn () => print ($pdf->output()),
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
    protected function withWorkflow(array $data): array
    {
        $data['workflow'] = app(WorkflowReportService::class)->build([
            'from' => $this->dateFrom,
            'to' => $this->dateTo,
        ]);

        return $data;
    }

    public function getReportData()
    {
        return $this->withWorkflow($this->service->build($this->filters()));
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


    public function render()
    {
        return view('livewire.admin.reports', [
            'report'        => $this->getReportData(),
            'insights'      => $this->insights(),
            'academicYears' => $this->service->availableAcademicYears(),
            'semesters'     => self::SEMESTERS,
        ])->layout('layouts.app', ['title' => 'Reports']);
    }
}