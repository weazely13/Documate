{{-- Needs: $src, $version.
     The document renders as A4 sheets (595pt wide, so 842px with its gutter) and is scaled to the container.
     The document is always one page (taller content is scaled to fit); the frame posts its height. --}}
<div wire:key="doc-preview-{{ $version }}"
     class="w-full"
     x-data="{
        w: 842, h: 1171, scale: 1,
        fit() {
            const cw = this.$refs.box.clientWidth;
            if (cw > 0) this.scale = Math.min(1, cw / this.w);
        },
        measure(e) {
            if (e.source === this.$refs.frame.contentWindow && e.data && e.data.documatePreviewHeight) {
                this.h = Math.ceil(e.data.documatePreviewHeight);
            }
        }
     }"
     x-init="fit(); new ResizeObserver(() => fit()).observe($refs.box)"
     @message.window="measure($event)">
    <div x-ref="box"
         class="w-full overflow-hidden rounded-xl border border-slate-200 bg-[#e7e5e1]"
         :style="`height:${h * scale}px`">
        <iframe x-ref="frame"
                src="{{ $src }}?v={{ $version }}"
                title="Document preview"
                class="mx-auto block border-0 bg-[#e7e5e1]"
                :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
    </div>
    <a href="{{ $src }}?v={{ $version }}" target="_blank" rel="noopener"
       class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#2A57B4] hover:underline">
        <i class="bx bx-link-external" aria-hidden="true"></i> Open full size
    </a>
</div>
