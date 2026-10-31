{{-- Full-screen preview of a page that renders as A4 sheets (the document, or its audit trail). The frame only loads once opened.
     Needs: $src, $version, $title. Optional: $event (window event that opens it, default "open-doc-preview"),
     $note (line under the title), $download (URL for a download button). --}}
@php
    $event ??= 'open-doc-preview';
    $note ??= 'A preview of the layout. The downloaded PDF is the official copy.';
    $titleId = 'pm-' . \Illuminate\Support\Str::slug($event) . '-title';
    $iconBtn = 'grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11';
@endphp
<div x-data="{ open: false, opener: null }" x-effect="if (open) window.documatePreviewOpen = true"
     x-on:{{ $event }}.window="opener = document.activeElement; open = true; $nextTick(() => $refs.close?.focus())">
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity
             class="fixed inset-0 z-[90] flex flex-col bg-slate-900/50 sm:p-6"
             role="dialog" aria-modal="true" aria-labelledby="{{ $titleId }}"
             @keydown.escape.window="if (open) { $event.stopImmediatePropagation(); open = false; window.documatePreviewOpen = false; opener?.focus(); }">
            <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl"
                 @click.outside="open = false; window.documatePreviewOpen = false">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <h2 id="{{ $titleId }}" class="truncate text-sm font-semibold text-slate-900">{{ $title }}</h2>
                        <p class="text-xs text-slate-500">{{ $note }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        @if (! empty($download))
                            <a href="{{ $download }}" class="{{ $iconBtn }}" aria-label="Download as PDF" title="Download as PDF">
                                <i class="bx bx-download text-lg" aria-hidden="true"></i>
                            </a>
                        @endif
                        <a href="{{ $src }}?v={{ $version }}" target="_blank" rel="noopener" class="{{ $iconBtn }}"
                           aria-label="Open the preview in a new tab" title="Open in a new tab">
                            <i class="bx bx-link-external text-lg" aria-hidden="true"></i>
                        </a>
                        <button type="button" x-ref="close" @click="open = false; window.documatePreviewOpen = false; opener?.focus()" class="{{ $iconBtn }}"
                                aria-label="Close preview">
                            <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto bg-[#e7e5e1] p-3 sm:p-5">
                    <template x-if="open">
                        <div>
                            @include('livewire.workflow._preview', ['src' => $src, 'version' => $version])
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
