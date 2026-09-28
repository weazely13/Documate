@php
    $idStr = (string) $workspace->workspace_id;
    $isCompleted = $workspace->status === 'completed';

    $status = $workspace->displayStatus();
    $badgeStyle = match ($status['tone']) {
        'green' => 'bg-emerald-50 text-emerald-700 border-emerald-200/60',
        'amber' => 'bg-amber-50 text-amber-700 border-amber-200/60',
        'red' => 'bg-rose-50 text-rose-700 border-rose-200/60',
        default => 'bg-slate-100 text-slate-600 border-slate-200',
    };
@endphp

<div class="flex flex-col sm:flex-row sm:items-center justify-between rounded-xl border border-slate-200/80 bg-white p-3.5 transition-colors hover:border-[#2A57B4]/40 gap-2 sm:gap-4">
    <div class="flex items-start sm:items-center gap-3 min-w-0 flex-1">
        @unless($isCompleted)
            <input type="checkbox" wire:model.live="selected" value="{{ $idStr }}"
                class="mt-1 sm:mt-0 h-4 w-4 shrink-0 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
        @endunless

        <a href="{{ route('student.documents.show', $workspace->workspace_id) }}" wire:navigate class="min-w-0 flex-1 space-y-1">
            <p class="text-sm font-semibold text-slate-800 truncate">
                {{ $workspace->template?->name ?? 'Untitled document' }}
            </p>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-400">
                <div class="flex items-center gap-1">
                    <i class='bx bx-time text-sm'></i>
                    <span>Updated {{ $workspace->updated_at->diffForHumans() }}</span>
                </div>
            </div>
        </a>
    </div>

    <div class="flex items-center justify-between sm:justify-end gap-2 shrink-0 pt-1 sm:pt-0 border-t sm:border-0 border-slate-100">
        <span class="rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ $badgeStyle }}">
            {{ $status['label'] }}
        </span>

        @unless($isCompleted)
            <button type="button" wire:click="confirmDelete('{{ $idStr }}')" title="Delete"
                class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-white text-rose-500 hover:bg-rose-50">
                <i class='bx bx-trash text-base'></i>
            </button>
        @endunless
    </div>
</div>