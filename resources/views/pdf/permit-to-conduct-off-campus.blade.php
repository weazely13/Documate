@php
    // Shrinks the font so the text fits one line in $w points. Never wraps.
    $fit = function (?string $t, float $w, float $base = 10, float $min = 5.5) {
        $len = max(1, mb_strlen((string) $t));
        return round(max($min, min($base, ($w - 4) / ($len * 0.6))), 1);
    };
    $one = fn ($s) => trim(preg_replace('/\s+/', ' ', (string) $s));
    $wrap = function (string $text, int $width, int $min) {
        $out = [];
        foreach (preg_split('/\r\n|\r|\n/', trim($text)) as $para) {
            foreach (explode("\n", wordwrap($para, $width, "\n", true)) as $line) {
                $out[] = $line;
            }
        }
        while (count($out) < $min) { $out[] = ''; }
        return $out;
    };

    $activity = $one($v['activity'] ?? '');
    $venue    = $one($v['venue'] ?? '');
    $slotLines = \App\Workflow\Documents\PermitToConductOffCampus::slotLines($v) ?: [''];

    // Purpose: ruled rows, minimum 3 (as on the paper form).
    $rows = $wrap((string) ($v['purposes'] ?? ''), 74, 3);

    // Persons involved: two columns of ruled lines, filled down the left column first. Minimum 4 lines per column.
    $persons = $wrap((string) ($v['persons_involved'] ?? ''), 40, 8);
    $perCol = max(4, (int) ceil(count($persons) / 2));
    $persons = array_pad($persons, $perCol * 2, '');
    $colL = array_slice($persons, 0, $perCol);
    $colR = array_slice($persons, $perCol, $perCol);

    // Position held in the organization.
    $hostedBy = $v['hosted_by'] ?? '';
    $isNonAcademic = $hostedBy === 'Non-Academic Organization';
    $position = $isNonAcademic ? ($v['non_academic_position'] ?? '') : ($v['officer_position'] ?? ($v['position_held'] ?? ''));
    $orgName = match (true) {
        $hostedBy === 'Department' => (string) ($v['department_name'] ?? ''),
        $isNonAcademic => (string) ($v['non_academic_org'] ?? ''),
        default => (string) ($v['officer_organization'] ?? ''),
    };
    $posText = $one(collect([$position, $orgName])->filter()->implode(' - '));

    $adviser = $signers[0] ?? [];
    $dean = $signers[1] ?? [];
    $vp = $signers[2] ?? [];
    $vpDate = ! empty($vp['signed_at']) ? $vp['signed_at']->format('F j, Y') : '';

    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Permit to Conduct/Participate in a Non-Curricular Off-Campus Activity Form {{ $code }}</title>
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
    .title { text-align: center; font-weight: bold; font-size: 10.5pt; margin: 20pt 0 16pt; }

    /* Absolute-positioned form lines (same mechanism as the Admission Slip) */
    .ln { position: relative; height: 24pt; width: 487pt; }
    .ln > div { position: absolute; top: 0; }
    .ln .lbl { height: 13pt; line-height: 13pt; white-space: nowrap; overflow: hidden; font-weight: bold; }
    .ln .val { height: 13pt; line-height: 13pt; border-bottom: .6pt solid #000; text-align: left; font-weight: bold; white-space: nowrap; overflow: hidden; }

    .purp { position: relative; margin-bottom: 11pt; }
    .purp .lbl { position: absolute; left: 0; top: 0; width: 90pt; height: 18pt; line-height: 18pt; font-weight: bold; }
    .purp .rows { margin-left: 92pt; }
    .purp.narrow .rows { margin-left: 64pt; }
    .rrow { height: 18pt; line-height: 18pt; border-bottom: .6pt solid #000; font-weight: bold; font-size: 9.5pt; white-space: nowrap; overflow: hidden; }

    .lab { font-weight: bold; }
    .sig-head { display: none; }
    .sig-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .sig-cap { font-size: 9.5pt; line-height: 1.3; text-align: center; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; text-align: center; }
    .sig-detail { font-size: 6.5pt; color: #666; text-align: center; }
    .req .sig-name { font-size: 8pt; white-space: nowrap; overflow: hidden; }
    .pos-name { border-top: .6pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; text-align: center; white-space: nowrap; overflow: hidden; }


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

<div class="title">PERMIT TO CONDUCT/PARTICIPATE IN A NON-CURRICULAR OFF-CAMPUS ACTIVITY FORM</div>

{{-- Activity/Event --}}
<div class="ln">
    <div class="lbl" style="left:0; width:84pt;">Activity/Event:</div>
    <div class="val" style="left:86pt; width:401pt; font-size:{{ $fit($activity, 401) }}pt;">{{ $activity }}</div>
</div>

{{-- Purpose/s --}}
<div class="purp narrow">
    <div class="lbl" style="width:62pt;">Purpose/s:</div>
    <div class="rows">
        @foreach ($rows as $line)
            <div class="rrow" style="font-size:{{ $fit($line, 423, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
        @endforeach
    </div>
</div>

{{-- Dates and times: one ruled row per date --}}
<div class="purp">
    <div class="lbl" style="width:100pt;">Date/s and Time:</div>
    <div class="rows" style="margin-left:102pt;">
        @foreach ($slotLines as $line)
            <div class="rrow" style="font-size:{{ $fit($line, 385, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
        @endforeach
    </div>
</div>

{{-- Venue --}}
<div class="ln">
    <div class="lbl" style="left:0; width:44pt;">Venue:</div>
    <div class="val" style="left:46pt; width:441pt; font-size:{{ $fit($venue, 441) }}pt;">{{ $venue }}</div>
</div>

{{-- Persons Involved: two columns of ruled lines --}}
<div class="purp" style="margin-bottom:10pt;">
    <div class="lbl" style="width:92pt;">Persons Involved:</div>
    <table style="margin-left:94pt; width:393pt;">
        <tr>
            <td style="width:48%;">
                @foreach ($colL as $line)
                    <div class="rrow" style="font-size:{{ $fit($line, 188, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
                @endforeach
            </td>
            <td style="width:4%;"></td>
            <td style="width:48%;">
                @foreach ($colR as $line)
                    <div class="rrow" style="font-size:{{ $fit($line, 188, 9.5, 6) }}pt;">{{ $line }}&nbsp;</div>
                @endforeach
            </td>
        </tr>
    </table>
</div>

{{-- Requested by: the student's name on the line, the caption, then their position - organization under it --}}
<div class="lab" style="margin-top:6pt;">Requested by:</div>
@php
    $requester = $studentSigner;
    $requester['name'] = $one($studentSigner['name'] ?? '');
@endphp
<table style="margin-top:2pt;">
    <tr>
        <td style="width:66%;" class="req">
            @include('pdf._signer', ['heading' => '', 's' => $requester, 'caption' => 'Name and Signature of the Student', 'signerNote' => $position !== '' ? $posText : ''])
        </td>
        <td style="width:34%;"></td>
    </tr>
</table>

<div class="lab" style="margin-top:10pt;">Attachment: Notarized Parents Permit of Students</div>

{{-- Recommending approval: adviser/chair and college dean --}}
<div class="lab" style="margin-top:12pt;">RECOMMENDING APPROVAL:</div>
<table style="margin-top:2pt;">
    <tr>
        <td style="width:46%;">
            @include('pdf._signer', ['heading' => '', 's' => $adviser, 'caption' => 'Name and Signature of the<br>Organization Adviser/Dept. Chair'])
        </td>
        <td style="width:8%;"></td>
        <td style="width:46%;">
            @include('pdf._signer', ['heading' => '', 's' => $dean, 'caption' => 'Name and Signature of the College Dean'])
        </td>
    </tr>
</table>

{{-- Approved by --}}
<div class="lab" style="margin-top:14pt;">APPROVED BY:</div>
<table style="margin-top:2pt;">
    <tr>
        <td style="width:25%;"></td>
        <td style="width:50%;">
            @include('pdf._signer', ['heading' => '', 's' => $vp, 'caption' => 'Name and Signature of the Vice President<br>for Student Development<br>Date Signed: ' . e($vpDate)])
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