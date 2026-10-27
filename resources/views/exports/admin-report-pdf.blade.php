@php
    // dompdf only renders SVG loaded as an image, so charts go in as base64 <img> tags.
    $svgImg = fn (string $svg) => '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '" style="width:100%;height:auto;display:block;" />';

    $wf = $report['workflow'];
    $ap = $report['appointments'];
    $cl = $report['clearance'];
    $ve = $report['verification'];
    $us = $report['users'];
    $lb = $report['logbook'];
    $cards = $report['overview']['cards'];
    $ins = $report['insights'];

    $pct = fn ($n, $d) => $d > 0 ? round($n / $d * 100) : 0;
    $attended = $ap['by_status']['attended'] ?? 0;
    $missed = $ap['by_status']['missed'] ?? 0;
    $delta = fn ($m) => $m['delta'] === null ? '—' : ($m['delta'] > 0 ? '+' : '') . $m['delta'] . '%';

    // label => count maps as [label, count] rows, biggest first
    $rows = function (array $map, bool $humanize = true) {
        arsort($map);
        return collect($map)->map(fn ($n, $k) => [$humanize ? ucwords(str_replace('_', ' ', (string) $k)) : (string) $k, (int) $n])->values()->all();
    };

    $orgs = collect($cl['by_organization'])->map(fn ($s, $org) => [
        'label' => $org, 'c' => (int) ($s['Cleared'] ?? 0), 'p' => (int) ($s['Pending'] ?? 0), 'u' => (int) ($s['Uncleared'] ?? 0),
    ])->sortByDesc(fn ($r) => $r['c'] + $r['p'] + $r['u'])->values();

    $listCap = 150;
    $hours = ['12–3a', '3–6a', '6–9a', '9–12n', '12–3p', '3–6p', '6–9p', '9–12m'];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 34px 34px 44px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0F172A; font-size: 9.5px; line-height: 1.4; }
        h1 { color: #0F172A; font-size: 19px; margin: 0; }
        h2 { font-size: 13px; color: #2A57B4; margin: 20px 0 2px; padding-bottom: 4px; border-bottom: 1.5px solid #2A57B4; }
        h3 { font-size: 10.5px; margin: 12px 0 4px; }
        .muted { color: #64748B; }
        .small { font-size: 8.5px; }
        .meta { color: #64748B; font-size: 9px; margin: 3px 0 10px; }
        .intro { background: #F8FAFC; border: 1px solid #E2E8F0; padding: 8px 10px; color: #334155; }
        .desc { color: #64748B; margin: 2px 0 8px; }

        table { border-collapse: collapse; width: 100%; }
        .stats td { width: 25%; border: 1px solid #E2E8F0; padding: 7px 8px; vertical-align: top; }
        .stats .label { color: #64748B; font-size: 8.5px; }
        .stats .value { font-size: 15px; font-weight: bold; margin-top: 2px; }
        .stats .note { color: #64748B; font-size: 8px; }

        table.data { margin-bottom: 8px; }
        table.data th { background: #2A57B4; color: #fff; font-weight: bold; text-align: left; padding: 4px 6px; font-size: 8.5px; }
        table.data td { border-bottom: 1px solid #E2E8F0; padding: 3.5px 6px; vertical-align: top; }
        table.data .num { text-align: right; white-space: nowrap; }
        table.data tr.total td { font-weight: bold; border-top: 1px solid #94A3B8; }
        table.list td, table.list th { font-size: 7.8px; padding: 3px 4px; }

        .bars td { padding: 2.5px 0; vertical-align: middle; }
        .bars .name { width: 38%; padding-right: 8px; }
        .bars .val { width: 12%; text-align: right; font-weight: bold; white-space: nowrap; }
        .track { width: 100%; background: #EEF2F6; height: 8px; }
        .track table { height: 8px; }
        .track td { padding: 0; height: 8px; }
        .legend span { display: inline-block; margin-right: 10px; }
        .dot { display: inline-block; width: 7px; height: 7px; margin-right: 3px; }

        .two td.col { width: 50%; vertical-align: top; }
        .two td.col:first-child { padding-right: 10px; }
        .two td.col:last-child { padding-left: 10px; }
        .page-break { page-break-before: always; }
        .empty { color: #94A3B8; font-style: italic; margin: 4px 0 8px; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 8px; color: #94A3B8; }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        <span>DocuMate · Leyte Normal University, VPSD Office · {{ $report['period']['range'] }}</span>
        <span style="float:right;">Page <span class="page"></span></span>
    </div>

    {{-- Horizontal bars. $series: [[key, label, color]], $items: rows with those keys plus 'label'. --}}
    @php
        $barTable = function (array $items, array $series, ?callable $value = null) {
            if (! $items) {
                return '<p class="empty">No data for this period.</p>';
            }
            $totals = array_map(fn ($r) => array_sum(array_map(fn ($s) => $r[$s[0]], $series)), $items);
            $max = max(1, max($totals));
            $html = '';
            if (count($series) > 1) {
                $html .= '<div class="legend small muted">';
                foreach ($series as [$k, $l, $c]) {
                    $html .= '<span><span class="dot" style="background:' . $c . '"></span>' . e($l) . '</span>';
                }
                $html .= '</div>';
            }
            $html .= '<table class="bars">';
            foreach ($items as $i => $r) {
                $total = $totals[$i];
                $html .= '<tr><td class="name">' . e($r['label']) . '</td><td><div class="track"><table style="width:' . max(0.5, $total / $max * 100) . '%"><tr>';
                foreach ($series as [$k, $l, $c]) {
                    if ($r[$k] > 0) {
                        $html .= '<td style="width:' . ($r[$k] / max(1, $total) * 100) . '%;background:' . $c . '"></td>';
                    }
                }
                $html .= '</tr></table></div></td><td class="val">' . e($value ? $value($r, $total) : number_format($total)) . '</td></tr>';
            }

            return $html . '</table>';
        };
        $simple = fn (array $pairs) => array_map(fn ($p) => ['label' => $p[0], 'n' => $p[1]], $pairs);
        $one = [['n', 'Count', '#2A57B4']];
    @endphp

    <h1>DocuMate report</h1>
    <div class="meta">
        Period: <strong>{{ $report['period']['range'] }}</strong> &nbsp;·&nbsp; Term: <strong>{{ $report['period']['term'] }}</strong>
        &nbsp;·&nbsp; Generated {{ $report['generated_at']->format('M j, Y g:i A') }}
    </div>
    <div class="intro">
        How documents, appointments and students moved in the period above. Each figure is compared with the same number
        of days just before. The <strong>period</strong> covers documents, appointments, accounts and logbook visits; the <strong>term</strong>
        covers clearance and verification. Full record lists are in the appendices
        (first {{ $listCap }} of each) and in the Excel download (all records).
    </div>

    {{-- ============ AT A GLANCE ============ --}}
    <h2>At a glance</h2>
    <table class="stats">
        <tr>
            @foreach ($ins['trend'] as $m)
                <td>
                    <div class="label">{{ $m['label'] }}</div>
                    <div class="value">{{ number_format($m['total']) }}</div>
                    <div class="note">{{ $delta($m) }} vs. previous ({{ number_format($m['previous']) }})</div>
                </td>
            @endforeach
        </tr>
        <tr>
            <td><div class="label">Completion rate</div><div class="value">{{ $wf['completion_rate'] }}%</div><div class="note">{{ $wf['completed'] }} of {{ $wf['total'] }} digital documents</div></td>
            <td><div class="label">Time to issue</div><div class="value">{{ $wf['avg_issue'] }}</div><div class="note">Average, submitted to completed</div></td>
            <td><div class="label">Attendance</div><div class="value">{{ $pct($attended, $attended + $missed) }}%</div><div class="note">{{ $attended }} attended · {{ $missed }} missed</div></td>
            <td><div class="label">Cleared</div><div class="value">{{ $cl['clearance_rate'] }}%</div><div class="note">{{ $cl['by_status']['Cleared'] ?? 0 }} of {{ $cl['total'] }} tagged · {{ $ve['verified_rate'] }}% verified</div></td>
        </tr>
    </table>

    @if (count($ins['trend'][0]['series']) > 1)
        <h3>Documents submitted over the period</h3>
        {!! $svgImg($service->renderLineChart([
            'labels' => array_column($ins['trend'][0]['series'], 'd'),
            'values' => array_column($ins['trend'][0]['series'], 'v'),
        ], ['width' => 720, 'height' => 190])) !!}
    @endif

    {{-- ============ DOCUMENTS ============ --}}
    <h2>Documents</h2>
    <p class="desc">Status and signer figures cover digital documents submitted in the period; requests by form include paper forms.</p>
    <table class="stats">
        <tr>
            <td><div class="label">Submitted</div><div class="value">{{ number_format($wf['total']) }}</div></td>
            <td><div class="label">Completed</div><div class="value">{{ number_format($wf['completed']) }}</div><div class="note">{{ $wf['completion_rate'] }}%</div></td>
            <td><div class="label">In progress</div><div class="value">{{ number_format($wf['in_progress']) }}</div></td>
            <td><div class="label">Rejected / voided</div><div class="value">{{ $wf['rejected'] }} / {{ $wf['voided'] }}</div><div class="note">{{ $wf['rejections']['review'] }} by VPSD · {{ $wf['rejections']['signer'] }} by signers</div></td>
        </tr>
    </table>

    <table class="two">
        <tr>
            <td class="col">
                <h3>Where documents stand</h3>
                {!! $barTable($simple($rows($wf['by_status'], false)), $one) !!}
            </td>
            <td class="col">
                <h3>Most requested documents</h3>
                {!! $barTable($ins['byType'], [['completed', 'Completed', '#2A57B4'], ['open', 'In progress', '#eb6834']]) !!}
            </td>
        </tr>
    </table>

    <h3>Signer performance</h3>
    @if ($wf['signers'])
        <table class="data">
            <tr><th>Role</th><th class="num">Signed</th><th class="num">Rejected</th><th class="num">Waiting</th><th>Avg. response</th><th>Oldest waiting</th></tr>
            @foreach ($wf['signers'] as $s)
                <tr><td>{{ $s['label'] }}</td><td class="num">{{ $s['signed'] }}</td><td class="num">{{ $s['rejected'] }}</td><td class="num">{{ $s['pending'] }}</td><td>{{ $s['avg_response'] }}</td><td>{{ $s['oldest_pending'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="empty">No signing activity in this period.</p>
    @endif

    <h3>Oldest unfinished documents</h3>
    @if ($wf['stuck'])
        <table class="data">
            <tr><th>Reference</th><th>Form</th><th>Status</th><th>Submitted</th></tr>
            @foreach ($wf['stuck'] as $s)
                <tr><td>{{ $s['code'] }}</td><td>{{ $s['name'] }}</td><td>{{ $s['status'] }}</td><td>{{ $s['since'] }}</td></tr>
            @endforeach
        </table>
    @else
        <p class="empty">Nothing is waiting.</p>
    @endif

    <h3>When documents arrive <span class="muted small">({{ $ins['times']['timezone'] }})</span></h3>
    @if ($ins['times']['total'])
        <p class="desc">
            Busiest:
            {{ collect($ins['times']['top'])->map(fn ($t) => $t['day'] . ' ' . $t['window'] . ' (' . $t['count'] . ')')->implode('; ') }}.
        </p>
        <table class="data">
            <tr><th>Day</th>@foreach ($hours as $h)<th class="num">{{ $h }}</th>@endforeach<th class="num">Total</th></tr>
            @foreach ($ins['times']['days'] as $d)
                <tr><td>{{ $d['name'] }}</td>@foreach ($d['bars'] as $n)<td class="num">{{ $n ?: '·' }}</td>@endforeach<td class="num"><strong>{{ array_sum($d['bars']) }}</strong></td></tr>
            @endforeach
        </table>
    @else
        <p class="empty">No submissions in this period.</p>
    @endif

    {{-- ============ APPOINTMENTS ============ --}}
    <h2>Appointments</h2>
    <p class="desc">Appointments booked in the period. The missed rate counts finished visits only (attended or missed).</p>
    <table class="stats">
        <tr>
            <td><div class="label">Booked</div><div class="value">{{ number_format($ap['total']) }}</div></td>
            <td><div class="label">Attended</div><div class="value">{{ number_format($attended) }}</div><div class="note">{{ $pct($attended, $attended + $missed) }}% of finished visits</div></td>
            <td><div class="label">Missed</div><div class="value">{{ $ap['missed_rate'] }}%</div><div class="note">{{ $missed }} no-shows</div></td>
            <td><div class="label">Rescheduled</div><div class="value">{{ number_format($ap['reschedule_count']) }}</div></td>
        </tr>
    </table>
    <table class="two">
        <tr>
            <td class="col"><h3>Outcome</h3>{!! $barTable($simple($rows($ap['by_status'])), $one) !!}</td>
            <td class="col"><h3>Morning or afternoon</h3>{!! $barTable($simple($rows($ap['by_session'])), $one) !!}</td>
        </tr>
    </table>
    @if (count($ins['visits'][0]['series']) > 1 && $ins['visits'][0]['total'])
        <h3>Visits scheduled over the period</h3>
        {!! $svgImg($service->renderLineChart([
            'labels' => array_column($ins['visits'][0]['series'], 'd'),
            'values' => array_column($ins['visits'][0]['series'], 'v'),
        ], ['width' => 720, 'height' => 170])) !!}
    @endif

    {{-- ============ STUDENTS ============ --}}
    <h2>Students</h2>
    <p class="desc">Clearance and verification follow the term ({{ $report['period']['term'] }}). New-account breakdowns cover accounts created in the period.</p>
    <table class="stats">
        <tr>
            <td><div class="label">Students and officers</div><div class="value">{{ number_format($cards['total_students']) }}</div><div class="note">{{ number_format($cards['active_accounts']) }} active</div></td>
            <td><div class="label">New accounts</div><div class="value">{{ number_format($us['total']) }}</div></td>
            <td><div class="label">Cleared</div><div class="value">{{ $cl['clearance_rate'] }}%</div><div class="note">{{ $cl['total'] }} tagged</div></td>
            <td><div class="label">Verification waiting</div><div class="value">{{ number_format($cards['pending_verifications']) }}</div><div class="note">{{ $ve['verified_rate'] }}% verified</div></td>
        </tr>
    </table>

    <h3>Clearance by organization</h3>
    @if ($orgs->isNotEmpty())
        <table class="data">
            <tr><th>Organization</th><th class="num">Cleared</th><th class="num">Pending</th><th class="num">Uncleared</th><th class="num">Total</th><th class="num">Cleared %</th></tr>
            @foreach ($orgs as $o)
                @php $t = $o['c'] + $o['p'] + $o['u']; @endphp
                <tr><td>{{ $o['label'] }}</td><td class="num">{{ $o['c'] }}</td><td class="num">{{ $o['p'] }}</td><td class="num">{{ $o['u'] }}</td><td class="num">{{ $t }}</td><td class="num"><strong>{{ $pct($o['c'], $t) }}%</strong></td></tr>
            @endforeach
        </table>
    @else
        <p class="empty">No students have been tagged for this term.</p>
    @endif

    <table class="two">
        <tr>
            <td class="col">
                <h3>Clearance status</h3>{!! $barTable($simple($rows($cl['by_status'], false)), $one) !!}
                <h3>Account verification</h3>{!! $barTable($simple($rows($ve['by_status'])), $one) !!}
                <h3>Accounts by role</h3>{!! $barTable($simple($rows($us['by_role'], false)), $one) !!}
            </td>
            <td class="col">
                <h3>New accounts by program</h3>{!! $barTable($simple($rows($us['by_program'], false)), $one) !!}
                <h3>New accounts by year level</h3>
                {!! $barTable(collect($us['by_year_level'])->map(fn ($n, $y) => ['label' => 'Year ' . $y, 'n' => (int) $n])->values()->all(), $one) !!}
            </td>
        </tr>
    </table>

    {{-- ============ LOGBOOK ============ --}}
    <h2>Front desk logbook</h2>
    @if ($lb['total_entries'])
        <p class="desc">{{ number_format($lb['total_entries']) }} walk-in visits in the period, from {{ $lb['total_uploads'] }} uploaded {{ \Illuminate\Support\Str::plural('logbook', $lb['total_uploads']) }}. The term does not apply: logbook rows only carry a date.</p>
        <table class="two">
            <tr>
                <td class="col"><h3>Why students visit</h3>{!! $barTable($simple($rows($lb['by_purpose'], false)), $one) !!}</td>
                <td class="col"><h3>Visits by program</h3>{!! $barTable($simple($rows($lb['by_program'], false)), $one) !!}</td>
            </tr>
        </table>
        @if (count($lb['monthly']) > 1)
            <h3>Entries per month</h3>
            {!! $svgImg($service->renderLineChart(['labels' => array_keys($lb['monthly']), 'values' => array_values($lb['monthly'])], ['width' => 720, 'height' => 170])) !!}
        @endif
    @else
        <p class="empty">No logbook entries are dated in this period.</p>
    @endif

    {{-- ============ APPENDIX A ============ --}}
    <div class="page-break"></div>
    <h2>Appendix A · Document register</h2>
    <p class="desc">
        Documents submitted in the period, newest first.
        @if (count($report['register']) > $listCap)
            Showing {{ $listCap }} of {{ number_format(count($report['register'])) }}; the Excel download has all of them.
        @else
            {{ count($report['register']) }} {{ \Illuminate\Support\Str::plural('document', count($report['register'])) }}.
        @endif
    </p>
    @if ($report['register'])
        <table class="data list">
            <tr><th>Reference</th><th>Form</th><th>Student</th><th>Program</th><th>Status</th><th>Submitted</th><th>Completed</th><th class="num">Days</th></tr>
            @foreach (array_slice($report['register'], 0, $listCap) as $d)
                <tr>
                    <td>{{ $d['code'] }}</td>
                    <td>{{ $d['document'] }}</td>
                    <td>{{ $d['student'] }}<br><span class="muted">{{ $d['student_number'] }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($d['program'], 36) }}</td>
                    <td>{{ $d['status'] }}</td>
                    <td>{{ $d['submitted']?->format('M j, Y') }}</td>
                    <td>{{ $d['completed']?->format('M j, Y') ?? '—' }}</td>
                    <td class="num">{{ $d['days'] ?? '—' }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="empty">No documents were submitted in this period.</p>
    @endif

    {{-- ============ APPENDIX B ============ --}}
    <div class="page-break"></div>
    <h2>Appendix B · Appointment list</h2>
    <p class="desc">
        Appointments booked in the period, by visit date.
        @if (count($report['appointment_list']) > $listCap)
            Showing {{ $listCap }} of {{ number_format(count($report['appointment_list'])) }}; the Excel download has all of them.
        @else
            {{ count($report['appointment_list']) }} {{ \Illuminate\Support\Str::plural('appointment', count($report['appointment_list'])) }}.
        @endif
    </p>
    @if ($report['appointment_list'])
        <table class="data list">
            <tr><th>Visit date</th><th>Session</th><th class="num">No.</th><th>Student</th><th>Purpose</th><th>Status</th><th class="num">Moved</th></tr>
            @foreach (array_slice($report['appointment_list'], 0, $listCap) as $a)
                <tr>
                    <td>{{ $a['date']?->format('M j, Y') }}</td>
                    <td>{{ $a['session'] }}</td>
                    <td class="num">{{ $a['queue'] ?? '—' }}</td>
                    <td>{{ $a['student'] }}<br><span class="muted">{{ $a['student_number'] }}</span></td>
                    <td>{{ \Illuminate\Support\Str::limit($a['purpose'], 60) }}</td>
                    <td>{{ $a['status'] }}</td>
                    <td class="num">{{ $a['reschedules'] }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <p class="empty">No appointments in this period.</p>
    @endif
</body>
</html>
