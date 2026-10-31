@php
    $idStr = (string) $workspace->workspace_id;
    $wf = $workspace->usesWorkflow();
    $tones = \App\Support\DocumentProgress::TONES;

    [
        'title' => $title, 'href' => $href, 'issued' => $issued, 'label' => $label,
        'tone' => $tone, 'step' => $step, 'next' => $next, 'closed' => $closed,
    ] = \App\Support\DocumentProgress::describe($workspace);

    $deletable = ! $wf && ! $workspace->isCompleted();

    // Another student's transaction that lists this student: a read-only copy.
    $sharedBy = (int) $workspace->user_id !== (int) auth()->id()
        ? ($workspace->field_values['student_name'] ?? 'another student')
        : null;
    if ($sharedBy) {
        $next = null;
        $deletable = false;
    }
@endphp

<div class="flex items-start gap-3 rounded-2xl border border-slate-200/70 bg-white p-4 transition hover:border-[#2A57B4]/30 sm:p-5">
    <a href="{{ $href }}" wire:navigate class="min-w-0 flex-1 space-y-2.5">
        <div class="flex flex-wrap items-center gap-2">
            <p class="break-words text-[15px] font-semibold {{ $closed ? 'text-slate-500' : 'text-slate-900' }}">{{ $title }}</p>
            @if (! $wf)
                <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800">Print</span>
            @endif
        </div>
        @if ($sharedBy)
            <p class="text-xs text-slate-500">Lists you &middot; filed by <span class="font-medium text-slate-700">{{ $sharedBy }}</span></p>
        @elseif ($issued)
            <p class="text-xs text-slate-500">Issued by <span class="font-medium text-slate-700">{{ $issuer ?? 'the VPSD office' }}</span></p>
        @endif

        {{-- Phones: status under the title, so the title keeps the full width --}}
        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold sm:hidden {{ $tones[$tone] }}">{{ $label }}</span>

        @unless ($closed)
            <div class="grid max-w-xs grid-cols-4 gap-1" aria-label="Step {{ $step }} of 4">
                @for ($i = 1; $i <= 4; $i++)
                    <span class="h-1.5 rounded-full {{ $i <= $step ? 'bg-emerald-500' : ($i === $step + 1 && $tone !== 'done' ? ($tone === 'you' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-slate-200') }}"></span>
                @endfor
            </div>
        @endunless

        @if ($next)
            <p class="text-sm leading-snug {{ $tone === 'you' ? 'text-amber-800' : 'text-slate-600' }}">{{ $next }}</p>
        @endif
    </a>

    <div class="-mr-2 -mt-1.5 flex shrink-0 items-center gap-1 sm:mr-0 sm:mt-0">
        <span class="hidden whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-semibold sm:inline-flex {{ $tones[$tone] }}">{{ $label }}</span>

        <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="More options for {{ $title }}"
                    class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition.opacity
                 class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                <a href="{{ $href }}" wire:navigate class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50">Open</a>
                @if ($wf && $workspace->transaction_code)
                    <p class="px-4 py-2 font-mono text-[11px] text-slate-400">{{ $workspace->transaction_code }}</p>
                @endif
                @if ($deletable)
                    <button type="button" wire:click="confirmDelete('{{ $idStr }}')" @click="open = false"
                            class="block w-full px-4 py-2.5 text-left text-rose-600 hover:bg-rose-50">Delete</button>
                @endif
            </div>
        </div>
    </div>
</div>
