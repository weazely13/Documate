@php
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $btnBase = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btnBase . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btnBase . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $segS = fn ($on) => 'inline-flex min-h-8 items-center gap-1.5 rounded-md px-2 text-sm transition-colors sm:px-3 [@media(pointer:coarse)]:min-h-10 ' . $focus . ' '
        . ($on ? 'bg-white font-medium text-[#24499A] shadow-sm' : 'text-slate-600 hover:text-slate-900');
    $short = ['active' => 'Active', 'issued' => 'Issued', 'history' => 'History'];
@endphp

{{-- My documents, laid out like the student's Appointments: header, a one-line toolbar card, then one list card. --}}
<div class="mx-auto max-w-6xl space-y-5 pb-28">

    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">My documents</h1>
            <p class="mt-0.5 text-sm text-slate-500">Everything you have started, and anything the office sent you.</p>
        </div>
        <a href="{{ route('student.home') }}" wire:navigate class="{{ $btnPrimary }} shrink-0">
            <i class="bx bx-plus text-base" aria-hidden="true"></i> New transaction
        </a>
    </div>

    {{-- Toolbar: tabs and search on one line at every width --}}
    <div class="relative flex items-center gap-1.5 rounded-2xl border border-slate-200 bg-white p-2 sm:gap-2 sm:p-3">
        <nav class="inline-flex shrink-0 rounded-lg bg-slate-100 p-1" aria-label="Documents">
            @foreach ($tabs as $key => $label)
                @php $on = $tab === $key; $count = $this->counts[$key] ?? 0; @endphp
                <button type="button" wire:click="$set('tab', '{{ $key }}')" wire:key="tab-{{ $key }}" @if ($on) aria-current="page" @endif class="{{ $segS($on) }}">
                    <span class="sm:hidden">{{ $short[$key] }}</span><span class="hidden sm:inline">{{ $label }}</span>
                    <span class="rounded-full px-1.5 text-[11px] tabular-nums {{ $on ? 'bg-[#e2e9f7] text-[#1E4090]' : ($key === 'issued' && $count ? 'bg-amber-100 text-amber-800' : 'bg-white/80 text-slate-600') }}">{{ $count }}</span>
                </button>
            @endforeach
        </nav>
        <x-dm.toolbar-search class="ml-auto sm:max-w-xs" placeholder="Search your documents" label="Search your documents" />
        {{-- Phones: one button that flips the layout; wider: both choices --}}
        <button type="button" wire:click="setView('{{ $view === 'grid' ? 'list' : 'grid' }}')"
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 hover:bg-slate-50 sm:hidden [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}"
                aria-label="{{ $view === 'grid' ? 'Show as a list' : 'Show as a grid' }}">
            <i class="bx {{ $view === 'grid' ? 'bx-list-ul' : 'bx-grid-alt' }} text-lg" aria-hidden="true"></i>
        </button>
        <div class="hidden shrink-0 rounded-lg bg-slate-100 p-1 sm:inline-flex" role="group" aria-label="Layout">
            @foreach (['list' => ['bx-list-ul', 'List'], 'grid' => ['bx-grid-alt', 'Grid']] as $v => [$icon, $vLabel])
                <button type="button" wire:click="setView('{{ $v }}')" aria-pressed="{{ $view === $v ? 'true' : 'false' }}" title="{{ $vLabel }} view" class="{{ $segS($view === $v) }}">
                    <i class="bx {{ $icon }} text-base" aria-hidden="true"></i><span class="max-md:sr-only">{{ $vLabel }}</span>
                </button>
            @endforeach
        </div>
    </div>

    @if ($view === 'grid')
        {{-- Grid: a card per document with its full A4 page, two across on phones and tablets, four on desktop --}}
        @if ($workspaces->isNotEmpty())
            <ul class="grid min-h-[22rem] grid-cols-2 content-start gap-3 sm:gap-4 lg:grid-cols-4" aria-label="{{ $tabs[$tab] }}"
                wire:loading.class="opacity-60" wire:target="tab, search">
                @foreach ($workspaces as $workspace)
                    @include('livewire.student.documents.partials.document-tile', [
                        'workspace' => $workspace,
                        'issuer' => \App\Support\Issuers::label($issuers[$workspace->workspace_id] ?? null),
                    ])
                @endforeach
            </ul>
        @else
            <p class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center text-sm text-slate-500">
                {{ trim($search) !== '' ? 'No documents match "' . $search . '". Try another word.' : 'Nothing here yet.' }}
            </p>
        @endif
    @else
    {{-- List: one row per document, the most recent first --}}
    <ul class="min-h-[22rem] divide-y divide-slate-100 overflow-hidden rounded-2xl border border-slate-200 bg-white" aria-label="{{ $tabs[$tab] }}"
        wire:loading.class="opacity-60" wire:target="tab, search">
        @forelse ($workspaces as $workspace)
            @include('livewire.student.documents.partials.document-card', [
                'workspace' => $workspace,
                'issuer' => \App\Support\Issuers::label($issuers[$workspace->workspace_id] ?? null),
            ])
        @empty
            <li class="px-6 py-14 text-center text-sm text-slate-500">
                <i class="bx {{ trim($search) !== '' ? 'bx-search-alt' : 'bx-folder' }} mx-auto mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                @if (trim($search) !== '')
                    No documents match "{{ $search }}". Try another word.
                @elseif ($tab === 'active')
                    Nothing in progress.
                    <a href="{{ route('student.home') }}" wire:navigate class="ml-1 font-medium text-[#2A57B4] hover:underline">Start a new transaction</a>
                @elseif ($tab === 'issued')
                    Nothing issued to you. Slips, advisements and other documents the VPSD office or a signatory issues to you show up here.
                @else
                    No finished documents yet. Completed, rejected and voided documents are kept here.
                @endif
            </li>
        @endforelse
    </ul>

    @endif

    {{-- Delete (print forms only) --}}
    @if ($deletingId)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="cancelDelete" wire:click.self="cancelDelete"
             role="alertdialog" aria-modal="true" aria-labelledby="del-title" aria-describedby="del-lead">
            <div class="w-full max-w-sm rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6" x-data x-init="$nextTick(() => $refs.keep.focus())">
                <h2 id="del-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">Delete this document?</h2>
                <p id="del-lead" class="mt-1 text-sm text-slate-500">This can't be undone. Any booked appointment for it is cancelled.</p>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" x-ref="keep" wire:click="cancelDelete" class="{{ $btnSecondary }}">Keep it</button>
                    <button type="button" wire:click="deleteWorkspace" wire:loading.attr="disabled" class="{{ $btnBase }} bg-rose-600 text-white shadow-sm hover:bg-rose-700">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
