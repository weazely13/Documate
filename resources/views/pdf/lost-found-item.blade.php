@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $kind = (string) ($v['item_kind'] ?? '');
    $desc = trim(preg_replace('/\s+/', ' ', (string) ($v['item_description'] ?? '')));
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $person = $studentSigner;

    // Description rows for the ticked option only (wrapped at word boundaries, minimum 1 row).
    $rowsFor = function (bool $on) use ($desc) {
        $rows = $on && $desc !== '' ? explode("\n", wordwrap($desc, 62, "\n", true)) : [''];
        return $rows;
    };
    $lostRows  = $rowsFor($kind === 'Lost');
    $foundRows = $rowsFor($kind === 'Found');

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Lost/Found Item Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 18pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 24pt; width: 487pt; }
    .ln > div, .dr > div { position: absolute; top: 0; }
    .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .opt { position: relative; margin-bottom: 14pt; }
    .opt .cb { position: absolute; left: 0; top: 0; width: 13pt; height: 13pt; border: 1.2pt solid #000; text-align: center; line-height: 13pt; font-weight: bold; font-size: 10pt; }
    .opt .body { margin-left: 28pt; }
    .opt .stmt { height: 15pt; line-height: 15pt; }
    .dr { position: relative; height: 18pt; width: 459pt; }
    .opt .cap { font-size: 8pt; font-style: italic; font-weight: bold; margin-top: 1pt; }

    .cut { border-top: .8pt dashed #888; margin: 18pt 0 12pt; }
    .part { text-align: center; font-weight: bold; font-size: 10.5pt; margin: 0 0 8pt; }

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

<div class="title">LOST/FOUND ITEM FORM</div>

<div class="ln">
    <div class="lbl" style="left:0; width:44pt; font-weight:bold;">Date:</div>
    <div class="val" style="left:46pt; width:170pt; font-size:{{ $fit($dateIssued, 170) }}pt;">{{ $dateIssued }}</div>
</div>
<div class="ln" style="margin-bottom:10pt;">
    <div class="lbl" style="left:0; width:44pt; font-weight:bold;">To/For:</div>
    <div class="val" style="left:46pt; width:300pt; font-size:{{ $fit('Office of the Vice President for Student Development', 300, 9.5) }}pt;">Office of the Vice President for Student Development</div>
</div>

{{-- Lost --}}
<div class="opt">
    <div class="cb">{{ $kind === 'Lost' ? 'X' : '' }}</div>
    <div class="body">
        <div class="stmt">May I respectfully report to your office a/an</div>
        @foreach ($lostRows as $i => $line)
            <div class="dr">
                <div class="val" style="left:0; width:318pt; font-size:{{ $fit($line, 318, 9.5, 6) }}pt;">{{ $line }}</div>
                @if ($loop->last)<div class="lbl" style="left:324pt; width:100pt;">that I lost.</div>@endif
            </div>
        @endforeach
        <div class="cap">(Name and Description of the Lost Item)</div>
    </div>
</div>

{{-- Found --}}
<div class="opt">
    <div class="cb">{{ $kind === 'Found' ? 'X' : '' }}</div>
    <div class="body">
        <div class="stmt">May I respectfully turn over to your office a/an</div>
        @foreach ($foundRows as $i => $line)
            <div class="dr">
                <div class="val" style="left:0; width:318pt; font-size:{{ $fit($line, 318, 9.5, 6) }}pt;">{{ $line }}</div>
                @if ($loop->last)<div class="lbl" style="left:324pt; width:100pt;">that I found.</div>@endif
            </div>
        @endforeach
        <div class="cap">(Name and Description of the Found Item)</div>
    </div>
</div>

<div style="margin: 14pt 0 0 28pt;">Thank you.</div>

<table style="margin-top:18pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $person, 'caption' => 'Name and Signature of the Person<br>who Lost/Found the Item'])
        </td>
        <td style="width:25%;"></td>
    </tr>
</table>

<div class="cut"></div>
<div class="part">Claim Acknowledgment Slip</div>

<table style="margin-top:14pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => [], 'caption' => 'Name and Signature of the Person<br>who Claimed the Item'])
        </td>
        <td style="width:25%;"></td>
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