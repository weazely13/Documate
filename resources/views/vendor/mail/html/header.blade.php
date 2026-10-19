@props(['url'])
<tr>
<td class="header">
<a href="{{ config('app.url') }}" style="display:inline-block;">
@if (is_file(public_path('images/DocumateLogo.png')))
<img src="cid:documate-logo.png" alt="DocuMate" height="56" style="height:56px;width:auto;border:0;"><br>
@endif
<span style="font-weight:700;color:#2A57B4;">DocuMate</span>
</a>
</td>
</tr>