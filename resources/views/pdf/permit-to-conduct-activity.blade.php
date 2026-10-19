@extends('pdf.layout')

@section('doc_title', 'PERMIT TO CONDUCT AN IN-CAMPUS STUDENT ACTIVITY FORM')

@section('extra_css')
    <style>
        .tight td { padding: 3pt 0; vertical-align: top; }
        .lbl { width: 118pt; font-weight: bold; }
    </style>
@endsection

@php
    $adviser = $signers[0] ?? [];
    $vp = $signers[1] ?? [];
    $date = ! empty($v['activity_date']) ? \Carbon\Carbon::parse($v['activity_date'])->format('F j, Y') : '';
    $time = ! empty($v['activity_time'])
        ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $v['activity_time'], 0, 5))->format('g:i A')
        : '';
    $vpDate = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $hostedBy = $v['hosted_by'] ?? '';
    $isNonAcademic = $hostedBy === 'Non-Academic Organization';
    $position = $isNonAcademic ? ($v['non_academic_position'] ?? '') : ($v['officer_position'] ?? ($v['position_held'] ?? ''));
    $orgName = $isNonAcademic ? ($v['non_academic_org'] ?? '') : ($v['officer_organization'] ?? '');

    $studentSig = $studentSigner;
@endphp

@section('body')
    <table class="tight">
        <tr><td class="lbl">Activity/Event:</td><td class="val">{{ $v['activity'] ?? '' }}</td></tr>
        <tr><td class="lbl">Purpose/s:</td><td>{!! nl2br(e($v['purposes'] ?? '')) !!}</td></tr>
        <tr><td class="lbl">Date:</td><td class="val">{{ $date }}</td></tr>
        <tr><td class="lbl">Time:</td><td class="val">{{ $time }}</td></tr>
        <tr><td class="lbl">Venue:</td><td class="val">{{ $v['venue'] ?? '' }}</td></tr>
        <tr><td class="lbl">Persons Involved:</td><td>{{ $v['persons_involved'] ?? '' }}</td></tr>
        <tr>
            <td class="lbl">Hosted by:</td>
            <td class="val">
                @if ($hostedBy === 'Department')
                    Department: {{ $v['department_name'] ?? '' }}
                @elseif ($isNonAcademic)
                    Non-Academic Organization: {{ $v['non_academic_org'] ?? '' }}
                @else
                    Organization{{ ! empty($v['officer_organization']) ? ': ' . $v['officer_organization'] : '' }}
                @endif
            </td>
        </tr>
    </table>

    <table class="sigs" style="margin-top:26pt;">
        <tr>
            <td class="col" style="vertical-align:top;">
                @include('pdf._signer', [
                    'heading' => 'Requested by',
                    's' => $studentSig,
                    'caption' => 'Name and Signature of the Student',
                ])
                <div style="margin-top:6pt; font-size:8.5pt; line-height:1.4; text-align:center;">
                    <strong>{{ $position !== '' ? $position : 'Not an officer' }}</strong>{!! $orgName !== '' ? '<br>' . e($orgName) : '' !!}
                </div>
            </td>
            <td class="gap"></td>
            <td class="col"></td>
        </tr>
    </table>

    <table class="sigs" style="margin-top:30pt;">
        <tr>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Recommending Approval',
                    's' => $adviser,
                    'caption' => 'Name and Signature of the<br>Organization Adviser/Dept. Chair',
                ])
            </td>
            <td class="gap"></td>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Approved by',
                    's' => $vp,
                    'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($vpDate),
                ])
            </td>
        </tr>
    </table>
@endsection