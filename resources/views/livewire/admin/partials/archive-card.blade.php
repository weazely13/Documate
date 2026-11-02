{{-- One document in the transaction archive, as a card with a preview of the page.
     Needs: $record, $showStudent (inside a folder: say whose it is), $tagFor, $tintFor, $focus. --}}
@php [$cBg, $cInk] = $tintFor($record['type']); $showDocType = $showDocType ?? false; @endphp
<li wire:key="card-{{ $record['workspace_id'] }}"
    class="group relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-[0_12px_28px_-12px_rgba(13,37,61,0.35)] motion-reduce:hover:translate-y-0">
    {{-- The page itself, shrunk to the card. Loads once the card is on screen. --}}
    <div class="relative aspect-[842/1000] overflow-hidden border-b border-slate-200 bg-white {{ $record['result'] === 'closed' ? 'opacity-70 grayscale-[40%]' : '' }}" aria-hidden="true"
         @if ($record['thumb'] && ! $record['thumb_is_image']) x-data="{ s: 0.2, src: '' }" x-init="const fit = () => s = $el.clientWidth / 842; fit(); new ResizeObserver(fit).observe($el)" x-intersect.once.margin.200px="src = @js($record['thumb'])" @endif>
        @if ($record['thumb'] && $record['thumb_is_image'])
            <img src="{{ $record['thumb'] }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover object-top">
        @elseif ($record['thumb'])
            <iframe :src="src || false" tabindex="-1" title=""
                    class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                    :style="`width: 842px; height: 1171px; transform: scale(${s})`" style="width: 842px; height: 1171px; transform: scale(0.2)"></iframe>
        @else
            <div class="flex h-full items-center justify-center {{ $cBg }}"><i class="bx bx-file text-4xl {{ $cInk }}"></i></div>
        @endif
        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            <span class="rounded-md px-1.5 py-0.5 text-[11px] font-medium shadow-sm {{ $tagFor($record) }}">{{ $record['status'] }}</span>
            @if ($record['deleted_by_student'])<span class="rounded-md bg-rose-100 px-1.5 py-0.5 text-[11px] font-medium text-rose-800 shadow-sm">Deleted by student</span>@endif
        </span>
    </div>

    <div class="flex flex-1 flex-col px-3 pb-2 pt-2.5">
        <a href="{{ $record['url'] }}" wire:navigate
           class="truncate rounded text-sm font-semibold text-[#2A57B4] after:absolute after:inset-0 after:content-[''] hover:text-[#1E4090] {{ $focus }}">
            @if ($showStudent)
                {{ $record['student_name'] }}
            @else
                <span class="{{ $record['paperless'] ? 'font-mono text-[13px]' : '' }}">{{ $record['transaction_label'] }}</span>
            @endif
        </a>
        @if ($showDocType)
            <p class="mt-0.5 flex min-w-0 items-center gap-1 text-xs font-medium {{ $cInk }}"><i class="bx bxs-folder text-sm" aria-hidden="true"></i><span class="truncate">{{ $record['type'] }}</span></p>
        @endif
        @if ($showStudent)
            <p class="mt-0.5 truncate text-xs text-slate-500"><span class="font-mono tabular-nums">{{ $record['transaction_label'] }}</span> · {{ $record['student_number'] }}</p>
        @endif
        <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-600"><i class="bx bx-calendar text-sm text-slate-400" aria-hidden="true"></i><span class="tabular-nums">{{ $record['date_label'] }}</span></p>
        <p class="mt-0.5 flex min-w-0 items-center gap-1.5 text-xs text-slate-600">
            <i class="bx {{ $record['paperless'] ? 'bx-globe' : 'bx-printer' }} text-sm text-slate-400" aria-hidden="true"></i>
            <span class="truncate">{{ $record['paperless'] ? 'Online' : 'Paper' }}@if ($record['issuer']) · {{ $record['issuer'] }}@endif</span>
        </p>
        @if ($record['search_snippet'])
            <p class="mt-1.5 line-clamp-3 text-xs leading-snug text-slate-500"><span class="font-medium text-slate-600">{{ $record['search_snippet_label'] }}:</span> {!! $record['search_snippet'] !!}</p>
        @endif
    </div>
    <div class="relative z-10 grid grid-cols-2 divide-x divide-slate-200 border-t border-slate-200 text-slate-500">
        <a href="{{ $record['url'] }}" wire:navigate aria-label="Open {{ $record['transaction_label'] }}"
           class="flex h-9 items-center justify-center gap-1 text-xs font-medium hover:bg-slate-50 hover:text-[#2A57B4] [@media(pointer:coarse)]:h-11 {{ $focus }}"><i class="bx bx-file text-base" aria-hidden="true"></i> Open</a>
        @if ($record['thumb'])
            <a href="{{ $record['thumb'] }}" target="_blank" rel="noopener" aria-label="Preview {{ $record['transaction_label'] }} in a new tab"
               class="flex h-9 items-center justify-center gap-1 text-xs font-medium hover:bg-slate-50 hover:text-[#2A57B4] [@media(pointer:coarse)]:h-11 {{ $focus }}"><i class="bx bx-show text-base" aria-hidden="true"></i> Preview</a>
        @else
            <span class="flex h-9 items-center justify-center gap-1 text-xs text-slate-300 [@media(pointer:coarse)]:h-11"><i class="bx bx-hide text-base" aria-hidden="true"></i> No preview</span>
        @endif
    </div>
</li>
