@extends('pdf.layout')

@section('doc_title', 'PERMIT TO ENTER THE CAMPUS / TO ATTEND CLASSES')
@section('doc_subtitle', 'Permission to enter the campus to attend classes')

@php
    $vp = $signers[0] ?? [];
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('F j, Y') : '';
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $validUntil = $fmt($v['valid_until'] ?? null);
    $cDate = $fmt($v['counseling_date'] ?? null);
    $cTime = ! empty($v['counseling_time'])
        ? \Carbon\Carbon::createFromFormat('H:i', substr($v['counseling_time'], 0, 5))->format('g:i A')
        : '';
    $signedOn = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
@endphp

@section('body')
    <div class="statement">
        This is to grant permission to <span class="val">{{ $v['student_name'] ?? '' }}</span>,
        a/an <span class="val">{{ $v['student_program'] ?? '' }}</span>,
        to enter the campus to attend classes:
    </div>

    <table style="margin: 8pt 0 0 14pt; width: auto;">
        <tr>
            <td style="padding:2pt 28pt 2pt 0;">{!! $tick(! empty($v['no_id'])) !!} without an ID</td>
            <td style="padding:2pt 0;">{!! $tick(! empty($v['no_uniform'])) !!} not wearing school uniform</td>
        </tr>
        <tr>
            <td colspan="2" style="padding:2pt 0;">
                {!! $tick(! empty($v['others'])) !!} others, please specify:
                <span class="val">{{ ! empty($v['others']) ? ($v['others_specify'] ?? '') : '' }}</span>
            </td>
        </tr>
    </table>

    <div class="box-label">For the reason/s cited below</div>
    <div class="box">{!! nl2br(e($v['reasons'] ?? '')) !!}</div>

    <div class="statement" style="margin-top:14pt;">
        This permit is valid only until <span class="val">{{ $validUntil ?: '________________' }}</span>.
    </div>

    <table class="sigs" style="margin-top:26pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Approved',
                    's' => $vp,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($signedOn),
                ])
            </td>
        </tr>
    </table>

    <div style="margin-top:18pt; font-size:8pt; font-style:italic; color:#444;">
        Guidance Counseling (every Wednesday)<br>
        Date: <span class="val" style="font-style:normal;">{{ $cDate ?: '____________' }}</span>
        &nbsp;&nbsp; Time: <span class="val" style="font-style:normal;">{{ $cTime ?: '____________' }}</span>
    </div>
@endsection