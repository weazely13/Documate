@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));

    $name    = $one($v['student_name'] ?? '');
    $number  = $one($v['student_number'] ?? '');
    $program = $one($v['student_program'] ?? '');
    $hearing = ! empty($v['hearing_date']) ? \Carbon\Carbon::parse($v['hearing_date'])->format('F j, Y') : '';
    $tick = fn ($on) => '[&nbsp;' . ($on ? '<b>X</b>' : '&nbsp;&nbsp;') . '&nbsp;]';

    $chair = $signers[0] ?? [];

    // Same order as DisciplinaryCaseReport::chain(): filled member rows, after the Chairman.
    $memberSigners = [];
    $k = 1;
    foreach (range(1, 5) as $i) {
        $typed = trim((string) ($v["member_{$i}_name"] ?? ''));
        if ($typed === '' && trim((string) ($v["member_{$i}_email"] ?? '')) === '') {
            continue;
        }
        $s = $signers[$k] ?? [];
        $s['name'] = ($s['name'] ?? '') ?: $typed;
        $memberSigners[] = $s;
        $k++;
    }

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Disciplinary Case Report Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 20pt 0 16pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 22pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .box { border: .8pt solid #000; margin: 0 14pt 10pt; padding: 5pt 10pt 6pt; font-size: 9.5pt; line-height: 1.35; }
    .box .bl { font-family: 'Times', 'Times New Roman', serif; font-weight: bold; font-size: 10.5pt; margin-bottom: 3pt; }
    .box .bt { font-weight: bold; }
    .rec { font-family: 'Times', 'Times New Roman', serif; font-weight: bold; font-size: 10pt; line-height: 1.7; }
    .rec .ul { border-bottom: .6pt solid #000; padding: 0 8pt; }

    .lab { font-weight: bold; }
    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    table.sg { page-break-inside: avoid; }

    .formno { border-top: .6pt solid #c8c8c8; margin-top: 18pt; padding-top: 4pt; font-size: 10pt; font-weight: bold; font-style: italic; }

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
            <div>vpsdas@lnu.edu.ph</div>
            <div>053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">DISCIPLINARY CASE REPORT FORM</div>

{{-- Student --}}
<div class="ln">
    <div class="lbl" style="left:0; width:90pt;">Student's Name:</div>
    <div class="val" style="left:92pt; width:230pt; font-size:{{ $fit($name, 230) }}pt;">{{ $name }}</div>
    <div class="lbl" style="left:336pt; width:70pt;">I.D. Number:</div>
    <div class="val" style="left:408pt; width:79pt; font-size:{{ $fit($number, 79) }}pt;">{{ $number }}</div>
</div>
<div class="ln" style="margin-bottom:6pt;">
    <div class="lbl" style="left:0; width:130pt;">Program, Year and Section:</div>
    <div class="val" style="left:132pt; width:190pt; font-size:{{ $fit($program, 190) }}pt;">{{ $program }}</div>
</div>

{{-- Boxes --}}
<div class="box" style="min-height:22pt;">
    <div class="bl">VIOLATION:</div>
    <div class="bt">{!! nl2br(e($v['violation'] ?? '')) !!}</div>
</div>
<div class="box" style="min-height:80pt;">
    <div class="bl">INCIDENT REPORT:</div>
    <div class="bt">{!! nl2br(e($v['incident_report'] ?? '')) !!}</div>
</div>
<div class="box" style="min-height:80pt;">
    <div class="bl">RESOLUTION:</div>
    <div class="bt">{!! nl2br(e($v['resolution'] ?? '')) !!}</div>
</div>
<div class="box">
    <div class="bl">RECOMMENDATIONS:</div>
    <div class="rec">
        {!! $tick(! empty($v['rec_closed'])) !!} Closed/ Resolved
        &nbsp;&nbsp; {!! $tick(! empty($v['rec_hearing'])) !!} For continuing hearing - Hearing Date:
        <span class="ul">{{ ! empty($v['rec_hearing']) ? $hearing : '' }}&nbsp;</span><br>
        {!! $tick(! empty($v['rec_formal'])) !!} For Formal Investigation
    </div>
</div>

{{-- Report given by --}}
<div class="lab" style="margin-top:14pt;">Report Given By:</div>

<table class="sg" style="margin-top:4pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $chair, 'caption' => 'Name and Signature of the Chairman'])
        </td>
        <td style="width:25%;"></td>
    </tr>
</table>

@foreach (array_chunk($memberSigners, 2) as $row)
    <table class="sg" style="margin-top:12pt;">
        <tr>
            <td style="width:46%;">
                @include('pdf._signer', ['heading' => '', 's' => $row[0], 'caption' => 'Name and Signature of the Member'])
            </td>
            <td style="width:8%;"></td>
            <td style="width:46%;">
                @if (isset($row[1]))
                    @include('pdf._signer', ['heading' => '', 's' => $row[1], 'caption' => 'Name and Signature of the Member'])
                @endif
            </td>
        </tr>
    </table>
@endforeach

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