@php
    use App\Workflow\Documents\StudentAssistantshipApplication as SA;

    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };

    $name    = (string) ($v['student_name'] ?? '');
    $program = (string) ($v['student_program'] ?? '');
    $office  = (string) ($v['office_name'] ?? '');
    $start   = ! empty($v['start_date']) ? \Carbon\Carbon::parse($v['start_date'])->format('F j, Y') : '';

    $vp         = $signers[0] ?? [];
    $signedOn   = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';
    $dateIssued = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';

    // The paper form lists three rows only.
    $days = ['mon_thu', 'tue_fri', 'wed'];

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Assistantship Endorsement Form {{ $code }}</title>
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

    .ln { position: relative; height: 30pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: center; font-weight: bold; white-space: nowrap; overflow: hidden; }
    .ln .cap { top: 15pt; font-size: 7.5pt; font-style: italic; text-align: center; line-height: 9pt; white-space: nowrap; overflow: hidden; }

    table.sched { width: 300pt; margin: 14pt auto 0; table-layout: fixed; }
    table.sched th, table.sched td { border: .6pt solid #000; font-size: 8.5pt; text-align: center; vertical-align: middle; padding: 2.5pt 4pt; }
    table.sched th { font-weight: bold; }
    table.sched th.sub { font-weight: normal; }
    table.sched td.day { text-align: left; }

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
        <td class="t serif">
            <div>LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div>vpsdas@lnu.edu.ph</div>
            <div>053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">STUDENT ASSISTANTSHIP ENDORSEMENT FORM</div>

<div style="margin-left:300pt; height:24pt; line-height:13pt;">Date: <span class="ul">{{ $dateIssued }}&nbsp;</span></div>

<div style="height:26pt; line-height:13pt;">Dear Sir/Ma'am:</div>

{{-- Line 1 --}}
<div class="ln">
    <div class="lbl" style="left:0; width:104pt;">May I inform you that</div>
    <div class="val" style="left:106pt; width:150pt; font-size:{{ $fit($name, 150) }}pt;">{{ $name }}</div>
    <div class="cap" style="left:106pt; width:150pt;">(Name of Student Assistant)</div>
    <div class="lbl" style="left:258pt; width:44pt;">, is a/an</div>
    <div class="val" style="left:304pt; width:128pt; font-size:{{ $fit($program, 128) }}pt;">{{ $program }}</div>
    <div class="cap" style="left:304pt; width:128pt;">(Program, Year and Section)</div>
    <div class="lbl" style="left:436pt; width:51pt;">student,</div>
</div>

{{-- Line 2 --}}
<div class="ln">
    <div class="lbl" style="left:0; width:190pt;">is assigned as Student Assistant in the</div>
    <div class="val" style="left:192pt; width:140pt; font-size:{{ $fit($office, 140) }}pt;">{{ $office }}</div>
    <div class="cap" style="left:192pt; width:140pt;">(Name of Office)</div>
    <div class="lbl" style="left:334pt; width:42pt;">starting</div>
    <div class="val" style="left:378pt; width:106pt; font-size:{{ $fit($start, 106) }}pt;">{{ $start }}</div>
    <div class="cap" style="left:378pt; width:106pt;">(Date)</div>
    <div class="lbl" style="left:485pt; width:4pt;">.</div>
</div>

{{-- Schedule --}}
<table class="sched">
    <tr>
        <th rowspan="2" style="width:130pt;">Days</th>
        <th colspan="2">Schedule of Vacant Period</th>
    </tr>
    <tr>
        <th class="sub" style="width:85pt;">AM</th>
        <th class="sub" style="width:85pt;">PM</th>
    </tr>
    @foreach ($days as $k)
        <tr>
            <td class="day">{{ SA::DAYS[$k][0] }}</td>
            <td>{{ SA::range($v, $k, 'am') }}</td>
            <td>{{ SA::range($v, $k, 'pm') }}</td>
        </tr>
    @endforeach
</table>

@if (! empty($v['source_application']))
    <div style="margin-top:4pt; text-align:center; font-size:6.5pt; color:#777;">Based on application {{ $v['source_application'] }}</div>
@endif

{{-- Signature --}}
<table style="margin-top:26pt;">
    <tr>
        <td style="width:50%;"></td>
        <td style="width:50%;">
            <div style="margin-bottom:4pt;">Very truly yours,</div>
            @include('pdf._signer', [
                'heading' => '',
                's' => $vp,
                'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($signedOn),
            ])
        </td>
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