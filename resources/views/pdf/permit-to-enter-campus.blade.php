@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $fmtD = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('F j, Y') : '';
    $fmtT = fn ($t) => $t ? \Carbon\Carbon::createFromFormat('H:i', substr((string) $t, 0, 5))->format('g:i A') : '';

    $name    = (string) ($v['student_name'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $others  = ! empty($v['others']) ? (string) ($v['others_specify'] ?? '') : '';
    $validUntil = $fmtD($v['valid_until'] ?? null);

    $cDate = $fmtD($v['counseling_date'] ?? null);
    $cTime = trim($fmtT($v['counseling_time'] ?? null)
        . (! empty($v['counseling_time_end']) ? ' to ' . $fmtT($v['counseling_time_end']) : ''));

    $vp = collect($signers)->firstWhere('label', 'Approved') ?? [];
    $gc = collect($signers)->firstWhere('label', 'Counselling Completed') ?? [];
    $vpDate = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
    $gcDate = ! empty($gc['signed_at']) ? $gc['signed_at']->format('F j, Y') : '';
    $dateIssued = $issuedAt ? $issuedAt->format('F j, Y') : '';

    // "( X )" / "(    )" like the paper form.
    $box = fn ($on) => '(&nbsp;' . ($on ? '<b>X</b>' : '&nbsp;&nbsp;') . '&nbsp;)';

    // Reason: one ruled line per row, wrapped at word boundaries, minimum 2 rows (as on the paper form).
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', trim((string) ($v['reasons'] ?? ''))) as $para) {
        foreach (explode("\n", wordwrap($para, 88, "\n", true)) as $line) {
            $rows[] = $line;
        }
    }
    while (count($rows) < 2) { $rows[] = ''; }

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Permit to Enter the Campus/to Attend Classes Form {{ $code }}</title>
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
    .hdr .blue { color: #1a6fc4; text-decoration: underline; font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 20pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 30pt; width: 487pt; }
    .ln.s { height: 20pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .cap { top: 15pt; font-size: 7.5pt; font-style: italic; text-align: center; line-height: 9pt; white-space: nowrap; overflow: hidden; }
    .cited { height: 14pt; line-height: 14pt; }

    .rrow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }

    .lab { font-weight: bold; font-style: italic; }
    .gcb { font-style: italic; }
    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }

    .issued { margin-top: 18pt; line-height: 13pt; }
    .issued .ul { border-bottom: .6pt solid #000; font-weight: bold; padding: 0 6pt; }
    .note { margin-top: 14pt; font-size: 7pt; font-weight: bold; text-align: center; }

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
            <div class="blue">lnustudentdevelopment@lnu.edu.ph</div>
        </td>
    </tr>
</table>

<div class="title">PERMIT TO ENTER THE CAMPUS/TO ATTEND CLASSES FORM</div>

{{-- Line 1 --}}
<div class="ln">
    <div class="lbl" style="left:0; width:140pt;">This is to grant permission to</div>
    <div class="val" style="left:142pt; width:150pt; font-size:{{ $fit($name, 150) }}pt;">{{ $name }}</div>
    <div class="cap" style="left:142pt; width:150pt;">(Name of Student)</div>
    <div class="lbl" style="left:294pt; width:32pt;">, a/an</div>
    <div class="val" style="left:328pt; width:152pt; font-size:{{ $fit($program, 152) }}pt;">{{ $program }}</div>
    <div class="cap" style="left:328pt; width:152pt;">(Program, Year and Section)</div>
    <div class="lbl" style="left:481pt; width:6pt;">,</div>
</div>

{{-- Line 2 --}}
<div class="ln s">
    <div class="lbl" style="left:0; width:220pt;">to enter the campus to attend classes</div>
</div>

{{-- Checkboxes --}}
<div class="ln s">
    <div class="lbl" style="left:28pt; width:150pt;">{!! $box(! empty($v['no_id'])) !!} without an ID</div>
    <div class="lbl" style="left:190pt; width:200pt;">{!! $box(! empty($v['no_uniform'])) !!} not wearing school uniform</div>
</div>
<div class="ln s">
    <div class="lbl" style="left:28pt; width:122pt;">{!! $box(! empty($v['others'])) !!} others, pls. specify</div>
    <div class="val" style="left:152pt; width:250pt; text-align:left; font-size:{{ $fit($others, 250) }}pt;">{{ $others }}</div>
</div>

<div class="cited">for the reason/s cited below:</div>

<div style="margin-top:4pt;">
    @foreach ($rows as $line)
        <div class="rrow" style="font-size:{{ $fit($line, 487, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
    @endforeach
</div>

{{-- Valid until --}}
<div class="ln s" style="margin-top:8pt;">
    <div class="lbl" style="left:0; width:136pt;">This permit is valid only until</div>
    <div class="val" style="left:138pt; width:150pt; font-size:{{ $fit($validUntil, 150) }}pt;">{{ $validUntil }}</div>
    <div class="lbl" style="left:292pt; width:195pt;">(to be filled out by the VPSD).</div>
</div>

{{-- Signatures: Guidance Counseling (left), Vice President (right) --}}
<table style="margin-top:14pt;">
    <tr>
        <td style="width:46%;">
            <div class="gcb">Guidance Counseling (every Wednesday)</div>
            <div class="ln s" style="width:210pt; margin-top:4pt; height:17pt;">
                <div class="lbl gcb" style="left:0; width:30pt;">Date:</div>
                <div class="val" style="left:32pt; width:130pt; font-size:{{ $fit($cDate, 130, 9.5) }}pt;">{{ $cDate }}</div>
            </div>
            <div class="ln s" style="width:210pt; height:17pt;">
                <div class="lbl gcb" style="left:0; width:30pt;">Time:</div>
                <div class="val" style="left:32pt; width:130pt; font-size:{{ $fit($cTime, 130, 9.5) }}pt;">{{ $cTime }}</div>
            </div>
            <div style="margin-top:14pt;">
                @include('pdf._signer', ['heading' => '', 's' => $gc, 'caption' => 'Name and Signature of the<br>Guidance Counselor<br>Date Signed: ' . e($gcDate)])
            </div>
        </td>
        <td style="width:6%;"></td>
        <td style="width:48%;">
            @include('pdf._signer', ['heading' => '', 's' => $vp, 'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($vpDate)])
        </td>
    </tr>
</table>

<div class="issued">Date Issued: <span class="ul">{{ $dateIssued }}&nbsp;</span></div>

<div class="note">(Note: To be accomplished in two copies: one for the student and one for the file of the office of the VPSD.)</div>


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