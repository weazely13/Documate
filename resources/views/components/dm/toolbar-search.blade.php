{{-- Toolbar search. From sm up it is an ordinary field; on phones it is a single icon button that opens the
     field over the whole toolbar, so the toolbar stays on one line. The toolbar must be `relative`.
     A dot on the icon tells a phone user a search is still applied. --}}
@props(['model' => 'search', 'placeholder' => 'Search', 'label' => 'Search'])
<div x-data="{ open: false }" {{ $attributes->merge(['class' => 'flex min-w-0 shrink-0 sm:flex-1']) }}>
    <button type="button" x-show="!open" @click="open = true; $nextTick(() => $refs.q.focus())"
            class="relative grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:!hidden [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11"
            aria-label="{{ $label }}">
        <i class="bx bx-search text-lg" aria-hidden="true"></i>
        <span x-show="$wire.{{ $model }}" x-cloak class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#2A57B4] ring-2 ring-white" aria-hidden="true"></span>
    </button>
    <div :class="open ? 'max-sm:flex' : 'max-sm:hidden'"
         class="items-center gap-1.5 max-sm:absolute max-sm:inset-0 max-sm:z-20 max-sm:rounded-2xl max-sm:bg-white max-sm:px-2 sm:flex sm:w-full">
        <div class="relative min-w-0 flex-1">
            <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
            <input x-ref="q" type="search" wire:model.live.debounce.300ms="{{ $model }}" placeholder="{{ $placeholder }}" aria-label="{{ $label }}"
                   @keydown.escape="open = false"
                   class="h-9 w-full rounded-lg border-slate-300 bg-white pl-9 pr-3 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm [@media(pointer:coarse)]:h-11">
        </div>
        <button type="button" @click="open = false" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 sm:hidden [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11" aria-label="Close search">
            <i class="bx bx-x text-xl" aria-hidden="true"></i>
        </button>
    </div>
</div>
