<?php

namespace App\Http\Controllers;

use App\Livewire\Admin\Reports;
use App\Services\Admin\AdminReportService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * The Reports table opened as a spreadsheet in its own browser tab, with the same report,
 * period, filters and columns as on screen. It can be downloaded from there as .xlsx or .csv.
 */
class ReportSheetController extends Controller
{
    public function show(Request $request)
    {
        $report = app(Reports::class);
        $report->boot(app(AdminReportService::class));
        $report->mount();
        $report->applyState($request->query());
        $table = $report->table();
        $format = $request->query('format');

        if (in_array($format, ['csv', 'xlsx'], true)) {
            Audit::record('exported', 'Reports', "Downloaded {$table['title']} as " . strtoupper($format) . " ({$table['count']} " . Str::plural('row', $table['count']) . ')');
            $filename = 'documate-' . Str::slug(Str::limit($table['title'], 60, '')) . '-' . now()->format('Ymd-His') . '.' . $format;

            return $format === 'csv'
                ? response()->streamDownload(fn () => Reports::writeCsv($table), $filename, ['Content-Type' => 'text/csv; charset=UTF-8'])
                : response()->streamDownload(fn () => (new Xlsx($this->workbook($table)))->save('php://output'), $filename, [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
        }

        return view('admin.report-sheet', [
            'table' => $table,
            'period' => $report->periodName(),
            'query' => $request->query(),
        ]);
    }

    protected function workbook(array $table): Spreadsheet
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Table data');
        $sheet->fromArray([$table['head'], $table['totals'], ...$table['rows']], null, 'A1', true);

        $last = $sheet->getHighestColumn();
        $sheet->getStyle("A1:{$last}2")->getFont()->setBold(true);
        $sheet->freezePane('B3');
        foreach (range(1, count($table['head'])) as $i) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        $sheet->setAutoFilter("A1:{$last}" . (count($table['rows']) + 2));

        return $book;
    }
}
