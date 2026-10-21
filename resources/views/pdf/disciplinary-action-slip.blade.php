@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one  = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));
    $fmtD = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('F j, Y') : '';
    $fmtT = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $t, 0, 5))->format('g:i A') : '';

    $name    = $one($v['student_name'] ?? '');
    $course  = $one($v['student_course'] ?? '');
    $ys      = $one($v['student_year_section'] ?? '');
    if ($course === '' && $ys === '') {
        $course = $one($v['student_program'] ?? '');   // older records
    }
    $offense = $one($v['offense'] ?? '');
    $when    = trim($fmtD($v['offense_date'] ?? null) . (! empty($v['offense_time']) ? ' at ' . $fmtT($v['offense_time']) : ''));

    $cDate = $fmtD($v['counseling_date'] ?? null);
    $cTime = trim($fmtT($v['counseling_time'] ?? null)
        . (! empty($v['counseling_time_end']) ? ' to ' . $fmtT($v['counseling_time_end']) : ''));

    $reporter   = $reporterSigner;
    $gc         = $signers[0] ?? [];
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Disciplinary Action Slip Form {{ $code }}</title>
<style>
    @page { margin: 40pt 54pt 96pt 54pt; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; color: #000; margin: 0; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td { padding: 0; vertical-align: top; }
    .serif { font-family: 'Times', 'Times New Roman', serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    table.hdr { table-layout: auto; width: auto; }
    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 10.5pt; line-height: 1.15; }
    .hdr .b { font-weight: bold; }
    .hdr .blue { color: #1a6fc4; font-size: 10pt; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 20pt 0 16pt; }

    .ln { position: relative; height: 24pt; width: 487pt; }
    .ln.s { height: 20pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; font-weight: bold; }
    .ln .lbl.n { font-weight: normal; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .cut { border-top: .8pt dashed #888; margin: 20pt 0 12pt; }
    .part { text-align: center; font-size: 10.5pt; margin-bottom: 12pt; }
    .gch { font-weight: bold; font-size: 10.5pt; margin-bottom: 6pt; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    table.sg { page-break-inside: avoid; }

    .formno { border-top: .6pt solid #c8c8c8; margin-top: 24pt; padding-top: 4pt; font-size: 10pt; font-weight: bold; font-style: italic; }

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
        <td style="width:82pt;">@if ($seal)<img src="{{ $seal }}" width="68" height="68">@endif</td>
        <td class="t serif" style="width:405pt;">
            <div>LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div class="blue">vpsdas@lnu.edu.ph</div>
            <div class="blue">053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">DISCIPLINARY ACTION SLIP FORM</div>

{{-- Student --}}
<div class="ln">
    <div class="lbl" style="left:0; width:98pt;">Name of Student:</div>
    <div class="val" style="left:100pt; width:387pt; font-size:{{ $fit($name, 387) }}pt;">{{ $name }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:48pt;">Program:</div>
    <div class="val" style="left:50pt; width:196pt; font-size:{{ $fit($course, 196) }}pt;">{{ $course }}</div>
    <div class="lbl" style="left:252pt; width:92pt;">Year and Section:</div>
    <div class="val" style="left:346pt; width:141pt; font-size:{{ $fit($ys, 141) }}pt;">{{ $ys }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:90pt;">Type of Offense:</div>
    <div class="val" style="left:92pt; width:395pt; font-size:{{ $fit($offense, 395) }}pt;">{{ $offense }}</div>
</div>
<div class="ln s">
    <div class="lbl" style="left:0; width:487pt;">Date &amp; Time the Offense was Committed:</div>
</div>
<div class="ln">
    <div class="val" style="left:0; width:487pt; font-size:{{ $fit($when, 487) }}pt;">{{ $when }}</div>
</div>

{{-- Reported by (right) --}}
<table class="sg" style="margin-top:10pt;">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $reporter, 'caption' => 'Name and Signature of the<br>Security Guard/Faculty/Personnel'])
        </td>
    </tr>
</table>

<div class="cut"></div>
<div class="part">VIOLATION CLEARANCE SLIP</div>

<div class="gch">Guidance Counselling (every Wednesday)</div>
<div class="ln s">
    <div class="lbl n" style="left:0; width:34pt;">Date:</div>
    <div class="val" style="left:36pt; width:170pt; font-size:{{ $fit($cDate, 170) }}pt;">{{ $cDate }}</div>
</div>
<div class="ln s">
    <div class="lbl n" style="left:0; width:34pt;">Time:</div>
    <div class="val" style="left:36pt; width:170pt; font-size:{{ $fit($cTime, 170) }}pt;">{{ $cTime }}</div>
</div>

<table class="sg" style="margin-top:10pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $gc, 'caption' => 'Name and Signature of the<br>Guidance Counselor'])
        </td>
        <td style="width:25%;"></td>
    </tr>
</table>

<div class="ln s" style="margin-top:14pt;">
    <div class="lbl n" style="left:0; width:64pt;">Date Issued:</div>
    <div class="val" style="left:66pt; width:170pt; font-size:{{ $fit($dateIssued, 170) }}pt;">{{ $dateIssued }}</div>
</div>

<div class="formno">{{ $formCode }}</div>

@unless ($preview ?? false)
<div class="foot">
    <div class="foot-qr">@if ($qr)<img src="{{ $qr }}" width="58" height="58">@endif</div>
    <div class="foot-k">Verify this document</div>
    <div class="foot-t">Scan the QR code, or go to <strong>{{ $lookup }}</strong> and enter:</div>
    <div class="foot-r">Reference &nbsp;<span class="foot-v mono">{{ $code }}</span></div>
    <div class="foot-f">Fingerprint &nbsp;<span class="foot-v mono">{{ $fingerprint ?: '-' }}</span></div>
    <div class="foot-form">{{ $formCode }}</div>
</div>
@endunless

@if ($preview ?? false)
    <div class="watermark">PREVIEW{{ $issuedAt ? '' : ' · NOT YET ISSUED' }}</div>
@endif
</body>
</html>