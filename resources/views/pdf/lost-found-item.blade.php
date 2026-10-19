@extends('pdf.layout')

@section('doc_title', 'LOST/FOUND ITEM FORM')

@section('extra_css')
    <style>
        .tight td { padding: 3pt 0; vertical-align: top; }
        .lbl { width: 48pt; font-weight: bold; }
        .cut { border-top: .8pt dashed #888; margin: 24pt 0 10pt; }
        .part { text-align: center; font-weight: bold; font-size: 10.5pt; margin: 8pt 0 6pt; }
    </style>
@endsection

@php
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $tick = fn ($on) => '<span class="mono">[' . ($on ? 'X' : '&nbsp;') . ']</span>';
    $kind = $v['item_kind'] ?? '';

    $person = $studentSigner;
@endphp

@section('body')
    <table class="tight">
        <tr><td class="lbl">Date:</td><td class="val">{{ $dateIssued }}</td></tr>
        <tr><td class="lbl">To/For:</td><td class="val">Office of the Vice President for Student Development</td></tr>
    </table>

    <div class="statement" style="margin-top:16pt;">
        {!! $tick($kind === 'Lost') !!} May I respectfully report to your office a/an
        <span class="val">{{ $kind === 'Lost' ? ($v['item_description'] ?? '') : '' }}</span> that I lost.
    </div>
    <div class="statement" style="margin-top:8pt;">
        {!! $tick($kind === 'Found') !!} May I respectfully turn over to your office a/an
        <span class="val">{{ $kind === 'Found' ? ($v['item_description'] ?? '') : '' }}</span> that I found.
    </div>

    <div style="margin-top:14pt;">Thank you.</div>

    <table class="sigs" style="margin-top:22pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => '',
                    's' => $person,
                    'caption' => 'Name and Signature of the Person<br>who Lost/Found the Item',
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>

    <div class="cut"></div>
    <div class="part">Claim Acknowledgment Slip</div>

    <table class="sigs" style="margin-top:14pt;">
        <tr>
            <td style="width:25%;"></td>
            <td style="width:50%; text-align:center;">
                @include('pdf._signer', [
                    'heading' => '',
                    's' => [],
                    'caption' => 'Name and Signature of the Person<br>who Claimed the Item',
                ])
            </td>
            <td style="width:25%;"></td>
        </tr>
    </table>
@endsection