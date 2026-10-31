{{-- One clearance per A4 page, same mechanism as the Admission Slip.
     Child views fill: doc_title, copy. Needs: $code, $formCode, $fingerprint, $seal, $qr, $preview, $issuedAt. --}}
@php
    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('doc_title') {{ $code }}</title>
<style>
    @page { margin: 34pt 54pt 96pt 54pt; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; color: #000; margin: 0; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td { padding: 0; vertical-align: top; }
    .serif { font-family: 'Times', 'Times New Roman', serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 10pt; line-height: 1.15; }
    .hdr .b { font-weight: bold; }
    .hdr .blue { color: #1a6fc4; }
    .title { text-align: center; font-weight: bold; font-size: 11.5pt; margin: 10pt 0 10pt; }

    /* Full-width certification lines: label | stretchy underlined value | trailing text */
    table.cl { table-layout: auto; width: 100%; margin-top: 2pt; }
    table.cl td { vertical-align: bottom; }
    .cl-pre, .cl-post { width: 1%; white-space: nowrap; line-height: 13pt; }
    .cl-pre  { padding-right: 3pt; text-align: left; }
    .cl-post { padding-left: 3pt; text-align: right; }
    .cl-val  { border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; line-height: 13pt; }
    .cl-cap  { font-size: 7.5pt; font-style: italic; text-align: center; line-height: 10pt; padding-bottom: 5pt; }

    /* Justified closing paragraph */
    .stmt { text-align: justify; line-height: 20pt; margin-top: 4pt; width: 100%; }

    .stmt { line-height: 20pt; text-align: justify; margin-top: 2pt; }
    .ul { border-bottom: .6pt solid #000; font-weight: bold; padding: 0 4pt; }
    .rule { border-top: 2pt solid #000; margin: 10pt 0 6pt; }

    /* Signature grid */
    table.sgrid { margin-top: 4pt; page-break-inside: avoid; }
    table.sgrid td.col { width: 46%; text-align: center; }
    table.sgrid td.gap { width: 8%; }
    .sline { border-top: .6pt solid #000; padding-top: 2pt; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .scap { font-size: 9pt; line-height: 1.25; text-align: center; }
    .sseal { font-size: 6pt; color: #777; text-align: center; margin-top: 2pt; }


    /* Verification footer (identical to the Admission Slip) */
    .foot { position: fixed; left: 0; right: 0; bottom: -78pt; height: 72pt; border-top: .6pt solid #c8c8c8; font-family: 'DejaVu Sans', sans-serif; }
    .foot > div { position: absolute; white-space: nowrap; }
    .foot-qr { left: 0; top: 8pt; }
    .foot-k { left: 72pt; top: 9pt; font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; }
    .foot-t { left: 72pt; top: 21pt; font-size: 7.5pt; color: #444; }
    .foot-r { left: 72pt; top: 35pt; font-size: 7.5pt; color: #444; }
    .foot-f { left: 72pt; top: 47pt; font-size: 7.5pt; color: #444; }
    .foot-v { font-size: 8pt; font-weight: bold; color: #111; }
    .foot-form { right: 0; top: 9pt; font-size: 7pt; color: #888; }
    @if ($preview ?? false)
        html { background: #e5e7eb; }
        body { max-width: 520pt; margin: 16pt auto; padding: 30pt 40pt; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.15); position: relative; }
        .watermark { position: absolute; top: 6pt; right: 10pt; font-size: 7pt; letter-spacing: 1pt; color: #b45309; background: #fffbeb; border: .6pt solid #fcd34d; padding: 2pt 6pt; border-radius: 3pt; }
    @endif
</style>
</head>
<body>

<table class="hdr">
    <tr>
        <td style="width:70pt;">@if ($seal)<img alt="Leyte Normal University seal" src="{{ $seal }}" width="58" height="58">@endif</td>
        <td class="t serif">
            <div>LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div class="blue">vpsdas@lnu.edu.ph</div>
            <div class="blue">053 888 0855 local 260</div>
        </td>
    </tr>
</table>

@yield('copy')


<div class="foot">
    <div class="foot-qr">@if ($qr)<img alt="QR code for verifying this document" src="{{ $qr }}" width="58" height="58">@elseif ($preview ?? false)<span class="ph-qr" style="width:58px;height:58px;">QR code<br>on issue</span>@endif</div>
    <div class="foot-k">Verify this document</div>
    <div class="foot-t">Scan the QR code, or go to <strong>{{ $lookup }}</strong> and enter:</div>
    <div class="foot-r">Reference &nbsp;<span class="foot-v mono">@if ($code){{ $code }}@else<span class="ph-text">assigned on submit</span>@endif</span></div>
    <div class="foot-f">Fingerprint &nbsp;<span class="foot-v mono">@if ($fingerprint){{ $fingerprint }}@elseif ($preview ?? false)<span class="ph-text">assigned when issued</span>@else-@endif</span></div>
    <div class="foot-form">{{ $formCode }}</div>
</div>

@if ($preview ?? false)
    <div class="watermark">PREVIEW{{ $issuedAt ? '' : ' · NOT YET ISSUED' }}</div>
@endif
</body>
</html>