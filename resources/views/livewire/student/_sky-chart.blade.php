{{-- A chart drawn straight onto the Home sky (no card): one series of columns, the peak picked out.
     Colours follow the sky's ink, so it reads on a light morning and a dark night alike.
     Compact on purpose: it shares the space above the centered search with the greeting.
     Needs: $title, $note, $bars = [['label' => 'M', 'name' => 'Mondays', 'n' => 12, 'sub' => 'AM' (optional)], ...], $caption (optional). --}}
@php
    $max = max(1, collect($bars)->max('n'));
    $total = collect($bars)->sum('n');
    $peak = $total ? collect($bars)->search(fn ($b) => $b['n'] === collect($bars)->max('n')) : null;
@endphp
<div class="mx-auto grid w-full max-w-3xl items-center gap-x-10 gap-y-4 text-center sm:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)] sm:text-left">
    <div class="min-w-0">
        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-[#1E4090] group-data-[ink=light]/greet:text-blue-100">When the office is busiest</p>
        <h2 class="mt-1 text-[clamp(1.25rem,3.4dvh,1.75rem)] font-semibold tracking-tight text-slate-900 group-data-[ink=light]/greet:text-white">{{ $title }}</h2>
        <p class="mt-1 hidden text-sm text-slate-600 sm:block group-data-[ink=light]/greet:text-blue-50 [@media(max-height:780px)]:!hidden">{{ $note }}</p>
        @if ($total && ! empty($caption))
            <p class="mt-2 text-sm text-slate-700 group-data-[ink=light]/greet:text-blue-50">{!! $caption !!}</p>
        @endif
    </div>

    @if ($total)
        <div class="flex h-[clamp(4rem,12dvh,8rem)] items-end justify-center gap-1.5 sm:gap-2.5" role="img"
             aria-label="{{ $title }}: {{ collect($bars)->map(fn ($b) => $b['name'] . ' ' . $b['n'])->implode(', ') }}">
            @foreach ($bars as $k => $bar)
                <div class="flex h-full max-w-[2.5rem] flex-1 flex-col items-center justify-end" title="{{ $bar['name'] }}: {{ number_format($bar['n']) }} {{ \Illuminate\Support\Str::plural('submission', $bar['n']) }}">
                    <span class="mb-1 text-[11px] font-semibold tabular-nums {{ $k === $peak ? 'text-slate-900 group-data-[ink=light]/greet:text-white' : 'invisible' }}">{{ number_format($bar['n']) }}</span>
                    <div class="w-full rounded-t-[4px] rounded-b-[2px] {{ $k === $peak ? 'bg-[#2A57B4] group-data-[ink=light]/greet:bg-white' : 'bg-[#2A57B4]/25 group-data-[ink=light]/greet:bg-white/30' }}"
                         style="height: {{ max(4, $bar['n'] / $max * 100) }}%"></div>
                    <span class="mt-1 text-[11px] font-medium leading-tight {{ $k === $peak ? 'text-slate-900 group-data-[ink=light]/greet:text-white' : 'text-slate-600 group-data-[ink=light]/greet:text-blue-100' }}">{{ $bar['label'] }}@isset($bar['sub'])<span class="block text-[9px] font-semibold opacity-70">{{ $bar['sub'] }}</span>@endisset</span>
                </div>
            @endforeach
        </div>
    @else
        <p class="text-sm text-slate-600 group-data-[ink=light]/greet:text-blue-50">No submissions in the last 30 days yet.</p>
    @endif
</div>
