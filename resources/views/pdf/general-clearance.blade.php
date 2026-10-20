@extends('pdf.clearance-layout')

@section('doc_title', "GENERAL STUDENTS' CLEARANCE")

@php
   

    $name    = (string) ($v['student_name'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $sem     = trim((string) preg_replace('/\s*semester\s*/i', '', (string) ($v['semester'] ?? '')));
    $ay      = (string) ($v['academic_year'] ?? '');

    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $cell = fn ($label, $cap) => ['s' => $by->get($label, []), 'cap' => $cap];

    $vp = $by->get('Vice President for Student Development', []);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $rows = [
        [$cell('Academic Organization Treasurer', 'Name and Signature of the<br>Academic Organization Treasurer'),
         $cell('Academic Organization Adviser', 'Name and Signature of the<br>Academic Organization Adviser')],
        [$cell('Non-Academic Organization Treasurer', 'Name and Signature of the<br>Non-Academic Organization Treasurer'),
         $cell('Non-Academic Organization Adviser', 'Name and Signature of the<br>Non-Academic Organization Adviser')],
        [$cell('Year Level Organization Treasurer', 'Name and Signature of the<br>Year Level Organization Treasurer'),
         $cell('Homeroom Adviser', 'Name and Signature of the<br>Homeroom Adviser')],
        [$cell('College Chief Librarian', 'Name and Signature of the<br>College Chief Librarian'), null],
    ];
@endphp

@section('copy')
    <div class="title">GENERAL STUDENTS' CLEARANCE</div>

    @include('pdf._clearance-line', ['pre' => 'This is to certify that I,', 'value' => $name, 'cap' => '(Printed Name of the Student)', 'post' => ','])
    @include('pdf._clearance-line', ['pre' => 'a/an', 'value' => $program, 'cap' => '(Program, Year and Section)', 'post' => 'student of this institution, is cleared'])

    <div class="stmt">
        of all property and financial responsibilities as of the <span class="ul">{{ $sem }}&nbsp;&nbsp;&nbsp;</span> semester
        of Academic Year <span class="ul">{{ $ay }}&nbsp;&nbsp;&nbsp;</span> as attested by the affixed signatures of
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