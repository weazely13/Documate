@php
    $rater = $signers[0] ?? [];
    $picked = (int) ($rater['response']['rating'] ?? 0);
    $dateSigned = ! empty($rater['signed_at']) ? $rater['signed_at']->format('F j, Y') : '';
    $labels = \App\Workflow\Documents\ModelStudentRating::RATINGS;
    $course = ($v['student_course'] ?? '') ?: ($v['student_program'] ?? '');
    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>YMCA Outstanding Student Award Faculty/Peer Rating Form {{ $code }}</title>
<style>
    @page { margin: 36pt 54pt 96pt 54pt; }
    body { font-family: 'Times', 'Times New Roman', serif; font-size: 11.5pt; color: #000; line-height: 1.5; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: middle; padding: 0; }
    .sans { font-family: 'Helvetica', Arial, sans-serif; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    /* Letterhead */
    .hdr { width: 330pt; margin: 0 auto; }
    .hdr .t { font-size: 8.5pt; line-height: 1.25; }
    .hdr .b { font-weight: bold; }
    .hdr .blue { color: #1a6fc4; }
    .title { text-align: center; font-weight: bold; font-size: 11.5pt; margin: 20pt 0 14pt; }

    /* Statement */
    .para { text-indent: 30pt; text-align: left; }
    .ul { border-bottom: .8pt solid #000; font-weight: bold; padding: 0 6pt; }
    .cap { font-size: 10pt; font-style: italic; text-align: left; padding-left: 60pt; margin-top: 1pt; }

    /* Ratings */
    table.opts td { padding: 1pt 0; }
    table.opts td.line { width: 150pt; border-bottom: .8pt solid #000; text-align: center; font-weight: bold; font-size: 10.5pt; height: 14pt; }
    table.opts td.gap { width: 56pt; }
    table.opts td.lbl { width: 130pt; }
    table.opts td.num { width: 40pt; }

    /* Signature */
    .sigwrap { width: 250pt; margin: 30pt auto 0; text-align: center; }
    .sig-head { display: none; }
    .sig-name { border-top: .8pt solid #000; padding-top: 2pt; font-size: 9pt; font-weight: bold; text-transform: uppercase; }
    .sig-cap { font-size: 11pt; line-height: 1.3; }
    .sig-seal { font-size: 6.5pt; color: #777; margin-top: 3pt; }
    .sig-detail { font-size: 6.5pt; color: #666; }

    .note { margin-top: 26pt; font-size: 7.5pt; font-style: italic; line-height: 1.3; }
    .formno { border-top: 1.2pt solid #000; margin-top: 4pt; padding-top: 1pt; font-size: 10.5pt; line-height: 1.3; }

    /* Verification footer */
    .foot { position: fixed; left: 0; right: 0; bottom: -78pt; height: 72pt; border-top: .6pt solid #c8c8c8; padding-top: 8pt; font-family: 'DejaVu Sans', sans-serif; }
    .foot td { vertical-align: top; }
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

<div class="title">YMCA OUTSTANDING STUDENT AWARD FACULTY/PEER RATING FORM</div>

<div class="para">
    This is to inform the Office of the Vice President for Student Development that I personally know
    Mr./Ms. <span class="ul">{{ $v['student_name'] ?? '' }}</span>,
    a <span class="ul">{{ $course }}</span> student of Leyte Normal University.
</div>
<div class="cap">(Program being pursued)</div>

<div style="margin-top:4pt;">
    Based on my honest assessment with respect to his/her interpersonal relations with his/her
    classmates and teachers, I am giving him/her a rating of:
</div>

<table class="opts" style="margin-top:4pt;">
    @foreach ($labels as $n => $label)
        <tr>
            <td style="width:40pt;"></td>
            <td class="line">{{ $picked === $n ? 'X' : '' }}</td>
            <td class="gap"></td>
            <td class="lbl">{{ $label }}</td>
            <td class="num">({{ $n }})</td>
            <td></td>
        </tr>
    @endforeach
</table>

<div class="sigwrap">
    @include('pdf._signer', [
        'heading' => '',
        's' => $rater,
        'caption' => 'Name and Signature of the Rater<br>Date: <u>&nbsp;' . e($dateSigned) . '&nbsp;</u>',
    ])
</div>

<div class="note">The data collected here will be for the sole use of legitimate interests of the university as an educational institution.</div>
<div class="formno">{{ $formCode }}</div>

@unless ($preview ?? false)
<div class="foot">
    <table>
        <tr>
            <td style="width:70pt;">@if ($qr)<img src="{{ $qr }}" width="58" height="58">@endif</td>
            <td>
                <div class="foot-k">Verify this document</div>
                <div class="foot-t">Scan the QR code, or go to <strong>{{ $lookup }}</strong> and enter:</div>
                <div class="foot-t" style="margin-top:2pt;">
                    Reference &nbsp;<span class="foot-v mono">{{ $code }}</span><br>
                    Fingerprint &nbsp;<span class="foot-v mono">{{ $fingerprint ?: '-' }}</span>
                </div>
            </td>
            <td style="width:135pt;" class="foot-form">{{ $formCode }}</td>
        </tr>
    </table>
</div>
@endunless

@if ($preview ?? false)
    <div class="watermark">PREVIEW{{ $issuedAt ? '' : ' · NOT YET ISSUED' }}</div>
@endif
</body>
</html>