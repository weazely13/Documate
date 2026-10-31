@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));

    // First line sits after a label; the rest wrap on ruled rows.
    $split = function (string $text, int $firstW, int $restW, int $minRows = 2) use ($one) {
        $text = $one($text);
        $first = '';
        $rest = [];
        if ($text !== '') {
            $parts = explode("\n", wordwrap($text, $firstW, "\n", true), 2);
            $first = $parts[0];
            if (isset($parts[1])) {
                $rest = explode("\n", wordwrap(str_replace("\n", ' ', $parts[1]), $restW, "\n", true));
            }
        }
        $rows = array_merge([$first], $rest);
        while (count($rows) < $minRows) { $rows[] = ''; }
        return $rows;
    };

    // "( X )" / "(    )" like the paper form.
    $box = fn ($on) => '(&nbsp;' . ($on ? '<b>X</b>' : '&nbsp;&nbsp;') . '&nbsp;)';

    $date    = ($submittedAt ?? $issuedAt)?->format('F j, Y') ?? '';
    $program = $one($v['student_program'] ?? '');
    $number  = $one($v['student_number'] ?? '');
    $other   = ! empty($v['other']);
    $rows    = $split($other ? (string) ($v['other_specify'] ?? '') : '', 60, 60, 2);

    $vp       = $signers[0] ?? [];
    $approved = ! empty($vp['signed_at']);
    $vpDate   = $approved ? $vp['signed_at']->format('F j, Y') : '';

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Request for Renewal/Reprinting of Student ID Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 20pt 0 14pt; }

    .ln { position: relative; height: 22pt; width: 487pt; }
    .ln.s { height: 18pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .val.left { text-align: left; }

    .rrow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }
    .stmt { line-height: 15pt; margin-top: 12pt; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    table.sg { page-break-inside: avoid; }


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

<div class="title">REQUEST FOR RENEWAL/REPRINTING OF STUDENT ID FORM</div>

{{-- Date (right) --}}
<div class="ln">
    <div class="lbl" style="left:356pt; width:34pt;">Date:</div>
    <div class="val" style="left:392pt; width:95pt; font-size:{{ $fit($date, 95) }}pt;">{{ $date }}</div>
</div>

<div class="ln s">
    <div class="lbl" style="left:0; width:487pt;">I would like to request renewal of my ID for the following reason/s:</div>
</div>

<div class="ln s"><div class="lbl" style="left:28pt; width:400pt;">{!! $box(! empty($v['not_presentable'])) !!} It does not look presentable anymore.</div></div>
<div class="ln s"><div class="lbl" style="left:28pt; width:400pt;">{!! $box(! empty($v['destroyed'])) !!} It has been destroyed.</div></div>
<div class="ln s"><div class="lbl" style="left:28pt; width:400pt;">{!! $box(! empty($v['lost'])) !!} It was lost.</div></div>

{{-- Other reason/s: first line after the label, then ruled rows --}}
<div class="ln s">
    <div class="lbl" style="left:28pt; width:122pt;">{!! $box($other) !!} Other reason/s:</div>
    <div class="val left" style="left:152pt; width:335pt; font-size:{{ $fit($rows[0], 335, 9.5, 6) }}pt;">{{ $rows[0] }}</div>
</div>
<div style="margin-left:152pt; width:335pt;">
    @foreach (array_slice($rows, 1) as $line)
        <div class="rrow" style="font-size:{{ $fit($line, 335, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
    @endforeach
</div>

<div class="stmt">
    Since the use of more than one ID is a ground for disciplinary action, my old ID is, therefore, surrendered to this office.
</div>

{{-- Student (right) --}}
<table class="sg" style="margin-top:16pt;">
    <tr>
        <td style="width:44%;"></td>
        <td style="width:56%;">
            <div style="margin-bottom:2pt;">Very truly yours,</div>
            @include('pdf._signer', ['heading' => '', 's' => $studentSigner, 'caption' => 'Name and Signature of the Student'])
            <div class="ln s" style="width:273pt; margin-top:4pt;">
                <div class="lbl" style="left:0; width:124pt;">Program, Year and Section:</div>
                <div class="val left" style="left:126pt; width:147pt; font-size:{{ $fit($program, 147, 9.5, 6) }}pt;">{{ $program }}</div>
            </div>
            <div class="ln s" style="width:273pt;">
                <div class="lbl" style="left:0; width:124pt;">Student Number:</div>
                <div class="val left" style="left:126pt; width:147pt; font-size:{{ $fit($number, 147, 9.5, 6) }}pt;">{{ $number }}</div>
            </div>
        </td>
    </tr>
</table>

{{-- Action taken --}}
<div style="margin-top:16pt;">Action Taken:</div>
<div class="ln s" style="margin-top:2pt;"><div class="lbl" style="left:28pt; width:200pt;">{!! $box($approved) !!} &nbsp; Approved</div></div>
<div class="ln s"><div class="lbl" style="left:28pt; width:200pt;">{!! $box(false) !!} &nbsp; Disapproved</div></div>

<table class="sg" style="margin-top:6pt;">
    <tr>
        <td style="width:28%;"></td>
        <td style="width:42%;">
            @include('pdf._signer', ['heading' => '', 's' => $vp, 'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($vpDate)])
        </td>
        <td style="width:30%;"></td>
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