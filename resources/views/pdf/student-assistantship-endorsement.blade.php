@extends('pdf.layout')

@section('doc_title', 'STUDENT ASSISTANTSHIP ENDORSEMENT FORM')

@section('extra_css')
    <style>
        @page { margin: 30pt 54pt 68pt 54pt; }
        .foot { bottom: -58pt; height: 54pt; padding-top: 6pt; }
        body { line-height: 1.35; }
        .rule { margin: 8pt 0 12pt; }
        .refline { margin: 4pt 0 12pt; }
        .sig-img { height: 34pt; line-height: 34pt; }

        table.sched { width: 62%; margin: 14pt auto 0; }
        table.sched th, table.sched td { border: .6pt solid #333; padding: 3pt 5pt; font-size: 8.5pt; text-align: center; vertical-align: middle; }
        table.sched th { font-weight: bold; background: #fff; }
        table.sched th.sub { font-weight: normal; }
        table.sched td.day { text-align: left; width: 38%; }
        table.sigs { page-break-inside: avoid; }
    </style>
@endsection

@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;

    $vp = $signers[0] ?? [];
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $start = ! empty($v['start_date']) ? \Carbon\Carbon::parse($v['start_date'])->format('F j, Y') : '';
    $signedOn = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    // The form lists three days only.
    $days = ['mon_thu', 'tue_fri', 'wed'];
@endphp

@section('body')
    <div style="text-align:right;">Date: <span class="val">{{ $dateIssued }}</span></div>

    <div style="margin-top:10pt;">Dear Sir/Ma'am:</div>

    <div class="statement" style="margin-top:8pt;">
        May I inform you that <span class="val">{{ $v['student_name'] ?? '' }}</span>, is a/an
        <span class="val">{{ $v['student_program'] ?? '' }}</span> student,
        is assigned as Student Assistant in the <span class="val">{{ $v['office_name'] ?? '' }}</span>
        starting <span class="val">{{ $start }}</span>.
    </div>

    <table class="sched">
        <tr>
            <th rowspan="2">Days</th>
            <th colspan="2">Schedule of Vacant Period</th>
        </tr>
        <tr>
            <th class="sub">AM</th>
            <th class="sub">PM</th>
        </tr>
        @foreach ($days as $k)
            <tr>
                <td class="day">{{ SA::DAYS[$k][0] }}</td>
                <td>{{ SA::range($v, $k, 'am') }}</td>
                <td>{{ SA::range($v, $k, 'pm') }}</td>
            </tr>
        @endforeach
    </table>

    @if (! empty($v['source_application']))
        <div style="margin-top:5pt; text-align:center; font-size:7pt; color:#777;">
            Based on application {{ $v['source_application'] }}
        </div>
    @endif

    <div style="margin-top:22pt; padding-left:50%;">Very truly yours,</div>

    <table class="sigs" style="margin-top:6pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Signed',
                    's' => $vp,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($signedOn),
                ])
            </td>
        </tr>
    </table>
@endsection