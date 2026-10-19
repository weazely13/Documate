@extends('pdf.layout')

@section('doc_title', 'RECOMMENDATION FOR STUDENT ASSISTANTSHIP FORM')

@php
    $faculty = $reporterSigner;
    $dateSigned = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
@endphp

@section('body')
    <div style="line-height:1.4;">
        <strong>{{ $v['vpsd_name'] ?? '' }}</strong><br>
        Vice President for Student Development<br>
        This University
    </div>

    <div style="margin-top:14pt;">Sir/Ma'am,</div>

    <div class="statement" style="margin-top:8pt;">
        I recommend <span class="val">{{ $v['student_name'] ?? '' }}</span>, a/an
        <span class="val">{{ $v['student_program'] ?? '' }}</span>
        student, for Student Assistantship.
    </div>

    <div style="margin-top:22pt;">Very truly yours,</div>

    <table class="sigs" style="margin-top:8pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Recommended by',
                    's' => $faculty,
                    'caption' => 'Name and Signature of the Recommending<br>' . e($v['reporter_role'] ?? 'Faculty') . '<br>Date: ' . e($dateSigned),
                ])
            </td>
        </tr>
    </table>
@endsection