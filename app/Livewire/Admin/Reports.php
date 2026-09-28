<?php

namespace App\Livewire\Admin;

use App\Exports\AdminReportSpreadsheet;
use App\Services\Admin\AdminReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        $this->dispatch('reports-charts-refresh');
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
        $this->dispatch('reports-charts-refresh');
    }

    /** Fires whenever a wire:model.live-bound filter changes, so Chart.js can pull fresh data. */
    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['dateFrom', 'dateTo', 'academicYear', 'semester'], true)) {
            $this->dispatch('reports-charts-refresh');
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

    public function getReportData()
    {
        return $this->service->build($this->filters());
    }

    /** Called from the front end (wire:ignore Chart.js block) to refresh chart data without a full re-render. */
    public function getChartData(): array
    {
        return $this->service->build($this->filters());
    }

    public function exportPdf()
    {
        $data = $this->service->build($this->filters());

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
        $data = $this->service->build($this->filters());
        $spreadsheet = (new AdminReportSpreadsheet())->build($data);

        $filename = 'documate-report-' . now()->format('Ymd-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->setIncludeCharts(true);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function render()
    {
        return view('livewire.admin.reports', [
            'report'        => $this->getReportData(),
            'academicYears' => $this->service->availableAcademicYears(),
            'semesters'     => self::SEMESTERS,
        ])->layout('layouts.app', ['title' => 'Reports']);
    }
}