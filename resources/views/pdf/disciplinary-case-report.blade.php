@extends('pdf.layout')

@section('doc_title', 'DISCIPLINARY CASE REPORT FORM')

@section('extra_css')
    <style>
        .tight td { padding: 2pt 0; }
        .box { min-height: 60pt; }
        table.sigs { page-break-inside: avoid; }
    </style>
@endsection

@php
    $chair = $signers[0] ?? [];
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $hearing = ! empty($v['hearing_date']) ? \Carbon\Carbon::parse($v['hearing_date'])->format('F j, Y') : '';

    // Same order as DisciplinaryCaseReport::chain(): filled member rows, after the Chairman.
    $memberSigners = [];
    $k = 1;
    foreach (range(1, 5) as $i) {
        $typed = trim((string) ($v["member_{$i}_name"] ?? ''));
        if ($typed === '' && trim((string) ($v["member_{$i}_email"] ?? '')) === '') {
            continue;
        }
        $s = $signers[$k] ?? [];
        $s['name'] = ($s['name'] ?? '') ?: $typed;
        $memberSigners[] = $s;
        $k++;
    }
@endphp

@section('body')
    <table class="tight">
        <tr>
            <td style="width:92pt; font-weight:bold;">Student's Name:</td>
            <td class="val">{{ $v['student_name'] ?? '' }}</td>
            <td style="width:70pt; font-weight:bold;">I.D. Number:</td>
            <td class="val" style="width:90pt;">{{ $v['student_number'] ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Program, Year and Section:</td>
            <td colspan="3" class="val">{{ $v['student_program'] ?? '' }}</td>
        </tr>
    </table>

    <div class="box-label">Violation</div>
    <div class="box" style="min-height:24pt;">{!! nl2br(e($v['violation'] ?? '')) !!}</div>

    <div class="box-label">Incident report</div>
    <div class="box">{!! nl2br(e($v['incident_report'] ?? '')) !!}</div>

    <div class="box-label">Resolution</div>
    <div class="box">{!! nl2br(e($v['resolution'] ?? '')) !!}</div>

    <div class="box-label">Recommendations</div>
    <div class="box" style="min-height:0;">
        {!! $tick(! empty($v['rec_closed'])) !!} Closed/Resolved
        &nbsp;&nbsp; {!! $tick(! empty($v['rec_hearing'])) !!} For continuing hearing &ndash; Hearing Date:
        <span class="val">{{ ! empty($v['rec_hearing']) ? $hearing : '' }}</span><br>
        {!! $tick(! empty($v['rec_formal'])) !!} For Formal Investigation
    </div>

    <div style="margin-top:16pt; font-weight:bold;">Report Given By:</div>

    <table class="sigs" style="margin-top:6pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Chairman',
                    's' => $chair,
                    'caption' => 'Name and Signature of the Chairman',
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>

    @foreach (array_chunk($memberSigners, 2) as $row)
        <table class="sigs" style="margin-top:18pt;">
            <tr>
                <td class="col">
                    @include('pdf._signer', ['heading' => 'Member', 's' => $row[0], 'caption' => 'Name and Signature of the Member'])
                </td>
                <td class="gap"></td>
                <td class="col">
                    @if (isset($row[1]))
                        @include('pdf._signer', ['heading' => 'Member', 's' => $row[1], 'caption' => 'Name and Signature of the Member'])
                    @endif
                </td>
            </tr>
        </table>
    @endforeach
@endsection