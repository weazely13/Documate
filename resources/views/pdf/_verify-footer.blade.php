{{-- The verification footer on every DocuMate PDF. Sits in the page's bottom margin (each view's own .foot rule
     places it). One table only: Dompdf has no flex/grid, and some views position direct div children of .foot.
     Needs: $qr, $code, $fingerprint, $formCode; optional $issuedAt, $preview. --}}
@php
    $vfLookup = preg_replace('#^https?://#', '', route('document.lookup'));
    $vfIssued = ! empty($issuedAt) ? $issuedAt->format('F j, Y') : null;
    // The DocuMate wordmark font (the sidebar's and the landing page's), embedded from the app's own copy
    $vfFont = str_replace(DIRECTORY_SEPARATOR, '/', resource_path('fonts/GveretLevin-Regular.ttf'));
@endphp
@if ($preview ?? false)
    {{-- On screen the browser can't read the server's font file; load the same font the app uses --}}
    <link href="https://fonts.googleapis.com/css2?family=Gveret+Levin&display=swap" rel="stylesheet">
@endif
<style>
    @font-face { font-family: 'Gveret Levin'; font-style: normal; font-weight: normal; src: url('{{ $vfFont }}') format('truetype'); }
    .foot.vf { border-top: 0 !important; padding-top: 0 !important; font-family: 'DejaVu Sans', sans-serif; }
    .vf table { border-collapse: collapse; width: 100%; table-layout: auto !important; }
    .vf td { padding: 0; vertical-align: top; }
    .vf-accent { width: 54pt; border-top: 1.8pt solid #2A57B4; }
    .vf-hair { border-top: .5pt solid #cfd3da; }
    .vf-qr { width: 66pt; padding-top: 7pt !important; }
    .vf-qrbox { border: .5pt solid #cfd3da; padding: 2pt; width: 54pt; }
    .vf-qrbox img { display: block; width: 54pt; height: 54pt; }
    .vf-mid { padding: 7pt 6pt 0 2pt !important; white-space: nowrap; }
    .vf-head { font-size: 7.6pt; font-weight: bold; color: #1e3f8a; }
    .vf-how { font-size: 6.6pt; color: #666; margin-top: 1pt; }
    .vf-fields { margin-top: 5pt; }
    .vf-fields td { padding-right: 12pt !important; }
    .vf-lbl { font-size: 5.6pt; color: #8a8a8a; letter-spacing: .6pt; }
    .vf-val { font-family: 'DejaVu Sans Mono', monospace; font-size: 8.4pt; font-weight: bold; color: #0d253d; background: #f1f4fa; border: .5pt solid #d3dcee; padding: 1pt 4pt; white-space: nowrap; }
    .vf-side { width: 118pt; text-align: right; padding-top: 7pt !important; }
    .vf-brand { font-family: 'Gveret Levin', 'DejaVu Sans', sans-serif; font-size: 15pt; font-weight: normal; color: #2A57B4; line-height: 1.05; }
    .vf-meta { font-size: 6.4pt; color: #777; line-height: 1.55; }
</style>
<div class="foot vf">
    <table><tr><td class="vf-accent" width="54" style="width:54pt;"></td><td class="vf-hair"></td></tr></table>
    <table>
        <tr>
            <td class="vf-qr" width="66" style="width:66pt;">
                <div class="vf-qrbox">
                    @if ($qr)<img alt="QR code for verifying this document" src="{{ $qr }}" style="width:54pt;height:54pt;">@elseif ($preview ?? false)<span class="ph-qr" style="width:54pt;height:54pt;">QR code<br>on issue</span>@endif
                </div>
            </td>
            <td class="vf-mid">
                <div class="vf-head">Check that this document is genuine</div>
                <div class="vf-how">Scan the QR code, or enter both codes at <strong>{{ $vfLookup }}</strong></div>
                <table class="vf-fields" style="width:auto;">
                    <tr>
                        <td>
                            <div class="vf-lbl">REFERENCE NO.</div>
                            <span class="vf-val">@if ($code){{ $code }}@else<span class="ph-text">assigned on submit</span>@endif</span>
                        </td>
                        <td>
                            <div class="vf-lbl">FINGERPRINT</div>
                            <span class="vf-val">@if ($fingerprint){{ $fingerprint }}@elseif ($preview ?? false)<span class="ph-text">assigned when issued</span>@else&mdash;@endif</span>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="vf-side" width="118" style="width:118pt;">
                <div class="vf-brand">DocuMate</div>
                <div class="vf-meta">
                    Leyte Normal University &middot; VPSD<br>
                    @if ($vfIssued)Issued {{ $vfIssued }}<br>@endif
                    {{ $formCode }}
                </div>
            </td>
        </tr>
    </table>
</div>
