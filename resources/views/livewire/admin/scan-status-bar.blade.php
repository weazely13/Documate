{{-- Document scans, in the top bar beside the notifications: an icon with a live badge, and the
     latest scan of each upload in a dropdown. A new upload ("scan-queued") opens it. --}}
<div x-data="{ open: $wire.entangle('expanded').live }" class="relative" wire:poll.2s @keydown.escape.window="open = false">
    <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open" aria-haspopup="dialog"
        aria-label="Document scans{{ $this->activeCount ? ', ' . $this->activeCount . ' in progress' : '' }}" data-tooltip="Document Scans"
        class="relative rounded-full p-2 transition hover:bg-slate-100" :class="open && 'bg-blue-50'">
        <i class='bx bx-scan text-xl text-slate-600' :class="open && '!text-[#2A57B4]'"></i>
        @if ($hasError)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-bold text-white ring-2 ring-white" aria-hidden="true">!</span>
        @elseif ($this->activeCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[#2A57B4] px-1 text-[10px] font-bold text-white ring-2 ring-white" aria-hidden="true">
                {{ $this->activeCount > 9 ? '9+' : $this->activeCount }}
            </span>
            <span class="absolute -right-0.5 -top-0.5 h-4 w-4 animate-ping rounded-full bg-[#2A57B4]/40" aria-hidden="true"></span>
        @endif
    </button>

    {{-- Below sm the panel spans the screen under the top bar; from sm up it hangs from the icon --}}
    <div x-show="open" x-cloak @click.outside="open = false" role="dialog" aria-label="Document scans"
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-x-3 top-[4.25rem] z-50 flex max-h-[calc(100dvh-5.5rem)] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/10 sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-96 sm:max-h-[min(32rem,calc(100dvh-5.5rem))]">
        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
            <div class="min-w-0">
                <p class="text-base font-semibold text-slate-900">Document scans</p>
                <p class="text-xs text-slate-500">
                    @if ($this->activeCount > 0)
                        <span class="inline-flex items-center gap-1 font-semibold text-[#2A57B4]"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#2A57B4]"></span>{{ $this->activeCount }} in progress</span> · updates live
                    @else
                        AI checks of uploaded photos · updates live
                    @endif
                </p>
            </div>
            <button type="button" wire:click="refreshNow" title="Refresh now" aria-label="Refresh now"
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#2A57B4]">
                <i class='bx bx-refresh text-lg' wire:loading.class="animate-spin" wire:target="refreshNow"></i>
            </button>
        </div>

        <div class="thin-scroll min-h-0 flex-1 overflow-y-auto">
            @if ($hasError)
                <div class="flex items-center gap-2 border-b border-rose-100 bg-rose-50 px-4 py-2.5 text-xs text-rose-700">
                    <i class='bx bx-error-circle'></i>
                    Couldn't load the latest status. Press refresh to try again.
                </div>
            @endif
            @if ($expanded)
                @forelse ($this->events as $event)
                    @php
                        $tone = $event->tone();
                        $ring = match ($tone) {
                            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
                            'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                            'sky' => 'bg-sky-50 text-sky-700 ring-sky-200',
                            default => 'bg-amber-50 text-amber-700 ring-amber-200',
                        };
                        $icon = match ($event->status) {
                            'approved' => 'bx-check-circle',
                            'rejected', 'failed' => 'bx-error-circle',
                            'scanning' => 'bx-loader-alt animate-spin',
                            'uploading' => 'bx-cloud-upload animate-pulse',
                            default => 'bx-time-five',
                        };
                    @endphp
                    <a href="{{ route('admin.document-uploads.show', $event->workspace_id) }}" wire:navigate @click="open = false"
                        class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                        <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full ring-1 {{ $ring }}">
                            <i class='bx {{ $icon }} text-sm'></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">
                                {{ $event->workspace?->user?->full_name }}
                                <span class="font-normal text-slate-400">— {{ $event->workspace?->template?->name ?: 'Document' }}</span>
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ $event->statusLabel() }}</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ $event->created_at->diffForHumans() }}</p>
                        </div>
                    </a>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-slate-400">No scans yet.</p>
                @endforelse
            @else
                <p class="px-4 py-10 text-center text-sm text-slate-400"><i class="bx bx-loader-alt animate-spin" aria-hidden="true"></i> Loading…</p>
            @endif
        </div>

        <a href="{{ route('admin.document-uploads.index') }}" wire:navigate @click="open = false"
            class="shrink-0 border-t border-slate-100 px-4 py-2.5 text-center text-sm font-semibold text-[#2A57B4] transition hover:bg-slate-50">
            Open Document Uploads
        </a>
    </div>
</div>
