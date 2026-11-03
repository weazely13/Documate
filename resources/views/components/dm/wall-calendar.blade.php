{{-- The wall calendar from the admin dashboard: two binder rings, a month header with its own
     navigation, a brand-tinted weekday row, then the caller's grid of day boxes (the slot).
     prev / next / today are Livewire actions; `today` is shown only when it is set.
     Pass :weekdays="false" when the slot draws its own day headers (the week view). --}}
@props([
    'title',
    'subtitle' => null,
    'prev' => null,
    'next' => null,
    'today' => null,
    'unit' => 'month',
    'weekdays' => true,
    'clip' => true,
])
@php
    $navBtn = 'flex h-8 w-8 items-center justify-center rounded-lg text-slate-600 hover:bg-slate-200/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:h-10 [@media(pointer:coarse)]:w-10';
@endphp
<div {{ $attributes->merge(['class' => 'relative pt-3']) }}>
    @foreach (['left-[22%]', 'right-[22%]'] as $ringPos)
        <span class="absolute top-0 z-10 h-7 w-3 rounded-full bg-gradient-to-b from-slate-200 via-slate-400 to-slate-500 shadow ring-2 ring-white {{ $ringPos }}" aria-hidden="true"></span>
    @endforeach

    {{-- clip=false lets popovers inside spill past the frame; the header then rounds its own corners --}}
    <div class="{{ $clip ? 'overflow-hidden' : '' }} rounded-xl border border-slate-200 bg-white shadow-md">
        <div class="flex items-center justify-between gap-3 rounded-t-xl border-b border-slate-200 bg-slate-50 px-4 pb-3 pt-5 text-slate-900">
            <div class="min-w-0">
                <p class="truncate text-xl font-semibold leading-none tracking-tight" aria-live="polite">{{ $title }}</p>
                @if ($subtitle)
                    <p class="mt-1 truncate text-xs text-slate-500">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="flex shrink-0 items-center gap-1">
                {{ $actions ?? '' }}
                @if ($today)
                    <button type="button" wire:click="{{ $today }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-700 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] [@media(pointer:coarse)]:py-2">Today</button>
                @endif
                @if ($prev)
                    <button type="button" wire:click="{{ $prev }}" class="{{ $navBtn }}" aria-label="Previous {{ $unit }}"><i class="bx bx-chevron-left text-xl" aria-hidden="true"></i></button>
                @endif
                @if ($next)
                    <button type="button" wire:click="{{ $next }}" class="{{ $navBtn }}" aria-label="Next {{ $unit }}"><i class="bx bx-chevron-right text-xl" aria-hidden="true"></i></button>
                @endif
            </div>
        </div>

        @if ($weekdays)
            <div class="grid grid-cols-7 border-b border-blue-100 bg-blue-50 text-center text-[11px] font-semibold uppercase tracking-wide" aria-hidden="true">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $i => $wd)
                    <span class="py-1.5 {{ $i === 0 ? 'text-rose-600' : 'text-[#2A57B4]' }}">{{ $wd }}</span>
                @endforeach
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
