@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $name    = (string) ($v['student_name'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $number  = (string) ($v['student_number'] ?? '');
    $subject = trim(preg_replace('/\s+/', ' ', (string) ($v['subject'] ?? '')));

    $when = ! empty($v['appointment_date']) ? \Carbon\Carbon::parse($v['appointment_date'])->format('F j, Y') : '';
    $time = ! empty($v['appointment_time'])
        ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $v['appointment_time'], 0, 5))->format('g:i A')
        : '';

    $slipDate = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    $vp = $signers[0] ?? [];
    $vpDate = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Appointment Slip Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 14pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 30pt; width: 487pt; }
    .ln.s { height: 20pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .val.left { text-align: left; }
    .ln .cap { top: 15pt; font-size: 7.5pt; font-style: italic; text-align: center; line-height: 9pt; white-space: nowrap; overflow: hidden; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }

    .formno { border-top: 1.2pt solid #000; margin-top: 40pt; padding-top: 4pt; font-size: 10pt; font-weight: bold; font-style: italic; }

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

<div class="title">STUDENT APPOINTMENT SLIP FORM</div>

{{-- Date (right) --}}
<div class="ln">
    <div class="val" style="left:367pt; width:120pt; font-size:{{ $fit($slipDate, 120) }}pt;">{{ $slipDate }}</div>
    <div class="cap" style="left:367pt; width:120pt;">Date</div>
</div>

{{-- To: --}}
<div class="ln s">
    <div class="lbl" style="left:0; width:36pt; font-weight:bold;">To:</div>
    <div class="val left" style="left:40pt; width:300pt; font-size:{{ $fit($name, 300) }}pt;">{{ $name }}</div>
</div>
<div class="ln s">
    <div class="val left" style="left:40pt; width:300pt; font-weight:normal; font-size:{{ $fit($program, 300) }}pt;">{{ $program }}</div>
</div>
<div class="ln" style="height:34pt;">
    <div class="val left" style="left:40pt; width:300pt; font-weight:normal; font-size:{{ $fit($number, 300) }}pt;">{{ $number }}</div>
</div>

{{-- RE: --}}
<div class="ln" style="height:34pt;">
    <div class="lbl" style="left:0; width:36pt; font-weight:bold;">RE:</div>
    <div class="val left" style="left:40pt; width:447pt; font-size:{{ $fit($subject, 447, 10, 6) }}pt;">{{ $subject }}</div>
</div>

{{-- Statement --}}
<div class="ln s" style="margin-top:6pt;">
    <div class="lbl" style="left:30pt; width:457pt;">Please see the Vice President for Student</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:80pt;">Development at</div>
    <div class="val" style="left:82pt; width:90pt; font-size:{{ $fit($time, 90) }}pt;">{{ $time }}</div>
    <div class="cap" style="left:82pt; width:90pt;">(Time)</div>
    <div class="lbl" style="left:176pt; width:16pt;">on</div>
    <div class="val" style="left:194pt; width:180pt; font-size:{{ $fit($when, 180) }}pt;">{{ $when }}</div>
    <div class="cap" style="left:194pt; width:180pt;">(Date)</div>
    <div class="lbl" style="left:376pt; width:8pt;">.</div>
</div>

{{-- Signature (right) --}}
<table style="margin-top:22pt;">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $vp, 'caption' => 'Name and Signature of the<br>Vice President for Student Development<br>Date Signed: ' . e($vpDate)])
        </td>
    </tr>
</table>

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