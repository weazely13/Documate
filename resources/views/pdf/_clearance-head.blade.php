{{-- The certification shared by the three clearances (F-SDM-020/021/022 Rev. 2), line for line as on the paper form.
     Needs: $v (answers), $attested (the text after "signatures of the", trusted HTML). --}}
@php
    $name    = (string) ($v['student_name'] ?? '');
    $number  = (string) ($v['student_number'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $sem     = trim((string) preg_replace('/\s*semester\s*/i', '', (string) ($v['semester'] ?? '')));
    $ay      = (string) ($v['academic_year'] ?? '');
@endphp

@include('pdf._clearance-line', ['pre' => 'This is to certify that I,', 'value' => $name, 'cap' => '(PRINTED SURNAME, FIRST NAME, MIDDLE INITIAL)', 'post' => ', with'])

<table class="cl">
    <tr>
        <td class="cl-val" style="width:28%;">{{ $number }}&nbsp;</td>
        <td class="cl-pre" style="padding-left:2pt;">, a/an</td>
        <td class="cl-val">{{ $program }}&nbsp;</td>
        <td class="cl-post">student of this</td>
    </tr>
    <tr>
        <td class="cl-cap">(STUDENT NUMBER)</td>
        <td></td>
        <td class="cl-cap">(PROGRAM, YEAR AND SECTION)</td>
        <td></td>
    </tr>
</table>

<div style="line-height:14pt;">institution, is cleared of all property and financial responsibilities as of the</div>

<table class="cl">
    <tr>
        <td class="cl-val" style="width:22%;">{{ $sem }}&nbsp;</td>
        <td class="cl-pre" style="padding-left:2pt;">semester of</td>
        <td class="cl-val" style="width:30%;">{{ $ay }}&nbsp;</td>
        <td class="cl-post">as attested by the affixed</td>
    </tr>
    <tr>
        <td class="cl-cap">(FIRST/SECOND)</td>
        <td></td>
        <td class="cl-cap">(ACADEMIC/SCHOOL YEAR)</td>
        <td></td>
    </tr>
</table>

<div class="stmt">signatures of the {!! $attested !!}</div>
