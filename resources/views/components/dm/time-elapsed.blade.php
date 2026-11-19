{{-- How long something has waited, as one word and a day count ("Late · 5d"), with a pop-out that says what it
     means in plain words (hover, or tap/focus on touch). Needs :w = App\Support\WaitingTime::describe(...).
     <x-dm.time-elapsed :w="$row->waiting" class="text-red-700" /> --}}
@props(['w'])
<span tabindex="0" {{ $attributes->merge(['class' => 'group/te relative inline-flex cursor-help items-center gap-2 rounded outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40']) }}
      aria-label="{{ $w['text'] }}. {{ $w['hint'] }}">
    <span class="h-2 w-2 shrink-0 rounded-full {{ $w['dot'] }}" aria-hidden="true"></span>
    <span aria-hidden="true">{{ $w['text'] }}</span>
    <span role="tooltip" aria-hidden="true"
          class="pointer-events-none absolute right-full top-1/2 z-40 mr-2 hidden w-60 -translate-y-1/2 whitespace-normal rounded-lg bg-slate-900 px-3 py-2 text-left text-xs font-normal leading-relaxed text-white shadow-lg group-hover/te:block group-focus/te:block">
        {{ $w['hint'] }}
    </span>
</span>
