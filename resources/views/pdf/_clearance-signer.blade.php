{{-- Needs: $s (signer array, may be empty), $caption (trusted HTML). --}}
<div style="height:21pt; text-align:center; line-height:21pt;">
    @if (! empty($s['signature']))
        <img src="{{ $s['signature'] }}" style="height:20pt; vertical-align:bottom;">
    @elseif (! empty($s['no_image']))
        <span style="font-size:5.5pt; color:#777;">Signed electronically</span>
    @endif
</div>
<div class="sline">{{ $s['name'] ?? '' }}&nbsp;</div>
<div class="scap">{!! $caption !!}</div>
@if (! empty($s['seal']) && ! empty($s['signed_at']))
    <div class="sseal">E-signed {{ $s['signed_at']->format('M j, Y g:i A') }} &middot; Seal {{ $s['seal'] }}</div>
@endif