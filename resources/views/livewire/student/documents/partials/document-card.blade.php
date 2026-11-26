{{-- One row of My documents, in the same shape as a row of the student's Appointments list:
     a preview, the title and its details, then the status pill. The whole row opens the document.
     Needs: $workspace, $issuer (label or null), $focus. --}}
@php
    $wf = $workspace->usesWorkflow();

    [
        'title' => $title, 'href' => $href, 'issued' => $issued, 'state' => $state, 'closed' => $closed,
    ] = \App\Support\DocumentProgress::describe($workspace);

    // Another student's transaction that lists this student: a read-only copy.
    $sharedBy = (int) $workspace->user_id !== (int) auth()->id()
        ? ($workspace->field_values['student_name'] ?? 'another student')
        : null;

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
        <x-status-badge :status="$state" class="mt-2 sm:hidden" />
    </div>

    <div class="relative z-10 hidden shrink-0 items-center gap-2 sm:flex">
        <x-status-badge :status="$state" />
    </div>

    <i class="bx bx-chevron-right shrink-0 self-center text-xl text-slate-400" aria-hidden="true"></i>
</li>
