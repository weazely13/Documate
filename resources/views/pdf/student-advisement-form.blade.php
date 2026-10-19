@extends('pdf.layout')

@section('doc_title', 'STUDENT ADVISEMENT FORM')

@section('extra_css')
    <style>
        .tight td { padding: 3pt 0; vertical-align: top; }
        .lbl { width: 70pt; font-weight: bold; }
        .cut { border-top: .8pt dashed #888; margin: 22pt 0 8pt; }
        .line { border-bottom: .6pt solid #555; height: 14pt; }
    </style>
@endsection

@php
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $reportDate = ! empty($v['haircut_report_date']) ? \Carbon\Carbon::parse($v['haircut_report_date'])->format('F j, Y') : '';
    $reporter = $reporterSigner;
    $insp = $signers[0] ?? [];
    $inspDate = ! empty($v['inspection_date']) ? \Carbon\Carbon::parse($v['inspection_date'])->format('F j, Y') : '';
@endphp

@section('body')
    <div style="text-align:right;">Date: <span class="val">{{ $dateIssued }}</span></div>

    <table class="tight" style="margin-top:8pt;">
        <tr><td class="lbl">Name of Student:</td><td class="val">{{ $v['student_name'] ?? '' }}</td></tr>
    </table>

    <div class="statement" style="margin-top:10pt;">
        {!! $tick(! empty($v['advise_doctor'])) !!} Advised to see the University Doctor for
        <span class="val">{{ ! empty($v['advise_doctor']) ? ($v['doctor_reason'] ?? '') : '' }}</span>.
    </div>

    <div class="statement" style="margin-top:6pt;">
        {!! $tick(! empty($v['advise_haircut'])) !!} Advised to have haircut/mustache shave and to report to the VPSD on
        <span class="val">{{ ! empty($v['advise_haircut']) ? $reportDate : '' }}</span> for further inspection.
    </div>

    <div class="statement" style="margin-top:6pt;">
        {!! $tick(! empty($v['advise_others'])) !!} Others, pls. specify:
        <span class="val">{{ ! empty($v['advise_others']) ? ($v['others_specify'] ?? '') : '' }}</span>
    </div>

    <table class="sigs" style="margin-top:24pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Advised by',
                    's' => $reporter,
                    'caption' => 'Name &amp; Signature of Faculty/<br>Security Guard/Personnel',
                ])
            </td>
        </tr>
    </table>

    <div class="cut"></div>

    <table class="tight">
        <tr><td class="lbl" style="width:120pt;">Student's Name:</td><td class="val">{{ $v['student_name'] ?? '' }}</td></tr>
        <tr><td class="lbl" style="width:120pt;">Program, Year and Section:</td><td class="val">{{ $v['student_program'] ?? '' }}</td></tr>
    </table>

    {{-- Filled in by hand by the VPSD at the inspection --}}
    <table class="tight" style="margin-top:14pt;">
        <tr><td class="lbl" style="width:120pt;">Date:</td><td class="val">{{ $inspDate }}</td></tr>
        <tr><td class="lbl" style="width:120pt;">Remarks:</td><td>{!! nl2br(e($v['inspection_remarks'] ?? '')) !!}</td></tr>
    </table>

    @if (! empty($v['advise_haircut']))
        <table class="sigs" style="margin-top:14pt;">
            <tr>
                <td style="width:50%;"></td>
                <td style="width:50%; text-align:center;">
                    @include('pdf._signer', [
                        'heading' => 'Inspected by',
                        's' => $insp,
                        'caption' => 'Name and Signature of the<br>Vice President for Student Development',
                    ])
                </td>
            </tr>
        </table>
    @endif
@endsection