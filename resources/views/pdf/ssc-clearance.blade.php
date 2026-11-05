@extends('pdf.clearance-layout')

@section('doc_title', 'CLEARANCE FOR SSC/STUDENT PUBLICATION OFFICERS')

@php
    // F-SDM-022 Rev. 2 (05-09-25). Older records may carry the earlier step labels (SSC Adviser, Chief Librarian).
    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $pick = fn (array $labels) => collect($labels)->map(fn ($l) => $by->get($l))->filter()->first() ?? [];
    $cell = fn ($labels, $cap) => ['s' => $pick((array) $labels), 'cap' => 'PRINTED NAME &amp; Signature of the<br><i>' . $cap . '</i>'];

    $vp = $pick(['Vice President for Student Development']);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $orgName = (string) ($v['officer_organization'] ?? '');
    $orgAbbr = preg_match('/\(([^)]+)\)/', $orgName, $m) ? $m[1] : $orgName;
@endphp

@section('copy')
    <div class="title">CLEARANCE FOR SSC/STUDENT PUBLICATION OFFICERS</div>

    @include('pdf._clearance-head', ['attested' => 'Adviser, President/Editor-in-Chief, Secretary and the Treasurer/Managing Editor of the organizations I am affiliated with, and the signatures of the College Chief Librarian, the University Accountant, Director for Student Organizations and Activities, and the Vice President for Student Development.'])

    @include('pdf._clearance-row', ['center' => true, 'l' => ['s' => $studentSigner, 'cap' => '<i>PRINTED NAME OF STUDENT &amp; SIGNATURE</i>']])

    <div class="rule"></div>

    @include('pdf._clearance-row', [
        'l' => $cell(['SSC Treasurer/SPO Managing Editor', 'SSC Treasurer'], 'SSC Treasurer/SPO Managing Editor'),
        'r' => $cell('SSC Secretary', 'SSC Secretary'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell(['SSC President/SPO Editor-in-Chief', 'SSC President'], 'SSC President/SPO Editor-in-Chief'),
        'r' => $cell(['SSC/SPO Adviser', 'SSC Adviser'], 'SSC/SPO Adviser'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell(['College Chief Librarian', 'Chief Librarian'], 'College Chief Librarian'),
        'r' => $cell('University Accountant', 'University Accountant'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell('Director, Student Organizations and Activities', 'Director, Student Organizations<br>and Activities'),
        'r' => ['s' => ['name' => $orgAbbr], 'cap' => 'PRINTED NAME OF ORGANIZATION<br>(ABBREVIATION)'],
    ])

    @include('pdf._clearance-row', [
        'center' => true,
        'l' => ['s' => $vp, 'cap' => 'PRINTED NAME &amp; Signature of the<br><i>Vice President for Student Development</i><br>Date Signed: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>'],
    ])
@endsection
