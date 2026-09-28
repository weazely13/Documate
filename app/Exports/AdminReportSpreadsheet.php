<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Builds the Excel export for the Admin Reports page.
 *
 * Uses PhpSpreadsheet directly (pure PHP, no native extensions like GD
 * required) so charts come out as real, editable Excel chart objects
 * rather than pasted-in images.
 *
 * Every sheet opens with a one- or two-sentence description so the
 * workbook is readable on its own, without the dashboard it came from.
 */
class AdminReportSpreadsheet
{
    private const BRAND = '2A57B4';
    private const DESC_COLOR = '64748B';

    public function build(array $report): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->summarySheet($spreadsheet, $report);

        $this->breakdownSheet(
            $spreadsheet, 'Users',
            'Everyone registered in DocuMate for the selected date range, split by role and by account status.',
            $report['users']['by_status'] ?? [], $report['users']['by_role'] ?? [],
            'Status', 'By Role'
        );

        $this->breakdownSheet(
            $spreadsheet, 'Transactions',
            'Document requests filed through DocuMate for the selected date range, by workflow status and by document type.',
            $report['transactions']['by_status'] ?? [], $report['transactions']['by_template'] ?? [],
            'Status', 'By Document Type'
        );

        $this->breakdownSheet(
            $spreadsheet, 'Appointments',
            'Appointments booked for the selected date range, by outcome and by session.',
            $report['appointments']['by_status'] ?? [], $report['appointments']['by_session'] ?? [],
            'Status', 'By Session'
        );

        $this->breakdownSheet(
            $spreadsheet, 'Clearance',
            'Students tagged for clearance in the selected academic year and semester, by current status. Follows the AY / Semester filter, not the date range.',
            $report['clearance']['by_status'] ?? [], [],
            'Status', null
        );

        $this->breakdownSheet(
            $spreadsheet, 'Verification',
            'e-Slip submissions for the selected academic year and semester, by review status. Follows the AY / Semester filter, not the date range.',
            $report['verification']['by_status'] ?? [], [],
            'Status', null
        );

        $this->logbookSheet($spreadsheet, $report['logbook'] ?? []);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** Writes a wrapped, italic description line under a sheet's title. Returns the next free row. */
    protected function writeDescription($sheet, string $description, int $row, string $range = 'A:F'): int
    {
        [$startCol, $endCol] = explode(':', $range);
        $sheet->setCellValue($startCol . $row, $description);
        $sheet->mergeCells($startCol . $row . ':' . $endCol . $row);
        $sheet->getStyle($startCol . $row)->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB(self::DESC_COLOR);
        $sheet->getStyle($startCol . $row)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(28);

        return $row + 1;
    }

    protected function summarySheet(Spreadsheet $spreadsheet, array $report): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Overview');

        $sheet->setCellValue('A1', 'DocuMate — Admin Report');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $filters = $report['filters'];
        $sheet->setCellValue('A2', sprintf(
            'Range: %s to %s   |   AY: %s   |   Semester: %s   |   Generated: %s',
            $filters['date_from'] ?? '—',
            $filters['date_to'] ?? '—',
            $filters['academic_year'] ?? 'All',
            $filters['semester'] ?? 'All',
            $report['generated_at']->format('M d, Y h:i A')
        ));
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->getColor()->setRGB('64748B');

        $descRow = $this->writeDescription(
            $sheet,
            'This workbook summarizes VPSD student services activity for the range above. "Range" scopes the Users, '
            . 'Transactions, Appointments and Logbook sheets; "AY / Semester" scopes Clearance and Verification, which '
            . 'are tracked by academic term instead. Each sheet after this one covers one system area with its own '
            . 'short description, data table and chart.',
            4,
            'A:D'
        );

        $cards = $report['overview']['cards'];
        $rows = [
            ['Metric', 'Value'],
            ['Total Students / Officers', $cards['total_students']],
            ['Active Accounts', $cards['active_accounts']],
            ['Pending Verifications', $cards['pending_verifications']],
            ['Total Transactions', $cards['total_transactions']],
            ['Completed Transactions', $cards['completed_transactions']],
            ['Total Appointments', $cards['total_appointments']],
            ["Today's Appointments", $cards['todays_appointments']],
            ['Clearance Rate', $cards['clearance_rate'] . '%'],
        ];

        $startRow = $descRow + 1;
        foreach ($rows as $i => $row) {
            $sheet->setCellValue('A' . ($startRow + $i), $row[0]);
            $sheet->setCellValue('B' . ($startRow + $i), $row[1]);
        }
        $sheet->getStyle('A' . $startRow . ':B' . $startRow)->getFont()->setBold(true);
        $sheet->getStyle('A' . $startRow . ':B' . $startRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BRAND);
        $sheet->getStyle('A' . $startRow . ':B' . $startRow)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(16);

