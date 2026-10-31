@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $vpName  = trim((string) ($v['vpsd_name'] ?? ''));
    $name    = trim((string) ($v['student_name'] ?? ''));
    $program = trim((string) ($v['student_program'] ?? ''));
    $rel     = strtolower(trim((string) ($v['relationship'] ?? '')));
    $relText = $rel !== '' ? $rel : 'son/daughter/ward';
    $pronoun = match ($rel) { 'son' => 'him', 'daughter' => 'her', default => 'him/her' };

    $parent = $signers[0] ?? [];
    $signedOn = ! empty($parent['signed_at']) ? $parent['signed_at']->format('F j, Y') : '';

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Parent's Consent for Student Assistantship Form {{ $code }}</title>
<style>
    @page { margin: 40pt 54pt 96pt 54pt; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; color: #000; margin: 0; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td { padding: 0; vertical-align: top; }
    .serif { font-family: 'Times', 'Times New Roman', serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    /* Letterhead: auto layout so Dompdf honours the seal cell width (fixed layout splits the row 50/50) */
    table.hdr { table-layout: auto; width: auto; }
    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 10.5pt; line-height: 1.15; }
    .hdr .b { font-weight: bold; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 24pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 30pt; width: 487pt; }
    .ln.s { height: 18pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .cap { top: 15pt; font-size: 7.5pt; font-style: italic; font-weight: bold; text-align: center; line-height: 9pt; white-space: nowrap; overflow: hidden; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }


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
        <td style="width:82pt;">@if ($seal)<img alt="Leyte Normal University seal" src="{{ $seal }}" width="68" height="68">@endif</td>
        <td class="t serif" style="width:405pt;">
            <div>LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div>vpsdas@lnu.edu.ph</div>
            <div>053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">PARENT'S CONSENT FOR STUDENT ASSISTANTSHIP FORM</div>

{{-- Addressee --}}
<div class="ln s">
    <div class="val" style="left:0; width:250pt; font-size:{{ $fit($vpName, 250) }}pt;">{{ $vpName }}</div>
</div>
<div style="margin: 2pt 0 22pt 40pt;">VP, Student Development</div>

<div style="margin-bottom:6pt;">This is to inform the office of the Vice President for Student Development that</div>

{{-- ___, my son/daughter/ward, is a/an ___ --}}
<div class="ln">
    <div class="val" style="left:0; width:165pt; font-size:{{ $fit($name, 165) }}pt;">{{ $name }}</div>
    <div class="cap" style="left:0; width:165pt;">(Name of Student)</div>
    <div class="lbl" style="left:169pt; width:132pt;">, my {{ $relText }}, is a/an</div>
    <div class="val" style="left:303pt; width:184pt; font-size:{{ $fit($program, 184) }}pt;">{{ $program }}</div>
    <div class="cap" style="left:303pt; width:184pt;">(Program/Year/Section)</div>
</div>

<div style="line-height:1.5;">
    student of the University and that I am allowing {{ $pronoun }} to work as student assistant in your institution.
</div>

{{-- Signature --}}
<table style="margin-top:34pt;">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;">
            <div style="margin-bottom:2pt;">Very truly yours,</div>
            @include('pdf._signer', [
                'heading' => '',
                's' => $parent,
                'caption' => 'Name and Signature of the Parent/Guardian<br>Date Signed: ' . e($signedOn),
            ])
        </td>
    </tr>
</table>


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