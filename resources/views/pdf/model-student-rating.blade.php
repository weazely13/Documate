@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 12, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));

    $docTitle = $docTitle ?? 'Model Student Faculty/Peer Rating Form';
    $name     = $one($v['student_name'] ?? '');
    $program  = $one(($v['student_course'] ?? '') ?: ($v['student_program'] ?? ''));

    $isStaff    = ! empty($v['reporter_name']);
    $rater      = $isStaff ? $reporterSigner : ($signers[0] ?? []);
    $picked     = $isStaff ? (int) ($v['rating'] ?? 0) : (int) ($rater['response']['rating'] ?? 0);
    $dateSigned = $isStaff
        ? (($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '')
        : (! empty($rater['signed_at']) ? $rater['signed_at']->format('F j, Y') : '');
    $labels = \App\Workflow\Documents\ModelStudentRating::RATINGS;

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
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
    .title { text-align: center; font-weight: bold; font-size: 13pt; margin: 20pt 0 16pt; }

    /* One-line form rows (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 18pt; width: 487pt; }
    .ln.c { height: 32pt; }
    .ln.r { height: 18pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 14pt; line-height: 14pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 14pt; line-height: 14pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .cap { top: 16pt; font-size: 10pt; font-style: italic; text-align: center; line-height: 11pt; white-space: nowrap; overflow: hidden; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 12pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }

    .note { margin-top: 24pt; font-size: 7.5pt; font-style: italic; }

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
    <div class="lbl" style="left:30pt; width:457pt;">This is to inform the Office of the Vice President for Student Development that I</div>
</div>

<div class="ln">
    <div class="lbl" style="left:0; width:124pt;">personally know Mr./Ms.</div>
    <div class="val" style="left:126pt; width:355pt; font-size:{{ $fit($name, 355) }}pt;">{{ $name }}</div>
    <div class="lbl" style="left:482pt; width:5pt;">,</div>
</div>

<div class="ln c">
    <div class="lbl" style="left:0; width:8pt;">a</div>
    <div class="val" style="left:10pt; width:288pt; font-size:{{ $fit($program, 288) }}pt;">{{ $program }}</div>
    <div class="cap" style="left:10pt; width:288pt;">(Program being pursued)</div>
    <div class="lbl" style="left:301pt; width:186pt;">student of Leyte Normal University.</div>
</div>

<div class="ln" style="margin-top:6pt;">
    <div class="lbl" style="left:0; width:487pt;">Based on my honest assessment with respect to his/her interpersonal relations with his/her</div>
</div>
<div class="ln" style="margin-bottom:4pt;">
    <div class="lbl" style="left:0; width:487pt;">classmates and teachers, I am giving him/her a rating of:</div>
</div>

@foreach ($labels as $n => $label)
    <div class="ln r">
        <div class="val" style="left:40pt; width:140pt;">{{ $picked === $n ? 'X' : '' }}&nbsp;</div>
        <div class="lbl" style="left:224pt; width:120pt;">{{ $label }}</div>
        <div class="lbl" style="left:350pt; width:40pt;">({{ $n }})</div>
    </div>
@endforeach

<table style="margin-top:24pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', [
                'heading' => '',
                's' => $rater,
                'caption' => 'Name and Signature of the Rater<br>Date: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>',
            ])
        </td>
        <td style="width:25%;"></td>
    </tr>
</table>

<div class="note">The data collected here will be for the sole use of legitimate interests of the university as an educational institution.</div>

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