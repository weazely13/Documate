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
        @page { size: A4 portrait; margin: 37mm 18mm 18mm 18mm; }
        * { box-sizing: border-box; }
        body { font-family: ReportSans, Calibri, Carlito, "DejaVu Sans", sans-serif; color: #000; font-size: 12pt; line-height: 1.17; margin: 0; }
        p { margin: 0 0 2.2mm; }
        b, strong { font-weight: bold; }
        .muted { color: #595959; }

        /* ---------- letterhead (every page) ---------- */
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
        .ft-table td { vertical-align: top; padding: 0; font-size: 9pt; color: #7F7F7F; }
        .ft-brand img { width: 5mm; height: 5mm; vertical-align: middle; }
        .ft-brand span { font-weight: bold; color: #7F7F7F; vertical-align: middle; }
        .ft-title { text-align: center; font-style: italic; font-weight: bold; }
        .ft-side { width: 32mm; }

        /* ---------- headings, paragraphs, bullets ---------- */
        .intro { margin-bottom: 3mm; }
        .title-block { text-align: center; margin: 0 0 3mm; line-height: 1.2; }
        .title-pre { font-size: 12pt; }
        .title-main { font-size: 18pt; font-weight: bold; }
        h2 { font-size: 12pt; font-weight: bold; color: #000; margin: 4.5mm 0 1mm; page-break-after: avoid; }
        h3 { font-size: 12pt; font-weight: bold; color: #000; margin: 3.5mm 0 1mm; page-break-after: avoid; }
        ul.bul { margin: 0 0 2mm; padding: 0 0 0 9mm; list-style: none; }
        ul.bul li { position: relative; margin: 0 0 1mm; }
        ul.bul li:before { content: "\25CF"; font-family: "DejaVu Sans", sans-serif; font-size: 7.5pt; position: absolute; left: -5.5mm; top: 0.9mm; }
        ol.num { margin: 0 0 2mm; padding: 0 0 0 9mm; }
        ol.num li { margin: 0 0 1mm; }
        .keep { page-break-inside: avoid; }
        .small { font-size: 10pt; }
        table.t td.c, table.t td.n { font-size: 10pt; }

        /* ---------- tables, as in the sample ---------- */
        table.t { width: 100%; border-collapse: collapse; margin: 1.5mm 0 2mm; }
        table.t thead { display: table-header-group; }
        table.t th { background: #002060; color: #fff; font-weight: bold; font-size: 11pt; text-align: center; vertical-align: middle; padding: 1.6mm 2mm; border: 0.75pt solid #A6A6A6; }
        table.t th .th-sub { display: block; font-size: 9pt; font-weight: bold; }
        table.t td { border: 0.75pt solid #A6A6A6; padding: 1.2mm 2mm; vertical-align: top; font-size: 10pt; }
        table.t tr { page-break-inside: avoid; }
        table.t tr.group td { background: #D9D9D9; font-weight: bold; font-size: 11pt; }
        table.t tr.group .obj { font-size: 10pt; }
        table.t tr.group .obj { font-weight: normal; font-style: italic; }
        table.t tr.group .obj u { text-decoration: underline; }
        table.t tr.total td { font-weight: bold; background: #F2F2F2; }
        table.t td.c, table.t th.c { text-align: center; vertical-align: middle; }
        table.t td.n { text-align: right; white-space: nowrap; }
        table.t td.sub { padding-left: 7mm; }
        table.t .share { color: #595959; font-size: 9pt; }
        table.t .vs { display: block; color: #595959; font-size: 9pt; }
        table.dense td { font-size: 10pt; padding: 0.9mm 1.6mm; line-height: 1.15; }
        table.dense th { font-size: 10pt; padding: 1.2mm 1.6mm; }
        table.facts td { font-size: 10pt; padding: 1mm 2mm; }
        table.figs td.small { font-size: 9pt; line-height: 1.2; }
        table.facts td.k { width: 30mm; font-weight: bold; background: #F2F2F2; }
        .mark { font-family: "DejaVu Sans", sans-serif; font-size: 12pt; font-weight: bold; }
        .legend { font-size: 10pt; margin: 0 0 0.6mm; }
        .legend .mark { font-size: 10.5pt; display: inline-block; width: 6mm; }
        .up { color: #00703C; font-weight: bold; } .down { color: #C00000; font-weight: bold; }
        .swatch { display: inline-block; width: 3mm; height: 3mm; margin-right: 1.4mm; }
        table.legend-t { border-collapse: collapse; margin: 1mm 0 2mm; }
        table.legend-t td { padding: 0 5mm 0 0; font-size: 10pt; }

        /* ---------- sign-off ---------- */
        table.signoff { width: 100%; border-collapse: collapse; margin-top: 2mm; }
        table.signoff td { width: 50%; vertical-align: top; padding: 0 8mm 0 0; font-size: 12pt; }
        .so-line { border-bottom: 0.75pt solid #000; height: 11mm; }
        .so-name { font-weight: bold; margin-top: 1mm; }
        .footnote { font-size: 9pt; color: #404040; border-top: 0.75pt solid #A6A6A6; padding-top: 1.4mm; margin-top: 3mm; }
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
                <td class="ft-side ft-brand"><img src="{{ $img('images/DocumateLogo.png') }}" alt=""> <span>DocuMate</span></td>
                <td class="ft-title">Report on {{ Str::title($reportName) }}</td>
                <td class="ft-side"></td>
            </tr>
        </table>
    </div>

    {{-- ================= OPENING ================= --}}
    <p class="intro">This report summarises {{ strtolower($reportName) }} activity in DocuMate from {{ str_replace(' – ', ' to ', $sum['rangeLabel']) }}. It sets out what was reviewed, how each key area is doing, the context behind the figures, and the actions recommended for the office.</p>

    <div class="title-block">
        <div class="title-pre">Report on</div>
        <div class="title-main">{{ Str::title($reportName) }}</div>
        <div>{{ $m['generatedAt']->format('F j, Y') }}</div>
    </div>

    <table class="t facts">
        <tr><td class="k">Period</td><td>{{ $m['periodName'] }}, {{ $sum['rangeLabel'] }}</td><td class="k">Compared with</td><td>{{ $compareLine }}</td></tr>
        <tr>
            <td class="k">{{ $tab !== 'forms' ? 'Semester' : 'Showing' }}</td>
            <td>{{ $tab !== 'forms' ? ($p['semesterLabel'] ?? 'No semester') : ($filters ? implode(' · ', $filters) : 'All forms') }}</td>
            <td class="k">Prepared for</td><td>{{ $signoff['preparedFor'] ?: '—' }}</td>
        </tr>
        @if ($tab !== 'forms' && $filters)
            <tr><td class="k">Showing</td><td colspan="3">{{ implode(' · ', $filters) }}</td></tr>
        @endif
    </table>

    {{-- ================= CONCLUSION ================= --}}
    <h2>Conclusion</h2>
    <ul class="bul">
        @foreach ($draft['conclusion'] as $line)<li>{{ $line }}</li>@endforeach
    </ul>

    {{-- ================= KEY AREAS AND RESULTS ================= --}}
    <h2>Key Areas, Objectives, and Results</h2>
    <p>The table below presents the key areas reviewed, the objective of each, and its result. Results and priority levels follow fixed rules on the figures (see Appendix 1).</p>
    <table class="t">
        <thead><tr>
            <th style="text-align:left;">Key Areas Reviewed</th>
            <th class="c" style="width:17mm;">Results</th>
            <th class="c" style="width:27mm;">Priority Level<span class="th-sub">(See Appendix 1 for definitions)</span></th>
            <th class="c" style="width:62mm;">Details</th>
        </tr></thead>
        <tr class="group">
            <td colspan="4">
                I.&nbsp;&nbsp;{{ ['forms' => 'Transaction Requests', 'organizations' => 'Organizations and Members', 'clearance' => 'Semester Clearance'][$tab] }}<br>
                <span class="obj"><u>Objective</u>: {{ ['forms' => 'Review how transaction forms filed in the period were handled, from filing to completion.', 'organizations' => 'Review how organization members used the office and where they stand on clearance.', 'clearance' => 'Review how far semester clearance has progressed for active students.'][$tab] }}</span>
            </td>
        </tr>
        @foreach ($assessment as $i => $a)
            <tr>
                <td class="sub">{{ chr(65 + $i) }}.&nbsp;&nbsp;{{ $a['area'] }} <span class="muted">({{ $a['valueText'] }})</span><br><span class="small muted">{{ $a['objective'] }}</span></td>
                <td class="c"><span class="mark">{{ $a['result'] === 'ok' ? '✓' : 'O' }}</span></td>
                <td class="c">{{ $a['priority'] ?? '' }}</td>
                <td>{{ $draft['details'][$a['key']] ?? $a['basis'] }}</td>
            </tr>
        @endforeach
    </table>
    <p class="legend"><span class="mark">✓</span>Working as intended; no observation noted.</p>
    <p class="legend"><span class="mark">O</span>Observation noted. The corresponding priority level is shown in the Priority Level column.</p>

    {{-- ================= CONTEXT ================= --}}
    <h2>Context and Key Considerations</h2>
    <ul class="bul">
        @foreach ($draft['context'] as $line)<li>{{ $line }}</li>@endforeach
    </ul>

    {{-- ================= RECOMMENDED ACTIONS ================= --}}
    <h2>Recommended Actions</h2>
    <ul class="bul">
        @foreach ($draft['actions'] as $line)<li>{{ $line }}</li>@endforeach
    </ul>

    {{-- ================= SUPPORTING FIGURES ================= --}}
    <h2>Supporting Figures</h2>
    <p>The headline and the four figures shown on the Reports page{{ $versus ? ', each compared with ' . $versus : '' }}.</p>
    <table class="t keep figs">
        <thead><tr>
            <th>Headline</th>
            @foreach ($sum['tiles'] as $t)<th>{{ $t['label'] }}</th>@endforeach
        </tr></thead>
        <tr>
            <td class="c"><b style="font-size:15pt;">{{ $sum['headline'][0] }}</b><br><span class="small">{{ $sum['headline'][1] }}</span></td>
            @foreach ($sum['tiles'] as $t)
                @php $d = $delta($t['value'], $t['before'], $t['lower']); @endphp
                <td class="c">
                    <b style="font-size:15pt;">{{ $fmt($t['value']) }}</b>
                    @if ($d)<br><span class="small {{ $d['tone'] === 'good' ? 'up' : ($d['tone'] === 'bad' ? 'down' : 'muted') }}">{{ $d['arrow'] }} {{ $d['text'] }}</span> <span class="small muted">({{ $fmt($t['before']) }} before)</span>
                    @elseif ($tab === 'clearance')<br><span class="small muted">{{ $pct($t['value'], $p['studentTotal']) }}% of students</span>@endif
                </td>
            @endforeach
        </tr>
        <tr>
            <td class="small">{{ $sum['insight'] }}</td>
            @foreach ($sum['tiles'] as $t)<td class="small">{{ $sum['help'][$t['label']][0] ?? '' }}</td>@endforeach
        </tr>
    </table>

    @if ($m['showChart'])
        @php $hasData = collect($p['trend']['series'])->contains(fn ($s) => array_sum($s[3]) > 0); @endphp
        <div class="keep">
            <h3>{{ $chartTitle }}</h3>
            <p class="small">
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

    @php [, , $itemHead, $bigHead, $smallHead] = $sum['list']; $bigTotal = max(1, $sum['listAll'][2]); @endphp
    <h3>{{ $p['breakdown'] ? ($p['compareTitle'] ? 'Compare ' . $p['compareTitle'] : 'Comparison') : 'Breakdown by ' . strtolower($itemHead) }}</h3>
    @if ($p['breakdown'])
        @php $bd = $p['breakdown']; @endphp
        <p class="small">Each {{ strtolower($bd['item']) }} with its figures and its share of the column total.@if ($bd['thenLabel']) The line under each number is {{ $bd['thenLabel'] }} and the change. @endif</p>
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

    {{-- ================= SIGN-OFF ================= --}}
    <div class="keep">
        <h2>Sign-off</h2>
        <table class="signoff">
            <tr>
                <td>Prepared by:<div class="so-line"></div><div class="so-name">{{ $m['preparedBy'] }}</div>DocuMate administrator</td>
                <td>Noted by:<div class="so-line"></div><div class="so-name">{{ $signoff['notedBy'] ?: ' ' }}</div>{{ $signoff['notedByPosition'] ?: ' ' }}</td>
            </tr>
        </table>
    </div>

    {{-- ================= APPENDIX 1 ================= --}}
    <h2>Appendix 1: Definitions for Priority Levels and Follow-Up</h2>
    <table class="t">
        <thead><tr><th style="width:24mm;">Priority</th><th>Definition</th><th style="width:58mm;">Follow-up Timing</th></tr></thead>
        <tr>
            <td class="c"><b>High</b></td>
            <td><ul class="bul" style="margin:0;"><li>A key area is well past its expected level.</li><li>Generally requires <b><i>immediate</i></b> action, as it is likely to affect students or the office’s work if left alone.</li></ul></td>
            <td><ul class="bul" style="margin:0;"><li>Act within <b><i>one month</i></b> of the report date.</li><li>Check again in the next report.</li></ul></td>
        </tr>
        <tr>
            <td class="c"><b>Medium</b></td>
            <td><ul class="bul" style="margin:0;"><li>A key area is below its expected level but not yet critical.</li></ul></td>
            <td><ul class="bul" style="margin:0;"><li>Plan the fix within <b><i>the semester</i></b>.</li></ul></td>
        </tr>
    </table>
    <h3>How Each Key Area Is Rated</h3>
    <table class="t">
        <thead><tr><th style="text-align:left;">Key Area</th><th><span class="mark" style="font-size:10.5pt;">✓</span> No observation</th><th>Medium</th><th>High</th></tr></thead>
        @foreach ($legend as [$area, $ok, $medium, $high])
            <tr><td><b>{{ $area }}</b></td><td class="c">{{ $ok }}</td><td class="c">{{ $medium }}</td><td class="c">{{ $high }}</td></tr>
        @endforeach
    </table>
    @if ($tab === 'forms')<p class="small">“Waiting work” counts requests not yet completed, rejected or voided that were filed more than {{ \App\Support\ReportAssessment::WAITING_DAYS }} days before the report date. Areas without a rule (such as the change against the comparison) are for information and are always marked ✓.</p>@endif

    <h3>Terms Used in This Report</h3>
    <table class="t">
        <thead><tr><th style="width:40mm;">Term</th><th>Meaning</th></tr></thead>
        @foreach ($terms as [$term, $meaning])<tr><td><b>{{ $term }}</b></td><td>{{ $meaning }}</td></tr>@endforeach
    </table>

    <p class="footnote">{{ $draft['ai'] ? 'Note: The wording of the Conclusion, Details, Context and Recommended Actions was drafted by AI from this report’s totals only and reviewed by the preparer. Results and priority levels were set by the rules above, not by the AI.' : 'Note: The wording was written from the report’s figures and reviewed by the preparer. Results and priority levels were set by the rules above.' }}@if ($signoff['preparedFor']) This report was prepared for {{ $signoff['preparedFor'] }}.@endif</p>

    {{-- ================= APPENDIX 2 (optional) ================= --}}
    @if ($includeRecords)
        <h2>Appendix 2: {{ $sum['records'][0] }}</h2>
        <p class="small">One row per {{ $sum['records'][2] }} ({{ $fmt($table['count']) }} in all), with the columns chosen on the Reports page{{ $m['search'] ? ', matching “' . $m['search'] . '”' : '' }}.@if ($abbreviated) BS is Bachelor of Science and AB is Bachelor of Arts.@endif @if ($table['count'] > $m['rowCap']) The first {{ $fmt($m['rowCap']) }} rows are shown; the CSV download has every row.@endif</p>
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