        // Registration trend as a data table for a chart
        $trend = $report['overview']['charts']['registration_trend'];
        $trendStart = $startRow + count($rows) + 2;
        $sheet->setCellValue('A' . $trendStart, 'New Registrations by Month');
        $sheet->getStyle('A' . $trendStart)->getFont()->setBold(true);
        $sheet->setCellValue('A' . ($trendStart + 1), 'Month');
        $sheet->setCellValue('B' . ($trendStart + 1), 'Registrations');

        foreach ($trend['labels'] as $i => $label) {
            $sheet->setCellValue('A' . ($trendStart + 2 + $i), $label);
            $sheet->setCellValue('B' . ($trendStart + 2 + $i), $trend['values'][$i] ?? 0);
        }

        if (count($trend['labels']) > 1) {
            $lastRow = $trendStart + 1 + count($trend['labels']);
            $this->addLineChart(
                $sheet,
                'RegTrend',
                'New Registrations Trend',
                "'Overview'!\$A\$" . ($trendStart + 2) . ":\$A\$" . $lastRow,
                "'Overview'!\$B\$" . ($trendStart + 2) . ":\$B\$" . $lastRow,
                'D' . $trendStart,
                count($trend['labels'])
            );
        }
    }

    protected function breakdownSheet(
        Spreadsheet $spreadsheet,
        string $title,
        string $description,
        array $primary,
        array $secondary = [],
        string $primaryLabel = 'Status',
        ?string $secondaryLabel = 'Breakdown'
    ): void {
        if (empty($primary) && empty($secondary)) {
            return;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(substr($title, 0, 31));

        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $descRow = $this->writeDescription($sheet, $description, 2, 'A:D');

        $tableTitleRow = $descRow + 1;
        $sheet->setCellValue('A' . $tableTitleRow, $primaryLabel);
        $sheet->getStyle('A' . $tableTitleRow)->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A' . ($tableTitleRow + 1), $primaryLabel);
        $sheet->setCellValue('B' . ($tableTitleRow + 1), 'Count');
        $sheet->getStyle('A' . ($tableTitleRow + 1) . ':B' . ($tableTitleRow + 1))->getFont()->setBold(true);

        $row = $tableTitleRow + 2;
        foreach ($primary as $label => $value) {
            $sheet->setCellValue('A' . $row, ucwords(str_replace('_', ' ', (string) $label)));
            $sheet->setCellValue('B' . $row, (int) $value);
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(12);

        if (count($primary) > 1) {
            $dataStartRow = $tableTitleRow + 2;
            $lastRow = $row - 1;
            $this->addPieChart(
                $sheet,
                'Chart' . preg_replace('/[^A-Za-z0-9]/', '', $title),
                $title . ' — ' . $primaryLabel,
                "'{$sheet->getTitle()}'!\$A\${$dataStartRow}:\$A\${$lastRow}",
                "'{$sheet->getTitle()}'!\$B\${$dataStartRow}:\$B\${$lastRow}",
                'D' . $tableTitleRow,
                count($primary)
            );
        }

        if (!empty($secondary) && $secondaryLabel) {
            $secStart = $row + 2;
            $sheet->setCellValue('A' . $secStart, $secondaryLabel);
            $sheet->getStyle('A' . $secStart)->getFont()->setBold(true)->setSize(13);
            $sheet->setCellValue('A' . ($secStart + 1), 'Label');
            $sheet->setCellValue('B' . ($secStart + 1), 'Count');
            $sheet->getStyle('A' . ($secStart + 1) . ':B' . ($secStart + 1))->getFont()->setBold(true);

            $r = $secStart + 2;
            foreach ($secondary as $label => $value) {
                $sheet->setCellValue('A' . $r, ucwords(str_replace('_', ' ', (string) $label)));
                $sheet->setCellValue('B' . $r, (int) $value);
                $r++;
            }
        }
    }

    protected function logbookSheet(Spreadsheet $spreadsheet, array $logbook): void
    {
        if (empty($logbook['by_purpose']) && empty($logbook['by_program']) && empty($logbook['monthly'])) {
            return;
        }

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Logbook');

        $sheet->setCellValue('A1', 'Front Desk Logbook');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        $descRow = $this->writeDescription(
            $sheet,
            'Walk-in visits pasted in from the paper front-desk logbook. This sheet always covers the full lifetime '
            . 'history — logbook entries carry their own free-text dates and are not affected by the Range or '
            . 'AY / Semester filters.',
            2,
            'A:D'
        );

        $purposeTitleRow = $descRow + 1;
        $sheet->setCellValue('A' . $purposeTitleRow, 'Requests by Purpose');
        $sheet->getStyle('A' . $purposeTitleRow)->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A' . ($purposeTitleRow + 1), 'Purpose');
        $sheet->setCellValue('B' . ($purposeTitleRow + 1), 'Count');
        $sheet->getStyle('A' . ($purposeTitleRow + 1) . ':B' . ($purposeTitleRow + 1))->getFont()->setBold(true);

        $row = $purposeTitleRow + 2;
        foreach ($logbook['by_purpose'] ?? [] as $label => $value) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, (int) $value);
            $row++;
        }
        $purposeLastRow = $row - 1;
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(12);

        if (count($logbook['by_purpose'] ?? []) > 1) {
            $purposeDataStartRow = $purposeTitleRow + 2;
            $this->addPieChart(
                $sheet, 'LogbookPurpose', 'Requests by Purpose',
                "'Logbook'!\$A\${$purposeDataStartRow}:\$A\${$purposeLastRow}",
                "'Logbook'!\$B\${$purposeDataStartRow}:\$B\${$purposeLastRow}",
                'D' . $purposeTitleRow, count($logbook['by_purpose'])
            );
        }

        // Program breakdown, offset below the purpose table
        $progStart = $purposeLastRow + 3;
        $sheet->setCellValue('A' . $progStart, 'Requests by Course / Program');
        $sheet->getStyle('A' . $progStart)->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A' . ($progStart + 1), 'Program');
        $sheet->setCellValue('B' . ($progStart + 1), 'Count');
        $sheet->getStyle('A' . ($progStart + 1) . ':B' . ($progStart + 1))->getFont()->setBold(true);

        $r = $progStart + 2;
        foreach ($logbook['by_program'] ?? [] as $label => $value) {
            $sheet->setCellValue('A' . $r, $label);
            $sheet->setCellValue('B' . $r, (int) $value);
            $r++;
        }
        $progLastRow = $r - 1;

        if (count($logbook['by_program'] ?? []) > 1) {
            $this->addPieChart(
                $sheet, 'LogbookProgram', 'Requests by Program',
                "'Logbook'!\$A\${$progStart}:\$A\${$progLastRow}",
                "'Logbook'!\$B\${$progStart}:\$B\${$progLastRow}",
                'D' . $progStart, count($logbook['by_program'])
            );
        }

        // Monthly trend, offset below the program table
        $monthStart = $progLastRow + 3;
        $sheet->setCellValue('A' . $monthStart, 'Monthly Trend');
        $sheet->getStyle('A' . $monthStart)->getFont()->setBold(true)->setSize(13);
        $sheet->setCellValue('A' . ($monthStart + 1), 'Month');
        $sheet->setCellValue('B' . ($monthStart + 1), 'Count');
        $sheet->getStyle('A' . ($monthStart + 1) . ':B' . ($monthStart + 1))->getFont()->setBold(true);

        $months = array_keys($logbook['monthly'] ?? []);
        $values = array_values($logbook['monthly'] ?? []);
        foreach ($months as $i => $label) {
            $sheet->setCellValue('A' . ($monthStart + 2 + $i), $label);
            $sheet->setCellValue('B' . ($monthStart + 2 + $i), $values[$i]);
        }

        if (count($months) > 1) {
            $monthLastRow = $monthStart + 1 + count($months);
            $this->addLineChart(
                $sheet, 'LogbookMonthly', 'Monthly Trend',
                "'Logbook'!\$A\$" . ($monthStart + 2) . ":\$A\${$monthLastRow}",
                "'Logbook'!\$B\$" . ($monthStart + 2) . ":\$B\${$monthLastRow}",
                'D' . $monthStart, count($months)
            );
        }
    }

    protected function addPieChart(
        $sheet,
        string $name,
        string $title,
        string $categoriesRange,
        string $valuesRange,
        string $anchorCell,
        int $pointCount
    ): void {
        $categories = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $categoriesRange, null, $pointCount);
        $values = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valuesRange, null, $pointCount);

        $series = new DataSeries(
            DataSeries::TYPE_PIECHART,
            null,
            range(0, 0),
            [],
            [$categories],
            [$values]
        );

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_RIGHT, null, false);
        $chart = new Chart($name, new Title($title), $legend, $plotArea);
        $chart->setTopLeftPosition($anchorCell);
        $chart->setBottomRightPosition(
            chr(ord($anchorCell[0]) + 5) . ((int) substr($anchorCell, 1) + 14)
        );

        $sheet->addChart($chart);
    }

    protected function addLineChart(
        $sheet,
        string $name,
        string $title,
        string $categoriesRange,
        string $valuesRange,
        string $anchorCell,
        int $pointCount
    ): void {
        $categories = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $categoriesRange, null, $pointCount);
        $values = new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, $valuesRange, null, $pointCount);

        $series = new DataSeries(
            DataSeries::TYPE_LINECHART,
            DataSeries::GROUPING_STANDARD,
            range(0, 0),
            [],
            [$categories],
            [$values]
        );
        $series->setPlotDirection(DataSeries::DIRECTION_COL);

        $plotArea = new PlotArea(null, [$series]);
        $legend = new Legend(Legend::POSITION_BOTTOM, null, false);
        $chart = new Chart($name, new Title($title), $legend, $plotArea);
        $chart->setTopLeftPosition($anchorCell);
        $chart->setBottomRightPosition(
            chr(ord($anchorCell[0]) + 6) . ((int) substr($anchorCell, 1) + 14)
        );

        $sheet->addChart($chart);
    }
}