{{-- Needs: $s (signer array, may be empty), $caption (trusted HTML). --}}
<div style="height:30pt; text-align:center; line-height:30pt;">
    @if (! empty($s['signature']))
        <img src="{{ $s['signature'] }}" style="height:28pt; vertical-align:bottom;">
    @elseif (! empty($s['no_image']))
        <span style="font-size:8pt; font-weight:bold; letter-spacing:1.2pt; color:#111;">APPROVED</span>
    @endif
</div>
<div class="sline">{{ $s['name'] ?? '' }}&nbsp;</div>
<div class="scap">{!! $caption !!}</div>
@if (! empty($s['seal']) && ! empty($s['signed_at']))
    <div class="sseal">E-signed {{ $s['signed_at']->format('M j, Y g:i A') }} &middot; Seal {{ $s['seal'] }}</div>
@endif