{{-- Needs: $src, $version. Renders the document on a fixed A4-width canvas and scales it to fit the screen. --}}
<div wire:key="doc-preview-{{ $version }}"
     x-data="{
        w: 840, h: 1188, scale: 1,
        fit() { this.scale = Math.min(1, this.$el.clientWidth / this.w); }
     }"
     x-init="fit()"
     @resize.window.debounce.100ms="fit()">
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
         :style="`height:${h * scale}px`">
        <iframe src="{{ $src }}?v={{ $version }}"
                title="Document preview"
                class="block border-0 bg-slate-100"
                :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
    </div>
    <a href="{{ $src }}?v={{ $version }}" target="_blank" rel="noopener"
       class="mt-2 inline-block text-xs font-semibold text-[#2A57B4] hover:underline sm:hidden">Open full size</a>
</div>