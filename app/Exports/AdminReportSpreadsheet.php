<?php

namespace App\Exports;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds the Excel export for the Admin Reports page: the same sections as the page
 * (summary, activity, documents, appointments, students, logbook) plus the records
 * behind them (every document and appointment in the period) as filterable sheets.
 *
 * Uses PhpSpreadsheet directly (pure PHP, no native extensions like GD required), so
 * charts come out as real, editable Excel chart objects rather than pasted-in images.
 */
class AdminReportSpreadsheet
{
    private const BRAND = '2A57B4';
    private const MUTED = '64748B';
    private const LINE = 'E2E8F0';

    public function build(array $report): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $spreadsheet->getProperties()->setCreator('DocuMate')->setTitle('DocuMate report ' . $report['period']['range']);

        $this->summarySheet($spreadsheet, $report);
        $this->activitySheet($spreadsheet, $report);
        $this->documentsSheet($spreadsheet, $report);
        $this->registerSheet($spreadsheet, $report);
        $this->appointmentsSheet($spreadsheet, $report);
        $this->appointmentListSheet($spreadsheet, $report);
        $this->studentsSheet($spreadsheet, $report);
        $this->logbookSheet($spreadsheet, $report['logbook']);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    // =========================================================
    // SHEETS
    // =========================================================

    protected function summarySheet(Spreadsheet $book, array $r): void
    {
        $sheet = $this->sheet($book, 'Summary', 'DocuMate report',
            'Headline numbers for the period, compared with the same number of days just before it. '
            . 'The period covers documents, appointments, accounts and logbook visits; the term covers clearance and verification.', $r);

        $wf = $r['workflow'];
        $ap = $r['appointments'];
        $cards = $r['overview']['cards'];
        $attended = $ap['by_status']['attended'] ?? 0;
        $missed = $ap['by_status']['missed'] ?? 0;

        $rows = [];
        foreach ($r['insights']['trend'] as $m) {
            $rows[] = [$m['label'], $m['total'], $m['previous'], $m['delta'] === null ? '—' : ($m['delta'] > 0 ? '+' : '') . $m['delta'] . '%', 'Period vs. the one before'];
        }
        $rows = array_merge($rows, [
            ['Digital documents completed', $wf['completed'], null, null, $wf['completion_rate'] . '% of ' . $wf['total'] . ' submitted'],
            ['Digital documents in progress', $wf['in_progress'], null, null, 'Still with the office, signers or the student'],
            ['Rejected', $wf['rejected'], null, null, $wf['rejections']['review'] . ' by VPSD, ' . $wf['rejections']['signer'] . ' by signers'],
            ['Voided', $wf['voided'], null, null, 'Completed documents later withdrawn'],
            ['Average time to issue', $wf['avg_issue'], null, null, 'Submitted to completed'],
            ['Attendance rate', $this->pct($attended, $attended + $missed) . '%', null, null, $attended . ' attended, ' . $missed . ' missed'],
            ['Reschedules', $ap['reschedule_count'], null, null, 'Appointments moved to another day'],
            ['Students and officers', $cards['total_students'], null, null, $cards['active_accounts'] . ' active accounts'],
            ['Cleared', $r['clearance']['clearance_rate'] . '%', null, null, ($r['clearance']['by_status']['Cleared'] ?? 0) . ' of ' . $r['clearance']['total'] . ' tagged students'],
            ['Verified', $r['verification']['verified_rate'] . '%', null, null, ($r['verification']['by_status']['verified'] ?? 0) . ' of ' . $r['verification']['total'] . ' e-slips'],
            ['Verifications waiting', $cards['pending_verifications'], null, null, 'E-slips not reviewed yet'],
            ['Logbook entries', $r['logbook']['total_entries'], null, null, $r['logbook']['total_uploads'] . ' logbooks uploaded'],
        ]);

        $this->table($sheet, 5, 'At a glance', ['Measure', 'This period', 'Previous period', 'Change', 'Notes'], $rows);
        $this->widths($sheet, [34, 14, 16, 12, 46]);
    }

