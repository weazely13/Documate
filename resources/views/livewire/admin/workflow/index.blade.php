@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $field = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';

    // DESIGN.md buttons: 8px, 36px tall (44px on touch), 14px/500 sentence case.
    $btnBase = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btnBase . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btnBase . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $btnDanger = $btnBase . ' border border-rose-200 bg-white text-rose-700 hover:bg-rose-50';

    // One plain-language status per row: [text, text classes, icon + its colour].
    $statusFor = function ($row, $queue) {
        return match (true) {
            $queue === 'action_required' => ['Waiting on student', 'text-slate-700', 'bx-hourglass text-amber-600'],
            $queue === 'signing' => ['Signed ' . (int) $row->approved_count . ' of ' . (int) $row->approvals_count, 'text-slate-700', 'bx-pen text-violet-600'],
            $row->workflow_status?->value === 'approved' => ['Ready to finish', 'font-medium text-emerald-700', 'bx-check-circle text-emerald-600'],
            $row->workflow_status?->value === 'submitted' => ['New', 'text-slate-700', 'bx-time-five text-[#2A57B4]'],
            default => ['Under review', 'text-slate-700', 'bx-time-five text-[#2A57B4]'],
        };
    };

    // Where the transaction is on its way (phone cards): [current step, labels, current tone: ring + text].
    $stageFor = function ($row) use ($tab) {
        return match (true) {
            $tab === 'action_required' => [0, ['With student', 'Signing', 'Finish'], ['ring-amber-500', 'text-amber-800']],
            $tab === 'signing' => [1, ['Review', 'Signed ' . (int) $row->approved_count . '/' . (int) $row->approvals_count, 'Finish'], ['ring-violet-600', 'text-violet-800']],
            $row->workflow_status?->value === 'approved' => [2, ['Review', 'Signing', 'Ready to finish'], ['ring-emerald-600', 'text-emerald-700']],
            default => [0, ['Review', 'Signing', 'Finish'], ['ring-[#2A57B4]', 'text-[#1E4090]']],
        };
    };

    // The AI check as a compact pill: [text, classes, icon] or null when it does not apply.
    $aiFor = function ($row) {
        if (! ($row->ai_applies ?? true)) {
            return null;
        }
        $rec = $row->ai_findings['recommendation'] ?? null;

        return match (true) {
            $row->ai_flag === 'pending' => ['Checking', 'bg-sky-50 text-[#1E4090] ring-sky-200', 'bx-loader-alt bx-spin'],
            $row->ai_flag === 'review_failed' => ['Check failed', 'bg-[#fdece3] text-[#b4471a] ring-orange-200', 'bx-error-circle'],
            $row->ai_flag === null => ['Not checked', 'bg-slate-100 text-slate-600 ring-slate-200', null],
            $rec === 'reject' => ['Suggests reject', 'bg-rose-50 text-rose-800 ring-rose-200', null],
            $rec === 'request_changes' => ['Suggests changes', 'bg-amber-50 text-amber-800 ring-amber-200', null],
            $row->ai_flag === 'flagged' => ['Worth a look', 'bg-amber-50 text-amber-800 ring-amber-200', null],
            default => ['Looks fine', 'bg-emerald-50 text-emerald-800 ring-emerald-200', null],
        };
    };

    $nameFor = fn ($row) => $row->field_values['student_name'] ?? (($row->user?->full_name ?? '') ?: '-');

    $initialsFor = function ($row) use ($nameFor) {
        $parts = preg_split('/\s+/', trim($nameFor($row))) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last) ?: '?';
    };

    // The student's photo, laid over their initials; if it is missing or fails to load, the initials show.
    $avatarFor = fn ($row) => $row->user?->profile_picture ? asset('storage/' . $row->user->profile_picture) : null;

    $metaFor = fn ($row) => collect([$row->transaction_code, $row->user?->student_number])->filter()->implode(' · ');

    // Priority lives in the Waiting cell alone: one calm type step per level, a soft chip once overdue.
    $waitStyle = fn ($w) => match ($w['level']) {
        'overdue' => '-ml-2 rounded-md bg-red-50 px-2 py-0.5 font-medium text-red-800',
        'urgent' => 'font-medium text-red-700',
        'follow_up' => 'font-medium text-orange-700',
        'due' => 'text-slate-700',
        default => 'text-slate-500',
    };

    // Queue cards: Notion tints from DESIGN.md's work folders. [key, label, tint, accent text, icon, hint].
    $cards = [
        ['attention', 'To review', 'bg-sky-50', 'text-[#1E4090]', 'bx-time-five', 'Waiting for the office'],
        ['signing', 'In signing', 'bg-violet-50', 'text-violet-700', 'bx-pen', 'Out with signatories'],
        ['action_required', 'Waiting on student', 'bg-amber-50', 'text-amber-800', 'bx-hourglass', 'Sent back for changes'],
    ];

    $legend = [
        ['bg-slate-300', 'Under 1'],
        ['bg-amber-300', '1–2'],
        ['bg-orange-500', '3–4 Follow up'],
        ['bg-red-600', '5–6 Urgent'],
        ['bg-red-900', '7+ Overdue'],
    ];

    $pageIds = $rows->getCollection()->map(fn ($r) => (string) $r->workspace_id)->values();
    $viewingFlagged = $tab === 'attention' && $ai === 'flagged';
