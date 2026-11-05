@extends('pdf.clearance-layout')

@section('doc_title', 'CLEARANCE FOR ORGANIZATION OFFICERS')

@php
    // F-SDM-021 Rev. 2 (05-09-25). Older records may carry the earlier step labels (Chief Librarian).
    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $pick = fn (array $labels) => collect($labels)->map(fn ($l) => $by->get($l))->filter()->first() ?? [];
    $cell = fn ($labels, $cap) => ['s' => $pick((array) $labels), 'cap' => 'PRINTED NAME &amp; Signature of the<br><i>' . $cap . '</i>'];

    $vp = $pick(['Vice President for Student Development']);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $orgName = (string) ($v['officer_organization'] ?? '');
    $orgAbbr = preg_match('/\(([^)]+)\)/', $orgName, $m) ? $m[1] : $orgName;
@endphp

@section('copy')
    <div class="title">CLEARANCE FOR ORGANIZATION OFFICERS</div>

    @include('pdf._clearance-head', ['attested' => 'Organization Adviser, Secretary and the Treasurer of the organizations I am affiliated with, the SSC President, the Director for Student Organizations and Activities, the College Chief Librarian, and the Vice President for Student Development.'])

    @include('pdf._clearance-row', ['center' => true, 'l' => ['s' => $studentSigner, 'cap' => '<i>PRINTED NAME OF STUDENT &amp; SIGNATURE</i>']])

    <div class="rule"></div>

    @include('pdf._clearance-row', ['center' => true, 'l' => $cell('Academic Organization Adviser', 'Academic Organization Adviser')])

    @include('pdf._clearance-row', [
        'l' => $cell('SSC Treasurer', 'SSC Treasurer<br>(For Organization Treasurer Only)'),
        'r' => $cell('SSC Secretary', 'SSC Secretary<br>(For Organization Secretary Only)'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell('SSC President', 'SSC President'),
        'r' => $cell('Director, Student Organizations and Activities', 'Director, Student Organizations<br>and Activities'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell(['College Chief Librarian', 'Chief Librarian'], 'College Chief Librarian'),
        'r' => ['s' => ['name' => $orgAbbr], 'cap' => 'PRINTED NAME OF ORGANIZATION<br>(ABBREVIATION)'],
    ])

    @include('pdf._clearance-row', [
        'center' => true,
        'l' => ['s' => $vp, 'cap' => 'PRINTED NAME &amp; Signature of the<br><i>Vice President for Student Development</i><br>Date Signed: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>'],
    ])
@endsection
