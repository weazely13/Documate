@extends('pdf.layout')

@section('doc_title', 'STUDENT APPOINTMENT SLIP FORM')
@section('doc_subtitle', 'Summons from the Vice President for Student Development')

@php
    $vp = $signers[0] ?? [];
    $slipDate = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $when = ! empty($v['appointment_date']) ? \Carbon\Carbon::parse($v['appointment_date'])->format('F j, Y') : '';
    $time = ! empty($v['appointment_time'])
        ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $v['appointment_time'], 0, 5))->format('g:i A')
        : '';
    $signedOn = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
@endphp

@section('body')
    <div style="text-align:right;">Date: <span class="val">{{ $slipDate }}</span></div>

    <table style="margin-top:10pt;">
        <tr>
            <td style="width:34pt; font-weight:bold;">To:</td>
            <td>
                <div class="val">{{ $v['student_name'] ?? '' }}</div>
                <div>{{ $v['student_program'] ?? '' }}</div>
                <div style="color:#555;">{{ $v['student_number'] ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding-top:14pt; font-weight:bold;">RE:</td>
            <td style="padding-top:14pt;">{!! nl2br(e($v['subject'] ?? '')) !!}</td>
        </tr>
    </table>

    <div class="statement" style="margin-top:26pt;">
        Please see the Vice President for Student Development at
        <span class="val">{{ $time ?: '__________' }}</span> on
        <span class="val">{{ $when ?: '________________' }}</span>.
    </div>

    <table class="sigs" style="margin-top:34pt;">
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