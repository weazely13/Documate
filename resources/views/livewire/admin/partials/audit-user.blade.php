{{-- Who did it, as Workflow review shows a student: a round avatar (photo over initials) with the name, and the role
     under it. System entries get a cog. $r is a row from AuditTrail::present(), $size the avatar's size classes. --}}
@php $isSystem = $r['user'] === 'System'; @endphp
<span class="flex min-w-0 items-center gap-3">
    @if ($isSystem)
        <span class="grid {{ $size }} shrink-0 place-items-center rounded-full bg-slate-100 text-slate-500" aria-hidden="true"><i class="bx bx-cog text-base"></i></span>
    @else
        <span class="relative grid {{ $size }} shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] font-semibold text-[#1E4090]" aria-hidden="true">
            {{ \App\Support\Honorifics::initials($r['user']) }}
            @if ($r['avatar'])
                <img src="{{ $r['avatar'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
            @endif
        </span>
    @endif
    <span class="min-w-0">
        <span class="block max-w-[220px] truncate font-medium {{ $isSystem ? 'text-slate-500' : 'text-slate-900' }}" title="{{ $r['user'] }}">{{ $r['user'] }}</span>
        @if ($r['role'])
            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::headline($r['role']) }}</span>
        @endif
    </span>
</span>
