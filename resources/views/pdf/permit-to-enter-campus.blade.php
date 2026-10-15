@extends('pdf.layout')

@section('doc_title', 'PERMIT TO ENTER THE CAMPUS / TO ATTEND CLASSES')
@section('doc_subtitle', 'Permission to enter the campus to attend classes')

@php
    $vp = $signers[0] ?? [];
    $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('F j, Y') : '';
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $validUntil = $fmt($v['valid_until'] ?? null);
    $cDate = $fmt($v['counseling_date'] ?? null);
    $fmtT = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', substr($t, 0, 5))->format('g:i A') : '';
    $cTime = trim($fmtT($v['counseling_time'] ?? null) . (! empty($v['counseling_time_end']) ? ' to ' . $fmtT($v['counseling_time_end']) : ''));
    $signedOn = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
    $vp = $signers[0] ?? [];
    $gc = $signers[1] ?? [];
    $gcDate = ! empty($gc['signed_at']) ? $gc['signed_at']->format('F j, Y') : '';
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
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Approved',
                    's' => $vp,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($signedOn),
                ])
            </td>
            <td class="gap"></td>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Counselling Completed',
                    's' => $gc,
                    'caption' => 'Name and Signature of the<br>Guidance Counselor<br>Date Signed: ' . e($gcDate),
                ])
            </td>
        </tr>
    </table>

    <div style="margin-top:18pt; font-size:8pt; color:#444;">
        <strong>Guidance Counseling (every Wednesday)</strong>: report to the Guidance Office to have this permit signed by the Guidance Counselor.<br>
        Date: <span class="val">{{ $cDate ?: '____________' }}</span>
        &nbsp;&nbsp; Time: <span class="val">{{ $cTime ?: '____________' }}</span>
    </div>
@endsection