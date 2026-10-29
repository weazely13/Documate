@use('Illuminate\Support\Str')
@use('App\Support\Charts\ReportPdfChart')
@php
    /*
     * The Reports page as a printable A4 document on the university letterhead, in Arial 12 pt.
     * Only what the Reports page shows, as it stood when downloaded — summary, chart, breakdown or
     * comparison, records with the Metrics columns — each with a plain-language note so the admin
     * reading it on paper knows what every part means.
     * dompdf: tables for layout, images by file path, SVG charts as <img>.
     */
    $p = $page;
    $sum = $p['summary'];
    $m = $meta;
    $tab = $m['tab'];

    $img = fn (string $path) => is_file(public_path($path)) ? public_path($path) : '';
    $svgImg = fn (string $svg) => '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" style="width:100%;display:block;" alt="">';
    $fmt = fn ($n) => number_format((int) $n);
    $pct = fn ($n, $d) => $d > 0 ? (int) round($n / $d * 100) : 0;

    // A change against the comparison: text, arrow and whether it is good news (the page's rule)
    $delta = function ($now, $before, bool $lowerIsBetter = false) {
        if ($before === null) {
            return null;
        }
        if ((int) $before === 0) {
            return (int) $now === 0 ? ['text' => 'No change', 'arrow' => '', 'tone' => 'flat'] : ['text' => 'New', 'arrow' => '▲', 'tone' => 'flat'];
        }
        $c = (int) round(($now - $before) / $before * 100);
        if ($c === 0) {
            return ['text' => 'Same', 'arrow' => '', 'tone' => 'flat'];
        }
        $good = $lowerIsBetter ? $c < 0 : $c > 0;

        return ['text' => abs($c) . '%', 'arrow' => $c > 0 ? '▲' : '▼', 'tone' => $good ? 'good' : 'bad', 'up' => $c > 0, 'pct' => abs($c)];
    };

    $reportName = ['forms' => 'Transaction forms', 'organizations' => 'Organizations', 'clearance' => 'Clearance'][$tab];
    $versus = collect($sum['tiles'])->contains(fn ($t) => $t['before'] !== null) ? $sum['versus'] : null;
    $comparison = $p['comparison'];
    $compareKind = $m['compare'];
    $compareLine = match ($compareKind) {
        'metric', 'item' => 'Compare ' . $p['compareTitle'],
        'previous', 'lastyear', 'custom' => $comparison ? $comparison['label'] : 'Nothing to compare with',
        default => $versus ?? 'Nothing',
    };
    $filters = array_values(array_filter([
        $tab === 'forms' ? ($sum['selectedForm']['label'] ?? null) : null,
        $tab === 'organizations' ? $sum['details'][2][1] ?? null : null,
        $tab === 'clearance' && $state['clearanceOrg'] ? $state['clearanceOrg'] : null,
        $tab === 'clearance' && $state['status'] ? 'Status: ' . ($state['status'] === 'none' ? 'Not tagged' : $state['status']) : null,
        $m['search'] ? 'Search: “' . $m['search'] . '”' : null,
    ], fn ($f) => $f && $f !== 'All organizations'));

    // The page's figure colours, as solid fills for print
    $tileColour = [
        'requests' => '#2A57B4', 'students' => '#0891B2', 'completed' => '#059669', 'returned' => '#E11D48',
        'orgs' => '#2A57B4', 'members' => '#0891B2', 'filed' => '#D97706', 'cleared' => '#059669',
        'Cleared' => '#059669', 'Pending' => '#D97706', 'Uncleared' => '#E11D48', 'Not tagged' => '#64748B',
    ];
    $tiles = collect($sum['tiles'])->keyBy('key');
    $v = fn (string $key) => (int) ($tiles[$key]['value'] ?? 0);

    // ---------- status pills in the records ----------
    $statusColour = function (?string $s) {
        return match (true) {
            in_array($s, ['Completed', 'Cleared'], true) => ['#DCFCE7', '#166534'],
            $s === 'Approved' => ['#DBEAFE', '#1E40AF'],
            in_array($s, ['Submitted', 'Under Review'], true) => ['#E0E7FF', '#3730A3'],
            in_array($s, ['Action Required', 'Pending'], true) => ['#FEF3C7', '#92400E'],
            in_array($s, ['Rejected', 'Voided', 'Uncleared'], true) => ['#FFE4E6', '#9F1239'],
            default => ['#F1F5F9', '#475569'],
        };
    };
    $statusCol = collect($m['metrics'])->search(fn ($k) => in_array($k, ['status', 'clearance'], true));
    $statusCol = $statusCol === false ? null : $statusCol + 1;
    $numericCols = collect($m['metrics'])->keys()->filter(fn ($i) => in_array($m['metrics'][$i], $m['numeric'], true))->map(fn ($i) => $i + 1)->values()->all();

    $cell = function ($val) {
        if ($val === null || $val === '') {
            return '—';
        }
        if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
            return \Illuminate\Support\Carbon::parse($val)->format('M j, Y');
        }
        if (is_numeric($val)) {
            return $val;
        }
        // degree names the way the university shortens them, so a program fits on one or two lines
        $val = preg_replace(['/^Bachelor of Science in /', '/^Bachelor of Arts in /', '/^Bachelor of /', '/^Master of Arts in /', '/^Master of /'], ['BS ', 'AB ', 'B. ', 'MA ', 'M. '], (string) $val);
        return Str::limit($val, 34);
    };
    $abbreviated = collect($table['shown'])->flatten()->contains(fn ($x) => is_string($x) && preg_match('/^(Bachelor|Master) of /', $x));

    $chartTitle = in_array($compareKind, ['metric', 'item'], true) && $p['compareTitle']
        ? 'Compare ' . $p['compareTitle']
        : ['forms' => 'Requests over time', 'organizations' => 'Member requests over time', 'clearance' => 'Clearance set over time'][$tab];
    $unitWord = $p['trend']['unit'] ?? 'period';
    $byUnit = $unitWord === 'day' ? 'day by day' : 'by ' . $unitWord;

    // ---------- terms used in the report, as the system defines them ----------
    $terms = match ($tab) {
        'forms' => [
            ['Submitted', 'The student filed it; the office has not started reviewing it yet.'],
            ['Under Review', 'The office and the signers are checking and signing it.'],
            ['Action Required', 'Sent back to the student to fix or add something.'],
            ['Approved', 'Every required signatory has approved it; final verification is next.'],
            ['Completed', 'Final verification is done and the official document may be issued.'],
            ['Rejected', 'Turned down by the office or a signer. It does not continue.'],
            ['Voided', 'A completed document the VPSD withdrew. It is kept for the record.'],
            ['Pending', 'A paper form waiting for the office.'],
        ],
        default => [
            ['Cleared', 'An officer checked the student and found nothing owed for the semester.'],
            ['Pending', 'An officer started checking the student but has not finished.'],
            ['Uncleared', 'Something is still owed or missing.'],
            ['Not tagged', 'No officer has checked the student yet this semester. This is not the same as Uncleared.'],
        ],
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $m['title'] }} · DocuMate</title>
    <style>
        @if ($fonts)
            @foreach ([['normal', 'normal', 'regular'], ['bold', 'normal', 'bold'], ['normal', 'italic', 'italic'], ['bold', 'italic', 'bolditalic']] as [$weight, $style, $file])
                @if (! empty($fonts[$file]))
                    @font-face { font-family: "ReportSans"; font-weight: {{ $weight }}; font-style: {{ $style }}; src: url("{{ $fonts[$file] }}") format("truetype"); }
                @endif
            @endforeach
        @endif

        /*
         * Styled after the sample internal-audit report: black text on white, bold black headings,
         * round bullets with a hanging indent, tables with a dark-navy header, grey group rows and
         * thin grey rules, a centred title and a small grey italic footer. A4 with print-safe margins.
         */
        @if ($brandFont)
            @font-face { font-family: "DocuMateBrand"; font-weight: normal; font-style: normal; src: url("{{ $brandFont }}") format("truetype"); }
        @endif

        /*
         * Line heights: dompdf multiplies line-height by the font's own height (1.22 em for Calibri),
         * so 0.95 here gives lines about 1.16 em apart, a little tighter than Word's single spacing.
         */
        @page { size: A4 portrait; margin: 36mm 18mm 18mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: ReportSans, Calibri, Carlito, "DejaVu Sans", sans-serif; color: #000; font-size: 11pt; line-height: 0.95; margin: 0; }
        p { margin: 0 0 1.6mm; }
        b, strong { font-weight: bold; }
        .muted { color: #595959; }

        /* ---------- letterhead and office line (every page) ---------- */
        .letterhead { position: fixed; top: -31mm; left: 0; right: 0; height: 27mm; }
        .lh-table { width: 100%; border-collapse: collapse; }
        .lh-table td { vertical-align: middle; padding: 0; }
        .lh-seal { width: 19mm; }
        .lh-seal img { width: 17mm; height: 17mm; }
        .lh-center { text-align: center; line-height: 1.15; }
        .lh-republic { font-style: italic; font-size: 9pt; color: #002060; }
        .lh-name { font-weight: bold; font-size: 15pt; color: #002060; }
        .lh-motto { font-size: 8.5pt; color: #002060; }
        .lh-address { font-size: 8.5pt; color: #404040; }
        .lh-rule { height: 2pt; background: #002060; margin-top: 1.6mm; }
        .lh-office { text-align: center; font-size: 9pt; font-weight: bold; color: #002060; margin-top: 1.2mm; }

        /* ---------- footer (every page): grey italics, as in the sample; the page number is stamped below it ---------- */
        .footer { position: fixed; bottom: -13mm; left: 0; right: 0; height: 9mm; }
        .ft-table { width: 100%; border-collapse: collapse; }
        .ft-table td { vertical-align: middle; padding: 0; font-size: 9pt; color: #7F7F7F; }
        .ft-title { text-align: center; font-style: italic; font-weight: bold; }
        .ft-side { width: 32mm; }
        /* the sidebar's brand: the DocuMate mark and the name in Gveret Levin, centred on each other */
        table.ft-brand { border-collapse: collapse; }
        table.ft-brand td { vertical-align: middle; padding: 0; }
        table.ft-brand img { width: 5mm; height: 5mm; display: block; }
        table.ft-brand td.ft-brand-name { font-family: DocuMateBrand, "DejaVu Sans", sans-serif; font-size: 12pt; line-height: 1; color: #2A57B4; padding-left: 1.2mm !important; }

        /* ---------- title, headings, paragraphs, bullets ---------- */
        /* the title is one line in one style: "Report on Transaction Forms | October 29, 2026" */
        .title-block { text-align: center; margin: 0 0 1.6mm; font-size: 14pt; font-weight: bold; }
        .title-block .sep { color: #A6A6A6; padding: 0 2.5mm; }
        /* the report details, two by two, without rules */
        table.meta { width: 100%; border-collapse: collapse; margin: 0 0 3mm; }
        table.meta td { width: 50%; padding: 0.3mm 2mm; font-size: 9.5pt; color: #404040; vertical-align: top; }
        table.meta td.l { text-align: right; }
        table.meta b { color: #000; }
        .intro { margin: 0 0 1mm; font-style: italic; }
        /* section titles, all alike: Summary, Results and Supporting Figures, Appendix 1, Appendix 2 */
        h1.part { font-size: 12pt; font-weight: bold; color: #002060; margin: 4mm 0 1.6mm; padding: 0 0 0.8mm; border-bottom: 1pt solid #002060; page-break-after: avoid; }
        h2 { font-size: 11pt; font-weight: bold; color: #000; margin: 3mm 0 0.8mm; page-break-after: avoid; }
        h3 { font-size: 10.5pt; font-weight: bold; color: #000; margin: 3mm 0 0.6mm; page-break-after: avoid; }
        ul.bul { margin: 0 0 1.2mm; padding: 0 0 0 8mm; list-style: none; }
        ul.bul li { position: relative; margin: 0 0 0.6mm; }
        ul.bul li:before { content: "\25CF"; font-family: "DejaVu Sans", sans-serif; font-size: 6.5pt; position: absolute; left: -5mm; top: 0.8mm; }
        .keep { page-break-inside: avoid; }
        .small { font-size: 9pt; }
        .note { font-size: 9pt; color: #404040; margin: 0 0 1mm; }
        .note.i, .legend, .footnote { font-style: italic; }

        /* ---------- tables, as in the sample: navy header, grey group rows, thin rules, tight rows ---------- */
        table.t { width: 100%; border-collapse: collapse; margin: 1mm 0 1.6mm; }
        table.t thead { display: table-header-group; }
        table.t th { background: #002060; color: #fff; font-weight: bold; font-size: 9pt; text-align: center; vertical-align: middle; padding: 0.8mm 1.4mm; border: 0.75pt solid #A6A6A6; line-height: 0.9; }
        table.t th .th-sub { display: block; font-size: 7.5pt; font-weight: normal; }
        table.t td { border: 0.75pt solid #A6A6A6; padding: 0.5mm 1.4mm; vertical-align: top; font-size: 9pt; line-height: 0.9; }
        table.t tr { page-break-inside: avoid; }
        table.t tr.group td { background: #D9D9D9; font-weight: bold; font-size: 9.5pt; }
        table.t tr.group .obj { font-size: 9pt; font-weight: normal; font-style: italic; }
        table.t tr.group .obj u { text-decoration: underline; }
        table.t tr.total td { font-weight: bold; background: #F2F2F2; }
        table.t td.c, table.t th.c { text-align: center; vertical-align: middle; }
        table.t td.n { text-align: right; white-space: nowrap; }
        table.t td.sub { padding-left: 5mm; }
        table.t .share { color: #595959; font-size: 8pt; }
        table.t .vs { display: block; color: #595959; font-size: 7.5pt; }
        table.t .area-obj { font-size: 8pt; color: #595959; }
        table.dense td, table.dense th { font-size: 8.5pt; padding: 0.4mm 1.2mm; }
        /* the headline and the four figures: an open table — navy rule on top, a pale-blue header, no grid */
        table.figs { width: 100%; border-collapse: collapse; margin: 1mm 0 2mm; table-layout: fixed; }
        table.figs th { background: #DEE6F4; color: #002060; font-size: 8.5pt; font-weight: bold; text-align: center; padding: 0.9mm 1.4mm; border-top: 1.5pt solid #002060; }
        table.figs td { text-align: center; vertical-align: top; padding: 1mm 1.6mm; font-size: 8pt; }
        table.figs tr.nums td { border-bottom: 0.5pt dotted #A6A6A6; }
        table.figs tr.help td { font-size: 7.5pt; color: #404040; border-bottom: 0.75pt solid #002060; text-align: left; }
        table.figs td + td, table.figs th + th { border-left: 0.5pt solid #D9D9D9; }
        table.figs .big { font-size: 15pt; font-weight: bold; line-height: 1; }
        .mark { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; font-weight: bold; }
        .legend { font-size: 8.5pt; margin: 0 0 0.3mm; }
        .legend .mark { font-size: 8.5pt; display: inline-block; width: 5mm; }
        .up { color: #00703C; font-weight: bold; } .down { color: #C00000; font-weight: bold; }
        .swatch { display: inline-block; width: 2.6mm; height: 2.6mm; margin-right: 1.2mm; }
        table.legend-t { border-collapse: collapse; margin: 0.6mm 0 1.6mm; }
        table.legend-t td { padding: 0 5mm 0 0; font-size: 9pt; }

        /* ---------- sign-off: straight after the breakdown table ---------- */
        .signoff-wrap { page-break-inside: avoid; margin-top: 2mm; }
        table.signoff { width: 100%; border-collapse: collapse; margin-top: 1mm; }
        table.signoff td { width: 50%; vertical-align: top; padding: 0 8mm 0 0; font-size: 11pt; }
        .so-line { border-bottom: 0.75pt solid #000; height: 10mm; }
        .so-name { font-weight: bold; margin-top: 1mm; }
        .appendix, .new-page { page-break-before: always; }
        /* a page holding only the word APPENDICES, centred */
        .appendices-page { page-break-before: always; page-break-after: always; text-align: center; padding-top: 108mm; font-size: 20pt; font-weight: bold; color: #002060; letter-spacing: 1.5pt; }
        .fit-mark { height: 0.5pt; }
        .footnote { font-size: 8.5pt; color: #404040; border-top: 0.75pt solid #A6A6A6; padding-top: 1.2mm; margin-top: 2.5mm; }
    </style>
</head>
<body>

    {{-- ================= LETTERHEAD ================= --}}
    <div class="letterhead">
        <table class="lh-table">
            <tr>
                <td class="lh-seal"><img src="{{ $img('images/lnulogo.png') }}" alt="Leyte Normal University seal"></td>
                <td class="lh-center">
                    <div class="lh-republic">Republic of the Philippines</div>
                    <div class="lh-name">LEYTE NORMAL UNIVERSITY</div>
                    <div class="lh-motto">Excellence • Integrity • Service</div>
                    <div class="lh-address">Paterno St., Tacloban City, 6500</div>
                </td>
                <td class="lh-seal" style="text-align:right;"><img src="{{ $img('images/osdas-logo-256.png') }}" alt="Office of Student Development and Auxiliary Services seal"></td>
            </tr>
        </table>
        <div class="lh-rule"></div>
        <div class="lh-office">OFFICE OF THE VICE PRESIDENT FOR STUDENT DEVELOPMENT</div>
    </div>

    {{-- ================= FOOTER ================= --}}
    <div class="footer">
        <table class="ft-table">
            <tr>
                <td class="ft-side"><table class="ft-brand"><tr><td><img src="{{ $img('images/favicon.png') }}" alt=""></td><td class="ft-brand-name">DocuMate</td></tr></table></td>
                <td class="ft-title">Report on {{ Str::title($reportName) }}</td>
                <td class="ft-side"></td>
            </tr>
        </table>
    </div>

    {{-- ================= OPENING: title, what the report covers, then what it is ================= --}}
    @php
        // the report details, laid out two by two
        $metaCells = array_chunk(array_values(array_filter([
            ['Period', $m['periodName'] . ', ' . $sum['rangeLabel']],
            ['Compared with', $compareLine],
            $tab !== 'forms' ? ['Semester', $p['semesterLabel'] ?? 'No semester'] : null,
            $tab === 'forms' || $filters ? ['Showing', $filters ? implode(' · ', $filters) : 'All forms'] : null,
            $signoff['preparedFor'] ? ['Prepared for', $signoff['preparedFor']] : null,
        ])), 2);
    @endphp
    @php
        /*
         * The summary keeps to page 1. Estimate its height from the wording (about 80 characters a
         * line at 11 pt across the bulleted width) and step the text size down until it fits.
         */
        $bullets = collect(['conclusion', 'context', 'actions'])->flatMap(fn ($k) => $draft[$k]);
        $sizes = [11, 10.5, 10, 9.5, 9];
        $step = 0;
        foreach ($sizes as $step => $pt) {
            $lines = $bullets->sum(fn ($b) => max(1, (int) ceil(mb_strlen($b) / (80 * 11 / $pt))));
            $height = $lines * $pt * 1.16 * 0.3528 + $bullets->count() * 0.6 + 3 * 8;
            if ($height <= 180) {
                break;
            }
        }
        // the measuring pass found the summary running onto page 2: one size smaller
        $fill = $fill ?? [];
        $summaryPt = $sizes[min(count($sizes) - 1, $step + (($fill['summary']['gap'] ?? 0) < 0 ? 1 : 0))];

        /*
         * Filling a page: the measuring pass reports the points left under a part ("gap") and the
         * part's height. Half goes between its entries (capped), the rest into its line spacing,
         * kept a little short of the margin so nothing tips onto the next page.
         */
        $spread = function (string $part, int $entries) use ($fill) {
            $gap = max(0, $fill[$part]['gap'] ?? 0);
            $height = max(1, $fill[$part]['height'] ?? 1);
            $each = $entries ? min(12, $gap * 0.5 / $entries) : 0;
            $rest = $gap - $each * $entries;

            return ['each' => round($each, 2), 'lh' => round(0.95 * min(1.6, 1 + $rest * 0.92 / $height), 3)];
        };
        $sFill = $spread('summary', $bullets->count() + 3);
    @endphp
    <div class="title-block">
        Report on {{ Str::title($reportName) }}<span class="sep">|</span>{{ $m['generatedAt']->format('F j, Y') }}
    </div>
    <table class="meta">
        @foreach ($metaCells as $row)
            <tr>
                @foreach ($row as $j => [$label, $value])<td class="{{ $j ? '' : 'l' }}"><b>{{ $label }}:</b> {{ $value }}</td>@endforeach
                @if (count($row) === 1)<td></td>@endif
            </tr>
        @endforeach
    </table>

    <p class="intro">This report summarises {{ strtolower($reportName) }} activity in DocuMate from {{ str_replace(' – ', ' to ', $sum['rangeLabel']) }}. It sets out what was reviewed, how each key area is doing, the context behind the figures, and the actions recommended for the office.</p>

    {{-- ================= PART 1: THE WRITTEN SUMMARY ================= --}}
    <div id="fit-summary-start" class="fit-mark"></div>
    <h1 class="part">Summary</h1>

    <div style="font-size: {{ $summaryPt }}pt; line-height: {{ $sFill['lh'] }};">
    <style>
        .sum h2 { margin-top: {{ 3 * 2.835 + $sFill['each'] }}pt; }
        .sum ul.bul li { margin-bottom: {{ 0.6 * 2.835 + $sFill['each'] }}pt; }
    </style>
    <div class="sum">
    <h2>Conclusion</h2>
    <ul class="bul">
        @foreach ($draft['conclusion'] as $line)<li>{{ $line }}</li>@endforeach
    </ul>

    <h2>Context and Key Considerations</h2>
    <ul class="bul">
        @foreach ($draft['context'] as $line)<li>{{ $line }}</li>@endforeach
    </ul>

    <h2>Recommended Actions</h2>
    <ul class="bul">
        @foreach ($draft['actions'] as $line)<li>{{ $line }}</li>@endforeach
    </ul>
    </div>
    </div>
    <div id="fit-summary-end" class="fit-mark"></div>

    {{-- ================= PART 2: RESULTS AND FIGURES (page 2: key areas; then the figures, breakdown and sign-off) ================= --}}
    @php
        // page 2's leftover space goes into the key-area rows, split above and below each row's text
        $rowPad = round(min(44, max(0, $fill['areas']['gap'] ?? 0) * 0.95 / (count($assessment) + 1)) / 2 + 0.5 * 2.835, 2);
    @endphp
    <h1 class="part new-page" id="fit-areas-start">Results and Supporting Figures</h1>

    <h2>Key Areas, Objectives, and Results</h2>
    <p class="note">The key areas reviewed, the objective of each, and its result. Results and priority levels follow fixed rules on the figures (see Appendix 1).</p>
    <table class="t">
        <thead><tr>
            <th style="text-align:left;">Key Areas Reviewed</th>
            <th class="c" style="width:14mm;">Results</th>
            <th class="c" style="width:22mm;">Priority Level<span class="th-sub">(see Appendix 1)</span></th>
            <th class="c" style="width:68mm;">Details</th>
        </tr></thead>
        <tr class="group">
            <td colspan="4" style="padding-top: {{ $rowPad }}pt; padding-bottom: {{ $rowPad }}pt;">
                I.&nbsp;&nbsp;{{ ['forms' => 'Transaction Requests', 'organizations' => 'Organizations and Members', 'clearance' => 'Semester Clearance'][$tab] }}<br>
                <span class="obj"><u>Objective</u>: {{ ['forms' => 'Review how transaction forms filed in the period were handled, from filing to completion.', 'organizations' => 'Review how organization members used the office and where they stand on clearance.', 'clearance' => 'Review how far semester clearance has progressed for active students.'][$tab] }}</span>
            </td>
        </tr>
        @foreach ($assessment as $i => $a)
            <tr class="area-row">
                <td class="sub">{{ chr(65 + $i) }}.&nbsp;&nbsp;{{ $a['area'] }} <span class="muted">({{ $a['valueText'] }})</span><br><span class="area-obj">{{ $a['objective'] }}</span></td>
                <td class="c"><span class="mark">{{ $a['result'] === 'ok' ? '✓' : 'O' }}</span></td>
                <td class="c">{{ $a['priority'] ?? '' }}</td>
                <td>{{ $draft['details'][$a['key']] ?? $a['basis'] }}</td>
            </tr>
        @endforeach
    </table>
    <p class="legend"><span class="mark">✓</span>Working as intended; no observation noted.</p>
    <p class="legend"><span class="mark">O</span>Observation noted. The corresponding priority level is shown in the Priority Level column.</p>
    <div id="fit-areas-end" class="fit-mark"></div>
    <style>table.t tr.area-row td { padding-top: {{ $rowPad }}pt; padding-bottom: {{ $rowPad }}pt; vertical-align: middle; }</style>

    <div class="keep">
    <h2 class="new-page">Supporting Figures</h2>
    <p class="note">The headline and the four figures shown on the Reports page{{ $versus ? ', each compared with ' . $versus : '' }}.</p>
    <table class="figs">
        <thead><tr>
            <th>Headline</th>
            @foreach ($sum['tiles'] as $t)<th>{{ $t['label'] }}</th>@endforeach
        </tr></thead>
        <tr class="nums">
            <td><span class="big">{{ $sum['headline'][0] }}</span><br>{{ $sum['headline'][1] }}</td>
            @foreach ($sum['tiles'] as $t)
                @php $d = $delta($t['value'], $t['before'], $t['lower']); @endphp
                <td>
                    <span class="big">{{ $fmt($t['value']) }}</span>
                    @if ($d)<br><span class="{{ $d['tone'] === 'good' ? 'up' : ($d['tone'] === 'bad' ? 'down' : 'muted') }}">{{ $d['arrow'] }} {{ $d['text'] }}</span> <span class="muted">({{ $fmt($t['before']) }} before)</span>
                    @elseif ($tab === 'clearance')<br><span class="muted">{{ $pct($t['value'], $p['studentTotal']) }}% of students</span>@endif
                </td>
            @endforeach
        </tr>
        <tr class="help">
            <td>{{ $sum['insight'] }}</td>
            @foreach ($sum['tiles'] as $t)<td>{{ $sum['help'][$t['label']][0] ?? '' }}</td>@endforeach
        </tr>
    </table>
    </div>

    @if ($m['showChart'])
        @php $hasData = collect($p['trend']['series'])->contains(fn ($s) => array_sum($s[3]) > 0); @endphp
        <div class="keep">
            <h3>{{ $chartTitle }}</h3>
            <p class="note">
                @if ($compareKind === 'metric')
                    Two figures side by side, each on its own scale: {{ $p['trend']['series'][0][1] }} on the left axis and {{ $p['trend']['series'][1][1] ?? '' }} on the right.
                @elseif ($compareKind === 'item')
                    The main figure of two {{ $tab === 'forms' ? 'forms' : 'organizations' }} on the same scale, {{ $byUnit }}.
                @elseif ($tab === 'clearance')
                    When this semester’s clearance statuses were set, {{ $byUnit }}.
                @else
                    {{ $tab === 'organizations' ? 'Requests filed by members, and how many members filed' : 'Requests filed, completed and returned' }}, {{ $byUnit }}.
                @endif
                @if ($comparison) The {{ $m['chartType'] === 'bar' ? 'paler bars' : 'dashed lines' }} show {{ $comparison['label'] }}. @endif
            </p>
            @if ($hasData)
                {!! $svgImg(ReportPdfChart::render($p['trend'], $comparison, $m['chartType'])) !!}
                <table class="legend-t"><tr>
                    @foreach ($p['trend']['series'] as [$key, $label, $colour, $values])
                        <td><span class="swatch" style="background:{{ $colour }};"></span>{{ $label }}: <b>{{ $fmt(array_sum($values)) }}</b>@if ($comparison && isset($comparison['series'][$key])) <span class="muted">(then {{ $fmt(array_sum(array_filter($comparison['series'][$key]))) }})</span>@endif</td>
                    @endforeach
                </tr></table>
            @else
                <p class="muted">{{ $tab === 'clearance' ? 'No clearance status has been set this semester yet.' : 'No requests were filed in this period.' }}</p>
            @endif
        </div>
    @endif

    @php
        [, , $itemHead, $bigHead, $smallHead] = $sum['list'];
        $bigTotal = max(1, $sum['listAll'][2]);
        $bd = $p['breakdown'];
    @endphp
    <h3>{{ $bd ? ($p['compareTitle'] ? 'Compare ' . $p['compareTitle'] : 'Comparison') : 'Breakdown by ' . strtolower($itemHead) }}</h3>
    @if ($bd)
        <p class="note">Each {{ strtolower($bd['item']) }} with its figures and its share of the column total.@if ($bd['thenLabel']) The line under each number is {{ $bd['thenLabel'] }} and the change.@endif</p>
        <table class="t">
            <thead><tr>
                <th style="text-align:left;">{{ $bd['item'] }}</th>
                @foreach ($bd['columns'] as $c)<th @if ($c['colour']) style="background:{{ $c['colour'] }};" @endif>{{ $c['label'] }}</th>@endforeach
            </tr></thead>
            <tr class="total">
                <td>Total</td>
                @foreach ($bd['columns'] as $c)
                    @php $now = $bd['totals'][$c['field']]; $was = $bd['thenTotals'][$c['field']] ?? null; $d = $was !== null ? $delta($now, $was) : null; @endphp
                    <td class="n">{{ $fmt($now) }}@if ($d)<span class="vs">then {{ $fmt($was) }} <span class="{{ $d['tone'] === 'good' ? 'up' : ($d['tone'] === 'bad' ? 'down' : '') }}">{{ $d['arrow'] }} {{ $d['text'] }}</span></span>@endif</td>
                @endforeach
            </tr>
            @foreach ($bd['rows'] as $r)
                <tr>
                    <td>@if ($r['colour'])<span class="swatch" style="background:{{ $r['colour'] }};"></span>@endif{{ $r['label'] }}</td>
                    @foreach ($bd['columns'] as $c)
                        @php $now = $r['values'][$c['field']]; $was = $r['then'][$c['field']] ?? null; $d = $was !== null ? $delta($now, $was) : null; $tot = $bd['totals'][$c['field']]; @endphp
                        <td class="n">{{ $now || $was ? $fmt($now) : '—' }} <span class="share">{{ $now && $tot ? round($now / $tot * 100) . '%' : '' }}</span>@if ($d && ($now || $was))<span class="vs">then {{ $fmt($was) }} <span class="{{ $d['tone'] === 'good' ? 'up' : ($d['tone'] === 'bad' ? 'down' : '') }}">{{ $d['arrow'] }} {{ $d['text'] }}</span></span>@endif</td>
                    @endforeach
                </tr>
            @endforeach
        </table>
    @elseif ($sum['listItems'])
        <table class="t">
            <thead><tr><th style="text-align:left;">{{ $itemHead }}</th><th>{{ $bigHead }}</th><th>Share</th><th>{{ $smallHead }}</th></tr></thead>
            <tr class="total"><td>{{ $sum['listAll'][1] }}</td><td class="n">{{ $fmt($sum['listAll'][2]) }}</td><td class="n">100%</td><td class="n">{{ $fmt($sum['listAll'][3]) }}</td></tr>
            @foreach ($sum['listItems'] as [$value, $label, $big, $small, $on])
                <tr><td>{{ $label }}@if ($on) <b>(selected)</b>@endif</td><td class="n">{{ $fmt($big) }}</td><td class="n">{{ round($big / $bigTotal * 100) }}%</td><td class="n">{{ $fmt($small) }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="muted">{{ $tab === 'forms' ? 'No forms were filed in this period.' : 'There are no active students yet.' }}</p>
    @endif

    {{-- ================= SIGN-OFF (straight after the breakdown) ================= --}}
    <div class="signoff-wrap">
        <h2>Sign-off</h2>
        <table class="signoff">
            <tr>
                <td>Prepared by:<div class="so-line"></div><div class="so-name">{{ $m['preparedBy'] }}</div>DocuMate administrator</td>
                <td>Noted by:<div class="so-line"></div><div class="so-name">{{ $signoff['notedBy'] ?: ' ' }}</div>{{ $signoff['notedByPosition'] ?: ' ' }}</td>
            </tr>
        </table>
    </div>

    {{-- ================= APPENDICES: a page with only the title, centred ================= --}}
    <div class="appendices-page">APPENDICES</div>

    {{-- ================= APPENDIX 1 (one page; its rows grow to fill it) ================= --}}
    @php
        $aPad = round(min(26, max(0, $fill['appendix']['gap'] ?? 0) * 0.9 / (2 + count($legend) + count($terms) + 3)) / 2 + 0.5 * 2.835, 2);
        $aCell = "padding-top: {$aPad}pt; padding-bottom: {$aPad}pt;";
    @endphp
    <h1 class="part" id="fit-appendix-start">Appendix 1: Definitions for Priority Levels and Follow-Up</h1>
    <table class="t">
        <thead><tr><th style="width:20mm;">Priority</th><th style="text-align:left;">What it means</th><th style="width:62mm; text-align:left;">Follow-up</th></tr></thead>
        <tr><td class="c" style="{{ $aCell }}"><b>High</b></td><td style="{{ $aCell }}">A key area is well past its expected level and is likely to affect students or the office’s work if left alone.</td><td style="{{ $aCell }}">Act within <b>one month</b> of the report date; check again in the next report.</td></tr>
        <tr><td class="c" style="{{ $aCell }}"><b>Medium</b></td><td style="{{ $aCell }}">A key area is below its expected level but not yet critical.</td><td style="{{ $aCell }}">Plan the fix within <b>the semester</b>.</td></tr>
    </table>
    <h3>How Each Key Area Is Rated</h3>
    <table class="t">
        <thead><tr><th style="text-align:left;">Key Area</th><th><span class="mark" style="font-size:8.5pt;">✓</span> No observation</th><th>Medium</th><th>High</th></tr></thead>
        @foreach ($legend as [$area, $ok, $medium, $high])
            <tr><td style="{{ $aCell }}"><b>{{ $area }}</b></td><td class="c" style="{{ $aCell }}">{{ $ok }}</td><td class="c" style="{{ $aCell }}">{{ $medium }}</td><td class="c" style="{{ $aCell }}">{{ $high }}</td></tr>
        @endforeach
    </table>
    <p class="note i">✓ means no observation. @if ($tab === 'forms')“Waiting work” counts requests not yet completed, rejected or voided that were filed more than {{ \App\Support\ReportAssessment::WAITING_DAYS }} days before the report date. @endif Areas without a rule (such as the change against the comparison) are for information and are always marked ✓.</p>

    <h3>Terms Used in This Report</h3>
    <table class="t">
        <thead><tr><th style="width:32mm; text-align:left;">Term</th><th style="text-align:left;">Meaning</th></tr></thead>
        @foreach ($terms as [$term, $meaning])<tr><td style="{{ $aCell }}"><b>{{ $term }}</b></td><td style="{{ $aCell }}">{{ $meaning }}</td></tr>@endforeach
    </table>

    <p class="footnote">{{ $draft['ai'] ? 'Note: The wording of the Conclusion, Details, Context and Recommended Actions was drafted by AI from this report’s totals only and reviewed by the preparer. Results and priority levels were set by the rules above, not by the AI.' : 'Note: The wording was written from the report’s figures and reviewed by the preparer. Results and priority levels were set by the rules above.' }}@if ($signoff['preparedFor']) This report was prepared for {{ $signoff['preparedFor'] }}.@endif</p>
    <div id="fit-appendix-end" class="fit-mark"></div>

    {{-- ================= APPENDIX 2 (optional) ================= --}}
    @if ($includeRecords)
        <h1 class="part appendix">Appendix 2: {{ $sum['records'][0] }}</h1>
        <p class="note">One row per {{ $sum['records'][2] }} ({{ $fmt($table['count']) }} in all), with the columns chosen on the Reports page{{ $m['search'] ? ', matching “' . $m['search'] . '”' : '' }}.@if ($abbreviated) BS is Bachelor of Science and AB is Bachelor of Arts.@endif @if ($table['count'] > $m['rowCap']) The first {{ $fmt($m['rowCap']) }} rows are shown; the CSV download has every row.@endif</p>
        @if ($table['count'])
            <table class="t dense">
                <thead><tr>@foreach ($table['head'] as $i => $h)<th>{{ $h }}</th>@endforeach</tr></thead>
                <tr class="total">@foreach ($table['totals'] as $i => $val)<td class="{{ in_array($i, $numericCols, true) ? 'n' : '' }}">{{ $val }}</td>@endforeach</tr>
                @foreach ($table['shown'] as $row)
                    <tr>@foreach ($row as $i => $val)<td class="{{ in_array($i, $numericCols, true) ? 'n' : '' }}">{{ $cell($val) }}</td>@endforeach</tr>
                @endforeach
            </table>
        @endif
    @endif
</body>
</html>
