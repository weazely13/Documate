@extends('pdf.layout')

@section('doc_title', 'REQUEST FOR RENEWAL/REPRINTING OF STUDENT ID')

@section('extra_css')
    <style>
        body { line-height: 1.35; } 
        .rule { margin: 8pt 0 10pt; } 
        .refline { margin: 4pt 0 10pt; } 
        .sig-img { height: 34pt; line-height: 34pt; } 
        .tight td { padding: 1pt 0; }
    </style>

@endsection

@php
    $vp = $signers[0] ?? [];
    $approved = ! empty($vp['signed_at']);
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $date = ! empty($submittedAt) ? $submittedAt->format('F j, Y') : '';
    $signedOn = $approved ? $vp['signed_at']->format('F j, Y') : '';
    $studentSig = [
        'name' => $v['student_name'] ?? '',
        'signature' => $studentSignature ?? null,
        'signed_at' => null,
        'seal' => '',
    ];
@endphp

@section('body')
    <div class="statement">Date: <span class="val">{{ $date }}</span></div>

    <div class="statement" style="margin-top:6pt;">
        I would like to request renewal of my ID for the following reason/s:
    </div>

    <table class="tight" style="margin: 4pt 0 0 14pt; width:auto;">
        <tr><td>{!! $tick(! empty($v['not_presentable'])) !!} It does not look presentable anymore.</td></tr>
        <tr><td>{!! $tick(! empty($v['destroyed'])) !!} It has been destroyed.</td></tr>
        <tr><td>{!! $tick(! empty($v['lost'])) !!} It was lost.</td></tr>
        <tr><td>{!! $tick(! empty($v['other'])) !!} Other reason/s:</td></tr>
    </table>

    @if (! empty($v['other']))
        <div class="box" style="margin-top:4pt; min-height:0; font-size:9pt;">{!! nl2br(e($v['other_specify'] ?? '')) !!}</div>
    @endif

    <div class="statement" style="margin-top:10pt;">
        Since the use of more than one ID is a ground for disciplinary action, my old ID is, therefore, surrendered to this office.
    </div>

    {{-- Student: right side, own row --}}
    <table class="sigs" style="margin-top:14pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                <div class="sig-cap" style="text-align:left; margin-bottom:2pt;">Very truly yours,</div>
                @include('pdf._signer', [
                    'heading' => 'Student',
                    's' => $studentSig,
                    'caption' => 'Name and Signature of the Student',
                ])
                <div class="sig-cap" style="margin-top:6pt; text-align:left;">
                    Program, Year and Section: <strong>{{ $v['student_program'] ?? '' }}</strong><br>
                    Student Number: <strong>{{ $v['student_number'] ?? '' }}</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- VPSD: left side, separate row below --}}
    <div style="margin-top:14pt; font-size:9pt;">
        <strong>Action Taken:</strong>
        &nbsp; {!! $tick($approved) !!} Approved
        &nbsp; {!! $tick(false) !!} Disapproved
    </div>

    <table class="sigs" style="margin-top:6pt;">
        <tr>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Approved',
                    's' => $vp,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($signedOn),
                ])
            </td>
            <td style="width:50%;"></td>
        </tr>
    </table>
@endsection