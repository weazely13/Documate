{{-- One card of My documents in grid view: a large preview of the document, then its title, status and next step.
     Same card shape as Home > Explore. Needs: $workspace, $issuer (label or null), $focus. --}}
@php
    $idStr = (string) $workspace->workspace_id;
    $wf = $workspace->usesWorkflow();

    [
        'title' => $title, 'href' => $href, 'issued' => $issued, 'label' => $label,
        'tone' => $tone, 'step' => $step, 'next' => $next, 'closed' => $closed,
    ] = \App\Support\DocumentProgress::describe($workspace);
    [$thumb, $thumbIsImage] = \App\Support\DocumentProgress::thumbnail($workspace);

    $pill = match ($tone) {
        'you' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'office' => 'bg-[#e2e9f7] text-[#1E4090] ring-sky-200',
        'signing' => 'bg-violet-50 text-violet-800 ring-violet-200',
        'done' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $nextTone = match ($tone) {
        'you' => 'text-amber-800',
        'done' => 'text-emerald-800',
        'closed' => 'text-slate-500',
        default => 'text-slate-600',
    };

    $deletable = ! $wf && ! $workspace->isCompleted();
    $sharedBy = (int) $workspace->user_id !== (int) auth()->id()
        ? ($workspace->field_values['student_name'] ?? 'another student')
        : null;
    if ($sharedBy) {
        $next = null;
        $deletable = false;
    }
@endphp

<li class="group relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px_rgba(13,37,61,0.35)] motion-reduce:hover:translate-y-0">
    {{-- Preview: the whole A4 page, shrunk to the card's width (like Google Docs). Loads once the card is on screen. --}}
    <div class="relative aspect-[842/1171] overflow-hidden border-b border-slate-200 bg-white {{ $closed ? 'opacity-75' : '' }}" aria-hidden="true"
         @if ($thumb && ! $thumbIsImage) x-data="{ s: 0.2, src: '' }" x-init="const fit = () => s = $el.clientWidth / 842; fit(); new ResizeObserver(fit).observe($el)" x-intersect.once.margin.200px="src = @js($thumb)" @endif>
        @if ($thumb && $thumbIsImage)
            <img src="{{ $thumb }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain">
        @elseif ($thumb)
            <iframe :src="src || false" tabindex="-1" title=""
                    class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                    :style="`width: 842px; height: 1171px; transform: scale(${s})`" style="width: 842px; height: 1171px; transform: scale(0.2)"></iframe>
        @else
            <div class="flex h-full items-center justify-center bg-[#f0f4fb] text-[#1E4090]"><i class="bx bx-file text-4xl"></i></div>
        @endif
        <span class="absolute left-2 top-2 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium shadow-sm ring-1 {{ $pill }}">{{ $label }}</span>
    </div>

    <div class="flex flex-1 flex-col p-3 sm:p-3.5">
        <a href="{{ $href }}" wire:navigate
           class="line-clamp-2 rounded text-sm font-semibold leading-snug after:absolute after:inset-0 after:content-[''] group-hover:text-[#2A57B4] {{ $closed ? 'text-slate-600' : 'text-slate-900' }} {{ $focus }}">{{ $title }}</a>
        <p class="mt-0.5 truncate text-xs text-slate-500">
            @if ($wf && $workspace->transaction_code)<span class="font-mono tabular-nums">{{ $workspace->transaction_code }}</span>@else Print form @endif
            @if ($sharedBy) · by {{ $sharedBy }} @elseif ($issued) · {{ $issuer ?? 'VPSD office' }} @endif
        </p>
        @if ($next)
            <p class="mt-1.5 line-clamp-2 text-xs leading-snug {{ $nextTone }}">{{ $next }}</p>
        @endif
        @unless ($closed)
            <span class="mt-auto grid grid-cols-4 gap-1 pt-2.5" role="img" aria-label="Step {{ $step }} of 4">
                @for ($i = 1; $i <= 4; $i++)
                    <span class="h-1 rounded-full {{ $i <= $step ? 'bg-emerald-500' : ($i === $step + 1 && $tone !== 'done' ? ($tone === 'you' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-slate-200') }}"></span>
                @endfor
            </span>
        @endunless
    </div>

    @if ($deletable)
        <div x-data="{ open: false }" class="absolute right-2 top-2 z-10" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="More options for {{ $title }}"
                    class="grid h-8 w-8 place-items-center rounded-full bg-white/95 text-slate-700 shadow ring-1 ring-slate-900/10 hover:text-slate-900 [@media(pointer:coarse)]:h-10 [@media(pointer:coarse)]:w-10 {{ $focus }}">
                <i class="bx bx-dots-horizontal-rounded text-xl" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak x-transition.opacity
                 class="absolute right-0 z-20 mt-1 w-36 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                <a href="{{ $href }}" wire:navigate class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50">Open</a>
                <button type="button" wire:click="confirmDelete('{{ $idStr }}')" @click="open = false"
                        class="block w-full px-4 py-2.5 text-left text-rose-700 hover:bg-rose-50">Delete</button>
            </div>
        </div>
    @endif
</li>
