@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;

    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $name    = trim((string) ($v['student_name'] ?? ''));
    $program = trim((string) ($v['student_program'] ?? ''));
    $vpName  = trim((string) ($v['vpsd_name'] ?? ''));
    $date    = ($submittedAt ?? $issuedAt)?->format('F j, Y') ?? '';

    $left = ['mon_thu', 'tue_fri', 'wed'];
    $right = ['sat', 'sun'];
    $sum = fn (array $keys, string $h) => array_sum(array_map(
        fn ($k) => SA::halfHours($v, $k, $h) * SA::DAYS[$k][1],
        $keys
    ));

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Application for Student Assistantship Form {{ $code }}</title>
<style>
    @page { margin: 34pt 54pt 80pt 54pt; }
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 16pt 0 16pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 30pt; width: 487pt; }
    .ln.s { height: 18pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .cap { top: 15pt; font-size: 7.5pt; font-style: italic; font-weight: bold; text-align: center; line-height: 9pt; white-space: nowrap; overflow: hidden; }

    .statement { text-align: justify; line-height: 1.5; }

    table.sched { margin-top: 6pt; }
    table.sched th, table.sched td { border: .6pt solid #777; padding: 2.5pt 2pt; text-align: center; font-size: 7.5pt; vertical-align: middle; }
    table.sched th { font-weight: bold; background: #f3f3f3; }
    table.sched td.nb { border: none; width: 6pt; }
    table.sched td.day { text-align: left; font-weight: bold; width: 62pt; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    table.sg { page-break-inside: avoid; }


    .foot { position: fixed; left: 0; right: 0; bottom: -64pt; height: 60pt; border-top: .6pt solid #c8c8c8; font-family: 'DejaVu Sans', sans-serif; }
    .foot > div { position: absolute; white-space: nowrap; }
    .foot-qr { left: 0; top: 5pt; }
    .foot-k { left: 72pt; top: 5pt; font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; }
    .foot-t { left: 72pt; top: 16pt; font-size: 7.5pt; color: #444; }
    .foot-r { left: 72pt; top: 29pt; font-size: 7.5pt; color: #444; }
    .foot-f { left: 72pt; top: 41pt; font-size: 7.5pt; color: #444; }
    .foot-v { font-size: 8pt; font-weight: bold; color: #111; }
    .foot-form { right: 0; top: 5pt; font-size: 7pt; color: #888; }
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

<div class="title">APPLICATION FOR STUDENT ASSISTANTSHIP FORM</div>

{{-- Addressee (left) and date (right) --}}
<div class="ln s">
    <div class="val" style="left:0; width:250pt; font-size:{{ $fit($vpName, 250) }}pt;">{{ $vpName }}</div>
    <div class="lbl" style="left:340pt; width:34pt;">Date:</div>
    <div class="val" style="left:376pt; width:111pt; font-size:{{ $fit($date, 111) }}pt;">{{ $date }}</div>
</div>
<div style="margin: 2pt 0 12pt 8pt;">VP, Student Development</div>

<div>Dear Sir/Ma'am,</div>

{{-- I am ___, a ___ student of this university. --}}
<div class="ln" style="margin-top:8pt;">
    <div class="lbl" style="left:30pt; width:20pt;">I am</div>
    <div class="val" style="left:52pt; width:138pt; font-size:{{ $fit($name, 138) }}pt;">{{ $name }}</div>
    <div class="cap" style="left:52pt; width:138pt;">(Name of Student)</div>
    <div class="lbl" style="left:194pt; width:16pt;">, a</div>
    <div class="val" style="left:212pt; width:130pt; font-size:{{ $fit($program, 130) }}pt;">{{ $program }}</div>
    <div class="cap" style="left:212pt; width:130pt;">(Program/Year/Section)</div>
    <div class="lbl" style="left:346pt; width:141pt;">student of this university.</div>
</div>

<div class="statement">
    I would like to apply as <strong>Student Assistant (SA)</strong> in which I shall render student
    assistantship services during my vacant periods. The total number of hours per week that I shall
    report to the office assigned to me shall be twelve (12) hours with no duty hours exceeding
    four (4) hours a day.
</div>

<div style="margin-top:8pt;">Below is my schedule of vacant periods.</div>

<table class="sched">
    <tr>
        <th rowspan="2">Days</th><th colspan="4">Schedule of Vacant Time</th>
        <td class="nb" rowspan="2"></td>
        <th rowspan="2">Days</th><th colspan="4">Schedule of Vacant Time</th>
    </tr>
    <tr>
        <th>AM</th><th>VACANT HOURS</th><th>PM</th><th>VACANT HOURS</th>
        <th>AM</th><th>VACANT HOURS</th><th>PM</th><th>VACANT HOURS</th>
    </tr>
@for ($i = 0; $i < 3; $i++)
    @php $l = $left[$i]; $r = $right[$i] ?? null; @endphp
    <tr>
        <td class="day">{{ SA::DAYS[$l][0] }}</td>
        <td>{{ SA::range($v, $l, 'am') }}</td><td>{{ SA::fmt(SA::halfHours($v, $l, 'am')) }}</td>
        <td>{{ SA::range($v, $l, 'pm') }}</td><td>{{ SA::fmt(SA::halfHours($v, $l, 'pm')) }}</td>
        <td class="nb"></td>
        @if ($r)
            <td class="day">{{ SA::DAYS[$r][0] }}</td>
            <td>{{ SA::range($v, $r, 'am') }}</td><td>{{ SA::fmt(SA::halfHours($v, $r, 'am')) }}</td>
            <td>{{ SA::range($v, $r, 'pm') }}</td><td>{{ SA::fmt(SA::halfHours($v, $r, 'pm')) }}</td>
        @else
            <td colspan="5" style="border:none;"></td>
        @endif
    </tr>
@endfor
    <tr>
        <td class="day">TOTAL:</td>
        <td></td><td>{{ SA::fmt($sum($left, 'am')) }}</td><td></td><td>{{ SA::fmt($sum($left, 'pm')) }}</td>
        <td class="nb"></td>
        <td class="day">TOTAL:</td>
        <td></td><td>{{ SA::fmt($sum($right, 'am')) }}</td><td></td><td>{{ SA::fmt($sum($right, 'pm')) }}</td>
    </tr>
</table>

<div style="margin-top:5pt; font-size:7.5pt; color:#444;">
    Vacant hours are per day. "Monday &amp; Thursday" and "Tuesday &amp; Friday" each count for two days in the TOTAL.
    <strong>Total duty hours per week: {{ SA::fmt(SA::weeklyTotal($v)) }}</strong>
</div>

<table class="sg" style="margin-top:20pt;">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;">
            <div class="sig-cap" style="text-align:left; margin-bottom:2pt;">Respectfully yours,</div>
            @include('pdf._signer', [
                'heading' => '',
                's' => $studentSigner,
                'caption' => 'Signature over Printed Name of the SA Applicant<br>Contact Number: <strong>' . e($v['contact_number'] ?? '') . '</strong>',
            ])
        </td>
    </tr>
</table>


<div class="foot">
    <div class="foot-qr">@if ($qr)<img alt="QR code for verifying this document" src="{{ $qr }}" width="52" height="52">@elseif ($preview ?? false)<span class="ph-qr" style="width:52px;height:52px;">QR code<br>on issue</span>@endif</div>
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