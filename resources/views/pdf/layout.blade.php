{{-- Shared A4 layout for every DocuMate PDF (minimal). Child views fill: doc_title, doc_subtitle (optional), body.
     Needs: $code, $issuedAt, $formCode, $fingerprint, $seal, $qr. Tables only: Dompdf has no flex/grid. --}}
@php
    $issued = $issuedAt ? $issuedAt->format('F j, Y') : '';
    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('doc_title') {{ $code }}</title>
<style>
    @page { margin: 38pt 54pt 96pt 54pt; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; color: #222; line-height: 1.6; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; padding: 0; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }

    /* Letterhead */
    .hdr td { vertical-align: middle; }
    .org-name { font-size: 11pt; font-weight: bold; color: #111; letter-spacing: .3pt; }
    .org-office { font-size: 8.5pt; color: #444; }
    .org-meta { font-size: 7.5pt; color: #888; }
    .rule { border-top: .6pt solid #c8c8c8; margin: 12pt 0 26pt; }

    /* Title */
    .title { text-align: center; font-size: 14pt; font-weight: bold; color: #111; margin: 0; }
    .subtitle { text-align: center; font-size: 8pt; color: #888; margin: 3pt 0 0; }
    .refline { text-align: center; font-size: 7.5pt; color: #888; margin: 8pt 0 26pt; }

    /* Body */
    .statement { text-align: justify; line-height: 1.9; }
    .val { font-weight: bold; }
    .box-label { font-size: 7pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; margin: 14pt 0 4pt; }
    .box { border-left: 1.5pt solid #bbb; padding: 2pt 0 2pt 10pt; min-height: 48pt; line-height: 1.6; }

    /* Signature blocks */
    .sigs { margin-top: 30pt; }
    .sigs td.col { width: 48%; text-align: center; }
    .sigs td.gap { width: 4%; }
    .sig-head { text-align: center; font-size: 7pt; color: #888; text-transform: uppercase; letter-spacing: .8pt; margin-bottom: 2pt; }
    .sig-img { height: 44pt; text-align: center; line-height: 44pt; }
    .sig-name { border-top: .6pt solid #555; margin: 0 14pt; padding-top: 3pt; font-weight: bold; font-size: 9pt; text-transform: uppercase; }
    .sig-cap { font-size: 7.5pt; color: #555; line-height: 1.4; }
    .sig-seal { font-size: 6.5pt; color: #999; margin-top: 3pt; line-height: 1.4; }
    .sig-detail { font-size:6.5pt; color:#666; text-align:center; line-height:1.3; }

    /* Verification footer */
    .foot { position: fixed; left: 0; right: 0; bottom: -78pt; height: 72pt; border-top: .6pt solid #c8c8c8; padding-top: 8pt; }
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
@yield('extra_css')
</head>
<body>

<table class="hdr">
    <tr>
        <td style="width:52pt;">@if ($seal)<img alt="Leyte Normal University seal" src="{{ $seal }}" width="42" height="42">@endif</td>
        <td>
            <div class="org-name">LEYTE NORMAL UNIVERSITY</div>
            <div class="org-office">Office of the Vice President for Student Development</div>
            <div class="org-meta">Tacloban City &middot; vpsdas@lnu.edu.ph &middot; 053 888 0855 local 260</div>
        </td>
    </tr>
</table>
<div class="rule"></div>

<div class="title">@yield('doc_title')</div>
@hasSection('doc_subtitle')<div class="subtitle">@yield('doc_subtitle')</div>@endif
<div class="refline">Ref. {{ $code }}@if ($issued) &nbsp;&middot;&nbsp; Issued {{ $issued }}@endif</div>

@yield('body')

<div class="foot">
    <table>
        <tr>
            <td style="width:70pt;">@if ($qr)<img alt="QR code for verifying this document" src="{{ $qr }}" width="58" height="58">@elseif ($preview ?? false)<span class="ph-qr" style="width:58px;height:58px;">QR code<br>on issue</span>@endif</td>
            <td>
                <div class="foot-k">Verify this document</div>
                <div class="foot-t">Scan the QR code, or go to <strong>{{ $lookup }}</strong> and enter:</div>
                <div class="foot-t" style="margin-top:2pt;">
                    Reference &nbsp;<span class="foot-v mono">@if ($code){{ $code }}@else<span class="ph-text">assigned on submit</span>@endif</span><br>
                    Fingerprint &nbsp;<span class="foot-v mono">@if ($fingerprint){{ $fingerprint }}@elseif ($preview ?? false)<span class="ph-text">assigned when issued</span>@else-@endif</span>
                </div>
            </td>
            <td style="width:135pt;" class="foot-form">{{ $formCode }}</td>
        </tr>
    </table>
</div>

</body>
@if ($preview ?? false)
    <div class="watermark">PREVIEW{{ $issuedAt ? '' : ' · NOT YET ISSUED' }}</div>
@endif
</html>