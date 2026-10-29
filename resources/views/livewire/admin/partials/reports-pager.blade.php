{{-- Reports list footer: where you are in the list, and previous / next. Expects $page, $pages, $first, $last, $rowTotal. --}}
@if ($pages > 1)
    <nav class="flex items-center justify-between gap-3 px-4 py-3 text-sm md:px-6" aria-label="Pages">
        <p class="text-slate-500"><span class="font-semibold tabular-nums text-slate-800">{{ $first }}–{{ $last }}</span> of {{ number_format($rowTotal) }}</p>
        <div class="flex items-center gap-1">
            <button type="button" wire:click="goToPage({{ $page - 1 }})" @disabled($page <= 1)
                class="inline-flex h-11 items-center gap-1 rounded-full sm:h-9 border border-slate-200 px-3 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                <i class="bx bx-chevron-left text-lg" aria-hidden="true"></i><span class="hidden sm:inline">Previous</span>
            </button>
            <span class="px-2 tabular-nums text-slate-500">{{ $page }} / {{ $pages }}</span>
            <button type="button" wire:click="goToPage({{ $page + 1 }})" @disabled($page >= $pages)
                class="inline-flex h-11 items-center gap-1 rounded-full sm:h-9 border border-slate-200 px-3 font-semibold text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                <span class="hidden sm:inline">Next</span><i class="bx bx-chevron-right text-lg" aria-hidden="true"></i>
            </button>
        </div>
    </nav>
@endif
