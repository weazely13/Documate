@php
    // Signatory version: the nominee is the picked student and the issuer is the nominator.
    $isStaff = ! empty($v['reporter_name']);

    $name = ($v['nominee_name'] ?? '') ?: ($isStaff ? ($v['student_name'] ?? '') : '');
    $college = ($v['nominee_college'] ?? '') ?: ($v['student_college'] ?? '');
    $program = ($v['nominee_program'] ?? '') ?: ($v['student_course'] ?? '');
    $yearSection = ($v['nominee_year_section'] ?? '') ?: ($v['student_year_section'] ?? '');
    $address = ($v['nominee_address'] ?? '') ?: ($v['student_address'] ?? '');
    $contact = ($v['nominee_contact'] ?? '') ?: ($v['student_contact'] ?? '');

    $nominator = $isStaff ? $reporterSigner : $studentSigner;
    $dateSigned = ($issuedAt ?? $submittedAt)?->format('F j, Y') ?? '';
    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $docTitle ?? 'College Model Student Nomination Form' }} {{ $code }}</title>
<style>
    @page { margin: 36pt 54pt 96pt 54pt; }
    body { font-family: 'Times', 'Times New Roman', serif; font-size: 11pt; color: #000; line-height: 1.3; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: bottom; padding: 0; }
    .sans { font-family: 'Helvetica', Arial, sans-serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    /* Letterhead */
    .hdr { width: 330pt; margin: 0 auto; }
    .hdr td { vertical-align: middle; }
    .hdr .t { font-size: 8.5pt; line-height: 1.25; }
    .hdr .b { font-weight: bold; }
    .hdr .blue { color: #1a6fc4; }
    .title { text-align: center; font-weight: bold; font-size: 11pt; margin: 20pt 0 24pt; }

    /* Fields */
    table.f td { padding: 4pt 0 0; }
    table.f td.l { width: 178pt; white-space: nowrap; padding-right: 6pt; }
    table.f td.u { border-bottom: .8pt solid #000; font-weight: bold; padding-bottom: 1pt; }
    table.f td.line { border-bottom: .8pt solid #000; height: 16pt; }

    /* Signature */
    .sigwrap { width: 250pt; margin: 34pt auto 0; text-align: center; }
    .sig-head { display: none; }
    .sig-name { border-top: .8pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; }
    .sig-cap { font-size: 11pt; line-height: 1.3; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; }
    .sig-detail { font-size: 6.5pt; color: #666; }

    .note { margin-top: 30pt; font-size: 7.5pt; font-style: italic; }
    .formno { border-top: 1.2pt solid #000; margin-top: 4pt; padding-top: 1pt; font-size: 10.5pt; width: 100%; }

    /* Verification footer */
    .foot { position: fixed; left: 0; right: 0; bottom: -78pt; height: 72pt; border-top: .6pt solid #c8c8c8; padding-top: 8pt; font-family: 'DejaVu Sans', sans-serif; }
    .foot-k { font-size: 6.5pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; }
    .foot-t { font-size: 7.5pt; color: #444; line-height: 1.5; }
    .foot-v { font-size: 8pt; font-weight: bold; color: #111; }
    .foot-form { font-size: 7pt; color: #888; text-align: right; white-space: nowrap; }
    @if ($preview ?? false)
        html { background: #e5e7eb; }
        body { max-width: 520pt; margin: 16pt auto; padding: 30pt 40pt; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.15); position: relative; }
        .watermark { position: absolute; top: 6pt; right: 10pt; font-size: 7pt; letter-spacing: 1pt; color: #b45309; background: #fffbeb; border: .6pt solid #fcd34d; padding: 2pt 6pt; border-radius: 3pt; }
    @endif
</style>
</head>
<body>

{{-- Letterhead --}}
<table class="hdr">
    <tr>
        <td style="width:92pt; text-align:center;">@if ($seal)<img src="{{ $seal }}" width="76" height="76">@endif</td>
        <td class="sans t">
            <div class="b">LEYTE NORMAL UNIVERSITY</div>
            <div>Tacloban City</div>
            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
            <div class="blue"><u>vpsdas@lnu.edu.ph</u></div>
            <div class="blue">053 888 0855 local 260</div>
        </td>
    </tr>
</table>

<div class="title">{{ $docTitle ?? 'COLLEGE MODEL STUDENT NOMINATION FORM' }}</div>

<table class="f">
    <tr><td class="l">Name of Nominee:</td><td class="u">{{ $name }}</td></tr>
    <tr><td class="l">College:</td><td class="u">{{ $college }}</td></tr>
    <tr><td class="l">Program Being Pursued of Nominee:</td><td class="u">{{ $program }}</td></tr>
    <tr><td class="l">Year and Section:</td><td class="u">{{ $yearSection }}</td></tr>
    <tr><td class="l">Address:</td><td class="u">{!! nl2br(e($address)) !!}&nbsp;</td></tr>
    <tr><td colspan="2" class="line">&nbsp;</td></tr>
    <tr><td class="l" style="padding-top:12pt;">Contact Number:</td><td class="u" style="padding-top:12pt;">{{ $contact }}</td></tr>
</table>

{{-- Nominator --}}
<div class="sigwrap">
    @include('pdf._signer', [
        'heading' => '',
        's' => $nominator,
        'caption' => 'Name and Signature of the Nominator<br>Date: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>',
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