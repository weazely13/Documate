{{-- The (i) beside a column header and what its words mean. It floats over the page (teleported), since tables clip what is inside them.
     <x-admin.legend-tip label="What the time words mean" note="..." :items="[[dotClass, word, range or '', meaning], ...]" /> --}}
@props(['label', 'note' => null, 'items' => []])
<span x-data="{ open: false, top: 0, left: 0,
        show() { const r = this.$refs.i.getBoundingClientRect(); this.top = r.bottom + 6; this.left = Math.max(12, Math.min(r.right - 288, window.innerWidth - 300)); this.open = true; } }"
      @keydown.escape.window="open = false" @scroll.window="open = false">
    <button type="button" x-ref="i" @click.stop="open ? open = false : show()" @mouseenter="show()" @mouseleave="open = false"
            class="grid h-6 w-6 place-items-center rounded text-slate-400 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30"
            aria-label="{{ $label }}" :aria-expanded="open.toString()">
        <i class="bx bx-info-circle text-base" aria-hidden="true"></i>
    </button>
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity :style="`top:${top}px;left:${left}px`" @click.outside="open = false"
             class="fixed z-[80] w-72 rounded-xl border border-slate-200 bg-white p-3 text-left font-normal normal-case tracking-normal shadow-lg" role="tooltip">
            @if ($note)
                <p class="text-xs text-slate-500">{{ $note }}</p>
            @endif
            <ul class="{{ $note ? 'mt-2' : '' }} space-y-2 text-sm text-slate-700">
                @foreach ($items as [$dot, $word, $range, $meaning])
                    <li class="flex items-start gap-2">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>
                        <span><span class="font-semibold text-slate-900">{{ $word }}</span>@if ($range) <span class="text-slate-500">· {{ $range }}</span>@endif <span class="block text-xs text-slate-500">{{ $meaning }}</span></span>
                    </li>
                @endforeach
            </ul>
        </div>
    </template>
</span>
