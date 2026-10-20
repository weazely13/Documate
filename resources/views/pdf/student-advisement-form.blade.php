@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));

    // First line is short (sits after a label); the rest wraps on full-width ruled rows.
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

    $doctor  = ! empty($v['advise_doctor']);
    $haircut = ! empty($v['advise_haircut']);
    $others  = ! empty($v['advise_others']);

    $docRows = $split($doctor ? (string) ($v['doctor_reason'] ?? '') : '', 48, 85);
    $othRows = $split($others ? (string) ($v['others_specify'] ?? '') : '', 58, 85, 1);

    $reportDate = $haircut && ! empty($v['haircut_report_date'])
        ? \Carbon\Carbon::parse($v['haircut_report_date'])->format('F j, Y')
          . (! empty($v['haircut_report_session']) ? ' (' . strtolower($v['haircut_report_session']) . ')' : '')
        : '';

    $name    = $one($v['student_name'] ?? '');
    $program = $one($v['student_program'] ?? '');
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    $inspDate = ! empty($v['inspection_date']) ? \Carbon\Carbon::parse($v['inspection_date'])->format('F j, Y') : '';
    $remarks = [];
    foreach (preg_split('/\r\n|\r|\n/', trim((string) ($v['inspection_remarks'] ?? ''))) as $para) {
        foreach (explode("\n", wordwrap($para, 80, "\n", true)) as $line) {
            $remarks[] = $line;
        }
    }
    while (count($remarks) < 3) { $remarks[] = ''; }

    $reporter = $reporterSigner;
    $insp = $signers[0] ?? [];

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Advisement Form {{ $code }}</title>
<style>
    @page { margin: 34pt 54pt 90pt 54pt; }
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 16pt 0 14pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 22pt; width: 487pt; }
    .ln.m { height: 32pt; }
    .ln > div, .dr > div { position: absolute; top: 0; }
    .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .cap { top: 15pt; font-size: 7.5pt; font-style: italic; text-align: center; line-height: 9pt; white-space: nowrap; overflow: hidden; }

    .opt { position: relative; margin-bottom: 8pt; }
    .opt .cb { position: absolute; left: 0; top: 0; width: 13pt; height: 13pt; border: 1.2pt solid #000; text-align: center; line-height: 13pt; font-weight: bold; font-size: 10pt; }
    .opt .body { margin-left: 28pt; width: 459pt; }
    .dr { position: relative; height: 20pt; width: 459pt; }
    .rrow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }

    .cut { border-top: .8pt dashed #888; margin: 16pt 0 12pt; }
    .lab { font-weight: bold; }

    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    table.sg { page-break-inside: avoid; }

    .formno { border-top: .6pt solid #c8c8c8; margin-top: 18pt; padding-top: 4pt; font-size: 10pt; font-weight: bold; font-style: italic; }

    .foot { position: fixed; left: 0; right: 0; bottom: -74pt; height: 68pt; border-top: .6pt solid #c8c8c8; font-family: 'DejaVu Sans', sans-serif; }
    .foot > div { position: absolute; white-space: nowrap; }
    .foot-qr { left: 0; top: 6pt; }
    .foot-k { left: 72pt; top: 7pt; font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; }
    .foot-t { left: 72pt; top: 19pt; font-size: 7.5pt; color: #444; }
    .foot-r { left: 72pt; top: 33pt; font-size: 7.5pt; color: #444; }
    .foot-f { left: 72pt; top: 45pt; font-size: 7.5pt; color: #444; }
    .foot-v { font-size: 8pt; font-weight: bold; color: #111; }
    .foot-form { right: 0; top: 7pt; font-size: 7pt; color: #888; }
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

<div class="title">STUDENT ADVISEMENT FORM</div>

{{-- Date / Name --}}
<div class="ln">
    <div class="lbl" style="left:330pt; width:34pt;">Date:</div>
    <div class="val" style="left:366pt; width:121pt; font-size:{{ $fit($dateIssued, 121) }}pt;">{{ $dateIssued }}</div>
</div>
<div class="ln" style="margin-bottom:6pt;">
    <div class="lbl" style="left:0; width:96pt;">Name of Student:</div>
    <div class="val" style="left:98pt; width:330pt; font-size:{{ $fit($name, 330) }}pt;">{{ $name }}</div>
</div>

{{-- Doctor --}}
<div class="opt">
    <div class="cb">@if ($doctor)X@endif</div>
    <div class="body">
        <div class="dr">
            <div class="lbl" style="left:0; width:200pt;">Advised to see the University Doctor for</div>
            <div class="val" style="left:202pt; width:257pt; font-size:{{ $fit($docRows[0], 257, 9.5, 6) }}pt;">{{ $docRows[0] }}</div>
        </div>
        @foreach (array_slice($docRows, 1) as $line)
            <div class="rrow" style="font-size:{{ $fit($line, 459, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
        @endforeach
    </div>
</div>

{{-- Haircut --}}
<div class="opt">
    <div class="cb">@if ($haircut)X@endif</div>
    <div class="body">
        <div style="height:15pt; line-height:15pt;">Advised to have haircut/mustache shave and to report</div>
        <div class="dr" style="height:30pt;">
            <div class="lbl" style="left:0; width:82pt;">to the VPSD on</div>
            <div class="val" style="left:84pt; width:170pt; text-align:center; font-size:{{ $fit($reportDate, 170, 9.5, 6) }}pt;">{{ $reportDate }}</div>
            <div class="cap" style="left:84pt; width:170pt;">(Date)</div>
            <div class="lbl" style="left:258pt; width:150pt;">for further inspection.</div>
        </div>
    </div>
</div>

{{-- Others --}}
<div class="opt">
    <div class="cb">@if ($others)X@endif</div>
    <div class="body">
        <div class="dr">
            <div class="lbl" style="left:0; width:104pt;">Others, pls. specify:</div>
            <div class="val" style="left:106pt; width:353pt; font-size:{{ $fit($othRows[0], 353, 9.5, 6) }}pt;">{{ $othRows[0] }}</div>
        </div>
        @foreach (array_slice($othRows, 1) as $line)
            <div class="rrow" style="font-size:{{ $fit($line, 459, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
        @endforeach
    </div>
</div>

{{-- Advised by --}}
<table class="sg" style="margin-top:10pt;">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $reporter, 'caption' => 'Name &amp; Signature of Faculty/<br>Security Guard/Personnel'])
        </td>
    </tr>
</table>

<div class="cut"></div>

{{-- Inspection slip --}}
<div class="ln" style="height:18pt;">
    <div class="lbl lab" style="left:20pt; width:112pt;">Student's Name:</div>
    <div class="val" style="left:134pt; width:250pt; font-size:{{ $fit($name, 250) }}pt;">{{ $name }}</div>
</div>
<div class="ln" style="height:24pt;">
    <div class="lbl lab" style="left:20pt; width:112pt;">Program, Year and Section:</div>
    <div class="val" style="left:134pt; width:250pt; font-size:{{ $fit($program, 250) }}pt;">{{ $program }}</div>
</div>
<div class="ln" style="height:18pt;">
    <div class="lbl lab" style="left:20pt; width:112pt;">Date:</div>
    <div class="val" style="left:134pt; width:250pt; font-size:{{ $fit($inspDate, 250) }}pt;">{{ $inspDate }}</div>
</div>
<div style="position:relative; margin-top:2pt;">
    <div class="lbl lab" style="position:absolute; left:20pt; top:0; width:112pt; height:18pt; line-height:18pt;">Remarks:</div>
    <div style="margin-left:134pt; width:250pt;">
        @foreach ($remarks as $line)
            <div class="rrow" style="font-size:{{ $fit($line, 250, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
        @endforeach
    </div>
</div>

@if ($haircut)
    <table class="sg" style="margin-top:10pt;">
        <tr>
            <td style="width:50%;"></td>
            <td style="width:50%;">
                <div class="lab" style="margin-bottom:2pt;">Inspected by:</div>
                @include('pdf._signer', ['heading' => '', 's' => $insp, 'caption' => 'Name and Signature of the<br>Vice President for Student Development'])
            </td>
        </tr>
    </table>
@endif

<div class="formno">{{ $formCode }}</div>

@unless ($preview ?? false)
<div class="foot">
    <div class="foot-qr">@if ($qr)<img src="{{ $qr }}" width="56" height="56">@endif</div>
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