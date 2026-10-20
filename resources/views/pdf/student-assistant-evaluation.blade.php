@php
    use App\Workflow\Documents\StudentAssistantEvaluation as Ev;

    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $name    = (string) ($v['student_name'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $office  = (string) ($v['office_area'] ?? '');
    $mean    = (string) ($v['mean_rating'] ?? '');
    $rec     = $v['recommendation'] ?? '';

    $keys   = array_keys(Ev::CRITERIA);
    $labels = array_values(Ev::CRITERIA);

    $rater      = $reporterSigner;
    $dateSigned = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    // Comments: one ruled line per row, wrapped at word boundaries, minimum 2 rows.
    $rows = [];
    foreach (preg_split('/\r\n|\r|\n/', trim((string) ($v['comments'] ?? ''))) as $para) {
        foreach (explode("\n", wordwrap($para, 80, "\n", true)) as $line) {
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
<title>Student Assistant Performance Evaluation Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 22pt 0 16pt; }

    .ln { position: relative; height: 22pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .dir { font-size: 9.5pt; line-height: 1.3; margin-top: 4pt; }

    table.rate { margin-top: 10pt; }
    table.rate th, table.rate td { border: .6pt solid #000; vertical-align: middle; }
    table.rate th { font-size: 8.5pt; font-weight: bold; text-align: center; padding: 2pt 2pt; }
    table.rate td { height: 15pt; font-size: 8pt; padding: 0 3pt; }
    table.rate td.n { text-align: center; padding: 0; font-size: 8.5pt; }
    table.rate td.r { text-align: center; font-weight: bold; font-size: 9pt; padding: 0; }
    table.rate td.mean { text-align: center; font-weight: bold; font-size: 8.5pt; }

    .scale { margin-top: 4pt; text-align: center; font-size: 8.5pt; font-weight: bold; }

    table.opts td { padding: 1.5pt 0; vertical-align: middle; }
    table.opts td.line { width: 50pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; font-size: 10pt; height: 13pt; }
    table.opts td.gap { width: 6pt; }

    .crow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }
    .clab { font-weight: normal; }

    .sigwrap { width: 250pt; margin: 24pt auto 0; text-align: center; }
    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }

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

<div class="title">STUDENT ASSISTANT PERFORMANCE EVALUATION FORM</div>

{{-- Student details (stacked so a long program name never gets squeezed) --}}
<div class="ln">
    <div class="lbl" style="left:0; width:134pt;">Name of Student Assistant:</div>
    <div class="val" style="left:136pt; width:351pt; font-size:{{ $fit($name, 351) }}pt;">{{ $name }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:134pt;">Program, Year and Section:</div>
    <div class="val" style="left:136pt; width:351pt; font-size:{{ $fit($program, 351) }}pt;">{{ $program }}</div>
</div>
<div class="ln">
    <div class="lbl" style="left:0; width:134pt;">Office/Area Assignment:</div>
    <div class="val" style="left:136pt; width:351pt; font-size:{{ $fit($office, 351) }}pt;">{{ $office }}</div>
</div>

<div class="dir">
    Directions: Please evaluate the performance of the student assistant assigned to your office in a scale of 5
    where 5 is the highest and 1, the lowest.
</div>

{{-- Ratings --}}
<table class="rate">
    <tr>
        <th style="width:32pt;">Item No.</th><th style="width:160pt;">Criteria</th><th style="width:36pt;">Rating</th>
        <th style="width:32pt;">Item No.</th><th style="width:193pt;">Criteria</th><th style="width:34pt;">Rating</th>
    </tr>
    @for ($i = 0; $i < 5; $i++)
        <tr>
            <td class="n">{{ $i + 1 }}</td><td>{{ $labels[$i] }}</td><td class="r">{{ $v[$keys[$i]] ?? '' }}</td>
            <td class="n">{{ $i + 6 }}</td><td>{{ $labels[$i + 5] }}</td><td class="r">{{ $v[$keys[$i + 5]] ?? '' }}</td>
        </tr>
    @endfor
    <tr>
        <td colspan="5" class="mean">MEAN RATING</td>
        <td class="r">{{ $mean }}</td>
    </tr>
</table>

<div class="scale"><u>Scale:</u> (5) Outstanding, (4) Very Satisfactory, (3) Satisfactory, (2) Good (1) Poor</div>

{{-- Recommendation --}}
<table class="opts" style="margin-top:12pt;">
    <tr>
        <td class="line">{{ $rec === Ev::RENEW ? 'X' : '' }}</td>
        <td class="gap"></td>
        <td>Recommended for Renewal of Service as Student Assistant</td>
    </tr>
    <tr>
        <td class="line">{{ $rec === Ev::NOT_RENEW ? 'X' : '' }}</td>
        <td class="gap"></td>
        <td>Not Recommended</td>
    </tr>
</table>

{{-- Comments: label shares the first ruled line --}}
<div style="margin-top:10pt;">
    @foreach ($rows as $i => $line)
        <div class="crow" style="font-size:{{ $fit($line, 487 - ($i === 0 ? 56 : 0), 9.5, 6) }}pt;">@if ($i === 0)<span class="clab" style="font-size:10pt;">Comment/s:&nbsp;</span>@endif{{ $line }}&nbsp;</div>
    @endforeach
</div>

{{-- Signature --}}
<div class="sigwrap">
    @include('pdf._signer', [
        'heading' => '',
        's' => $rater,
        'caption' => 'Name and Signature of the Supervisor (Rater)<br>Date: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>',
    ])
</div>

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