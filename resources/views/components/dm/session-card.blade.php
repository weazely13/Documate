{{-- A morning or afternoon session card, as on the admin dashboard: a flat sky (morning) or lavender
     (afternoon) tint, a faint sun or cloud in the corner, a status pill, "N of M seen" and a progress bar.
     Sizes follow the nearest `cq` container, so two cards sit side by side even on a phone.
     Anything passed in the slot (now serving, actions, queue numbers) goes under the bar. --}}
@props([
    'session' => 'morning',
    'label' => null,
    'time' => null,
    'status' => null,
    'live' => false,
    'value' => 0,
    'total' => 0,
    'unit' => 'seen',
])
@php
    $morning = $session === 'morning';
    $pct = $total > 0 ? min(100, (int) round($value / $total * 100)) : 0;
@endphp
<div {{ $attributes->merge(['class' => 'relative isolate flex min-w-0 flex-col overflow-hidden rounded-xl p-3 text-slate-900 cq-sm:p-4 ' . ($morning ? 'bg-[#dcecfa]' : 'bg-[#e6e0f5]')]) }}>
    <i class="bx {{ $morning ? 'bx-sun' : 'bx-cloud' }} pointer-events-none absolute -bottom-3 -right-2 -z-10 text-5xl text-slate-900/[0.07] cq-sm:text-6xl" aria-hidden="true"></i>
    <div class="flex flex-wrap items-center justify-between gap-1">
        <p class="min-w-0 text-xs font-semibold cq-sm:text-sm">
            {{ $label ?? ucfirst($session) }}@if ($time)<span class="hidden font-normal text-slate-600 cq-sm:inline"> · {{ $time }}</span>@endif
        </p>
        @if ($status)
            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold cq-sm:px-2.5 cq-sm:text-[11px] {{ $live ? 'bg-white/80 text-emerald-800' : 'bg-white/70 text-slate-600' }}">{{ $status }}</span>
        @endif
    </div>
    <p class="mt-2 text-xl font-semibold tabular-nums cq-sm:mt-3 cq-sm:text-2xl">{{ $value }} <span class="text-xs font-medium text-slate-600 cq-sm:text-sm">of {{ $total }} {{ $unit }}</span></p>
    <span class="mt-2 block h-1.5 overflow-hidden rounded-full bg-slate-900/10" role="img" aria-label="{{ $pct }}% {{ $unit }}">
        <span class="block h-full rounded-full {{ $morning ? 'bg-[#2A57B4]' : 'bg-violet-600' }}" style="width: {{ $pct }}%"></span>
    </span>
    {{ $slot }}
</div>