    protected function activitySheet(Spreadsheet $book, array $r): void
    {
        $sheet = $this->sheet($book, 'Activity', 'Activity over time',
            'Documents submitted and completed, appointments booked and new accounts, per day (per week or month for long periods).', $r);

        $metrics = $r['insights']['trend'];
        $rows = [];
        foreach ($metrics[0]['series'] as $i => $point) {
            $rows[] = array_merge([$point['d']], array_map(fn ($m) => $m['series'][$i]['v'] ?? 0, $metrics));
        }
        $rows[] = array_merge(['Total'], array_map(fn ($m) => $m['total'], $metrics));

        [$first, $last] = $this->table($sheet, 5, 'Per period', array_merge(['Period'], array_column($metrics, 'label')), $rows, boldLast: true);

        if ($last - 1 > $first) {
            $this->chart($sheet, 'ActivityTrend', 'Submitted and completed', DataSeries::TYPE_LINECHART,
                $first, $last - 1, [2, 3], 'G5', 'P24');
        }
        $this->widths($sheet, [18, 12, 12, 20, 14]);
    }

    protected function documentsSheet(Spreadsheet $book, array $r): void
    {
        $wf = $r['workflow'];
        $sheet = $this->sheet($book, 'Documents', 'Documents',
            'Where the documents submitted in this period stand, which forms are requested most, how quickly signers respond, '
            . 'and when documents arrive. Status and signer figures cover digital documents; requests by form include paper forms.', $r);

        $row = 5;
        [$first, $last, $row] = $this->table($sheet, $row, 'Where documents stand', ['Status', 'Documents'],
            collect($wf['by_status'])->map(fn ($n, $s) => [$s, $n])->values()->all());
        if ($last > $first) {
            $this->chart($sheet, 'DocStatus', 'Where documents stand', DataSeries::TYPE_BARCHART, $first, $last, [2], 'H5', 'O20');
        }

        $byType = array_map(fn ($t) => [$t['label'], $t['completed'], $t['open'], $t['total']], $r['insights']['byType']);
        [$first, $last, $row] = $this->table($sheet, $row, 'Most requested documents', ['Form', 'Completed', 'In progress', 'Total'], $byType);
        if ($last > $first) {
            $this->chart($sheet, 'DocTypes', 'Most requested documents', DataSeries::TYPE_BARCHART, $first, $last, [2, 3], 'H22', 'O42', stacked: true);
        }

        [, , $row] = $this->table($sheet, $row, 'Signer performance', ['Role', 'Signed', 'Rejected', 'Waiting', 'Avg. response', 'Oldest waiting'],
            array_map(fn ($s) => [$s['label'], $s['signed'], $s['rejected'], $s['pending'], $s['avg_response'], $s['oldest_pending']], $wf['signers']));

        [, , $row] = $this->table($sheet, $row, 'Oldest unfinished documents', ['Reference', 'Form', 'Status', 'Submitted'],
            array_map(fn ($s) => [$s['code'], $s['name'], $s['status'], $s['since']], $wf['stuck']));

        $times = $r['insights']['times'];
        $hours = ['12–3 AM', '3–6 AM', '6–9 AM', '9 AM–12 PM', '12–3 PM', '3–6 PM', '6–9 PM', '9 PM–12 AM'];
        $grid = array_map(fn ($d) => array_merge([$d['name']], $d['bars'], [array_sum($d['bars'])]), $times['days']);
        $this->table($sheet, $row, 'When documents arrive (' . $times['timezone'] . ')', array_merge(['Day'], $hours, ['Total']), $grid);

        $this->widths($sheet, [40, 12, 12, 12, 14, 16, 12, 12, 12, 12]);
    }

    protected function registerSheet(Spreadsheet $book, array $r): void
    {
        $sheet = $this->sheet($book, 'Document register', 'Document register',
            'Every document submitted in this period, newest first. Use the filter arrows in the header row to narrow it down.', $r);

        $rows = array_map(fn ($d) => [
            $d['code'], $d['document'], $d['student'], $d['student_number'], $d['program'], $d['status'],
            $this->excelDate($d['submitted']), $this->excelDate($d['completed']), $d['days'],
        ], $r['register']);

        [$first, $last] = $this->table($sheet, 5, null,
            ['Reference', 'Form', 'Student', 'Student no.', 'Program', 'Status', 'Submitted', 'Completed', 'Days to complete'], $rows);

        $this->finishList($sheet, 'A5:I' . max(5, $last), $first, $last, ['G', 'H']);
        $this->widths($sheet, [18, 40, 28, 14, 40, 16, 18, 18, 16]);
    }

