<div wire:poll.2s>
    {{-- Tab handle — always visible, shows live count badge --}}
    <div class="flex w-full items-stretch rounded-t-2xl border border-b-0 border-slate-200 bg-white shadow-lg overflow-hidden">
        <button type="button" wire:click="toggle"
            class="flex flex-1 items-center justify-between gap-3 px-4 py-2.5 transition hover:bg-slate-50">
            <span class="flex items-center gap-2 text-xs font-semibold text-slate-700">
                <i class='bx bx-scan text-base text-[#2A57B4]'></i>
                Document Scans
                @if($this->activeCount > 0)
                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-[#2A57B4]">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#2A57B4]"></span>
                        {{ $this->activeCount }} active
                    </span>
                @endif
                @if($hasError)
                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-600">
                        <i class='bx bx-error'></i> Sync issue
                    </span>
                @endif
            </span>
            <i class='bx {{ $expanded ? "bx-chevron-down" : "bx-chevron-up" }} text-lg text-slate-500'></i>
        </button>
        <button type="button" wire:click="refreshNow" title="Refresh now"
            class="flex items-center justify-center border-l border-slate-100 px-3 text-slate-400 transition hover:bg-slate-50 hover:text-[#2A57B4]">
            <i class='bx bx-refresh text-base' wire:loading.class="animate-spin" wire:target="refreshNow"></i>
        </button>
    </div>

    {{-- Expandable panel --}}
    @if($expanded)
        <div class="max-h-[60vh] w-full overflow-y-auto rounded-b-none border border-t-0 border-slate-200 bg-white shadow-xl sm:max-h-96 sm:w-96">
            @if($hasError)
                <div class="flex items-center gap-2 border-b border-rose-100 bg-rose-50 px-4 py-2.5 text-xs text-rose-700">
                    <i class='bx bx-error-circle'></i>
                    Couldn't load the latest status. Tap the refresh icon above to retry.
                </div>
            @endif
            @forelse($this->events as $event)
                @php
                    $inProgress = in_array($event->status, ['uploading', 'queued', 'scanning']);
                    $stale = $inProgress && $event->workspace && ! $event->workspace->processing;
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
                <a href="{{ route('admin.document-uploads.show', $event->workspace_id) }}" wire:navigate
                    class="flex items-start gap-3 border-b border-slate-100 px-4 py-3 transition hover:bg-slate-50">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full ring-1 {{ $ring }}">
                        <i class='bx {{ $icon }} text-sm'></i>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-xs font-semibold text-slate-800">
                            {{ $event->workspace?->user?->first_name }} {{ $event->workspace?->user?->last_name }}
                            <span class="font-normal text-slate-400">— {{ $event->workspace?->template?->name ?: 'Document' }}</span>
                        </p>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ $event->statusLabel() }}</p>
                        <p class="mt-0.5 text-[10px] text-slate-400">{{ $event->created_at->diffForHumans() }}</p>
                    </div>
                </a>
            @empty
                <p class="px-4 py-8 text-center text-xs text-slate-400">No scans yet.</p>
            @endforelse
        </div>
    @endif
</div>