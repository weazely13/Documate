{{-- One row of My documents, in the same shape as a row of the student's Appointments list:
     a date tile, the title and its details, the next step, then the status pill and the row's menu.
     Needs: $workspace, $issuer (label or null), $focus. --}}
@php
    $idStr = (string) $workspace->workspace_id;
    $wf = $workspace->usesWorkflow();

    [
        'title' => $title, 'href' => $href, 'issued' => $issued, 'label' => $label,
        'tone' => $tone, 'step' => $step, 'next' => $next, 'closed' => $closed,
    ] = \App\Support\DocumentProgress::describe($workspace);

    // Status as a pill (DESIGN.md: tint + deep text + 200 ring), the same pills as Appointments.
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
        default => 'text-[#1E4090]',
    };

    $deletable = ! $wf && ! $workspace->isCompleted();

    // Another student's transaction that lists this student: a read-only copy.
    $sharedBy = (int) $workspace->user_id !== (int) auth()->id()
        ? ($workspace->field_values['student_name'] ?? 'another student')
        : null;
    if ($sharedBy) {
        $next = null;
        $deletable = false;
    }

    [$thumb, $thumbIsImage] = \App\Support\DocumentProgress::thumbnail($workspace);

    $when = $workspace->updated_at;
    $whenLabel = $when?->isToday() ? 'Today' : ($when?->isYesterday() ? 'Yesterday' : $when?->format('M j, Y'));
@endphp

<li class="relative flex items-start gap-3 p-3 transition-colors hover:bg-slate-50 sm:items-center sm:gap-4 sm:p-4">
    {{-- Preview: the document itself, shrunk. Loads once the row is on screen. --}}
    <span class="relative h-[4.5rem] w-14 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-[#e7e5e1] shadow-sm {{ $closed ? 'opacity-70' : '' }}" aria-hidden="true"
          @if ($thumb && ! $thumbIsImage) x-data="{ src: '' }" x-intersect.once.margin.200px="src = @js($thumb)" @endif>
        @if ($thumb && $thumbIsImage)
            <img src="{{ $thumb }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-cover object-top">
        @elseif ($thumb)
            <iframe :src="src || false" tabindex="-1" title="" loading="lazy"
                    class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                    style="width: 842px; height: 1171px; transform: scale({{ 56 / 842 }})"></iframe>
        @else
            <span class="grid h-full w-full place-items-center bg-[#f0f4fb] text-[#1E4090]"><i class="bx bx-file text-2xl"></i></span>
        @endif
    </span>

    <div class="min-w-0 flex-1">
        <a href="{{ $href }}" wire:navigate
           class="block rounded font-medium after:absolute after:inset-0 after:content-[''] hover:text-[#2A57B4] {{ $closed ? 'text-slate-600' : 'text-slate-900' }} {{ $focus }}">
            {{ $title }}
        </a>
        <p class="mt-0.5 text-xs text-slate-500">
            <span class="sr-only">Last updated </span>{{ $whenLabel }}
            @if ($wf && $workspace->transaction_code) · <span class="font-mono tabular-nums">{{ $workspace->transaction_code }}</span>@endif
            @if (! $wf) · Print form @endif
            @if ($sharedBy) · Lists you, filed by <span class="font-medium text-slate-700">{{ $sharedBy }}</span>
            @elseif ($issued) · Issued by <span class="font-medium text-slate-700">{{ $issuer ?? 'the VPSD office' }}</span>
            @endif
        </p>
        @if ($next)
            <p class="mt-1 flex items-start gap-1.5 text-xs {{ $nextTone }}">
                @unless ($closed)
                    <span class="mt-[5px] h-1.5 w-1.5 shrink-0 rounded-full bg-current {{ $tone === 'you' ? 'animate-pulse motion-reduce:animate-none' : '' }}" aria-hidden="true"></span>
                @endunless
                <span>{{ $next }}</span>
            </p>
        @endif
        @unless ($closed)
            <span class="mt-2 grid max-w-[9rem] grid-cols-4 gap-1" role="img" aria-label="Step {{ $step }} of 4">
                @for ($i = 1; $i <= 4; $i++)
                    <span class="h-1 rounded-full {{ $i <= $step ? 'bg-emerald-500' : ($i === $step + 1 && $tone !== 'done' ? ($tone === 'you' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-slate-200') }}"></span>
                @endfor
            </span>
        @endunless
        <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 sm:hidden {{ $pill }}">{{ $label }}</span>
    </div>

    <div class="relative z-10 hidden shrink-0 items-center gap-2 sm:flex">
        <span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $pill }}">{{ $label }}</span>
    </div>

    @if ($deletable)
        <div x-data="{ open: false }" class="relative z-10 shrink-0 self-center" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" @click="open = !open" :aria-expanded="open" aria-label="More options for {{ $title }}"
                    class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-800 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
                <i class="bx bx-dots-horizontal-rounded text-xl" aria-hidden="true"></i>
            </button>
            <div x-show="open" x-cloak x-transition.opacity
                 class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg">
                <a href="{{ $href }}" wire:navigate class="block px-4 py-2.5 text-slate-700 hover:bg-slate-50">Open</a>
                <button type="button" wire:click="confirmDelete('{{ $idStr }}')" @click="open = false"
                        class="block w-full px-4 py-2.5 text-left text-rose-700 hover:bg-rose-50">Delete</button>
            </div>
        </div>
    @else
        <i class="bx bx-chevron-right shrink-0 self-center text-xl text-slate-400" aria-hidden="true"></i>
    @endif
</li>
