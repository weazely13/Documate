{{-- Needs: $s (signer array, may be empty), $caption (trusted HTML).
     Signed: "Digitally signed by:" + signature, then the printed name ON the line, caption under it. --}}
@php $signedBlock = ! empty($s['signature']) || ! empty($s['no_image']); @endphp
@if ($signedBlock)
    <div style="height:7pt; line-height:7pt; font-size:6pt; color:#444; text-align:left;">Digitally signed by:</div>
@endif
<div style="height:{{ $signedBlock ? 23 : 30 }}pt; text-align:center; line-height:{{ $signedBlock ? 23 : 30 }}pt;">
    @if (! empty($s['signature']))
        <img src="{{ $s['signature'] }}" alt="Signature of {{ $s['name'] ?? 'the signer' }}" style="height:{{ $signedBlock ? 22 : 28 }}pt; vertical-align:bottom;">
    @elseif (! empty($s['no_image']))
        <span style="font-size:8pt; font-weight:bold; letter-spacing:1.2pt; color:#111;">APPROVED</span>
    @endif
</div>
<div class="sline" style="border-top:0; border-bottom:.6pt solid #000; padding:0 0 1pt 0;">{{ $s['name'] ?? '' }}&nbsp;</div>
<div class="scap" style="padding-top:1pt;">{!! $caption !!}</div>
@if (! empty($s['seal']) && ! empty($s['signed_at']))
    <div class="sseal">E-signed {{ $s['signed_at']->format('M j, Y g:i A') }} &middot; Seal {{ $s['seal'] }}</div>
@endif
