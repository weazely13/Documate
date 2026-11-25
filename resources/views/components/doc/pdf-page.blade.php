{{-- The printed page itself, as the place to fill out (Alpine "pdfFill"). Two frames take turns so redrawing never flashes.
     Must sit inside the pdfFill element. wire:ignore: Livewire must not reset the frames. --}}
@props(['label' => 'The form'])
<div wire:ignore {{ $attributes->merge(['class' => 'relative']) }}>
    <div data-page-box class="relative overflow-hidden rounded-xl bg-[#e7e5e1]" :style="`height:${ready ? h * scale : 420}px`">
        <div x-show="! ready" class="absolute inset-0 flex items-center justify-center gap-2 text-sm text-slate-600" role="status">
            <i class="bx bx-loader-alt bx-spin text-xl" aria-hidden="true"></i> Opening the form…
        </div>
        @foreach ([0, 1] as $i)
            <iframe data-frame="{{ $i }}" title="{{ $label }}"
                    class="absolute top-0 border-0 bg-[#e7e5e1] transition-opacity duration-150"
                    :class="front === {{ $i }} && ready ? 'z-10 opacity-100' : 'z-0 opacity-0 pointer-events-none'"
                    :tabindex="front === {{ $i }} ? 0 : -1"
                    :style="`left:${offset}px;width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
        @endforeach
    </div>
    <span x-show="busy && ready" x-cloak class="absolute right-2 top-2 z-20 inline-flex items-center gap-1 rounded-full bg-white/90 px-2 py-0.5 text-[11px] text-slate-500 shadow-sm" role="status">
        <i class="bx bx-loader-alt bx-spin" aria-hidden="true"></i> Updating
    </span>
</div>
