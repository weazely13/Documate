@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $respondent = (string) ($v['respondent'] ?? '');
    $vpsdName   = (string) ($v['vpsd_name'] ?? '');
    $dateFiled  = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    // Signers: parent first, then each filled witness row (same order as StudentComplaintGrievance::chain()).
    $parent = $signers[0] ?? [];
    $parent['name'] = ($parent['name'] ?? '') ?: trim((string) ($v['parent_name'] ?? ''));

    $witnesses = [];
    $k = 1;
    foreach ([1, 2] as $i) {
        $typed = trim((string) ($v["witness_{$i}_name"] ?? ''));
        if ($typed === '' && trim((string) ($v["witness_{$i}_email"] ?? '')) === '') {
            continue;
        }
        $s = $signers[$k] ?? [];
        $s['name'] = ($s['name'] ?? '') ?: $typed;
        $witnesses[$i] = $s;
        $k++;
    }

    // Complaint: one ruled line per row, wrapped at word boundaries, minimum 4 rows.
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', trim((string) ($v['complaint'] ?? ''))) as $para) {
        foreach (explode("\n", wordwrap($para, 88, "\n", true)) as $line) {
            $rows[] = $line;
        }
    }
    while (count($rows) < 4) { $rows[] = ''; }

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Complaint/Grievance Form {{ $code }}</title>
<style>
    @page { margin: 40pt 54pt 96pt 54pt; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10pt; color: #000; margin: 0; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    td { padding: 0; vertical-align: top; }
    .serif { font-family: 'Times', 'Times New Roman', serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 10.5pt; line-height: 1.15; }
    .hdr .b { font-weight: bold; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 14pt; }

    .ul { border-bottom: .6pt solid #000; font-weight: bold; padding: 0 6pt; }

    .ln { position: relative; height: 24pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .rrow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }

    .vpname { width: 250pt; height: 15pt; line-height: 15pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; text-transform: uppercase; font-size: 9pt; white-space: nowrap; overflow: hidden; }
    .vpcap { width: 250pt; text-align: center; font-size: 9.5pt; margin-top: 2pt; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    table.sg { page-break-inside: avoid; }

    .formno { border-top: .6pt solid #c8c8c8; margin-top: 22pt; padding-top: 4pt; font-size: 10pt; font-weight: bold; font-style: italic; }

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
        <td class="t serif">
            <div>LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div>vpsdas@lnu.edu.ph</div>
            <div>053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">STUDENT COMPLAINT/GRIEVANCE FORM</div>

<div style="margin-left:290pt; height:22pt; line-height:13pt;">Date: <span class="ul">{{ $dateFiled }}&nbsp;</span></div>

{{-- Addressee --}}
<div class="vpname" style="font-size:{{ $fit($vpsdName, 250, 9, 5.5) }}pt;">{{ $vpsdName }}&nbsp;</div>
<div class="vpcap">VP, Student Development</div>

{{-- Statement --}}
<div class="ln" style="margin-top:12pt;">
    <div class="lbl" style="left:28pt; width:152pt;">I would like to file a complaint against</div>
    <div class="val" style="left:182pt; width:170pt; font-size:{{ $fit($respondent, 170) }}pt;">{{ $respondent }}</div>
    <div class="lbl" style="left:356pt; width:131pt;">for the reason/s cited below:</div>
</div>

<div style="margin-top:2pt;">
    @foreach ($rows as $line)
        <div class="rrow" style="font-size:{{ $fit($line, 487, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
    @endforeach
</div>

{{-- Complainant --}}
<table class="sg" style="margin-top:22pt;">
    <tr>
        <td style="width:46%;"></td>
        <td style="width:54%;">
            @include('pdf._signer', [
                'heading' => '',
                's' => $studentSigner,
                'caption' => 'Name and Signature of Complainant',
            ])
        </td>
    </tr>
</table>

{{-- Parent/Guardian --}}
<table class="sg" style="margin-top:18pt;">
    <tr>
        <td style="width:46%;"></td>
        <td style="width:54%;">
            @include('pdf._signer', [
                'heading' => '',
                's' => $parent,
                'caption' => 'Name and Signature of<br>Complainant\'s Parent/Guardian',
            ])
        </td>
    </tr>
</table>

{{-- Witnesses --}}
<table class="sg" style="margin-top:12pt;">
    <tr>
        <td style="width:46%;"></td>
        <td style="width:54%;">
            <div style="margin-bottom:4pt;">Witnesses:</div>
            @foreach ([1, 2] as $i)
                <div style="margin: {{ $i === 1 ? '0' : '14pt' }} 24pt 0 24pt;">
                    @include('pdf._signer', [
                        'heading' => '',
                        's' => $witnesses[$i] ?? [],
                        'caption' => 'Name and Signature of Witness #' . $i,
                    ])
                </div>
            @endforeach
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