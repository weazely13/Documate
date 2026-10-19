@extends('pdf.layout')

@section('doc_title', "PARENT'S CONSENT FOR STUDENT ASSISTANTSHIP FORM")

@php
    $parent = $signers[0] ?? [];
    $signedOn = ! empty($parent['signed_at']) ? $parent['signed_at']->format('F j, Y') : '';
    $rel = strtolower((string) ($v['relationship'] ?? ''));
    $pronoun = match ($rel) { 'son' => 'him', 'daughter' => 'her', default => 'him/her' };
@endphp

@section('body')
    <div style="line-height:1.4;">
        <strong>{{ $v['vpsd_name'] ?? '' }}</strong><br>
        Vice President for Student Development<br>
        This University
    </div>

    <div class="statement" style="margin-top:18pt;">
        This is to inform the Office of the Vice President for Student Development that
        <span class="val">{{ $v['student_name'] ?? '' }}</span>, my
        <span class="val">{{ $rel ?: 'son/daughter/ward' }}</span>, is a/an
        <span class="val">{{ $v['student_program'] ?? '' }}</span>
        student of the University and that I am allowing {{ $pronoun }} to work as student assistant in your institution.
    </div>

    <div style="margin-top:22pt;">Very truly yours,</div>

    <table class="sigs" style="margin-top:8pt;">
        <tr>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Parent/Guardian',
                    's' => $parent,
                    'caption' => 'Name and Signature of the Parent/Guardian<br>Date Signed: ' . e($signedOn),
                ])
            </td>
            <td style="width:50%;"></td>
        </tr>
    </table>
@endsection