    protected function appointmentsSheet(Spreadsheet $book, array $r): void
    {
        $ap = $r['appointments'];
        $sheet = $this->sheet($book, 'Appointments', 'Appointments',
            'Appointments booked in this period: how they turned out, which session students pick, and how many visits fall on each day. '
            . 'The missed rate counts finished visits only (attended or missed).', $r);

        $attended = $ap['by_status']['attended'] ?? 0;
        $missed = $ap['by_status']['missed'] ?? 0;
        [, , $row] = $this->table($sheet, 5, 'At a glance', ['Measure', 'Value'], [
            ['Booked', $ap['total']],
            ['Attended', $attended],
            ['Missed', $missed],
            ['Attendance rate', $this->pct($attended, $attended + $missed) . '%'],
            ['Missed rate', $ap['missed_rate'] . '%'],
            ['Reschedules', $ap['reschedule_count']],
        ]);

        [$first, $last, $row] = $this->table($sheet, $row, 'Outcome', ['Status', 'Appointments'], $this->labelled($ap['by_status']));
        if ($last > $first) {
            $this->chart($sheet, 'ApptStatus', 'Outcome', DataSeries::TYPE_BARCHART, $first, $last, [2], 'E5', 'L20');
        }
        [, , $row] = $this->table($sheet, $row, 'Session', ['Session', 'Appointments'], $this->labelled($ap['by_session']));

        $visits = $r['insights']['visits'][0];
        [$first, $last] = $this->table($sheet, $row, 'Visits scheduled per period', ['Period', 'Visits'],
            array_map(fn ($p) => [$p['d'], $p['v']], $visits['series']));
        if ($last > $first) {
            $this->chart($sheet, 'ApptVisits', 'Visits scheduled', DataSeries::TYPE_LINECHART, $first, $last, [2], 'E22', 'P40');
        }
        $this->widths($sheet, [26, 14]);
    }

    protected function appointmentListSheet(Spreadsheet $book, array $r): void
    {
        $sheet = $this->sheet($book, 'Appointment list', 'Appointment list',
            'Every appointment booked in this period, by visit date.', $r);

        $rows = array_map(fn ($a) => [
            $this->excelDate($a['date']), $a['session'], $a['queue'], $a['student'], $a['student_number'], $a['purpose'], $a['status'], $a['reschedules'],
        ], $r['appointment_list']);

        [$first, $last] = $this->table($sheet, 5, null,
            ['Visit date', 'Session', 'Queue no.', 'Student', 'Student no.', 'Purpose', 'Status', 'Reschedules'], $rows);

        $this->finishList($sheet, 'A5:H' . max(5, $last), $first, $last, ['A'], 'yyyy-mm-dd');
        $this->widths($sheet, [14, 12, 10, 28, 14, 44, 12, 12]);
    }

    protected function studentsSheet(Spreadsheet $book, array $r): void
    {
        $cl = $r['clearance'];
        $us = $r['users'];
        $cards = $r['overview']['cards'];
        $sheet = $this->sheet($book, 'Students', 'Students',
            'Accounts, clearance and account verification. Clearance and verification follow the term filter; '
            . 'new-account breakdowns cover accounts created in the period.', $r);

        [, , $row] = $this->table($sheet, 5, 'At a glance', ['Measure', 'Value'], [
            ['Students and officers', $cards['total_students']],
            ['Active accounts', $cards['active_accounts']],
            ['New accounts in the period', $us['total']],
            ['Tagged for clearance', $cl['total']],
            ['Clearance rate', $cl['clearance_rate'] . '%'],
            ['E-slips submitted', $r['verification']['total']],
            ['Verified rate', $r['verification']['verified_rate'] . '%'],
            ['Verifications waiting', $cards['pending_verifications']],
        ]);

        [, , $row] = $this->table($sheet, $row, 'Clearance status', ['Status', 'Students'], $this->labelled($cl['by_status'], false));

        $orgs = collect($cl['by_organization'])->map(function ($s, $org) {
            $c = (int) ($s['Cleared'] ?? 0);
            $p = (int) ($s['Pending'] ?? 0);
            $u = (int) ($s['Uncleared'] ?? 0);

            return [$org, $c, $p, $u, $c + $p + $u, $this->pct($c, $c + $p + $u) . '%'];
        })->sortByDesc(4)->values()->all();
        [$first, $last, $row] = $this->table($sheet, $row, 'Clearance by organization', ['Organization', 'Cleared', 'Pending', 'Uncleared', 'Total', 'Cleared %'], $orgs);
        if ($last > $first) {
            $this->chart($sheet, 'ClearanceOrgs', 'Clearance by organization', DataSeries::TYPE_BARCHART,
                $first, min($last, $first + 11), [2, 3, 4], 'H5', 'P28', stacked: true);
        }

        [, , $row] = $this->table($sheet, $row, 'Account verification', ['Status', 'E-slips'], $this->labelled($r['verification']['by_status']));
        [, , $row] = $this->table($sheet, $row, 'New accounts by program', ['Program', 'Accounts'], $this->labelled($us['by_program'], false));
        [, , $row] = $this->table($sheet, $row, 'New accounts by year level', ['Year level', 'Accounts'],
            collect($us['by_year_level'])->map(fn ($n, $y) => ['Year ' . $y, (int) $n])->values()->all());
        $this->table($sheet, $row, 'Accounts by role', ['Role', 'Accounts'], $this->labelled($us['by_role'], false));

        $this->widths($sheet, [40, 12, 12, 12, 12, 12]);
    }

