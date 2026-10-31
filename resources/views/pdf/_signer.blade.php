{{-- Needs: $heading, $s (signer array, may be empty), $caption (trusted HTML). Optional: $signerNote (plain text under the caption).
     Signed: "Digitally signed by:" + signature, then the printed name ON the line, caption under it. --}}
@php $signedBlock = ! empty($s['signature']) || ! empty($s['no_image']); @endphp
<div class="sig-head">{{ $heading }}</div>
@if ($signedBlock)
    <div style="height:8pt; line-height:8pt; font-size:6.5pt; font-weight:normal; color:#444; text-align:left; text-transform:none; letter-spacing:0;">Digitally signed by:</div>
@endif
<table style="width:100%"><tr><td style="height:{{ $signedBlock ? 36 : 44 }}pt; vertical-align:bottom; text-align:center; padding:0;">
    @if (! empty($s['signature']))
        <img src="{{ $s['signature'] }}" alt="Signature of {{ $s['name'] ?? 'the signer' }}" style="height:{{ $signedBlock ? 32 : 38 }}pt; vertical-align:bottom;">
    @elseif (! empty($s['no_image']))
        <span style="font-size:9pt; font-weight:bold; letter-spacing:1.5pt; color:#111;">APPROVED</span>
    @endif
</td></tr></table>
<div class="sig-name" style="border-top:0; border-bottom:.6pt solid #000; padding:0 0 1pt 0;">{{ $s['name'] ?? '' }}&nbsp;</div>
<div class="sig-cap" style="padding-top:2pt;">{!! $caption !!}</div>
@if (! empty($signerNote))
    <div class="sig-cap" style="padding-top:1pt;">{{ $signerNote }}</div>
@endif
@if (! empty($s['seal']) && ! empty($s['signed_at']))
    <div class="sig-seal">E-signed {{ $s['signed_at']->format('M j, Y g:i A') }} &middot; Seal {{ $s['seal'] }}</div>
@endif
