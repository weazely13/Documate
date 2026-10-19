{{-- Three identical copies side by side on 8.5 x 13 in landscape, like the paper form.
     Child views fill: doc_title, copy. Needs: $code, $formCode, $fingerprint, $seal, $qr, $copies, $preview. --}}
@php
    $lookup = preg_replace('#^https?://#', '', route('document.lookup'));
    $copies = max(1, (int) ($copies ?? 3));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>@yield('doc_title') {{ $code }}</title>
<style>
    @page { margin: 10pt; }
    body { font-family: 'Helvetica', Arial, sans-serif; font-size: 8.5pt; color: #000; line-height: 1.25; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    td { padding: 0; vertical-align: top; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .serif { font-family: 'Times', 'Times New Roman', serif; }

    table.sheet { table-layout: fixed; }
    td.copy { border: .8pt solid #000; padding: 8pt 9pt 5pt; height: 566pt; }

    /* Letterhead */
    table.hdr td { vertical-align: middle; }
    .hdr .t { font-size: 7.5pt; line-height: 1.2; }
    .hdr .b { font-weight: bold; }
    .hdr .blue { color: #1a6fc4; }

    .title { text-align: center; font-weight: bold; font-size: 10pt; margin: 12pt 0 10pt; }
    .stmt { font-size: 8.5pt; line-height: 1.35; }
    .ul { border-bottom: .6pt solid #000; font-weight: bold; padding: 0 3pt; }
    .cap { font-size: 6pt; line-height: 1.1; padding-left: 70pt; margin-bottom: 1pt; }
    .rule { border-top: 2pt solid #000; margin: 8pt 0 6pt; }

    /* Signatures */
    table.sgrid { margin-top: 3pt; }
    table.sgrid td.col { width: 48%; text-align: center; }
    table.sgrid td.gap { width: 4%; }
    .sline { border-top: .6pt solid #000; padding-top: 1pt; font-size: 6.5pt; font-weight: bold; text-transform: uppercase; text-align: center; }
    .scap { font-size: 7pt; line-height: 1.2; text-align: center; }
    .sseal { font-size: 5pt; color: #777; text-align: center; margin-top: 1pt; }

    /* Form number, verification */
    .fno { border-top: 1.2pt solid #000; margin-top: 8pt; padding-top: 2pt; }
    .fno .no { font-size: 9pt; font-weight: bold; font-style: italic; }
    .vline { font-size: 5pt; color: #666; }

    @if ($preview ?? false)
        html { background: #e5e7eb; }
        body { zoom: .55; width: 936pt; margin: 0 auto; background: #fff; position: relative; }
        .watermark { position: absolute; top: 2pt; right: 10pt; font-size: 8pt; letter-spacing: 1pt; color: #b45309; background: #fffbeb; border: .6pt solid #fcd34d; padding: 2pt 6pt; }
    @endif
</style>
</head>
<body>

<table class="sheet">
    <tr>
        @for ($c = 0; $c < $copies; $c++)
            <td class="copy" style="width: {{ round(100 / $copies, 3) }}%;">

                <table class="hdr">
                    <tr>
                        <td style="width:60pt;">@if ($seal)<img src="{{ $seal }}" width="50" height="50">@endif</td>
                        <td class="t serif">
                            <div>LEYTE NORMAL UNIVERSITY</div>
                            <div>Tacloban City</div>
                            <div class="b">OFFICE OF THE VICE PRESIDENT<br>FOR STUDENT DEVELOPMENT</div>
                            <div class="blue">vpsdas@lnu.edu.ph</div>
                            <div class="blue">053 888 0855 local 260</div>
                        </td>
                    </tr>
                </table>

                @yield('copy')

                <div class="fno">
                    <table>
                        <tr>
                            <td class="no">{{ $formCode }}</td>
                            <td style="width:30pt; text-align:right;">@if ($qr)<img src="{{ $qr }}" width="26" height="26">@endif</td>
                        </tr>
                    </table>
                    @unless ($preview ?? false)
                        <div class="vline">Verify at {{ $lookup }} &middot; Ref. <span class="mono">{{ $code }}</span> &middot; Fingerprint <span class="mono">{{ $fingerprint ?: '-' }}</span></div>
                    @endunless
                </div>
            </td>
        @endfor
    </tr>
</table>

@if ($preview ?? false)
    <div class="watermark">PREVIEW{{ $issuedAt ? '' : ' · NOT YET ISSUED' }}</div>
@endif
</body>
</html>