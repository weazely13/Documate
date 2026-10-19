@extends('pdf.layout')

@section('doc_title', 'DISCIPLINARY REFERRAL SLIP FORM')

@section('extra_css')
    <style>
        .tight td { padding: 3pt 0; vertical-align: top; }
        .lbl { width: 40pt; font-weight: bold; }
    </style>
@endsection

@php
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    $reporter = $reporterSigner;
@endphp

@section('body')
    <table class="tight">
        <tr><td class="lbl">Date:</td><td class="val">{{ $dateIssued }}</td></tr>
        <tr>
            <td class="lbl">To:</td>
            <td>
                Vice President for Student Development<br>
                This University
            </td>
        </tr>
    </table>

    <div class="statement" style="margin-top:18pt;">
        May I respectfully refer to your office for appropriate action
        <span class="val">{{ $v['student_name'] ?? '' }}</span>
        ({{ $v['student_program'] ?? '' }}) for the reason cited below:
    </div>

    <div class="box-label">Reason</div>
    <div class="box">{!! nl2br(e($v['reason'] ?? '')) !!}</div>

    <div style="margin-top:16pt;">Thank you.</div>

    <table class="sigs" style="margin-top:26pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Referred by',
                    's' => $reporter,
                    'caption' => 'Name and Signature of the<br>Faculty/Security Guard/Personnel',
                ])
            </td>
        </tr>
    </table>
@endsection