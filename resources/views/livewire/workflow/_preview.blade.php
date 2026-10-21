{{-- Needs: $src, $version. Renders the document on a fixed A4-width canvas and scales it to fill the container. --}}
<div wire:key="doc-preview-{{ $version }}"
     class="w-full"
     x-data="{
        w: 840, h: 1188, scale: 1,
        fit() {
            const cw = this.$refs.box.clientWidth;
            if (cw > 0) this.scale = cw / this.w;
        }
     }"
     x-init="fit(); new ResizeObserver(() => fit()).observe($refs.box)">
    <div x-ref="box"
         class="w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
         :style="`height:${h * scale}px`">
        <iframe src="{{ $src }}?v={{ $version }}"
                title="Document preview"
                class="block border-0 bg-slate-100"
                :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
    </div>
    <a href="{{ $src }}?v={{ $version }}" target="_blank" rel="noopener"
       class="mt-2 inline-block text-xs font-semibold text-[#2A57B4] hover:underline sm:hidden">Open full size</a>
</div>