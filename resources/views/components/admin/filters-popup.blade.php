{{-- The Filters button and its pop-up (Workflow Review, the Appointments All tab). The page still scrolls while it is
     open: its backdrop lets the wheel and touch through, and a click outside closes it. On phones it is a bottom sheet.
     <x-admin.filters-popup :count="$activeFilters" reset="resetFilters"> labelled fields </x-admin.filters-popup>
     The fields go in the slot, laid out in two columns (col-span-2 for a full-width one). --}}
@props(['count' => 0, 'reset' => 'resetFilters'])
<div {{ $attributes->merge(['class' => 'relative shrink-0']) }} x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
    <button type="button" @click="open = ! open" :aria-expanded="open.toString()" aria-haspopup="dialog" aria-label="Filters"
            class="relative inline-flex h-9 items-center justify-center gap-2 rounded-lg border bg-white px-2.5 text-sm font-medium shadow-sm transition hover:bg-slate-50 sm:px-3.5 [@media(pointer:coarse)]:h-11"
            :class="open ? 'border-[#2A57B4]/40 text-[#2A57B4]' : 'border-slate-200 text-slate-700'">
        <i class="bx bx-slider-alt text-base" aria-hidden="true"></i>
        <span class="hidden sm:inline">Filters</span>
        @if ($count)
            <span class="absolute -right-1.5 -top-1.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-[#2A57B4] px-1 text-[10px] font-semibold text-white sm:static sm:h-auto sm:min-w-0 sm:px-1.5 sm:text-[11px]">{{ $count }}</span>
        @endif
    </button>

    {{-- Phones: a dim behind the sheet that does not catch the finger, so the list still scrolls --}}
    <div x-show="open" x-cloak class="pointer-events-none fixed inset-0 z-[90] bg-slate-900/30 sm:hidden" aria-hidden="true"></div>
    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-x-0 bottom-0 z-[95] max-h-[80dvh] overflow-y-auto rounded-t-2xl bg-white p-4 pb-[max(1rem,env(safe-area-inset-bottom))] shadow-2xl
                sm:absolute sm:z-50 sm:inset-x-auto sm:bottom-auto sm:left-0 sm:top-full sm:mt-2 sm:max-h-[min(36rem,calc(100dvh-10rem))] sm:w-[24rem] sm:rounded-xl sm:border sm:border-slate-200 sm:p-4 sm:shadow-lg"
         role="dialog" aria-label="Filters">
        <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-300 sm:hidden" aria-hidden="true"></div>
        <div class="mb-3 flex items-center justify-between">
            <p class="text-[15px] font-semibold text-slate-900">Filters</p>
            <button type="button" @click="open = false" aria-label="Close filters" class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"><i class="bx bx-x text-xl" aria-hidden="true"></i></button>
        </div>
        <div class="grid grid-cols-2 gap-3">
            {{ $slot }}
        </div>
        <div class="mt-4 grid grid-cols-2 gap-2">
            <button type="button" wire:click="{{ $reset }}" @click="open = false"
                    class="inline-flex min-h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-sm font-medium text-slate-700 hover:bg-slate-50 [@media(pointer:coarse)]:min-h-11">Reset</button>
            <button type="button" @click="open = false"
                    class="inline-flex min-h-9 items-center justify-center rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] [@media(pointer:coarse)]:min-h-11">Done</button>
        </div>
    </div>
</div>
