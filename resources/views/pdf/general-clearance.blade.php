@extends('pdf.clearance-layout')

@section('doc_title', "GENERAL STUDENTS' CLEARANCE")

@php
    // F-SDM-020 Rev. 2 (05-09-25). Older records may carry the earlier step labels (Homeroom Adviser).
    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $pick = fn (array $labels) => collect($labels)->map(fn ($l) => $by->get($l))->filter()->first() ?? [];
    $cell = fn ($labels, $cap) => ['s' => $pick((array) $labels), 'cap' => 'PRINTED NAME &amp; Signature of the<br><i>' . $cap . '</i>'];

    $vp = $pick(['Vice President for Student Development']);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $orgName = (string) ($v['clr_academic_org'] ?? $v['clr_non_academic_org'] ?? $v['clr_year_org'] ?? '');
    $orgAbbr = preg_match('/\(([^)]+)\)/', $orgName, $m) ? $m[1] : $orgName;

    $rows = [
        [$cell('Academic Organization Treasurer', 'Academic Organization Treasurer'), $cell('Academic Organization Adviser', 'Academic Organization Adviser')],
        [$cell('Non-Academic Organization Treasurer', 'Non-Academic Organization Treasurer'), $cell('Non-Academic Organization Adviser', 'Non-Academic Organization Adviser')],
        [$cell('Year Level Organization Treasurer', 'Year Level Organization Treasurer'), $cell(['Year-level Adviser', 'Homeroom Adviser'], 'Year-level Adviser')],
        [$cell(['College Chief Librarian', 'Chief Librarian'], 'College Chief Librarian'), ['s' => ['name' => $orgAbbr], 'cap' => 'PRINTED NAME OF ORGANIZATION<br>(ABBREVIATION)']],
    ];
@endphp

@section('copy')
    <div class="title">GENERAL STUDENTS' CLEARANCE</div>

    @include('pdf._clearance-head', ['attested' => '<i>Academic</i> and <i>Non-Academic Adviser</i>, the <i>Treasurer of the organizations</i> I am affiliated with, the signatures of the <i>Year-level Adviser</i> and <i>Year Level Organization Treasurer</i>, the <i>College Chief Librarian</i> and the <i>Vice President for Student Development</i>.'])

    @include('pdf._clearance-row', ['center' => true, 'l' => ['s' => $studentSigner, 'cap' => '<i>PRINTED NAME OF STUDENT &amp; SIGNATURE</i>']])

    <div class="rule"></div>

    @foreach ($rows as $row)
        @include('pdf._clearance-row', ['l' => $row[0], 'r' => $row[1]])
    @endforeach

    @include('pdf._clearance-row', [
        'center' => true,
        'l' => ['s' => $vp, 'cap' => 'PRINTED NAME &amp; Signature of the<br><i>Vice President for Student Development</i><br>Date Signed: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>'],
    ])
@endsection
