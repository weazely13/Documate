@extends('pdf.layout')

@section('doc_title', 'APPLICATION FOR STUDENT ASSISTANTSHIP FORM')

@section('extra_css')
    <style>
        /* Lower the footer: smaller bottom margin, footer sits closer to the page edge */
        @page { margin: 30pt 54pt 68pt 54pt; }
        .foot { bottom: -58pt; height: 54pt; padding-top: 6pt; }

        /* Tighten the body so the applicant's signature stays on page 1 */
        body { line-height: 1.35; }
        .rule { margin: 8pt 0 12pt; }
        .refline { margin: 4pt 0 12pt; }
        .sig-img { height: 34pt; line-height: 34pt; }
        table.sigs { page-break-inside: avoid; }

        table.sched { margin-top: 6pt; }
        table.sched th, table.sched td { border: .6pt solid #777; padding: 2.5pt 2pt; text-align: center; font-size: 7.5pt; vertical-align: middle; }
        table.sched th { font-weight: bold; background: #f3f3f3; }
        table.sched td.nb { border: none; width: 6pt; }
        table.sched td.day { text-align: left; font-weight: bold; width: 62pt; }
    </style>
@endsection

@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;
    $date = ($submittedAt ?? $issuedAt)?->format('F j, Y') ?? '';
    $left = ['mon_thu', 'tue_fri', 'wed'];
    $right = ['sat', 'sun'];
    $sum = fn (array $keys, string $h) => array_sum(array_map(
        fn ($k) => SA::halfHours($v, $k, $h) * SA::DAYS[$k][1],
        $keys
    ));
@endphp

@section('body')
    <div style="margin-top:4pt;">Date: <span class="val">{{ $date }}</span></div>
    <div style="margin-top:6pt; line-height:1.4;">
        <strong>{{ $v['vpsd_name'] ?? '' }}</strong><br>
        Vice President for Student Development<br>
        This University
    </div>

    <div style="margin-top:10pt;">Dear Sir/Ma'am,</div>

    <div class="statement" style="margin-top:6pt;">
        I am <span class="val">{{ $v['student_name'] ?? '' }}</span>, a
        <span class="val">{{ $v['student_program'] ?? '' }}</span> student of this university.
        I would like to apply as <strong>Student Assistant (SA)</strong> in which I shall render student
        assistantship services during my vacant periods. The total number of hours per week that I shall
        report to the office assigned to me shall be twelve (12) hours with no duty hours exceeding
        four (4) hours a day.
    </div>

    <div style="margin-top:8pt;">Below is my schedule of vacant periods.</div>

    <table class="sched">
        <tr>
            <th rowspan="2">Days</th><th colspan="4">Schedule of Vacant Time</th>
            <td class="nb" rowspan="2"></td>
            <th rowspan="2">Days</th><th colspan="4">Schedule of Vacant Time</th>
        </tr>
        <tr>
            <th>AM</th><th>VACANT HOURS</th><th>PM</th><th>VACANT HOURS</th>
            <th>AM</th><th>VACANT HOURS</th><th>PM</th><th>VACANT HOURS</th>
        </tr>
    @for ($i = 0; $i < 3; $i++)
        @php $l = $left[$i]; $r = $right[$i] ?? null; @endphp
        <tr>
            <td class="day">{{ SA::DAYS[$l][0] }}</td>
            <td>{{ SA::range($v, $l, 'am') }}</td><td>{{ SA::fmt(SA::halfHours($v, $l, 'am')) }}</td>
            <td>{{ SA::range($v, $l, 'pm') }}</td><td>{{ SA::fmt(SA::halfHours($v, $l, 'pm')) }}</td>
            <td class="nb"></td>
            @if ($r)
                <td class="day">{{ SA::DAYS[$r][0] }}</td>
                <td>{{ SA::range($v, $r, 'am') }}</td><td>{{ SA::fmt(SA::halfHours($v, $r, 'am')) }}</td>
                <td>{{ SA::range($v, $r, 'pm') }}</td><td>{{ SA::fmt(SA::halfHours($v, $r, 'pm')) }}</td>
            @else
                <td colspan="5" style="border:none;"></td>
            @endif
        </tr>
    @endfor
    <tr>
        <td class="day">TOTAL:</td>
        <td></td><td>{{ SA::fmt($sum($left, 'am')) }}</td><td></td><td>{{ SA::fmt($sum($left, 'pm')) }}</td>
        <td class="nb"></td>
        <td class="day">TOTAL:</td>
        <td></td><td>{{ SA::fmt($sum($right, 'am')) }}</td><td></td><td>{{ SA::fmt($sum($right, 'pm')) }}</td>
    </tr>
    </table>

    <div style="margin-top:5pt; font-size:7.5pt; color:#444;">
        Vacant hours are per day. "Monday &amp; Thursday" and "Tuesday &amp; Friday" each count for two days in the TOTAL.
        <strong>Total duty hours per week: {{ SA::fmt(SA::weeklyTotal($v)) }}</strong>
    </div>

    <table class="sigs" style="margin-top:24pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                <div class="sig-cap" style="text-align:left; margin-bottom:2pt;">Respectfully yours,</div>
                @include('pdf._signer', [
                    'heading' => 'Applicant',
                    's' => $studentSigner,
                    'caption' => 'Signature over Printed Name of the SA Applicant<br>Contact Number: <strong>' . e($v['contact_number'] ?? '') . '</strong>',
                ])
            </td>
        </tr>
    </table>
@endsection