@endphp

<div class="mx-auto max-w-[1440px] space-y-5" @if ($polling) wire:poll.5s @endif>

    {{-- Header: the queue you are looking at, search, and the one primary action --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">
                {{ $viewingFlagged ? 'Flagged by AI' : $tabs[$tab] }}
            </h1>
            <p class="mt-0.5 text-sm text-slate-500">
                Select a transaction to check it and decide here. Finished ones are in
                <a href="{{ route('admin.transactions.index') }}" wire:navigate class="rounded font-medium text-[#2A57B4] hover:underline {{ $focus }}">Transaction Records</a>.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <div class="relative min-w-0 flex-1 md:w-72 md:flex-none">
                <i class="bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400" aria-hidden="true"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, code or document"
                       aria-label="Search by name, student number, code or document type"
                       class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $field }}">
            </div>
            <a href="{{ route('admin.workflow.issue') }}" wire:navigate class="{{ $btnPrimary }} shrink-0">
                <i class="bx bx-plus text-base" aria-hidden="true"></i>
                <span class="sm:hidden">Issue</span>
                <span class="hidden sm:inline">Issue a document</span>
            </a>
        </div>
    </div>

    {{-- Queue cards: count, what it is, who has waited longest --}}
    <nav class="grid grid-cols-2 gap-3 lg:grid-cols-4" aria-label="Queues">
        @foreach ($cards as [$key, $label, $tint, $accent, $icon, $hint])
            @php
                $current = $tab === $key && ! $viewingFlagged;
                $late = $lateCounts[$key] ?? 0;
                $oldest = $faces[$key]->first();
            @endphp
            <button type="button" wire:click="openQueue('{{ $key }}')" wire:key="card-{{ $key }}"
                    @if ($current) aria-current="page" @endif
                    class="flex min-h-[104px] flex-col rounded-2xl border bg-white p-3.5 text-left sm:min-h-[132px] sm:p-4 transition-colors {{ $focus }}
                        {{ $current ? 'border-[#2A57B4] shadow-[0_0_0_1px_#2A57B4]' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60' }}">
                <span class="flex items-start justify-between gap-2">
                    <span class="grid h-8 min-w-8 place-items-center rounded-lg px-1.5 text-sm font-semibold tabular-nums {{ $tint }} {{ $accent }}">{{ $counts[$key] }}</span>
                    <i class="bx {{ $icon }} text-xl text-slate-400" aria-hidden="true"></i>
                </span>
                <span class="mt-2.5 block text-sm font-semibold leading-tight text-slate-900 sm:mt-3 sm:text-[15px]">{{ $label }}</span>
                <span class="mt-0.5 block truncate text-xs text-slate-500">
                    @if ($late)
                        <span class="font-medium text-red-700">{{ $late }} overdue</span> ·
                    @endif
                    {{ $oldest?->submitted_at ? 'Oldest ' . $oldest->submitted_at->format('M j, Y') : $hint }}
                </span>
                <span class="mt-auto hidden -space-x-1.5 pt-3 sm:flex" aria-hidden="true">
                    @foreach ($faces[$key] as $f)
                        <span class="relative grid h-6 w-6 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[10px] font-semibold text-[#1E4090] ring-2 ring-white">
                            {{ $initialsFor($f) }}
                            @if ($avatarFor($f))
                                <img src="{{ $avatarFor($f) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                            @endif
                        </span>
                    @endforeach
                </span>
            </button>
        @endforeach

        {{-- The AI's shortlist: the review queue, narrowed to what it wants a person to look at --}}
        <button type="button" wire:click="showFlagged" wire:key="card-flagged"
                @if ($viewingFlagged) aria-current="page" @endif
                class="flex min-h-[104px] flex-col rounded-2xl border bg-white p-3.5 text-left sm:min-h-[132px] sm:p-4 transition-colors {{ $focus }}
                    {{ $viewingFlagged ? 'border-[#2A57B4] shadow-[0_0_0_1px_#2A57B4]' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60' }}">
            <span class="flex items-start justify-between gap-2">
                <span class="grid h-8 min-w-8 place-items-center rounded-lg bg-rose-50 px-1.5 text-sm font-semibold tabular-nums text-rose-800">{{ $flaggedCount }}</span>
                <i class="bx bx-bot text-xl text-slate-400" aria-hidden="true"></i>
            </span>
            <span class="mt-2.5 block text-sm font-semibold leading-tight text-slate-900 sm:mt-3 sm:text-[15px]">Flagged by AI</span>
            <span class="mt-0.5 block truncate text-xs text-slate-500">{{ $flaggedCount ? 'In To review, worth a closer look' : 'Nothing flagged in To review' }}</span>
            <span class="mt-auto hidden -space-x-1.5 pt-3 sm:flex" aria-hidden="true">
                @foreach ($flaggedFaces as $f)
                    <span class="relative grid h-6 w-6 place-items-center overflow-hidden rounded-full bg-rose-50 text-[10px] font-semibold text-rose-800 ring-2 ring-white">
                        {{ $initialsFor($f) }}
                        @if ($avatarFor($f))
                            <img src="{{ $avatarFor($f) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                        @endif
                    </span>
                @endforeach
            </span>
        </button>
    </nav>

    @if ($readyCount > 0 || $overdue->isNotEmpty())
        <div class="flex flex-wrap gap-2 text-sm">
            @if ($readyCount > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-emerald-800 ring-1 ring-emerald-200">
                    <i class="bx bx-check-circle text-base" aria-hidden="true"></i>
                    {{ $readyCount }} ready to finish in To review
                </span>
            @endif
            @if ($overdue->isNotEmpty())
                <span role="status" class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-3 py-1 text-rose-800 ring-1 ring-rose-200">
                    <i class="bx bx-error text-base" aria-hidden="true"></i>
                    {{ $overdue->count() }} waiting 7+ school days
                </span>
            @endif
        </div>
    @endif

    {{-- List and, when a row is selected, the panel beside it (a drawer on smaller screens) --}}
    <div class="{{ $panel ? '2xl:grid 2xl:grid-cols-[minmax(0,1fr)_420px] 2xl:items-start 2xl:gap-5' : '' }}">

        <div class="cq min-w-0 space-y-3">
            {{-- View bar: AI filter, sort, legend, bulk AI check --}}
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="-mx-4 flex min-w-0 items-center gap-1 overflow-x-auto px-4 [scrollbar-width:none] max-sm:w-[calc(100%+2rem)] sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden"
                     role="group" aria-label="Filter by AI check">
                    <span class="mr-1 inline-flex shrink-0 items-center gap-1 text-sm text-slate-500">
                        <i class="bx bx-bot text-base text-slate-400" aria-hidden="true"></i> AI check
                    </span>
                    @foreach ($aiFilters as $key => $label)
                        <button type="button" wire:click="setAi('{{ $key }}')" aria-pressed="{{ $ai === $key ? 'true' : 'false' }}"
                                class="inline-flex min-h-8 shrink-0 items-center whitespace-nowrap rounded-lg px-2.5 text-sm transition-colors [@media(pointer:coarse)]:min-h-11 {{ $focus }}
                                    {{ $ai === $key ? 'bg-[#e2e9f7] font-medium text-[#24499A]' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                <div class="flex w-full min-w-0 items-center gap-2 sm:w-auto">
                    @if ($uncheckedInTab > 0)
                        <button type="button" wire:click="checkUnchecked" wire:loading.attr="disabled" wire:target="checkUnchecked" class="{{ $btnSecondary }} shrink-0 !px-3">
                            <i class="bx bx-bot text-base text-[#2A57B4]" aria-hidden="true"></i>
                            <span class="sm:hidden">Check {{ $uncheckedInTab }}<span class="sr-only"> not checked</span></span>
                            <span class="hidden sm:inline">Check {{ $uncheckedInTab }} not checked</span>
                        </button>
                    @endif

                    @if (in_array($tab, \App\Livewire\Admin\Workflow\Index::FLAGGED_TABS, true))
                        <details class="relative shrink-0" x-data @click.outside="$el.removeAttribute('open')">
                            <summary class="inline-flex min-h-9 min-w-9 cursor-pointer list-none items-center justify-center gap-1 rounded-lg px-2 text-sm text-slate-600 hover:bg-slate-100 [@media(pointer:coarse)]:min-h-11 [@media(pointer:coarse)]:min-w-11 [&::-webkit-details-marker]:hidden {{ $focus }}">
                                <i class="bx bx-info-circle text-lg text-slate-400" aria-hidden="true"></i>
                                <span class="max-sm:sr-only">Waiting colours</span>
                            </summary>
                            <div class="absolute left-0 z-20 mt-1 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-lg sm:left-auto sm:right-0">
                                <p class="text-xs text-slate-500">Counted in school days.</p>
                                <ul class="mt-2 space-y-1.5 text-sm text-slate-700">
                                    @foreach ($legend as [$dot, $text])
                                        <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}" aria-hidden="true"></span>{{ $text }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </details>
                    @endif

                    <label for="sort-menu" class="sr-only">Sort by</label>
                    <select id="sort-menu" wire:model.live="sort" class="h-9 min-w-0 flex-1 py-0 sm:w-56 sm:flex-none [@media(pointer:coarse)]:h-11 {{ $field }}">
                        @foreach ($sortOptions as $value => $text)
                            <option value="{{ $value }}">{{ $text }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Table, once the list is wide enough --}}
            <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity cq-md:block"
                 wire:loading.class="opacity-60" wire:loading.attr="aria-busy"
                 wire:target="search, sort, setTab, setAi, showFlagged, sortBy, gotoPage, nextPage, previousPage">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs">
                        <tr>
                            <th scope="col" class="w-10 py-2.5 pl-4 pr-1">
                                @if ($pageIds->isNotEmpty())
                                    <input type="checkbox" aria-label="Select all on this page"
                                           @checked($pageIds->diff($picked)->isEmpty())
                                           x-on:change="$wire.set('picked', $event.target.checked ? @js($pageIds) : [])"
                                           class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                                @endif
                            </th>
                            <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" class="!py-2.5" />
                            <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Document</th>
                            <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" class="!py-2.5" />
                            <x-admin.sort-th key="risk" label="AI check" :sort="$sortKey" :dir="$sortDir" class="!py-2.5" />
                            <x-admin.sort-th key="waiting" label="Waiting" :sort="$sortKey" :dir="$sortDir" class="!py-2.5" />
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $doc = DocumentRegistry::find($row->document_type);
                                [$statusText, $statusClass, $statusIcon] = $statusFor($row, $tab);
                                $aiPill = $aiFor($row);
                                $isOpen = $selected === $row->workspace_id;
                            @endphp
                            <tr wire:key="row-{{ $row->workspace_id }}" wire:click="select({{ $row->workspace_id }})"
                                class="cursor-pointer transition-colors {{ $isOpen ? 'bg-[#f0f4fb]' : 'hover:bg-slate-50' }}">
                                <td class="py-3 pl-4 pr-1" wire:click.stop>
                                    <input type="checkbox" wire:model.live="picked" value="{{ $row->workspace_id }}"
                                           aria-label="Select {{ $nameFor($row) }}, {{ $row->transaction_code }}"
                                           class="h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="relative grid h-8 w-8 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">
                                            {{ $initialsFor($row) }}
                                            @if ($avatarFor($row))
                                                <img src="{{ $avatarFor($row) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                            @endif
                                        </span>
                                        <div class="min-w-0">
                                            <button type="button" wire:click.stop="select({{ $row->workspace_id }})"
                                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="wf-panel"
                                                    class="block max-w-[220px] truncate rounded text-left font-medium text-slate-900 hover:text-[#2A57B4] {{ $focus }}">
                                                {{ $nameFor($row) }}
                                            </button>
                                            @if ($metaFor($row) !== '')
                                                <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $metaFor($row) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="max-w-[240px] truncate text-slate-700" title="{{ $doc?->name() ?? $row->document_type }}">{{ $doc?->name() ?? $row->document_type }}</p>
                                    @if ($row->issuer)
                                        <p class="mt-0.5 max-w-[240px] truncate text-xs text-slate-500">Issued by {{ $row->issuer }}</p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-[13px] {{ $statusClass }}">
                                        <i class="bx {{ $statusIcon }} text-base" aria-hidden="true"></i>{{ $statusText }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($aiPill)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $aiPill[1] }}"
                                              @if (! empty($row->ai_findings['summary'])) title="{{ $row->ai_findings['summary'] }}" @endif>
                                            @if ($aiPill[2]) <i class="bx {{ $aiPill[2] }} text-sm" aria-hidden="true"></i> @endif
                                            {{ $aiPill[0] }}
                                        </span>
                                    @else
                                        <span class="text-xs text-slate-500">Filled in by staff</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center gap-2 {{ $waitStyle($row->waiting) }}">
                                        <span class="h-2 w-2 shrink-0 rounded-full {{ $row->waiting['dot'] }}" aria-hidden="true"></span>
                                        {{ $row->waiting['text'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">
                                    {{ trim($search) !== '' || $ai !== '' ? 'No match. Try another search or filter.' : 'Nothing waiting here.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Cards on narrow lists: tap to open the panel --}}
            <ul class="grid gap-3 transition-opacity cq-sm:grid-cols-2 cq-md:hidden" aria-label="{{ $tabs[$tab] }}"
                wire:loading.class="opacity-60" wire:loading.attr="aria-busy"
                wire:target="search, sort, setTab, setAi, showFlagged, sortBy, gotoPage, nextPage, previousPage">
                @forelse ($rows as $row)
                    @php
                        $doc = DocumentRegistry::find($row->document_type);
                        [$stage, $stageLabels, [$stageRing, $stageText]] = $stageFor($row);
                        $aiPill = $aiFor($row);
                        $isOpen = $selected === $row->workspace_id;
                    @endphp
                    <li wire:key="mcard-{{ $row->workspace_id }}">
                        <button type="button" wire:click="select({{ $row->workspace_id }})"
                                aria-expanded="{{ $isOpen ? 'true' : 'false' }}" aria-controls="wf-panel"
                                class="flex h-full w-full flex-col rounded-2xl border bg-white p-4 text-left transition-colors {{ $focus }}
                                    {{ $isOpen ? 'border-[#2A57B4] bg-[#f0f4fb]' : 'border-slate-200 active:bg-slate-50' }}">
                            <span class="flex w-full items-start gap-3">
                                <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[13px] font-semibold text-[#1E4090]" aria-hidden="true">
                                    {{ $initialsFor($row) }}
                                    @if ($avatarFor($row))
                                        <img src="{{ $avatarFor($row) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[15px] font-semibold leading-snug text-slate-900">{{ $nameFor($row) }}</span>
                                    @if ($metaFor($row) !== '')
                                        <span class="mt-0.5 block truncate text-xs tabular-nums text-slate-500">{{ $metaFor($row) }}</span>
                                    @endif
                                </span>
                                @if ($aiPill)
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 {{ $aiPill[1] }}">
                                        @if ($aiPill[2]) <i class="bx {{ $aiPill[2] }} text-xs" aria-hidden="true"></i> @endif
                                        <span class="sr-only">AI check:</span> {{ $aiPill[0] }}
                                    </span>
                                @endif
                            </span>

                            <span class="mt-3 block text-sm leading-snug text-slate-700">{{ $doc?->name() ?? $row->document_type }}</span>

                            <ol class="mt-4 grid w-full grid-cols-3" aria-label="Progress">
                                @foreach ($stageLabels as $i => $label)
                                    @php $state = $i < $stage ? 'done' : ($i === $stage ? 'current' : 'todo'); @endphp
                                    <li class="relative flex flex-col items-center text-center" @if ($state === 'current') aria-current="step" @endif>
                                        @if ($i > 0)
                                            <span class="absolute right-1/2 top-[7px] h-0.5 w-full {{ $i <= $stage ? 'bg-[#2A57B4]' : 'bg-slate-200' }}" aria-hidden="true"></span>
                                        @endif
                                        <span class="relative z-10 h-4 w-4 rounded-full {{ $state === 'done' ? 'bg-[#2A57B4]' : ($state === 'current' ? 'bg-white ring-[3px] ' . $stageRing : 'bg-white ring-2 ring-slate-200') }}" aria-hidden="true"></span>
                                        <span class="mt-1.5 px-0.5 text-[11px] leading-tight {{ $state === 'current' ? 'font-semibold ' . $stageText : 'text-slate-500' }}">
                                            <span class="sr-only">{{ $state === 'done' ? 'Done: ' : ($state === 'current' ? 'Now: ' : 'Next: ') }}</span>{{ $label }}
                                        </span>
                                    </li>
                                @endforeach
                            </ol>

                            <span class="mt-4 flex w-full items-center justify-between gap-3 border-t border-slate-100 pt-3 text-sm">
                                <span class="inline-flex items-center gap-2 {{ $waitStyle($row->waiting) }}">
                                    <span class="h-2 w-2 shrink-0 rounded-full {{ $row->waiting['dot'] }}" aria-hidden="true"></span>
                                    {{ $row->waiting['text'] }}
                                </span>
                                <span class="inline-flex items-center text-xs font-medium text-[#2A57B4]">Details <i class="bx bx-chevron-right text-base" aria-hidden="true"></i></span>
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500 cq-sm:col-span-2">
                        {{ trim($search) !== '' || $ai !== '' ? 'No match. Try another search or filter.' : 'Nothing waiting here.' }}
                    </li>
                @endforelse
            </ul>

            @if ($rows->hasPages())
                <div>{{ $rows->links() }}</div>
            @endif
        </div>

        {{-- Side panel --}}
        @if ($panel)
            @include('livewire.admin.workflow._panel', compact('panel', 'focus', 'btnPrimary', 'btnSecondary', 'btnDanger', 'statusFor', 'waitStyle', 'nameFor', 'initialsFor', 'avatarFor'))
        @endif
    </div>

    {{-- Bulk bar: appears once rows are ticked --}}
    @if (count($picked))
        <div class="pointer-events-none fixed inset-x-0 bottom-4 z-30 flex justify-center px-4">
            <div class="pointer-events-auto flex items-center gap-1 rounded-xl border border-slate-200 bg-white p-1.5 pl-3 shadow-lg" role="region" aria-label="Selected transactions">
                <span class="mr-2 text-sm font-medium tabular-nums text-slate-800" role="status">{{ count($picked) }} selected</span>
                <button type="button" wire:click="checkPicked" wire:loading.attr="disabled" wire:target="checkPicked"
                        class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-100 [@media(pointer:coarse)]:min-h-11 {{ $focus }}">
                    <i class="bx bx-bot text-base text-[#2A57B4]" aria-hidden="true"></i> Run AI check
                </button>
                @if (count($picked) === 1)
                    <a href="{{ route('admin.workflow.show', (int) reset($picked)) }}" wire:navigate
                       class="inline-flex min-h-9 items-center gap-1.5 rounded-lg px-3 text-sm font-medium text-slate-700 hover:bg-slate-100 [@media(pointer:coarse)]:min-h-11 {{ $focus }}">
                        Open record
                    </a>
                @endif
                <button type="button" wire:click="clearPicked" aria-label="Clear selection"
                        class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
                    <i class="bx bx-x text-xl" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    @endif

    {{-- Request changes / Reject, from the panel --}}
    @if ($decision && $panel)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="closeDecision" wire:click.self="closeDecision"
             role="dialog" aria-modal="true" aria-labelledby="decision-title" aria-describedby="decision-lead">
            <div class="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                 x-data x-init="$nextTick(() => $refs.msg.focus())">
                <h2 id="decision-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">
                    {{ $decision === 'request' ? 'Request changes' : 'Reject this transaction' }}
                </h2>
                <p id="decision-lead" class="mt-1 text-sm text-slate-500">
                    {{ $nameFor($panel['ws']) }} · {{ $panel['document']?->name() }}.
                    {{ $decision === 'request'
                        ? 'The student sees this message and can edit and resubmit.'
                        : 'The student sees this reason. Rejection is final; they would need to start a new transaction.' }}
                </p>

                <label for="decision-message" class="mt-4 block text-sm font-medium text-slate-800">
                    {{ $decision === 'request' ? 'What needs to change?' : 'Why is it being rejected?' }} <span class="text-rose-600" aria-hidden="true">*</span>
                </label>
                <textarea id="decision-message" x-ref="msg" wire:model="message" rows="5" maxlength="500" required
                          aria-describedby="decision-hint"
                          class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm"></textarea>
                <p id="decision-hint" class="mt-1 text-xs text-slate-500">
                    @if ($message !== '' && $message === ($panel['ws']->ai_findings['message'] ?? null))
                        Filled in from the AI suggestion. Read it and edit before sending.
                    @else
                        Up to 500 characters.
                    @endif
                </p>

                @error('action') <p class="mt-2 text-sm font-medium text-rose-700" role="alert">{{ $message }}</p> @enderror

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeDecision" class="{{ $btnSecondary }}">Cancel</button>
                    <button type="button" wire:click="submitDecision" wire:loading.attr="disabled" wire:target="submitDecision"
                            class="{{ $decision === 'reject' ? $btnBase . ' bg-rose-600 text-white shadow-sm hover:bg-rose-700' : $btnPrimary }}">
                        {{ $decision === 'request' ? 'Send request' : 'Reject transaction' }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
