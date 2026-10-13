@extends('pdf.layout')

@section('doc_title', 'ADMISSION SLIP')
@section('doc_subtitle', 'Permission to attend class after an absence')

@php
    $counselor = $signers[0] ?? [];
    $vp = $signers[1] ?? [];
    $absence = ! empty($v['absence_date']) ? \Carbon\Carbon::parse($v['absence_date'])->format('F j, Y') : '';
    $vpDate = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
@endphp

@section('body')
    <div class="statement">
        This is to grant permission to <span class="val">{{ $v['student_name'] ?? '' }}</span>,
        a/an <span class="val">{{ $v['student_program'] ?? '' }}</span>,
        to attend the <span class="val">{{ $v['subject'] ?? '' }}</span>
        class of <span class="val">{{ $v['professor'] ?? '' }}</span>
        after being absent on <span class="val">{{ $absence }}</span>
        for the reason/s cited below:
    </div>

    <div class="box-label">Reason/s for absence</div>
    <div class="box">{!! nl2br(e($v['reasons'] ?? '')) !!}</div>

    <table class="sigs">
        <tr>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Recommending Approval',
                    's' => $counselor,
                    'caption' => 'Name and Signature of the<br>Guidance Counselor',
                ])
            </td>
            <td class="gap"></td>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Approved',
                    's' => $vp,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($vpDate),
                ])
            </td>
        </tr>
    </table>
@endsection