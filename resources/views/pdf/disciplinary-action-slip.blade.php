@extends('pdf.layout')

@section('doc_title', 'DISCIPLINARY ACTION SLIP FORM')

@section('extra_css')
    <style>
        .tight td { padding: 3pt 0; vertical-align: top; }
        .lbl { width: 170pt; font-weight: bold; }
        .cut { border-top: .8pt dashed #888; margin: 22pt 0 8pt; }
        .part { text-align: center; font-weight: bold; font-size: 11pt; letter-spacing: .5pt; margin: 14pt 0 12pt; }
    </style>
@endsection

@php
    $counselor = $signers[0] ?? [];
    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('F j, Y') : '';
    $fmtTime = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $t, 0, 5))->format('g:i A') : '';

    $offenseWhen = trim($fmtDate($v['offense_date'] ?? null) . (! empty($v['offense_time']) ? ' at ' . $fmtTime($v['offense_time']) : ''));

    $reporter = $reporterSigner;

    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
@endphp

@section('body')
    <table class="tight">
        <tr><td class="lbl">Name of Student:</td><td class="val">{{ $v['student_name'] ?? '' }}</td></tr>
        <tr><td class="lbl">Program, Year and Section:</td><td class="val">{{ $v['student_program'] ?? '' }}</td></tr>
        <tr><td class="lbl">Type of Offense:</td><td class="val">{{ $v['offense'] ?? '' }}</td></tr>
        <tr><td class="lbl">Date &amp; Time the Offense was Committed:</td><td class="val">{{ $offenseWhen }}</td></tr>
    </table>

    <table class="sigs" style="margin-top:22pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Reported by',
                    's' => $reporter,
                    'caption' => 'Name and Signature of the<br>Security Guard/Faculty/Personnel',
                ])
            </td>
        </tr>
    </table>

    <div class="cut"></div>
    <div class="part">VIOLATION CLEARANCE SLIP</div>

    <div style="font-weight:bold; font-size:10.5pt;">Guidance Counselling (every Wednesday)</div>
    <table class="tight" style="margin-top:4pt;">
        <tr><td style="width:40pt;">Date:</td><td class="val">{{ $fmtDate($v['counseling_date'] ?? null) }}</td></tr>
        <tr><td>Time:</td><td class="val">{{ trim($fmtTime($v['counseling_time'] ?? null) . (! empty($v['counseling_time_end']) ? ' to ' . $fmtTime($v['counseling_time_end']) : '')) }}</td></tr>
    </table>

    <table class="sigs" style="margin-top:26pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Counselling completed',
                    's' => $counselor,
                    'caption' => 'Name and Signature of the Guidance Counselor',
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>

    <div style="margin-top:16pt;">Date Issued: <span class="val">{{ $dateIssued }}</span></div>
@endsection