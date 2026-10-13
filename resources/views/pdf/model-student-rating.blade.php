@extends('pdf.layout')

@section('doc_title', 'Model Student Faculty/Peer Rating Form')

@php
    $rater = $signers[0] ?? [];
    $picked = (int) ($rater['response']['rating'] ?? 0);
    $dateSigned = ! empty($rater['signed_at']) ? $rater['signed_at']->format('F j, Y') : '';
    $labels = \App\Workflow\Documents\ModelStudentRating::RATINGS;
    $course = $v['student_course'] ?? $v['student_program'] ?? '';
@endphp

@section('body')
    <div class="statement">
        This is to inform the Office of the Vice President for Student Development that I personally know
        <span class="val">{{ $v['student_name'] ?? '' }}</span>, a
        <span class="val">{{ $course }}</span> student of Leyte Normal University.
    </div>

    <div class="statement" style="margin-top:10pt;">
        Based on my honest assessment with respect to his/her interpersonal relations with his/her
        classmates and teachers, I am giving him/her a rating of:
    </div>

    <table style="margin-top:12pt;">
        <tr>
            <td style="width:30%;"></td>
            <td style="width:40%;">
                <table>
                    @foreach ($labels as $n => $label)
                        <tr>
                            <td style="width:20pt; padding:3pt 0;">
                                <div style="width:11pt; height:11pt; border:.8pt solid #555; text-align:center; font-size:8.5pt; font-weight:bold; line-height:11pt;">{{ $picked === $n ? 'X' : '' }}</div>
                            </td>
                            <td style="padding:3pt 0 3pt 6pt; font-size:10pt; {{ $picked === $n ? 'font-weight:bold;' : 'color:#555;' }}">{{ $label }}</td>
                            <td style="width:26pt; padding:3pt 0; text-align:right; font-size:9pt; color:#888;">({{ $n }})</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td style="width:30%;"></td>
        </tr>
    </table>

    <table class="sigs" style="margin-top:34pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Rater',
                    's' => $rater,
                    'caption' => 'Name and Signature of the Rater<br>Date: ' . e($dateSigned),
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>

    <div style="margin-top:22pt; font-size:7pt; font-style:italic; color:#999; text-align:center;">
        The data collected here will be for the sole use of legitimate interests of the university as an educational institution.
    </div>
@endsection