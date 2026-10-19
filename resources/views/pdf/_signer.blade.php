<div class="sig-head">{{ $heading }}</div>
<table style="width:100%"><tr><td style="height:44pt; vertical-align:bottom; text-align:center; padding:0;">
    @if (! empty($s['signature']))
        <img src="{{ $s['signature'] }}" style="height:38pt; vertical-align:bottom;">
    @elseif (! empty($s['no_image']))
        <span style="font-size:7pt; color:#777;">Signed electronically</span>
    @endif
</td></tr></table>
<div class="sig-name">{{ $s['name'] ?? '' }}&nbsp;</div>
<div class="sig-cap">{!! $caption !!}</div>
@if (! empty($s['seal']) && ! empty($s['signed_at']))
    <div class="sig-seal">E-signed {{ $s['signed_at']->format('M j, Y g:i A') }} &middot; Seal {{ $s['seal'] }}</div>
@endif