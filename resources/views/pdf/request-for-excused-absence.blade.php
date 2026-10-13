@extends('pdf.layout')

@section('doc_title', 'REQUEST FOR EXCUSED ABSENCE FORM')

@php
    $vpsd = $signers[0] ?? [];
    $dean = $signers[1] ?? [];
    $vpas = $signers[2] ?? [];

    $from = ! empty($v['absence_from']) ? \Carbon\Carbon::parse($v['absence_from']) : null;
    $to = ! empty($v['absence_to']) ? \Carbon\Carbon::parse($v['absence_to']) : null;

    $dates = match (true) {
        $from && $to && $from->isSameDay($to) => $from->format('F j, Y'),
        $from && $to && $from->format('Y-m') === $to->format('Y-m') => $from->format('F j') . '-' . $to->format('j, Y'),
        $from && $to => $from->format('F j, Y') . ' to ' . $to->format('F j, Y'),
        default => '',
    };

    $fmt = fn ($s) => ! empty($s['signed_at']) ? $s['signed_at']->format('F j, Y') : '';
@endphp

@section('body')
    <div class="statement">
        This is to grant permission to <span class="val">{{ $v['student_name'] ?? '' }}</span>,
        a/an <span class="val">{{ $v['student_program'] ?? '' }}</span>,
        to be absent from his/her classes on <span class="val">{{ $dates }}</span>
        for the reason/s cited below:
    </div>

    <div class="box-label">Reason/s for absence</div>
    <div class="box">{!! nl2br(e($v['reasons'] ?? '')) !!}</div>

    <table class="sigs">
        <tr>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Recommending Approval',
                    's' => $vpsd,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($fmt($vpsd)),
                ])
            </td>
            <td class="gap"></td>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Recommending Approval',
                    's' => $dean,
                    'caption' => 'Name and Signature of the<br>College Dean<br>Date Signed: ' . e($fmt($dean)),
                ])
            </td>
        </tr>
    </table>

    <table class="sigs" style="margin-top:26pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Approved by',
                    's' => $vpas,
                    'caption' => 'Name and Signature of the<br>Vice President for Academic Services<br>Date Signed: ' . e($fmt($vpas)),
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>
@endsection