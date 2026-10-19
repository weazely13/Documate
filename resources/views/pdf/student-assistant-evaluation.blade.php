@extends('pdf.layout')

@section('doc_title', 'STUDENT ASSISTANT PERFORMANCE EVALUATION FORM')

@section('extra_css')
    <style>
        @page { margin: 28pt 50pt 66pt 50pt; }
        .foot { bottom: -56pt; height: 52pt; padding-top: 5pt; }

        body { font-size: 9pt; line-height: 1.25; }
        .rule { margin: 6pt 0 8pt; }
        .title { font-size: 12pt; }
        .refline { margin: 3pt 0 8pt; }
        .box-label { margin: 7pt 0 2pt; }
        .box { min-height: 26pt; line-height: 1.35; font-size: 8.5pt; }
        .sig-img { height: 32pt; line-height: 32pt; }

        /* Header: label above value, no wrapping labels */
        table.info td { padding: 0 8pt 0 0; vertical-align: top; }
        table.info .k { font-size: 6.5pt; color: #777; text-transform: uppercase; letter-spacing: .6pt; }
        table.info .v { font-size: 9.5pt; font-weight: bold; color: #111; border-bottom: .5pt solid #bbb; padding-bottom: 1.5pt; }

        /* Ratings table */
        table.rate { margin-top: 6pt; }
        table.rate th, table.rate td { border: .6pt solid #777; padding: 2pt 3pt; font-size: 7.5pt; line-height: 1.2; vertical-align: middle; }
        table.rate th { background: #f3f3f3; text-align: center; font-size: 7pt; }
        table.rate td.n, table.rate td.r { text-align: center; }
        table.rate td.r { font-weight: bold; font-size: 8.5pt; }
        table.rate th, table.rate td.n, table.rate td.r { padding-left: 2pt; padding-right: 2pt; }
        table.rate td.n { width: 28pt; }
        table.sigs { page-break-inside: avoid; }
    </style>
@endsection

@php
    use App\Workflow\Documents\StudentAssistantEvaluation as Ev;

    $keys = array_keys(Ev::CRITERIA);
    $labels = array_values(Ev::CRITERIA);
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $rec = $v['recommendation'] ?? '';
    $rater = $reporterSigner;
    $dateSigned = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $mean = $v['mean_rating'] ?? '';
@endphp

@section('body')
    <table class="info">
        <tr>
            <td style="width:50%;">
                <div class="k">Name of Student Assistant</div>
                <div class="v">{{ $v['student_name'] ?? '' }}</div>
            </td>
            <td style="width:50%;">
                <div class="k">Program, Year and Section</div>
                <div class="v">{{ $v['student_program'] ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:5pt;">
                <div class="k">Office/Area Assignment</div>
                <div class="v">{{ $v['office_area'] ?? '' }}</div>
            </td>
        </tr>
    </table>

    <div style="margin-top:8pt; font-size:8pt; color:#444; line-height:1.3;">
        Directions: Please evaluate the performance of the student assistant assigned to your office in a scale of
        5 where 5 is the highest and 1, the lowest.
    </div>

    <table class="rate">
        <tr>
            <th style="width:28pt;">No.</th><th>Criteria</th><th style="width:34pt;">Rating</th>
            <th style="width:28pt;">No.</th><th>Criteria</th><th style="width:34pt;">Rating</th>
        </tr>
        @for ($i = 0; $i < 5; $i++)
            <tr>
                <td class="n">{{ $i + 1 }}</td><td>{{ $labels[$i] }}</td><td class="r">{{ $v[$keys[$i]] ?? '' }}</td>
                <td class="n">{{ $i + 6 }}</td><td>{{ $labels[$i + 5] }}</td><td class="r">{{ $v[$keys[$i + 5]] ?? '' }}</td>
            </tr>
        @endfor
        <tr>
            <td colspan="5" style="text-align:center; font-weight:bold;">MEAN RATING</td>
            <td class="r">{{ $mean }}</td>
        </tr>
    </table>

    <div style="margin-top:4pt; text-align:center; font-size:7.5pt; font-weight:bold;">
        <u>Scale:</u> (5) Outstanding, (4) Very Satisfactory, (3) Satisfactory, (2) Good (1) Poor
    </div>

    <div style="margin-top:7pt; font-size:9pt;">
        {!! $tick($rec === Ev::RENEW) !!} Recommended for Renewal of Service as Student Assistant<br>
        {!! $tick($rec === Ev::NOT_RENEW) !!} Not Recommended
    </div>

    <div class="box-label">Comment/s</div>
    <div class="box">{!! nl2br(e($v['comments'] ?? '')) !!}</div>

    <table class="sigs" style="margin-top:12pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => 'Rater',
                    's' => $rater,
                    'caption' => 'Name and Signature of the Supervisor (Rater)<br>Date: ' . e($dateSigned),
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>
@endsection