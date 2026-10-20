@extends('pdf.clearance-layout')

@section('doc_title', 'CLEARANCE FOR SSC/STUDENT PUBLICATION OFFICERS')

@php
   

    $name    = (string) ($v['student_name'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $posOrg  = collect([$v['officer_position'] ?? null, $v['officer_organization'] ?? null])->filter()->implode(', ');
    $sem     = trim((string) preg_replace('/\s*semester\s*/i', '', (string) ($v['semester'] ?? '')));
    $ay      = (string) ($v['academic_year'] ?? '');

    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $cell = fn ($label, $cap) => ['s' => $by->get($label, []), 'cap' => $cap];

    $vp = $by->get('Vice President for Student Development', []);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
@endphp

@section('copy')
    <div class="title">CLEARANCE FOR SSC/STUDENT PUBLICATION OFFICERS</div>

    @include('pdf._clearance-line', ['pre' => 'This is to certify that I,', 'value' => $name, 'cap' => '(Printed Name of the Student)', 'post' => ','])
    @include('pdf._clearance-line', ['pre' => 'a/an', 'value' => $program, 'cap' => '(Program, Year and Section)', 'post' => 'student of this institution and the'])
    @include('pdf._clearance-line', ['pre' => '', 'value' => $posOrg, 'cap' => '(Position in SSC/SPO Organization)', 'post' => ', is cleared of all property and financial'])

    <div class="stmt">
        responsibilities as of the <span class="ul">{{ $sem }}&nbsp;&nbsp;&nbsp;</span> semester of Academic Year
        <span class="ul">{{ $ay }}&nbsp;&nbsp;&nbsp;</span> as attested by the affixed signatures of the Adviser and
        the Treasurers of the organizations I am affiliated with, and the signatures of the School Librarian, the
        University Accountant, and the Vice President for Student Development.
    </div>

    @include('pdf._clearance-row', ['center' => true, 'l' => ['s' => $studentSigner, 'cap' => 'Printed Name of Student &amp; Signature']])

    <div class="rule"></div>

    @include('pdf._clearance-row', [
        'l' => $cell('SSC Treasurer', 'Name and Signature of the<br>SSC Treasurer'),
        'r' => $cell('SSC Secretary', 'Name and Signature of the<br>SSC Secretary'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell('SSC President', 'Name and Signature of the<br>SSC President'),
        'r' => $cell('SSC Adviser', 'Name and Signature of the<br>SSC Adviser'),
    ])
    @include('pdf._clearance-row', [
        'l' => $cell('Chief Librarian', 'Name and Signature of the<br>Chief Librarian'),
        'r' => $cell('University Accountant', 'Name and Signature of the<br>University Accountant'),
    ])

    @include('pdf._clearance-row', [
        'center' => true,
        'l' => ['s' => $vp, 'cap' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>'],
    ])
@endsection