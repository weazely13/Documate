@extends('pdf.clearance-layout')

@section('doc_title', 'CLEARANCE FOR ORGANIZATION OFFICERS')

@php
    $by = collect($signers)->filter(fn ($s) => ! empty($s['label']))->keyBy('label');
    $cell = fn ($label, $cap) => ['s' => $by->get($label, []), 'cap' => $cap];

    $sem = trim((string) preg_replace('/\s*semester\s*/i', '', (string) ($v['semester'] ?? '')));
    $vp = $by->get('Vice President for Student Development', []);
    $dateSigned = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
    $positionOrg = collect([$v['officer_position'] ?? null, $v['officer_organization'] ?? null])->filter()->implode(', ');
@endphp

@section('copy')
    <div class="title">CLEARANCE FOR ORGANIZATION OFFICERS</div>

    <div class="stmt">This is to certify that I, <span class="ul">{{ $v['student_name'] ?? '' }}</span>,</div>
    <div class="cap">(Printed Name of the Student)</div>
    <div class="stmt">a/an <span class="ul">{{ $v['student_program'] ?? '' }}</span> student of this institution and the</div>
    <div class="cap">(Program, Year and Section)</div>
    <div class="stmt"><span class="ul">{{ $positionOrg }}</span>, is cleared of all property and financial</div>
    <div class="cap">(Position and Name of Organization)</div>
    <div class="stmt">
        responsibilities as of the <span class="ul">{{ $sem }}</span> semester of Academic Year
        <span class="ul">{{ $v['academic_year'] ?? '' }}</span> as attested by the affixed signatures of the Adviser and
        the Treasurers of the organizations I am affiliated with, and the signatures of the School Librarian, the
        University Accountant, and the Vice President for Student Development.
    </div>

    @include('pdf._clearance-row', ['center' => true, 'l' => ['s' => $studentSigner, 'cap' => 'Printed Name of Student &amp; Signature']])

    <div class="rule"></div>

    @include('pdf._clearance-row', ['center' => true, 'l' => $cell('Academic Organization Adviser', 'Academic Organization Adviser')])

    @include('pdf._clearance-row', [
        'l' => $cell('SSC Treasurer', 'Name and Signature of the<br>SSC Treasurer<br>(For Organization Treasurer Only)'),
        'r' => $cell('SSC Secretary', 'Name and Signature of the<br>SSC Secretary<br>(For Organization Secretary Only)'),
    ])

    @include('pdf._clearance-row', ['center' => true, 'l' => $cell('SSC President', 'Name and Signature of the SSC President')])

    @include('pdf._clearance-row', [
        'l' => $cell('Chief Librarian', 'Name and Signature of the<br>Chief Librarian'),
        'r' => $cell('University Accountant', 'Name and Signature of the<br>University Accountant'),
    ])

    @include('pdf._clearance-row', [
        'center' => true,
        'l' => ['s' => $vp, 'cap' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>'],
    ])
@endsection