    protected function logbookSheet(Spreadsheet $book, array $lb): void
    {
        $sheet = $book->createSheet();
        $sheet->setTitle('Logbook');
        $this->heading($sheet, 'Front desk logbook',
            'Walk-in visits from the uploaded paper logbooks, dated in the period. The term does not apply: logbook rows only carry a date.');

        if (! $lb['total_entries']) {
            $sheet->setCellValue('A5', 'No logbook entries are dated in this period.');

            return;
        }

        [, , $row] = $this->table($sheet, 5, 'At a glance', ['Measure', 'Value'], [
            ['Logbooks uploaded', $lb['total_uploads']],
            ['Entries', $lb['total_entries']],
        ]);
        [$first, $last, $row] = $this->table($sheet, $row, 'Why students visit', ['Purpose', 'Visits'], $this->labelled($lb['by_purpose'], false));
        if ($last > $first) {
            $this->chart($sheet, 'LogPurpose', 'Why students visit', DataSeries::TYPE_BARCHART, $first, $last, [2], 'E5', 'L22');
        }
        [, , $row] = $this->table($sheet, $row, 'Visits by program', ['Program', 'Visits'], $this->labelled($lb['by_program'], false));
        [$first, $last] = $this->table($sheet, $row, 'Entries per month', ['Month', 'Entries'],
            collect($lb['monthly'])->map(fn ($n, $m) => [$m, (int) $n])->values()->all());
        if ($last > $first) {
            $this->chart($sheet, 'LogMonthly', 'Entries per month', DataSeries::TYPE_LINECHART, $first, $last, [2], 'E24', 'P40');
        }
        $this->widths($sheet, [40, 12]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /** A new sheet with its title, description and the filters it was built with. */
    protected function sheet(Spreadsheet $book, string $name, string $title, string $description, array $r): Worksheet
    {
        $sheet = $book->createSheet();
        $sheet->setTitle(substr($name, 0, 31));
        $this->heading($sheet, $title, $description);
        $sheet->setCellValue('A3', 'Period: ' . $r['period']['range'] . '   ·   Term: ' . $r['period']['term']
            . '   ·   Generated ' . $r['generated_at']->format('M j, Y g:i A'));
        $sheet->getStyle('A3')->getFont()->setSize(9)->getColor()->setRGB(self::MUTED);

        return $sheet;
    }

    protected function heading(Worksheet $sheet, string $title, string $description): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB(self::BRAND);
        $sheet->setCellValue('A2', $description);
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getAlignment()->setWrapText(true)->setVertical('top');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setRGB(self::MUTED);
        $sheet->getRowDimension(2)->setRowHeight(42);
    }

    /**
     * Writes an optional heading, a styled header row and the data rows starting at $row.
     *
     * @return array{0: int, 1: int, 2: int} first data row, last data row, next free row
     */
    protected function table(Worksheet $sheet, int $row, ?string $heading, array $headers, array $rows, bool $boldLast = false): array
    {
        if ($heading) {
            $sheet->setCellValue('A' . $row, $heading);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $row++;
        }

        $lastCol = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->fromArray($headers, null, 'A' . $row);
        $header = $sheet->getStyle("A{$row}:{$lastCol}{$row}");
        $header->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $header->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::BRAND);

        $first = $row + 1;
        if ($rows === []) {
            $sheet->setCellValue('A' . $first, 'No data for this period.');
            $sheet->getStyle('A' . $first)->getFont()->setItalic(true)->getColor()->setRGB(self::MUTED);

            return [$first, $first - 1, $first + 2];
        }

        $sheet->fromArray(array_values(array_map('array_values', $rows)), null, 'A' . $first, true);
        $last = $first + count($rows) - 1;
        $sheet->getStyle("A{$first}:{$lastCol}{$last}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::LINE);
        $sheet->getStyle("A{$first}:{$lastCol}{$last}")->getBorders()->getInside()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::LINE);
        if ($boldLast) {
            $sheet->getStyle("A{$last}:{$lastCol}{$last}")->getFont()->setBold(true);
        }

        return [$first, $last, $last + 3];
    }

