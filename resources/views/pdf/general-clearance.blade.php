@extends('pdf.clearance-layout')

@section('doc_title', "GENERAL STUDENTS' CLEARANCE")

@php
    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $cell = fn ($label, $cap) => ['s' => $by->get($label, []), 'cap' => $cap];

    $sem = trim((string) preg_replace('/\s*semester\s*/i', '', (string) ($v['semester'] ?? '')));
    $vp = $by->get('Vice President for Student Development', []);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $rows = [
        [$cell('Academic Organization Treasurer', 'Name and Signature of the<br>Academic Organization Treasurer'),
         $cell('Academic Organization Adviser', 'Name and Signature of the<br>Academic Organization Adviser')],
        [$cell('Non-Academic Organization Treasurer', 'Name and Signature of the<br>Non-Academic Organization Treasurer'),
         $cell('Non-Academic Organization Adviser', 'Name and Signature of the<br>Non-Academic Organization Adviser')],
        [$cell('Year Level Organization Treasurer', 'Name and Signature of the<br>Year Level Organization<br>Treasurer'),
         $cell('Homeroom Adviser', 'Name and Signature of the<br>Homeroom Adviser')],
        [$cell('College Chief Librarian', 'Name and Signature of the<br>College Chief Librarian'), null],
    ];
@endphp

@section('copy')
    <div class="title">GENERAL STUDENTS' CLEARANCE</div>

    <div class="stmt">This is to certify that I, <span class="ul">{{ $v['student_name'] ?? '' }}</span>,</div>
    <div class="cap">(Printed Name of the Student)</div>
    <div class="stmt">a/an <span class="ul">{{ $v['student_program'] ?? '' }}</span> student of this institution, is cleared</div>
    <div class="cap">(Program, Year and Section)</div>
    <div class="stmt">
        of all property and financial responsibilities as of the <span class="ul">{{ $sem }}</span> semester
        of Academic Year <span class="ul">{{ $v['academic_year'] ?? '' }}</span> as attested by the affixed signatures of
        the Academic and Non-Academic Adviser and the Treasurers of the organizations I am affiliated with, and the
        signatures of the School Librarian, the Homeroom Adviser, and the Vice President for Student Development.
    </div>

    @include('pdf._clearance-row', ['center' => true, 'l' => ['s' => $studentSigner, 'cap' => 'Printed Name of Student &amp; Signature']])

    <div class="rule"></div>

    @foreach ($rows as $row)
        @include('pdf._clearance-row', ['l' => $row[0], 'r' => $row[1]])
    @endforeach

    @include('pdf._clearance-row', [
        'center' => true,
        'l' => ['s' => $vp, 'cap' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>'],
    ])
@endsection