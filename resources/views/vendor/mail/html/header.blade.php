@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
<img src="{{ config('documate.mail_logo_url') ?: asset('images/DocumateLogo.png') }}"
     alt="DocuMate"
     style="height: 60px; width: auto; max-height: 60px;">
</a>
</td>
</tr>