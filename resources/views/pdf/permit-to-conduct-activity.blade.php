@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));

    $activity = $one($v['activity'] ?? '');
    $venue    = $one($v['venue'] ?? '');
    $persons  = $one($v['persons_involved'] ?? '');
    $date = ! empty($v['activity_date']) ? \Carbon\Carbon::parse($v['activity_date'])->format('F j, Y') : '';
    $time = ! empty($v['activity_time'])
        ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $v['activity_time'], 0, 5))->format('g:i A')
        : '';

    // Purpose: one ruled line per row, wrapped at word boundaries, minimum 3 rows (as on the paper form).
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', trim((string) ($v['purposes'] ?? ''))) as $para) {
        foreach (explode("\n", wordwrap($para, 74, "\n", true)) as $line) {
            $rows[] = $line;
        }
    }
    while (count($rows) < 3) { $rows[] = ''; }

    // Position held in the organization.
    $hostedBy = $v['hosted_by'] ?? '';
    $isNonAcademic = $hostedBy === 'Non-Academic Organization';
    $position = $isNonAcademic ? ($v['non_academic_position'] ?? '') : ($v['officer_position'] ?? ($v['position_held'] ?? ''));
    $orgName = match (true) {
        $hostedBy === 'Department' => (string) ($v['department_name'] ?? ''),
        $isNonAcademic => (string) ($v['non_academic_org'] ?? ''),
        default => (string) ($v['officer_organization'] ?? ''),
    };
    $posText = $one(collect([$position !== '' ? $position : 'Not an officer', $orgName])->filter()->implode(', '));

    $adviser = $signers[0] ?? [];
    $vp = $signers[1] ?? [];
    $vpDate = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Permit to Conduct an In-Campus Student Activity Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 18pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 24pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; font-weight: bold; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .purp { position: relative; height: auto; margin-bottom: 11pt; }
    .purp .lbl { position: absolute; left: 0; top: 0; width: 62pt; height: 18pt; line-height: 18pt; font-weight: bold; }
    .purp .rows { margin-left: 64pt; }
    .rrow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }

    .lab { font-weight: bold; }
    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    .pos-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; white-space: nowrap; overflow: hidden; }

    .formno { border-top: .6pt solid #c8c8c8; margin-top: 30pt; padding-top: 4pt; font-size: 10pt; font-weight: bold; font-style: italic; }

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

<div class="title">PERMIT TO CONDUCT AN IN-CAMPUS STUDENT ACTIVITY FORM</div>

{{-- Activity/Event --}}
<div class="ln">
    <div class="lbl" style="left:0; width:84pt;">Activity/Event:</div>
    <div class="val" style="left:86pt; width:401pt; font-size:{{ $fit($activity, 401) }}pt;">{{ $activity }}</div>
</div>

{{-- Purpose/s --}}
<div class="purp">
    <div class="lbl">Purpose/s:</div>
    <div class="rows">
        @foreach ($rows as $line)
            <div class="rrow" style="font-size:{{ $fit($line, 423, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
        @endforeach
    </div>
</div>

{{-- Date / Time / Venue --}}
<div class="ln">
    <div class="lbl" style="left:0; width:142pt;">Date of the Activity/Event:</div>
    <div class="val" style="left:144pt; width:100pt; font-size:{{ $fit($date, 100) }}pt;">{{ $date }}</div>
    <div class="lbl" style="left:252pt; width:32pt;">Time:</div>
    <div class="val" style="left:286pt; width:58pt; font-size:{{ $fit($time, 58) }}pt;">{{ $time }}</div>
    <div class="lbl" style="left:354pt; width:40pt;">Venue:</div>
    <div class="val" style="left:396pt; width:91pt; font-size:{{ $fit($venue, 91) }}pt;">{{ $venue }}</div>
</div>

{{-- Persons Involved --}}
<div class="ln">
    <div class="lbl" style="left:0; width:92pt;">Persons Involved:</div>
    <div class="val" style="left:94pt; width:393pt; font-size:{{ $fit($persons, 393) }}pt;">{{ $persons }}</div>
</div>

{{-- Requested by: student (name + signature) / position held --}}
<div class="lab" style="margin-top:14pt;">Requested by:</div>
<table style="margin-top:2pt;">
    <tr>
        <td style="width:46%;">
            @include('pdf._signer', ['heading' => '', 's' => $studentSigner, 'caption' => 'Name and Signature of the Student'])
        </td>
        <td style="width:8%; text-align:center; vertical-align:bottom; font-weight:bold; padding-bottom:16pt;">/</td>
        <td style="width:46%; vertical-align:bottom;">
            <div style="height:44pt;"></div>
            <div class="pos-name" style="font-size:{{ $fit($posText, 200, 9, 6) }}pt;">{{ $posText }}&nbsp;</div>
            <div class="sig-cap">(Position Held in the Organization)</div>
        </td>
    </tr>
</table>

{{-- Approvals --}}
<table style="margin-top:22pt;">
    <tr>
        <td style="width:46%;" class="lab">RECOMMENDING APPROVAL:</td>
        <td style="width:8%;"></td>
        <td style="width:46%;" class="lab">APPROVED BY:</td>
    </tr>
</table>
<table style="margin-top:4pt;">
    <tr>
        <td style="width:46%;">
            @include('pdf._signer', ['heading' => '', 's' => $adviser, 'caption' => 'Name and Signature of the<br>Organization Adviser/Dept. Chair'])
        </td>
        <td style="width:8%;"></td>
        <td style="width:46%;">
            @include('pdf._signer', ['heading' => '', 's' => $vp, 'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($vpDate)])
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