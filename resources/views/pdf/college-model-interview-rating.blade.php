@php
    use App\Workflow\Documents\CollegeModelInterviewRating as R;

    $interviewer = $reporterSigner;
    $dateSigned = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>College Model Student Interview/Talent Presentation Rating Form {{ $code }}</title>
<style>
    @page { margin: 34pt 40pt 96pt 40pt; }
    body { font-family: 'Times', 'Times New Roman', serif; font-size: 10.5pt; color: #000; line-height: 1.25; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    td, th { padding: 0; }
    .sans { font-family: 'Helvetica', Arial, sans-serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    .hdr { width: 330pt; margin: 0 auto; }
    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 8.5pt; line-height: 1.25; }
    .hdr .b { font-weight: bold; }
    .title { text-align: center; font-weight: bold; font-size: 9.5pt; margin: 16pt 0 16pt; }
    .dir { margin-bottom: 8pt; }

    table.rate th, table.rate td { border: .8pt solid #000; }
    table.rate th { font-weight: normal; font-size: 8.5pt; padding: 4pt 3pt; text-align: center; line-height: 1.2; }
    table.rate th.name { font-size: 10pt; }
    table.rate td { height: 15pt; padding: 0 4pt; font-size: 9.5pt; vertical-align: middle; }
    table.rate td.n { width: 22pt; padding: 0 2pt; font-size: 10pt; }
    table.rate td.s { text-align: center; font-weight: bold; }
    table.rate td.nm { font-weight: bold; }

    .sigwrap { width: 250pt; margin: 30pt auto 0; text-align: center; }
    .sig-head { display: none; }
    .sig-name { border-top: .8pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; }
    .sig-cap { font-size: 10.5pt; line-height: 1.3; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; }
    .sig-detail { font-size: 6.5pt; color: #666; }

    .note { margin-top: 26pt; font-size: 7.5pt; font-style: italic; }
    .formno { border-top: 1.2pt solid #000; margin-top: 2pt; padding-top: 1pt; font-size: 10pt; }

    .foot { position: fixed; left: 0; right: 0; bottom: -78pt; height: 72pt; border-top: .6pt solid #c8c8c8; padding-top: 8pt; font-family: 'DejaVu Sans', sans-serif; }
    .foot-k { font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; }
    .foot-t { font-size: 7.5pt; color: #444; line-height: 1.5; }
    .foot-v { font-size: 8pt; font-weight: bold; color: #111; }
    .foot-form { font-size: 7pt; color: #888; text-align: right; white-space: nowrap; }
    @if ($preview ?? false)
        html { background: #e5e7eb; }
        body { max-width: 540pt; margin: 16pt auto; padding: 30pt 36pt; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.15); position: relative; }
        .watermark { position: absolute; top: 6pt; right: 10pt; font-size: 7pt; letter-spacing: 1pt; color: #b45309; background: #fffbeb; border: .6pt solid #fcd34d; padding: 2pt 6pt; border-radius: 3pt; }
    @endif
</style>
</head>
<body>

<table class="hdr">
    <tr>
        <td style="width:92pt; text-align:center;">@if ($seal)<img src="{{ $seal }}" width="76" height="76">@endif</td>
        <td class="sans t">
            <div class="b">Leyte Normal University</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
        </td>
    </tr>
</table>

<div class="title sans">COLLEGE MODEL STUDENT INTERVIEW/TALENT PRESENTATION RATING FORM</div>

<div class="dir">
    Directions: Please evaluate the interviewee against a 10-point scale with 10 being the highest and 1, the lowest.
    The evaluation criteria are indicated below.
</div>

<table class="rate">
    <tr>
        <th colspan="2" class="name" style="width:192pt;">Name of Interviewee</th>
        <th style="width:84pt;">Personality (10 points)</th>
        <th style="width:84pt;">Communication Skills (10 points)</th>
        <th style="width:70pt;">Talent<br>(10 points)</th>
        <th style="width:85pt;">Total Number of Points Earned</th>
    </tr>
    @for ($i = 1; $i <= R::ROWS; $i++)
        @php
            $nm = trim((string) ($v["interviewee_{$i}_name"] ?? ''));
            $total = $nm === '' ? '' : ($v["total_{$i}"] ?? '');
        @endphp
        <tr>
            <td class="n">{{ $i }}</td>
            <td class="nm">{{ $nm }}</td>
            <td class="s">{{ $nm === '' ? '' : ($v["score_{$i}_personality"] ?? '') }}</td>
            <td class="s">{{ $nm === '' ? '' : ($v["score_{$i}_communication"] ?? '') }}</td>
            <td class="s">{{ $nm === '' ? '' : ($v["score_{$i}_talent"] ?? '') }}</td>
            <td class="s">{{ $total }}</td>
        </tr>
    @endfor
</table>

<div class="sigwrap">
    @include('pdf._signer', [
        'heading' => '',
        's' => $interviewer,
        'caption' => 'Name and Signature of the Interviewer<br>Date: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>',
    ])
</div>

<div class="note">The data collected here will be for the sole use of the legitimate interests of the university as an educational institution.</div>
<div class="formno">{{ $formCode }}</div>

@unless ($preview ?? false)
<div class="foot">
    <table>
        <tr>
            <td style="width:70pt; vertical-align:top;">@if ($qr)<img src="{{ $qr }}" width="58" height="58">@endif</td>
            <td style="vertical-align:top;">
                <div class="foot-k">Verify this document</div>
                <div class="foot-t">Scan the QR code, or go to <strong>{{ $lookup }}</strong> and enter:</div>
                <div class="foot-t" style="margin-top:2pt;">
                    Reference &nbsp;<span class="foot-v mono">{{ $code }}</span><br>
                    Fingerprint &nbsp;<span class="foot-v mono">{{ $fingerprint ?: '-' }}</span>
                </div>
            </td>
            <td style="width:135pt; vertical-align:top;" class="foot-form">{{ $formCode }}</td>
        </tr>
    </table>
</div>
@endunless

@if ($preview ?? false)
    <div class="watermark">PREVIEW{{ $issuedAt ? '' : ' · NOT YET ISSUED' }}</div>
@endif
</body>
</html>