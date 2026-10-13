{{-- Needs: $src, $version. Shows the document exactly as the official PDF. --}}
<iframe wire:key="doc-preview-{{ $version }}"
        src="{{ $src }}?v={{ $version }}"
        title="Document preview"
        class="h-[860px] w-full rounded-xl border border-slate-200 bg-slate-100"></iframe>