    /** Freeze the header, add filter arrows and format the date columns of a record list. */
    protected function finishList(Worksheet $sheet, string $range, int $first, int $last, array $dateCols, string $format = 'yyyy-mm-dd hh:mm'): void
    {
        $sheet->freezePane('A' . $first);
        if ($last >= $first) {
            $sheet->setAutoFilter($range);
            foreach ($dateCols as $col) {
                $sheet->getStyle("{$col}{$first}:{$col}{$last}")->getNumberFormat()->setFormatCode($format);
            }
        }
    }

    /**
     * An Excel chart over a table: categories in column A, one series per column in $valueCols
     * (1-based), each named after its header cell. Horizontal bars for bar charts.
     */
    protected function chart(Worksheet $sheet, string $name, string $title, string $type, int $first, int $last, array $valueCols, string $from, string $to, bool $stacked = false): void
    {
        $ref = "'" . $sheet->getTitle() . "'!";
        $count = $last - $first + 1;
        $col = fn (int $i) => Coordinate::stringFromColumnIndex($i);

        $labels = array_map(fn ($c) => new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, $ref . '$' . $col($c) . '$' . ($first - 1), null, 1), $valueCols);
        $values = array_map(fn ($c) => new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_NUMBER, "{$ref}\${$col($c)}\${$first}:\${$col($c)}\${$last}", null, $count), $valueCols);
        $categories = [new DataSeriesValues(DataSeriesValues::DATASERIES_TYPE_STRING, "{$ref}\$A\${$first}:\$A\${$last}", null, $count)];

        $grouping = $type === DataSeries::TYPE_LINECHART
            ? DataSeries::GROUPING_STANDARD
            : ($stacked ? DataSeries::GROUPING_STACKED : DataSeries::GROUPING_CLUSTERED);

        $series = new DataSeries($type, $grouping, range(0, count($values) - 1), $labels, $categories, $values);
        $series->setPlotDirection($type === DataSeries::TYPE_BARCHART ? DataSeries::DIRECTION_BAR : DataSeries::DIRECTION_COL);

        $chart = new Chart($name, new Title($title), count($values) > 1 ? new Legend(Legend::POSITION_BOTTOM, null, false) : null, new PlotArea(null, [$series]));
        $chart->setTopLeftPosition($from);
        $chart->setBottomRightPosition($to);
        $sheet->addChart($chart);
    }

    /** label => count map as rows, biggest first. */
    protected function labelled(array $map, bool $humanize = true): array
    {
        arsort($map);

        return collect($map)->map(fn ($n, $k) => [$humanize ? ucwords(str_replace('_', ' ', (string) $k)) : (string) $k, (int) $n])->values()->all();
    }

    protected function excelDate($value): ?float
    {
        return $value instanceof CarbonInterface ? Date::PHPToExcel($value) : null;
    }

    protected function pct(int $n, int $total): int
    {
        return $total > 0 ? (int) round($n / $total * 100) : 0;
    }

    protected function widths(Worksheet $sheet, array $widths): void
    {
        foreach ($widths as $i => $w) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1))->setWidth($w);
        }
    }
}
