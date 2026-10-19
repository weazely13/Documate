@extends('pdf.layout')

@section('doc_title', 'STUDENT COMPLAINT/GRIEVANCE FORM')

@section('extra_css')
    <style>
        @page { margin: 28pt 54pt 68pt 54pt; }
        .foot { bottom: -58pt; height: 54pt; padding-top: 6pt; }
        body { line-height: 1.3; }
        .rule { margin: 6pt 0 8pt; }
        .refline { margin: 3pt 0 8pt; }
        .box { min-height: 70pt; font-size: 9pt; line-height: 1.4; }
        .sig-img { height: 32pt; line-height: 32pt; }
        table.sigs { page-break-inside: avoid; }
    </style>
@endsection

@php
    $dateFiled = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    $parent = $signers[0] ?? [];
    $parent['name'] = ($parent['name'] ?? '') ?: trim((string) ($v['parent_name'] ?? ''));

    // Same order as StudentComplaintGrievance::chain(): filled witness rows after the parent.
    $witnesses = [];
    $k = 1;
    foreach ([1, 2] as $i) {
        $typed = trim((string) ($v["witness_{$i}_name"] ?? ''));
        if ($typed === '' && trim((string) ($v["witness_{$i}_email"] ?? '')) === '') {
            continue;
        }
        $s = $signers[$k] ?? [];
        $s['name'] = ($s['name'] ?? '') ?: $typed;
        $witnesses[$i] = $s;
        $k++;
    }
@endphp

@section('body')
    <div style="text-align:right;">Date: <span class="val">{{ $dateFiled }}</span></div>

    <div style="margin-top:6pt; line-height:1.4;">
        <strong>{{ $v['vpsd_name'] ?? '' }}</strong><br>
        Vice President for Student Development<br>
        This University
    </div>

    <div class="statement" style="margin-top:12pt;">
        I would like to file a complaint against <span class="val">{{ $v['respondent'] ?? '' }}</span>
        for the reason/s cited below:
    </div>
    <div class="box" style="margin-top:6pt;">{!! nl2br(e($v['complaint'] ?? '')) !!}</div>

    <table class="sigs" style="margin-top:20pt;">
        <tr>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Complainant',
                    's' => $studentSigner,
                    'caption' => 'Name and Signature of Complainant',
                ])
            </td>
            <td class="gap"></td>
            <td class="col">
                @include('pdf._signer', [
                    'heading' => 'Parent/Guardian',
                    's' => $parent,
                    'caption' => 'Name and Signature of<br>Complainant\'s Parent/Guardian',
                ])
            </td>
        </tr>
    </table>

    <div style="margin-top:14pt; font-weight:bold;">Witnesses:</div>

    <table class="sigs" style="margin-top:4pt;">
        <tr>
            <td class="col">
                @if (isset($witnesses[1]))
                    @include('pdf._signer', ['heading' => 'Witness #1', 's' => $witnesses[1], 'caption' => 'Name and Signature of Witness #1'])
                @endif
            </td>
            <td class="gap"></td>
            <td class="col">
                @if (isset($witnesses[2]))
                    @include('pdf._signer', ['heading' => 'Witness #2', 's' => $witnesses[2], 'caption' => 'Name and Signature of Witness #2'])
                @endif
            </td>
        </tr>
    </table>
@endsection