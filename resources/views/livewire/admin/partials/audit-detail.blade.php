{{-- An audit entry's details, under its row or card: the changed fields (before → after) or the recorded values,
     then the email, IP address, browser and a link to the transaction. $r is a row from AuditTrail::present(). --}}
@php use Illuminate\Support\Str; @endphp
<div class="space-y-3 text-sm">
    @if ($r['changes'])
        @if ($r['is_diff'])
            <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                <table class="min-w-full text-xs">
                    <thead class="border-b border-slate-200 text-left text-slate-500">
                        <tr><th class="px-3 py-2 font-medium">Field</th><th class="px-3 py-2 font-medium">Before</th><th class="px-3 py-2 font-medium">After</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($r['changes'] as $fieldName => $pair)
                            <tr>
                                <td class="whitespace-nowrap px-3 py-2 font-medium text-slate-700">{{ Str::headline($fieldName) }}</td>
                                <td class="max-w-xs break-words px-3 py-2 text-rose-700"><span class="bg-rose-50">{{ $show($pair[0] ?? null) }}</span></td>
                                <td class="max-w-xs break-words px-3 py-2 text-emerald-700"><span class="bg-emerald-50">{{ $show($pair[1] ?? null) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 rounded-lg border border-slate-200 bg-white p-3 text-xs">
                @foreach ($r['changes'] as $k => $v)
                    <dt class="font-medium text-slate-500">{{ Str::headline((string) $k) }}</dt>
                    <dd class="break-words text-slate-700">{{ $show($v) }}</dd>
                @endforeach
            </dl>
        @endif
    @endif

    <div class="flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-500">
        @if ($r['email'])<span class="min-w-0 break-all"><i class="bx bx-envelope" aria-hidden="true"></i> {{ $r['email'] }}</span>@endif
        @if ($r['ip'])<span><i class="bx bx-globe" aria-hidden="true"></i> {{ $r['ip'] }}</span>@endif
        @if ($r['agent'])<span class="min-w-0 max-w-full truncate" title="{{ $r['agent'] }}"><i class="bx bx-desktop" aria-hidden="true"></i> {{ Str::limit($r['agent'], 90) }}</span>@endif
        @if ($r['link'])<a href="{{ $r['link'] }}" wire:navigate wire:click.stop class="font-medium text-[#2A57B4] hover:underline">Open transaction →</a>@endif
    </div>
</div>
