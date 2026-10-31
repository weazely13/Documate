@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 12, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));

    // Signatory version: the nominee is the picked student and the issuer is the nominator.
    $isStaff = ! empty($v['reporter_name']);

    $name        = $one(($v['nominee_name'] ?? '') ?: ($isStaff ? ($v['student_name'] ?? '') : ''));
    $college     = $one(($v['nominee_college'] ?? '') ?: ($v['student_college'] ?? ''));
    $program     = $one(($v['nominee_program'] ?? '') ?: ($v['student_course'] ?? ''));
    $yearSection = $one(($v['nominee_year_section'] ?? '') ?: ($v['student_year_section'] ?? ''));
    $address     = $one(($v['nominee_address'] ?? '') ?: ($v['student_address'] ?? ''));
    $contact     = $one(($v['nominee_contact'] ?? '') ?: ($v['student_contact'] ?? ''));

    // Address: split at a word boundary across the two ruled lines; each line stays on one line (shrunk to fit).
    $parts = explode("\n", wordwrap($address, 58, "\n", true), 2);
    $addr1 = $parts[0] ?? '';
    $addr2 = str_replace("\n", ' ', $parts[1] ?? '');

    $docTitle   = $docTitle ?? 'COLLEGE MODEL STUDENT NOMINATION FORM';
    $nominator  = $isStaff ? $reporterSigner : $studentSigner;
    $dateSigned = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $lookup     = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $docTitle }} {{ $code }}</title>
<style>
    @page { margin: 40pt 54pt 96pt 54pt; }
    body { font-family: 'Times', 'Times New Roman', serif; font-size: 12pt; color: #000; margin: 0; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td { padding: 0; vertical-align: top; }
    .sans { font-family: 'Helvetica', Arial, sans-serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    /* Letterhead */
    table.hdr { table-layout: auto; width: 360pt; margin: 0 auto; }
    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 9.5pt; line-height: 1.2; }
    .hdr .b { font-weight: bold; }
    .hdr .blue { color: #1a6fc4; text-decoration: underline; }
    .title { text-align: center; font-weight: bold; font-size: 11.5pt; margin: 20pt 0 26pt; }

    /* One-line form rows (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 26pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 15pt; line-height: 15pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 15pt; line-height: 15pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 12pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }

    .note { margin-top: 26pt; font-size: 7.5pt; font-style: italic; }

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
        <td style="width:92pt; text-align:center;">@if ($seal)<img alt="Leyte Normal University seal" src="{{ $seal }}" width="76" height="76">@endif</td>
        <td class="sans t">
            <div class="b">LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div class="blue">vpsdas@lnu.edu.ph</div>
            <div style="color:#1a6fc4;">053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">{{ $docTitle }}</div>

<div class="ln">
    <div class="lbl" style="left:0; width:124pt;">Name of Nominee:</div>
    <div class="val" style="left:127pt; width:360pt; font-size:{{ $fit($name, 360) }}pt;">{{ $name }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:52pt;">College:</div>
    <div class="val" style="left:54pt; width:433pt; font-size:{{ $fit($college, 433) }}pt;">{{ $college }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:208pt;">Program Being Pursued of Nominee:</div>
    <div class="val" style="left:211pt; width:276pt; font-size:{{ $fit($program, 276) }}pt;">{{ $program }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:102pt;">Year and Section:</div>
    <div class="val" style="left:105pt; width:382pt; font-size:{{ $fit($yearSection, 382) }}pt;">{{ $yearSection }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:82pt;">Address:</div>
    <div class="val" style="left:85pt; width:402pt; font-size:{{ $fit($addr1, 402) }}pt;">{{ $addr1 }}</div>
</div>
<div class="ln" style="margin-bottom:6pt;">
    <div class="val" style="left:0; width:487pt; font-size:{{ $fit($addr2, 487) }}pt;">{{ $addr2 }}&nbsp;</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:124pt;">Contact Number:</div>
    <div class="val" style="left:127pt; width:360pt; font-size:{{ $fit($contact, 360) }}pt;">{{ $contact }}</div>
</div>

<table style="margin-top:22pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', [
                'heading' => '',
                's' => $nominator,
                'caption' => 'Name and Signature of the Nominator<br>Date: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>',
            ])
        </td>
        <td style="width:25%;"></td>
    </tr>
</table>

<div class="note">The data collected here will be for the sole use of the legitimate interests of the university as an educational institution.</div>

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