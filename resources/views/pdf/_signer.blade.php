{{-- Needs: $heading, $caption, $s (signer array from DocumentPdf: name, signature, signed_at, seal) --}}
<div class="sig-head">{{ $heading }}</div>
<div class="sig-img">@if (! empty($s['signature']))<img src="{{ $s['signature'] }}" style="height:38pt;">@endif</div>
<div class="sig-name">{{ $s['name'] ?? '' }}&nbsp;</div>
<div class="sig-cap">{!! $caption !!}</div>
@if (! empty($s['seal']) && ! empty($s['signed_at']))
    <div class="sig-seal">E-signed {{ $s['signed_at']->format('M j, Y g:i A') }} &middot; Seal {{ $s['seal'] }}</div>
@endif