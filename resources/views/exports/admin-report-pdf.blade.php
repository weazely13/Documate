@php
    // dompdf does not render inline <svg>...</svg> markup in the page body — it only
    // understands SVG when it's loaded as an image. Wrapping each chart's SVG string
    // as a base64 data: URI <img> is what actually makes it render in the PDF; without
    // this the SVG's shapes are silently dropped and only its <text> nodes flow through
    // as plain paragraph text (which is the "broken" look this replaces).
    $svgImg = fn (string $svg) => '<img src="data:image/svg+xml;base64,' . base64_encode($svg)
        . '" style="width:100%;height:auto;display:block;" />';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 28px 32px; }
        body { font-family: Arial, Helvetica, sans-serif; color: #0F172A; font-size: 11px; }
        h1 { color: #2A57B4; font-size: 20px; margin: 0 0 2px; }
        .subtitle { color: #64748B; font-size: 10px; margin-bottom: 6px; }
        .intro {
            background: #F8FBFF; border: 1px solid #E2E8F0; border-radius: 6px;
            padding: 10px 12px; margin: 10px 0 18px; font-size: 10.5px; color: #334155; line-height: 1.5;
        }
        .section-title {
            font-size: 14px; font-weight: bold; color: #2A57B4;
            border-bottom: 2px solid #2A57B4; padding-bottom: 4px; margin: 22px 0 4px;
        }
        .section-desc { font-size: 10px; color: #64748B; margin: 0 0 10px; line-height: 1.45; }
        .cards { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .cards td {
            width: 25%; padding: 10px; border: 1px solid #E2E8F0; background: #F8FBFF;
            text-align: center; vertical-align: top;
        }
        .cards .value { font-size: 18px; font-weight: bold; color: #2A57B4; display: block; }
        .cards .label { font-size: 9px; color: #64748B; text-transform: uppercase; letter-spacing: .04em; }
        .chart-box {
            border: 1px solid #E2E8F0; border-radius: 6px; padding: 8px;
            display: inline-block; width: 100%; margin-bottom: 12px;
        }
        .two-col { width: 100%; }
        .two-col td { width: 50%; vertical-align: top; padding-right: 8px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th, table.data td {
            border: 1px solid #E2E8F0; padding: 5px 8px; text-align: left; font-size: 10px;
        }
        table.data th { background: #2A57B4; color: #fff; }
        table.data td.num { text-align: right; }
        .note { font-size: 9px; color: #94A3B8; font-style: italic; margin: -4px 0 14px; }
        .empty-note { font-size: 10px; color: #94A3B8; font-style: italic; margin: 4px 0 14px; }
        .footer { margin-top: 24px; font-size: 8px; color: #94A3B8; text-align: center; }
    </style>
</head>
<body>
    <h1>DocuMate — Administrative Report</h1>
    <div class="subtitle">
        Range: {{ $report['filters']['date_from'] ?? '—' }} to {{ $report['filters']['date_to'] ?? '—' }}
        &nbsp;|&nbsp; AY: {{ $report['filters']['academic_year'] ?? 'All' }}
        &nbsp;|&nbsp; Semester: {{ $report['filters']['semester'] ?? 'All' }}
        &nbsp;|&nbsp; Generated {{ $report['generated_at']->format('M d, Y h:i A') }}
    </div>

    <div class="intro">
        This report summarizes activity in the VPSD student services system for the range above. The
        <strong>Range</strong> filter covers Users, Transactions, Appointments and Logbook; the
        <strong>AY / Semester</strong> filter covers Clearance and Verification, which are tracked by academic
        term rather than by date. Each section below pairs a short explanation with its chart and figures so it
        can be read on its own, without the dashboard.
    </div>

    {{-- OVERVIEW CARDS --}}
    <div class="section-title">At a Glance</div>
    <p class="section-desc">
        The headline numbers for the selected range: how many people are registered, how document requests and
        appointments are moving, and how clean the clearance queue looks.
    </p>
    <table class="cards">
        <tr>
            <td><span class="value">{{ $report['overview']['cards']['total_students'] }}</span><span class="label">Students / Officers</span></td>
            <td><span class="value">{{ $report['overview']['cards']['active_accounts'] }}</span><span class="label">Active Accounts</span></td>
            <td><span class="value">{{ $report['overview']['cards']['pending_verifications'] }}</span><span class="label">Pending Verification</span></td>
            <td><span class="value">{{ $report['overview']['cards']['clearance_rate'] }}%</span><span class="label">Clearance Rate</span></td>
        </tr>
        <tr>
            <td><span class="value">{{ $report['overview']['cards']['total_transactions'] }}</span><span class="label">Transactions</span></td>
            <td><span class="value">{{ $report['overview']['cards']['completed_transactions'] }}</span><span class="label">Completed</span></td>
            <td><span class="value">{{ $report['overview']['cards']['total_appointments'] }}</span><span class="label">Appointments</span></td>
            <td><span class="value">{{ $report['overview']['cards']['todays_appointments'] }}</span><span class="label">Today's Appointments</span></td>
        </tr>
    </table>

    @if(count($report['overview']['charts']['registration_trend']['labels']) > 1)
        <p class="section-desc" style="margin-top:10px;">New account registrations by month, for the selected date range.</p>
        <div class="chart-box">
            {!! $svgImg($service->renderLineChart($report['overview']['charts']['registration_trend'], ['width' => 700, 'height' => 220])) !!}
        </div>
    @endif

    {{-- USERS --}}
    <div class="section-title">User Base</div>
    <p class="section-desc">
        Everyone registered in DocuMate as of the selected range, split by role (student, officer, staff) and by
        account status. A large inactive or suspended share is worth a closer look at account upkeep.
    </p>
    <table class="two-col">
        <tr>
            <td>
                @if(count($report['users']['charts']['by_role']['labels']))
                    <div class="chart-box">{!! $svgImg($service->renderPieChart($report['users']['charts']['by_role'], ['width' => 340, 'height' => 190])) !!}</div>
                @endif
            </td>
            <td>
                @if(count($report['users']['charts']['by_status']['labels']))
                    <div class="chart-box">{!! $svgImg($service->renderPieChart($report['users']['charts']['by_status'], ['width' => 340, 'height' => 190])) !!}</div>
                @endif
            </td>
        </tr>
    </table>
    @if(count($report['users']['by_program'] ?? []))
        <p class="section-desc">Programs with the most registered students in this range.</p>
        <table class="data">
            <tr><th>Program</th><th style="text-align:right;">Students</th></tr>
            @foreach($report['users']['by_program'] as $program => $count)
                <tr><td>{{ $program }}</td><td class="num">{{ $count }}</td></tr>
            @endforeach
        </table>
    @endif

    {{-- TRANSACTIONS --}}
    <div class="section-title">Document Transactions</div>
    <p class="section-desc">
        Document requests filed through DocuMate. <strong>Completed</strong> means the requested document was
        released; <strong>Avg. Completion Time</strong> is the average hours between a request being filed and
        completed, for requests that finished in this range.
    </p>
    <table class="two-col">
        <tr>
            <td>
                @if(count($report['transactions']['charts']['by_status']['labels']))
                    <div class="chart-box">{!! $svgImg($service->renderPieChart($report['transactions']['charts']['by_status'], ['width' => 340, 'height' => 190])) !!}</div>
                @endif
            </td>
            <td>
                @if(count($report['transactions']['charts']['by_template']['labels']))
                    <div class="chart-box">{!! $svgImg($service->renderBarChart($report['transactions']['charts']['by_template'], ['width' => 340, 'height' => 190])) !!}</div>
                @endif
            </td>
        </tr>
    </table>
    <table class="data">
        <tr><th>Total</th><th>Completed</th><th>Pending</th><th>Avg. Completion (hrs)</th></tr>
        <tr>
            <td>{{ $report['transactions']['total'] }}</td>
            <td>{{ $report['transactions']['completed'] }}</td>
            <td>{{ $report['transactions']['pending'] }}</td>
            <td>{{ $report['transactions']['avg_completion_hours'] ?? '—' }}</td>
        </tr>
    </table>

    {{-- APPOINTMENTS --}}
    <div class="section-title">Appointments</div>
    <p class="section-desc">
        Appointments booked in this range. <strong>Missed Rate</strong> is calculated against resolved
        appointments only (attended + missed) — upcoming or still-pending bookings are excluded so the rate isn't
        skewed by appointments that haven't happened yet.
    </p>
    <table class="two-col">
        <tr>
            <td>
                @if(count($report['appointments']['charts']['by_status']['labels']))
                    <div class="chart-box">{!! $svgImg($service->renderPieChart($report['appointments']['charts']['by_status'], ['width' => 340, 'height' => 190])) !!}</div>
                @endif
            </td>
            <td>
                @if(count($report['appointments']['charts']['by_session']['labels']))
                    <div class="chart-box">{!! $svgImg($service->renderPieChart($report['appointments']['charts']['by_session'], ['width' => 340, 'height' => 190])) !!}</div>
                @endif
            </td>
        </tr>
    </table>
    <table class="data">
        <tr><th>Total</th><th>Missed Rate</th><th>Reschedules in Range</th></tr>
        <tr>
            <td>{{ $report['appointments']['total'] }}</td>
            <td>{{ $report['appointments']['missed_rate'] }}%</td>
            <td>{{ $report['appointments']['reschedule_count'] }}</td>
        </tr>
    </table>
    @if(count($report['appointments']['charts']['per_day']['labels'] ?? []) > 1)
        <p class="section-desc">Bookings per day — useful for spotting which days need more slots or staff coverage.</p>
        <div class="chart-box">
            {!! $svgImg($service->renderLineChart($report['appointments']['charts']['per_day'], ['width' => 700, 'height' => 200])) !!}
        </div>
    @endif

    {{-- CLEARANCE --}}
    <div class="section-title">Clearance Monitoring</div>
    <p class="section-desc">
        Students tagged for clearance in the selected academic year and semester, by current status. This section
        follows the <strong>AY / Semester</strong> filter, not the date range above.
    </p>
    @if(count($report['clearance']['charts']['by_status']['labels']))
        <div class="chart-box">{!! $svgImg($service->renderPieChart($report['clearance']['charts']['by_status'], ['width' => 640, 'height' => 170])) !!}</div>
    @endif
    <table class="data">
        <tr><th>Total Tagged</th><th>Clearance Rate</th></tr>
        <tr><td>{{ $report['clearance']['total'] }}</td><td>{{ $report['clearance']['clearance_rate'] }}%</td></tr>
    </table>

    {{-- VERIFICATION --}}
    <div class="section-title">Enrollment Verification (e-slip)</div>
    <p class="section-desc">
        e-Slip submissions for the selected academic year and semester, by review status. This section also
        follows the <strong>AY / Semester</strong> filter, not the date range above.
    </p>
    @if(count($report['verification']['charts']['by_status']['labels']))
        <div class="chart-box">{!! $svgImg($service->renderPieChart($report['verification']['charts']['by_status'], ['width' => 640, 'height' => 170])) !!}</div>
    @endif
    <table class="data">
        <tr><th>Total Submitted</th><th>Verified Rate</th></tr>
        <tr><td>{{ $report['verification']['total'] }}</td><td>{{ $report['verification']['verified_rate'] }}%</td></tr>
    </table>

    {{-- LOGBOOK --}}
    @if($report['logbook']['total_uploads'] > 0)
        <div class="section-title">Front Desk Logbook</div>
        <p class="section-desc">
            Walk-in visits pasted in from the paper front-desk logbook. This section always shows the full
            lifetime history — logbook entries carry their own free-text dates and are not affected by the
            filters above.
        </p>
        <table class="data">
            <tr><th>Uploads</th><th>Total Entries</th></tr>
            <tr><td>{{ $report['logbook']['total_uploads'] }}</td><td>{{ $report['logbook']['total_entries'] }}</td></tr>
        </table>
        {{-- Stacked full-width rather than side-by-side: up to 10 categories each,
             too many to fit legibly at half the page width even with angled labels. --}}
        @if(count($report['logbook']['charts']['by_purpose']['labels']))
            <p class="section-desc">Most common reasons for a visit (top 10).</p>
            <div class="chart-box">{!! $svgImg($service->renderBarChart($report['logbook']['charts']['by_purpose'], ['width' => 640, 'height' => 230])) !!}</div>
        @endif
        @if(count($report['logbook']['charts']['by_program']['labels']))
            <p class="section-desc">Programs with the most logbook entries (top 10).</p>
            <div class="chart-box">{!! $svgImg($service->renderBarChart($report['logbook']['charts']['by_program'], ['width' => 640, 'height' => 230])) !!}</div>
        @endif
        @if(count($report['logbook']['charts']['monthly']['labels']) > 1)
            <p class="section-desc">Entries per month across the entire uploaded history.</p>
            <div class="chart-box">
                {!! $svgImg($service->renderLineChart($report['logbook']['charts']['monthly'], ['width' => 700, 'height' => 200])) !!}
            </div>
        @endif
    @else
        <div class="section-title">Front Desk Logbook</div>
        <p class="empty-note">No logbook has been uploaded yet, so this section is empty.</p>
    @endif

    <div class="footer">Generated by DocuMate — Leyte Normal University, VPSD Office</div>
</body>
</html>