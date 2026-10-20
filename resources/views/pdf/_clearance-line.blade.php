{{-- Needs: $pre, $value, $cap, $post. Fills the full text width: the value stretches between $pre and $post. --}}
@php
    $est  = 487 - (mb_strlen((string) ($pre ?? '')) * 5.2) - (mb_strlen((string) ($post ?? '')) * 5.2) - 10;
    $len  = max(1, mb_strlen((string) ($value ?? '')));
    $size = round(max(5.5, min(10, ($est - 4) / ($len * 0.6))), 1);
@endphp
<table class="cl">
    <tr>
        <td class="cl-pre">{{ $pre ?? '' }}</td>
        <td class="cl-val" style="font-size:{{ $size }}pt;">{{ $value ?? '' }}&nbsp;</td>
        <td class="cl-post">{{ $post ?? '' }}</td>
    </tr>
    <tr>
        <td></td>
        <td class="cl-cap">{{ $cap ?? '' }}</td>
        <td></td>
    </tr>
</table>